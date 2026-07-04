<?php
/**
 * Export class - Google Ads offline conversion and Customer Match CSV exports.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export class.
 *
 * @package SmartLeadCRM
 */
class Smart_Lead_CRM_Export {

	/**
	 * Database instance.
	 *
	 * @var Smart_Lead_CRM_DB
	 */
	protected $db;

	/**
	 * Helper instance.
	 *
	 * @var Smart_Lead_CRM_Helper
	 */
	protected $helper;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$plugin       = smart_lead_crm();
		$this->db     = $plugin->db;
		$this->helper = $plugin->helper;
	}

	/**
	 * Export Google Ads Offline Conversions CSV.
	 *
	 * Format: Google Ads Conversion ID, Label, Conversion Time, Conversion Value,
	 * Currency Code, GCLID, GBRAID, WBRAID, Order ID.
	 *
	 * @return array Result with CSV content or error.
	 */
	public function export_offline_conversions() {
		global $wpdb;

		$leads_table    = $this->db->table( 'leads' );
		$tracking_table = $this->db->table( 'tracking' );
		$bookings_table = $this->db->table( 'bookings' );

		// Get booked leads with tracking and booking data.
		// Attribution fields now live directly on the lead row, so we no longer
		// need to join the tracking table — every booked lead carries its own
		// gclid/gbraid/wbraid/utm values for the offline conversion upload.
		$query = $wpdb->prepare(
			"SELECT l.id, l.name, l.phone, l.created_at, l.campaign, l.keyword, l.ad_group,
				l.gclid, l.gbraid, l.wbraid,
				l.utm_source, l.utm_medium, l.utm_campaign, l.utm_term, l.utm_content,
				b.fare, b.booking_date, b.route, b.booking_type
			FROM {$leads_table} l
			LEFT JOIN {$bookings_table} b ON b.lead_id = l.id
			WHERE l.status = %s AND b.status = %s
			ORDER BY l.created_at DESC",
			'booked',
			'booked'
		);

		$results = $wpdb->get_results( $query );

		if ( empty( $results ) ) {
			return array(
				'success' => false,
				'message' => __( 'No booked leads with tracking data found to export.', 'smart-lead-crm' ),
			);
		}

		$conversion_id = slcrm_get_setting( 'google_ads_conversion_id', '' );
		$label         = slcrm_get_setting( 'google_ads_label', '' );

		$rows   = array();
		$rows[] = array(
			'Google Ads Conversion ID',
			'Conversion Label',
			'Conversion Name',
			'Conversion Time',
			'Conversion Value',
			'Currency Code',
			'GCLID',
			'GBRAID',
			'WBRAID',
			'Order ID',
		);

		foreach ( $results as $row ) {
			// Skip if no GCLID/GBRAID/WBRAID at all.
			if ( empty( $row->gclid ) && empty( $row->gbraid ) && empty( $row->wbraid ) ) {
				continue;
			}

			$conversion_time = ! empty( $row->booking_date ) ? $row->booking_date . ' 00:00:00' : $row->created_at;
			$conversion_value = ! empty( $row->fare ) ? $row->fare : 0;
			$order_id         = 'SLCRM-' . $row->id;
			$conversion_name  = ! empty( $row->booking_type ) ? $this->helper->get_booking_type_label( $row->booking_type ) : 'Booking';

			$rows[] = array(
				$conversion_id,
				$label,
				$conversion_name,
				$conversion_time,
				$conversion_value,
				'INR',
				$row->gclid,
				$row->gbraid,
				$row->wbraid,
				$order_id,
			);
		}

		// Remove header if no data rows.
		if ( count( $rows ) <= 1 ) {
			return array(
				'success' => false,
				'message' => __( 'No leads with GCLID/GBRAID/WBRAID data found. Only Google Ads tracked leads can be exported as offline conversions.', 'smart-lead-crm' ),
			);
		}

		$csv = $this->array_to_csv( $rows );

		return array(
			'success'  => true,
			'csv'      => $csv,
			'filename' => 'offline-conversions-' . date( 'Y-m-d' ) . '.csv',
			'count'    => count( $rows ) - 1,
		);
	}

	/**
	 * Export Customer Match CSV for Google Ads audience targeting.
	 *
	 * Format: Email, Phone, First Name, Last Name, Country, Zip Code.
	 * Uses hashed email/phone per Google Customer Match requirements.
	 *
	 * @return array Result with CSV content or error.
	 */
	public function export_customer_match() {
		global $wpdb;

		$leads_table = $this->db->table( 'leads' );

		// Get all leads with email or phone.
		$query = $wpdb->prepare(
			"SELECT name, phone, email FROM {$leads_table} WHERE status = %s ORDER BY created_at DESC",
			'booked'
		);

		$results = $wpdb->get_results( $query );

		if ( empty( $results ) ) {
			return array(
				'success' => false,
				'message' => __( 'No booked leads found to export.', 'smart-lead-crm' ),
			);
		}

		$rows   = array();
		$rows[] = array( 'Email', 'Phone', 'First Name', 'Last Name', 'Country', 'Zip Code' );

		foreach ( $results as $row ) {
			$email      = ! empty( $row->email ) ? trim( strtolower( $row->email ) ) : '';
			$phone      = ! empty( $row->phone ) ? $this->normalize_phone( $row->phone ) : '';
			$first_name = '';
			$last_name  = '';

			if ( ! empty( $row->name ) ) {
				$parts = explode( ' ', $row->name, 2 );
				$first_name = $parts[0];
				$last_name  = isset( $parts[1] ) ? $parts[1] : '';
			}

			// Hash with SHA-256 for Google Customer Match.
			$hashed_email = ! empty( $email ) ? hash( 'sha256', $email ) : '';
			$hashed_phone = ! empty( $phone ) ? hash( 'sha256', $phone ) : '';

			$rows[] = array(
				$hashed_email,
				$hashed_phone,
				$first_name,
				$last_name,
				'IN',
				'',
			);
		}

		$csv = $this->array_to_csv( $rows );

		return array(
			'success'  => true,
			'csv'      => $csv,
			'filename' => 'customer-match-' . date( 'Y-m-d' ) . '.csv',
			'count'    => count( $rows ) - 1,
		);
	}

	/**
	 * Export full attribution CSV — every lead with all stored tracking fields.
	 *
	 * Useful for importing into BI tools, Google Sheets, or building custom
	 * campaign/keyword ROI reports outside WordPress.
	 *
	 * @return array Result with CSV content or error.
	 */
	public function export_full_attribution() {
		global $wpdb;

		$leads_table = $this->db->table( 'leads' );

		$results = $wpdb->get_results(
			"SELECT id, created_at, name, phone, email, status, lead_source, medium,
				campaign, ad_group, keyword, gclid, gbraid, wbraid,
				utm_source, utm_medium, utm_campaign, utm_term, utm_content,
				landing_page, booking_route, device, browser, ip, referer
			FROM {$leads_table}
			ORDER BY created_at DESC"
		);

		if ( empty( $results ) ) {
			return array(
				'success' => false,
				'message' => __( 'No leads found to export.', 'smart-lead-crm' ),
			);
		}

		$rows   = array();
		$rows[] = array(
			'Lead ID', 'Created At', 'Name', 'Phone', 'Email', 'Status',
			'Source', 'Medium', 'Campaign', 'Ad Group', 'Keyword',
			'GCLID', 'GBRAID', 'WBRAID',
			'UTM Source', 'UTM Medium', 'UTM Campaign', 'UTM Term', 'UTM Content',
			'Landing Page', 'Booking Route', 'Device', 'Browser', 'IP', 'Referer',
		);

		foreach ( $results as $row ) {
			$rows[] = array(
				$row->id,
				$row->created_at,
				$row->name,
				$row->phone,
				$row->email,
				$row->status,
				$row->lead_source,
				$row->medium,
				$row->campaign,
				$row->ad_group,
				$row->keyword,
				$row->gclid,
				$row->gbraid,
				$row->wbraid,
				$row->utm_source,
				$row->utm_medium,
				$row->utm_campaign,
				$row->utm_term,
				$row->utm_content,
				$row->landing_page,
				$row->booking_route,
				$row->device,
				$row->browser,
				$row->ip,
				$row->referer,
			);
		}

		$csv = $this->array_to_csv( $rows );

		return array(
			'success'  => true,
			'csv'      => $csv,
			'filename' => 'full-attribution-' . date( 'Y-m-d' ) . '.csv',
			'count'    => count( $rows ) - 1,
		);
	}

	/**
	 * Normalize a phone number to international format.
	 *
	 * @param string $phone Phone number.
	 * @return string
	 */
	private function normalize_phone( $phone ) {
		$phone = preg_replace( '/[^0-9+]/', '', $phone );
		if ( empty( $phone ) ) {
			return '';
		}
		// If starts with +, keep as is.
		if ( '+' === substr( $phone, 0, 1 ) ) {
			return $phone;
		}
		// If 10 digits starting with 0, replace with +91 (India default).
		if ( 11 === strlen( $phone ) && '0' === substr( $phone, 0, 1 ) ) {
			return '+91' . substr( $phone, 1 );
		}
		// If 10 digits, prepend +91.
		if ( 10 === strlen( $phone ) ) {
			return '+91' . $phone;
		}
		return $phone;
	}

	/**
	 * Convert array to CSV string.
	 *
	 * @param array $rows Array of row arrays.
	 * @return string
	 */
	private function array_to_csv( $rows ) {
		$output = fopen( 'php://temp', 'r+' );
		foreach ( $rows as $row ) {
			fputcsv( $output, $row );
		}
		rewind( $output );
		$csv = stream_get_contents( $output );
		fclose( $output );
		return $csv;
	}

	/**
	 * Stream a CSV download to the browser.
	 *
	 * @param string $csv      CSV content.
	 * @param string $filename Filename.
	 */
	public function download_csv( $csv, $filename ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Content-Length: ' . strlen( $csv ) );
			echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}
	}
}
