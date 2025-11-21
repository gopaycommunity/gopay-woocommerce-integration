<?php
/**
 * GoPay gateway subscriptions.
 * Deal with woocommerce subscriptions plugin.
 *
 * @package   GoPay gateway
 * @author    GoPay
 * @link      https://www.gopay.com/
 * @copyright 2022 GoPay
 * @since     1.0.0
 */

/**
 * GoPay subscriptions
 *
 * @since 1.0.0
 */
class Gopay_Gateway_Subscriptions {


	/**
	 * Constructor for the gateway
	 *
	 * @since  1.0.0
	 */
	public static function subscriptions_actions_filters() {
		// Disable multiple checkout option.
		add_action(
			'plugins_loaded',
			array(
				'Gopay_Gateway_Subscriptions',
				'disable_subscriptions_multiple_purchase',
			)
		);
		add_action(
			'update_option_woocommerce_subscriptions_multiple_purchase',
			array( 'Gopay_Gateway_Subscriptions', 'disable_subscriptions_multiple_purchase' )
		);
		add_action(
			'add_option_woocommerce_subscriptions_multiple_purchase',
			array( 'Gopay_Gateway_Subscriptions', 'disable_subscriptions_multiple_purchase' )
		);

		// When a subscription is added to the cart check if any other product/subscriptions was included.
		add_filter(
			'woocommerce_add_to_cart_validation',
			array( 'Gopay_Gateway_Subscriptions', 'subscriptions_check_add_to_cart' ),
			9,
			3
		);
		add_filter(
			'woocommerce_update_cart_validation',
			array( 'Gopay_Gateway_Subscriptions', 'subscriptions_check_cart_update' ),
			10,
			4
		);
		add_filter(
			'woocommerce_cart_redirect_after_error',
			array( 'Gopay_Gateway_Subscriptions', 'redirect_to_shop' )
		);

		// Process/Cancel subscription payments.
		add_action(
			'woocommerce_scheduled_subscription_payment_' . GOPAY_GATEWAY_ID,
			array( 'Gopay_Gateway_Subscriptions', 'process_subscription_payment' ),
			5,
			2
		);
		add_action(
			'woocommerce_subscription_status_updated',
			array( 'Gopay_Gateway_Subscriptions', 'cancel_subscription_payment' ),
			4,
			3
		);
	}

