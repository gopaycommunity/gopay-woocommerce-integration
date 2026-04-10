<?php

/**
 * The plugin bootstrap file
 *
 * @link                 https://www.gopay.com/
 * @since                1.0.0
 * @package              gopay-gateway
 * @wordpress-plugin
 * Plugin Name:          GoPay gateway
 * Plugin URI:           https://github.com/argo22packages/gopay-woocommerce-integration
 * Description:          WooCommerce and GoPay payment gateway integration
 * Version:              1.0.27
 * Author:               GoPay
 * Author URI:           https://www.gopay.com/
 * Text Domain:          gopay-gateway
 * License:              GPLv2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:          /languages
 * WC requires at least: 7.0.0
 * WC tested up to:      10.5.0
 * Requires Plugins:     woocommerce
 */

// If this file is called directly, abort.
// Preventing direct access to your WordPress.
if (!defined('WPINC')) {
	die();
}

/**
 * Plugin version.
 * Rename this and update it as you release new versions.
 */
define( 'GOPAY_WOOCOMMERCE_VERSION', '1.0.27' );

/**
 * Constants.
 */
define('GOPAY_GATEWAY_DOMAIN', 'gopay-gateway');
define('GOPAY_GATEWAY_ID', 'wc_gopay_gateway');
define('GOPAY_GATEWAY_FULLPATH', __FILE__);
define('GOPAY_GATEWAY_URL', plugin_dir_url(__FILE__));
define('GOPAY_GATEWAY_DIR', plugin_dir_path(__FILE__));
define('GOPAY_GATEWAY_BASENAME', plugin_basename(__FILE__));
define('GOPAY_GATEWAY_BASENAME_DIR', dirname(plugin_basename(__FILE__)));
define('GOPAY_GATEWAY_LOG_TABLE_NAME', 'gopay_gateway_log');

// Check requirements.
require GOPAY_GATEWAY_DIR .
	'check-requirements.php';

// Load files.
require_once GOPAY_GATEWAY_DIR .
	'vendor/autoload.php';
require_once GOPAY_GATEWAY_DIR .
	'admin/class-gopay-gateway-admin.php';
require_once GOPAY_GATEWAY_DIR .
	'includes/class-gopay-gateway-log.php';
require_once GOPAY_GATEWAY_DIR .
	'includes/class-gopay-gateway-options.php';
require_once GOPAY_GATEWAY_DIR .
	'includes/class-gopay-gateway-activator.php';
require_once GOPAY_GATEWAY_DIR .
	'includes/class-gopay-gateway-deactivator.php';
require_once GOPAY_GATEWAY_DIR .
	'includes/class-gopay-gateway-api.php';
require_once GOPAY_GATEWAY_DIR .
	'includes/class-gopay-gateway-subscriptions.php';
require_once GOPAY_GATEWAY_DIR .
	'includes/class-gopay-gateway.php';

// Register activation/deactivation hook.
register_activation_hook(__FILE__, array('Gopay_Gateway_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Gopay_Gateway_Deactivator', 'deactivate'));

// Check if Woocommerce GoPay Gateway was instantiated.
add_action('init', array('Gopay_Gateway', 'get_instance'));
// Load text domain for translations.
add_action('init', array('Gopay_Gateway', 'load_textdomain'), 99);


// Declaring HPOS compatibility
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
* Declare compatibility with cart_checkout_blocks feature 
*/
add_action( 'before_woocommerce_init', function() {
	if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
	}
} );

add_action( 'woocommerce_blocks_loaded', function() {
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		require_once dirname( __FILE__ ) . '/includes/class-gopay-wc-blocks-support.php';

		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new WC_Gopay_Blocks_Support );
			}
		);
	}
} );

// Settings section
add_action( 'admin_notices', 'gopay_review_banner' );

// Installed plugins section
add_action( 'after_plugin_row_gopay-woocommerce-integration/gopay-gateway.php', 'gopay_plugin_row_notice', 10, 2 );

add_action( 'admin_init', 'gopay_handle_review_dismiss' );

function gopay_get_review_data() {
	$defaults = [
		'count'		=> 0,
		'last_dismiss' => 0,
		'later_until'  => 0,
		'reviewed'	 => false,
	];
	return wp_parse_args( get_option( 'gopay_review_dismiss', [] ), $defaults );
}

