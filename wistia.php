<?php
/**
 * Plugin Name:       Wistia
 * Description:       Adds a Wistia variation to the "Embed" Gutenberg block.
 * Requires at least: 5.9
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Himad Mouhtar
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wistia
 *
 * @package           wistia
 */

/**
 * Enqueue script responsible for registering a new Wistia embed block variation.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_variation
 */
function wistia_embed_variation_enqueue() {

	wp_enqueue_script(
		'wistia_embed_variation',
		plugins_url( '/assets/js/wistia-variation.js', __FILE__ ),
		array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-i18n' ),
		filemtime( plugin_dir_path( __FILE__ ) . '/assets/js/wistia-variation.js' ),
		false
	);

}

add_action( 'enqueue_block_editor_assets', 'wistia_embed_variation_enqueue' );

/**
 * Enqueue dependencies to manage Wistia video player, also wp-auth-check to show a login modal.
 * wp_auth_check_html() is used by WordPress to display a login modal when session expires. I'm reusing
 * this logic to display the same modal with custom conditions defined in wistia-variation-front.js.
 *
 * @see https://developer.wordpress.org/reference/functions/wp_auth_check_html/
 */
function wistia_embed_variation_enqueue_front() {
	if ( is_singular() ) {
		if ( has_block( 'core/embed' ) ) {
			$wp_post = get_post( get_the_ID() );
			$blocks  = parse_blocks( $wp_post->post_content );
			foreach ( (array) $blocks as $block ) {
				if ( isset( $block['attrs']['providerNameSlug'] )
				&& 'wistia-inc' === $block['attrs']['providerNameSlug']
				) {
					wp_register_script(
						'wistia-api',
						'https://fast.wistia.com/assets/external/E-v1.js',
						array(),
						false,
						false
					);

					wp_enqueue_script(
						'wistia-variation-front',
						plugins_url( '/assets/js/wistia-variation-front.js', __FILE__ ),
						array( 'wp-dom-ready', 'wistia-api', 'jquery' ),
						filemtime( plugin_dir_path( __FILE__ ) . '/assets/js/wistia-variation-front.js' ),
						false
					);

					wp_enqueue_style( 'wp-auth-check' );
					add_filter( 'login_url', 'add_wistia_login_param', 10, 3 );
					add_action( 'wp_print_footer_scripts', 'wp_auth_check_html', 5 );
				}
			}
		}
	}
}

add_action( 'wp_enqueue_scripts', 'wistia_embed_variation_enqueue_front' );

/**
 * Wistia API needs the oEmbed iframe to have the "wistia_embed" class to function correctly.
 *
 * @since 2.9.0
 *
 * @param string $return The returned oEmbed HTML.
 * @param object $data   A data object result from an oEmbed provider.
 * @param string $url    The URL of the content to be embedded.
 */
function add_wistia_embed_class( $return, $data, $url ) {
	$html = str_ireplace( 'wp-embedded-content', 'wp-embedded-content wistia_embed', $return );
	return $html;
}

add_filter( 'oembed_dataparse', 'add_wistia_embed_class', 11, 3 );

/**
 * Add a custom wistia-login param to the interim login form displayed in the modal. That param will be used
 * to add a custom notice.
 *
 * @since 2.8.0
 * @since 4.2.0 The `$force_reauth` parameter was added.
 *
 * @param string $login_url    The login URL. Not HTML-encoded.
 * @param string $redirect     The path to redirect to on login, if supplied.
 * @param bool   $force_reauth Whether to force reauthorization, even if a cookie is present.
 */
function add_wistia_login_param( $login_url, $redirect, $force_reauth ) {
	return add_query_arg(
		array( 'wistia-login' => '1' ),
		$login_url
	);
}

/**
 * Modify the modal default notice message for a custom one.
 *
 * @since 3.6.0
 *
 * @param WP_Error $errors      WP Error object.
 * @param string   $redirect_to Redirect destination URL.
 */
function add_wistia_custom_login_notice( $errors, $redirect_to ) {
	if ( isset( $_REQUEST['wistia-login'] ) ) {
		$errors->remove( 'expired' );
		$errors->add( 'wistia-login-required', __( 'Please log in to continue watching this video.' ), 'message' );
	}
	return $errors;
}

add_filter( 'wp_login_errors', 'add_wistia_custom_login_notice', 10, 2 );


/**
 * Set a custom cookie to identify when the user is logged in from the front end.
 *
 * @since 2.6.0
 * @since 4.9.0 The `$token` parameter was added.
 *
 * @param string $logged_in_cookie The logged-in cookie value.
 * @param int    $expire           The time the login grace period expires as a UNIX timestamp.
 *                                 Default is 12 hours past the cookie's expiration time.
 * @param int    $expiration       The time when the logged-in authentication cookie expires as a UNIX timestamp.
 *                                 Default is 14 days from now.
 * @param int    $user_id          User ID.
 * @param string $scheme           Authentication scheme. Default 'logged_in'.
 * @param string $token            User's session token to use for this cookie.
 */
function wistia_logged_in_cookie( $logged_in_cookie, $expire, $expiration, $user_id, $logged_in, $token ) {
	setcookie( 'wistia_logged_in', $user_id, $expire, COOKIEPATH, COOKIE_DOMAIN );
	return;
}

add_action( 'set_logged_in_cookie', 'wistia_logged_in_cookie', 10, 6 );

/**
 * Clear Wistia logged in user cookie.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_variation
 */
function wistia_clear_logged_in_cookie() {
	setcookie( 'wistia_logged_in', ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
}
add_action( 'clear_auth_cookie', 'wistia_clear_logged_in_cookie' );


/**
 * Insert demo page with a Wistia video.
 */
function wistia_plugin_demo_page() {
	wp_insert_post(
		array(
			'post_title'   => wp_strip_all_tags( 'Wistia Demo' ),
			'post_content' => '<!-- wp:embed {"url":"http://fast.wistia.com/embed/iframe/b0767e8ebb","type":"video","providerNameSlug":"wistia-inc","responsive":true,"className":"wistia_embed wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->
            <figure class="wp-block-embed is-type-video is-provider-wistia-inc wp-block-embed-wistia-inc wistia_embed wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">
            http://fast.wistia.com/embed/iframe/b0767e8ebb
            </div></figure>
            <!-- /wp:embed -->',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'page',
		)
	);
}

register_activation_hook( __FILE__, 'wistia_plugin_demo_page' );
