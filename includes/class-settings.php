<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Settings {

	private $prefix   = 'smart_lead_crm_';
	private $defaults = array(
		'business_name'                 => '',
		'business_type'                 => 'taxi',
		'google_ads_conversion_id'      => '',
		'google_ads_label'              => '',
		'ga4_measurement_id'            => '',
		'cookie_duration'               => 90,
		'capture_gclid'                 => 'yes',
		'capture_utm'                   => 'yes',
		'capture_organic_keywords'      => 'yes',
		'enable_debug'                  => 'no',
		'form_capture_name'             => 'auto',
		'form_capture_email'            => 'auto',
		'form_capture_phone'             => 'auto',
		'whatsapp_connection_mode'      => 'app_mode',
		'whatsapp_access_token'         => '',
		'whatsapp_phone_number_id'      => '',
		'whatsapp_verify_token'         => '',
		'whatsapp_business_number'      => '',
		'whatsapp_api_version'          => 'v21.0',
		'whatsapp_default_country_code' => '91',
		'auto_reply_enabled'            => 'no',
		'auto_reply_template'          => '',
		'google_ads_daily_spend'        => '',
	);

	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function get( $key, $default = null ) {
		$val = get_option( $this->prefix . $key );
		if ( false === $val ) {
			return $default !== null ? $default : ( $this->defaults[ $key ] ?? '' );
		}
		return $val;
	}

	public function set( $key, $value ) {
		update_option( $this->prefix . $key, $value );
	}

	public function get_whatsapp_mode() {
		return $this->get( 'whatsapp_connection_mode', 'app_mode' );
	}

	public function is_cloud_api_configured() {
		return ! empty( $this->get( 'whatsapp_access_token' ) ) && ! empty( $this->get( 'whatsapp_phone_number_id' ) );
	}

	public function get_whatsapp_mode_label() {
		$labels = array(
			'app_mode'    => 'WhatsApp Business App',
			'cloud_api'   => 'Cloud API',
			'coexistence' => 'Coexistence (App + Cloud API)',
		);
		return $labels[ $this->get_whatsapp_mode() ] ?? 'WhatsApp Business App';
	}

	public function get_defaults() {
		return $this->defaults;
	}

	public function get_prefix() {
		return $this->prefix;
	}

	public function register_settings() {
		$checkbox_keys = array( 'capture_gclid', 'capture_utm', 'capture_organic_keywords', 'enable_debug', 'auto_reply_enabled' );
		foreach ( array_keys( $this->defaults ) as $key ) {
			$option_name = $this->prefix . $key;
			if ( in_array( $key, $checkbox_keys, true ) ) {
				register_setting( 'smart_lead_crm_settings_group', $option_name, array( $this, 'sanitize_checkbox' ) );
			} else {
				register_setting( 'smart_lead_crm_settings_group', $option_name );
			}
		}
	}

	public function sanitize_checkbox( $value ) {
		return ( 'yes' === $value || '1' === $value ) ? 'yes' : 'no';
	}

	public function render_settings_page() {
		include SMART_LEAD_CRM_PLUGIN_DIR . 'admin/settings.php';
	}

	public function render_whatsapp_page() {
		include SMART_LEAD_CRM_PLUGIN_DIR . 'admin/whatsapp.php';
	}
}
