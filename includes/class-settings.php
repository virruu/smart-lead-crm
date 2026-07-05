<?php
/**
 * Settings class - handles plugin settings.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 *
 * @package SmartLeadCRM
 */
class Smart_Lead_CRM_Settings {

	/**
	 * Settings key prefix.
	 *
	 * @var string
	 */
	private $prefix = 'smart_lead_crm_';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $defaults = array(
		'business_name'                  => '',
		'google_ads_conversion_id'       => '',
		'google_ads_label'               => '',
		'ga4_measurement_id'             => '',
		'cookie_duration'                => 90,
		'capture_gclid'                  => 'yes',
		'capture_utm'                    => 'yes',
		'enable_debug'                   => 'no',
		'whatsapp_access_token'          => '',
		'whatsapp_phone_number_id'       => '',
		'whatsapp_verify_token'          => '',
		'whatsapp_business_number'       => '',
		'whatsapp_api_version'           => 'v18.0',
		'whatsapp_default_country_code'  => '91',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$option_key = $this->prefix . $key;
		$value      = get_option( $option_key );
		if ( false === $value ) {
			if ( null !== $default ) {
				return $default;
			}
			return isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : '';
		}
		return $value;
	}

	/**
	 * Set a setting value.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 */
	public function set( $key, $value ) {
		update_option( $this->prefix . $key, $value );
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			'smart_lead_crm_settings_group',
			'smart_lead_crm_business_name',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => get_bloginfo( 'name' ),
			)
		);

		register_setting(
			'smart_lead_crm_settings_group',
			'smart_lead_crm_google_ads_conversion_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'smart_lead_crm_settings_group',
			'smart_lead_crm_google_ads_label',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'smart_lead_crm_settings_group',
			'smart_lead_crm_ga4_measurement_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'smart_lead_crm_settings_group',
			'smart_lead_crm_cookie_duration',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_cookie_duration' ),
				'default'           => 90,
			)
		);

		register_setting(
			'smart_lead_crm_settings_group',
			'smart_lead_crm_capture_gclid',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'yes',
			)
		);

		register_setting(
			'smart_lead_crm_settings_group',
			'smart_lead_crm_capture_utm',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'yes',
			)
		);

		register_setting(
			'smart_lead_crm_settings_group',
			'smart_lead_crm_enable_debug',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'no',
			)
		);

		register_setting( 'smart_lead_crm_settings_group', 'smart_lead_crm_whatsapp_access_token', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'smart_lead_crm_settings_group', 'smart_lead_crm_whatsapp_phone_number_id', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'smart_lead_crm_settings_group', 'smart_lead_crm_whatsapp_verify_token', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'smart_lead_crm_settings_group', 'smart_lead_crm_whatsapp_business_number', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
		register_setting( 'smart_lead_crm_settings_group', 'smart_lead_crm_whatsapp_api_version', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'v18.0' ) );
		register_setting( 'smart_lead_crm_settings_group', 'smart_lead_crm_whatsapp_default_country_code', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '91' ) );

		// Business settings section.
		add_settings_section(
			'smart_lead_crm_business_section',
			__( 'Business Settings', 'smart-lead-crm' ),
			array( $this, 'render_business_section' ),
			'smart-lead-crm-settings'
		);

		// Tracking settings section.
		add_settings_section(
			'smart_lead_crm_tracking_section',
			__( 'Tracking Settings', 'smart-lead-crm' ),
			array( $this, 'render_tracking_section' ),
			'smart-lead-crm-settings'
		);

		// WhatsApp settings section.
		add_settings_section(
			'smart_lead_crm_whatsapp_section',
			__( 'WhatsApp Business API', 'smart-lead-crm' ),
			array( $this, 'render_whatsapp_section' ),
			'smart-lead-crm-settings'
		);

		// Business fields.
		add_settings_field( 'business_name', __( 'Business Name', 'smart-lead-crm' ), array( $this, 'render_text_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_business_section', array( 'key' => 'business_name', 'label_for' => 'smart_lead_crm_business_name' ) );
		add_settings_field( 'google_ads_conversion_id', __( 'Google Ads Conversion ID', 'smart-lead-crm' ), array( $this, 'render_text_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_business_section', array( 'key' => 'google_ads_conversion_id', 'label_for' => 'smart_lead_crm_google_ads_conversion_id', 'placeholder' => 'AW-XXXXXXXXX' ) );
		add_settings_field( 'google_ads_label', __( 'Google Ads Label', 'smart-lead-crm' ), array( $this, 'render_text_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_business_section', array( 'key' => 'google_ads_label', 'label_for' => 'smart_lead_crm_google_ads_label', 'placeholder' => 'conversion_label' ) );
		add_settings_field( 'ga4_measurement_id', __( 'GA4 Measurement ID', 'smart-lead-crm' ), array( $this, 'render_text_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_business_section', array( 'key' => 'ga4_measurement_id', 'label_for' => 'smart_lead_crm_ga4_measurement_id', 'placeholder' => 'G-XXXXXXXXXX' ) );

		// Tracking fields.
		add_settings_field( 'cookie_duration', __( 'Cookie Duration (days)', 'smart-lead-crm' ), array( $this, 'render_number_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_tracking_section', array( 'key' => 'cookie_duration', 'label_for' => 'smart_lead_crm_cookie_duration', 'min' => 1, 'max' => 365 ) );
		add_settings_field( 'capture_gclid', __( 'Capture GCLID', 'smart-lead-crm' ), array( $this, 'render_checkbox_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_tracking_section', array( 'key' => 'capture_gclid' ) );
		add_settings_field( 'capture_utm', __( 'Capture UTM Parameters', 'smart-lead-crm' ), array( $this, 'render_checkbox_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_tracking_section', array( 'key' => 'capture_utm' ) );
		add_settings_field( 'enable_debug', __( 'Enable Debug Mode', 'smart-lead-crm' ), array( $this, 'render_checkbox_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_tracking_section', array( 'key' => 'enable_debug' ) );

		// WhatsApp fields.
		add_settings_field( 'whatsapp_access_token', __( 'Access Token', 'smart-lead-crm' ), array( $this, 'render_text_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_whatsapp_section', array( 'key' => 'whatsapp_access_token', 'label_for' => 'smart_lead_crm_whatsapp_access_token', 'placeholder' => 'EAAG...' ) );
		add_settings_field( 'whatsapp_phone_number_id', __( 'Phone Number ID', 'smart-lead-crm' ), array( $this, 'render_text_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_whatsapp_section', array( 'key' => 'whatsapp_phone_number_id', 'label_for' => 'smart_lead_crm_whatsapp_phone_number_id', 'placeholder' => '123456789012345' ) );
		add_settings_field( 'whatsapp_verify_token', __( 'Webhook Verify Token', 'smart-lead-crm' ), array( $this, 'render_text_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_whatsapp_section', array( 'key' => 'whatsapp_verify_token', 'label_for' => 'smart_lead_crm_whatsapp_verify_token', 'placeholder' => 'my_verify_token' ) );
		add_settings_field( 'whatsapp_business_number', __( 'Business WhatsApp Number', 'smart-lead-crm' ), array( $this, 'render_text_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_whatsapp_section', array( 'key' => 'whatsapp_business_number', 'label_for' => 'smart_lead_crm_whatsapp_business_number', 'placeholder' => '919876543210' ) );
		add_settings_field( 'whatsapp_api_version', __( 'API Version', 'smart-lead-crm' ), array( $this, 'render_text_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_whatsapp_section', array( 'key' => 'whatsapp_api_version', 'label_for' => 'smart_lead_crm_whatsapp_api_version', 'placeholder' => 'v18.0' ) );
		add_settings_field( 'whatsapp_default_country_code', __( 'Default Country Code', 'smart-lead-crm' ), array( $this, 'render_text_field' ), 'smart-lead-crm-settings', 'smart_lead_crm_whatsapp_section', array( 'key' => 'whatsapp_default_country_code', 'label_for' => 'smart_lead_crm_whatsapp_default_country_code', 'placeholder' => '91' ) );
	}

	/**
	 * Sanitize cookie duration.
	 *
	 * @param mixed $value Input value.
	 * @return int
	 */
	public function sanitize_cookie_duration( $value ) {
		$value = absint( $value );
		if ( $value < 1 ) {
			$value = 1;
		}
		if ( $value > 365 ) {
			$value = 365;
		}
		return $value;
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		include SMART_LEAD_CRM_PLUGIN_DIR . 'admin/settings.php';
	}

	/**
	 * Render business section description.
	 */
	public function render_business_section() {
		echo '<p>' . esc_html__( 'Configure your business and Google Ads integration settings.', 'smart-lead-crm' ) . '</p>';
	}

	/**
	 * Render tracking section description.
	 */
	public function render_tracking_section() {
		echo '<p>' . esc_html__( 'Configure how the plugin captures and stores tracking data.', 'smart-lead-crm' ) . '</p>';
	}

	/**
	 * Render WhatsApp section description.
	 */
	public function render_whatsapp_section() {
		echo '<p>' . esc_html__( 'Connect your WhatsApp Business API to automatically match inbound messages to leads. Webhook URL:', 'smart-lead-crm' ) . ' <code>' . esc_html( home_url( '/slcrm-whatsapp-webhook' ) ) . '</code></p>';
	}

	/**
	 * Render a text input field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_text_field( $args ) {
		$key         = $args['key'];
		$option_name = $this->prefix . $key;
		$value        = get_option( $option_name, $this->defaults[ $key ] );
		$placeholder  = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
		?>
		<input type="text" id="<?php echo esc_attr( $option_name ); ?>" name="<?php echo esc_attr( $option_name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="regular-text" />
		<?php
	}

	/**
	 * Render a number input field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_number_field( $args ) {
		$key         = $args['key'];
		$option_name = $this->prefix . $key;
		$value        = get_option( $option_name, $this->defaults[ $key ] );
		$min          = isset( $args['min'] ) ? $args['min'] : '';
		$max          = isset( $args['max'] ) ? $args['max'] : '';
		?>
		<input type="number" id="<?php echo esc_attr( $option_name ); ?>" name="<?php echo esc_attr( $option_name ); ?>" value="<?php echo esc_attr( $value ); ?>" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" class="small-text" />
		<?php
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array $args Field arguments.
	 */
	public function render_checkbox_field( $args ) {
		$key         = $args['key'];
		$option_name = $this->prefix . $key;
		$value        = get_option( $option_name, $this->defaults[ $key ] );
		$checked      = ( 'yes' === $value ) ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" id="<?php echo esc_attr( $option_name ); ?>" name="<?php echo esc_attr( $option_name ); ?>" value="yes" <?php echo esc_attr( $checked ); ?> />
			<?php esc_html_e( 'Enable', 'smart-lead-crm' ); ?>
		</label>
		<?php
	}
}
