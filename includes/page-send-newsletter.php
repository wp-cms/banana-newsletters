<?php

namespace banana\newsletters;

add_action( 'admin_menu', 'banana\newsletters\add_newsletter_page_to_admin_area' );

function add_newsletter_page_to_admin_area(): void {
    // Get required capability
	$current_capability_value = get_option( 'newsletters-required-capability' );
	$current_capability_value = ! empty( $current_capability_value ) ? $current_capability_value : 'update_core';
	add_menu_page(
		__( 'Send Newsletter', 'banana-newsletters' ),
		__( 'Send Newsletter', 'banana-newsletters' ),
		$current_capability_value,
		'send-newsletter',
		'banana\newsletters\render_newsletter_page',
		'dashicons-megaphone',
		3
	);
}

function render_newsletter_page(): void {

	// Flag to display form or not, default true
	$display_form = true;

	// Check if a newsletter has been created
	if (
		isset( $_POST['nonce_submit_newsletter'] ) &&
		wp_verify_nonce( $_POST['nonce_submit_newsletter'], basename( __FILE__ ) ) &&
		isset( $_POST['newsletter-submit'] )
	) {
		// Handle newsletter submission
		$insert_newsletter_args = array(
			'post_title'   => wp_strip_all_tags( $_POST['newsletter-title'] ),
			'post_content' => wp_kses_post( $_POST['newsletter-content'] ),
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'newsletter',
		);
		$newsletter_id          = wp_insert_post( $insert_newsletter_args );
		if ( ! is_wp_error( $newsletter_id ) ) {
			echo '<h1>' . esc_html__( 'Great! Newsletter is on its way.', 'banana-newsletters' ) . '</h1>';
			// Get list of all subscribers
			$all_subscribers = get_posts(
				array(
					'post_type'      => 'subscriber',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
				)
			);
			// Add as pending emails to the newsletter
			echo '<p>' . esc_html__( 'Subscribers added to sending queue:', 'banana-newsletters' ) . '</p>
            <ul>';
			foreach ( $all_subscribers as $subscriber ) {
				$subscriber_email = get_post_meta( $subscriber->ID, 'user_email', true );
				echo '<li>Subscriber #' . esc_html( $subscriber->ID . ' (' . $subscriber_email . ')' ) . '</li>';
				add_post_meta( $newsletter_id, 'pending-subscriber', $subscriber->ID );
			}
			echo '</ul>';
		}
		$display_form = false;
	}

	if ( ! $display_form ) {
		return;
	}
	?>
    <div class="wrap send-newsletter">
        <h1 class="send-newsletter__heading"><?php esc_html_e( 'Send Newsletter', 'banana-newsletters' ); ?></h1>
        <form method="post">
			<?php
			wp_nonce_field( basename( __FILE__ ), 'nonce_submit_newsletter' );
			?>
            <input required type="text" name="newsletter-title"
                   class="send-newsletter__input-text"
                   placeholder="<?php esc_attr_e( 'Newsletter title', 'banana-newsletters' ); ?>">
			<?php wp_editor( '', 'newsletter-content' ); ?>
            <div class="send-newsletter__input-submit">
                <input type="submit" name="newsletter-submit"
                       class="button button-primary"
                       value="<?php esc_attr_e( 'Send to all subscribers', 'banana-newsletters' ); ?>">
            </div>
        </form>
    </div>
	<?php
}
