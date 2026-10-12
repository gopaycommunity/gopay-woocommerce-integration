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
				'recurrence_date_to' => $end_date,
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
	 * Pick a localised label from the GoPay API response.
	 *
	 * GoPay returns labels as a map of language code => text and only
	 * includes the languages it has available, so the requested language
	 * is not guaranteed to be present.
	 *
	 * @param array  $labels    Map of language code => label as returned by GoPay.
	 * @param string $preferred Preferred language code (lowercase).
	 *
	 * @return string Label in the preferred language, or the closest available fallback.
	 * @since  1.0.36
	 */
	private static function pick_label( array $labels, string $preferred ): string {
		$candidates = array(
			$preferred,
			strtolower( GoPay\Definition\Language::CZECH ),
			strtolower( GoPay\Definition\Language::ENGLISH ),
		);

		foreach ( $candidates as $code ) {
			if ( ! empty( $labels[ $code ] ) && is_string( $labels[ $code ] ) ) {
				return $labels[ $code ];
			}
		}

		foreach ( $labels as $label ) {
			if ( ! empty( $label ) && is_string( $label ) ) {
				return $label;
			}
		}

		return '';
	}

	/**
	 * Extract payment methods and banks from the GoPay API response.
	 *
	 * Every key of the response is treated as optional: GoPay omits keys that are
	 * not relevant for the given entry instead of sending them empty. Most notably
	 * enabledSwifts is missing whenever no bank is assigned to the currency.
	 *
	 * @param mixed  $instruments   Value of enabledPaymentInstruments from the API response.
	 * @param string $language_code Preferred language code (lowercase).
	 *
	 * @return array Array with the payment methods and the banks.
	 * @since  1.0.36
	 */
	private static function parse_payment_instruments( $instruments, string $language_code ): array {
		$payment_methods = array();
		$banks           = array();

		if ( ! is_array( $instruments ) ) {
			return array( $payment_methods, $banks );
		}

		foreach ( $instruments as $payment_method ) {
			if ( ! is_array( $payment_method ) || empty( $payment_method['paymentInstrument'] ) ) {
				continue;
			}

			$payment_methods[ $payment_method['paymentInstrument'] ] = array(
				'label' => self::pick_label( (array) ( $payment_method['label'] ?? array() ), $language_code ),
				'image' => $payment_method['image']['normal'] ?? '',
			);

			if ( 'BANK_ACCOUNT' !== $payment_method['paymentInstrument'] ) {
				continue;
			}

			// The enabledSwifts key is nullable and is omitted entirely when no
			// bank is assigned to the currency on the GoPay account.
			$enabled_swifts = $payment_method['enabledSwifts'] ?? array();
			if ( ! is_array( $enabled_swifts ) ) {
				$enabled_swifts = array();
			}

			foreach ( $enabled_swifts as $bank ) {
				if ( ! is_array( $bank ) || empty( $bank['swift'] ) ) {
					continue;
				}

				$banks[ $bank['swift'] ] = array(
					'label'   => self::pick_label( (array) ( $bank['label'] ?? array() ), $language_code ),
					'country' => 'OTHERS' !== $bank['swift'] ? substr( $bank['swift'], 4, 2 ) : '',
					'image'   => $bank['image']['normal'] ?? '',
				);
			}
		}

		return array( $payment_methods, $banks );
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

		$enabled_payments = $gopay->getPaymentInstruments( $options['goid'], $currency . '?lang=' . $language_code );

		if ( ! $enabled_payments instanceof Response || 200 != $enabled_payments->statusCode ) {
			return array( array(), array() );
		}

		return self::parse_payment_instruments(
			$enabled_payments->json['enabledPaymentInstruments'] ?? null,
			$language_code
		);
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

		$order = self::find_order( $gopay_transaction_id, $response );
		if ( empty( $order ) ) {
			Gopay_Gateway_Log::insert_log(
				array(
					'order_id'       => 0,
					'transaction_id' => $gopay_transaction_id,
					'message'        => 'Order for GoPay payment not found',
					'log_level'      => 'ERROR',
					'log'            => $response,
				)
			);

			return;
		}

		// Only the current payment can change the order status,
		// an older payment of the order can only pay it.
		$is_current_payment = (string) $order->get_meta( 'GoPay_Transaction_id', true ) ===
								(string) $gopay_transaction_id;

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
				$transaction_id = $response->json['id'];

				if ( $order->is_paid() ) {
					// Order was already paid by another GoPay payment.
					if ( (string) $order->get_transaction_id() !== (string) $transaction_id ) {
						self::notify_duplicate_payment( $order, $transaction_id, $response );
					}
					wp_safe_redirect( $order->get_checkout_order_received_url() );
					exit;
				}

				// Older payment was paid, use it as the order payment
				// so refunds and recurrences are made from the paid one.
				if ( ! $is_current_payment ) {
					$order->add_order_note(
						sprintf(
							/* translators: %1$s: paid GoPay payment ID, %2$s: replaced GoPay payment ID */
							__( 'Older GoPay payment %1$s was paid, it replaces the payment %2$s of the order.', 'gopay-gateway' ),
							$transaction_id,
							$order->get_meta( 'GoPay_Transaction_id', true )
						)
					);
					$order->update_meta_data( 'GoPay_Transaction_id', $transaction_id );
				}

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
				// Skip older payment, e.g. TIMEOUTED notification of abandoned
				// payment must not fail the order while a newer payment is open.
				if ( ! $is_current_payment ) {
					self::skip_older_payment( $order, $gopay_transaction_id, $response->json['state'] );
				}
				// Skip if the order has already been paid (e.g. customer switched
				// payment method to a bank transfer after abandoning the card payment).
				// Without this check, a late TIMEOUTED notification from GoPay would
				// overwrite a paid order back to "failed".
				if ( GOPAY_GATEWAY_ID !== $order->get_payment_method() || $order->is_paid() ) {
					$order->add_order_note( sprintf(
					/* translators: %s: GoPay payment state (e.g. TIMEOUTED). */
						__( 'GoPay returned state "%s", but the order is already paid - status change skipped.', 'gopay-gateway' ),
						$response->json['state']
					) );
					wp_safe_redirect( $order->get_checkout_order_received_url() );
					exit;
				}
				$order->set_status( 'failed' );
				$order->save();
				wp_safe_redirect( $order->get_checkout_order_received_url() );

				break;
			case 'REFUNDED':
				if ( ! $is_current_payment ) {
					self::skip_older_payment( $order, $gopay_transaction_id, $response->json['state'] );
				}
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
	 * Add GoPay transaction id to order
	 * The id becomes the current payment of the order and it is
	 * kept in the payment history, so the order can be found
	 * even when an older payment is paid. Order is not saved.
	 *
	 * @param WC_Order $order          Order.
	 * @param string   $transaction_id GoPay transaction id.
	 *
	 * @since 1.0.37
	 */
	public static function add_transaction_id( WC_Order $order, string $transaction_id ) {
		$order->update_meta_data( 'GoPay_Transaction_id', $transaction_id );

		$transaction_ids = $order->get_meta( '_GoPay_Transaction_ids', false );
		foreach ( $transaction_ids as $meta ) {
			if ( (string) $meta->value === $transaction_id ) {
				return;
			}
		}
		$order->add_meta_data( '_GoPay_Transaction_ids', $transaction_id );
	}

	/**
	 * Find order of the GoPay payment
	 *
	 * @param string   $gopay_transaction_id GoPay transaction id.
	 * @param Response $response             GoPay payment status.
	 *
	 * @return WC_Order|null
	 * @since  1.0.37
	 */
	private static function find_order( string $gopay_transaction_id, Response $response ): ?WC_Order {
		// Current payment of the order and then older payments of the order.
		foreach ( array( 'GoPay_Transaction_id', '_GoPay_Transaction_ids' ) as $meta_key ) {
			$orders = wc_get_orders(
				array(
					'limit'        => 1,
					'meta_key'     => $meta_key,
					'meta_value'   => $gopay_transaction_id,
					'meta_compare' => '=',
				)
			);

			if ( ! empty( $orders ) ) {
				return $orders[0];
			}
		}

		// Order id from notification/return URL, used for orders
		// created before the payment history was saved. Accept it only
		// when the order number of the GoPay payment matches the order.
		$order_id = filter_input( INPUT_GET, 'order_id', FILTER_VALIDATE_INT );
		if ( empty( $order_id ) || 200 != $response->statusCode ||
			empty( $response->json['order_number'] ) ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ||
			(string) $order->get_order_number() !== (string) $response->json['order_number'] ) {
			return null;
		}

		$order->add_meta_data( '_GoPay_Transaction_ids', $gopay_transaction_id );
		$order->save();

		return $order;
	}

	/**
	 * Skip status change caused by older payment of the order
	 *
	 * @param WC_Order $order                Order.
	 * @param string   $gopay_transaction_id GoPay transaction id.
	 * @param string   $state                GoPay payment state.
	 *
	 * @since 1.0.37
	 */
	private static function skip_older_payment( WC_Order $order, string $gopay_transaction_id, string $state ) {
		$order->add_order_note(
			sprintf(
				/* translators: %1$s: GoPay payment state (e.g. TIMEOUTED), %2$s: GoPay payment ID */
				__( 'GoPay returned state "%1$s" for older payment %2$s of the order - status change skipped.', 'gopay-gateway' ),
				$state,
				$gopay_transaction_id
			)
		);
		wp_safe_redirect( $order->get_checkout_order_received_url() );
		exit;
	}

	/**
	 * Notify about order paid by more GoPay payments
	 *
	 * @param WC_Order $order          Order.
	 * @param string   $transaction_id GoPay transaction id of the duplicate payment.
	 * @param Response $response       GoPay payment status.
	 *
	 * @since 1.0.37
	 */
	private static function notify_duplicate_payment( WC_Order $order, string $transaction_id, Response $response ) {
		// Notify only once, GoPay may send the notification repeatedly.
		foreach ( $order->get_meta( '_GoPay_Duplicate_paid_ids', false ) as $meta ) {
			if ( (string) $meta->value === $transaction_id ) {
				return;
			}
		}

		$order->add_meta_data( '_GoPay_Duplicate_paid_ids', $transaction_id );
		if ( ! empty( $order->get_transaction_id() ) ) {
			$note = sprintf(
				/* translators: %1$s: duplicate GoPay payment ID, %2$s: payment ID the order was paid by */
				__( 'Order was paid more times: GoPay payment %1$s was paid, although the order was already paid by the payment %2$s. Please check the payments and refund the duplicate one in the GoPay account.', 'gopay-gateway' ),
				$transaction_id,
				$order->get_transaction_id()
			);
		} else {
			$note = sprintf(
				/* translators: %s: duplicate GoPay payment ID */
				__( 'Order was paid more times: GoPay payment %s was paid, although the order was already paid. Please check the payments and refund the duplicate one in the GoPay account.', 'gopay-gateway' ),
				$transaction_id
			);
		}
		$order->add_order_note( $note );
		$order->save();

		Gopay_Gateway_Log::insert_log(
			array(
				'order_id'       => $order->get_id(),
				'transaction_id' => $transaction_id,
				'message'        => 'Order paid more times - duplicate payment',
				'log_level'      => 'WARNING',
				'log'            => $response,
			)
		);
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
