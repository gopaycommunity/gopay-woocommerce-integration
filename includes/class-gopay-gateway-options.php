<?php
/**
 * GoPay gateway lists of supported
 * options default
 * Lists of supported options for languages,
 * payment methods, banks, shipping methods,
 * countries and currencies
 *
 * @package   GoPay gateway
 * @author    GoPay
 * @link      https://www.gopay.com/
 * @copyright 2022 GoPay
 * @since     1.0.0
 */

/**
 * GoPay available options
 *
 * @since 1.0.0
 */
class Gopay_Gateway_Options {


	/**
	 * Return supported currencies that
	 * can be used in the gateway
	 *
	 * @return array
	 */
	public static function supported_currencies(): array {
		return array(
			'CZK' => __( 'Czech koruna', 'gopay-gateway' ),
			'EUR' => __( 'Euro', 'gopay-gateway' ),
			'PLN' => __( 'Polish złoty', 'gopay-gateway' ),
			'USD' => __( 'United States dollar', 'gopay-gateway' ),
			'GBP' => __( 'Pound sterling', 'gopay-gateway' ),
			'HUF' => __( 'Hungarian forint', 'gopay-gateway' ),
			'RON' => __( 'Romanian lei', 'gopay-gateway' ),
			'BGN' => __( 'Bulgarian lev', 'gopay-gateway' ),
			'HRK' => __( 'Croatian kuna', 'gopay-gateway' ),
		);
	}

	/**
	 * Return supported languages that
	 * can be used in the gateway
	 *
	 * @return array
	 */
	public static function supported_languages(): array {
		return array(
			'CS' => __( 'Czech', 'gopay-gateway' ),
			'SK' => __( 'Slovak', 'gopay-gateway' ),
			'EN' => __( 'English', 'gopay-gateway' ),
			'DE' => __( 'German', 'gopay-gateway' ),
			'RU' => __( 'Russian', 'gopay-gateway' ),
			'PL' => __( 'Polish', 'gopay-gateway' ),
			'HU' => __( 'Hungarian', 'gopay-gateway' ),
			'RO' => __( 'Romanian', 'gopay-gateway' ),
			'BG' => __( 'Bulgarian', 'gopay-gateway' ),
			'HR' => __( 'Croatian', 'gopay-gateway' ),
			'IT' => __( 'Italian', 'gopay-gateway' ),
			'FR' => __( 'French', 'gopay-gateway' ),
			'ES' => __( 'Spanish', 'gopay-gateway' ),
			'UK' => __( 'Ukrainian', 'gopay-gateway' ),
		);
	}

