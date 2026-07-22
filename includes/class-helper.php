<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Helper {

	public $lead_statuses = array(
		'new_lead'  => 'New Lead',
		'pending'   => 'Pending',
		'contacted' => 'Contacted',
		'booked'    => 'Booked',
		'cancelled' => 'Cancelled',
		'follow-up' => 'Follow-up',
	);

	public $lead_sources = array(
		'google_ads' => 'Google Ads',
		'organic'    => 'Organic',
		'gbp'        => 'Google Business Profile',
		'facebook'   => 'Facebook',
		'instagram'  => 'Instagram',
		'whatsapp'   => 'WhatsApp',
		'referral'   => 'Referral',
		'manual'     => 'Manual',
		'direct'     => 'Direct',
	);

	public $booking_types = array(
		'airport'    => 'Airport Transfer',
		'outstation' => 'Outstation',
		'local'      => 'Local',
		'hourly'     => 'Hourly',
		'railway'    => 'Railway',
		'corporate'  => 'Corporate',
		'tour'       => 'Tour',
	);

	public $business_types = array(
		'taxi'         => 'Taxi / Cab / Car Rental',
		'tours'        => 'Tours / Travel / Tourism',
		'realestate'   => 'Real Estate / Property',
		'clinic'       => 'Clinic / Healthcare',
		'restaurant'   => 'Restaurant / Food',
		'education'    => 'Education / Coaching',
		'salon'        => 'Salon / Spa / Beauty',
		'legal'        => 'Legal / Consulting',
		'ecommerce'    => 'E-commerce / Retail',
		'services'     => 'Professional Services',
		'other'        => 'Other',
	);

	public $conversion_presets = array(
		array( 'crm_action' => 'phone',        'label' => 'Phone Call',          'ga4_event' => 'phone_call',          'default_enabled' => true,  'category' => 'interaction' ),
		array( 'crm_action' => 'whatsapp',    'label' => 'WhatsApp Click',      'ga4_event' => 'whatsapp_click',      'default_enabled' => true,  'category' => 'interaction' ),
		array( 'crm_action' => 'email',       'label' => 'Email Click',         'ga4_event' => 'email_click',         'default_enabled' => false, 'category' => 'interaction' ),
		array( 'crm_action' => 'sms',         'label' => 'SMS Click',           'ga4_event' => 'sms_click',           'default_enabled' => false, 'category' => 'interaction' ),
		array( 'crm_action' => 'directions',  'label' => 'Directions Click',    'ga4_event' => 'directions_click',    'default_enabled' => false, 'category' => 'interaction' ),
		array( 'crm_action' => 'contact_form','label' => 'Contact Form',         'ga4_event' => 'contact_form_submit', 'default_enabled' => true,  'category' => 'form' ),
		array( 'crm_action' => 'booking',     'label' => 'Booking Form',         'ga4_event' => 'booking_submit',      'default_enabled' => false, 'category' => 'form' ),
		array( 'crm_action' => 'tour',        'label' => 'Tour Enquiry',         'ga4_event' => 'tour_enquiry_submit',  'default_enabled' => false, 'category' => 'form' ),
		array( 'crm_action' => 'quote',       'label' => 'Quote Request',        'ga4_event' => 'quote_request_submit', 'default_enabled' => false, 'category' => 'form' ),
		array( 'crm_action' => 'newsletter',  'label' => 'Newsletter Signup',   'ga4_event' => 'newsletter_signup',   'default_enabled' => false, 'category' => 'form' ),
	);

	public function get_lead_statuses()  { return $this->lead_statuses; }
	public function get_lead_sources()   { return $this->lead_sources; }
	public function get_booking_types()  { return $this->booking_types; }
	public function get_business_types() { return $this->business_types; }
	public function get_conversion_presets() { return $this->conversion_presets; }

	public function get_business_type_label( $k ) {
		return $this->business_types[ $k ] ?? ucfirst( $k );
	}

	public function get_conversion_label( $crm_action ) {
		$conversions = smart_lead_crm()->db->get_conversions();
		foreach ( $conversions as $c ) {
			if ( $c->crm_action === $crm_action ) return $c->label;
		}
		$presets = $this->conversion_presets;
		foreach ( $presets as $p ) {
			if ( $p['crm_action'] === $crm_action ) return $p['label'];
		}
		return ucfirst( $crm_action );
	}

	public function get_status_label( $k ) {
		return $this->lead_statuses[ $k ] ?? ucfirst( $k );
	}
	public function get_source_label( $k ) {
		return $this->lead_sources[ $k ] ?? ucfirst( $k );
	}
	public function get_booking_type_label( $k ) {
		return $this->booking_types[ $k ] ?? ucfirst( $k );
	}

	public function format_currency( $a, $s = '₹' ) {
		return $s . number_format( (float) $a, 2 );
	}

	public function format_date( $d, $fmt = 'M j, Y' ) {
		if ( empty( $d ) || str_starts_with( $d, '0000' ) ) return '—';
		return date_i18n( $fmt, strtotime( $d ) );
	}

	public function get_client_ip() {
		foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $k ) {
			if ( ! empty( $_SERVER[ $k ] ) ) {
				$ip = explode( ',', wp_unslash( $_SERVER[ $k ] ) );
				return sanitize_text_field( trim( $ip[0] ) );
			}
		}
		return '';
	}

	public function log( $msg ) {
		if ( 'yes' === slcrm_get_setting( 'enable_debug', 'no' ) && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[SmartLeadCRM] ' . $msg );
		}
	}
}
