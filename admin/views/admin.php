<?php
/**
 * Plugin admin menu
 *
 * @package   GoPay gateway
 * @author    GoPay
 * @link      https://www.gopay.com/
 * @copyright 2022 GoPay
 * @since     1.0.0
 */

defined( 'ABSPATH' ) || exit;

$plugin_data  = get_plugin_data( GOPAY_GATEWAY_FULLPATH );
$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . GOPAY_GATEWAY_ID );
?>


<div class="wrap">
	<div class="gopay-gateway-menu">
		<h1><?php echo wp_kses_post( __( 'GoPay gateway', 'gopay-gateway' ) ); ?></h1>
	</div>

	<div class="gopay-gateway-menu">
		<table>
			<tr>
				<th><?php echo wp_kses_post( __( 'Plugin Name', 'gopay-gateway' ) ); ?></th>
				<th><?php echo wp_kses_post( __( 'Version', 'gopay-gateway' ) ); ?></th>
				<th><?php echo wp_kses_post( __( 'Description', 'gopay-gateway' ) ); ?></th>
				<th><?php echo wp_kses_post( __( 'Author', 'gopay-gateway' ) ); ?></th>
				<th><?php echo wp_kses_post( __( 'Settings', 'gopay-gateway' ) ); ?></th>
			</tr>
			<tr>
				<td><a href="https://github.com/argo22packages/gopay-woocommerce-integration">
                    <?php echo wp_kses_post( __( 'GoPay gateway', 'gopay-gateway' ) ); ?></a></td>
				<td><?php echo wp_kses_post( esc_html( GOPAY_WOOCOMMERCE_VERSION ) ) ?></td>
				<td><?php echo wp_kses_post( __( 'WooCommerce and GoPay payment gateway integration', 'gopay-gateway' ) ); ?></td>
				<td><a href="https://www.gopay.com/"><?php echo wp_kses_post( __( 'GoPay', 'gopay-gateway' ) ); ?></a></td>
				<?php
				echo wp_kses_post(
					'<td><a href="' . $settings_url . '">' . __(
						'Settings',
						'gopay-gateway'
					) . '</a></td>'
				)
				?>
			</tr>
		</table>
	</div>

</div>