	/**
	 * Return countries as keys and the language spoken
	 * in the country as values.
	 * If country has more than one spoken language than
	 * the one with the highest number of speakers is returned.
	 *
	 * @return array
	 */
	public static function country_to_language(): array {
		// Extracted from geonames.org (http://download.geonames.org/export/dump/countryInfo.txt).
		return array(
			'AD' => 'CA',
			'AE' => 'AR',
			'AF' => 'FA',
			'AG' => 'EN',
			'AI' => 'EN',
			'AL' => 'SQ',
			'AM' => 'HY',
			'AO' => 'PT',
			'AR' => 'ES',
			'AS' => 'EN',
			'AT' => 'DE',
			'AU' => 'EN',
			'AW' => 'NL',
			'AX' => 'SV',
			'AZ' => 'AZ',
			'BA' => 'BS',
			'BB' => 'EN',
			'BD' => 'BN',
			'BE' => 'NL',
			'BF' => 'FR',
			'BG' => 'BG',
			'BH' => 'AR',
			'BI' => 'FR',
			'BJ' => 'FR',
			'BL' => 'FR',
			'BM' => 'EN',
			'BN' => 'MS',
			'BO' => 'ES',
			'BQ' => 'NL',
			'BR' => 'PT',
			'BS' => 'EN',
			'BT' => 'DZ',
			'BW' => 'EN',
			'BY' => 'BE',
			'BZ' => 'EN',
			'CA' => 'EN',
			'CC' => 'MS',
			'CD' => 'FR',
			'CF' => 'FR',
			'CG' => 'FR',
			'CH' => 'DE',
			'CI' => 'FR',
			'CK' => 'EN',
			'CL' => 'ES',
			'CM' => 'EN',
			'CN' => 'ZH',
			'CO' => 'ES',
			'CR' => 'ES',
			'CU' => 'ES',
			'CV' => 'PT',
			'CW' => 'NL',
			'CX' => 'EN',
			'CY' => 'EL',
			'CZ' => 'CS',
			'DE' => 'DE',
			'DJ' => 'FR',
			'DK' => 'DA',
			'DM' => 'EN',
			'DO' => 'ES',
			'DZ' => 'AR',
			'EC' => 'ES',
			'EE' => 'ET',
			'EG' => 'AR',
			'EH' => 'AR',
			'ER' => 'AA',
			'ES' => 'ES',
			'ET' => 'AM',
			'FI' => 'FI',
			'FJ' => 'EN',
			'FK' => 'EN',
			'FM' => 'EN',
			'FO' => 'FO',
			'FR' => 'FR',
			'GA' => 'FR',
			'GB' => 'EN',
			'GD' => 'EN',
			'GE' => 'KA',
			'GF' => 'FR',
			'GG' => 'EN',
			'GH' => 'EN',
			'GI' => 'EN',
			'GL' => 'KL',
			'GM' => 'EN',
			'GN' => 'FR',
			'GP' => 'FR',
			'GQ' => 'ES',
			'GR' => 'EL',
			'GS' => 'EN',
			'GT' => 'ES',
			'GU' => 'EN',
			'GW' => 'PT',
			'GY' => 'EN',
			'HK' => 'ZH',
			'HN' => 'ES',
			'HR' => 'HR',
			'HT' => 'HT',
			'HU' => 'HU',
			'ID' => 'ID',
			'IE' => 'EN',
			'IL' => 'HE',
			'IM' => 'EN',
			'IN' => 'EN',
			'IO' => 'EN',
			'IQ' => 'AR',
			'IR' => 'FA',
			'IS' => 'IS',
			'IT' => 'IT',
			'JE' => 'EN',
			'JM' => 'EN',
			'JO' => 'AR',
			'JP' => 'JA',
			'KE' => 'EN',
			'KG' => 'KY',
			'KH' => 'KM',
			'KI' => 'EN',
			'KM' => 'AR',
			'KN' => 'EN',
			'KP' => 'KO',
			'KR' => 'KO',
			'XK' => 'SQ',
			'KW' => 'AR',
			'KY' => 'EN',
			'KZ' => 'KK',
			'LA' => 'LO',
			'LB' => 'AR',
			'LC' => 'EN',
			'LI' => 'DE',
			'LK' => 'SI',
			'LR' => 'EN',
			'LS' => 'EN',
			'LT' => 'LT',
			'LU' => 'LB',
			'LV' => 'LV',
			'LY' => 'AR',
			'MA' => 'AR',
			'MC' => 'FR',
			'MD' => 'RO',
			'ME' => 'SR',
			'MF' => 'FR',
			'MG' => 'FR',
			'MH' => 'MH',
			'MK' => 'MK',
			'ML' => 'FR',
			'MM' => 'MY',
			'MN' => 'MN',
			'MO' => 'ZH',
			'MP' => 'FIL',
			'MQ' => 'FR',
			'MR' => 'AR',
			'MS' => 'EN',
			'MT' => 'MT',
			'MU' => 'EN',
			'MV' => 'DV',
			'MW' => 'NY',
			'MX' => 'ES',
			'MY' => 'MS',
			'MZ' => 'PT',
			'NA' => 'EN',
			'NC' => 'FR',
			'NE' => 'FR',
			'NF' => 'EN',
			'NG' => 'EN',
			'NI' => 'ES',
			'NL' => 'NL',
			'NO' => 'NO',
			'NP' => 'NE',
			'NR' => 'NA',
			'NU' => 'NIU',
			'NZ' => 'EN',
			'OM' => 'AR',
			'PA' => 'ES',
			'PE' => 'ES',
			'PF' => 'FR',
			'PG' => 'EN',
			'PH' => 'TL',
			'PK' => 'UR',
			'PL' => 'PL',
			'PM' => 'FR',
			'PN' => 'EN',
			'PR' => 'EN',
			'PS' => 'AR',
			'PT' => 'PT',
			'PW' => 'PAU',
			'PY' => 'ES',
			'QA' => 'AR',
			'RE' => 'FR',
			'RO' => 'RO',
			'RS' => 'SR',
			'RU' => 'RU',
			'RW' => 'RW',
			'SA' => 'AR',
			'SB' => 'EN',
			'SC' => 'EN',
			'SD' => 'AR',
			'SS' => 'EN',
			'SE' => 'SV',
			'SG' => 'CMN',
			'SH' => 'EN',
			'SI' => 'SL',
			'SJ' => 'NO',
			'SK' => 'SK',
			'SL' => 'EN',
			'SM' => 'IT',
			'SN' => 'FR',
			'SO' => 'SO',
			'SR' => 'NL',
			'ST' => 'PT',
			'SV' => 'ES',
			'SX' => 'NL',
			'SY' => 'AR',
			'SZ' => 'EN',
			'TC' => 'EN',
			'TD' => 'FR',
			'TF' => 'FR',
			'TG' => 'FR',
			'TH' => 'TH',
			'TJ' => 'TG',
			'TK' => 'TKL',
			'TL' => 'TET',
			'TM' => 'TK',
			'TN' => 'AR',
			'TO' => 'TO',
			'TR' => 'TR',
			'TT' => 'EN',
			'TV' => 'TVL',
			'TW' => 'ZH',
			'TZ' => 'SW',
			'UA' => 'UK',
			'UG' => 'EN',
			'UM' => 'EN',
			'US' => 'EN',
			'UY' => 'ES',
			'UZ' => 'UZ',
			'VA' => 'LA',
			'VC' => 'EN',
			'VE' => 'ES',
			'VG' => 'EN',
			'VI' => 'EN',
			'VN' => 'VI',
			'VU' => 'BI',
			'WF' => 'WLS',
			'WS' => 'SM',
			'YE' => 'AR',
			'YT' => 'FR',
			'ZA' => 'ZU',
			'ZM' => 'EN',
			'ZW' => 'EN',
			'CS' => 'CU',
			'AN' => 'NL',
		);
	}

