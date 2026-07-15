<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings     = smart_lead_crm()->settings;
$mode         = $settings->get_whatsapp_mode();
$mode_label   = $settings->get_whatsapp_mode_label();
$wa_number    = $settings->get( 'whatsapp_business_number' );
$cloud_ready  = $settings->is_cloud_api_configured();
$webhook_url  = rest_url( 'slcrm/v1/webhook' );
$verify_token = $settings->get( 'whatsapp_verify_token' );

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
		'steps'=>array(
			__('Your WhatsApp Business App on your phone continues working normally.','smart-lead-crm'),
			__('Add the webhook URL below to your Meta App under WhatsApp → Configuration → Webhook.','smart-lead-crm'),
			__('Set the Verify Token to match what you enter in Settings.','smart-lead-crm'),
			__('Every inbound message auto-creates or updates a lead in the CRM.','smart-lead-crm'),
			__('Use "Chat on WhatsApp" from any lead to reply from your phone.','smart-lead-crm'),
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
		'steps'=>array(
			__('Create a Meta App at developers.facebook.com and add the WhatsApp product.','smart-lead-crm'),
			__('Copy your Permanent Access Token and Phone Number ID into Settings.','smart-lead-crm'),
			__('Add the webhook URL and verify token in your Meta App dashboard.','smart-lead-crm'),
			__('Subscribe to the "messages" webhook field.','smart-lead-crm'),
			__('The CRM can now send and receive messages — reply directly from any lead.','smart-lead-crm'),
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
		'steps'=>array(
			__('Verify your WhatsApp Business account is eligible for coexistence in Meta Business Manager.','smart-lead-crm'),
			__('Configure Cloud API credentials in Settings (Access Token + Phone Number ID).','smart-lead-crm'),
			__('Add the webhook URL and verify token in your Meta App dashboard.','smart-lead-crm'),
			__('Both the phone app and CRM can now send/receive — messages sync in both directions.','smart-lead-crm'),
		),
	),
);
$current = $modes[ $mode ];
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title"><span class="dashicons dashicons-whatsapp" style="color:var(--wa-green);"></span> <?php esc_html_e( 'WhatsApp Connection', 'smart-lead-crm' ); ?></h1>
	<p class="slcrm-subtitle"><?php esc_html_e( 'Choose how WhatsApp connects to Smart Lead CRM. You can change this anytime.', 'smart-lead-crm' ); ?></p>

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

	<div class="slcrm-grid slcrm-grid-2" style="margin-top:8px;">
		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-<?php echo esc_attr( $current['icon'] ); ?>" style="color:<?php echo esc_attr( $current['color'] ); ?>;"></span> <?php echo esc_html( $current['title'] ); ?> — <?php esc_html_e( 'Setup', 'smart-lead-crm' ); ?></h3></div>
			<ol style="padding-left:20px;margin:0;">
				<?php foreach ( $current['steps'] as $i => $step ) : ?>
				<li style="padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:14px;line-height:1.6;color:var(--gray-600);"><span style="font-weight:700;color:<?php echo esc_attr( $current['color'] ); ?>;"><?php echo esc_html( $i+1 ); ?>.</span> <?php echo esc_html( $step ); ?></li>
				<?php endforeach; ?>
			</ol>
			<?php if ( $wa_number ) : ?>
			<div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--gray-100);">
				<div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-400);margin-bottom:6px;"><?php esc_html_e( 'Business Number', 'smart-lead-crm' ); ?></div>
				<div style="display:flex;align-items:center;gap:10px;">
					<span style="font-size:18px;font-weight:700;color:var(--gray-800);"><?php echo esc_html( $wa_number ); ?></span>
					<a href="https://wa.me/<?php echo esc_attr( preg_replace('/[^0-9]/','',$wa_number) ); ?>" target="_blank" class="slcrm-btn slcrm-btn-sm" style="background:var(--wa-green);color:#fff;"><span class="dashicons dashicons-whatsapp"></span> <?php esc_html_e( 'Open', 'smart-lead-crm' ); ?></a>
				</div>
			</div>
			<?php endif; ?>
			<?php if ( 'app_mode' === $mode ) : ?>
			<div class="slcrm-banner slcrm-banner-info" style="margin-top:16px;"><span class="dashicons dashicons-info"></span><div><?php esc_html_e( 'App Mode: The CRM captures and stores every inbound customer message as a lead, but does not send replies. Use the "Chat on WhatsApp" button on any lead to reply from your phone.', 'smart-lead-crm' ); ?></div></div>
			<?php elseif ( $cloud_ready ) : ?>
			<div class="slcrm-banner slcrm-banner-success" style="margin-top:16px;"><span class="dashicons dashicons-yes-alt"></span><div><?php esc_html_e( 'Cloud API is connected. You can send and receive messages directly from the CRM lead detail screen.', 'smart-lead-crm' ); ?></div></div>
			<?php else : ?>
			<div class="slcrm-banner slcrm-banner-warning" style="margin-top:16px;"><span class="dashicons dashicons-warning"></span><div><?php esc_html_e( 'Cloud API credentials are not set. Add your Access Token and Phone Number ID in Settings.', 'smart-lead-crm' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" style="font-weight:700;margin-left:6px;"><?php esc_html_e( 'Configure now →', 'smart-lead-crm' ); ?></a></div></div>
			<?php endif; ?>
		</div>

		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Webhook Configuration', 'smart-lead-crm' ); ?></h3></div>
			<p style="font-size:13px;color:var(--gray-500);margin-bottom:16px;"><?php esc_html_e( 'Add these details to your Meta App under WhatsApp → Configuration → Webhook to start receiving messages.', 'smart-lead-crm' ); ?></p>
			<div style="margin-bottom:20px;">
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
			<div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--gray-100);">
				<div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-400);margin-bottom:10px;"><?php esc_html_e( 'Webhook Fields to Subscribe', 'smart-lead-crm' ); ?></div>
				<div style="display:flex;gap:8px;flex-wrap:wrap;">
					<?php foreach ( array( 'messages', 'message_deliveries', 'message_reads', 'messaging_postbacks' ) as $field ) : ?>
					<code style="background:var(--gray-100);padding:4px 10px;border-radius:4px;font-size:12px;"><?php echo esc_html( $field ); ?></code>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="slcrm-quick-actions" style="margin-top:8px;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" class="slcrm-btn slcrm-btn-primary"><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'WhatsApp Settings', 'smart-lead-crm' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads' ) ); ?>" class="slcrm-btn slcrm-btn-outline"><span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'View All Leads', 'smart-lead-crm' ); ?></a>
	</div>
</div>
