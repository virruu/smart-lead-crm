<?php
/**
 * Uninstall Smart Lead CRM.
 *
 * Removes all plugin data: tables and options.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'slcrm_leads',
	$wpdb->prefix . 'slcrm_tracking',
	$wpdb->prefix . 'slcrm_bookings',
	$wpdb->prefix . 'slcrm_notes',
	$wpdb->prefix . 'slcrm_conversations',
	$wpdb->prefix . 'slcrm_messages',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore
}

$options = array(
	'smart_lead_crm_business_name',
	'smart_lead_crm_whatsapp_business_number',
	'smart_lead_crm_whatsapp_mode',
	'smart_lead_crm_whatsapp_verify_token',
	'smart_lead_crm_whatsapp_access_token',
	'smart_lead_crm_whatsapp_phone_number_id',
	'smart_lead_crm_whatsapp_business_account_id',
	'smart_lead_crm_whatsapp_app_secret',
	'smart_lead_crm_google_ads_conversion_id',
	'smart_lead_crm_google_ads_label',
	'smart_lead_crm_ga4_measurement_id',
	'smart_lead_crm_cookie_duration',
	'smart_lead_crm_capture_gclid',
	'smart_lead_crm_capture_utm',
	'smart_lead_crm_enable_debug',
	'smart_lead_crm_db_version',
	'smart_lead_crm_install_date',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

wp_clear_scheduled_hook( 'smart_lead_crm_daily_maintenance' );
