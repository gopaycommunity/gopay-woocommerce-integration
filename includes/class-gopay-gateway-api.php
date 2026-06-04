<?php
/**
 * GoPay Gateway API
 * Connect to GoPay API using the GoPay's PHP SDK
 *
 * @package   WooCommerce GoPay gateway
 * @author    GoPay
 * @link      https://www.gopay.com/
 * @copyright 2022 GoPay
 * @since     1.0.0
 */

use GoPay\Http\Response;
use GoPay\Payments;

/**
 * GoPay API connector
 *
 * @since 1.0.0
 */
class Gopay_Gateway_API {


	/**
	 * Constructor for the plugin GoPay api
	 *
	 * @since 1.0.0
	 */
	public function __construct() { }

	/**
	 * GoPay authentication
	 *
	 * @param array $options plugin options.
	 * @return Payments object
	 * @since  1.0.0
	 */
	public static function auth_gopay( $options ): Payments {
		static $urls = array(
			true  => 'https://gate.gopay.cz/',
			false => 'https://gw.sandbox.gopay.com/',
		);

		return GoPay\payments(
			array(
				'goid'             => $options['goid'],
				'clientId'         => $options['client_id'],
				'clientSecret'     => $options['client_secret'],
				'isProductionMode' => ! ( 'yes' === $options['test'] ),
				'scope'            => GoPay\Definition\TokenScope::ALL,
				'language'         => array_key_exists( 'default_language_gopay_interface', $options ) ?
					$options['default_language_gopay_interface'] : 'EN',
				'timeout'          => 30,
				'gatewayUrl'       => $urls[ ! ( 'yes' === $options['test'] ) ],
			)
		);
	}

