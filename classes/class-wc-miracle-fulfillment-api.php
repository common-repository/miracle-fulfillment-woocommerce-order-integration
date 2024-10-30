<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Fulfillment_API Class
 */
class WC_Miracle_Fulfillment_API {

//	const API_URL = 'http://localhost/srv-miracle-fulfillment/api'; // FOR DEBUG
	const API_URL = 'http://www.miraclefulfillment.com/flow/api';
	const SYSTEM_TYPE = 'Woocommerce';
	/**
	 * Stores logger class
	 * @var WC_Logger
	 */
	private static $log = null;
	private $request = null;


	public function __construct() {
		nocache_headers();

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( "DONOTCACHEPAGE", "true" );
		}

		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			define( "DONOTCACHEOBJECT", "true" );
		}

		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( "DONOTCACHEDB", "true" );
		}

		$this->request();
	}

	/**
	 * Log something
	 *
	 * @param  string $message
	 */
	private static function log( $message ) {

		if ( is_null( self::$log ) ) {
			self::$log = new WC_Logger();
		}
		self::$log->add( 'miracle_fulfillment', $message );
	}

	/**
	 * Trigger and log an error
	 *
	 * @param  string $message
	 */
	private static function trigger_error( $message ) {
		self::log( $message );
		wp_send_json_error( $message );
	}

	/**
	 * Handle the request
	 */
	private function request() {
		if ( empty( $_GET['key'] ) ) {
			self::trigger_error( __( 'Authentication key is required!', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ) );
		}

		if ( ! hash_equals( sanitize_text_field( $_GET['key'] ),
			WC_Miracle_Fulfillment_Integration::get( 'client_key' ) )
		) {
			self::trigger_error( __( 'Invalid authentication key', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ) );
		}

		$request = $_GET;

		if ( isset( $request['action'] ) ) {
			$this->request = $request;
		} else {
			self::trigger_error( __( 'Invalid request', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ) );
		}

		$request_method = 'request_' . $this->request['action'];
		if ( method_exists( $this, $request_method ) ) {
			self::log( sprintf( __( 'Input params: %s', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
				http_build_query( $this->request ) ) );
			$ret = $this->$request_method();
			echo json_encode( $ret );
		} else {
			self::trigger_error( __( 'Invalid request', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ) );
		}

		exit;
	}

	private function request_update_orders() {
		if ( empty( $this->request['data'] ) ) {
			return array( 'success' => 'false', 'data' => 'no items' );
		}

		foreach ( $this->request['data'] as $order_id => $item ) {
			$order           = wc_get_order( $order_id );
			$carrier         = $item['tracking_provider'];
			$tracking_number = $item['tracking_number'];
			$timestamp       = strtotime( $item['date_shipped'] );

			$order_note = sprintf(
				__( 'Items shipped via %s on %s with tracking number %s.', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
				esc_html( $carrier ), date_i18n( get_option( 'date_format' ), $timestamp ), $tracking_number
			);

			// Tracking information - WC Shipment Tracking extension
			if ( class_exists( 'WC_Shipment_Tracking' ) ) {
				update_post_meta( $order->id, '_tracking_provider', $carrier );
				update_post_meta( $order->id, '_custom_tracking_provider', $carrier );
				update_post_meta( $order->id, '_tracking_number', $tracking_number );
				update_post_meta( $order->id, '_date_shipped', $timestamp );
				update_post_meta( $order->id, '_custom_tracking_link', '' );
				$is_customer_note = 0;
			} else {
				$is_customer_note = 1;
			}

			$order->add_order_note( $order_note, $is_customer_note );
			$order->update_status( WC_Miracle_Fulfillment_Integration::get( 'shipped_order_status' ) );
		}

		return array( 'success' => true );
	}

	/**
	 * @param $key
	 *
	 * @return bool|string
	 */
	public static function test_connection( $key ) {
		$response = self::get_remote(
				self::API_URL . '/test_connection.php',
			array(
				'data' => array(
					'key'    => $key,
					'host'   => get_option( 'siteurl' ),
					'system' => self::SYSTEM_TYPE,
				)
			),
			'post'
		);

		return $response['status'] == 'ok' ? true : $response['error'];
	}

	public static function add_order( $key, $data ) {
		$data['key']    = $key;
		$data['host']   = get_option( 'siteurl' );
		$data['system'] = self::SYSTEM_TYPE;

		$response = self::get_remote(
				self::API_URL . '/add_order.php',
			json_encode($data),
			'post'
		);
		
		return $response['status'] == 'ok' ? true : $response['error'];
	}

	/**
	 * Execute request to the API
	 *
	 * @param  string $url
	 * @param  array $body
	 * @param  string $method
	 *
	 * @return array
	 */
	private static function get_remote( $url, $body = array(), $method = 'get' ) {
		$args = array(
			'headers' => array(),
			'timeout' => 30,
		);
		if ( ! empty( $body ) ) {
			$args['body'] = $body;
		}

		$func = "wp_remote_{$method}";
		self::log( $func );
		self::log( $url );
		self::log( print_r( $args, 1 ) );
		$response = $func( $url, $args );
		self::log( print_r( $response, 1 ) );
		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		return $data;
	}
}
