<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings     = smart_lead_crm()->settings;
$mode         = $settings->get_whatsapp_mode();
$mode_label   = $settings->get_whatsapp_mode_label();
$wa_number    = $settings->get( 'whatsapp_business_number' );
$cloud_ready  = $settings->is_cloud_api_configured();
$webhook_url  = rest_url( 'slcrm/v1/webhook' );
$verify_token = $settings->get( 'whatsapp_verify_token' );
$site_url     = home_url();

$modes = array(
	'app_mode' => array(
		'title'=>__('WhatsApp Business App','smart-lead-crm'), 'subtitle'=>__('Recommended for small businesses','smart-lead-crm'),
		'desc'=>__('Keep using your phone exactly as you do today. The CRM automatically captures every inbound customer message as a lead with full contact history. You reply from the WhatsApp Business App.','smart-lead-crm'),
		'icon'=>'phone', 'color'=>'var(--wa-green)', 'bg'=>'#f0fdf4',
		'features'=>array(
			array('yes',__('Keep using phone app','smart-lead-crm')),
			array('yes',__('Auto lead capture from messages','smart-lead-crm')),
			array('yes',__('Full customer history in CRM','smart-lead-crm')),
			array('yes',__('Click-to-chat from leads','smart-lead-crm')),
			array('no',__('Reply from CRM (future)','smart-lead-crm')),
			array('no',__('Team inbox (future)','smart-lead-crm')),
		),
	),
	'cloud_api' => array(
		'title'=>__('Cloud API','smart-lead-crm'), 'subtitle'=>__('Official Meta integration','smart-lead-crm'),
		'desc'=>__('The official Meta WhatsApp Cloud API. Enables sending and receiving messages directly from the CRM — ideal for team inboxes, automation, AI replies, and marketing campaigns.','smart-lead-crm'),
		'icon'=>'cloud', 'color'=>'var(--primary-600)', 'bg'=>'var(--primary-50)',
		'features'=>array(
			array('yes',__('Send & receive from CRM','smart-lead-crm')),
			array('yes',__('Auto lead capture','smart-lead-crm')),
			array('yes',__('Team inbox ready','smart-lead-crm')),
			array('yes',__('Automation & AI ready','smart-lead-crm')),
			array('yes',__('Marketing campaigns','smart-lead-crm')),
			array('no',__('Phone app (separate number)','smart-lead-crm')),
		),
	),
	'coexistence' => array(
		'title'=>__('Coexistence','smart-lead-crm'), 'subtitle'=>__('Premium — App + Cloud API','smart-lead-crm'),
		'desc'=>__('Use the WhatsApp Business App on your phone AND the Cloud API simultaneously. Keep the familiar phone experience while gaining full CRM power.','smart-lead-crm'),
		'icon'=>'sync', 'color'=>'#7c3aed', 'bg'=>'#faf5ff',
		'features'=>array(
			array('yes',__('Keep using phone app','smart-lead-crm')),
			array('yes',__('Send & receive from CRM','smart-lead-crm')),
			array('yes',__('Messages sync both ways','smart-lead-crm')),
			array('yes',__('Team inbox ready','smart-lead-crm')),
			array('yes',__('Automation & AI ready','smart-lead-crm')),
			array('yes',__('Best of both worlds','smart-lead-crm')),
		),
	),
);
$current = $modes[ $mode ];
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title"><span class="dashicons dashicons-whatsapp" style="color:var(--wa-green);"></span> <?php esc_html_e( 'WhatsApp Connection', 'smart-lead-crm' ); ?></h1>
	<p class="slcrm-subtitle"><?php esc_html_e( 'Choose how WhatsApp connects to Smart Lead CRM. You can change this anytime.', 'smart-lead-crm' ); ?></p>

	<!-- Mode Cards -->
	<div class="slcrm-wa-mode-selector">
		<?php foreach ( $modes as $mk => $m ) : $is_active = ( $mode === $mk ); ?>
		<div class="slcrm-wa-mode-card <?php echo $is_active ? 'slcrm-wa-mode-card--active' : ''; ?>">
			<div class="slcrm-wa-mode-icon" style="background:<?php echo esc_attr( $m['bg'] ); ?>;color:<?php echo esc_attr( $m['color'] ); ?>;"><span class="dashicons dashicons-<?php echo esc_attr( $m['icon'] ); ?>"></span></div>
			<h3 style="color:var(--gray-800);"><?php echo esc_html( $m['title'] ); ?></h3>
			<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:<?php echo esc_attr( $m['color'] ); ?>;margin-bottom:8px;"><?php echo esc_html( $m['subtitle'] ); ?></div>
			<p class="slcrm-wa-mode-desc"><?php echo esc_html( $m['desc'] ); ?></p>
			<ul class="slcrm-wa-mode-features">
				<?php foreach ( $m['features'] as $f ) : ?>
				<li><span class="feat-<?php echo esc_attr( $f[0] ); ?> dashicons dashicons-<?php echo 'yes'===$f[0]?'yes-alt':'minus'; ?>"></span> <?php echo esc_html( $f[1] ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( ! $is_active ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" class="slcrm-btn slcrm-btn-outline slcrm-btn-sm" style="margin-top:16px;"><?php esc_html_e( 'Switch to this mode', 'smart-lead-crm' ); ?></a>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Status Banner -->
	<?php if ( 'app_mode' === $mode ) : ?>
	<div class="slcrm-banner slcrm-banner-info" style="margin-bottom:16px;">
		<span class="dashicons dashicons-info"></span>
		<div><?php esc_html_e( 'App Mode is active. The CRM captures every inbound customer message as a lead. To enable this, complete the Meta App setup below — the webhook is what makes auto-capture work. You reply from your phone\'s WhatsApp Business App.', 'smart-lead-crm' ); ?></div>
	</div>
	<?php elseif ( $cloud_ready ) : ?>
	<div class="slcrm-banner slcrm-banner-success" style="margin-bottom:16px;">
		<span class="dashicons dashicons-yes-alt"></span>
		<div><strong><?php esc_html_e( 'Cloud API is connected.', 'smart-lead-crm' ); ?></strong> <?php esc_html_e( 'You can send and receive messages directly from the CRM lead detail screen. Make sure the webhook is also configured so inbound messages are captured.', 'smart-lead-crm' ); ?></div>
	</div>
	<?php else : ?>
	<div class="slcrm-banner slcrm-banner-warning" style="margin-bottom:16px;">
		<span class="dashicons dashicons-warning"></span>
		<div><strong><?php esc_html_e( 'Cloud API credentials not set.', 'smart-lead-crm' ); ?></strong> <?php esc_html_e( 'Add your Access Token and Phone Number ID in Settings to enable sending/receiving from the CRM.', 'smart-lead-crm' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" style="font-weight:700;margin-left:6px;"><?php esc_html_e( 'Configure now →', 'smart-lead-crm' ); ?></a></div>
	</div>
	<?php endif; ?>

	<!-- Webhook Configuration -->
	<div class="slcrm-card" style="margin-bottom:16px;">
		<div class="slcrm-section-header"><h3><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Webhook Configuration', 'smart-lead-crm' ); ?> — <?php esc_html_e( 'Required for all modes', 'smart-lead-crm' ); ?></h3></div>
		<p style="font-size:13px;color:var(--gray-500);margin-bottom:20px;"><?php esc_html_e( 'The webhook is what makes auto-lead-capture work. When a customer messages your WhatsApp Business number, Meta sends that message to this URL, and the CRM creates a lead. Without the webhook, messages will not appear in the CRM.', 'smart-lead-crm' ); ?></p>

		<div class="slcrm-grid slcrm-grid-2">
			<div>
				<div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-400);margin-bottom:8px;"><?php esc_html_e( 'Callback URL', 'smart-lead-crm' ); ?></div>
				<div class="slcrm-webhook-box">
					<span><?php echo esc_html( $webhook_url ); ?></span>
					<button class="slcrm-copy-btn button" data-copy="<?php echo esc_attr( $webhook_url ); ?>"><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'smart-lead-crm' ); ?></button>
				</div>
			</div>
			<div>
				<div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-400);margin-bottom:8px;"><?php esc_html_e( 'Verify Token', 'smart-lead-crm' ); ?></div>
				<?php if ( $verify_token ) : ?>
				<div class="slcrm-webhook-box">
					<span><?php echo esc_html( $verify_token ); ?></span>
					<button class="slcrm-copy-btn button" data-copy="<?php echo esc_attr( $verify_token ); ?>"><span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'smart-lead-crm' ); ?></button>
				</div>
				<?php else : ?>
				<div class="slcrm-banner slcrm-banner-warning" style="margin:0;"><span class="dashicons dashicons-warning"></span><div><?php esc_html_e( 'Verify token not set.', 'smart-lead-crm' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" style="font-weight:700;margin-left:4px;"><?php esc_html_e( 'Set it in Settings →', 'smart-lead-crm' ); ?></a></div></div>
				<?php endif; ?>
			</div>
		</div>

		<div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--gray-100);">
			<div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-400);margin-bottom:10px;"><?php esc_html_e( 'Webhook Fields to Subscribe', 'smart-lead-crm' ); ?></div>
			<div style="display:flex;gap:8px;flex-wrap:wrap;">
				<code style="background:var(--primary-50);color:var(--primary-700);padding:4px 10px;border-radius:4px;font-size:12px;font-weight:600;">messages</code>
				<?php foreach ( array( 'message_deliveries', 'message_reads', 'messaging_postbacks' ) as $field ) : ?>
				<code style="background:var(--gray-100);padding:4px 10px;border-radius:4px;font-size:12px;"><?php echo esc_html( $field ); ?></code>
				<?php endforeach; ?>
			</div>
			<p style="font-size:12px;color:var(--gray-400);margin-top:8px;margin-bottom:0;"><?php esc_html_e( 'At minimum, subscribe to "messages" — that is the field that delivers inbound customer messages to the CRM.', 'smart-lead-crm' ); ?></p>
		</div>
	</div>

	<!-- Detailed Setup Guide -->
	<div class="slcrm-card" style="margin-bottom:16px;">
		<div class="slcrm-section-header"><h3><span class="dashicons dashicons-<?php echo esc_attr( $current['icon'] ); ?>" style="color:<?php echo esc_attr( $current['color'] ); ?>;"></span> <?php echo esc_html( $current['title'] ); ?> — <?php esc_html_e( 'Complete Setup Guide', 'smart-lead-crm' ); ?></h3></div>

		<?php if ( 'app_mode' === $mode ) : ?>
		<!-- ═══ APP MODE GUIDE ═══ -->
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--wa-green);">1</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Install WhatsApp Business App', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'Download the WhatsApp Business App from the Play Store (Android) or App Store (iPhone). Register with your business number (currently:', 'smart-lead-crm' ); ?> <strong><?php echo esc_html( $wa_number ?: '—' ); ?></strong>).<?php esc_html_e( ' This is the number customers message you on.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--wa-green);">2</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Go to Meta for Developers', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'Open', 'smart-lead-crm' ); ?> <a href="https://developers.facebook.com" target="_blank" style="font-weight:600;">developers.facebook.com</a> <?php esc_html_e( 'and log in with your Facebook account. Click "My Apps" → "Create App". Select "Business" as the app type. Give it a name like "Smart Lead CRM" and link it to your Business Manager.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--wa-green);">3</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Add the WhatsApp Product', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'In your Meta App dashboard, scroll down and click "Add Product". Find "WhatsApp" and click "Set Up". Select your Business Manager and the phone number registered in your WhatsApp Business App. Meta will give you a test phone number and a Phone Number ID — note the Phone Number ID.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--wa-green);">4</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Configure the Webhook', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'In the left sidebar, go to WhatsApp → Configuration → Webhook. Click "Edit" or "Add Callback URL". Paste the Callback URL from the box above. Paste the Verify Token from the box above. Click "Verify and Save" — Meta will ping your site to confirm it\'s reachable.', 'smart-lead-crm' ); ?></p>
				<div class="slcrm-banner slcrm-banner-warning" style="margin-top:10px;"><span class="dashicons dashicons-warning"></span><div><strong><?php esc_html_e( 'If verification fails:', 'smart-lead-crm' ); ?></strong> <?php esc_html_e( 'Make sure your site has SSL (https://) active. Meta requires HTTPS for all webhooks. Also check that the Verify Token in Settings matches exactly what you entered in Meta.', 'smart-lead-crm' ); ?></div></div>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--wa-green);">5</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Subscribe to Webhook Fields', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'After verifying the callback URL, scroll down to the "Webhook Fields" section. Click "Subscribe" next to "messages" — this is the critical one. Also subscribe to "message_deliveries" and "message_reads" for delivery status. Without subscribing to "messages", the CRM will not receive anything.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--wa-green);">6</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Add a Test Recipient', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'In Meta App → WhatsApp → API Setup, add your own phone number as a test recipient. Send a test message from that number to your WhatsApp Business number. The message should appear in the CRM within seconds as a new lead.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--wa-green);">7</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Verify It Works', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'Send a WhatsApp message from any phone to your business number. Then go to Smart Lead CRM → Leads. You should see a new lead with the customer\'s phone number, their message text, and source "WhatsApp". If it does not appear, check:', 'smart-lead-crm' ); ?></p>
				<ul style="margin:8px 0 0 20px;font-size:13px;color:var(--gray-600);line-height:1.8;">
					<li><?php esc_html_e( 'Webhook is verified and saved in Meta App', 'smart-lead-crm' ); ?></li>
					<li><?php esc_html_e( '"messages" field is subscribed', 'smart-lead-crm' ); ?></li>
					<li><?php esc_html_e( 'Your site has valid SSL (https)', 'smart-lead-crm' ); ?></li>
					<li><?php esc_html_e( 'Verify Token in Settings matches Meta App', 'smart-lead-crm' ); ?></li>
				</ul>
			</div>
		</div>

		<?php elseif ( 'cloud_api' === $mode ) : ?>
		<!-- ═══ CLOUD API GUIDE ═══ -->
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--primary-600);">1</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Create a Meta App', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'Go to', 'smart-lead-crm' ); ?> <a href="https://developers.facebook.com" target="_blank" style="font-weight:600;">developers.facebook.com</a> <?php esc_html_e( '→ My Apps → Create App → Business type. Name it "Smart Lead CRM". Link it to your Business Manager.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--primary-600);">2</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Add WhatsApp Product & Get Credentials', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'Add the WhatsApp product to your app. Go to WhatsApp → API Setup. You need two values:', 'smart-lead-crm' ); ?></p>
				<ul style="margin:8px 0 0 20px;font-size:13px;color:var(--gray-600);line-height:1.8;">
					<li><strong><?php esc_html_e( 'Permanent Access Token:', 'smart-lead-crm' ); ?></strong> <?php esc_html_e( 'Go to System Users in Business Settings → Add System User → Generate Permanent Token. Give it whatsapp_business_messaging permission.', 'smart-lead-crm' ); ?></li>
					<li><strong><?php esc_html_e( 'Phone Number ID:', 'smart-lead-crm' ); ?></strong> <?php esc_html_e( 'Found in WhatsApp → API Setup → Phone Number ID field.', 'smart-lead-crm' ); ?></li>
				</ul>
				<p style="margin-top:10px;"><?php esc_html_e( 'Enter both values in', 'smart-lead-crm' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" style="font-weight:600;"><?php esc_html_e( 'Settings → WhatsApp tab', 'smart-lead-crm' ); ?></a>.</p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--primary-600);">3</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Configure the Webhook', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'Go to WhatsApp → Configuration → Webhook. Paste the Callback URL and Verify Token from the boxes above. Click "Verify and Save". Then subscribe to "messages", "message_deliveries", and "message_reads" fields.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--primary-600);">4</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Add a Test Number & Send', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'In WhatsApp → API Setup, add your personal phone as a test recipient. Send a message from that number to your WhatsApp Business number. It should appear in CRM → Leads within seconds. You can also reply directly from the lead detail screen in the CRM.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:var(--primary-600);">5</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Go Live (Production)', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'In Meta App → App Review → Permissions, submit for "whatsapp_business_messaging" access. Once approved, any WhatsApp user can message your business number and the CRM captures it automatically. Until approved, only test numbers work.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>

		<?php else : ?>
		<!-- ═══ COEXISTENCE GUIDE ═══ -->
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:#7c3aed;">1</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Verify Eligibility', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'Coexistence requires a verified Meta Business account and a WhatsApp Business number that is not already registered as a Cloud API-only number. Check in Meta Business Manager → WhatsApp Manager → Overview. Your number must show as "Connected" to the Business App.', 'smart-lead-crm' ); ?></p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:#7c3aed;">2</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Configure Cloud API Credentials', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'Follow the Cloud API steps above (steps 2-3) to get your Access Token and Phone Number ID. Enter them in', 'smart-lead-crm' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" style="font-weight:600;"><?php esc_html_e( 'Settings → WhatsApp', 'smart-lead-crm' ); ?></a>.</p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:#7c3aed;">3</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Configure the Webhook', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'Same as the other modes — add the Callback URL and Verify Token to Meta App → WhatsApp → Configuration → Webhook. Subscribe to "messages".', 'smart-lead-crm' ); ?></p>
			</div>
		</div>
		<div class="slcrm-setup-step">
			<div class="slcrm-step-num" style="background:#7c3aed;">4</div>
			<div class="slcrm-step-body">
				<h4><?php esc_html_e( 'Both Channels Active', 'smart-lead-crm' ); ?></h4>
				<p><?php esc_html_e( 'You can now receive messages on your phone\'s WhatsApp Business App AND send/receive from the CRM. Messages sync in both directions. When you reply from the CRM, it also appears in the phone app (and vice versa).', 'smart-lead-crm' ); ?></p>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<!-- Business Number + Quick Actions -->
	<div class="slcrm-grid slcrm-grid-2">
		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-phone"></span> <?php esc_html_e( 'Business Number', 'smart-lead-crm' ); ?></h3></div>
			<?php if ( $wa_number ) : ?>
				<div style="display:flex;align-items:center;gap:12px;">
					<div style="width:48px;height:48px;border-radius:50%;background:var(--wa-green);color:#fff;display:flex;align-items:center;justify-content:center;">
						<span class="dashicons dashicons-whatsapp" style="font-size:24px;width:24px;height:24px;"></span>
					</div>
					<div>
						<div style="font-size:20px;font-weight:700;color:var(--gray-800);"><?php echo esc_html( $wa_number ); ?></div>
						<div style="font-size:12px;color:var(--gray-400);"><?php esc_html_e( 'Customers message this number → CRM captures as lead', 'smart-lead-crm' ); ?></div>
					</div>
					<a href="https://wa.me/<?php echo esc_attr( preg_replace('/[^0-9]/','',$wa_number) ); ?>" target="_blank" class="slcrm-btn slcrm-btn-sm" style="background:var(--wa-green);color:#fff;margin-left:auto;"><span class="dashicons dashicons-whatsapp"></span> <?php esc_html_e( 'Open', 'smart-lead-crm' ); ?></a>
				</div>
				<div class="slcrm-banner slcrm-banner-info" style="margin-top:16px;margin-bottom:0;">
					<span class="dashicons dashicons-info"></span>
					<div><?php esc_html_e( 'Make sure this exact number (with country code, no + or spaces) is entered in Settings → WhatsApp → Business Number. The tracker script uses this to identify your number and avoid creating leads when you click your own WhatsApp link.', 'smart-lead-crm' ); ?></div>
				</div>
			<?php else : ?>
				<div class="slcrm-banner slcrm-banner-warning" style="margin:0;"><span class="dashicons dashicons-warning"></span><div><strong><?php esc_html_e( 'Business number not set.', 'smart-lead-crm' ); ?></strong> <?php esc_html_e( 'Add it in Settings so the CRM knows which number to track and display.', 'smart-lead-crm' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" style="font-weight:700;margin-left:4px;"><?php esc_html_e( 'Set it →', 'smart-lead-crm' ); ?></a></div></div>
			<?php endif; ?>
		</div>

		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-external"></span> <?php esc_html_e( 'Quick Links', 'smart-lead-crm' ); ?></h3></div>
			<div style="display:flex;flex-direction:column;gap:10px;">
				<a href="https://developers.facebook.com/apps" target="_blank" class="slcrm-btn slcrm-btn-outline"><span class="dashicons dashicons-external-alt"></span> <?php esc_html_e( 'Meta for Developers — Your Apps', 'smart-lead-crm' ); ?></a>
				<a href="https://business.facebook.com/settings/whatsapp" target="_blank" class="slcrm-btn slcrm-btn-outline"><span class="dashicons dashicons-external-alt"></span> <?php esc_html_e( 'Meta Business Manager — WhatsApp Settings', 'smart-lead-crm' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" class="slcrm-btn slcrm-btn-primary"><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'CRM Settings', 'smart-lead-crm' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads' ) ); ?>" class="slcrm-btn slcrm-btn-outline"><span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'View All Leads', 'smart-lead-crm' ); ?></a>
			</div>
		</div>
	</div>
</div>
