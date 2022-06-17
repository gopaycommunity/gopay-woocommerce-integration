<?php

use GoPay\Http\Response;
use GoPay\Payments;

/**
 * WooCommerce GoPay API
 * Connect to GoPay API using the GoPay's PHP SDK
 *
 * @package   WooCommerce GoPay gateway
 * @author    argo22
 * @link      https://www.argo22.com
 * @copyright 2022 argo22
 * @since     1.0.0
 */
class Woocommerce_Gopay_API {


	/**
	 * Constructor for the plugin GoPay api
	 *
	 * @since 1.0.0
	 */
	public function __construct() {     }

	/**
	 * GoPay authentication
	 *
	 * @param array $options
	 * @return Payments object
	 * @since  1.0.0
	 */
	public static function auth_GoPay( $options ): Payments {
		return GoPay\payments(
			array(
				'goid'             => $options['goid'],
				'clientId'         => $options['client_id'],
				'clientSecret'     => $options['client_secret'],
				'isProductionMode' => ! ( $options['test'] == 'yes' ),
				'scope'            => GoPay\Definition\TokenScope::ALL,
				'language'         => array_key_exists( 'default_language_gopay_interface', $options ) ?
					$options['default_language_gopay_interface'] : 'EN',
				'timeout'          => 30,
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
			if ( $item->get_tax_status() == 'taxable' ) {
				$tax_rates = WC_Tax::get_base_tax_rates( $item->get_tax_class() );
				if ( ! empty( $tax_rates ) ) {
					$vat_rate = (int) end( $tax_rates )['rate'];
				}
			}

			$items[] = array(
				'type'        => 'ITEM',
				'name'        => $item['name'],
				'product_url' => get_permalink( $item['product_id'] ),
				'amount'      => wc_format_decimal( $item['total'], 2 ) * 100, // Rounding total to 2 decimals
				'count'       => $item['quantity'],
				'vat_rate'    => $vat_rate,
			);
		}

		return $items;
	}

	/**
	 * GoPay create payment
	 *
	 * @param string   $gopay_payment_method payment method.
	 * @param WC_Order $order                order detail.
	 * @param string   $end_date             the end date of recurrence
	 * @param          $is_retry
	 *
	 * @return Response
	 * @since 1.0.0
	 */
	public static function create_payment( string $gopay_payment_method, WC_Order $order,
									string $end_date, $is_retry ): Response {
		$options    = get_option( 'woocommerce_' . WOOCOMMERCE_GOPAY_ID . '_settings' );
		$gopay      = self::auth_GoPay( $options );
		$simplified = $options['simplified_payment_method'] == 'yes';

		$allowed_swifts = array();
		if ( array_key_exists( $gopay_payment_method, Woocommerce_Gopay_Options::supported_banks() ) ) {
			$allowed_swifts       = array( $gopay_payment_method );
			$gopay_payment_method = 'BANK_ACCOUNT';
		}

		if ( empty( $order->get_meta( '_GoPay_payment_method' ) ) || ! $is_retry ) {
			if ( ! $simplified && ! empty( $gopay_payment_method ) ) {
				$default_payment_instrument = $gopay_payment_method;
			} else {
				$default_payment_instrument = '';
			}
		} else {
			$default_payment_instrument = $order->get_meta( '_GoPay_payment_method' );
			$allowed_swifts             = ! empty( $order->get_meta( '_GoPay_bank_swift' ) ) ?
											array( $order->get_meta( '_GoPay_bank_swift' ) ) :
											$allowed_swifts;
		}

		$items = self::get_items( $order );

		$notification_url = add_query_arg(
			array(
				'gopay-api' => WOOCOMMERCE_GOPAY_ID . '_notification',
				'order_id'  => $order->get_id(),
			),
			get_site_url()
		);
		$return_url       = add_query_arg(
			array(
				'gopay-api' => WOOCOMMERCE_GOPAY_ID . '_return',
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
			'country_code' => Woocommerce_Gopay_Options::iso2_to_iso3()[ $order->get_billing_country() ],
		);

		if ( ! empty( $default_payment_instrument ) ) {
			$payer = array(
				'default_payment_instrument'  => $default_payment_instrument,
				'allowed_payment_instruments' => array( $default_payment_instrument ),
				'allowed_swifts'              => $allowed_swifts,
				'contact'                     => $contact,
			);
		} else {
			$payer = array(
				'contact' => $contact,
			);
		}

		$additional_params = array(
			array(
				'name'  => 'invoicenumber',
				'value' => $order->get_order_number(),
			),
		);

		$language = Woocommerce_Gopay_Options::country_to_language()[ $order->get_billing_country() ];
		if ( ! array_key_exists( $language, Woocommerce_Gopay_Options::supported_languages() ) ) {
			$language = $options['default_language_gopay_interface'];
		}

		$data = array(
			'payer'             => $payer,
			'amount'            => $order->get_total() * 100,
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
				'recurrence_date_to' => $end_date != 0 ? $end_date : date( 'Y-m-d', strtotime( '+5 years' ) ),
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

		$options              = get_option( 'woocommerce_' . WOOCOMMERCE_GOPAY_ID . '_settings' );
		$gopay                = self::auth_GoPay( $options );
		$parent_order         = Woocommerce_Gopay_Subscriptions::get_parent_order( $order );
		$GoPay_Transaction_id = $parent_order->get_meta( 'GoPay_Transaction_id', true );

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

		$response = $gopay->createRecurrence( $GoPay_Transaction_id, $data );

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
		$options              = get_option( 'woocommerce_' . WOOCOMMERCE_GOPAY_ID . '_settings' );
		$gopay                = self::auth_GoPay( $options );
		$GoPay_Transaction_id = $subscription->get_parent()->get_meta( 'GoPay_Transaction_id', true );
		$response             = $gopay->voidRecurrence( $GoPay_Transaction_id );

		return $response;
	}

	/**
	 * GoPay get enabled payments methods
	 *
	 * @param string $currency
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function get_enabled_payment_methods( string $currency ): array {
		$options         = get_option( 'woocommerce_' . WOOCOMMERCE_GOPAY_ID . '_settings' );
		$gopay           = self::auth_GoPay( $options );
		$enabledPayments = $gopay->getPaymentInstruments( $options['goid'], $currency );

		$paymentInstruments = array();
		if ( $enabledPayments->statusCode == 200 ) {
			foreach ( $enabledPayments->json['enabledPaymentInstruments'] as $key => $paymentMethod ) {
				if ( $paymentMethod['paymentInstrument'] == 'BANK_ACCOUNT' ) {
					$paymentInstruments[ $paymentMethod['paymentInstrument'] ] = array(
						'label'  => $paymentMethod['label']['cs'],
						'image'  => $paymentMethod['image']['normal'],
						'swifts' => array(),
					);
					$enabledSwifts = $paymentMethod['enabledSwifts'];
					foreach ( $enabledSwifts as $bank ) {
						$paymentInstruments[ $paymentMethod['paymentInstrument'] ]['swifts'][ $bank['swift'] ] = array(
							'label' => $bank['label']['cs'],
							'image' => $bank['image']['normal'],
						);
					}
				} else {
					$paymentInstruments[ $paymentMethod['paymentInstrument'] ] = array(
						'label' => $paymentMethod['label']['cs'],
						'image' => $paymentMethod['image']['normal'],
					);
				}
			}
		}

		return $paymentInstruments;
	}

	/**
	 * Check payment methods and banks that
	 * are enabled on GoPay account.
	 *
	 * @param string
	 * @return array
	 * @since  1.0.0
	 */
	public static function check_enabled_on_GoPay( $currency ): array {
		$options = get_option( 'woocommerce_' . WOOCOMMERCE_GOPAY_ID . '_settings' );
		$gopay   = self::auth_GoPay( $options );

		$payment_methods = array();
		$banks           = array();
		$enabledPayments = $gopay->getPaymentInstruments( $options['goid'], $currency );

		if ( $enabledPayments->statusCode == 200 ) {
			foreach ( $enabledPayments->json['enabledPaymentInstruments'] as $key => $paymentMethod ) {
				$payment_methods[ $paymentMethod['paymentInstrument'] ] = array(
					'label' => __( $paymentMethod['label']['cs'], 'woocommerce-gopay' ),
					'image' => $paymentMethod['image']['normal'],
				);

				if ( $paymentMethod['paymentInstrument'] == 'BANK_ACCOUNT' ) {
					foreach ( $paymentMethod['enabledSwifts'] as $bank ) {
						$banks[ $bank['swift'] ] = array(
							'label'   => __(
								$bank['label']['cs'],
								'woocommerce-gopay'
							),
							'country' => $bank['swift'] != 'OTHERS' ? substr( $bank['swift'], 4, 2 ) : '',
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
	 * @param string $order_id
	 * @param string $GoPay_Transaction_id
	 *
	 * @since  1.0.0
	 */
	public static function check_payment_status( string $GoPay_Transaction_id ) {
		$options  = get_option( 'woocommerce_' . WOOCOMMERCE_GOPAY_ID . '_settings' );
		$gopay    = self::auth_GoPay( $options );
		$response = $gopay->getStatus( $GoPay_Transaction_id );

		$orders = wc_get_orders(
			array(
				'limit'        => 1,
				'meta_key'     => 'GoPay_Transaction_id',
				'meta_value'   => $GoPay_Transaction_id,
				'meta_compare' => '=',
			)
		);

		if ( ! empty( $orders ) ) {
			$order = $orders[0];
		} else {
			return;
		}

		// Save log
		$log = array(
			'order_id'       => $order->get_id(),
			'transaction_id' => $response->statusCode == 200 ? $response->json['id'] : '0',
			'message'        => $response->statusCode == 200 ? 'Checking payment status' :
																	'Error checking payment status',
			'log_level'      => $response->statusCode == 200 ? 'INFO' : 'ERROR',
			'log'            => $response,
		);
		Woocommerce_Gopay_Log::insert_log( $log );

		if ( $response->statusCode != 200 ) {
			return;
		}

		switch ( $response->json['state'] ) {
			case 'PAID':
				// Check if all products are either virtual or downloadable
				$all_virtual_downloadable = true;
				foreach ( $order->get_items() as $item ) {
					$product = wc_get_product( $item['product_id'] );
					if ( ! $product->is_virtual() && ! $product->is_downloadable() ) {
						$all_virtual_downloadable = false;
						break;
					}
				}

				if ( $all_virtual_downloadable ) {
					$order->set_status( 'completed' );
				} else {
					$order->set_status( 'processing' );
				}

				// Update retry status
				if ( class_exists( 'WCS_Retry_Manager', false ) ) {
					$retry = WCS_Retry_Manager::store()->get_last_retry_for_order(
						wcs_get_objects_property( $order, 'id' )
					);
					if ( ! empty( $retry ) ) {
						$retry->update_status( 'complete' );
					}
				}

				$order->save();
				wp_redirect( $order->get_checkout_order_received_url() );

				break;
			case 'PAYMENT_METHOD_CHOSEN':
			case 'AUTHORIZED':
			case 'CREATED':
				wp_redirect( $order->get_checkout_order_received_url() );

				break;
			case 'TIMEOUTED':
			case 'CANCELED':
				$order->set_status( 'failed' );
				$order->save();
				wp_redirect( $order->get_checkout_order_received_url() );

				break;
			case 'REFUNDED':
				$order->set_status( 'refunded' );
				$order->save();
				wp_redirect( $order->get_checkout_order_received_url() );

				break;
		}
	}

	/**
	 * Get status of the transaction
	 *
	 * @since  1.0.0
	 */
	public static function get_status( $order_id ): Response {
		$options              = get_option( 'woocommerce_' . WOOCOMMERCE_GOPAY_ID . '_settings' );
		$gopay                = self::auth_GoPay( $options );
		$GoPay_Transaction_id = get_post_meta( $order_id, 'GoPay_Transaction_id', true );
		$response             = $gopay->getStatus( $GoPay_Transaction_id );

		return $response;
	}

	/**
	 * Refund payment
	 *
	 * @param int    $transaction_id
	 * @param string $amount
	 *
	 * @return Response $response
	 * @since  1.0.0
	 */
	public static function refund_payment( int $transaction_id, string $amount ): Response {
		$options  = get_option( 'woocommerce_' . WOOCOMMERCE_GOPAY_ID . '_settings' );
		$gopay    = self::auth_GoPay( $options );
		$response = $gopay->refundPayment( $transaction_id, $amount );

		return $response;
	}
}
