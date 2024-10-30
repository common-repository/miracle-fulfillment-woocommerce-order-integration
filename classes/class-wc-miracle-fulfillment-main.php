<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Miracle_Fulfillment_Main {
	const TEXT_DOMAIN = 'woocommerce-miracle-fulfillment';
	public static $order_added_meta_key = '_wc_miracle_fulfillment_order_uploaded';

	/**
	 * WC_Miracle_Fulfillment_Main constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_processing', array( $this, 'woocommerce_order_status_processing' ) );
	}

	public function woocommerce_order_status_processing( $order_id ) {
		if ( get_post_meta( $order_id, self::$order_added_meta_key, true ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		$line_items = $order->get_items();
		if ( empty( $line_items ) ) {
			return;
		}

		$dataItems = array();
		foreach ( $line_items as $item ) {
			$product = $order->get_product_from_item( $item );
			if ( ! $product ) {
				return;
			}
			$sku = $product->get_sku();
			$product_id = isset($product->variation_id) ? $product->variation_id : $product->id;
			$dataItems[] = array(
				'name'     => $item['name'],
				'sku'      => ! empty( $sku ) ? $sku : $product_id,
				'qty'      => isset( $item['qty'] ) ? $item['qty'] : 0,
				'qtyavail' => $product->get_stock_quantity(),
			);
		}

		$data = array(
			'order_id'            => $order_id,
			'billing_first_name'  => $order->billing_first_name,
			'billing_last_name'   => $order->billing_last_name,
			'billing_address_1'   => $order->billing_address_1,
			'billing_address_2'   => $order->billing_address_2,
			'billing_city'        => $order->billing_city,
			'billing_state'       => $order->billing_state,
			'billing_postcode'    => $order->billing_postcode,
			'billing_country'     => $order->billing_country,
			'billing_email'       => $order->billing_email,
			'billing_phone'       => $order->billing_phone,
			'order_date'          => date( 'Y-m-d H:i:s', strtotime( $order->order_date ) ),
			'shipping_phone'      => '',
			'shipping_email'      => '',
			'shipping_first_name' => $order->shipping_first_name,
			'shipping_last_name'  => $order->shipping_last_name,
			'shipping_address_1'  => $order->shipping_address_1,
			'shipping_address_2'  => $order->shipping_address_2,
			'shipping_city'       => $order->shipping_city,
			'shipping_state'      => $order->shipping_state,
			'shipping_postcode'   => $order->shipping_postcode,
			'shipping_country'    => $order->shipping_country,
			'shipping_method'     => $order->get_shipping_method(),
			'giftnote'            => '',
			'items'               => $dataItems,
			'other'               => array()
		);

		$response = WC_Miracle_Fulfillment_API::add_order(
			WC_Miracle_Fulfillment_Integration::get( 'client_key' ),
			$data
		);

		if ( true === $response ) {
			update_post_meta( $order_id, self::$order_added_meta_key, true );
		}
	}

}