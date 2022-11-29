<?php

namespace banana\newsletters;

add_action( 'send_pending_newsletters', 'banana\newsletters\send_pending_newsletters' );

function send_pending_newsletters(): void {
	// Select oldest newsletter which has pending subscribers
	$newsletters_with_pending_subscribers = get_posts(
		array(
			'post_type'      => 'newsletter',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => 'pending-subscriber',
					'compare' => 'EXISTS',
				),
			),
		)
	);
	if ( ! isset( $newsletters_with_pending_subscribers[0] ) ) {
		return;
	}
	// Newsletters HTML template
	$newsletters_html_template           = get_option( 'newsletters-html-template' );
	$newsletters_html_template           = ! empty( $newsletters_html_template ) ? $newsletters_html_template : "{NEWSLETTER_TITLE}\n\n{NEWSLETTER_BODY}";
	$newsletters_html_body               = str_replace(
		array( '{NEWSLETTER_TITLE}', '{NEWSLETTER_BODY}' ),
		array(
			$newsletters_with_pending_subscribers[0]->post_title,
			apply_filters( 'the_content', $newsletters_with_pending_subscribers[0]->post_content ),
		),
		$newsletters_html_template
	);
	$newsletter_with_pending_subscribers = $newsletters_with_pending_subscribers[0];
	$pending_subscribers                 = get_post_meta( $newsletter_with_pending_subscribers->ID, 'pending-subscriber' );
	$sent_emails_counter                 = 0;
	foreach ( $pending_subscribers as $pending_subscriber ) {
		if ( $sent_emails_counter > 10 ) {
			break;
		}
		// Get email and secret key
		$subscriber_email = get_post_meta( $pending_subscriber, 'user_email', true );
		$secret_key       = get_post_meta( $pending_subscriber, 'secret_key', true );
		// Generate unsubscribe link
		$unsubscribe_link = get_site_url() . '/?unsubscribe-from-newsletter=' . $pending_subscriber . '&key=' . $secret_key;
		// Replace unsubscribe link in the HTML body
		$newsletters_html_body = str_replace( '{UNSUBSCRIBE_URL}', $unsubscribe_link, $newsletters_html_body );
		// Send email
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$mail    = wp_mail(
			$subscriber_email,
			$newsletters_with_pending_subscribers[0]->post_title,
			$newsletters_html_body,
			$headers
		);
		// Delete only if the email was sent successfully
		if ( $mail ) {
			delete_post_meta( $newsletter_with_pending_subscribers->ID, 'pending-subscriber', $pending_subscriber );
		}
		$sent_emails_counter ++;
	}
}
