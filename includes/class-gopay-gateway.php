<?php
/**
 * GoPay gateway
 * Initialize the payment gateway between WooCommerce and GoPay
 *
 * @package   GoPay gateway
 * @author    GoPay
 * @link      https://www.gopay.com/
 * @copyright 2022 GoPay
 * @since     1.0.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', 'init_gopay_gateway_gateway' );

/**
 * Init plugin
 *
 * @since  1.0.0
 */
function init_gopay_gateway_gateway() {
	/**
	 * Plugin main class
	 *
	 * @since  1.0.0
	 */
	class Gopay_Gateway extends WC_Payment_Gateway {

		/**
		 * Instance of the class.
		 *
		 * @var object instance of the class.
		 * @since 1.0.0
		 */
		protected static $instance = null;
        private $enable_currencies;
        private $supported_languages;
        private $supported_countries;
        private $supported_shipping_methods;
        private $supported_payment_methods;
        private $supported_banks;
        private $iso2_to_iso3;
        private $goid;
        private $client_id;
        private $client_secret;
        private $test;
        private $instructions;
        private $simplified_bank_selection;
        private $payment_retry;
        private $enable_countries;
        private $virtual_products_skip_country;
        private $enable_gopay_payment_methods;
        private $enable_banks;
        private $enable_shipping_methods;
		private $card_token;

		/**
		 * Constructor for the gateway
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			$this->id                 = GOPAY_GATEWAY_ID;
			$this->icon               = apply_filters(
				'gopay_gateway_icon',
				GOPAY_GATEWAY_URL . 'includes/assets/images/gopay.png'
			);
			$this->has_fields         = false;
			$this->method_title       = __( 'GoPay payment gateway', 'gopay-gateway' );
			$this->method_description = __( 'Take payments via GoPay payment gateway.', 'gopay-gateway' );

			$this->enable_currencies          = Gopay_Gateway_Options::supported_currencies();
			$this->supported_languages        = Gopay_Gateway_Options::supported_languages();
			$this->supported_countries        = Gopay_Gateway_Options::supported_countries();
			$this->supported_shipping_methods = Gopay_Gateway_Options::supported_shipping_methods();
			$this->supported_payment_methods  = Gopay_Gateway_Options::supported_payment_methods();
			$this->supported_banks            = Gopay_Gateway_Options::supported_banks();
			$this->iso2_to_iso3               = Gopay_Gateway_Options::iso2_to_iso3();

			$this->init_form_fields();
			$this->init_settings();

			$this->title         = $this->get_option( 'title' );
			$this->description   = $this->get_option( 'description' );
			$this->goid          = $this->get_option( 'goid' );
			$this->client_id     = $this->get_option( 'client_id' );
			$this->client_secret = $this->get_option( 'client_secret' );
			$this->test          = ! $this->get_option( 'test' );
			$this->instructions  = $this->get_option( 'instructions' );

			$this->simplified_bank_selection     = $this->get_option( 'simplified_bank_selection' ) === 'yes';
			$this->payment_retry                 = $this->get_option( 'payment_retry' ) === 'yes';
			$this->enable_countries              = $this->get_option( 'enable_countries', array() );
			$this->virtual_products_skip_country = $this->get_option( 'virtual_products_skip_country' ) === 'yes';
			$this->enable_gopay_payment_methods  = $this->get_option( 'enable_gopay_payment_methods', array() );
			$this->enable_banks                  = $this->get_option( 'enable_banks', array() );
			$this->enable_shipping_methods       = $this->get_option( 'enable_shipping_methods', array() );
			$this->card_token                    = $this->get_option( 'card_token' ) === 'yes';

			$this->supports = array(
				'subscriptions',
				'products',
				'subscription_cancellation',
				'subscription_reactivation',
				'subscription_suspension',
				'subscription_amount_changes',
				'subscription_payment_method_change',
				'subscription_date_changes',
				'refunds',
				'pre-orders',
			);

			add_action( 'admin_init', array( $this, 'update_payment_methods' ), 1 );
			add_action( 'update_payment_methods_and_banks', array( $this, 'check_enabled_on_gopay' ), 1 );
			add_action( 'template_redirect', array( $this, 'check_status_gopay_redirect' ) );
			add_action( 'template_redirect', array( $this, 'check_payment_cards_access' ) );
			add_action( 'woocommerce_create_refund', array( $this, 'calculate_refund_amount' ), 10, 2 );
			add_action(
				'woocommerce_update_options_payment_gateways_' . $this->id,
				array(
					$this,
					'process_admin_options',
				)
			);
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );
			add_action( 'delete_user', array( $this, 'delete_user_logs' ), 10 );
			add_action( 'after_delete_post', array( $this, 'delete_order_logs' ), 10, 1 );
			add_action( 'woocommerce_thankyou', array( $this, 'thankyou_order_failed_text' ), 10, 1 );
			// AJAX callback
			add_action( 'wp_ajax_gopay_delete_card', [$this, 'delete_card_callback'] );

			add_filter(
				'woocommerce_thankyou_order_received_text',
				array( $this, 'thankyou_page' ),
				20,
				2
			);

			add_filter('woocommerce_account_menu_items', array( $this, 'gopay_add_payment_cards_tab' ));

			// Load Woocommerce GoPay gateway admin page.
			if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
				Gopay_Gateway_Admin_Menu::create_menu_actions();
			}

			// Check if WooCommerce Subscriptions is active.
			if ( check_is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
				Gopay_Gateway_Subscriptions::subscriptions_actions_filters();
			}
		}
		
		public function get_method_title() {
			return $this->method_title; 
		}

		public function get_method_description() {
			return $this->method_description;
		}

		public function get_simplified_bank_selection() {
			return $this->simplified_bank_selection;
		}

		/**
		 * Get Gopay_Gateway_Gateway instance if it exists
		 * or create a new one.
		 *
		 * @return Gopay_Gateway|null Instance
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( empty( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Load plugin textdomain
		 *
		 * @since 1.0.0
		 */
		public static function load_textdomain() {
			load_plugin_textdomain(
				'gopay-gateway',
				false,
				GOPAY_GATEWAY_BASENAME_DIR . '/languages'
			);
		}

		/**
		 * Update payment methods and banks
		 *
		 * @since 1.0.0
		 */
		public function update_payment_methods() {
			if ( empty( $this->settings['goid'] ) ||
				empty( $this->settings['test'] ) ) {
				$timestamp = wp_next_scheduled( 'update_payment_methods_and_banks' );
				wp_unschedule_event( $timestamp, 'update_payment_methods_and_banks' );

				return;
			}

			if ( ! wp_next_scheduled( 'update_payment_methods_and_banks' ) ) {
				wp_schedule_event( time(), 'daily', 'update_payment_methods_and_banks' );
			}
		}

		/**
		 * Check payment methods and banks that
		 * are enabled on GoPay account.
		 *
		 * @since 1.0.0
		 */
		public function check_enabled_on_gopay() {
			$payment_methods = array();
			$banks           = array();
			foreach ( Gopay_Gateway_Options::supported_currencies() as $currency => $value ) {
				$supported       = Gopay_Gateway_API::check_enabled_on_gopay( $currency );
				$payment_methods = $payment_methods + $supported[0];
				$banks           = $banks + $supported[1];

				$this->update_option( 'gopay_payment_methods_' . $currency, $supported[0] );
				$this->update_option( 'gopay_banks_' . $currency, $supported[1] );
			}

			if ( ! empty( $payment_methods ) ) {
				$this->update_option( 'option_gopay_payment_methods', $payment_methods );
			}
			if ( ! empty( $banks ) ) {
				if ( array_key_exists( 'OTHERS', $banks ) ) {
					// Send 'Others' to the end.
					$other = $banks['OTHERS'];
					unset( $banks['OTHERS'] );
					$banks['OTHERS'] = $other;
				}

				$this->update_option( 'option_gopay_banks', $banks );
			}
		}

		/**
		 * Extract payment methods/banks from
		 * list of supported
		 *
		 * @param array $supported either payment methods or banks.
		 * @return array
		 */
		private function extract_payment_methods( $supported ): array {
			foreach ( $supported as $key => $value ) {
				$supported[ $key ] = $value['label'] .
					( array_key_exists( 'country', $value ) ? ' ' . $value['country'] : '' );
			}

			return $supported;
		}

		/**
		 * Gateway Settings Form Fields in Admin.
		 *
		 * @since  1.0.0
		 */
		public function init_form_fields() {
			$this->init_settings();
			if ( empty( $this->settings['goid'] ) ||
				empty( $this->settings['client_id'] ) ||
				empty( $this->settings['client_secret'] ) ) {
				$this->update_option( 'enabled', 'no' );

				$this->form_fields = array(
					'enabled'       => array(
						'title'   => __( 'Enable/Disable', 'gopay-gateway' ),
						'type'    => 'checkbox',
						'label'   => __(
							'Inform goid, client id and secret to enable GoPay payment gateway and load the other options',
							'gopay-gateway'
						),
						'css'     => 'display: none;',
						'default' => 'no',
					),
					'client_id'     => array(
						'title'       => __( 'Client ID', 'gopay-gateway' ),
						'type'        => 'text',
						'description' => sprintf(
							/* translators: %1$s: opening HTML anchor tag for the "More information" link, %2$s: closing HTML anchor tag */
							__(
								'Enter your client ID, which can be found in your GoPay account settings. %1$sMore information%2$s.',
								'gopay-gateway'
							),
							'<a href="https://help.gopay.com/en/knowledge-base/gopay-account/' .
							'gopay-business-account/signing-in-password-reset-activating-and-deactivating' .
							'-the-payment-gateway/how-to-activate-the-payment-gateway">',
							'</a>'
						),
						'css'         => 'width: 500px;',
						'placeholder' => __( 'Insert Your GoPay Client ID...', 'gopay-gateway' ),
					),
					'client_secret' => array(
						'title'       => __( 'Client Secret', 'gopay-gateway' ),
						'type'        => 'text',
						'description' => sprintf(
							/* translators: %1$s: opening HTML anchor tag for the "More information" link, %2$s: closing HTML anchor tag */
							__(
								'Enter your Client Secret Token, which can be found in your GoPay account settings. %1$sMore information%2$s.',
								'gopay-gateway'
							),
							'<a href="https://help.gopay.com/en/knowledge-base/gopay-account/' .
							'gopay-business-account/signing-in-password-reset-activating-and-deactivating' .
							'-the-payment-gateway/how-to-activate-the-payment-gateway">',
							'</a>'
						),
						'css'         => 'width: 500px;',
						'placeholder' => __( 'Insert Your GoPay Client Secret Token...', 'gopay-gateway' ),
					),
					'goid'          => array(
						'title'       => __( 'GoID', 'gopay-gateway' ),
						'type'        => 'text',
						'description' => sprintf(
							/* translators: %1$s: opening HTML anchor tag for the "More information" link, %2$s: closing HTML anchor tag */
							__(
								'Enter your unique GoID, which can be found in your GoPay account settings. %1$sMore information%2$s.',
								'gopay-gateway'
							),
							'<a href="https://help.gopay.com/en/knowledge-base/gopay-account/' .
							'gopay-business-account/signing-in-password-reset-activating-and-deactivating' .
							'-the-payment-gateway/how-to-activate-the-payment-gateway">',
							'</a>'
						),
						'css'         => 'width: 500px;',
						'placeholder' => __( 'Insert Your GoID...', 'gopay-gateway' ),
					),
					'test'                             => array(
						'title'    => __( 'Test mode', 'gopay-gateway' ),
						'type'     => 'checkbox',
						'label'    => __(
							'Enable GoPay payment gateway test mode',
							'gopay-gateway'
						),
						'default'  => 'yes',
						'desc_tip' => true,
					),
				);
			}

			if ( ! empty( $this->settings['goid'] ) &&
				! empty( $this->settings['client_id'] ) &&
				! empty( $this->settings['client_secret'] ) ) {
				// Set default parameters.
				if ( empty( $this->settings['enabled'] ) ) {
					$this->update_option( 'enabled', 'yes' );
				}
				if ( empty( $this->settings['title'] ) ) {
					$this->update_option( 'title', 'GoPay' );
				}
				if ( empty( $this->settings['description'] ) ) {
					$this->update_option( 'description', 'Payment via GoPay gateway' );
				}
				if ( empty( $this->settings['test'] ) ) {
					$this->update_option( 'test', 'yes' );
				}
				// end.

				$this->form_fields = array(
					'enabled'                          => array(
						'title'   => __( 'Enable/Disable', 'gopay-gateway' ),
						'type'    => 'checkbox',
						'label'   => __(
							'Enable GoPay payment gateway',
							'gopay-gateway'
						),
						'default' => 'yes',
					),
					'title'                            => array(
						'title'       => __( 'Title', 'gopay-gateway' ),
						'type'        => 'text',
						'description' => __(
							'Name of the payment method that is displayed at the checkout',
							'gopay-gateway'
						),
						'default'     => __( 'GoPay', 'gopay-gateway' ),
						'css'         => 'width: 500px;',
						'desc_tip'    => true,
						'placeholder' => __( 'Insert Payment Title...', 'gopay-gateway' ),
					),
					'description'                      => array(
						'title'       => __( 'Description', 'gopay-gateway' ),
						'type'        => 'textarea',
						'description' => __(
							'Description of the payment method that is displayed at the checkout',
							'gopay-gateway'
						),
						'default'     => __(
							'Payment via GoPay gateway',
							'gopay-gateway'
						),
						'css'         => 'width: 500px; min-height: 100px;',
						'desc_tip'    => true,
						'placeholder' => __( 'Insert Description...', 'gopay-gateway' ),
					),
					'client_id'                        => array(
						'title'       => __( 'Client ID', 'gopay-gateway' ),
						'type'        => 'text',
						'description' => sprintf(
							/* translators: %1$s: opening HTML anchor tag for the "More information" link, %2$s: closing HTML anchor tag */
							__(
								'Enter your client ID, which can be found in your GoPay account settings. %1$sMore information%2$s.',
								'gopay-gateway'
							),
							'<a href="https://help.gopay.com/en/knowledge-base/gopay-account/' .
							'gopay-business-account/signing-in-password-reset-activating-and-deactivating' .
							'-the-payment-gateway/how-to-activate-the-payment-gateway">',
							'</a>'
						),
						'css'         => 'width: 500px;',
						'placeholder' => __( 'Insert Your GoPay Client ID...', 'gopay-gateway' ),
					),
					'client_secret'                    => array(
						'title'       => __( 'Client Secret', 'gopay-gateway' ),
						'type'        => 'text',
						'description' => sprintf(
							/* translators: %1$s: opening HTML anchor tag for the "More information" link, %2$s: closing HTML anchor tag */
							__(
								'Enter your Client Secret Token, which can be found in your GoPay account settings. %1$sMore information%2$s.',
								'gopay-gateway'
							),
							'<a href="https://help.gopay.com/en/knowledge-base/gopay-account/' .
							'gopay-business-account/signing-in-password-reset-activating-and-deactivating' .
							'-the-payment-gateway/how-to-activate-the-payment-gateway">',
							'</a>'
						),
						'css'         => 'width: 500px;',
						'placeholder' => __( 'Insert Your GoPay Client Secret Token...', 'gopay-gateway' ),
					),
					'goid'                             => array(
						'title'       => __( 'GoID', 'gopay-gateway' ),
						'type'        => 'text',
						'description' => sprintf(
							/* translators: %1$s: opening HTML anchor tag for the "More information" link, %2$s: closing HTML anchor tag */
							__(
								'Enter your unique GoID, which can be found in your GoPay account settings. %1$sMore information%2$s.',
								'gopay-gateway'
							),
							'<a href="https://help.gopay.com/en/knowledge-base/gopay-account/' .
							'gopay-business-account/signing-in-password-reset-activating-and-deactivating' .
							'-the-payment-gateway/how-to-activate-the-payment-gateway">',
							'</a>'
						),
						'css'         => 'width: 500px;',
						'placeholder' => __( 'Insert Your GoID...', 'gopay-gateway' ),
					),
					'test'                             => array(
						'title'    => __( 'Test mode', 'gopay-gateway' ),
						'type'     => 'checkbox',
						'label'    => __(
							'Enable GoPay payment gateway test mode',
							'gopay-gateway'
						),
						'default'  => 'yes',
						'desc_tip' => true,
					),
					'default_language_gopay_interface' => array(
						'title'       => __( 'Default Language', 'gopay-gateway' ),
						'type'        => 'select',
						'class'       => 'chosen_select',
						'options'     => $this->supported_languages,
						'description' => __(
							'Default language is used when a customer of an e-commerce site is from a country whose language is not supported.',
							'gopay-gateway'
						),
						'desc_tip'    => true,
						'default'     => 'EN',
						'css'         => 'width: 500px; min-height: 50px;',
						'placeholder' => __( 'Select Default Language...', 'gopay-gateway' ),
					),
					'enable_shipping_methods'          => array(
						'title'       => __( 'Enable Shipping Methods', 'gopay-gateway' ),
						'type'        => 'multiselect',
						'class'       => 'chosen_select',
						'options'     => $this->supported_shipping_methods,
						'description' => __(
							'Enable the GoPay payment gateway only for the selected WooCommerce shipping methods.',
							'gopay-gateway'
						),
						'desc_tip'    => true,
						'css'         => 'width: 500px; min-height: 50px;',
						'placeholder' => __( 'Select Shipping Methods...', 'gopay-gateway' ),
					),
					'enable_countries'                 => array(
						'title'       => __( 'Enable Countries', 'gopay-gateway' ),
						'type'        => 'multiselect',
						'class'       => 'chosen_select',
						'options'     => $this->supported_countries,
						'description' => __(
							'Enable the GoPay payment gateway only for the selected countries.',
							'gopay-gateway'
						),
						'desc_tip'    => true,
						'css'         => 'width: 500px; min-height: 50px;',
						'placeholder' => __( 'Select Available Countries...', 'gopay-gateway' ),
					),
					'virtual_products_skip_country'  => array(
						'title'       => __( 'Skip Country Restriction', 'gopay-gateway' ),
						'type'        => 'checkbox',
						'label'       => __(
							'Skip the country restriction for virtual downloadable products.',
							'gopay-gateway'
						),
						'default'     => 'no',
						'description' => __(
							'When enabled, the country restriction is ignored if all products in the cart are both virtual and downloadable.',
							'gopay-gateway'
						),
						'desc_tip'    => true,
					),
					'simplified_bank_selection'        => array(
						'title'       => __( 'Bank Selection', 'gopay-gateway' ),
						'type'        => 'checkbox',
						'label'       => __(
							'Enable simplified bank selection',
							'gopay-gateway'
						),
						'description' => __(
							'If enabled, customers cannot choose any specific bank at the checkout, they are grouped into one “Bank account” option, but they have to select the bank once the GoPay payment gateway is invoked.',
							'gopay-gateway'
						),
						'desc_tip'    => true,
					),
					'enable_gopay_payment_methods'     => array(
						'title'       => __(
							'Enable GoPay Payment Methods',
							'gopay-gateway'
						),
						'type'        => 'multiselect',
						'class'       => 'chosen_select',
						'options'     => $this->extract_payment_methods( $this->supported_payment_methods ),
						'description' => __(
							'Enable only the selected payment methods on the GoPay payment gateway .',
							'gopay-gateway'
						),
						'desc_tip'    => true,
						'css'         => 'width: 500px; min-height: 50px;',
						'placeholder' => __( 'Select GoPay Payment Methods...', 'gopay-gateway' ),
					),
					'enable_banks'                     => array(
						'title'       => __( 'Enable Banks', 'gopay-gateway' ),
						'type'        => 'multiselect',
						'class'       => 'chosen_select',
						'options'     => $this->extract_payment_methods( $this->supported_banks ),
						'description' => __(
							'Enable only the selected banks on the GoPay payment gateway .',
							'gopay-gateway'
						),
						'desc_tip'    => true,
						'css'         => 'width: 500px; min-height: 50px;',
						'placeholder' => __( 'Select Available Banks...', 'gopay-gateway' ),
					),
					'payment_retry'                    => array(
						'title'       => __(
							'Retry Payment Method',
							'gopay-gateway'
						),
						'type'        => 'checkbox',
						'label'       => __(
							'Enable payment retry using the same payment method',
							'gopay-gateway'
						),
						'description' => __(
							'If enabled, payment retry of a failed payment will be done using the same payment method that was selected when customer was placing an order.',
							'gopay-gateway'
						),
						'desc_tip'    => true,
					),
					'card_token'                    => array(
						'title'       => __( 'Pay with a saved card', 'gopay-gateway' ),
						'type'        => 'checkbox',
						'label' 	  => __( 'Enable customers to save payment cards', 'gopay-gateway' ) . '<br>' .
							'<div class="gopay-setting-warning">' .
							'<span class="dashicons dashicons-warning"></span>' .
							__( 'Enable this option only if card saving / tokenization is active for your GoPay merchant account.', 'gopay-gateway' ) . '</div>' .
							'<div class="gopay-setting-warning">' .
							__( 'If your account does not support this feature, checkout may fail and payments may become unavailable.', 'gopay-gateway' ) . '</div>',
						'description' => __( 'When a customer pays by card, you can offer to save the card. In that case, a card token will be generated and can be used for future payments. If used, the customer will not have to enter the card details manually again.', 'gopay-gateway' ),
						'desc_tip'    => true,
						'default'  => 'no',
					),
				);
			}
		}

		/**
		 * Is the gateway available based on the restrictions
		 * of countries and shipping methods.
		 *
		 * @return bool
		 * @since  1.0.0
		 */
		public function is_available(): bool {

			// Inline.
			$get = wp_unslash( $_GET );
			if ( ! empty( $get['gopay_url'] ) && ! empty( $get['_wpnonce'] ) &&
				wp_verify_nonce( $get['_wpnonce'], 'gw_url' ) ) {

				$gateway_url = esc_js( esc_url_raw( $get['gopay_url'] ) );

				$inline_js = "
				(function(){
					// prevent double-init across multiple inline injections
					if (window.__gopay_init_done) return;

					function initGoPay(){
						if (window.__gopay_init_done) return;

						if (typeof _gopay !== 'undefined' && typeof _gopay.checkout === 'function' && document.body) {
							try {
								_gopay.checkout({gatewayUrl: \"{$gateway_url}\", inline: true});
							} catch (e) {
								if (window.console && console.error) console.error('gopay checkout error', e);
							}
							window.__gopay_init_done = true;
							return;
						}

						setTimeout(initGoPay, 120);
					}

					// run after DOM is at least parsed
					if (document.readyState === 'loading') {
						document.addEventListener('DOMContentLoaded', initGoPay);
					} else {
						initGoPay();
					}
				})();
				";

				// Attach to the SDK handle so WP prints it immediately
				add_action( 'wp_enqueue_scripts', function() use ( $inline_js ) {
					wp_add_inline_script( 'gopay-gateway-inline-scripts', $inline_js );
				} );

				//  Fallback - print in footer for themes that handle enqueues unexpectedly.
				//	Late priority - after most other footer work.
				add_action( 'wp_footer', function() use ( $inline_js ) {
					wp_print_inline_script_tag( $inline_js, array( 'id' => 'gopay-inline-fallback' ) );
				}, 999 );
			}
			// end Inline.

			if ( ! empty( WC()->customer ) ) {
				// Check if all products are virtual and/or downloadable.
				$all_virtual_downloadable = true;
				$all_virtual              = true;

				foreach ( WC()->cart->get_cart() as $item ) {
					$product = $item['data'];
					if ( ! $product->is_virtual() ) {
						$all_virtual = false;
					}
					if ( ! $product->is_virtual() || ! $product->is_downloadable() ) {
						$all_virtual_downloadable = false;
					}

					if ( ! $all_virtual && ! $all_virtual_downloadable ) {
						break;
					}
				}
				// end check virtual or downloadable.

				// Check countries.
				$billing_country = WC()
					->cart->get_customer()
					->get_billing_country();

				$skip_country_check = $this->virtual_products_skip_country && $all_virtual_downloadable;

				if ( ! $skip_country_check ) {
					if ( empty( $this->enable_countries ) || empty( $billing_country ) ||
						! in_array( $billing_country, (array) $this->enable_countries, true ) ) {
						return false;
					}
				}
				// end check countries.

				// Check currency matches one of the supported currencies.
				if ( ! get_woocommerce_currency() || ! array_key_exists(
					get_woocommerce_currency(),
					$this->enable_currencies
				) ) {
					return false;
				}
				// end check currency.

				if ( $all_virtual_downloadable || $all_virtual ) {
					return parent::is_available();
				}

				// Check shipping methods.
				if ( is_page( wc_get_page_id( 'checkout' ) ) &&
					! empty( get_query_var( 'order-pay' ) )
				) {
					$order_id = absint( get_query_var( 'order-pay' ) );
					$order    = wc_get_order( $order_id );

					$items_shipping = $order->get_items( 'shipping' );
					foreach ( $items_shipping as $item_shipping ) {
						if ( ! in_array(
							$item_shipping->get_method_title(),
							(array) $this->enable_shipping_methods,
							true
						)
						) {
							return false;
						}
					}
				} else {
					$chosen_rates   = (array) WC()->session->get( 'chosen_shipping_methods' );
					$rate_to_method = $this->get_shipping_rate_method_map( array_keys( $chosen_rates ) );

					$chosen_shipping_methods = array();
					foreach ( $chosen_rates as $key => $value ) {
						if ( ! is_string( $value ) || '' === $value ) {
							continue;
						}

						// Fallback to rate ID prefix when the rate is not among the known.
						$method_id = $rate_to_method[ $value ] ?? current( explode( ':', $value ) );

						$chosen_shipping_methods[ $key ] = $this->resolve_shipping_method_id( $method_id );
					}

					if ( empty( $chosen_shipping_methods ) ||
						array_diff( $chosen_shipping_methods, (array) $this->enable_shipping_methods )
					) {
						return false;
					}
				}
				// end check shipping methods.
			}

			return parent::is_available();
		}

		/**
		 * Map shipping rate IDs to the class ID of the shipping method that created them.
		 *
		 * @param array $package_keys Package keys to read the cached rates for.
		 * @return array<string, string> Rate ID => shipping method class ID.
		 */
		private function get_shipping_rate_method_map( array $package_keys ): array {
			$rate_to_method = array();

			foreach ( WC()->shipping()->get_packages() as $package ) {
				foreach ( $package['rates'] ?? array() as $rate_id => $rate ) {
					$rate_to_method[ $rate_id ] = $rate->get_method_id();
				}
			}

			if ( ! empty( $rate_to_method ) || empty( WC()->session ) ) {
				return $rate_to_method;
			}

			foreach ( $package_keys as $package_key ) {
				$stored_rates = WC()->session->get( 'shipping_for_package_' . $package_key );

				if ( empty( $stored_rates['rates'] ) || ! is_array( $stored_rates['rates'] ) ) {
					continue;
				}

				foreach ( $stored_rates['rates'] as $rate_id => $rate ) {
					if ( $rate instanceof WC_Shipping_Rate ) {
						$rate_to_method[ $rate_id ] = $rate->get_method_id();
					}
				}
			}

			return $rate_to_method;
		}

		/**
		 * Resolve a method class ID to the method class ID stored in GoPay settings.
		 *
		 * @param string $method_id Method class ID from WC_Shipping_Rate::get_method_id().
		 * @return string Method class ID matched against settings, or original if not found.
		 */
		private function resolve_shipping_method_id( string $method_id ): string {
			if ( in_array( $method_id, (array) $this->enable_shipping_methods, true ) ) {
				return $method_id;
			}

			foreach ( (array) $this->enable_shipping_methods as $enabled_id ) {
				$suffix_pos = strrpos( $enabled_id, '_' );
				if ( false !== $suffix_pos && substr( $enabled_id, 0, $suffix_pos ) === $method_id ) {
					return $enabled_id;
				}
			}

			return $method_id;
		}

		/**
		 * Extract banks by country code
		 *
		 * @since  1.0.0
		 */
		public function extract_banks_by_country() {
			$supported_banks = array();
			if ( ! empty( WC()->customer->get_billing_country() ) ) {

				$country = WC()->customer->get_billing_country();
				foreach ( Gopay_Gateway_Options::supported_banks() as $swift => $value ) {
					if ( $country === $value['country'] || '' === $value['country'] ) {
						$supported_banks[ $swift ] = $value;
					}
				}
			}

			return $supported_banks;
		}

		/**
		 * Payment fields.
		 *
		 * @since  1.0.0
		 */
		public function payment_fields() {
			echo wp_kses( wpautop( wptexturize( $this->description ) ), wp_kses_allowed_html() );

			$enabled_payment_methods = '';
			$checked                 = 'checked="checked"';
			$payment_retry           = ( $this->payment_retry &&
				is_page( wc_get_page_id( 'checkout' ) ) &&
				! empty( get_query_var( 'order-pay' ) ) );
			if ( ! $payment_retry ) {
				// Only supported by the currency.
				$supported_payment_methods = $this->get_option(
					'gopay_payment_methods_' . get_woocommerce_currency(),
					array()
				);
				$supported_banks           = $this->get_option(
					'gopay_banks_' . get_woocommerce_currency(),
					array()
				);

				// All selected in the settings page.
				$selected_payment_methods = $this->get_option( 'enable_gopay_payment_methods', array() );
				$selected_banks           = $this->get_option( 'enable_banks', array() );

				// Intersection of all selected and the supported by the currency.
				$payment_methods = array();
				foreach ( $selected_payment_methods as $method ) {
					if ( isset($supported_payment_methods[$method]) ) {
						$payment_methods[$method] = $supported_payment_methods[$method];
					}
				}
				$banks           = array_intersect_key( $supported_banks, array_flip( $selected_banks ) );

				// Check if subscription - only card payment is enabled.
				if ( Gopay_Gateway_Subscriptions::cart_contains_subscription() ) {
					if ( array_key_exists( 'PAYMENT_CARD', (array) $payment_methods ) ) {
						$payment_methods = array( 'PAYMENT_CARD' => $payment_methods['PAYMENT_CARD'] );
					} else {
						$payment_methods = array();
					}
				}

				$input =
					'
					<div class="payment_method_' . GOPAY_GATEWAY_ID . '_selection" name="%s">
					<div>
					    <input class="payment_method_' . GOPAY_GATEWAY_ID .
						'_input" name="gopay_payment_method" type="radio" id="%s" value="%s" %s />
					    <span>%s</span>
					</div>
					<img src="%s" alt="ico" style="max-height: 60px; height: fit-content; width: auto; margin-left: auto;"/>
					</div>';

				foreach ( $payment_methods as $payment_method => $payment_method_label_image ) {
					if ( 'BANK_ACCOUNT' === $payment_method ) {
						if ( ! $this->simplified_bank_selection ) {
							foreach ( $banks as $bank => $bank_label_image ) {
								$span = $bank_label_image['label'];
								$img  = array_key_exists( 'image', $bank_label_image ) ?
									$bank_label_image['image'] : '';

								$enabled_payment_methods .= sprintf(
									$input,
									$payment_method,
									$payment_method,
									$bank,
									$checked,
									$span,
									$img
								);
							}
							continue;
						}
					}

					$span = $payment_method_label_image['label'];
					$img  = array_key_exists( 'image', $payment_method_label_image ) ?
						$payment_method_label_image['image'] : '';

					// Add tokenize section
					if ( 'PAYMENT_CARD' === $payment_method ) {
						if ( $this->card_token && is_user_logged_in() ) {
							$saved_cards    = Gopay_Gateway_API::get_card_details();
							$checkout_cards = array_filter( $saved_cards, function( $c ) {
								return ( $c['status'] ?? 'ACTIVE' ) === 'ACTIVE';
							} );

							$enabled_payment_methods .= '
								<div class="payment_wc_tokenize_container" name="' . esc_attr( $payment_method ) . '">
								<div class="payment_card_with_tokenize">
									<input
										class="payment_method_' . GOPAY_GATEWAY_ID . '_input"
										name="gopay_payment_method"
										type="radio"
										id="' . esc_attr( $payment_method ) . '"
										value="' . esc_attr( $payment_method ) . '"
										' . $checked . '
									/>
									<span>' . esc_html__( 'Payment card', 'gopay-gateway' ) . '</span>
									<img src="' . esc_url( $img ) . '" alt="ico" style="max-height: 60px; height: auto; width: auto; margin-left: auto;" />
								</div>';

							$enabled_payment_methods .= '
								<div class="card_selection_container" id="card_selection_container">';

							if ( ! empty( $checkout_cards ) ) {
								$enabled_payment_methods .= '
									<div class="gopay-card-list" id="gopay-card-list">
										<div class="gopay-card-list__title">
											' . esc_html__( 'Select payment card', 'gopay-gateway' ) . '
										</div>
										<div class="gopay-card-options">';

								$is_first_card = true;
								foreach ( $checkout_cards as $card ) {
									$card_art = ! empty( $card['card_art_url'] )
										? '<img src="' . esc_url( $card['card_art_url'] ) . '" class="gopay-card-option__art" />'
										: '';

									$enabled_payment_methods .= sprintf(
										'<label class="gopay-card-option" style="margin-bottom: 0px">
											<input type="radio" name="saved_card" value="%s"%s />
											<div class="gopay-card-option__content">
												%s
												<div class="gopay-card-option__details">
													<span class="gopay-card-option__brand">%s</span>
													<span class="gopay-card-option__number">%s</span>
													<span class="gopay-card-option__expiry">(exp %s)</span>
												</div>
											</div>
										</label>',
										esc_attr( $card['card_id'] ),
										$is_first_card ? ' checked' : '',
										$card_art,
										esc_html( $card['card_brand'] ),
										esc_html( $card['real_masked_pan'] ),
										esc_html( $card['card_expiration'] )
									);
									$is_first_card = false;
								}

								// New Card Option
								$enabled_payment_methods .= '
											<label class="gopay-card-option is-new">
												<input type="radio" name="saved_card" value="new" />
												<div class="gopay-card-option__content">
													<div class="gopay-card-option__details">
														<span class="gopay-card-option__brand">' . esc_html__( 'New Card', 'gopay-gateway' ) . '</span>
													</div>
												</div>
											</label>';

								$enabled_payment_methods .= '
										</div>
									</div>';
							}

							$enabled_payment_methods .= '
									<div class="payment_wc_store_token" id="payment_wc_store_token">
										<input type="checkbox" id="request_card_token" name="request_card_token" value="1" />
										<label for="request_card_token">'. esc_html__( 'Save this payment card to my account for future purchases.', 'gopay-gateway' ) . '</label>
									</div>
								</div>

							</div>
							';

							$checked = '';
							continue;
						}
					}

					$enabled_payment_methods .= sprintf(
						$input,
						$payment_method,
						$payment_method,
						$payment_method,
						$checked,
						$span,
						$img
					);

					$checked = '';
				}
			}

			// Check if Apple pay is available.
			?>
			<script>
				var applePayAvailable = false;
				if(window.ApplePaySession && window.ApplePaySession.canMakePayments()) {
					applePayAvailable = true;
				}

				var applePay = document.getElementsByName('APPLE_PAY');
				if (applePay.length !== 0 && !applePayAvailable) {
					applePay[0].remove();
				}
			</script>
			<?php

			echo wp_kses(
				$enabled_payment_methods,
				array(
					'div'    => array( 'id' => true, 'class' => true, 'name' => true, 'style' => true ),
					'input'  => array( 'class' => true, 'name' => true, 'type' => true, 'id' => true, 'value' => true, 'checked' => true ),
					'span'   => array( 'class' => true ),
					'img'    => array( 'src' => true, 'alt' => true, 'style' => true, 'class' => true ),
					'label'  => array( 'for' => true, 'class' => true, 'style' => true ),
					'select' => array( 'id' => true, 'name' => true, 'class' => true, 'style' => true ),
					'option' => array( 'value' => true, 'selected' => true, 'data-card-art' => true ),
				)
			);
		}

		/**
		 * Process payment.
		 *
		 * @param int $order_id Order ID.
		 *
		 * @since  1.0.0
		 */
		public function process_payment( $order_id ): array {
			$order = wc_get_order( $order_id );
			$order->set_status( 'pending' );
			$order->save();

			if ( ! $order->get_currency() || ! array_key_exists( $order->get_currency(), $this->enable_currencies ) ) {
				if ( ! wc_has_notice( __( 'Currency is not supported on GoPay', 'gopay-gateway' ), 'error' ) ) {
					wc_add_notice( __( 'Currency is not supported on GoPay', 'gopay-gateway' ), 'error' );
				}
				return array(
					'result'   => 'failed',
					'redirect' => wc_get_checkout_url(),
				);
			}

			global $wpdb;
			$lock_name    = 'gopay_pp_' . md5( DB_NAME . '|' . $order_id );
			$lock_timeout = (int) apply_filters( 'gopay_gateway_payment_lock_timeout', 15 );
			$lock_held    = ( '1' === (string) $wpdb->get_var(
				$wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, $lock_timeout )
			) );

			try {
				$order->read_meta_data( true );

				// Duplicate-submit guard.
				$existing_tx_id  = $order->get_meta( 'GoPay_Transaction_id' );
				$last_created_at = (int) $order->get_meta( '_GoPay_payment_created_at' );
				$dedupe_window   = (int) apply_filters( 'gopay_gateway_payment_dedupe_window', 30 );

				if ( $existing_tx_id && $last_created_at && ( time() - $last_created_at ) < $dedupe_window ) {
					$status          = Gopay_Gateway_API::get_status( $order_id );
					$reusable_states = array( 'CREATED', 'PAYMENT_METHOD_CHOSEN', 'AUTHORIZED' );

					if ( 200 === $status->statusCode
						&& in_array( $status->json['state'] ?? '', $reusable_states, true )
						&& ! empty( $status->json['gw_url'] ) ) {

						Gopay_Gateway_Log::insert_log( array(
							'order_id'       => $order_id,
							'transaction_id' => $existing_tx_id,
							'message'        => 'Duplicate process_payment - reusing existing GoPay payment',
							'log_level'      => 'INFO',
							'log'            => $status,
						) );

						$url_args     = array( 'gopay_url' => $status->json['gw_url'] );
						$redirect_url = wc_get_checkout_url();
						if ( ! empty( $_GET['pay_for_order'] ) && $_GET['pay_for_order'] === 'true' ) {
							$url_args     = array_merge( $_GET, $url_args );
							$redirect_url = wc_get_endpoint_url( 'order-pay' ) . $order_id . '/';
						}

						return array(
							'result'   => 'success',
							'redirect' => htmlspecialchars_decode(
								wp_nonce_url( add_query_arg( $url_args, $redirect_url ), 'gw_url' )
							),
						);
					}
				}

				// Check if total is equal to zero.
				$subscription = Gopay_Gateway_Subscriptions::get_subscription_data( $order );
				if ( $order->get_total() == 0 ) {
					if ( empty( $subscription ) ) {
						foreach ( $order->get_items() as $item ) {
							$product = wc_get_product( $item['product_id'] );
							if ( ! $product->is_virtual() && ! $product->is_downloadable() ) {
								$order->set_status( 'processing' );
								break;
							}
						}

						if ( $order->get_status() != 'processing' ) {
							$order->set_status( 'completed' );
						}
						$order->save();
					}
					return array(
						'result'   => 'success',
						'redirect' => $this->get_return_url( $order ),
					);
				}

				$gopay_payment_method = filter_input( INPUT_POST, 'gopay_payment_method' );
				$is_retry             = $this->payment_retry &&
											is_page( wc_get_page_id( 'checkout' ) ) &&
											! empty( get_query_var( 'order-pay' ) );

				$request_card_token = filter_input( INPUT_POST, 'request_card_token' ) ?? false;
				$card_id = filter_input( INPUT_POST, 'saved_card' ) ?? '';

				// Add GoPay payment method to order.
				if ( $gopay_payment_method ) {
					if ( array_key_exists( $gopay_payment_method, Gopay_Gateway_Options::supported_banks() ) ) {
						$order->update_meta_data( '_GoPay_bank_swift', $gopay_payment_method );
						$order->update_meta_data( '_GoPay_payment_method', 'BANK_ACCOUNT' );
					} else {
						$order->update_meta_data( '_GoPay_payment_method', $gopay_payment_method );
					}
				}

				// GoPay API only considers cents.
				// Rounding total to 2 decimals.
				$order->set_total( wc_format_decimal( $order->get_total(), 2 ) );

				// Try to get Payment method from $_POST or $_Request
				if (isset($_POST['gopay_payment_method'])) {
					$gopay_payment_method = sanitize_text_field($_POST['gopay_payment_method']);
				} elseif (isset($_REQUEST['payment_data']) && isset($_REQUEST['payment_data']['gopay_payment_method'])) {
					$gopay_payment_method = sanitize_text_field($_REQUEST['payment_data']['gopay_payment_method']);
				}

				if (isset($_POST['saved_card'])) {
					$card_id = sanitize_text_field($_POST['saved_card']);
				}

				if (isset($_POST['request_card_token'])) {
					$request_card_token = sanitize_text_field($_POST['request_card_token']);
				}

				$response = Gopay_Gateway_API::create_payment(
					$gopay_payment_method,
					$order,
					! empty( $subscription ) ? ( $subscription->get_date( 'end' ) ?: gmdate( 'Y-m-d', strtotime( '+5 years' ) ) ) : '',
					$is_retry,
					$request_card_token,
					$card_id
				);

				if ( 200 != $response->statusCode ) {
					$log = array(
						'order_id'       => $order_id,
						'transaction_id' => 0,
						'message'        => 'Process payment error',
						'log_level'      => 'ERROR',
						'log'            => $response,
					);
					Gopay_Gateway_Log::insert_log( $log );
					if ( ! wc_has_notice(
						__(
							'Payment creation on GoPay not possible',
							'gopay-gateway'
						),
						'error'
					) ) {
						wc_add_notice(
							__(
								'Payment creation on GoPay not possible',
								'gopay-gateway'
							),
							'error'
						);
					}

					return array(
						'result'   => 'failed',
						'redirect' => wc_get_checkout_url(),
					);
				}

				// Add GoPay transaction id to order.
				// $order->set_status('on-hold'); !
				$order->update_meta_data( 'GoPay_Transaction_id', $response->json['id'] );
				$order->update_meta_data( '_GoPay_payment_created_at', time() );
				$order->save();

				// Save log.
				$log = array(
					'order_id'       => $order_id,
					'transaction_id' => $response->json['id'],
					'message'        => 'Payment created',
					'log_level'      => 'INFO',
					'log'            => $response,
				);
				Gopay_Gateway_Log::insert_log( $log );

				$redirect_url = wc_get_checkout_url();
				$url_args     = array( 'gopay_url' => $response->json['gw_url'] );
				if ( ! empty( $_GET['pay_for_order'] ) && $_GET['pay_for_order'] == 'true' ) {
					$url_args     = array_merge( $_GET, $url_args );
					$redirect_url = wc_get_endpoint_url( 'order-pay' );
					$redirect_url = $redirect_url . $order_id . '/';
				}

				return array(
					'result'   => 'success',
					'redirect' => htmlspecialchars_decode(
						wp_nonce_url( add_query_arg( $url_args, $redirect_url ), 'gw_url' )
					),
				);
			} finally {
				if ( $lock_held ) {
					$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
				}
			}
		}

		/**
		 * Process refund.
		 *
		 * @param int        $order_id ID.
		 * @param float|null $amount   amount.
		 * @param string     $reason   reason.
		 *
		 * @return boolean|WP_Error if succeeded, or a WP_Error object.
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {

			// GoPay API only considers cents.
			// Rounding amount to be refunded to 2 decimals.
			$amount = wc_format_decimal( $amount, 2 );

            // Check if refund can be made
			$order = wc_get_order( $order_id );
			if ( $amount != wc_format_decimal( $order->get_total(), 2 ) && ! ( $order->get_date_modified()
                        ->getTimestamp() < time() - 86400 ) ) {
				return new WP_Error( 'error',
                    __( 'You can only issue a partial refund 24 hours after the payment.', 'gopay-gateway' ) );
			}

			$transaction_id = $order->get_meta( 'GoPay_Transaction_id', true );
			$response       = Gopay_Gateway_API::refund_payment( $transaction_id, $amount * 100 );
			$status         = Gopay_Gateway_API::get_status( $order_id );

			$log = array(
				'order_id'       => $order_id,
				'transaction_id' => $transaction_id,
				'message'        => 200 == $status->statusCode ? ( 'PARTIALLY_REFUNDED' === $status->json['state'] ?
					'Payment partially refunded' : 'Payment refunded' ) : 'Payment refund executed',
				'log_level'      => 'INFO',
				'log'            => $status,
			);

			if ( $response->statusCode != 200 ) {
				$log['message']   = 'Process refund error';
				$log['log_level'] = 'ERROR';
				$log['log']       = $response;
				Gopay_Gateway_Log::insert_log( $log );

				return new WP_Error( 'error', __( 'Refund failed.', 'gopay-gateway' ) );
			}
			Gopay_Gateway_Log::insert_log( $log );

			if ( 'FINISHED' === $response->json['result'] ) {
				return true;
			} else {
				return new WP_Error( 'error', __( 'Refund failed.', 'gopay-gateway' ) );
			}
		}

		/**
		 * Calculate refund without rounding up/down.
		 *
		 * @param object $refund an object with refund info.
		 * @param array  $args   refund's arguments.
		 */
		public static function calculate_refund_amount( $refund, array $args ) {
			$amount = 0;
			if ( count( $args['line_items'] ) > 0 ) {
				foreach ( $args['line_items'] as $item_id => $item ) {
					$refund_total = $item['refund_total'];
					$refund_tax   = isset( $item['refund_tax'] ) ? array_sum( $item['refund_tax'] ) : 0;

					$amount += (float) $refund_total + (float) $refund_tax;
				}
			}

			$refund->set_amount( $amount );
			$refund->save();
		}

		/**
		 * Check Status of GoPay payment
		 *
		 * @since 1.0.0
		 */
		public function check_status_gopay_redirect() {
			$gopay_api = filter_input( INPUT_GET, 'gopay-api' );
			$id        = filter_input( INPUT_GET, 'id' );
			if ( $gopay_api && $id ) {
				static $has_run = false;
				if ($has_run) {
					return;
				}
				$has_run = true;
				Gopay_Gateway_API::check_payment_status( $id );
			}
		}

		/**
		 * Message order received page.
		 *
		 * @param string $message Message.
		 * @param object $order Order.
		 * @since  1.0.0
		 */
		public function thankyou_page( $message, $order ) {
			// Order is not created by GoPay
			if ( ! is_object( $order ) || $this->id !== $order->get_payment_method() ) {
				return $message;
			}

			$message      = __( 'Thank you. Your order has been received.', 'gopay-gateway' );

			if ( is_object( $order ) ) {
				$subscription = Gopay_Gateway_Subscriptions::get_subscription_data( $order );
				if ( ! empty( $subscription ) && $order->get_total() == 0 ) {
					return $message . __(
						' Please pay for your subscription after the trial period.',
						'gopay-gateway'
					);
				}

				if ( $order->has_status( array( 'pending', 'on-hold' ) ) ) {
					return $message . __(
						' However, we are still waiting for the confirmation or payment rejection.',
						'gopay-gateway'
					);
				}
			}

			return $message;
		}

		/**
		 * Change thank you order failed text
		 *
		 * @param int $order_id Order ID.
		 * @since  1.0.0
		 */
		public function thankyou_order_failed_text( int $order_id ) {
			$order = new WC_Order( $order_id );
			if ( $order->has_status( 'failed' ) ) {
				$message = esc_attr(
					__(
						'Unfortunately your order cannot be processed as the payment was not completed. Please attempt the payment or your purchase again.',
						'gopay-gateway'
					)
				);

				?>
				<script>
					if (typeof failed_message === 'undefined') {
						let failed_message = document.getElementsByClassName('woocommerce-thankyou-order-failed');
						failed_message[0].textContent = <?php echo wp_json_encode( $message ); ?>;
					}
				</script>
				<?php
			}
		}

		/**
		 * Process admin options.
		 *
		 * @return bool
		 * @since  1.0.0
		 */
		public function process_admin_options(): bool {
			$saved = parent::process_admin_options();
			$this->init_form_fields();

			// Check payment methods and banks enabled on GoPay account.
			if ( // empty( $this->get_option( 'option_gopay_payment_methods', '' ) ) &&
				! empty( $this->get_option( 'goid', '' ) ) &&
				! empty( $this->get_option( 'test', '' ) ) ) {
				$this->check_enabled_on_gopay();
			}

			// Check credentials (GoID, Client ID and Client Secret).
            if ( empty( $this->get_option( 'goid', '' ) ) ||
                empty( $this->get_option( 'client_id', '' ) ) ||
	            empty( $this->get_option( 'client_secret', '' ) )
            ) {
	            if ( empty( $this->get_option( 'admin_notice_credentials_empty' ) ) ) {
		            add_action( 'admin_notices', array( $this, 'admin_notice_credentials_empty' ) );
		            $this->update_option( 'admin_notice_credentials_empty', true );
	            } else {
		            $this->update_option( 'admin_notice_credentials_empty', false );
	            }
            } else {
	            $options = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
	            if ( array_key_exists( 'test', $options ) ) {
		            $gopay = Gopay_Gateway_API::auth_gopay( $options );

		            $response = $gopay->getPaymentInstruments(
			            $this->get_option( 'goid', '' ), 'CZK' );
		            if ( !$response->hasSucceed() ) {

			            if ( empty( $this->get_option( 'admin_notice_credentials_error' ) ) ) {
				            add_action( 'admin_notices', array( $this, 'admin_notice_credentials_error' ) );
				            $this->update_option( 'admin_notice_credentials_error', true );
			            } else {
				            $this->update_option( 'admin_notice_credentials_error', false );
			            }
		            }

		            $response = $gopay->getPaymentInstruments(
			            $this->get_option( 'goid', '' ), 'CZK' );
		            if ( array_key_exists( 'errors', $response->json ) &&
			            $response->json['errors'][0]['error_name'] == 'INVALID' ) {
			            $this->update_option( 'goid', '' );
		            }

		            $response = $gopay->getAuth()->authorize()->response;
		            if ( array_key_exists( 'errors', $response->json ) &&
			            $response->json['errors'][0]['error_name'] == 'AUTH_WRONG_CREDENTIALS' ) {
			            $this->update_option( 'client_id', '' );
			            $this->update_option( 'client_secret', '' );
		            }

	            }
            }
			// END.

			return $saved;
		}

		/**
		 * Admin notice message GoID
		 *
		 * @since 1.0.0
		 */
		public function admin_notice_credentials_empty() {
			$class   = 'notice notice-error';
			$message = __(
                    'GoID, Client ID and Client Secret are mandatory. Please provide valid GoID, Client ID and Client Secret.',
                    'gopay-gateway' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}

		/**
		 * Admin notice message GoID
		 *
		 * @since 1.0.0
		 */
		public function admin_notice_credentials_error() {
			$class   = 'notice notice-error';
			$message = __(
                    'Wrong GoID and/or credentials. Please provide valid GoID, Client ID and Client Secret.',
                    'gopay-gateway' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}

		/**
		 * Enqueue styles
		 *
		 * @since 1.0.0
		 */
		public function enqueue_styles() {
			wp_enqueue_style(
				'gopay-gateway-payment-methods-styles',
				GOPAY_GATEWAY_URL . 'includes/assets/css/payment_methods.css',
				array(),
				'1.0.0'
			);

			wp_enqueue_style(
				'gopay-gateway-payment-cards-style',
				GOPAY_GATEWAY_URL . 'includes/assets/css/payment_cards.css',
				array(),
				'1.0.0'
			);
		}

		/**
		 * Admin enqueue styles
		 *
		 * @since 1.0.0
		 */
		public function admin_enqueue_styles() {
			wp_enqueue_style(
				'gopay-gateway-payment-methods-styles',
				GOPAY_GATEWAY_URL . 'includes/assets/css/form_fields.css',
				array(),
				'1.0.0'
			);
		}

		/**
		 * Enqueue scripts
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts() {
			$test = $this->get_option( 'test', '' );
			if ( 'yes' === $test ) {
				wp_enqueue_script(
					'gopay-gateway-inline-scripts',
					'https://gw.sandbox.gopay.com/gp-gw/js/embed.js'
				);
			} else {
				wp_enqueue_script(
					'gopay-gateway-inline-scripts',
					'https://gate.gopay.cz/gp-gw/js/embed.js'
				);
			}

			if ( is_checkout() ) {
				wp_enqueue_script(
					'gopay-checkout-js',
					plugin_dir_url( __FILE__ ) . 'assets/js/gopay-checkout.js',
					[ 'jquery' ],
					'1.0.0',
					true
				);
			}

			if (is_account_page()) {
				wp_enqueue_script(
					'gopay-account-js',
					plugin_dir_url( __FILE__ ) . 'assets/js/account.js',
					[ 'jquery' ],
					'1.0.0',
					true
				);

				wp_localize_script(
					'gopay-account-js',
					'GoPayCardsAjax',
					[
						'ajaxurl' => admin_url('admin-ajax.php')
					]
				);
			}
		}

		/**
		 * Delete customer logs when
		 * the user is deleted
		 *
		 * @param int $user_id User id.
		 * @since 1.0.0
		 */
		public function delete_user_logs( $user_id ) {
			$args   = array(
				'customer_id' => $user_id,
				'limit'       => -1,
			);
			$orders = wc_get_orders( $args );

			foreach ( $orders as $key => $order ) {
				global $wpdb;
				$log_table = $wpdb->prefix . GOPAY_GATEWAY_LOG_TABLE_NAME;
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$log_table} WHERE order_id = %d",
						$order->get_id()
					)
				);
			}
		}

		/**
		 * Delete Order logs when
		 * the order is deleted
		 *
		 * @param int $order_id Order id.
		 * @since 1.0.0
		 */
		public function delete_order_logs( int $order_id ) {
			global $wpdb;
			$log_table = $wpdb->prefix . GOPAY_GATEWAY_LOG_TABLE_NAME;
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$log_table} WHERE order_id = %d",
					$order_id
				)
			);
		}

		public function delete_card_callback() {
			$card_id = $_POST['card_id'] ?? null;
			$nonce   = $_POST['nonce'] ?? '';

			if (!wp_verify_nonce($nonce, 'gopay_delete_card')) {
				wp_send_json_error(['message' => 'Invalid nonce']);
			}

			// Delete from GoPay
			$response = Gopay_Gateway_API::delete_payment_card($card_id);

			if ( ! $response ) {
				wp_send_json_error(['message' => 'Failed to connect to GoPay. Please try again.']);
			}

			// GoPay SDK Response status 2xx or 404
			$is_success = floor($response->statusCode / 100) == 2 || $response->statusCode == 404;

			if ( $is_success ) {
				$deleted_from_db = Gopay_Gateway_Log::delete_customer_card(get_current_user_id(), $card_id);

				if ( ! $deleted_from_db ) {
					wp_send_json_error(['message' => 'Card deleted from GoPay, but database update failed.']);
				}

				wp_send_json_success(['card_id' => $card_id]);
			} else {
				$error_msg = $response->json['errors'][0]['message'] ?? 'Failed to delete card from GoPay.';
				wp_send_json_error(['message' => $error_msg]);
			}
		}

		public function check_payment_cards_access() {
			if ( ! $this->card_token && is_account_page() ) {
				global $wp_query;
				if ( isset( $wp_query->query_vars['payment-cards'] ) ) {
					$wp_query->set_404();
					status_header( 404 );
					nocache_headers();
					include get_query_template( '404' );
					exit;
				}
			}
		}

		function gopay_add_payment_cards_tab($items) {
			if ( ! $this->card_token ) {
				return $items;
			}

			$new_items = array();

			foreach ($items as $key => $label) {
				$new_items[$key] = $label;

				if ($key === 'dashboard') {
					$new_items['payment-cards'] = __('Payment cards', 'gopay-gateway');
				}
			}

			return $new_items;
		}
	}

	/**
	 *  Add the Gateway to WooCommerce
	 *
	 * @param array $methods methods.
	 *
	 * @return array
	 */
	function add_gopay_gateway( array $methods ): array {
		$methods[] = 'Gopay_Gateway';
		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_gopay_gateway' );

	function gopay_payment_cards_content() {
		echo '<h3>'.esc_html__('Saved payment cards', 'gopay-gateway').'</h3>';
		echo '<p>'.esc_html__('Here are your saved cards:', 'gopay-gateway').'</p>';

		$saved_cards = Gopay_Gateway_API::get_card_details();

		// Nonce for security
		$nonce = wp_create_nonce('gopay_delete_card');

		echo '<table class="gopay-cards-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>'.esc_html__('Brand', 'gopay-gateway').'</th>';
		echo '<th>'.esc_html__('Card number', 'gopay-gateway').'</th>';
		echo '<th>'.esc_html__('Expiry', 'gopay-gateway').'</th>';
		echo '<th>'.esc_html__('Action', 'gopay-gateway').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ($saved_cards as $card) {
			$is_suspended = ( $card['status'] ?? 'ACTIVE' ) === 'SUSPENDED';

			echo '<tr id="gopay-card-row-' . esc_attr($card['card_id']) . '"' . ( $is_suspended ? ' class="gopay-card-row--suspended"' : '' ) . '>';
			echo '<td data-label="Brand">';
			if ( ! empty( $card['card_art_url'] ) ) {
				echo '<img src="' . esc_url( $card['card_art_url'] ) . '" alt="' . esc_attr( $card['card_brand'] ) . '" class="gopay-card-art' . ( $is_suspended ? ' gopay-card-art--suspended' : '' ) . '" />';
			} else {
				echo esc_html( $card['card_brand'] );
			}
			if ( $is_suspended ) {
				echo '<span class="gopay-suspended-badge" aria-label="' . esc_attr__( 'This card has been suspended by your bank. You cannot pay with it, but you can delete it.', 'gopay-gateway' ) . '">'
					. esc_html__( 'Suspended', 'gopay-gateway' )
					. '</span>';
			}
			echo '</td>';
			$short_pan = '****...' . substr( $card['real_masked_pan'], -4 );
			echo '<td data-label="Card number">
				<div class="gopay-card-number">
					<span class="gopay-masked-pan" data-short="' . esc_attr( $short_pan ) . '">'
						. esc_html($card['real_masked_pan']) .
					'</span>
				</div>
			</td>';
			echo '<td data-label="Expiry">' . esc_html($card['card_expiration']) . '</td>';
			echo '<td data-label="Actions">';

			echo '<div id="gopay-delete-wrapper" class="gopay-delete-wrapper">';

			echo '<button id="gopay-delete-card" class="gopay-delete-card"
					data-card-id="' . esc_attr($card['card_id']) . '"
					data-nonce="' . esc_attr($nonce) . '">
					'.esc_html__('Delete', 'gopay-gateway').'
				</button>';

			echo '<div id="gopay-delete-confirm" class="gopay-delete-confirm" style="display:none;">
					<span>'.esc_html__('Are you sure?', 'gopay-gateway').'</span>
					<div>
						<button id="gopay-confirm-yes" class="gopay-confirm-yes"
							data-card-id="' . esc_attr($card['card_id']) . '"
							data-nonce="' . esc_attr($nonce) . '">'.esc_html__('OK', 'gopay-gateway').'</button>
						<button id="gopay-confirm-cancel" class="gopay-confirm-cancel">'.esc_html__('Cancel', 'gopay-gateway').'</button>
					</div>
				</div>';

			echo '</div>';
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
	}

	add_action( 'woocommerce_account_payment-cards_endpoint', 'gopay_payment_cards_content' );
}
