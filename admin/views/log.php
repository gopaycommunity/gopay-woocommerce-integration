<?php
/**
 * Plugin admin log
 *
 * @package   GoPay gateway
 * @author    GoPay
 * @link      https://www.gopay.com/
 * @copyright 2022 GoPay
 * @since     1.0.0
 */

defined( 'ABSPATH' ) || exit;

$pagenum          = isset( $_GET['pagenum'] ) ? absint( wp_unslash( $_GET['pagenum'] ) ) : 0;
$log_table_filter = isset( $_GET['log_table_filter'] )
	? sanitize_text_field( wp_unslash( $_GET['log_table_filter'] ) )
	: '';

global $wpdb;
$log_table = $wpdb->prefix . GOPAY_GATEWAY_LOG_TABLE_NAME;
$like      = '%' . $wpdb->esc_like( strtoupper( $log_table_filter ) ) . '%';

$rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT COUNT(*) as num_rows FROM {$log_table}
                WHERE UPPER(CONCAT(order_id, transaction_id, message, created_at, log_level, log)) LIKE %s",
		$like
	)
);

$results_per_page = 20;
$number_of_rows   = $rows[0]->num_rows;
$number_of_pages  = ceil( $number_of_rows / $results_per_page );

if ( $pagenum < 1 ) {
	$pagenum = 1;
}

$page_pagination = ( $pagenum - 1 ) * $results_per_page;
$log_data        = $page_pagination >= 0 ? $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$log_table}
                WHERE UPPER(CONCAT(order_id, transaction_id, message, created_at, log_level, log)) LIKE %s
                ORDER BY created_at DESC LIMIT %d,%d",
		$like,
		$page_pagination,
		$results_per_page
	)
) : array();

?>

