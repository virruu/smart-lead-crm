<?php
/**
 * Installer class - creates tables, runs upgrades, database versioning.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installer class.
 *
 * @package SmartLeadCRM
 */
class Smart_Lead_CRM_Installer {

	/**
	 * Table names.
	 *
	 * @var array
	 */
	private $tables = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
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
	public function get_table_name( $key ) {
		return isset( $this->tables[ $key ] ) ? $this->tables[ $key ] : '';
	}

	/**
	 * Run on plugin activation.
	 */
	public function activate() {
		$this->create_tables();
		$this->add_default_options();
		$this->schedule_events();
		update_option( 'smart_lead_crm_db_version', SMART_LEAD_CRM_DB_VERSION );
		update_option( 'smart_lead_crm_install_date', current_time( 'mysql' ) );
		flush_rewrite_rules();
	}

	/**
	 * Run on plugin deactivation.
	 */
	public function deactivate() {
		$this->clear_scheduled_events();
		flush_rewrite_rules();
	}

	/**
	 * Create database tables.
	 */
	private function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$leads_table     = $this->tables['leads'];
		$tracking_table  = $this->tables['tracking'];
		$bookings_table  = $this->tables['bookings'];
		$notes_table     = $this->tables['notes'];

		// Leads table.
		$sql_leads = "CREATE TABLE {$leads_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at DATETIME NOT NULL,
			visitor_id VARCHAR(36) NOT NULL DEFAULT '',
			phone VARCHAR(50) NOT NULL DEFAULT '',
			name VARCHAR(255) NOT NULL DEFAULT '',
			email VARCHAR(255) NOT NULL DEFAULT '',
			status VARCHAR(50) NOT NULL DEFAULT 'pending',
			lead_source VARCHAR(100) NOT NULL DEFAULT '',
			campaign VARCHAR(255) NOT NULL DEFAULT '',
			ad_group VARCHAR(255) NOT NULL DEFAULT '',
			keyword VARCHAR(255) NOT NULL DEFAULT '',
			landing_page TEXT NOT NULL,
			booking_route VARCHAR(255) NOT NULL DEFAULT '',
			booking_date DATE NULL DEFAULT NULL,
			remarks TEXT NOT NULL,
			device VARCHAR(100) NOT NULL DEFAULT '',
			browser VARCHAR(100) NOT NULL DEFAULT '',
			ip VARCHAR(100) NOT NULL DEFAULT '',
			referer TEXT NOT NULL,
			last_updated DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY phone (phone(20)),
			KEY visitor_id (visitor_id),
			KEY status (status),
			KEY lead_source (lead_source),
			KEY created_at (created_at)
		) {$charset_collate};";

		// Tracking table - one lead can have multiple visits.
		$sql_tracking = "CREATE TABLE {$tracking_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			visitor_id VARCHAR(36) NOT NULL DEFAULT '',
			visit_time DATETIME NOT NULL,
			gclid VARCHAR(255) NOT NULL DEFAULT '',
			gbraid VARCHAR(255) NOT NULL DEFAULT '',
			wbraid VARCHAR(255) NOT NULL DEFAULT '',
			utm_source VARCHAR(255) NOT NULL DEFAULT '',
			utm_medium VARCHAR(255) NOT NULL DEFAULT '',
			utm_campaign VARCHAR(255) NOT NULL DEFAULT '',
			utm_term VARCHAR(255) NOT NULL DEFAULT '',
			utm_content VARCHAR(255) NOT NULL DEFAULT '',
			landing_page TEXT NOT NULL,
			referer TEXT NOT NULL,
			device VARCHAR(100) NOT NULL DEFAULT '',
			browser VARCHAR(100) NOT NULL DEFAULT '',
			ip VARCHAR(100) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			KEY lead_id (lead_id),
			KEY visitor_id (visitor_id),
			KEY visit_time (visit_time),
			KEY gclid (gclid(50))
		) {$charset_collate};";

		// Bookings table.
		$sql_bookings = "CREATE TABLE {$bookings_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			booking_type VARCHAR(100) NOT NULL DEFAULT '',
			route VARCHAR(255) NOT NULL DEFAULT '',
			fare DECIMAL(12,2) NOT NULL DEFAULT 0,
			booking_date DATE NOT NULL DEFAULT '0000-00-00',
			driver VARCHAR(255) NOT NULL DEFAULT '',
			status VARCHAR(50) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY lead_id (lead_id),
			KEY booking_type (booking_type),
			KEY status (status),
			KEY booking_date (booking_date)
		) {$charset_collate};";

		// Notes table - follow-up history.
		$sql_notes = "CREATE TABLE {$notes_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			note TEXT NOT NULL,
			author_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY lead_id (lead_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql_leads );
		dbDelta( $sql_tracking );
		dbDelta( $sql_bookings );
		dbDelta( $sql_notes );

		// Migration: add visitor_id column to existing tables if upgrading.
		$this->maybe_add_visitor_id_column( $leads_table, 'leads' );
		$this->maybe_add_visitor_id_column( $tracking_table, 'tracking' );
	}

	/**
	 * Add visitor_id column if it doesn't exist (migration for 1.0.0 → 1.1.0).
	 *
	 * @param string $table Table name.
	 * @param string $key   Table key for index naming.
	 */
	private function maybe_add_visitor_id_column( $table, $key ) {
		global $wpdb;
		$column_exists = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'visitor_id' ) );
		if ( empty( $column_exists ) ) {
			// Add after created_at for leads, after lead_id for tracking.
			if ( 'leads' === $key ) {
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN visitor_id VARCHAR(36) NOT NULL DEFAULT '' AFTER created_at" );
			} else {
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN visitor_id VARCHAR(36) NOT NULL DEFAULT '' AFTER lead_id" );
			}
			$wpdb->query( "ALTER TABLE {$table} ADD INDEX visitor_id (visitor_id)" );
		}
	}

	/**
	 * Add default plugin options.
	 */
	private function add_default_options() {
		$defaults = array(
			'business_name'             => get_bloginfo( 'name' ),
			'google_ads_conversion_id'  => '',
			'google_ads_label'          => '',
			'ga4_measurement_id'        => '',
			'cookie_duration'           => 90,
			'capture_gclid'             => 'yes',
			'capture_utm'               => 'yes',
			'enable_debug'              => 'no',
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( 'smart_lead_crm_' . $key ) ) {
				add_option( 'smart_lead_crm_' . $key, $value );
			}
		}
	}

	/**
	 * Schedule recurring events.
	 */
	private function schedule_events() {
		if ( ! wp_next_scheduled( 'smart_lead_crm_daily_maintenance' ) ) {
			wp_schedule_event( time(), 'daily', 'smart_lead_crm_daily_maintenance' );
		}
	}

	/**
	 * Clear scheduled events.
	 */
	private function clear_scheduled_events() {
		$timestamp = wp_next_scheduled( 'smart_lead_crm_daily_maintenance' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'smart_lead_crm_daily_maintenance' );
		}
	}

	/**
	 * Check and run database upgrades if needed.
	 */
	public function check_upgrades() {
		$current_version = get_option( 'smart_lead_crm_db_version' );
		if ( version_compare( $current_version, SMART_LEAD_CRM_DB_VERSION, '<' ) ) {
			$this->create_tables();
			update_option( 'smart_lead_crm_db_version', SMART_LEAD_CRM_DB_VERSION );
		}
	}
}
