<?php
/**
 * Settings admin page.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title">
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'Smart Lead CRM Settings', 'smart-lead-crm' ); ?>
	</h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'smart_lead_crm_settings_group' );
		do_settings_sections( 'smart-lead-crm-settings' );
		submit_button( __( 'Save Settings', 'smart-lead-crm' ) );
		?>
	</form>
</div>
