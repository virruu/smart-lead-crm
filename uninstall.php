<?php
/**
 * Uninstall Smart Lead CRM.
 *
 * Removes all plugin data: tables and options.
 *
 * @package SmartLeadCRM
 */

// Exit if accessed directly or not uninstalling.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove database tables.
$tables = array(
	$wpdb->prefix . 'slcrm_leads',
	$wpdb->prefix . 'slcrm_tracking',
	$wpdb->prefix . 'slcrm_bookings',
	$wpdb->prefix . 'slcrm_notes',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Remove plugin options.
$options = array(
	'smart_lead_crm_business_name',
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

// Clear scheduled events.
	wp_clear_scheduled_hook( 'smart_lead_crm_daily_maintenance' );
