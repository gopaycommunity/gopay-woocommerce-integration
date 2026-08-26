<?php
/**
 * Check requirements
 *
 * @package GoPay gateway
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check if plugin is active
 *
 * @param string $path Path to plugin file.
 */
function check_is_plugin_active( $path ): bool {
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( is_plugin_active( $path ) ) {
			return true;
		}
	} else {
		if ( in_array( $path, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			return true;
		}
	}

	return false;
}

// Check if WooCommerce is active.
if ( ! check_is_plugin_active('woocommerce/woocommerce.php') ) {
	$message = __(
		'GoPay gateway plugin requires WooCommerce to be active.',
		'gopay-gateway'
	);
	exit( esc_html( $message ) );
}

// Deactivate woocommerce gopay plugin if woocommerce is deactivated.
register_deactivation_hook(
	'woocommerce/woocommerce.php',
	'woocommerce_deactivate_dependents'
);

/**
 * When woocommerce is deactivated then deactivate GoPay gateway as well.
 */
function woocommerce_deactivate_dependents() {
	if ( check_is_plugin_active( GOPAY_GATEWAY_BASENAME ) ) {
		add_action(
			'update_option_active_plugins',
			'gopay_gateway_deactivation'
		);
	}
}

/**
 * GoPay gateway deactivation.
 */
function gopay_gateway_deactivation() {
	deactivate_plugins( GOPAY_GATEWAY_BASENAME );
}