	/**
	 * Get items info
	 *
	 * @param object $order order detail.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private static function get_items( $order ): array {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$vat_rate = '0';
			if ( $item->get_tax_status() === 'taxable' ) {
				$tax_rates = WC_Tax::get_base_tax_rates( $item->get_tax_class() );
				if ( ! empty( $tax_rates ) ) {
					$vat_rate = (int) end( $tax_rates )['rate'];
				}
			}

			$items[] = array(
				'type'        => 'ITEM',
				'name'        => $item['name'],
				'product_url' => get_permalink( $item['product_id'] ),
				'amount'      => wc_format_decimal( $item['total'], 2 ) * 100, // Rounding total to 2 decimals.
				'count'       => $item['quantity'],
				'vat_rate'    => $vat_rate,
			);
		}

		return $items;
	}

	/**
	 * GoPay create payment
	 *
	 * @param ?string  $gopay_payment_method payment method.
	 * @param WC_Order $order                order detail.
	 * @param string   $end_date             the end date of recurrence.
	 * @param bool     $is_retry             is payment retry.
	 *
	 * @return Response
	 * @since 1.0.0
	 */
	public static function create_payment( ?string $gopay_payment_method, WC_Order $order,
									string $end_date, $is_retry, bool $request_card_token, string $card_id ): Response {
		$options = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
		$gopay   = self::auth_gopay( $options );

		$default_swift = '';
		if ( array_key_exists( $gopay_payment_method, Gopay_Gateway_Options::supported_banks() ) ) {
			$default_swift        = $gopay_payment_method;
			$gopay_payment_method = 'BANK_ACCOUNT';
		}

		if ( empty( $order->get_meta( '_GoPay_payment_method' ) ) || ! $is_retry ) {
			if ( ! empty( $gopay_payment_method ) ) {
				$default_payment_instrument = $gopay_payment_method;
			} else {
				$default_payment_instrument = '';
			}
		} else {
			$default_payment_instrument = $order->get_meta( '_GoPay_payment_method' );
		}

		$items = self::get_items( $order );

		$notification_url = add_query_arg(
			array(
				'gopay-api' => GOPAY_GATEWAY_ID . '_notification',
				'order_id'  => $order->get_id(),
			),
			get_site_url()
		);
		$return_url       = add_query_arg(
			array(
				'gopay-api' => GOPAY_GATEWAY_ID . '_return',
				'order_id'  => $order->get_id(),
			),
			get_site_url()
		);

		$callback = array(
			'return_url'       => $return_url,
			'notification_url' => $notification_url,
		);

		$contact = array(
			'first_name'   => $order->get_billing_first_name(),
			'last_name'    => $order->get_billing_last_name(),
			'email'        => $order->get_billing_email(),
			'phone_number' => $order->get_billing_phone(),
			'city'         => $order->get_billing_city(),
			'street'       => $order->get_billing_address_1(),
			'postal_code'  => $order->get_billing_postcode(),
			'country_code' => Gopay_Gateway_Options::iso2_to_iso3()[ $order->get_billing_country() ],
		);

		if ( ! empty( $default_payment_instrument ) ) {
			$payer = array(
				'default_payment_instrument'  => $default_payment_instrument,
				'allowed_payment_instruments' => $options['enable_gopay_payment_methods'],
				'allowed_swifts'              => ! empty( $options['enable_banks'] ) ? $options['enable_banks'] : array(),
				'contact'                     => $contact,
			);

			// If card tokenization is requested
			if ( isset( $request_card_token ) && true === $request_card_token ) {
				$payer['request_card_token'] = true;
			}

			// Pay with already saved card
			if ( ! empty( $card_id ) && $card_id !== 'new' ) {
				$enabled_methods = $options['enable_gopay_payment_methods'] ?? array();

				if ( is_array( $enabled_methods ) && in_array( 'PAYMENT_CARD', $enabled_methods, true ) ) {

					$card_details = $gopay->getCardDetails($card_id);

					if ( isset( $card_details->statusCode ) && $card_details->statusCode == 200
					&& ( $card_details->json['status'] ?? 'ACTIVE' ) === 'ACTIVE' ) {
						$payer['allowed_payment_instruments'] = ["PAYMENT_CARD"];
						$payer['allowed_card_token'] = $card_details->json['card_token'];
					}
				}
			}

			if ( ! empty( $default_swift ) ) {
				$payer['default_swift'] = $default_swift;
			}
		} else {
			$payer = array(
				'allowed_payment_instruments' => $options['enable_gopay_payment_methods'],
				'allowed_swifts'              => ! empty( $options['enable_banks'] ) ? $options['enable_banks'] : array(),
				'contact'                     => $contact,
			);
		}

		$additional_params = array(
			array(
				'name'  => 'invoicenumber',
				'value' => $order->get_order_number(),
			),
			array(
				'name'  => 'gopay_plugin',
				'value' => 'gopay-woocommerce-' . GOPAY_WOOCOMMERCE_VERSION,
			),
		);

		$language = Gopay_Gateway_Options::country_to_language()[ $order->get_billing_country() ];
		if ( ! array_key_exists( $language, Gopay_Gateway_Options::supported_languages() ) ) {
			$language = $options['default_language_gopay_interface'];
		}

		$data = array(
			'payer'             => $payer,
			'amount'            => wc_format_decimal( $order->get_total() * 100, 0 ),
			'currency'          => $order->get_currency(),
			'order_number'      => $order->get_order_number(),
			'order_description' => 'order',
			'items'             => $items,
			'additional_params' => $additional_params,
			'callback'          => $callback,
			'lang'              => $language,
		);

		if ( ! empty( $end_date ) ) {
			$data['recurrence'] = array(
				'recurrence_cycle'   => 'ON_DEMAND',
				'recurrence_date_to' => 0 != $end_date ? $end_date : gmdate( 'Y-m-d', strtotime( '+5 years' ) ),
			);
		}

		$response = $gopay->createPayment( $data );

		return $response;
	}

	/**
	 * GoPay create recurrence
	 *
	 * @param object $order order detail.
	 *
	 * @return Response
	 * @since 1.0.0
	 */
	public static function create_recurrence( $order ): Response {

		$options              = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
		$gopay                = self::auth_gopay( $options );
		$parent_order         = Gopay_Gateway_Subscriptions::get_parent_order( $order );
		$gopay_transaction_id = $parent_order->get_meta( 'GoPay_Transaction_id', true );

		$data = array(
			'amount'            => $order->get_total() * 100,
			'currency'          => $order->get_currency(),
			'order_number'      => $order->get_order_number(),
			'order_description' => 'subscription',
			'items'             => self::get_items( $order ),
			'additional_params' => array(
				array(
					'name'  => 'invoicenumber',
					'value' => $order->get_order_number(),
				),
			),
		);

		$response = $gopay->createRecurrence( $gopay_transaction_id, $data );

		return $response;
	}

	/**
	 * GoPay cancel recurrence
	 *
	 * @param object $subscription subscription detail.
	 *
	 * @return Response
	 * @since 1.0.0
	 */
	public static function cancel_recurrence( $subscription ): Response {
		$options              = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
		$gopay                = self::auth_gopay( $options );
		$gopay_transaction_id = $subscription->get_parent()->get_meta( 'GoPay_Transaction_id', true );
		$response             = $gopay->voidRecurrence( $gopay_transaction_id );

		return $response;
	}

