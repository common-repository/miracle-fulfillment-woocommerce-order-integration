<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Fulfillment_Integration Class
 */
class WC_Miracle_Fulfillment_Integration extends WC_Integration {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'miracle_fulfillment';
		$this->method_title       = __( 'Miracle Fulfillment', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN );
		$this->method_description = __( 'Miracle Fulfillment description', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN );

		// Load admin form
		$this->init_form_fields();

		// Load settings
		$this->init_settings();

		// Hooks
		add_action( "woocommerce_update_options_integration_{$this->id}", array( $this, 'process_admin_options' ) );

		if ( false === $this->settings['api_enabled'] ) {
			add_action( 'admin_notices', array( $this, 'global_notice' ) );
		}
	}

	/**
	 * Init integration form fields
	 */
	public function init_form_fields() {

		$statuses = wc_get_order_statuses();

		$this->form_fields = array(
			'client_key'           => array(
				'title'       => __( 'Customer Key', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
				'description' => __( '', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
				'default'     => '',
				'type'        => 'text',
				'desc_tip'    => __( '', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
			),
			'client_email'         => array(
				'title'       => __( 'Admin Email', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
				'description' => __( '', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
				'default'     => sanitize_email( get_option( 'woocommerce_email_from_address' ) ),
				'type'        => 'text',
				'desc_tip'    => __( '', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
			),
			'export_order_status'  => array(
				'title'       => __( 'Export Order Status', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
				'type'        => 'select',
				'options'     => $statuses,
				'description' => __( '', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
				'desc_tip'    => true,
				'default'     => 'wc-processing',
			),
			'shipped_order_status' => array(
				'title'       => __( 'Shipped Order Status', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
				'type'        => 'select',
				'options'     => $statuses,
				'description' => __( '', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ),
				'desc_tip'    => true,
				'default'     => 'wc-completed',
			),
			'api_enabled'          => array(
				'default' => false,
				'type'    => 'hidden',
			),
		);
	}

	public function process_admin_options() {

		$this->validate_settings_fields();

		if ( count( $this->errors ) > 0 ) {
			$this->display_errors();
		}
		update_option( $this->plugin_id . $this->id . '_settings', apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->sanitized_fields ) );
		$this->init_settings();
		return true;
	}

	public function display_errors() {
		foreach ( $this->errors as $error ) {
			?>
			<div id="message" class="updated error woocommerce-message">
				<p><?php echo sprintf( __( '<strong>Miracle Fulfillment</strong> error - %s',
						WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ), $error ) ?></p>
			</div>
			<?php
		}
	}

	public function generate_hidden_html( $key, $data ) {
		return '';
	}

	public function validate_hidden_field( $key ) {
		if ( 'api_enabled' === $key ) {
			if ( $this->sanitized_fields['client_key'] !== $this->settings['client_key'] ) {
				$response = WC_Miracle_Fulfillment_API::test_connection( $this->sanitized_fields['client_key'] );
				if ( true === $response ) {
					$ret = true;
				} else {
					$ret = $response;
				}
			} else {
				$ret = $this->settings['api_enabled'];
			}

			if ( ! is_bool( $ret ) ) {
				$this->errors[] = $ret;
			}

			return $ret;
		}

		return false;
	}

	public function global_notice() {

		if ( ! empty( $_GET['tab'] ) && 'integration' === $_GET['tab'] ) {
			return;
		}
		?>
		<div id="message" class="updated woocommerce-message">
			<p><?php _e( '<strong>Miracle Fulfillment</strong> is almost ready &#8211; Please configure the plugin to begin exporting orders.',
					WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ) ?></p>

			<p class="submit">
				<a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=integration&section=miracle_fulfillment' ); ?>"
				   class="button-primary"><?php _e( 'Settings', WC_Miracle_Fulfillment_Main::TEXT_DOMAIN ); ?></a>
			</p>
		</div>
		<?php
	}

	public static function get( $key ) {
		$settings = get_option( 'woocommerce_miracle_fulfillment_settings' );

		return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
	}
}