	/**
	 * Get languages by country
	 *
	 * @param string $country country code.
	 * @return array
	 */
	public static function get_languages_by_country( string $country ) {
		$locales = ResourceBundle::getLocales( '' );

		$matches = array();
		foreach ( $locales as $key => $locale ) {
			if ( Locale::getRegion( $locale ) == $country ) {
				$matches[ Locale::getPrimaryLanguage( $locale ) ][] = $locale;
			}
		}

		return $matches;
	}

	/**
	 * Return supported countries where
	 * the gateway can be available
	 *
	 * @return array
	 */
	public static function supported_countries(): array {
		return ! empty( WC()->countries ) ? WC()->countries->get_allowed_countries() : array();
	}

	/**
	 * Return supported shipping methods that
	 * the gateway can use
	 *
	 * @return array
	 */
	public static function supported_shipping_methods(): array {
		// Only in admin context.
		if ( ! is_admin() ) {
			if ( ! isset ( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
				return array();
			}
			if ( ! isset ( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
				return array();
			}
			if ( ! isset ( $_REQUEST['section'] ) || 'wc_gopay_gateway' !== $_REQUEST['section'] ) {
				return array();
			}
		}

		if ( empty( WC()->countries ) ) {
			return array();
		}

		// Get all shipping zones
		$shipping_zones = WC_Shipping_Zones::get_zones();
		$all_enabled_shipping_methods = array();
	
		foreach ($shipping_zones as $zone_data) {
			$zone = WC_Shipping_Zones::get_zone($zone_data['zone_id']);
	
			// Get enabled shipping methods for zone
			$enabled_shipping_methods = $zone->get_shipping_methods(true);
	
			foreach ($enabled_shipping_methods as $shipping_method) {
				// Check if the method is already added
				if (!isset($all_enabled_shipping_methods[$shipping_method->id])) {
					$all_enabled_shipping_methods[$shipping_method->id] = $shipping_method->get_method_title();
				}
			}
		}

		if ( class_exists( '\Automattic\WooCommerce\Blocks\Shipping\PickupLocation' ) ) {
			$pickup_class = new Automattic\WooCommerce\Blocks\Shipping\PickupLocation();

			// Add Local Pickup option only if there is a pickup location defined
			$pickup_locations = get_option( 'pickup_location_pickup_locations', array() );

			if ( ! empty( $pickup_locations ) && is_array( $pickup_locations ) ) {
				$all_enabled_shipping_methods[$pickup_class->id] = $pickup_class->title;
			}
		}

		return $all_enabled_shipping_methods;
	}

	/**
	 * Return supported payment methods that
	 * the gateway can use
	 *
	 * @return array
	 */
	public static function supported_payment_methods(): array {
		// Supported payment methods according to https://doc.gopay.com/#payment-instrument !
		$payment_methods = array(
			'PAYMENT_CARD' => array( 'label' => __( 'Payment card', 'gopay-gateway' ) ),
			'BANK_ACCOUNT' => array( 'label' => __( 'Bank account', 'gopay-gateway' ) ),
			'GPAY'         => array( 'label' => __( 'Google Pay', 'gopay-gateway' ) ),
			'APPLE_PAY'    => array( 'label' => __( 'Apple Pay', 'gopay-gateway' ) ),
			'GOPAY'        => array( 'label' => __( 'GoPay wallet', 'gopay-gateway' ) ),
			'TWISTO'       => array( 'label' => __( 'Twisto', 'gopay-gateway' ) ),
			'SKIPPAY'      => array( 'label' => __( 'Skip Pay', 'gopay-gateway' ) ),
			'PAYPAL'       => array( 'label' => __( 'PayPal wallet', 'gopay-gateway' ) ),
			'MPAYMENT'     => array( 'label' => __( 'mPlatba (mobile payment)', 'gopay-gateway' ) ),
			'PRSMS'        => array( 'label' => __( 'Premium SMS', 'gopay-gateway' ) ),
			'PAYSAFECARD'  => array( 'label' => __( 'PaySafeCard coupon', 'gopay-gateway' ) ),
			'BITCOIN'      => array( 'label' => __( 'Bitcoin wallet', 'gopay-gateway' ) ),
			'CLICK_TO_PAY' => array( 'label' => __( 'Click to Pay', 'gopay-gateway' ) ),
		);

		$options = get_option( 'woocommerce_wc_gopay_gateway_settings', array() );
		$key     = 'option_gopay_payment_methods';

		return ! empty( $options ) && array_key_exists( $key, $options ) && ! empty( $options[ $key ] ) ?
			array_intersect_key( $payment_methods, $options[ $key ] ) : $payment_methods;
	}

	/**
	 * Return supported banks for bank payment that
	 * the gateway can use
	 *
	 * @return array
	 */
	public static function supported_banks(): array {

		// Supported banks according to https://doc.gopay.com/#swift !
		$banks = array(
			'GIBACZPX'     => array(
				'label'   => __( 'Česká Spořitelna', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'KOMBCZPP'     => array(
				'label'   => __( 'Komerční Banka', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'RZBCCZPP'     => array(
				'label'   => __( 'Raiffeisenbank', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'FIOBCZPP'     => array(
				'label'   => __( 'FIO Banka', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'BACXCZPP'     => array(
				'label'   => __( 'UniCredit Bank', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'BREXCZPP'     => array(
				'label'   => __( 'mBank', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'CEKOCZPP'     => array(
				'label'   => __( 'ČSOB', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'CEKOCZPP-ERA' => array(
				'label'   => __( 'Poštovní Spořitelna', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'AGBACZPP'     => array(
				'label'   => __( 'Moneta Money Bank', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'AIRACZPP'     => array(
				'label'   => __( 'AirBank', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'EQBKCZPP'     => array(
				'label'   => __( 'EQUA Bank', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'INGBCZPP'     => array(
				'label'   => __( 'ING Bank', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'EXPNCZPP'     => array(
				'label'   => __( 'Expobank', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'OBKLCZ2X'     => array(
				'label'   => __( 'OberBank AG', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'SUBACZPP'     => array(
				'label'   => __( 'Všeobecná Úvěrová Banka - pobočka Praha', 'gopay-gateway' ),
				'country' => 'CZ',
			),
			'TATRSKBX'     => array(
				'label'   => __( 'Tatra Banka', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'SUBASKBX'     => array(
				'label'   => __( 'Všeobecná Úverová Banka', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'UNCRSKBX'     => array(
				'label'   => __( 'UniCredit Bank SK', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'GIBASKBX'     => array(
				'label'   => __( 'Slovenská Sporiteľňa', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'POBNSKBA'     => array(
				'label'   => __( 'Poštová Banka', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'OTPVSKBX'     => array(
				'label'   => __( 'OTP Banka', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'KOMASK2X'     => array(
				'label'   => __( 'Prima Banka', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'CITISKBA'     => array(
				'label'   => __( 'Citibank Europe', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'FIOZSKBA'     => array(
				'label'   => __( 'FIO Banka SK', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'INGBSKBX'     => array(
				'label'   => __( 'ING Wholesale Banking SK', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'BREXSKBX'     => array(
				'label'   => __( 'mBank SK', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'JTBPSKBA'     => array(
				'label'   => __( 'J&T Banka SK', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'OBKLSKBA'     => array(
				'label'   => __( 'OberBank AG SK', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'BSLOSK22'     => array(
				'label'   => __( 'Privatbanka', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'BFKKSKBB'     => array(
				'label'   => __( 'BKS Bank AG SK', 'gopay-gateway' ),
				'country' => 'SK',
			),
			'GBGCPLPK'     => array(
				'label'   => __( 'Getin Bank', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'NESBPLPW'     => array(
				'label'   => __( 'Nest Bank', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'VOWAPLP9'     => array(
				'label'   => __( 'Volkswagen Bank', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'CITIPLPX'     => array(
				'label'   => __( 'Citi handlowy', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'WBKPPLPP'     => array(
				'label'   => __( 'Santander', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'BIGBPLPW'     => array(
				'label'   => __( 'Millenium Bank', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'EBOSPLPW'     => array(
				'label'   => __( 'Bank Ochrony Srodowiska', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'PKOPPLPW'     => array(
				'label'   => __( 'Pekao Bank', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'PPABPLPK'     => array(
				'label'   => __( 'BNP Paribas', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'BPKOPLPW'     => array(
				'label'   => __( 'OWSZECHNA KASA OSZCZEDNOSCI BANK POLSK', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'AGRIPLPR'     => array(
				'label'   => __( 'Credit Agricole Banka Polska', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'GBGCPLPK-NOB' => array(
				'label'   => __( 'Noble Bank', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'POLUPLPR'     => array(
				'label'   => __( 'BPS/Bank Nowy BFG', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'BREXPLPW'     => array(
				'label'   => __( 'mBank PL', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'INGBPLPW'     => array(
				'label'   => __( 'ING Bank PL', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'ALBPPLPW'     => array(
				'label'   => __( 'Alior', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'IEEAPLPA'     => array(
				'label'   => __( 'IdeaBank', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'POCZPLP4'     => array(
				'label'   => __( 'Pocztowy24', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'IVSEPLPP'     => array(
				'label'   => __( 'Plus Bank', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'TOBAPLPW'     => array(
				'label'   => __( 'Toyota Bank', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'GBGCPLPK-BLIK'     => array(
				'label'   => __( 'Blik', 'gopay-gateway' ),
				'country' => 'PL',
			),
			'OTHERS'       => array(
				'label'   => __( 'Another bank', 'gopay-gateway' ),
				'country' => '',
			),
		);

		$options = get_option( 'woocommerce_wc_gopay_gateway_settings', array() );
		$key     = 'option_gopay_banks';

		return ! empty( $options ) && array_key_exists( $key, $options ) && ! empty( $options[ $key ] ) ?
			array_intersect_key( $banks, $options[ $key ] ) : $banks;
	}

	/**
	 * Return iso 2 as keys and iso 3 equivalence as values
	 *
	 * @return array
	 */
	public static function iso2_to_iso3(): array {
		// Extracted from geonames.org (http://download.geonames.org/export/dump/countryInfo.txt) !
		return array(
			'AD' => 'AND',
			'AE' => 'ARE',
			'AF' => 'AFG',
			'AG' => 'ATG',
			'AI' => 'AIA',
			'AL' => 'ALB',
			'AM' => 'ARM',
			'AO' => 'AGO',
			'AQ' => 'ATA',
			'AR' => 'ARG',
			'AS' => 'ASM',
			'AT' => 'AUT',
			'AU' => 'AUS',
			'AW' => 'ABW',
			'AX' => 'ALA',
			'AZ' => 'AZE',
			'BA' => 'BIH',
			'BB' => 'BRB',
			'BD' => 'BGD',
			'BE' => 'BEL',
			'BF' => 'BFA',
			'BG' => 'BGR',
			'BH' => 'BHR',
			'BI' => 'BDI',
			'BJ' => 'BEN',
			'BL' => 'BLM',
			'BM' => 'BMU',
			'BN' => 'BRN',
			'BO' => 'BOL',
			'BQ' => 'BES',
			'BR' => 'BRA',
			'BS' => 'BHS',
			'BT' => 'BTN',
			'BV' => 'BVT',
			'BW' => 'BWA',
			'BY' => 'BLR',
			'BZ' => 'BLZ',
			'CA' => 'CAN',
			'CC' => 'CCK',
			'CD' => 'COD',
			'CF' => 'CAF',
			'CG' => 'COG',
			'CH' => 'CHE',
			'CI' => 'CIV',
			'CK' => 'COK',
			'CL' => 'CHL',
			'CM' => 'CMR',
			'CN' => 'CHN',
			'CO' => 'COL',
			'CR' => 'CRI',
			'CU' => 'CUB',
			'CV' => 'CPV',
			'CW' => 'CUW',
			'CX' => 'CXR',
			'CY' => 'CYP',
			'CZ' => 'CZE',
			'DE' => 'DEU',
			'DJ' => 'DJI',
			'DK' => 'DNK',
			'DM' => 'DMA',
			'DO' => 'DOM',
			'DZ' => 'DZA',
			'EC' => 'ECU',
			'EE' => 'EST',
			'EG' => 'EGY',
			'EH' => 'ESH',
			'ER' => 'ERI',
			'ES' => 'ESP',
			'ET' => 'ETH',
			'FI' => 'FIN',
			'FJ' => 'FJI',
			'FK' => 'FLK',
			'FM' => 'FSM',
			'FO' => 'FRO',
			'FR' => 'FRA',
			'GA' => 'GAB',
			'GB' => 'GBR',
			'GD' => 'GRD',
			'GE' => 'GEO',
			'GF' => 'GUF',
			'GG' => 'GGY',
			'GH' => 'GHA',
			'GI' => 'GIB',
			'GL' => 'GRL',
			'GM' => 'GMB',
			'GN' => 'GIN',
			'GP' => 'GLP',
			'GQ' => 'GNQ',
			'GR' => 'GRC',
			'GS' => 'SGS',
			'GT' => 'GTM',
			'GU' => 'GUM',
			'GW' => 'GNB',
			'GY' => 'GUY',
			'HK' => 'HKG',
			'HM' => 'HMD',
			'HN' => 'HND',
			'HR' => 'HRV',
			'HT' => 'HTI',
			'HU' => 'HUN',
			'ID' => 'IDN',
			'IE' => 'IRL',
			'IL' => 'ISR',
			'IM' => 'IMN',
			'IN' => 'IND',
			'IO' => 'IOT',
			'IQ' => 'IRQ',
			'IR' => 'IRN',
			'IS' => 'ISL',
			'IT' => 'ITA',
			'JE' => 'JEY',
			'JM' => 'JAM',
			'JO' => 'JOR',
			'JP' => 'JPN',
			'KE' => 'KEN',
			'KG' => 'KGZ',
			'KH' => 'KHM',
			'KI' => 'KIR',
			'KM' => 'COM',
			'KN' => 'KNA',
			'KP' => 'PRK',
			'KR' => 'KOR',
			'KW' => 'KWT',
			'KY' => 'CYM',
			'KZ' => 'KAZ',
			'LA' => 'LAO',
			'LB' => 'LBN',
			'LC' => 'LCA',
			'LI' => 'LIE',
			'LK' => 'LKA',
			'LR' => 'LBR',
			'LS' => 'LSO',
			'LT' => 'LTU',
			'LU' => 'LUX',
			'LV' => 'LVA',
			'LY' => 'LBY',
			'MA' => 'MAR',
			'MC' => 'MCO',
			'MD' => 'MDA',
			'ME' => 'MNE',
			'MF' => 'MAF',
			'MG' => 'MDG',
			'MH' => 'MHL',
			'MK' => 'MKD',
			'ML' => 'MLI',
			'MM' => 'MMR',
			'MN' => 'MNG',
			'MO' => 'MAC',
			'MP' => 'MNP',
			'MQ' => 'MTQ',
			'MR' => 'MRT',
			'MS' => 'MSR',
			'MT' => 'MLT',
			'MU' => 'MUS',
			'MV' => 'MDV',
			'MW' => 'MWI',
			'MX' => 'MEX',
			'MY' => 'MYS',
			'MZ' => 'MOZ',
			'NA' => 'NAM',
			'NC' => 'NCL',
			'NE' => 'NER',
			'NF' => 'NFK',
			'NG' => 'NGA',
			'NI' => 'NIC',
			'NL' => 'NLD',
			'NO' => 'NOR',
			'NP' => 'NPL',
			'NR' => 'NRU',
			'NU' => 'NIU',
			'NZ' => 'NZL',
			'OM' => 'OMN',
			'PA' => 'PAN',
			'PE' => 'PER',
			'PF' => 'PYF',
			'PG' => 'PNG',
			'PH' => 'PHL',
			'PK' => 'PAK',
			'PL' => 'POL',
			'PM' => 'SPM',
			'PN' => 'PCN',
			'PR' => 'PRI',
			'PS' => 'PSE',
			'PT' => 'PRT',
			'PW' => 'PLW',
			'PY' => 'PRY',
			'QA' => 'QAT',
			'RE' => 'REU',
			'RO' => 'ROU',
			'RS' => 'SRB',
			'RU' => 'RUS',
			'RW' => 'RWA',
			'SA' => 'SAU',
			'SB' => 'SLB',
			'SC' => 'SYC',
			'SD' => 'SDN',
			'SE' => 'SWE',
			'SG' => 'SGP',
			'SH' => 'SHN',
			'SI' => 'SVN',
			'SJ' => 'SJM',
			'SK' => 'SVK',
			'SL' => 'SLE',
			'SM' => 'SMR',
			'SN' => 'SEN',
			'SO' => 'SOM',
			'SR' => 'SUR',
			'SS' => 'SSD',
			'ST' => 'STP',
			'SV' => 'SLV',
			'SX' => 'SXM',
			'SY' => 'SYR',
			'SZ' => 'SWZ',
			'TC' => 'TCA',
			'TD' => 'TCD',
			'TF' => 'ATF',
			'TG' => 'TGO',
			'TH' => 'THA',
			'TJ' => 'TJK',
			'TK' => 'TKL',
			'TL' => 'TLS',
			'TM' => 'TKM',
			'TN' => 'TUN',
			'TO' => 'TON',
			'TR' => 'TUR',
			'TT' => 'TTO',
			'TV' => 'TUV',
			'TW' => 'TWN',
			'TZ' => 'TZA',
			'UA' => 'UKR',
			'UG' => 'UGA',
			'UM' => 'UMI',
			'US' => 'USA',
			'UY' => 'URY',
			'UZ' => 'UZB',
			'VA' => 'VAT',
			'VC' => 'VCT',
			'VE' => 'VEN',
			'VG' => 'VGB',
			'VI' => 'VIR',
			'VN' => 'VNM',
			'VU' => 'VUT',
			'WF' => 'WLF',
			'WS' => 'WSM',
			'XK' => 'XKX',
			'YE' => 'YEM',
			'YT' => 'MYT',
			'ZA' => 'ZAF',
			'ZM' => 'ZMB',
			'ZW' => 'ZWE',
		);
	}
}
