<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Export {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_handle_export' ) );
	}

	public function maybe_handle_export() {
		if ( ! isset( $_GET['slcrm_export'] ) ) return;
		if ( ! current_user_can( 'manage_options' ) ) return;
		check_admin_referer( 'slcrm_export', 'slcrm_nonce' );

		$type = sanitize_text_field( wp_unslash( $_GET['slcrm_export'] ) );

		switch ( $type ) {
			case 'offline':
				$this->export_offline_conversions();
				break;
			case 'customer_match':
				$this->export_customer_match();
				break;
			case 'full_attribution':
				$this->export_full_attribution();
				break;
		}
	}

	public function export_offline_conversions() {
		global $wpdb;
		$lt = $wpdb->prefix . 'slcrm_leads';
		$bt = $wpdb->prefix . 'slcrm_bookings';

		$rows = $wpdb->get_results(
			"SELECT l.id, l.created_at, l.gclid, l.gbraid, l.wbraid, b.fare, b.route
			 FROM $lt l
			 INNER JOIN $bt b ON l.id = b.lead_id
			 WHERE b.status IN ('booked','completed')
			 AND (l.gclid != '' OR l.gbraid != '' OR l.wbraid != '')
			 ORDER BY l.created_at DESC"
		);

		$conv_id  = slcrm_get_setting( 'google_ads_conversion_id', '' );
		$conv_lbl = slcrm_get_setting( 'google_ads_label', '' );

		$csv_rows = array();
		$csv_rows[] = array( 'Conversion ID', 'Conversion Label', 'Conversion Name', 'Conversion Time', 'Conversion Value', 'Currency', 'GCLID', 'GBRAID', 'WBRAID', 'Order ID' );

		foreach ( $rows as $r ) {
			$csv_rows[] = array(
				$conv_id,
				$conv_lbl,
				'Booking #' . $r->id,
				$r->created_at,
				$r->fare,
				'INR',
				$r->gclid,
				$r->gbraid,
				$r->wbraid,
				'SLCRM-' . $r->id,
			);
		}

		$this->download_csv( $this->array_to_csv( $csv_rows ), 'offline-conversions.csv' );
	}

	public function export_customer_match() {
		global $wpdb;
		$lt = $wpdb->prefix . 'slcrm_leads';

		$rows = $wpdb->get_results(
			"SELECT DISTINCT name, phone, email FROM $lt WHERE status = 'booked' AND phone != '' ORDER BY created_at DESC"
		);

		$csv_rows = array();
		$csv_rows[] = array( 'Email', 'Phone', 'First Name', 'Last Name', 'Country', 'Zip Code' );

		$cc = slcrm_get_setting( 'whatsapp_default_country_code', '91' );

		foreach ( $rows as $r ) {
			$phone = $this->normalize_phone( $r->phone, $cc );
			$email = $r->email ? hash( 'sha256', strtolower( trim( $r->email ) ) ) : '';
			$phone_hash = hash( 'sha256', $phone );
			$parts = explode( ' ', $r->name, 2 );
			$csv_rows[] = array(
				$email,
				$phone_hash,
				$parts[0] ?? '',
				$parts[1] ?? '',
				'IN',
				'',
			);
		}

		$this->download_csv( $this->array_to_csv( $csv_rows ), 'customer-match.csv' );
	}

	public function export_full_attribution() {
		global $wpdb;
		$lt = $wpdb->prefix . 'slcrm_leads';

		$rows = $wpdb->get_results( "SELECT * FROM $lt ORDER BY created_at DESC" );

		$csv_rows = array();
		$csv_rows[] = array( 'ID', 'Created At', 'Name', 'Phone', 'Email', 'Status', 'Source', 'Medium', 'Campaign', 'Ad Group', 'Keyword', 'GCLID', 'GBRAID', 'WBRAID', 'UTM Source', 'UTM Campaign', 'UTM Medium', 'UTM Term', 'UTM Content', 'Landing Page', 'Booking Route', 'Device', 'Browser', 'IP', 'Referer' );

		foreach ( $rows as $r ) {
			$csv_rows[] = array(
				$r->id, $r->created_at, $r->name, $r->phone, $r->email,
				$r->status, $r->lead_source, $r->medium, $r->campaign, $r->ad_group, $r->keyword,
				$r->gclid, $r->gbraid, $r->wbraid,
				$r->utm_source, $r->utm_campaign, $r->utm_medium, $r->utm_term, $r->utm_content,
				$r->landing_page, $r->booking_route, $r->device, $r->browser, $r->ip_address, $r->referer,
			);
		}

		$this->download_csv( $this->array_to_csv( $csv_rows ), 'full-attribution.csv' );
	}

	private function normalize_phone( $phone, $cc = '91' ) {
		$digits = preg_replace( '/[^0-9]/', '', $phone );
		if ( str_starts_with( $digits, '00' ) ) $digits = substr( $digits, 2 );
		if ( strlen( $digits ) === 10 ) $digits = $cc . $digits;
		elseif ( str_starts_with( $digits, '0' ) && strlen( $digits ) > 10 ) $digits = $cc . substr( $digits, 1 );
		return '+' . $digits;
	}

	private function array_to_csv( $rows ) {
		$fp = fopen( 'php://temp', 'r+' );
		foreach ( $rows as $row ) {
			fputcsv( $fp, $row );
		}
		rewind( $fp );
		$csv = stream_get_contents( $fp );
		fclose( $fp );
		return $csv;
	}

	private function download_csv( $csv, $filename ) {
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $csv ) );
		echo $csv;
		exit;
	}
}