	/**
	 * When a subscription is added to the cart then check cart add
	 * Check if only one subscription was added to the cart
	 * without any other products/subscriptions
	 *
	 * @param bool $valid      is valid.
	 * @param int  $product_id product id.
	 * @param int  $quantity   quantity of the item.
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function subscriptions_check_add_to_cart( bool $valid, int $product_id, int $quantity ): bool {
		remove_filter(
			'woocommerce_add_to_cart_validation',
			array( 'WC_Subscriptions_Cart_Validator', 'maybe_empty_cart' )
		);

		if ( 0 !== WC()->cart->get_cart_contents_count() &&
			( WC_Subscriptions_Product::is_subscription( end( WC()->cart->cart_contents )['product_id'] ) ||
				WC_Subscriptions_Product::is_subscription( $product_id ) )
		) {
			wc_add_notice(
				__(
					'Products and subscriptions can not be purchased at the same time and only one subscription per checkout is possible.',
					'gopay-gateway'
				),
				'notice'
			);
			return false;
		}

		return true;
	}

	/**
	 * When a subscription is added to the cart then check cart update
	 * Check if only one subscription was added to the cart
	 *
	 * @param bool   $passed passed.
	 * @param string $cart_item_key cart item key.
	 * @param array  $values   values of the item.
	 * @param int    $quantity quantity of the item.
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function subscriptions_check_cart_update( bool $passed,
															string $cart_item_key, array $values, int $quantity ): bool {
		if ( $quantity > 1 ) {
			if ( WC_Subscriptions_Product::is_subscription( $values['product_id'] ) ) {
				wc_add_notice(
					__(
						'Only one recurring payment/subscription per checkout is possible',
						'gopay-gateway'
					),
					'notice'
				);
				return false;
			}
		}

		return true;
	}

	/**
	 * Redirect to the shop page if subscription was included
	 * into the cart with any other product/subscription
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public static function redirect_to_shop(): string {
		return get_permalink( wc_get_page_id( 'shop' ) );
	}

	/**
	 * Get subscription data from order
	 *
	 * @param object $order Order object.
	 *
	 * @return array|false|WC_Subscription
	 * @since  1.0.0
	 */
	public static function get_subscription_data( $order ) {
		$is_subscriptions_plugin_active = in_array(
			'woocommerce-subscriptions/woocommerce-subscriptions.php',
			apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
		);
		if ( $is_subscriptions_plugin_active ) {
			$order_id        = $order->get_id();
			$is_subscription = ( wcs_is_subscription( $order_id ) ||
				wcs_order_contains_subscription( $order_id ) ||
				wcs_order_contains_renewal( $order_id ) );

			if ( $is_subscription ) {
				$subscription    = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'any' ) );
				$subscription_id = json_decode( end( $subscription ) )->id;

				return wcs_get_subscription( $subscription_id );
			}
		}

		return array();
	}

	/**
	 * Get parent order
	 *
	 * @param object $order Order object.
	 *
	 * @return array|WC_Order|bool
	 * @since  1.0.0
	 */
	public static function get_parent_order( $order ) {
		$subscription = ( new Gopay_Gateway_Subscriptions() )->get_subscription_data( $order );

		if ( ! empty( $subscription ) ) {
			return $subscription->get_parent();
		}

		return array();
	}

	/**
	 * Is subscription present in the cart
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function cart_contains_subscription(): bool {
		// Check if WC function and cart exist
		if (!function_exists('WC') || is_null(WC()) || is_null(WC()->cart)) {
			return false;
		}

		if (class_exists('WC_Subscriptions_Cart')) {
			return WC_Subscriptions_Cart::cart_contains_subscription();
		}

		return false;
	}

	/**
	 * Process subscription payment when triggered
	 * by the action on the anniversary
	 * of the original purchase or when triggered
	 * off-schedule by 3rd party code or
	 * store manager actions
	 *
	 * @param float  $renewal_total Renewal total.
	 * @param object $renewal_order Renewal order object.
	 *
	 * @since  1.0.0
	 */
	public static function process_subscription_payment( float $renewal_total, $renewal_order ) {
		$renewal_order->update_status( 'pending' );
		$response = Gopay_Gateway_API::create_recurrence( $renewal_order );

		if ( 200 == $response->statusCode ) {
			$renewal_order->update_meta_data( 'GoPay_Transaction_id', $response->json['id'] );
		} else {
			$renewal_order->update_status( 'failed' );
		}
		$renewal_order->save();

		$log = array(
			'order_id'       => $renewal_order->get_id(),
			'transaction_id' => 200 == $response->statusCode ? $response->json['id'] : 0,
			'message'        => 200 == $response->statusCode ?
								'Recurrence of previously created payment executed' : 'Recurring payment error',
			'log_level'      => 200 == $response->statusCode ? 'INFO' : 'ERROR',
			'log'            => $response,
		);
		Gopay_Gateway_Log::insert_log( $log );
	}

	/**
	 * Cancel subscription payment when
	 * the status is changed
	 *
	 * @param object $subscription Subscriptions.
	 * @param string $new_status   New status.
	 * @param string $old_status   Old status.
	 *
	 * @since  1.0.0
	 */
	public static function cancel_subscription_payment( $subscription, string $new_status, string $old_status ) {
		$status_to_cancel = array( 'cancelled', 'expired', 'pending-cancel' );
		$gopay_status     = Gopay_Gateway_API::get_status( $subscription->get_parent()->get_id() );
		if ( in_array( $new_status, $status_to_cancel ) &&
			'STARTED' === $gopay_status->json['recurrence']['recurrence_state'] ) {
			$response = Gopay_Gateway_API::cancel_recurrence( $subscription );
			$status   = Gopay_Gateway_API::get_status( $subscription->get_parent()->get_id() );

			$order = $subscription->order;
			if ( 200 == $response->statusCode ) {
				$order->set_status( 'cancelled' );
				$order->save();
			} else {
				$subscription->set_status( 'on-hold' );
				$subscription->save();
			}

			$log = array(
				'order_id'       => $order->get_id(),
				'transaction_id' => 200 == $response->statusCode ? $response->json['id'] :
									( 200 == $status->statusCode ? $status->json['id'] : 0 ),
				'message'        => 200 == $response->statusCode ?
									'Recurrence of previously created payment cancelled' : 'Cancel recurrence error',
				'log_level'      => 200 == $response->statusCode ? 'INFO' : 'ERROR',
				'log'            => 200 != $response->statusCode ? $response : $status,
			);
			Gopay_Gateway_Log::insert_log( $log );
		}
	}

	/**
	 * Disable woocommerce subscriptions multiple purchase option
	 *
	 * @since  1.0.0
	 */
	public static function disable_subscriptions_multiple_purchase() {
		if ( ! get_option( WC_Subscriptions_Admin::$option_prefix . '_multiple_purchase' ) ||
			get_option( WC_Subscriptions_Admin::$option_prefix . '_multiple_purchase' ) === 'yes' ) {
			add_action( 'admin_notices', array( 'Gopay_Gateway_Subscriptions', 'admin_notice_error' ) );
			update_option( WC_Subscriptions_Admin::$option_prefix . '_multiple_purchase', 'no' );
		}
	}

	/**
	 * Show an error message about mixed checkout option was disabled
	 *
	 * @since  1.0.0
	 */
	public static function admin_notice_error() {
		$message = __(
			'GoPay gateway plugin requires WooCommerce Subscriptions Mixed Checkout option to be disabled.',
			'gopay-gateway'
		);
		echo wp_kses( '<div class="notice notice-error"><p>' . esc_attr( $message ) . '</p></div>',
		array( 'div' => array( 'class' => 1 ), 'p' => array() ) );
	}
}