<div class="wrap">
	<div class="gopay-gateway-menu">
		<h1><?php echo wp_kses_post( __( 'GoPay gateway', 'gopay-gateway' ) ); ?></h1>
	</div>

	<div class="gopay-gateway-menu">
		<table>
			<thead>
				<tr>
					<th><?php echo wp_kses_post( __( 'Id', 'gopay-gateway' ) ); ?></th>
					<th><?php echo wp_kses_post( __( 'Order id', 'gopay-gateway' ) ); ?></th>
					<th><?php echo wp_kses_post( __( 'Transaction id', 'gopay-gateway' ) ); ?></th>
					<th><?php echo wp_kses_post( __( 'Message', 'gopay-gateway' ) ); ?></th>
					<th><?php echo wp_kses_post( __( 'Created at', 'gopay-gateway' ) ); ?></th>
					<th><?php echo wp_kses_post( __( 'Log level', 'gopay-gateway' ) ); ?></th>
					<th><?php echo wp_kses_post( __( 'Log', 'gopay-gateway' ) ); ?></th>
				</tr>
			</thead>
			<tbody id="log_table_body">
				<?php
				foreach ( $log_data as $log ) {
					$order_log = wc_get_order( $log->order_id );
					if ( is_object( $order_log ) && $order_log instanceof \Automattic\WooCommerce\Admin\Overrides\Order
					) {
						$order_url = ! empty( $order_log->get_edit_order_url() ) ? $order_log->get_edit_order_url() :
							'#';
					} else {
						$order_url = '#';
					}
					$log_decoded = json_decode( $log->log );
					$gw_url      = ( ! empty( $log_decoded->json ) &&
										property_exists( $log_decoded->json, 'gw_url' ) ) ?
										$log_decoded->json->gw_url : '#';

					echo wp_kses_post( '<tr>' );
					echo wp_kses_post( '<td>' . esc_attr( $log->id ) . '</td>' );
					echo wp_kses_post( '<td><a href="' . esc_attr( $order_url ) . '">' . esc_attr( $log->order_id ) .
                        '</a></td>' );
					echo wp_kses_post( '<td><a href="' . esc_attr( $gw_url ) . '">' . esc_attr( $log->transaction_id
                        ) . '</a></td>' );
					echo wp_kses_post( '<td>' . esc_attr( $log->message ) . '</td>' );
					echo wp_kses_post( '<td>' . esc_attr( $log->created_at ) . ' (GMT)</td>' );
					echo wp_kses_post( '<td>' . esc_attr( $log->log_level ) . '</td>' );
					echo wp_kses( '<td><a href="#" onClick="openPopup(' .
						wp_kses_post( htmlspecialchars( $log->log, ENT_QUOTES ) ) . ');">Open log</a></td>',
                    array( 'td' => array(), 'a' => array( 'href' => 1, 'onclick' => 1 ) ) );
					echo wp_kses_post( '</tr>' );
				}
				?>
			</tbody>
		</table>

		<form action="">
			<label for="page"></label>
			<input type="hidden" id="page" name="page" value="gopay-gateway-menu-log">
			<label for="log_table_filter"><?php esc_html_e( 'Filter table by any column:', 'gopay-gateway' ); ?></label>
			<input type="text" id="log_table_filter" name="log_table_filter"
				   placeholder="<?php echo wp_kses_post( __( 'Search here', 'gopay-gateway' ) ); ?>">
			<input type="submit" value="<?php echo wp_kses_post( __( 'Search', 'gopay-gateway' ) ); ?>">
		</form>

		<?php
		if ( ! empty( $log_data ) ) {
			?>

		<div id="gopay-gateway-menu-popup" class="gopay-gateway-menu-popup">
			<div class="gopay-gateway-menu-close" onclick="closePopup();"></div>
		</div>

		<nav>
			<ul class="gopay-gateway-menu-pagination">
				<li class="gopay-gateway-menu-<?php echo wp_kses_post( $pagenum > 1 ? 'enabled' : 'disabled' ); ?>">
					<a href="<?php echo wp_kses_post( add_query_arg( 'pagenum', $pagenum - 1 ) ); ?>">Previous</a>
				</li>
				<?php
				if ( $number_of_pages > 10 ) {
					$start = max( $pagenum - 5, 1 );
					$stop  = $start + 10;

					if ( $stop > $number_of_pages ) {
						$start = $number_of_pages - 10;
						$stop  = $number_of_pages;
					}

					$pages_log = range( $start, $stop );
				} else {
					$pages_log = range( 1, $number_of_pages );
				}

				foreach ( $pages_log as $page_log ) {
					echo wp_kses( '<li class="gopay-gateway-menu-' .
						( $pagenum == $page_log ? 'active' : 'inactive' ) . '"><a href = "'
						. wp_kses_post( add_query_arg( 'pagenum', $page_log ) ) . '">' . wp_kses_post( $page_log ) . ' </a>',
                    array( 'li' => array( 'class' => 1 ), 'a' => array( 'href' => 1 ) ) );
				}
				?>
				<li class="gopay-gateway-menu-<?php echo wp_kses_post( $pagenum < $number_of_pages ? 'enabled' : 'disabled' ); ?>">
					<a href="<?php echo wp_kses_post( add_query_arg( 'pagenum', $pagenum + 1 ) ); ?>">Next</a>
				</li>
			</ul>
		</nav>
		<form action="">
			<label for="page"></label>
			<input type="hidden" id="page" name="page" value="gopay-gateway-menu-log">
			<label for="pagenum">Page (
			<?php
			echo wp_kses_post( $pagenum ) . ' of ' . wp_kses_post( $number_of_pages );
			?>
			):</label>
			<input type="number" id="pagenum" name="pagenum" min="1" max="
			<?php
			echo wp_kses_post( $number_of_pages );
			?>
			"
				   style="width: 65px;">
			<input type="submit" value="<?php echo wp_kses_post( __( 'Go to', 'gopay-gateway' ) ); ?>">
		</form>

			<?php
		}
		?>

	</div>
</div>
