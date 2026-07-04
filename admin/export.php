<?php
/**
 * Export admin page - Google Ads offline conversions and Customer Match CSV.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$export        = smart_lead_crm()->export;
$conv_id       = slcrm_get_setting( 'google_ads_conversion_id', '' );
$conv_label    = slcrm_get_setting( 'google_ads_label', '' );

// Handle export download.
if ( isset( $_GET['slcrm_export'] ) && isset( $_GET['_wpnonce'] ) ) {
	$export_type = sanitize_text_field( wp_unslash( $_GET['slcrm_export'] ) );
	if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'slcrm_export_' . $export_type ) ) {
		if ( 'offline' === $export_type ) {
			$result = $export->export_offline_conversions();
			if ( $result['success'] ) {
				$export->download_csv( $result['csv'], $result['filename'] );
			}
		} elseif ( 'customer_match' === $export_type ) {
			$result = $export->export_customer_match();
			if ( $result['success'] ) {
				$export->download_csv( $result['csv'], $result['filename'] );
			}
		}
	}
}
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title">
		<span class="dashicons dashicons-download"></span>
		<?php esc_html_e( 'Export', 'smart-lead-crm' ); ?>
	</h1>

	<?php if ( empty( $conv_id ) ) : ?>
		<div class="slcrm-notice slcrm-notice-warning">
			<span class="dashicons dashicons-warning"></span>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: settings URL */
					__( 'Google Ads Conversion ID is not set. <a href="%s">Configure it in Settings</a> before exporting offline conversions.', 'smart-lead-crm' ),
					esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) )
				)
			);
			?>
		</div>
	<?php endif; ?>

	<div class="slcrm-export-grid">
		<div class="slcrm-card slcrm-export-card">
			<h2><?php esc_html_e( 'Google Ads Offline Conversions', 'smart-lead-crm' ); ?></h2>
			<p><?php esc_html_e( 'Export booked leads with GCLID/GBRAID/WBRAID data as a Google-ready CSV for importing offline conversions into Google Ads.', 'smart-lead-crm' ); ?></p>
			<p class="slcrm-export-meta">
				<strong><?php esc_html_e( 'Conversion ID:', 'smart-lead-crm' ); ?></strong> <?php echo esc_html( $conv_id ? $conv_id : '—' ); ?><br />
				<strong><?php esc_html_e( 'Label:', 'smart-lead-crm' ); ?></strong> <?php echo esc_html( $conv_label ? $conv_label : '—' ); ?>
			</p>
			<p class="description"><?php esc_html_e( 'Only leads with GCLID, GBRAID, or WBRAID tracking data will be included. These are leads that came from Google Ads.', 'smart-lead-crm' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=smart-lead-crm-export&slcrm_export=offline' ), 'slcrm_export_offline' ) ); ?>" class="button button-primary">
				<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export Offline Conversions', 'smart-lead-crm' ); ?>
			</a>
		</div>

		<div class="slcrm-card slcrm-export-card">
			<h2><?php esc_html_e( 'Customer Match Audience', 'smart-lead-crm' ); ?></h2>
			<p><?php esc_html_e( 'Export booked customer data as a Google-ready CSV with SHA-256 hashed emails and phone numbers for Customer Match audience targeting.', 'smart-lead-crm' ); ?></p>
			<p class="description"><?php esc_html_e( 'Phone numbers are normalized to international format (+91 for India) and hashed with SHA-256 per Google Customer Match requirements.', 'smart-lead-crm' ); ?></p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=smart-lead-crm-export&slcrm_export=customer_match' ), 'slcrm_export_customer_match' ) ); ?>" class="button button-primary">
				<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export Customer Match', 'smart-lead-crm' ); ?>
			</a>
		</div>
	</div>

	<div class="slcrm-card">
		<h2><?php esc_html_e( 'How to Use These Exports', 'smart-lead-crm' ); ?></h2>
		<h3><?php esc_html_e( 'Offline Conversions', 'smart-lead-crm' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Click "Export Offline Conversions" to download the CSV.', 'smart-lead-crm' ); ?></li>
			<li><?php esc_html_e( 'Go to Google Ads > Tools > Conversions > Uploads.', 'smart-lead-crm' ); ?></li>
			<li><?php esc_html_e( 'Click "Upload conversions" and select the downloaded CSV file.', 'smart-lead-crm' ); ?></li>
			<li><?php esc_html_e( 'Google will match the GCLID/GBRAID/WBRAID to the original ad click and attribute the conversion.', 'smart-lead-crm' ); ?></li>
		</ol>
		<h3><?php esc_html_e( 'Customer Match', 'smart-lead-crm' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Click "Export Customer Match" to download the CSV.', 'smart-lead-crm' ); ?></li>
			<li><?php esc_html_e( 'Go to Google Ads > Tools > Audience Manager > New Audience > Customer list.', 'smart-lead-crm' ); ?></li>
			<li><?php esc_html_e( 'Upload the CSV file with hashed data.', 'smart-lead-crm' ); ?></li>
			<li><?php esc_html_e( 'Google will create a remarketing audience from your customer data.', 'smart-lead-crm' ); ?></li>
		</ol>
	</div>
</div>
