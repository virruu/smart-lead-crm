<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function smart_lead_crm()              { return Smart_Lead_CRM::instance(); }
function slcrm_db()                    { return smart_lead_crm()->db; }
function slcrm_helper()                { return smart_lead_crm()->helper; }
function slcrm_get_setting( $k, $d = null ) { return smart_lead_crm()->settings->get( $k, $d ); }
function slcrm_get_lead_statuses()     { return slcrm_helper()->get_lead_statuses(); }
function slcrm_get_lead_sources()      { return slcrm_helper()->get_lead_sources(); }
function slcrm_get_booking_types()     { return slcrm_helper()->get_booking_types(); }
function slcrm_format_currency( $a, $s = '₹' ) { return slcrm_helper()->format_currency( $a, $s ); }
