<?php
/**
 * Plugin Name: Miracle Fulfillment WooCommerce Order Integration
 * Plugin URI:
 * Version: 1.01
 * Description: Instant WooCommerce order integration with Miracle Fulfillment - A warehousing and shipping company that specializes helping web-based businesses.
 * Author: Miracle Fulfillment	
 * Author URI: http://www.miraclefulfillment.com/
 * Text Domain: woocommerce-miracle-fulfillment
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}
include_once( 'classes/class-wc-miracle-fulfillment-main.php' );
include_once( 'classes/class-wc-miracle-fulfillment-api.php' );

/**
 * Include fulfillment class
 */
function __woocommerce_miracle_fulfillment_init() {
	include_once( 'classes/class-wc-miracle-fulfillment-integration.php' );
}

add_action( 'plugins_loaded', '__woocommerce_miracle_fulfillment_init' );

/**
 * Define integration
 *
 * @param  array $integrations
 *
 * @return array
 */
function __woocommerce_miracle_fulfillment_load_integration( $integrations ) {
	$integrations[] = 'WC_Miracle_Fulfillment_Integration';

	return $integrations;
}

add_filter( 'woocommerce_integrations', '__woocommerce_miracle_fulfillment_load_integration' );

/**
 * Listen for API requests
 */
function __woocommerce_miracle_fulfillment_api() {
	new WC_Miracle_Fulfillment_API();
}

add_action( 'woocommerce_api_wc_miracle_fulfillment', '__woocommerce_miracle_fulfillment_api' );
new WC_Miracle_Fulfillment_Main();
