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

	public function get_lead_statuses()  { return $this->lead_statuses; }
	public function get_lead_sources()   { return $this->lead_sources; }
	public function get_booking_types()  { return $this->booking_types; }

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