function gopay_handle_review_dismiss() {
	$data = gopay_get_review_data();

	// Handle "No Thanks" case
	if ( isset( $_GET['gopay_review_dismiss'] ) ) {
		$data['count']++;
		$data['last_dismiss'] = time();
		$data['later_until']  = 0; // clear snooze
		update_option( 'gopay_review_dismiss', $data );

		wp_safe_redirect( remove_query_arg( [ 'gopay_review_dismiss' ] ) );
		exit;
	}

	// Handle "Remind me later" case
	if ( isset( $_GET['gopay_review_later'] ) ) {
		$data['later_until'] = time() + ( 14 * DAY_IN_SECONDS ); // snooze 14 days
		update_option( 'gopay_review_dismiss', $data );

		wp_safe_redirect( remove_query_arg( [ 'gopay_review_later' ] ) );
		exit;
	}

	// Handle "Leave a review" case
	if ( isset( $_GET['gopay_review_done'] ) ) {
		$data['reviewed'] = true;
		update_option( 'gopay_review_dismiss', $data );

		wp_redirect( 'https://wordpress.org/support/plugin/gopay-gateway/reviews/?filter=5#new-post' );
		exit;
	}
}

function check_review_availability(){
	$data = gopay_get_review_data();

	$now = time();

	// Already reviewed
	if ( ! empty( $data['reviewed'] ) ) {
		return false;
	}

	// Handle "Remind me later" delay
	if ( $data['later_until'] && $now < $data['later_until'] ) {
		return false;
	}

	// Handle "No Thanks" dismiss
	$three_months = 90 * DAY_IN_SECONDS;
	if ( $data['count'] >= 4 ) {
		return false;
	}

	if ( $data['last_dismiss'] && ( $now - $data['last_dismiss'] ) < $three_months ) {
		return false; // too soon
	}

	return true;
}

function gopay_review_banner() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || $screen->id !== 'woocommerce_page_wc-settings' ) {
		return;
	}

	if ( ! isset($_GET['section']) || $_GET['section'] !== 'wc_gopay_gateway' ) {
		return;
	}

	if (!check_review_availability()){
		return;
	}

	// URLs for actions
	$dismiss_url = add_query_arg( [ 'gopay_review_dismiss' => '1' ] );
	$later_url   = add_query_arg( [ 'gopay_review_later' => '1' ] );
	$review_url  = add_query_arg( [ 'gopay_review_done' => '1' ] );
	?>

	<div class="notice notice-info is-dismissible">
		<p>
			<span class="dashicons dashicons-star-filled" style="color:#f7b600;"></span>
			<span>
				If our plugin works well for you, consider leaving us a short review.
			</span>
			<span class="gopay-gateway-review-links"> 
				<a href="<?php echo esc_url( $dismiss_url ); ?>">No Thanks</a> /
				<a href="<?php echo esc_url( $review_url ); ?>" target="_blank">Leave a review</a> /
				<a href="<?php echo esc_url( $later_url ); ?>">Remind me later</a>
			</span>
		</p>
	</div>
	<?php
}

function gopay_plugin_row_notice() {
	if (!check_review_availability()){
		return;
	}

	$dismiss_url = add_query_arg( [ 'gopay_review_dismiss' => '1' ] );
	$later_url   = add_query_arg( [ 'gopay_review_later' => '1' ] );
	$review_url  = add_query_arg( [ 'gopay_review_done' => '1' ] );
	?>

	<tr class="plugin-update-tr active">
		<td colspan="4" class="plugin-update colspanchange">
			<div class="update-message notice inline notice-info notice-alt is-dismissible gopay-gateway-review-banner">
				<span class="dashicons dashicons-star-filled" style="color:#f7b600;"></span>
				<span>
					If our plugin works well for you, consider leaving us a short review.
				</span>
				<span class="gopay-gateway-review-links"> 
					<a href="<?php echo esc_url( $dismiss_url ); ?>">No Thanks</a> /
					<a href="<?php echo esc_url( $review_url ); ?>" target="_blank">Leave a review</a> /
					<a href="<?php echo esc_url( $later_url ); ?>">Remind me later</a>
				</span>
			</div>
		</td>
	</tr>
<?php
}