	/**
	 * GoPay get enabled payments methods
	 *
	 * @param string $currency Currency.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function get_enabled_payment_methods( string $currency ): array {
		$options          = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
		$gopay            = self::auth_gopay( $options );
		$enabled_payments = $gopay->getPaymentInstruments( $options['goid'], $currency );

		$payment_instruments = array();
		if ( 200 == $enabled_payments->statusCode ) {
			foreach ( $enabled_payments->json['enabledPaymentInstruments'] as $key => $payment_method ) {
				if ( 'BANK_ACCOUNT' === $payment_method['paymentInstrument'] ) {
					$payment_instruments[ $payment_method['paymentInstrument'] ] = array(
						'label'  => $payment_method['label']['cs'],
						'image'  => $payment_method['image']['normal'],
						'swifts' => array(),
					);
					$enabled_swifts = $payment_method['enabledSwifts'];
					foreach ( $enabled_swifts as $bank ) {
						$payment_instruments[ $payment_method['paymentInstrument'] ]['swifts'][ $bank['swift'] ] = array(
							'label' => $bank['label']['cs'],
							'image' => $bank['image']['normal'],
						);
					}
				} else {
					$payment_instruments[ $payment_method['paymentInstrument'] ] = array(
						'label' => $payment_method['label']['cs'],
						'image' => $payment_method['image']['normal'],
					);
				}
			}
		}

		return $payment_instruments;
	}

	/**
	 * Check payment methods and banks that
	 * are enabled on GoPay account.
	 *
	 * @param string $currency Currency.
	 * @return array
	 * @since  1.0.0
	 */
	public static function check_enabled_on_gopay( string $currency ): array {
		$options       = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
		$gopay         = self::auth_gopay( $options );
		$site_language = get_locale();
		$language_code = strstr( $site_language, '_', true ) ?: $site_language;

		$reflection  = new ReflectionClass( GoPay\Definition\Language::class );
		$constants   = $reflection->getConstants();
		$is_in_array = in_array( strtoupper( $language_code ), $constants, true );
		if ( ! $is_in_array ) {
			$language_code = strtolower( GoPay\Definition\Language::ENGLISH );
		}

		$payment_methods  = array();
		$banks            = array();
		$enabled_payments = $gopay->getPaymentInstruments( $options['goid'], $currency . '?lang=' . $language_code );

		if ( 200 == $enabled_payments->statusCode && isset( $enabled_payments->json['enabledPaymentInstruments'] ) ) {
			// Determine if the specified language code exists in the response
			$paymentInstrument = reset( $enabled_payments->json['enabledPaymentInstruments'] );
			$language_code     = isset( $paymentInstrument['label'][ $language_code ] ) ? $language_code : strtolower( GoPay\Definition\Language::CZECH );

			foreach ( $enabled_payments->json['enabledPaymentInstruments'] as $key => $payment_method ) {
				$payment_methods[ $payment_method['paymentInstrument'] ] = array(
					'label' => $payment_method['label'][ $language_code ],
					'image' => $payment_method['image']['normal'],
				);

				if ( 'BANK_ACCOUNT' === $payment_method['paymentInstrument'] ) {
					foreach ( $payment_method['enabledSwifts'] as $bank ) {
						$banks[ $bank['swift'] ] = array(
							'label'   => $bank['label'][ $language_code ],
							'country' => 'OTHERS' !== $bank['swift'] ? substr( $bank['swift'], 4, 2 ) : '',
							'image'   => $bank['image']['normal'],
						);
					}
				}
			}
		}

		return array( $payment_methods, $banks );
	}

