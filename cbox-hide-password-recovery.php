<?php
/**
 * Plugin Name: Hide Password Recovery
 * Plugin URI: https://github.com/coderboxnet/cbox-hide-password-recovery
 * Description: Hide lost password link and disable the password recovery form.
 * Version: 0.1.1
 * Author: CODERBOX
 * Author URI: https://www.coderbox.net/
 *
 * @package CODERBX Plugins
 * @subpackage Hide Password Recovery
 */

/**
 * Remove lost password link
 *
 * @param string $translation Translated text.
 * @param string $text Text to translate.
 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
 * @return string Updated text.
 * @since 0.1.0 cbox_hide_lost_password_text( $text ) introduced
 * @since 0.1.2 Updated list of parameters and improved the translation validation.
 * @see https://developer.wordpress.org/reference/hooks/gettext/
 */
function cbox_hide_lost_password_text( $translation, $text, $domain ) {
	$allowed_domains = array( 'default' );
	if ( in_array( $domain, $allowed_domains, true ) ) {
		$lost_password_text = 'Lost your password?';

		if ( 0 === strcasecmp( $lost_password_text, $text ) ) {
			$translation = '';
		}
	}
	return $translation;
}
add_filter( 'gettext', 'cbox_hide_lost_password_text', 20, 3 );

/**
 * Use this function to retrieve the final url
 * of where to take the user if they try to access
 * the recovery url form.
 *
 * @param string $type (optional) The type of url to retrieve.
 * @return string The url
 * @see cbox_disable_recovery_password_form()
 * @see https://developer.wordpress.org/reference/functions/wp_login_url/
 * @see https://developer.wordpress.org/reference/functions/get_home_url/
 * @see https://developer.wordpress.org/reference/classes/wp_query/
 * @see https://developer.wordpress.org/reference/functions/get_permalink/
 * @see https://stackoverflow.com/questions/8672401/get-random-post-in-wordpress
 */
function cbox_get_redirect_url( $type = 'RANDOM_POST' ) {
	$url = '/'; // Initialize to the root.
	switch ( $type ) {
		case 'RANDOM_POST':
			// set arguments for WP_Query on published posts to get 1 at random.
			$args = array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'rand',
				'fields'         => 'ids',
			);

			// Execute the query.
			$query = new WP_Query( $args );

			// If have post get the permalink and update $url.
			if ( $query->have_posts() ) {
				$post = array_shift( $query->posts );
				$url  = get_permalink( $post );
			}
			break;
		case 'LOGIN':
			// Send the user back to the login form.
			$url = wp_login_url();
			break;
		default:
			// Redirect the user to the home page of the WordPress site.
			$url = get_home_url();
			break;
	}
	return $url;
}

/**
 * Disable password recovery form and
 * redirect the user to another url
 *
 * @since 0.1.0 cbox_disable_recovery_password_form() introduced
 * @uses cbox_get_redirect_url() Get the final redirection url
 * @see https://developer.wordpress.org/reference/functions/wp_safe_redirect/
 * @see https://developer.wordpress.org/reference/hooks/login_init/
 */
function cbox_disable_recovery_password_form() {
	$disabled_actions = array( 'lostpassword', 'retrievepassword' );
	if ( isset( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $action, $disabled_actions, true ) ) {
			$url = cbox_get_redirect_url();
			wp_safe_redirect( $url, 301 );
			exit;
		}
	}
}
add_action( 'login_init', 'cbox_disable_recovery_password_form' );
