<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings = smart_lead_crm()->settings;
$mode     = $settings->get_whatsapp_mode();
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title"><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'smart-lead-crm' ); ?></h1>
	<p class="slcrm-subtitle"><?php esc_html_e( 'Configure Smart Lead CRM for your business.', 'smart-lead-crm' ); ?></p>

	<form method="post" action="options.php">
		<?php settings_fields( 'smart_lead_crm_settings_group' ); ?>

		<div class="slcrm-settings-tabs">
			<a href="#" class="slcrm-settings-tab active" data-tab="business"><span class="dashicons dashicons-building"></span><?php esc_html_e( 'Business', 'smart-lead-crm' ); ?></a>
			<a href="#" class="slcrm-settings-tab" data-tab="whatsapp"><span class="dashicons dashicons-whatsapp"></span><?php esc_html_e( 'WhatsApp', 'smart-lead-crm' ); ?></a>
			<a href="#" class="slcrm-settings-tab" data-tab="tracking"><span class="dashicons dashicons-chart-bar"></span><?php esc_html_e( 'Tracking', 'smart-lead-crm' ); ?></a>
		</div>

		<div id="slcrm-tab-business" class="slcrm-tab-panel active">
			<div class="slcrm-card" style="max-width:680px;">
				<h3 style="margin-bottom:20px;"><?php esc_html_e( 'Business Details', 'smart-lead-crm' ); ?></h3>
				<table class="slcrm-detail-table">
					<tr><th><label for="smart_lead_crm_business_name"><?php esc_html_e( 'Business Name', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_business_name" name="smart_lead_crm_business_name" value="<?php echo esc_attr( $settings->get( 'business_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" /></td></tr>
				</table>
				<h3 style="margin:24px 0 20px;"><?php esc_html_e( 'Google Ads', 'smart-lead-crm' ); ?></h3>
				<table class="slcrm-detail-table">
					<tr><th><label for="smart_lead_crm_google_ads_conversion_id"><?php esc_html_e( 'Conversion ID', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_google_ads_conversion_id" name="smart_lead_crm_google_ads_conversion_id" value="<?php echo esc_attr( $settings->get( 'google_ads_conversion_id' ) ); ?>" placeholder="AW-XXXXXXXXX" class="regular-text" /></td></tr>
					<tr><th><label for="smart_lead_crm_google_ads_label"><?php esc_html_e( 'Conversion Label', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_google_ads_label" name="smart_lead_crm_google_ads_label" value="<?php echo esc_attr( $settings->get( 'google_ads_label' ) ); ?>" placeholder="conversion_label" class="regular-text" /></td></tr>
					<tr><th><label for="smart_lead_crm_ga4_measurement_id"><?php esc_html_e( 'GA4 Measurement ID', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_ga4_measurement_id" name="smart_lead_crm_ga4_measurement_id" value="<?php echo esc_attr( $settings->get( 'ga4_measurement_id' ) ); ?>" placeholder="G-XXXXXXXXXX" class="regular-text" /></td></tr>
				</table>
			</div>
		</div>

		<div id="slcrm-tab-whatsapp" class="slcrm-tab-panel">
			<div class="slcrm-card" style="max-width:780px;">
				<h3 style="margin-bottom:4px;"><?php esc_html_e( 'Connection Mode', 'smart-lead-crm' ); ?></h3>
				<p style="color:var(--gray-500);font-size:13px;margin-bottom:20px;"><?php esc_html_e( 'Choose how WhatsApp connects to your CRM.', 'smart-lead-crm' ); ?></p>
				<div class="slcrm-mode-cards">
					<?php
					$opts = array(
						'app_mode'    => array( 'label'=>__('WhatsApp Business App','smart-lead-crm'), 'desc'=>__('Keep using your phone. CRM reads messages.','smart-lead-crm'), 'icon'=>'phone' ),
						'cloud_api'   => array( 'label'=>__('Cloud API','smart-lead-crm'), 'desc'=>__('Official Meta API. Full send/receive.','smart-lead-crm'), 'icon'=>'cloud' ),
						'coexistence' => array( 'label'=>__('Coexistence','smart-lead-crm'), 'desc'=>__('App + API together. Premium.','smart-lead-crm'), 'icon'=>'sync' ),
					);
					foreach ( $opts as $mk => $mv ) :
					?>
					<label class="slcrm-mode-card <?php echo $mode === $mk ? 'slcrm-mode-card--active' : ''; ?>">
						<input type="radio" name="smart_lead_crm_whatsapp_connection_mode" value="<?php echo esc_attr( $mk ); ?>" <?php checked( $mode, $mk ); ?> />
						<span class="slcrm-mode-card-icon"><span class="dashicons dashicons-<?php echo esc_attr( $mv['icon'] ); ?>"></span></span>
						<span class="slcrm-mode-card-label"><?php echo esc_html( $mv['label'] ); ?></span>
						<span class="slcrm-mode-card-desc"><?php echo esc_html( $mv['desc'] ); ?></span>
					</label>
					<?php endforeach; ?>
				</div>
				<h3 style="margin:28px 0 8px;"><?php esc_html_e( 'Business Number', 'smart-lead-crm' ); ?></h3>
				<table class="slcrm-detail-table">
					<tr><th><label for="smart_lead_crm_whatsapp_business_number"><?php esc_html_e( 'WhatsApp Number', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_whatsapp_business_number" name="smart_lead_crm_whatsapp_business_number" value="<?php echo esc_attr( $settings->get( 'whatsapp_business_number' ) ); ?>" placeholder="919876543210" class="regular-text" /><p class="description"><?php esc_html_e( 'Include country code, no + or spaces.', 'smart-lead-crm' ); ?></p></td></tr>
					<tr><th><label for="smart_lead_crm_whatsapp_default_country_code"><?php esc_html_e( 'Default Country Code', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_whatsapp_default_country_code" name="smart_lead_crm_whatsapp_default_country_code" value="<?php echo esc_attr( $settings->get( 'whatsapp_default_country_code', '91' ) ); ?>" placeholder="91" style="max-width:80px;" /><p class="description"><?php esc_html_e( 'Used when normalizing phone numbers.', 'smart-lead-crm' ); ?></p></td></tr>
				</table>
				<h3 style="margin:28px 0 8px;"><?php esc_html_e( 'Webhook', 'smart-lead-crm' ); ?></h3>
				<table class="slcrm-detail-table">
					<tr><th><?php esc_html_e( 'Webhook URL', 'smart-lead-crm' ); ?></th><td><code style="font-size:12px;word-break:break-all;"><?php echo esc_html( rest_url( 'slcrm/v1/webhook' ) ); ?></code></td></tr>
					<tr><th><label for="smart_lead_crm_whatsapp_verify_token"><?php esc_html_e( 'Verify Token', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_whatsapp_verify_token" name="smart_lead_crm_whatsapp_verify_token" value="<?php echo esc_attr( $settings->get( 'whatsapp_verify_token' ) ); ?>" placeholder="my_verify_token" class="regular-text" /></td></tr>
				</table>
				<h3 style="margin:28px 0 8px;"><?php esc_html_e( 'Cloud API Credentials', 'smart-lead-crm' ); ?></h3>
				<p style="color:var(--gray-500);font-size:13px;margin-bottom:16px;"><?php esc_html_e( 'Only required for Cloud API or Coexistence mode. Leave blank for App Mode.', 'smart-lead-crm' ); ?></p>
				<table class="slcrm-detail-table">
					<tr><th><label for="smart_lead_crm_whatsapp_access_token"><?php esc_html_e( 'Access Token', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_whatsapp_access_token" name="smart_lead_crm_whatsapp_access_token" value="<?php echo esc_attr( $settings->get( 'whatsapp_access_token' ) ); ?>" placeholder="EAAG…" class="regular-text" autocomplete="off" /></td></tr>
					<tr><th><label for="smart_lead_crm_whatsapp_phone_number_id"><?php esc_html_e( 'Phone Number ID', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_whatsapp_phone_number_id" name="smart_lead_crm_whatsapp_phone_number_id" value="<?php echo esc_attr( $settings->get( 'whatsapp_phone_number_id' ) ); ?>" placeholder="123456789012345" class="regular-text" /></td></tr>
					<tr><th><label for="smart_lead_crm_whatsapp_api_version"><?php esc_html_e( 'API Version', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_whatsapp_api_version" name="smart_lead_crm_whatsapp_api_version" value="<?php echo esc_attr( $settings->get( 'whatsapp_api_version', 'v18.0' ) ); ?>" placeholder="v18.0" style="max-width:120px;" /></td></tr>
				</table>
			</div>
		</div>

		<div id="slcrm-tab-tracking" class="slcrm-tab-panel">
			<div class="slcrm-card" style="max-width:680px;">
				<h3 style="margin-bottom:20px;"><?php esc_html_e( 'Attribution Tracking', 'smart-lead-crm' ); ?></h3>
				<table class="slcrm-detail-table">
					<tr><th><label for="smart_lead_crm_cookie_duration"><?php esc_html_e( 'Cookie Duration', 'smart-lead-crm' ); ?></label></th><td><input type="number" id="smart_lead_crm_cookie_duration" name="smart_lead_crm_cookie_duration" value="<?php echo esc_attr( $settings->get( 'cookie_duration', 90 ) ); ?>" min="1" max="365" class="small-text" /> <span style="color:var(--gray-400);font-size:13px;margin-left:6px;"><?php esc_html_e( 'days', 'smart-lead-crm' ); ?></span></td></tr>
					<tr><th><?php esc_html_e( 'Capture GCLID', 'smart-lead-crm' ); ?></th><td><label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="smart_lead_crm_capture_gclid" value="yes" <?php checked( $settings->get( 'capture_gclid', 'yes' ), 'yes' ); ?> /> <span><?php esc_html_e( 'Capture Google Click ID, GBRAID, and WBRAID parameters', 'smart-lead-crm' ); ?></span></label></td></tr>
					<tr><th><?php esc_html_e( 'Capture UTM', 'smart-lead-crm' ); ?></th><td><label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="smart_lead_crm_capture_utm" value="yes" <?php checked( $settings->get( 'capture_utm', 'yes' ), 'yes' ); ?> /> <span><?php esc_html_e( 'Capture UTM source, medium, campaign, term, and content', 'smart-lead-crm' ); ?></span></label></td></tr>
					<tr><th><?php esc_html_e( 'Debug Mode', 'smart-lead-crm' ); ?></th><td><label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="smart_lead_crm_enable_debug" value="yes" <?php checked( $settings->get( 'enable_debug', 'no' ), 'yes' ); ?> /> <span><?php esc_html_e( 'Write debug messages to WP_DEBUG_LOG', 'smart-lead-crm' ); ?></span></label></td></tr>
				</table>
			</div>
		</div>

		<div style="margin-top:24px;"><?php submit_button( __( 'Save Settings', 'smart-lead-crm' ), 'primary', 'submit', false ); ?></div>
	</form>
</div>
