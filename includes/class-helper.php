<?php
/**
 * Helper class - utility functions.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class.
 *
 * @package SmartLeadCRM
 */
class Smart_Lead_CRM_Helper {

	/**
	 * Lead statuses.
	 *
	 * @var array
	 */
	private $lead_statuses = array(
		'new_lead'   => 'New Lead',
		'pending'    => 'Pending',
		'contacted'  => 'Contacted',
		'booked'     => 'Booked',
		'cancelled'  => 'Cancelled',
		'follow-up'  => 'Follow-up',
	);

	/**
	 * Lead sources.
	 *
	 * @var array
	 */
	private $lead_sources = array(
		'google_ads'    => 'Google Ads',
		'organic'       => 'Organic',
		'gbp'           => 'Google Business Profile',
		'facebook'      => 'Facebook',
		'instagram'     => 'Instagram',
		'whatsapp'      => 'WhatsApp Direct',
		'referral'      => 'Referral',
		'manual'        => 'Manual',
	);

	/**
	 * Booking types.
	 *
	 * @var array
	 */
	private $booking_types = array(
		'airport'    => 'Airport',
		'outstation' => 'Outstation',
		'local'      => 'Local',
		'hourly'     => 'Hourly Rental',
		'railway'    => 'Railway',
		'corporate'  => 'Corporate',
		'tour'       => 'Tour',
	);

	/**
	 * Get lead statuses.
	 *
	 * @return array
	 */
	public function get_lead_statuses() {
		return $this->lead_statuses;
	}

	/**
	 * Get lead sources.
	 *
	 * @return array
	 */
	public function get_lead_sources() {
		return $this->lead_sources;
	}

	/**
	 * Get booking types.
	 *
	 * @return array
	 */
	public function get_booking_types() {
		return $this->booking_types;
	}

	/**
	 * Format currency.
	 *
	 * @param float  $amount Amount.
	 * @param string $symbol Currency symbol.
	 * @return string
	 */
	public function format_currency( $amount, $symbol = '₹' ) {
		return $symbol . number_format( (float) $amount, 2, '.', ',' );
	}

	/**
	 * Format a date.
	 *
	 * @param string $date   Date string.
	 * @param string $format Date format.
	 * @return string
	 */
	public function format_date( $date, $format = 'M j, Y' ) {
		if ( empty( $date ) || '0000-00-00' === $date || '0000-00-00 00:00:00' === $date ) {
			return '—';
		}
		$timestamp = strtotime( $date );
		return $timestamp ? date( $format, $timestamp ) : '—';
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	public function get_client_ip() {
		$ip = '';
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$ip = explode( ',', $ip );
			$ip = trim( $ip[0] );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return $ip;
	}

	/**
	 * Log a debug message if debug mode is enabled.
	 *
	 * @param string $message Message to log.
	 */
	public function log( $message ) {
		$settings = smart_lead_crm()->settings;
		if ( 'yes' !== $settings->get( 'enable_debug', 'no' ) ) {
			return;
		}
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}
		if ( is_array( $message ) || is_object( $message ) ) {
			$message = wp_json_encode( $message );
		}
		error_log( '[Smart Lead CRM] ' . $message );
	}

	/**
	 * Get a human-readable label for a status key.
	 *
	 * @param string $key Status key.
	 * @return string
	 */
	public function get_status_label( $key ) {
		return isset( $this->lead_statuses[ $key ] ) ? $this->lead_statuses[ $key ] : ucfirst( $key );
	}

	/**
	 * Get a human-readable label for a source key.
	 *
	 * @param string $key Source key.
	 * @return string
	 */
	public function get_source_label( $key ) {
		return isset( $this->lead_sources[ $key ] ) ? $this->lead_sources[ $key ] : ucfirst( $key );
	}

	/**
	 * Get a human-readable label for a booking type key.
	 *
	 * @param string $key Booking type key.
	 * @return string
	 */
	public function get_booking_type_label( $key ) {
		return isset( $this->booking_types[ $key ] ) ? $this->booking_types[ $key ] : ucfirst( $key );
	}
}
