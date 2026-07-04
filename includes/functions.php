<?php
/**
 * Helper functions for Smart Lead CRM.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the database helper.
 *
 * @return Smart_Lead_CRM_DB
 */
function slcrm_db() {
	return smart_lead_crm()->db;
}

/**
 * Get the helper.
 *
 * @return Smart_Lead_CRM_Helper
 */
function slcrm_helper() {
	return smart_lead_crm()->helper;
}

/**
 * Get a setting value.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function slcrm_get_setting( $key, $default = null ) {
	return smart_lead_crm()->settings->get( $key, $default );
}

/**
 * Get lead statuses.
 *
 * @return array
 */
function slcrm_get_lead_statuses() {
	return slcrm_helper()->get_lead_statuses();
}

/**
 * Get lead sources.
 *
 * @return array
 */
function slcrm_get_lead_sources() {
	return slcrm_helper()->get_lead_sources();
}

/**
 * Get booking types.
 *
 * @return array
 */
function slcrm_get_booking_types() {
	return slcrm_helper()->get_booking_types();
}

/**
 * Format currency.
 *
 * @param float  $amount Amount.
 * @param string $symbol Currency symbol.
 * @return string
 */
function slcrm_format_currency( $amount, $symbol = '₹' ) {
	return slcrm_helper()->format_currency( $amount, $symbol );
}
