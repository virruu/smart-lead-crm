<?php
/**
 * Database helper class - CRUD with prepared statements.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database helper class.
 *
 * @package SmartLeadCRM
 */
class Smart_Lead_CRM_DB {

	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * Table names.
	 *
	 * @var array
	 */
	protected $tables = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb   = $wpdb;
		$this->tables = array(
			'leads'    => $wpdb->prefix . 'slcrm_leads',
			'tracking' => $wpdb->prefix . 'slcrm_tracking',
			'bookings' => $wpdb->prefix . 'slcrm_bookings',
			'notes'    => $wpdb->prefix . 'slcrm_notes',
		);
	}

	/**
	 * Get a table name.
	 *
	 * @param string $key Table key.
	 * @return string
	 */
	public function table( $key ) {
		return isset( $this->tables[ $key ] ) ? $this->tables[ $key ] : '';
	}

	/**
	 * Insert a lead.
	 *
	 * @param array $data Lead data.
	 * @return int|false Inserted ID or false on failure.
	 */
	public function insert_lead( $data ) {
		$defaults = array(
			'created_at'    => current_time( 'mysql' ),
			'last_updated'  => current_time( 'mysql' ),
			'status'        => 'pending',
		);
		$data = wp_parse_args( $data, $defaults );

		$format = $this->get_lead_format( $data );
		$result = $this->wpdb->insert( $this->tables['leads'], $data, $format );

		return $result ? (int) $this->wpdb->insert_id : false;
	}

	/**
	 * Update a lead.
	 *
	 * @param int   $id   Lead ID.
	 * @param array $data Data to update.
	 * @return int|false Number of rows updated or false.
	 */
	public function update_lead( $id, $data ) {
		$data['last_updated'] = current_time( 'mysql' );
		$format               = $this->get_lead_format( $data );
		$where_format         = array( '%d' );

		return $this->wpdb->update( $this->tables['leads'], $data, array( 'id' => $id ), $format, $where_format );
	}

	/**
	 * Delete a lead.
	 *
	 * @param int $id Lead ID.
	 * @return int|false Number of rows deleted or false.
	 */
	public function delete_lead( $id ) {
		return $this->wpdb->delete( $this->tables['leads'], array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Get a single lead by ID.
	 *
	 * @param int $id Lead ID.
	 * @return object|null
	 */
	public function get_lead( $id ) {
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['leads']} WHERE id = %d",
			$id
		);
		return $this->wpdb->get_row( $query );
	}

	/**
	 * Find a lead by visitor_id created today (for dedup).
	 *
	 * @param string $visitor_id Visitor UUID.
	 * @return object|null
	 */
	public function find_lead_by_visitor_today( $visitor_id ) {
		$today_start = current_time( 'Y-m-d 00:00:00' );
		$today_end   = current_time( 'Y-m-d 23:59:59' );
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['leads']} WHERE visitor_id = %s AND created_at BETWEEN %s AND %s ORDER BY created_at DESC LIMIT 1",
			$visitor_id,
			$today_start,
			$today_end
		);
		return $this->wpdb->get_row( $query );
	}

	/**
	 * Find a lead by visitor_id (most recent, any date).
	 *
	 * @param string $visitor_id Visitor UUID.
	 * @return object|null
	 */
	public function find_lead_by_visitor( $visitor_id ) {
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['leads']} WHERE visitor_id = %s ORDER BY created_at DESC LIMIT 1",
			$visitor_id
		);
		return $this->wpdb->get_row( $query );
	}

	/**
	 * Get leads with optional filters.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_leads( $args = array() ) {
		$defaults = array(
			'number'  => 20,
			'offset'  => 0,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
			'status'   => '',
			'search'   => '',
			'source'   => '',
		);
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
			$search   = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$where    .= ' AND (phone LIKE %s OR name LIKE %s OR booking_route LIKE %s OR campaign LIKE %s)';
			$params[] = $search;
			$params[] = $search;
			$params[] = $search;
			$params[] = $search;
		}

		// Whitelist orderby.
		$allowed_orderby = array( 'id', 'created_at', 'last_updated', 'status', 'phone', 'name' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$where   .= " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params[] = (int) $args['number'];
		$params[] = (int) $args['offset'];

		$query = "SELECT * FROM {$this->tables['leads']} {$where}";
		$query = $this->wpdb->prepare( $query, $params );

		return $this->wpdb->get_results( $query );
	}

	/**
	 * Count leads.
	 *
	 * @param array $args Query arguments.
	 * @return int
	 */
	public function count_leads( $args = array() ) {
		$defaults = array(
			'status' => '',
			'source' => '',
			'search' => '',
		);
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
			$search   = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$where    .= ' AND (phone LIKE %s OR name LIKE %s OR booking_route LIKE %s OR campaign LIKE %s)';
			$params[] = $search;
			$params[] = $search;
			$params[] = $search;
			$params[] = $search;
		}

		$query = "SELECT COUNT(*) FROM {$this->tables['leads']} {$where}";
		if ( ! empty( $params ) ) {
			$query = $this->wpdb->prepare( $query, $params );
		}

		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Insert a tracking record.
	 *
	 * @param array $data Tracking data.
	 * @return int|false
	 */
	public function insert_tracking( $data ) {
		$defaults = array(
			'visit_time' => current_time( 'mysql' ),
		);
		$data = wp_parse_args( $data, $defaults );

		$format = $this->get_tracking_format( $data );
		$result = $this->wpdb->insert( $this->tables['tracking'], $data, $format );

		return $result ? (int) $this->wpdb->insert_id : false;
	}

	/**
	 * Get tracking records for a lead.
	 *
	 * @param int $lead_id Lead ID.
	 * @return array
	 */
	public function get_tracking( $lead_id ) {
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['tracking']} WHERE lead_id = %d ORDER BY visit_time DESC",
			$lead_id
		);
		return $this->wpdb->get_results( $query );
	}

	/**
	 * Insert a booking.
	 *
	 * @param array $data Booking data.
	 * @return int|false
	 */
	public function insert_booking( $data ) {
		$defaults = array(
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
			'status'     => 'pending',
		);
		$data = wp_parse_args( $data, $defaults );

		$format = $this->get_booking_format( $data );
		$result = $this->wpdb->insert( $this->tables['bookings'], $data, $format );

		return $result ? (int) $this->wpdb->insert_id : false;
	}

	/**
	 * Get bookings for a lead.
	 *
	 * @param int $lead_id Lead ID.
	 * @return array
	 */
	public function get_bookings( $lead_id ) {
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['bookings']} WHERE lead_id = %d ORDER BY booking_date DESC",
			$lead_id
		);
		return $this->wpdb->get_results( $query );
	}

	/**
	 * Insert a note.
	 *
	 * @param array $data Note data.
	 * @return int|false
	 */
	public function insert_note( $data ) {
		$defaults = array(
			'created_at' => current_time( 'mysql' ),
		);
		$data = wp_parse_args( $data, $defaults );

		$format = array( '%d', '%s', '%d', '%s' );
		$result = $this->wpdb->insert( $this->tables['notes'], $data, $format );

		return $result ? (int) $this->wpdb->insert_id : false;
	}

	/**
	 * Get notes for a lead.
	 *
	 * @param int $lead_id Lead ID.
	 * @return array
	 */
	public function get_notes( $lead_id ) {
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['notes']} WHERE lead_id = %d ORDER BY created_at DESC",
			$lead_id
		);
		return $this->wpdb->get_results( $query );
	}

	/**
	 * Update a booking.
	 *
	 * @param int   $id   Booking ID.
	 * @param array $data Data to update.
	 * @return int|false
	 */
	public function update_booking( $id, $data ) {
		$data['updated_at'] = current_time( 'mysql' );
		$format             = $this->get_booking_format( $data );
		return $this->wpdb->update( $this->tables['bookings'], $data, array( 'id' => $id ), $format, array( '%d' ) );
	}

	/**
	 * Delete a booking.
	 *
	 * @param int $id Booking ID.
	 * @return int|false
	 */
	public function delete_booking( $id ) {
		return $this->wpdb->delete( $this->tables['bookings'], array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Get a single booking by ID.
	 *
	 * @param int $id Booking ID.
	 * @return object|null
	 */
	public function get_booking( $id ) {
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['bookings']} WHERE id = %d",
			$id
		);
		return $this->wpdb->get_row( $query );
	}

	/**
	 * Delete all data related to a lead (tracking, bookings, notes).
	 *
	 * @param int $lead_id Lead ID.
	 */
	public function delete_lead_cascade( $lead_id ) {
		$this->wpdb->delete( $this->tables['tracking'], array( 'lead_id' => $lead_id ), array( '%d' ) );
		$this->wpdb->delete( $this->tables['bookings'], array( 'lead_id' => $lead_id ), array( '%d' ) );
		$this->wpdb->delete( $this->tables['notes'], array( 'lead_id' => $lead_id ), array( '%d' ) );
		$this->wpdb->delete( $this->tables['leads'], array( 'id' => $lead_id ), array( '%d' ) );
	}

	/**
	 * Get report data for a date range.
	 *
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date   End date (Y-m-d).
	 * @return array
	 */
	public function get_report_data( $start_date, $end_date ) {
		$leads_table    = $this->tables['leads'];
		$bookings_table = $this->tables['bookings'];

		$report = array(
			'total_leads'      => 0,
			'total_bookings'   => 0,
			'revenue'          => 0,
			'conversion'       => 0,
			'avg_fare'         => 0,
			'repeat_customers' => 0,
			'top_routes'       => array(),
			'top_campaigns'    => array(),
			'top_landing_pages' => array(),
			'by_source'        => array(),
			'by_status'        => array(),
		);

		$start = $start_date . ' 00:00:00';
		$end   = $end_date . ' 23:59:59';

		// Total leads in range.
		$report['total_leads'] = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$leads_table} WHERE created_at BETWEEN %s AND %s",
				$start,
				$end
			)
		);

		// Total bookings in range.
		$report['total_bookings'] = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$bookings_table} WHERE created_at BETWEEN %s AND %s AND status = %s",
				$start,
				$end,
				'booked'
			)
		);

		// Revenue.
		$report['revenue'] = (float) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COALESCE(SUM(fare), 0) FROM {$bookings_table} WHERE created_at BETWEEN %s AND %s AND status = %s",
				$start,
				$end,
				'booked'
			)
		);

		// Conversion %.
		if ( $report['total_leads'] > 0 ) {
			$report['conversion'] = round( ( $report['total_bookings'] / $report['total_leads'] ) * 100, 2 );
		}

		// Average fare.
		$report['avg_fare'] = (float) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COALESCE(AVG(fare), 0) FROM {$bookings_table} WHERE created_at BETWEEN %s AND %s AND status = %s",
				$start,
				$end,
				'booked'
			)
		);

		// Repeat customers.
		$report['repeat_customers'] = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM (SELECT lead_id FROM {$bookings_table} WHERE created_at BETWEEN %s AND %s GROUP BY lead_id HAVING COUNT(*) > 1) AS repeats",
				$start,
				$end
			)
		);

		// Top routes.
		$report['top_routes'] = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT route, COUNT(*) as count, SUM(fare) as revenue FROM {$bookings_table} WHERE created_at BETWEEN %s AND %s AND status = %s AND route != '' GROUP BY route ORDER BY count DESC LIMIT 10",
				$start,
				$end,
				'booked'
			)
		);

		// Top campaigns.
		$report['top_campaigns'] = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT campaign, COUNT(*) as count FROM {$leads_table} WHERE created_at BETWEEN %s AND %s AND campaign != '' GROUP BY campaign ORDER BY count DESC LIMIT 10",
				$start,
				$end
			)
		);

		// Top landing pages.
		$report['top_landing_pages'] = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT landing_page, COUNT(*) as count FROM {$leads_table} WHERE created_at BETWEEN %s AND %s AND landing_page != '' GROUP BY landing_page ORDER BY count DESC LIMIT 10",
				$start,
				$end
			)
		);

		// Leads by source.
		$report['by_source'] = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT lead_source, COUNT(*) as count FROM {$leads_table} WHERE created_at BETWEEN %s AND %s AND lead_source != '' GROUP BY lead_source ORDER BY count DESC",
				$start,
				$end
			)
		);

		// Leads by status.
		$report['by_status'] = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT status, COUNT(*) as count FROM {$leads_table} WHERE created_at BETWEEN %s AND %s GROUP BY status ORDER BY count DESC",
				$start,
				$end
			)
		);

		return $report;
	}

	/**
	 * Get dashboard stats.
	 *
	 * @return array
	 */
	public function get_dashboard_stats() {
		$today_start = current_time( 'Y-m-d 00:00:00' );
		$today_end   = current_time( 'Y-m-d 23:59:59' );

		$stats = array(
			'today_leads'    => 0,
			'today_bookings' => 0,
			'revenue'        => 0,
			'conversion'     => 0,
			'avg_fare'       => 0,
			'repeat_customers' => 0,
		);

		// Today's leads.
		$stats['today_leads'] = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->tables['leads']} WHERE created_at BETWEEN %s AND %s",
				$today_start,
				$today_end
			)
		);

		// Today's bookings.
		$stats['today_bookings'] = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->tables['bookings']} WHERE created_at BETWEEN %s AND %s AND status = %s",
				$today_start,
				$today_end,
				'booked'
			)
		);

		// Revenue (sum of fares for booked).
		$stats['revenue'] = (float) $this->wpdb->get_var(
			"SELECT COALESCE(SUM(fare), 0) FROM {$this->tables['bookings']} WHERE status = 'booked'"
		);

		// Conversion %.
		$total_leads    = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->tables['leads']}" );
		$total_bookings = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->tables['bookings']} WHERE status = 'booked'" );
		if ( $total_leads > 0 ) {
			$stats['conversion'] = round( ( $total_bookings / $total_leads ) * 100, 2 );
		}

		// Average fare.
		$stats['avg_fare'] = (float) $this->wpdb->get_var(
			"SELECT COALESCE(AVG(fare), 0) FROM {$this->tables['bookings']} WHERE status = 'booked'"
		);

		// Repeat customers (leads with more than one booking).
		$stats['repeat_customers'] = (int) $this->wpdb->get_var(
			"SELECT COUNT(*) FROM (SELECT lead_id FROM {$this->tables['bookings']} GROUP BY lead_id HAVING COUNT(*) > 1) AS repeats"
		);

		return $stats;
	}

	/**
	 * Get format array for lead data.
	 *
	 * @param array $data Data array.
	 * @return array
	 */
	private function get_lead_format( $data ) {
		$format_map = array(
			'id'            => '%d',
			'created_at'    => '%s',
			'visitor_id'    => '%s',
			'phone'         => '%s',
			'name'          => '%s',
			'email'         => '%s',
			'status'        => '%s',
			'lead_source'   => '%s',
			'campaign'      => '%s',
			'ad_group'      => '%s',
			'keyword'       => '%s',
			'landing_page'  => '%s',
			'booking_route' => '%s',
			'booking_date'  => '%s',
			'remarks'       => '%s',
			'device'        => '%s',
			'browser'       => '%s',
			'ip'            => '%s',
			'referer'       => '%s',
			'last_updated'  => '%s',
		);
		$format = array();
		foreach ( $data as $key => $value ) {
			if ( isset( $format_map[ $key ] ) ) {
				$format[] = $format_map[ $key ];
			}
		}
		return $format;
	}

	/**
	 * Get format array for tracking data.
	 *
	 * @param array $data Data array.
	 * @return array
	 */
	private function get_tracking_format( $data ) {
		$format_map = array(
			'id'           => '%d',
			'lead_id'      => '%d',
			'visitor_id'   => '%s',
			'visit_time'   => '%s',
			'gclid'        => '%s',
			'gbraid'       => '%s',
			'wbraid'       => '%s',
			'utm_source'   => '%s',
			'utm_medium'   => '%s',
			'utm_campaign' => '%s',
			'utm_term'     => '%s',
			'utm_content'  => '%s',
			'landing_page' => '%s',
			'referer'      => '%s',
			'device'       => '%s',
			'browser'      => '%s',
			'ip'           => '%s',
		);
		$format = array();
		foreach ( $data as $key => $value ) {
			if ( isset( $format_map[ $key ] ) ) {
				$format[] = $format_map[ $key ];
			}
		}
		return $format;
	}

	/**
	 * Get format array for booking data.
	 *
	 * @param array $data Data array.
	 * @return array
	 */
	private function get_booking_format( $data ) {
		$format_map = array(
			'id'           => '%d',
			'lead_id'      => '%d',
			'booking_type' => '%s',
			'route'        => '%s',
			'fare'         => '%f',
			'booking_date' => '%s',
			'driver'       => '%s',
			'status'       => '%s',
			'created_at'   => '%s',
			'updated_at'   => '%s',
		);
		$format = array();
		foreach ( $data as $key => $value ) {
			if ( isset( $format_map[ $key ] ) ) {
				$format[] = $format_map[ $key ];
			}
		}
		return $format;
	}
}
