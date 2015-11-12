<?php

/*
Plugin Name: WC Autoship Upsell
Plugin URI: https://wooautoship.com
Description: Add autoship upsell options to the cart
Version: 1.0
Author: Patterns In the Cloud
Author URI: http://patternsinthecloud.com
License: Single-site
*/

define( 'WC_Autoship_Upsell_Version', '1.0' );

function wc_autoship_upsell_install() {

}
register_activation_hook( __FILE__, 'wc_autoship_upsell_install' );

function wc_autoship_upsell_deactivate() {

}
register_deactivation_hook( __FILE__, 'wc_autoship_upsell_deactivate' );

function wc_autoship_upsell_uninstall() {

}
register_uninstall_hook( __FILE__, 'wc_autoship_upsell_uninstall' );

function wc_autoship_upsell_scripts() {
	wp_enqueue_script( 'jquery-ui-dialog' );

	wp_enqueue_style( 'wc-autoship-upsell', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), WC_Autoship_Upsell_Version );
	wp_register_script( 'wc-autoship-upsell', plugin_dir_url( __FILE__ ) . 'js/scripts.js', array( 'jquery' ), WC_Autoship_Upsell_Version, true );
	wp_localize_script( 'wc-autoship-upsell', 'WC_Autoship_Upsell', array(
		'cart_upsell_url' => admin_url( 'admin-ajax.php?action=wc_autoship_upsell_cart' ),
		'cart_url' => WC()->cart->get_cart_url()
	) );
	wp_enqueue_script( 'wc-autoship-upsell' );
}
add_action( 'wp_enqueue_scripts', 'wc_autoship_upsell_scripts' );

function wc_autoship_upsell_cart_item_name( $name, $item, $item_key ) {
	if ( ! is_cart() || isset( $item['wc_autoship_frequency'] ) ) {
		return $name;
	}

	$product_id = $item['product_id'];
	$var_product_id = ( ! empty( $item['variation_id'] ) ) ? $item['variation_id'] : $item['product_id'];
	$product = wc_get_product( $var_product_id );
	$price = $product->get_price();
	$autoship_price = (int) apply_filters( 'wc_autoship_price',
			get_post_meta( $var_product_id, '_wc_autoship_price', true ),
			$var_product_id,
			0,
			get_current_user_id(),
			0
	);
	$upsell_title = '';
	if ($autoship_price > 0) {
		$diff = $product->get_price() - $autoship_price;
		$upsell_title = __( 'Save ' . wc_price( $diff ) . ' with Auto-Ship', 'wc-autoship-upsell' );
	} else {
		$upsell_title = __( 'Add to Auto-Ship', 'wc-autoship-upsell' );
	}
	$upsell_title = apply_filters( 'wc-autoship-upsell-title', $upsell_title, $item, $item_key );

	ob_start();
		?>
			<a class="wc-autoship-upsell-cart-toggle" data-target="#wc-autoship-upsell-cart-options-<?php echo esc_attr( $item_key ); ?>"><span class="wc-autoship-upsell-icon">&plus;</span><?php echo $upsell_title; ?></a>
			<div id="wc-autoship-upsell-cart-options-<?php echo esc_attr( $item_key ); ?>" class="wc-autoship-upsell-cart-options" title="<?php echo esc_attr( strip_tags( $upsell_title ) ); ?>">
				<input type="hidden" name="wc_autoship_upsell_item_key" value="<?php echo esc_attr( $item_key ); ?>" />
				<?php WC_Autoship::include_template( 'product/autoship-options', array( 'product' => $product ) ); ?>
				<button type="button" class="wc-autoship-upsell-cart-submit button expand"><?php echo __( 'Update', 'wc-autoship-upsell' ); ?></button>
			</div>
		<?php
	$upsell_content = ob_get_clean();
	return $name . $upsell_content;
}
add_filter( 'woocommerce_cart_item_name', 'wc_autoship_upsell_cart_item_name', 10, 3 );

function wc_autoship_upsell_cart_ajax() {
	if ( empty( $_POST['frequency'] ) ) {
		header( "HTTP/1.1 200 OK" );
		die();
	}
	if ( isset( $_POST['item_key'] ) ) {
		$cart = WC()->cart;
		$item = $cart->get_cart_item( $_POST['item_key'] );

		$quantity = ( ! empty( $_POST['quantity'] ) ) ? $_POST['quantity'] : $item['quantity'];

		$cart->remove_cart_item( $_POST['item_key'] );
		$cart->add_to_cart( $item['product_id'], $quantity, $item['variation_id'], $item['variation'], array(
			'wc_autoship_frequency' => $_POST['frequency']
		) );
		header( "HTTP/1.1 200 OK" );
		die();
	}
	header( "HTTP/1.1 400 Bad Request" );
	die();
}
add_action( 'wp_ajax_wc_autoship_upsell_cart', 'wc_autoship_upsell_cart_ajax' );
add_action( 'wp_ajax_nopriv_wc_autoship_upsell_cart', 'wc_autoship_upsell_cart_ajax' );