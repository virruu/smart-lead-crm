<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_DB {

	public $wpdb;
	public $tables = array();

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->tables = array(
			'leads'         => $wpdb->prefix . 'slcrm_leads',
			'tracking'      => $wpdb->prefix . 'slcrm_tracking',
			'bookings'      => $wpdb->prefix . 'slcrm_bookings',
			'notes'         => $wpdb->prefix . 'slcrm_notes',
			'conversations' => $wpdb->prefix . 'slcrm_conversations',
			'messages'      => $wpdb->prefix . 'slcrm_messages',
			'conversions'   => $wpdb->prefix . 'slcrm_conversions',
			'forms'         => $wpdb->prefix . 'slcrm_form_tracking',
		);
	}

	/* ── Lead CRUD ─────────────────────────────────────────── */

	public function insert_lead( $data ) {
		$format = $this->get_lead_format( $data );
		$result = $this->wpdb->insert( $this->tables['leads'], $data, $format );
		return $result ? (int) $this->wpdb->insert_id : false;
	}

	public function update_lead( $id, $data ) {
		$format = $this->get_lead_format( $data );
		$result = $this->wpdb->update( $this->tables['leads'], $data, array( 'id' => $id ), $format, array( '%d' ) );
		if ( $result ) {
			$this->wpdb->update( $this->tables['leads'], array( 'last_updated' => current_time( 'mysql' ) ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
		}
		return (bool) $result;
	}

	public function delete_lead( $id ) {
		return (bool) $this->wpdb->delete( $this->tables['leads'], array( 'id' => $id ), array( '%d' ) );
	}

	public function delete_lead_cascade( $id ) {
		$this->wpdb->delete( $this->tables['tracking'], array( 'lead_id' => $id ), array( '%d' ) );
		$this->wpdb->delete( $this->tables['bookings'], array( 'lead_id' => $id ), array( '%d' ) );
		$this->wpdb->delete( $this->tables['notes'],    array( 'lead_id' => $id ), array( '%d' ) );
		$conv_ids = $this->wpdb->get_col( $this->wpdb->prepare(
			"SELECT id FROM {$this->tables['conversations']} WHERE lead_id = %d", $id
		) );
		if ( $conv_ids ) {
			foreach ( $conv_ids as $cid ) {
				$this->wpdb->delete( $this->tables['messages'], array( 'conversation_id' => $cid ), array( '%d' ) );
			}
		}
		$this->wpdb->delete( $this->tables['conversations'], array( 'lead_id' => $id ), array( '%d' ) );
		$this->delete_lead( $id );
	}

	public function get_lead( $id ) {
		return $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['leads']} WHERE id = %d", $id
		) );
	}

	public function get_leads( $args = array() ) {
		$defaults = array(
			'number'  => 25,
			'offset'  => 0,
			'orderby' => 'last_updated',
			'order'   => 'DESC',
			'status'  => '',
			'source'  => '',
			'search'  => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$allowed = array( 'last_updated', 'created_at', 'name', 'phone', 'status', 'lead_source' );
		$orderby = in_array( $args['orderby'], $allowed, true ) ? $args['orderby'] : 'last_updated';
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$where  = ' WHERE 1=1';
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		}
		if ( ! empty( $args['source'] ) ) {
			$where   .= ' AND lead_source = %s';
			$params[] = $args['source'];
		}
		if ( ! empty( $args['search'] ) ) {
			$s        = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$where    .= ' AND (phone LIKE %s OR name LIKE %s OR booking_route LIKE %s OR campaign LIKE %s)';
			$params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
		}

		$limit  = ' LIMIT %d OFFSET %d';
		$params[] = (int) $args['number'];
		$params[] = (int) $args['offset'];

		$sql = "SELECT * FROM {$this->tables['leads']}{$where} ORDER BY $orderby $order $limit";
		return $this->wpdb->get_results( $this->wpdb->prepare( $sql, $params ) );
	}

	public function count_leads( $args = array() ) {
		$defaults = array( 'status' => '', 'source' => '', 'search' => '' );
		$args = wp_parse_args( $args, $defaults );

		$where  = ' WHERE 1=1';
		$params = array();
		if ( ! empty( $args['status'] ) ) {
			$where   .= ' AND status = %s';
			$params[] = $args['status'];
		}
		if ( ! empty( $args['source'] ) ) {
			$where   .= ' AND lead_source = %s';
			$params[] = $args['source'];
		}
		if ( ! empty( $args['search'] ) ) {
			$s        = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$where    .= ' AND (phone LIKE %s OR name LIKE %s OR booking_route LIKE %s OR campaign LIKE %s)';
			$params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
		}
		$sql = "SELECT COUNT(*) FROM {$this->tables['leads']}{$where}";
		if ( ! empty( $params ) ) $sql = $this->wpdb->prepare( $sql, $params );
		return (int) $this->wpdb->get_var( $sql );
	}

	public function find_lead_by_phone( $phone ) {
		return $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['leads']} WHERE phone = %s ORDER BY created_at DESC LIMIT 1", $phone
		) );
	}

	public function find_lead_by_phone_partial( $phone ) {
		$digits = preg_replace( '/[^0-9]/', '', $phone );
		if ( strlen( $digits ) < 10 ) return null;
		$last10 = substr( $digits, -10 );
		return $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['leads']} WHERE phone LIKE %s ORDER BY created_at DESC LIMIT 1",
			'%' . $this->wpdb->esc_like( $last10 )
		) );
	}

	public function find_lead_by_visitor( $visitor_id ) {
		return $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['leads']} WHERE visitor_id = %s ORDER BY created_at DESC LIMIT 1", $visitor_id
		) );
	}

	public function get_lead_by_phone( $phone ) {
		global $wpdb;
		$phone = preg_replace( '/[^0-9]/', '', $phone );
		if ( strlen( $phone ) < 8 ) return null;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->tables['leads']} WHERE REPLACE(REPLACE(phone,' ',''),'+','') = %s ORDER BY id DESC LIMIT 1",
			$phone
		) );
	}

	/* ── Conversions ────────────────────────────────────────── */

	public function get_conversions() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$this->tables['conversions']} ORDER BY sort_order ASC, id ASC" );
	}

	public function get_enabled_conversions() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$this->tables['conversions']} WHERE enabled = 1 ORDER BY sort_order ASC, id ASC" );
	}

	public function get_conversion( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tables['conversions']} WHERE id = %d", $id ) );
	}

	public function insert_conversion( $data ) {
		global $wpdb;
		$wpdb->insert( $this->tables['conversions'], $data );
		return (int) $wpdb->insert_id;
	}

	public function update_conversion( $id, $data ) {
		global $wpdb;
		return $wpdb->update( $this->tables['conversions'], $data, array( 'id' => $id ) );
	}

	public function delete_conversion( $id ) {
		global $wpdb;
		return $wpdb->delete( $this->tables['conversions'], array( 'id' => $id ) );
	}

	/* ── Form Tracking ───────────────────────────────────────── */

	public function get_form_trackings() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$this->tables['forms']} ORDER BY sort_order ASC, id ASC" );
	}

	public function get_enabled_forms() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$this->tables['forms']} WHERE enabled = 1 ORDER BY sort_order ASC, id ASC" );
	}

	public function insert_form_tracking( $data ) {
		global $wpdb;
		$wpdb->insert( $this->tables['forms'], $data );
		return (int) $wpdb->insert_id;
	}

	public function update_form_tracking( $id, $data ) {
		global $wpdb;
		return $wpdb->update( $this->tables['forms'], $data, array( 'id' => $id ) );
	}

	public function delete_form_tracking( $id ) {
		global $wpdb;
		return $wpdb->delete( $this->tables['forms'], array( 'id' => $id ) );
	}

	public function find_lead_by_visitor_today( $visitor_id ) {
		return $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['leads']} WHERE visitor_id = %s AND DATE(created_at) = %s ORDER BY created_at DESC LIMIT 1",
			$visitor_id, current_time( 'Y-m-d' )
		) );
	}

	/* ── Tracking ──────────────────────────────────────────── */

	public function insert_tracking( $data ) {
		$format = $this->get_tracking_format( $data );
		$result = $this->wpdb->insert( $this->tables['tracking'], $data, $format );
		return $result ? (int) $this->wpdb->insert_id : false;
	}

	public function get_tracking( $lead_id ) {
		return $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['tracking']} WHERE lead_id = %d ORDER BY visit_time ASC", $lead_id
		) );
	}

	/* ── Bookings ──────────────────────────────────────────── */

	public function insert_booking( $data ) {
		$format = $this->get_booking_format( $data );
		$result = $this->wpdb->insert( $this->tables['bookings'], $data, $format );
		return $result ? (int) $this->wpdb->insert_id : false;
	}

	public function update_booking( $id, $data ) {
		$format = $this->get_booking_format( $data );
		return (bool) $this->wpdb->update( $this->tables['bookings'], $data, array( 'id' => $id ), $format, array( '%d' ) );
	}

	public function delete_booking( $id ) {
		return (bool) $this->wpdb->delete( $this->tables['bookings'], array( 'id' => $id ), array( '%d' ) );
	}

	public function get_booking( $id ) {
		return $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['bookings']} WHERE id = %d", $id
		) );
	}

	public function get_bookings( $lead_id ) {
		return $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['bookings']} WHERE lead_id = %d ORDER BY created_at DESC", $lead_id
		) );
	}

	/* ── Notes ─────────────────────────────────────────────── */

	public function insert_note( $data ) {
		$defaults = array(
			'lead_id'    => 0,
			'note'       => '',
			'author_id'  => get_current_user_id(),
			'created_at' => current_time( 'mysql' ),
		);
		$data = wp_parse_args( $data, $defaults );
		$result = $this->wpdb->insert( $this->tables['notes'], $data, array( '%d', '%s', '%d', '%s' ) );
		return $result ? (int) $this->wpdb->insert_id : false;
	}

	public function get_notes( $lead_id ) {
		return $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['notes']} WHERE lead_id = %d ORDER BY created_at DESC", $lead_id
		) );
	}

	/* ── Dashboard Stats ───────────────────────────────────── */

	public function get_dashboard_stats() {
		$today = current_time( 'Y-m-d' );

		$today_leads = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tables['leads']} WHERE DATE(created_at) = %s", $today
		) );

		$today_bookings = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tables['bookings']} WHERE DATE(created_at) = %s", $today
		) );

		$revenue = (float) $this->wpdb->get_var(
			"SELECT COALESCE(SUM(fare),0) FROM {$this->tables['bookings']} WHERE status IN ('booked','completed')"
		);

		$total_leads   = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->tables['leads']}" );
		$booked_count  = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->tables['leads']} WHERE status = 'booked'" );
		$conv_pct      = $total_leads > 0 ? round( ( $booked_count / $total_leads ) * 100, 1 ) : 0;

		$avg_fare = (float) $this->wpdb->get_var(
			"SELECT COALESCE(AVG(fare),0) FROM {$this->tables['bookings']} WHERE status IN ('booked','completed')"
		);

		$repeat = (int) $this->wpdb->get_var(
			"SELECT COUNT(*) FROM (SELECT phone, COUNT(*) c FROM {$this->tables['leads']} WHERE phone != '' GROUP BY phone HAVING c > 1) t"
		);

		return array(
			'today_leads'    => $today_leads,
			'today_bookings' => $today_bookings,
			'revenue'        => $revenue,
			'conversion'     => $conv_pct,
			'avg_fare'       => $avg_fare,
			'repeat_customers' => $repeat,
		);
	}

	/* ── Report Data ───────────────────────────────────────── */

	public function get_report_data( $start, $end ) {
		$lt = $this->tables['leads'];
		$bt = $this->tables['bookings'];

		$total_leads = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM $lt WHERE created_at BETWEEN %s AND %s", $start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$total_bookings = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM $bt WHERE created_at BETWEEN %s AND %s", $start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$revenue = (float) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COALESCE(SUM(b.fare),0) FROM $bt b INNER JOIN $lt l ON b.lead_id = l.id
			 WHERE b.created_at BETWEEN %s AND %s AND b.status IN ('booked','completed')",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$booked_count = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM $lt WHERE status = 'booked' AND created_at BETWEEN %s AND %s",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );
		$conv_pct = $total_leads > 0 ? round( ( $booked_count / $total_leads ) * 100, 1 ) : 0;

		$avg_fare = (float) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COALESCE(AVG(b.fare),0) FROM $bt b INNER JOIN $lt l ON b.lead_id = l.id
			 WHERE b.created_at BETWEEN %s AND %s AND b.status IN ('booked','completed')",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$repeat = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM (SELECT phone, COUNT(*) c FROM $lt
			 WHERE phone != '' AND created_at BETWEEN %s AND %s GROUP BY phone HAVING c > 1) t",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$top_routes = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT b.route, COUNT(*) as bookings, COALESCE(SUM(b.fare),0) as revenue
			 FROM $bt b WHERE b.route != '' AND b.created_at BETWEEN %s AND %s
			 GROUP BY b.route ORDER BY bookings DESC LIMIT 10",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$top_campaigns = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT campaign, COUNT(*) as count FROM $lt
			 WHERE campaign != '' AND created_at BETWEEN %s AND %s
			 GROUP BY campaign ORDER BY count DESC LIMIT 10",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$top_landing_pages = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT landing_page, COUNT(*) as count FROM $lt
			 WHERE landing_page != '' AND created_at BETWEEN %s AND %s
			 GROUP BY landing_page ORDER BY count DESC LIMIT 10",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$by_source = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT lead_source, COUNT(*) as count FROM $lt
			 WHERE lead_source != '' AND created_at BETWEEN %s AND %s
			 GROUP BY lead_source ORDER BY count DESC",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$by_status = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT status, COUNT(*) as count FROM $lt
			 WHERE created_at BETWEEN %s AND %s GROUP BY status ORDER BY count DESC",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$campaign_roi = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT l.campaign,
				COUNT(l.id) as leads,
				COUNT(CASE WHEN l.status='booked' THEN 1 END) as bookings,
				COALESCE(SUM(CASE WHEN b.status IN ('booked','completed') THEN b.fare ELSE 0 END),0) as revenue
			 FROM $lt l LEFT JOIN $bt b ON l.id = b.lead_id
			 WHERE l.campaign != '' AND l.created_at BETWEEN %s AND %s
			 GROUP BY l.campaign ORDER BY leads DESC LIMIT 15",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );
		foreach ( $campaign_roi as &$r ) {
			$r->conversion_pct = $r->leads > 0 ? round( ( $r->bookings / $r->leads ) * 100, 1 ) : 0;
		}
		unset( $r );

		$keyword_roi = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT l.keyword,
				COUNT(l.id) as leads,
				COALESCE(SUM(CASE WHEN b.status IN ('booked','completed') THEN b.fare ELSE 0 END),0) as revenue
			 FROM $lt l LEFT JOIN $bt b ON l.id = b.lead_id
			 WHERE l.keyword != '' AND l.created_at BETWEEN %s AND %s
			 GROUP BY l.keyword ORDER BY leads DESC LIMIT 15",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$source_revenue = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT l.lead_source,
				COALESCE(SUM(CASE WHEN b.status IN ('booked','completed') THEN b.fare ELSE 0 END),0) as revenue
			 FROM $lt l LEFT JOIN $bt b ON l.id = b.lead_id
			 WHERE l.lead_source != '' AND l.created_at BETWEEN %s AND %s
			 GROUP BY l.lead_source ORDER BY revenue DESC",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$google_ads_conversions = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM $lt WHERE gclid != '' AND created_at BETWEEN %s AND %s",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		$google_ads_revenue = (float) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COALESCE(SUM(CASE WHEN b.status IN ('booked','completed') THEN b.fare ELSE 0 END),0)
			 FROM $lt l LEFT JOIN $bt b ON l.id = b.lead_id
			 WHERE l.gclid != '' AND l.created_at BETWEEN %s AND %s",
			$start . ' 00:00:00', $end . ' 23:59:59'
		) );

		return array(
			'total_leads'             => $total_leads,
			'total_bookings'          => $total_bookings,
			'revenue'                 => $revenue,
			'conversion'              => $conv_pct,
			'avg_fare'                => $avg_fare,
			'repeat_customers'        => $repeat,
			'top_routes'              => $top_routes,
			'top_campaigns'           => $top_campaigns,
			'top_landing_pages'       => $top_landing_pages,
			'by_source'               => $by_source,
			'by_status'               => $by_status,
			'campaign_roi'            => $campaign_roi,
			'keyword_roi'             => $keyword_roi,
			'source_revenue'          => $source_revenue,
			'google_ads_conversions'  => $google_ads_conversions,
			'google_ads_revenue'      => $google_ads_revenue,
		);
	}

	/* ── Format helpers ────────────────────────────────────── */

	private function get_lead_format( $data ) {
		$int_keys  = array( 'id' );
		$float_keys = array();
		$format = array();
		foreach ( $data as $k => $v ) {
			if ( in_array( $k, $int_keys, true ) ) {
				$format[] = '%d';
			} elseif ( in_array( $k, $float_keys, true ) ) {
				$format[] = '%f';
			} else {
				$format[] = '%s';
			}
		}
		return $format;
	}

	private function get_tracking_format( $data ) {
		$int_keys = array( 'lead_id' );
		$format = array();
		foreach ( $data as $k => $v ) {
			$format[] = in_array( $k, $int_keys, true ) ? '%d' : '%s';
		}
		return $format;
	}

	private function get_booking_format( $data ) {
		$int_keys   = array( 'lead_id' );
		$float_keys = array( 'fare' );
		$format = array();
		foreach ( $data as $k => $v ) {
			if ( in_array( $k, $int_keys, true ) ) {
				$format[] = '%d';
			} elseif ( in_array( $k, $float_keys, true ) ) {
				$format[] = '%f';
			} else {
				$format[] = '%s';
			}
		}
		return $format;
	}
}
