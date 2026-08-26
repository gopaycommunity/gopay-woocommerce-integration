<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;


/**
 * WC_Gopay_Blocks_Support class.
 *
 * @extends AbstractPaymentMethodType
 */
final class WC_Gopay_Blocks_Support extends AbstractPaymentMethodType {
	private $gateway;
	protected $name = 'gopay';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
		$this->gateway = new Gopay_Gateway();
	}

	public function is_active() {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-gopay-blocks-integration',
			plugin_dir_url(__FILE__) . 'block/index.js',
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			GOPAY_WOOCOMMERCE_VERSION,
			true
		);

		wp_localize_script(
			'wc-gopay-blocks-integration',
			'gopayI18n',
			[
				'selectPaymentMethod' => __( 'Select GoPay Payment Methods...', 'gopay-gateway' ),
				'selectPaymentCard'   => __( 'Select payment card', 'gopay-gateway' ),
				'newCard'             => __( 'New Card', 'gopay-gateway' ),
				'saveCard'            => __( 'Save this payment card to my account for future purchases.', 'gopay-gateway' ),
				'gopay'               => __( 'GoPay', 'gopay-gateway' ),
			]
		);

		return [ 'wc-gopay-blocks-integration' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script client side.
	 *
	 * @return array
	 */
	public function get_supported_features() {
		$features = $this->gateway->supports;
		return $features;
	}

	public function get_payment_method_data(){
		$payment_methods_output = [];

		// Get users stored cards from API
		$saved_cards    = Gopay_Gateway_API::get_card_details();
		$checkout_cards = array_values( array_filter( $saved_cards, function( $c ) {
			return ( $c['status'] ?? 'ACTIVE' ) === 'ACTIVE';
		} ) );

		// Only supported by the currency.
		$supported_payment_methods = $this->gateway->get_option(
			'gopay_payment_methods_' . get_woocommerce_currency(),
			array()
		);
		$supported_banks = $this->gateway->get_option(
			'gopay_banks_' . get_woocommerce_currency(),
			array()
		);

		// All selected in the settings page.
		$selected_payment_methods = $this->gateway->get_option('enable_gopay_payment_methods', array());
		$selected_banks = $this->gateway->get_option('enable_banks', array());

		// Intersection of all selected and the supported by the currency.
		$payment_methods = array();
		if (is_array($selected_payment_methods)) {
			foreach ($selected_payment_methods as $method) {
				if (isset($supported_payment_methods[$method])) {
					$payment_methods[$method] = $supported_payment_methods[$method];
				}
			}
		}

		$banks = array_intersect_key((array) $supported_banks, array_flip((array) $selected_banks));

		// Check if subscription - only card payment is enabled.
		$cart_has_subscription = class_exists('Gopay_Gateway_Subscriptions') && Gopay_Gateway_Subscriptions::cart_contains_subscription();

		if ( $cart_has_subscription ) {
			$has_payment_card = array_key_exists('PAYMENT_CARD', (array) $payment_methods);
			if ( $has_payment_card ) {
				$payment_methods = array('PAYMENT_CARD' => $payment_methods['PAYMENT_CARD']);
			} else {
				$payment_methods = array();
			}
		}

		if (is_array($payment_methods)) {
			foreach ($payment_methods as $payment_method_code => $payment_method_details) {
				if ('BANK_ACCOUNT' === $payment_method_code && !$this->gateway->get_simplified_bank_selection()) {
					if (is_array($banks)) {
						foreach ($banks as $bank_code => $bank_details) {
							$payment_methods_output[] = [
								'id' => $bank_code,
								'label' => $bank_details['label'] ?? $bank_code,
								'image' => $bank_details['image'] ?? '',
							];
						}
					}
					continue;
				}

				$payment_methods_output[] = [
					'id' => $payment_method_code,
					'label' => $payment_method_details['label'] ?? $payment_method_code,
					'image' => $payment_method_details['image'] ?? '',
				];
			}
		}

		return [
			'title' => $this->get_setting('title'),
			'description' => $this->get_setting('description'),
			'supports' => $this->get_supported_features(),
			'paymentMethods' => $payment_methods_output,
			'savedCards' => $checkout_cards,
			'isTokenizeEnabled' => is_user_logged_in() && $this->gateway->get_option( 'card_token' ) === 'yes',
		];
	}
}
