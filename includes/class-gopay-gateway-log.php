<?php
/**
 * GoPay gateway log
 * Insert log into database
 *
 * @package   GoPay gateway
 * @author    GoPay
 * @link      https://www.gopay.com/
 * @copyright 2022 GoPay
 * @since     1.0.0
 */

/**
 * Plugin log
 *
 * @since 1.0.0
 */
class Gopay_Gateway_Log {


	/**
	 * Constructor for the plugin log
	 *
	 * @since 1.0.0
	 */
	public function __construct() { }

	/**
	 * Insert log into the database
	 *
	 * @param array $log Log text.
	 *
	 * @since  1.0.0
	 */
	public static function insert_log( array $log ) {
		global $wpdb;

		$table_name = $wpdb->prefix . GOPAY_GATEWAY_LOG_TABLE_NAME;
		$data       = array(
			'order_id'       => $log['order_id'],
			'transaction_id' => $log['transaction_id'],
			'message'        => $log['message'],
			'created_at'     => gmdate( 'Y-m-d H:i:s' ),
			'log_level'      => $log['log_level'],
			'log'            => wp_json_encode( $log['log'] ),
		);
		$where      = array(
			'order_id'       => $log['order_id'],
			'transaction_id' => $log['transaction_id'],
			'message'        => $log['message'],
		);

		$response = $wpdb->update( $table_name, $data, $where );
		if ( false === $response || $response < 1 ) {
			$wpdb->insert( $table_name, $data );
		}
	}

	public static function update_database() {
		global $wpdb;
		$stored_version = get_option('gopay_woocommerce_version');
		$table_name = $wpdb->prefix . GOPAY_GATEWAY_TABLE_CARDS;

		$table_exists = $wpdb->get_var(
			$wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
		);

		if ($stored_version !== GOPAY_WOOCOMMERCE_VERSION || $table_exists !== $table_name) {

			self::create_cards_table();

			update_option('gopay_woocommerce_version', GOPAY_WOOCOMMERCE_VERSION);
		}
	}

	public static function create_cards_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . GOPAY_GATEWAY_TABLE_CARDS;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			card_id VARCHAR(50) NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_user_card (user_id, card_id),
			KEY user_id (user_id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	public static function insert_saved_card($user_id, $card_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . GOPAY_GATEWAY_TABLE_CARDS;

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND card_id = %s",
				$user_id,
				$card_id
			)
		);

		if ( $exists > 0 ) {
			return;
		}

		$wpdb->insert(
			$table_name,
			array(
				'user_id'    => $user_id,
				'card_id'    => $card_id,
				'created_at' => current_time('mysql'),
			),
			array(
				'%d',
				'%s',
				'%s',
			)
		);
	}

	public static function delete_customer_card($user_id, $card_id) {
		global $wpdb;
		$table_name = $wpdb->prefix . GOPAY_GATEWAY_TABLE_CARDS;

		if ( empty($card_id) ) {
			return false;
		}

		$deleted = $wpdb->delete(
			$table_name,
			['user_id' => $user_id, 'card_id' => $card_id],
			['%d', '%s']
		);

		return (bool) $deleted;
	}
}
