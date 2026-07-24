<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings      = smart_lead_crm()->settings;
$mode          = $settings->get_whatsapp_mode();
$helper        = slcrm_helper();
$db            = slcrm_db();
$business_types = $helper->get_business_types();
$conversions    = $db->get_conversions();
$form_trackings = $db->get_form_trackings();
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title"><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'smart-lead-crm' ); ?></h1>
	<p class="slcrm-subtitle"><?php esc_html_e( 'Configure Smart Lead CRM for your business.', 'smart-lead-crm' ); ?></p>

	<form method="post" action="options.php">
		<?php settings_fields( 'smart_lead_crm_settings_group' ); ?>

		<div class="slcrm-settings-tabs">
			<a href="#" class="slcrm-settings-tab active" data-tab="business"><span class="dashicons dashicons-building"></span><?php esc_html_e( 'Business', 'smart-lead-crm' ); ?></a>
			<a href="#" class="slcrm-settings-tab" data-tab="whatsapp"><span class="dashicons dashicons-whatsapp"></span><?php esc_html_e( 'WhatsApp', 'smart-lead-crm' ); ?></a>
			<a href="#" class="slcrm-settings-tab" data-tab="autoreply"><span class="dashicons dashicons-megaphone"></span><?php esc_html_e( 'Auto-Reply', 'smart-lead-crm' ); ?></a>
			<a href="#" class="slcrm-settings-tab" data-tab="tracking"><span class="dashicons dashicons-chart-bar"></span><?php esc_html_e( 'Tracking', 'smart-lead-crm' ); ?></a>
			<a href="#" class="slcrm-settings-tab" data-tab="conversions"><span class="dashicons dashicons-target"></span><?php esc_html_e( 'Conversions', 'smart-lead-crm' ); ?></a>
			<a href="#" class="slcrm-settings-tab" data-tab="forms"><span class="dashicons dashicons-feedback"></span><?php esc_html_e( 'Form Tracking', 'smart-lead-crm' ); ?></a>
		</div>

		<div id="slcrm-tab-business" class="slcrm-tab-panel active">
			<div class="slcrm-card" style="max-width:680px;">
				<h3 style="margin-bottom:20px;"><?php esc_html_e( 'Business Details', 'smart-lead-crm' ); ?></h3>
				<table class="slcrm-detail-table">
					<tr><th><label for="smart_lead_crm_business_name"><?php esc_html_e( 'Business Name', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_business_name" name="smart_lead_crm_business_name" value="<?php echo esc_attr( $settings->get( 'business_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" /></td></tr>
					<tr><th><label for="smart_lead_crm_business_type"><?php esc_html_e( 'Business Type', 'smart-lead-crm' ); ?></label></th><td>
						<select id="smart_lead_crm_business_type" name="smart_lead_crm_business_type">
							<?php foreach ( $business_types as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $settings->get( 'business_type', 'taxi' ), $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Selecting your business type pre-configures conversion presets and form recommendations.', 'smart-lead-crm' ); ?></p>
					</td></tr>
				</table>
				<h3 style="margin:24px 0 20px;"><?php esc_html_e( 'Google Ads', 'smart-lead-crm' ); ?></h3>
				<table class="slcrm-detail-table">
					<tr><th><label for="smart_lead_crm_google_ads_conversion_id"><?php esc_html_e( 'Conversion ID', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_google_ads_conversion_id" name="smart_lead_crm_google_ads_conversion_id" value="<?php echo esc_attr( $settings->get( 'google_ads_conversion_id' ) ); ?>" placeholder="AW-XXXXXXXXX" class="regular-text" /></td></tr>
					<tr><th><label for="smart_lead_crm_ga4_measurement_id"><?php esc_html_e( 'GA4 Measurement ID', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_ga4_measurement_id" name="smart_lead_crm_ga4_measurement_id" value="<?php echo esc_attr( $settings->get( 'ga4_measurement_id' ) ); ?>" placeholder="G-XXXXXXXXXX" class="regular-text" /></td></tr>
				</table>
				<p style="color:var(--gray-500);font-size:13px;margin-top:12px;"><?php esc_html_e( 'Per-conversion labels are configured in the Conversions tab.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>

		<div id="slcrm-tab-whatsapp" class="slcrm-tab-panel">
			<div class="slcrm-card" style="max-width:800px;">
				<h3 style="margin-bottom:4px;"><?php esc_html_e( 'Connection Mode', 'smart-lead-crm' ); ?></h3>
				<p style="color:var(--gray-500);font-size:13px;margin-bottom:20px;"><?php esc_html_e( 'Choose how WhatsApp connects to your CRM. Settings below will update based on your selection.', 'smart-lead-crm' ); ?></p>

				<div class="slcrm-mode-cards" id="slcrm-wa-mode-cards">
					<?php
					$opts = array(
						'app_mode'    => array( 'label' => __( 'WhatsApp Business App', 'smart-lead-crm' ), 'desc' => __( 'Keep using your phone. CRM generates wa.me links.', 'smart-lead-crm' ), 'icon' => 'phone' ),
						'cloud_api'   => array( 'label' => __( 'Cloud API', 'smart-lead-crm' ),             'desc' => __( 'Official Meta API. Full automated send/receive.', 'smart-lead-crm' ),  'icon' => 'cloud' ),
						'coexistence' => array( 'label' => __( 'Coexistence', 'smart-lead-crm' ),           'desc' => __( 'Use Business App + Cloud API together.', 'smart-lead-crm' ),          'icon' => 'controls-repeat' ),
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

				<!-- Always visible: business number -->
				<h3 style="margin:28px 0 8px;"><?php esc_html_e( 'Your WhatsApp Number', 'smart-lead-crm' ); ?></h3>
				<table class="slcrm-detail-table">
					<tr><th><label for="smart_lead_crm_whatsapp_business_number"><?php esc_html_e( 'Business Number', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_whatsapp_business_number" name="smart_lead_crm_whatsapp_business_number" value="<?php echo esc_attr( $settings->get( 'whatsapp_business_number' ) ); ?>" placeholder="919876543210" class="regular-text" /><p class="description"><?php esc_html_e( 'Include country code, no + or spaces. E.g. 919876543210 for India.', 'smart-lead-crm' ); ?></p></td></tr>
					<tr><th><label for="smart_lead_crm_whatsapp_default_country_code"><?php esc_html_e( 'Default Country Code', 'smart-lead-crm' ); ?></label></th><td><input type="text" id="smart_lead_crm_whatsapp_default_country_code" name="smart_lead_crm_whatsapp_default_country_code" value="<?php echo esc_attr( $settings->get( 'whatsapp_default_country_code', '91' ) ); ?>" placeholder="91" style="max-width:80px;" /><p class="description"><?php esc_html_e( 'Prepended when a lead phone number has no country code.', 'smart-lead-crm' ); ?></p></td></tr>
				</table>

				<!-- APP MODE: setup guide -->
				<div class="slcrm-wa-section" data-modes="app_mode">
					<h3 style="margin:28px 0 8px;"><?php esc_html_e( 'App Mode — How It Works', 'smart-lead-crm' ); ?></h3>
					<div class="slcrm-setup-steps" style="margin-bottom:0;">
						<div class="slcrm-setup-step"><div class="slcrm-step-num">1</div><div><strong><?php esc_html_e( 'Enter your business number above', 'smart-lead-crm' ); ?></strong><p><?php esc_html_e( 'The CRM will generate wa.me links pointing to this number.', 'smart-lead-crm' ); ?></p></div></div>
						<div class="slcrm-setup-step"><div class="slcrm-step-num">2</div><div><strong><?php esc_html_e( 'No API key needed', 'smart-lead-crm' ); ?></strong><p><?php esc_html_e( 'When a lead is captured, the CRM shows a wa.me deep-link button. Click it to open WhatsApp on your phone and send messages manually.', 'smart-lead-crm' ); ?></p></div></div>
						<div class="slcrm-setup-step"><div class="slcrm-step-num">3</div><div><strong><?php esc_html_e( 'Save settings', 'smart-lead-crm' ); ?></strong><p><?php esc_html_e( 'That\'s it — no further configuration required for App Mode.', 'smart-lead-crm' ); ?></p></div></div>
					</div>
				</div>

				<!-- CLOUD API + COEXISTENCE: credentials + setup guide -->
				<div class="slcrm-wa-section" data-modes="cloud_api coexistence">
					<h3 style="margin:28px 0 8px;"><?php esc_html_e( 'Cloud API — Step-by-Step Setup', 'smart-lead-crm' ); ?></h3>
					<div class="slcrm-setup-steps">
						<div class="slcrm-setup-step"><div class="slcrm-step-num">1</div><div><strong><?php esc_html_e( 'Create a Meta Developer App', 'smart-lead-crm' ); ?></strong><p><?php esc_html_e( 'Go to developers.facebook.com → My Apps → Create App. Choose "Business" type. Connect it to your Meta Business Manager.', 'smart-lead-crm' ); ?></p></div></div>
						<div class="slcrm-setup-step"><div class="slcrm-step-num">2</div><div><strong><?php esc_html_e( 'Add the WhatsApp product', 'smart-lead-crm' ); ?></strong><p><?php esc_html_e( 'Inside your app dashboard click "Add Product" and select WhatsApp. This creates a WhatsApp Business Account (WABA) linked to your app.', 'smart-lead-crm' ); ?></p></div></div>
						<div class="slcrm-setup-step"><div class="slcrm-step-num">3</div><div><strong><?php esc_html_e( 'Add and verify your phone number', 'smart-lead-crm' ); ?></strong><p><?php esc_html_e( 'WhatsApp → API Setup → Add Phone Number. The number must NOT already be active on WhatsApp — if it is, delete that account in the WhatsApp app first, then register here. Verify with OTP.', 'smart-lead-crm' ); ?></p></div></div>
						<div class="slcrm-setup-step"><div class="slcrm-step-num">4</div><div><strong><?php esc_html_e( 'Get a Permanent Access Token (System User)', 'smart-lead-crm' ); ?></strong><p><?php esc_html_e( 'Temporary tokens expire in 24 hours. For production use a permanent System User token: Business Manager → Settings → System Users → Add → Admin → Generate Token → select your WhatsApp app → grant whatsapp_business_messaging + whatsapp_business_management permissions. Copy the token below.', 'smart-lead-crm' ); ?></p></div></div>
						<div class="slcrm-setup-step"><div class="slcrm-step-num">5</div><div><strong><?php esc_html_e( 'Copy your Phone Number ID', 'smart-lead-crm' ); ?></strong><p><?php esc_html_e( 'In WhatsApp → API Setup, select your phone number from the dropdown. The numeric "Phone number ID" (15+ digits) is shown just below — copy it into the field below. This is different from your actual phone number.', 'smart-lead-crm' ); ?></p></div></div>
						<div class="slcrm-setup-step"><div class="slcrm-step-num">6</div><div><strong><?php esc_html_e( 'Register the Webhook', 'smart-lead-crm' ); ?></strong><p><?php esc_html_e( 'In your Meta App → WhatsApp → Configuration → Webhooks: paste the Webhook URL below, enter your Verify Token (any secret string you choose), click Verify. Then subscribe to the "messages" field.', 'smart-lead-crm' ); ?></p></div></div>
						<div class="slcrm-setup-step"><div class="slcrm-step-num">7</div><div><strong><?php esc_html_e( 'Go Live', 'smart-lead-crm' ); ?></strong><p><?php esc_html_e( 'Your app starts in Development mode (can only message numbers added as test contacts). To message any number: submit your app for Business Verification in Meta Business Manager, then set the app to Live mode in the app dashboard.', 'smart-lead-crm' ); ?></p></div></div>
					</div>

					<h3 style="margin:28px 0 8px;"><?php esc_html_e( 'Webhook', 'smart-lead-crm' ); ?></h3>
					<table class="slcrm-detail-table">
						<tr>
							<th><?php esc_html_e( 'Webhook URL', 'smart-lead-crm' ); ?></th>
							<td>
								<code id="slcrm-webhook-url-text" style="font-size:12px;word-break:break-all;user-select:all;"><?php echo esc_html( rest_url( 'slcrm/v1/webhook' ) ); ?></code>
								<button type="button" class="slcrm-btn slcrm-btn-outline slcrm-btn-sm slcrm-copy-btn" style="margin-left:10px;" data-copy="<?php echo esc_attr( rest_url( 'slcrm/v1/webhook' ) ); ?>">
									<span class="dashicons dashicons-admin-page"></span> <?php esc_html_e( 'Copy', 'smart-lead-crm' ); ?>
								</button>
								<p class="description"><?php esc_html_e( 'Paste this as your Webhook Callback URL in the Meta App dashboard.', 'smart-lead-crm' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="smart_lead_crm_whatsapp_verify_token"><?php esc_html_e( 'Verify Token', 'smart-lead-crm' ); ?></label></th>
							<td>
								<input type="text" id="smart_lead_crm_whatsapp_verify_token" name="smart_lead_crm_whatsapp_verify_token" value="<?php echo esc_attr( $settings->get( 'whatsapp_verify_token' ) ); ?>" placeholder="e.g. mySecretToken123" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Any secret string you choose. Enter the same value in Meta\'s Webhook verification field.', 'smart-lead-crm' ); ?></p>
							</td>
						</tr>
					</table>

					<h3 style="margin:28px 0 8px;"><?php esc_html_e( 'Cloud API Credentials', 'smart-lead-crm' ); ?></h3>
					<table class="slcrm-detail-table">
						<tr>
							<th><label for="smart_lead_crm_whatsapp_access_token"><?php esc_html_e( 'Permanent Access Token', 'smart-lead-crm' ); ?></label></th>
							<td>
								<input type="password" id="smart_lead_crm_whatsapp_access_token" name="smart_lead_crm_whatsapp_access_token" value="<?php echo esc_attr( $settings->get( 'whatsapp_access_token' ) ); ?>" placeholder="EAAG…" class="regular-text" autocomplete="new-password" />
								<p class="description"><?php esc_html_e( 'Use a System User token (never expires). Found in Business Manager → System Users.', 'smart-lead-crm' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="smart_lead_crm_whatsapp_phone_number_id"><?php esc_html_e( 'Phone Number ID', 'smart-lead-crm' ); ?></label></th>
							<td>
								<input type="text" id="smart_lead_crm_whatsapp_phone_number_id" name="smart_lead_crm_whatsapp_phone_number_id" value="<?php echo esc_attr( $settings->get( 'whatsapp_phone_number_id' ) ); ?>" placeholder="123456789012345" class="regular-text" />
								<p class="description"><?php esc_html_e( 'The numeric ID shown under WhatsApp → API Setup (not your phone number itself).', 'smart-lead-crm' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="smart_lead_crm_whatsapp_api_version"><?php esc_html_e( 'API Version', 'smart-lead-crm' ); ?></label></th>
							<td>
								<input type="text" id="smart_lead_crm_whatsapp_api_version" name="smart_lead_crm_whatsapp_api_version" value="<?php echo esc_attr( $settings->get( 'whatsapp_api_version', 'v21.0' ) ); ?>" placeholder="v21.0" style="max-width:120px;" />
								<p class="description"><?php esc_html_e( 'Current stable version is v21.0. Only change this if Meta releases a newer version.', 'smart-lead-crm' ); ?></p>
							</td>
						</tr>
					</table>

					<div style="margin-top:20px;padding:14px 16px;background:var(--primary-50,#eff6ff);border:1px solid var(--primary-200,#bfdbfe);border-radius:var(--radius-sm);font-size:13px;color:var(--primary-700,#1d4ed8);">
						<strong><?php esc_html_e( 'Coexistence note:', 'smart-lead-crm' ); ?></strong>
						<?php esc_html_e( 'Coexistence lets the same number work in both the WhatsApp Business App on your phone AND the Cloud API simultaneously. Enable it in Meta App → WhatsApp → Configuration → Coexistence. Both the App Mode wa.me links and Cloud API automated messages will work at the same time.', 'smart-lead-crm' ); ?>
					</div>
				</div>

			</div>
		</div>

		<div id="slcrm-tab-autoreply" class="slcrm-tab-panel">
			<div class="slcrm-card" style="max-width:680px;">
				<h3 style="margin-bottom:8px;"><?php esc_html_e( 'Auto-Reply', 'smart-lead-crm' ); ?></h3>
				<p style="color:var(--gray-500);font-size:13px;margin-bottom:20px;"><?php esc_html_e( 'Automatically send a WhatsApp message when a new lead is captured. Responding within 5 minutes increases conversion by up to 21x.', 'smart-lead-crm' ); ?></p>
				<table class="slcrm-detail-table">
					<tr><th><?php esc_html_e( 'Enable Auto-Reply', 'smart-lead-crm' ); ?></th><td><label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="smart_lead_crm_auto_reply_enabled" value="yes" <?php checked( $settings->get( 'auto_reply_enabled', 'no' ), 'yes' ); ?> /> <span><?php esc_html_e( 'Send automatic WhatsApp message when a lead is created', 'smart-lead-crm' ); ?></span></label></td></tr>
					<tr><th><label for="smart_lead_crm_auto_reply_template"><?php esc_html_e( 'Reply Template', 'smart-lead-crm' ); ?></label></th><td>
						<textarea id="smart_lead_crm_auto_reply_template" name="smart_lead_crm_auto_reply_template" rows="4" class="large-text" placeholder="Hi! Thanks for reaching out to {business_name}. We'll get back to you within 10 minutes."><?php echo esc_textarea( $settings->get( 'auto_reply_template' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Use {business_name} and {customer_name} as placeholders.', 'smart-lead-crm' ); ?></p>
					</td></tr>
				</table>
				<h3 style="margin:28px 0 8px;"><?php esc_html_e( 'Ad Spend (for ROI Dashboard)', 'smart-lead-crm' ); ?></h3>
				<table class="slcrm-detail-table">
					<tr><th><label for="smart_lead_crm_google_ads_daily_spend"><?php esc_html_e( 'Daily Google Ads Spend', 'smart-lead-crm' ); ?></label></th><td><input type="number" id="smart_lead_crm_google_ads_daily_spend" name="smart_lead_crm_google_ads_daily_spend" value="<?php echo esc_attr( $settings->get( 'google_ads_daily_spend' ) ); ?>" placeholder="500" class="regular-text" /> <span style="color:var(--gray-400);font-size:13px;margin-left:6px;"><?php esc_html_e( 'in your currency', 'smart-lead-crm' ); ?></span><p class="description"><?php esc_html_e( 'Used to calculate ROI on the dashboard. Enter your average daily Google Ads budget.', 'smart-lead-crm' ); ?></p></td></tr>
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
					<tr><th><?php esc_html_e( 'Organic Keywords', 'smart-lead-crm' ); ?></th><td><label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="smart_lead_crm_capture_organic_keywords" value="yes" <?php checked( $settings->get( 'capture_organic_keywords', 'yes' ), 'yes' ); ?> /> <span><?php esc_html_e( 'Extract search keywords from organic search engine referrers (Google, Bing, Yahoo, etc.)', 'smart-lead-crm' ); ?></span></label></td></tr>
					<tr><th><?php esc_html_e( 'Debug Mode', 'smart-lead-crm' ); ?></th><td><label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="smart_lead_crm_enable_debug" value="yes" <?php checked( $settings->get( 'enable_debug', 'no' ), 'yes' ); ?> /> <span><?php esc_html_e( 'Write debug messages to WP_DEBUG_LOG', 'smart-lead-crm' ); ?></span></label></td></tr>
				</table>
				<h3 style="margin:28px 0 12px;"><?php esc_html_e( 'Form Data Capture', 'smart-lead-crm' ); ?></h3>
				<table class="slcrm-detail-table">
					<tr><th><label for="smart_lead_crm_form_capture_name"><?php esc_html_e( 'Customer Name', 'smart-lead-crm' ); ?></label></th><td>
						<select id="smart_lead_crm_form_capture_name" name="smart_lead_crm_form_capture_name">
							<option value="auto" <?php selected( $settings->get( 'form_capture_name', 'auto' ), 'auto' ); ?>><?php esc_html_e( 'Auto-detect from form fields', 'smart-lead-crm' ); ?></option>
							<option value="require" <?php selected( $settings->get( 'form_capture_name', 'auto' ), 'require' ); ?>><?php esc_html_e( 'Require (only save if name field exists)', 'smart-lead-crm' ); ?></option>
							<option value="skip" <?php selected( $settings->get( 'form_capture_name', 'auto' ), 'skip' ); ?>><?php esc_html_e( 'Skip (do not capture name)', 'smart-lead-crm' ); ?></option>
						</select>
					</td></tr>
					<tr><th><label for="smart_lead_crm_form_capture_email"><?php esc_html_e( 'Email Address', 'smart-lead-crm' ); ?></label></th><td>
						<select id="smart_lead_crm_form_capture_email" name="smart_lead_crm_form_capture_email">
							<option value="auto" <?php selected( $settings->get( 'form_capture_email', 'auto' ), 'auto' ); ?>><?php esc_html_e( 'Auto-detect from form fields', 'smart-lead-crm' ); ?></option>
							<option value="require" <?php selected( $settings->get( 'form_capture_email', 'auto' ), 'require' ); ?>><?php esc_html_e( 'Require', 'smart-lead-crm' ); ?></option>
							<option value="skip" <?php selected( $settings->get( 'form_capture_email', 'auto' ), 'skip' ); ?>><?php esc_html_e( 'Skip', 'smart-lead-crm' ); ?></option>
						</select>
					</td></tr>
					<tr><th><label for="smart_lead_crm_form_capture_phone"><?php esc_html_e( 'Phone Number', 'smart-lead-crm' ); ?></label></th><td>
						<select id="smart_lead_crm_form_capture_phone" name="smart_lead_crm_form_capture_phone">
							<option value="auto" <?php selected( $settings->get( 'form_capture_phone', 'auto' ), 'auto' ); ?>><?php esc_html_e( 'Auto-detect from form fields', 'smart-lead-crm' ); ?></option>
							<option value="require" <?php selected( $settings->get( 'form_capture_phone', 'auto' ), 'require' ); ?>><?php esc_html_e( 'Require', 'smart-lead-crm' ); ?></option>
							<option value="skip" <?php selected( $settings->get( 'form_capture_phone', 'auto' ), 'skip' ); ?>><?php esc_html_e( 'Skip', 'smart-lead-crm' ); ?></option>
						</select>
					</td></tr>
				</table>
			</div>
		</div>

		<div id="slcrm-tab-conversions" class="slcrm-tab-panel">
			<div class="slcrm-card" style="max-width:920px;">
				<h3 style="margin-bottom:8px;"><?php esc_html_e( 'Conversion Tracking', 'smart-lead-crm' ); ?></h3>
				<p style="color:var(--gray-500);font-size:13px;margin-bottom:20px;"><?php esc_html_e( 'Map each CRM action to a Google Ads conversion label and GA4 event name. Enabled conversions fire automatically when the action occurs.', 'smart-lead-crm' ); ?></p>
				<table class="slcrm-table slcrm-conversion-table">
					<thead><tr>
						<th style="width:40px;">✓</th>
						<th><?php esc_html_e( 'Label', 'smart-lead-crm' ); ?></th>
						<th><?php esc_html_e( 'CRM Action', 'smart-lead-crm' ); ?></th>
						<th><?php esc_html_e( 'Google Ads Label', 'smart-lead-crm' ); ?></th>
						<th><?php esc_html_e( 'GA4 Event', 'smart-lead-crm' ); ?></th>
						<th><?php esc_html_e( 'Category', 'smart-lead-crm' ); ?></th>
						<th style="width:80px;"></th>
					</tr></thead>
					<tbody id="slcrm-conversions-body">
					<?php foreach ( $conversions as $c ) : ?>
						<tr data-id="<?php echo esc_attr( $c->id ); ?>">
							<td><input type="checkbox" class="slcrm-conv-enabled" <?php checked( $c->enabled, 1 ); ?> /></td>
							<td><input type="text" class="slcrm-conv-label" value="<?php echo esc_attr( $c->label ); ?>" /></td>
							<td><input type="text" class="slcrm-conv-action" value="<?php echo esc_attr( $c->crm_action ); ?>" /></td>
							<td><input type="text" class="slcrm-conv-ads" value="<?php echo esc_attr( $c->google_ads_label ); ?>" placeholder="e.g. AbcD1234" /></td>
							<td><input type="text" class="slcrm-conv-ga4" value="<?php echo esc_attr( $c->ga4_event ); ?>" /></td>
							<td>
								<select class="slcrm-conv-category">
									<option value="interaction" <?php selected( $c->category, 'interaction' ); ?>><?php esc_html_e( 'Interaction', 'smart-lead-crm' ); ?></option>
									<option value="form" <?php selected( $c->category, 'form' ); ?>><?php esc_html_e( 'Form', 'smart-lead-crm' ); ?></option>
									<option value="custom" <?php selected( $c->category, 'custom' ); ?>><?php esc_html_e( 'Custom', 'smart-lead-crm' ); ?></option>
								</select>
							</td>
							<td><button class="slcrm-btn slcrm-btn-outline slcrm-btn-sm slcrm-conv-save"><?php esc_html_e( 'Save', 'smart-lead-crm' ); ?></button> <button class="slcrm-btn slcrm-btn-outline slcrm-btn-sm slcrm-conv-delete" style="margin-left:4px;color:#dc2626;"><?php esc_html_e( 'Delete', 'smart-lead-crm' ); ?></button></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<div style="margin-top:16px;">
					<button id="slcrm-add-conversion" class="slcrm-btn slcrm-btn-primary slcrm-btn-sm"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Conversion', 'smart-lead-crm' ); ?></button>
				</div>
			</div>
		</div>

		<div id="slcrm-tab-forms" class="slcrm-tab-panel">
			<div class="slcrm-card" style="max-width:920px;">
				<h3 style="margin-bottom:8px;"><?php esc_html_e( 'Form Tracking', 'smart-lead-crm' ); ?></h3>
				<p style="color:var(--gray-500);font-size:13px;margin-bottom:20px;"><?php esc_html_e( 'Track any form on your site by its CSS selector. Works with Contact Form 7, Elementor Forms, WPForms, Fluent Forms, Gravity Forms, and custom HTML forms.', 'smart-lead-crm' ); ?></p>
				<table class="slcrm-table slcrm-form-track-table">
					<thead><tr>
						<th style="width:40px;">✓</th>
						<th><?php esc_html_e( 'Form Name', 'smart-lead-crm' ); ?></th>
						<th><?php esc_html_e( 'CSS Selector', 'smart-lead-crm' ); ?></th>
						<th><?php esc_html_e( 'Event', 'smart-lead-crm' ); ?></th>
						<th><?php esc_html_e( 'CRM Action', 'smart-lead-crm' ); ?></th>
						<th style="width:80px;"></th>
					</tr></thead>
					<tbody id="slcrm-forms-body">
					<?php foreach ( $form_trackings as $f ) : ?>
						<tr data-id="<?php echo esc_attr( $f->id ); ?>">
							<td><input type="checkbox" class="slcrm-form-enabled" <?php checked( $f->enabled, 1 ); ?> /></td>
							<td><input type="text" class="slcrm-form-name" value="<?php echo esc_attr( $f->form_name ); ?>" /></td>
							<td><input type="text" class="slcrm-form-selector" value="<?php echo esc_attr( $f->selector ); ?>" placeholder="#my-form" /></td>
							<td>
								<select class="slcrm-form-event">
									<option value="submit" <?php selected( $f->event_type, 'submit' ); ?>><?php esc_html_e( 'submit', 'smart-lead-crm' ); ?></option>
									<option value="click" <?php selected( $f->event_type, 'click' ); ?>><?php esc_html_e( 'click', 'smart-lead-crm' ); ?></option>
									<option value="change" <?php selected( $f->event_type, 'change' ); ?>><?php esc_html_e( 'change', 'smart-lead-crm' ); ?></option>
								</select>
							</td>
							<td><input type="text" class="slcrm-form-action" value="<?php echo esc_attr( $f->crm_action ); ?>" placeholder="e.g. contact_form" /></td>
							<td><button class="slcrm-btn slcrm-btn-outline slcrm-btn-sm slcrm-form-save"><?php esc_html_e( 'Save', 'smart-lead-crm' ); ?></button> <button class="slcrm-btn slcrm-btn-outline slcrm-btn-sm slcrm-form-delete" style="margin-left:4px;color:#dc2626;"><?php esc_html_e( 'Delete', 'smart-lead-crm' ); ?></button></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<div style="margin-top:16px;">
					<button id="slcrm-add-form" class="slcrm-btn slcrm-btn-primary slcrm-btn-sm"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Form', 'smart-lead-crm' ); ?></button>
				</div>
				<p style="color:var(--gray-400);font-size:12px;margin-top:16px;"><?php esc_html_e( 'Tip: Use any valid CSS selector. For example #wpcf7-f123-p456-o1 for Contact Form 7, .elementor-form for Elementor, or .wpforms-form for WPForms.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>

		<div style="margin-top:24px;"><?php submit_button( __( 'Save Settings', 'smart-lead-crm' ), 'primary', 'submit', false ); ?></div>
	</form>
</div>