	/**
	 * Check payment status
	 *
	 * @param string $gopay_transaction_id GoPay transaction id.
	 *
	 * @since  1.0.0
	 */
	public static function check_payment_status( string $gopay_transaction_id ) {
		$options  = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
		$gopay    = self::auth_gopay( $options );
		$response = $gopay->getStatus( $gopay_transaction_id );

		$orders = wc_get_orders(
			array(
				'limit'        => 1,
				'meta_key'     => 'GoPay_Transaction_id',
				'meta_value'   => $gopay_transaction_id,
				'meta_compare' => '=',
			)
		);

		if ( ! empty( $orders ) ) {
			$order = $orders[0];
		} else {
			return;
		}

		// Save log.
		$log = array(
			'order_id'       => $order->get_id(),
			'transaction_id' => 200 == $response->statusCode ? $response->json['id'] : '0',
			'message'        => 200 == $response->statusCode ? 'Checking payment status' :
																	'Error checking payment status',
			'log_level'      => 200 == $response->statusCode ? 'INFO' : 'ERROR',
			'log'            => $response,
		);
		Gopay_Gateway_Log::insert_log( $log );

		if ( 200 != $response->statusCode ) {
			return;
		}

		switch ( $response->json['state'] ) {
			case 'PAID':
				if ( $order->is_paid() ) {
					wp_safe_redirect( $order->get_checkout_order_received_url() );
					exit;
				}
				$transaction_id = $response->json['id'];
				$order->payment_complete( $transaction_id );

				// Update retry status.
				if ( class_exists( 'WCS_Retry_Manager', false ) ) {
					$retry = WCS_Retry_Manager::store()->get_last_retry_for_order(
						wcs_get_objects_property( $order, 'id' )
					);
					if ( ! empty( $retry ) ) {
						$retry->update_status( 'complete' );
					}
				}

				$order->save();
				wp_safe_redirect( $order->get_checkout_order_received_url() );

				$card_id = $response->json['payer']['card_id'] ?? null;

				if ( $card_id ) {
					// Fetch card details
					$card_details = $gopay->getCardDetails($card_id);

					if ( isset($card_details->statusCode) && $card_details->statusCode == 200 ) {
						$user_id = $order->get_user_id();
						Gopay_Gateway_Log::insert_saved_card($user_id,$card_id);
					}
				}

				break;
			case 'PAYMENT_METHOD_CHOSEN':
			case 'AUTHORIZED':
				wp_safe_redirect( $order->get_checkout_order_received_url() );

				break;
			case 'CREATED':
			case 'TIMEOUTED':
			case 'CANCELED':
				$order->set_status( 'failed' );
				$order->save();
				wp_safe_redirect( $order->get_checkout_order_received_url() );

				break;
			case 'REFUNDED':
				$order->set_status( 'refunded' );
				$order->save();
				wp_safe_redirect( $order->get_checkout_order_received_url() );

				break;
		}
	}

	/**
	 * Get status of the transaction
	 *
	 * @param int $order_id Order id.
	 *
	 * @since  1.0.0
	 */
	public static function get_status( int $order_id ): Response {
		$order                = wc_get_order( $order_id );
		$options              = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
		$gopay                = self::auth_gopay( $options );
		$gopay_transaction_id = $order->get_meta( 'GoPay_Transaction_id', true );
		$response             = $gopay->getStatus( $gopay_transaction_id );

		return $response;
	}

	/**
	 * Refund payment
	 *
	 * @param int    $transaction_id Transaction id.
	 * @param string $amount amount to be refunded.
	 *
	 * @return Response $response
	 * @since  1.0.0
	 */
	public static function refund_payment( int $transaction_id, string $amount ): Response {
		$options  = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
		$gopay    = self::auth_gopay( $options );
		$response = $gopay->refundPayment( $transaction_id, $amount );

		return $response;
	}

	public static function get_card_details() {
		global $wpdb;

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$table_name = $wpdb->prefix . GOPAY_GATEWAY_TABLE_CARDS;

		// Authenticate GoPay
		$options  = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
		$gopay    = self::auth_gopay( $options );

		$user_id = get_current_user_id();

		// Get saved cards for current user
		$cards = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, card_id FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC",
				$user_id
			)
		);

		if ( empty($cards) ) {
			return array();
		}

		$results = array();

		foreach ( $cards as $card ) {
			try {
				$card_details = $gopay->getCardDetails( $card->card_id );

				if ( isset( $card_details->statusCode ) && $card_details->statusCode == 200 ) {
					$card_status = $card_details->json['status'] ?? 'ACTIVE';

					if ( $card_status === 'DELETED' ) {
						Gopay_Gateway_Log::delete_customer_card( $user_id, $card->card_id );
						continue;
					}

					$card_exp_raw    = $card_details->json['card_expiration'] ?? '';

					// Trim whitespace from expiration
					$card_expiration = str_replace(' ', '', $card_exp_raw);

					$results[] = array(
						'id'              => $card->id,
						'card_id'         => $card->card_id,
						'status'          => $card_status,
						'real_masked_pan' => $card_details->json['real_masked_pan'] ?? '',
						'card_brand'      => $card_details->json['card_brand'] ?? '',
						'card_expiration' => $card_expiration,
						'card_art_url'    => $card_details->json['card_art_url'] ?? '',
					);
				}

			} catch ( Exception $e ) {
				return array();
			}
		}

		return $results;
	}

	public static function delete_payment_card($card_id) {
		$options  = get_option( 'woocommerce_' . GOPAY_GATEWAY_ID . '_settings' );
		$gopay    = self::auth_gopay( $options );

		try {
			return $gopay->deleteCard( $card_id );
		} catch (Exception $e) {
			return null;
		}
	}
}
