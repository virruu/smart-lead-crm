<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$conv_id = slcrm_get_setting( 'google_ads_conversion_id', '' );
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title"><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export', 'smart-lead-crm' ); ?></h1>
	<p class="slcrm-subtitle"><?php esc_html_e( 'Export lead data for Google Ads and audience building.', 'smart-lead-crm' ); ?></p>

	<?php if ( ! $conv_id ) : ?>
	<div class="slcrm-banner slcrm-banner-warning"><span class="dashicons dashicons-warning"></span><div><?php esc_html_e( 'Google Ads Conversion ID is not set. Offline conversion export will not work until you add it in Settings.', 'smart-lead-crm' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" style="font-weight:700;margin-left:6px;"><?php esc_html_e( 'Configure →', 'smart-lead-crm' ); ?></a></div></div>
	<?php endif; ?>

	<div class="slcrm-grid slcrm-grid-2">
		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-megaphone"></span> <?php esc_html_e( 'Google Ads Offline Conversions', 'smart-lead-crm' ); ?></h3></div>
			<p style="font-size:13px;color:var(--gray-500);line-height:1.6;"><?php esc_html_e( 'Export booked leads with GCLID/GBRAID/WBRAID for Google Ads offline conversion tracking. Upload this CSV in Google Ads → Conversions → Uploads.', 'smart-lead-crm' ); ?></p>
			<ol style="font-size:13px;color:var(--gray-600);padding-left:20px;line-height:1.8;">
				<li><?php esc_html_e( 'Download the CSV using the button below.', 'smart-lead-crm' ); ?></li>
				<li><?php esc_html_e( 'Go to Google Ads → Tools → Conversions → Uploads.', 'smart-lead-crm' ); ?></li>
				<li><?php esc_html_e( 'Click "Upload conversions" and select "CSV" format.', 'smart-lead-crm' ); ?></li>
				<li><?php esc_html_e( 'Upload the file and Google Ads will match GCLIDs to clicks.', 'smart-lead-crm' ); ?></li>
			</ol>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=smart-lead-crm-export&slcrm_export=offline' ), 'slcrm_export', 'slcrm_nonce' ) ); ?>" class="slcrm-btn slcrm-btn-primary"><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download CSV', 'smart-lead-crm' ); ?></a>
		</div>

		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Customer Match', 'smart-lead-crm' ); ?></h3></div>
			<p style="font-size:13px;color:var(--gray-500);line-height:1.6;"><?php esc_html_e( 'Export booked customers with SHA-256 hashed phone numbers for Google Ads Customer Match audiences. Phone numbers are normalized to E.164 and hashed per Google\'s spec.', 'smart-lead-crm' ); ?></p>
			<ol style="font-size:13px;color:var(--gray-600);padding-left:20px;line-height:1.8;">
				<li><?php esc_html_e( 'Download the CSV using the button below.', 'smart-lead-crm' ); ?></li>
				<li><?php esc_html_e( 'Go to Google Ads → Tools → Audience Manager → New Audience → Customer List.', 'smart-lead-crm' ); ?></li>
				<li><?php esc_html_e( 'Select "Customer data (phone numbers, emails)" format.', 'smart-lead-crm' ); ?></li>
				<li><?php esc_html_e( 'Upload the CSV — Google will match to known users.', 'smart-lead-crm' ); ?></li>
			</ol>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=smart-lead-crm-export&slcrm_export=customer_match' ), 'slcrm_export', 'slcrm_nonce' ) ); ?>" class="slcrm-btn slcrm-btn-primary"><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download CSV', 'smart-lead-crm' ); ?></a>
		</div>

		<div class="slcrm-card" style="grid-column:span 2;">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-database"></span> <?php esc_html_e( 'Full Attribution Export', 'smart-lead-crm' ); ?></h3></div>
			<p style="font-size:13px;color:var(--gray-500);line-height:1.6;"><?php esc_html_e( 'Export all leads with 25 attribution fields: ID, dates, name, phone, email, status, source, medium, campaign, ad group, keyword, GCLID, GBRAID, WBRAID, all UTMs, landing page, booking route, device, browser, IP, referer. Useful for backup or custom analytics.', 'smart-lead-crm' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=smart-lead-crm-export&slcrm_export=full_attribution' ), 'slcrm_export', 'slcrm_nonce' ) ); ?>" class="slcrm-btn slcrm-btn-outline"><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download Full Attribution CSV', 'smart-lead-crm' ); ?></a>
		</div>
	</div>
</div>
