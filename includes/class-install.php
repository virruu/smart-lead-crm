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
			'conversations' => $wpdb->prefix . 'slcrm_conversations',
			'messages'       => $wpdb->prefix . 'slcrm_messages',
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
		$notes_table      = $this->tables['notes'];
		$wa_messages_table = $this->tables['wa_messages'];

		// Leads table — stores full attribution data permanently for reporting.
		$sql_leads = "CREATE TABLE {$leads_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at DATETIME NOT NULL,
			visitor_id VARCHAR(36) NOT NULL DEFAULT '',
			phone VARCHAR(50) NOT NULL DEFAULT '',
			name VARCHAR(255) NOT NULL DEFAULT '',
			email VARCHAR(255) NOT NULL DEFAULT '',
			status VARCHAR(50) NOT NULL DEFAULT 'pending',
			lead_source VARCHAR(100) NOT NULL DEFAULT '',
			medium VARCHAR(100) NOT NULL DEFAULT '',
			campaign VARCHAR(255) NOT NULL DEFAULT '',
			ad_group VARCHAR(255) NOT NULL DEFAULT '',
			keyword VARCHAR(255) NOT NULL DEFAULT '',
			gclid VARCHAR(255) NOT NULL DEFAULT '',
			gbraid VARCHAR(255) NOT NULL DEFAULT '',
			wbraid VARCHAR(255) NOT NULL DEFAULT '',
			utm_source VARCHAR(255) NOT NULL DEFAULT '',
			utm_medium VARCHAR(255) NOT NULL DEFAULT '',
			utm_campaign VARCHAR(255) NOT NULL DEFAULT '',
			utm_term VARCHAR(255) NOT NULL DEFAULT '',
			utm_content VARCHAR(255) NOT NULL DEFAULT '',
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
			KEY created_at (created_at),
			KEY gclid (gclid(50)),
			KEY campaign (campaign(50))
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

		// Conversations table — one lead can have multiple conversations across channels.
		$conversations_table = $this->tables['conversations'];
		$sql_conversations = "CREATE TABLE {$conversations_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			platform VARCHAR(50) NOT NULL DEFAULT 'whatsapp',
			conversation_id VARCHAR(255) NOT NULL DEFAULT '',
			customer_name VARCHAR(255) NOT NULL DEFAULT '',
			customer_phone VARCHAR(50) NOT NULL DEFAULT '',
			started_at DATETIME NOT NULL,
			last_message_at DATETIME NOT NULL,
			status VARCHAR(50) NOT NULL DEFAULT 'active',
			assigned_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY lead_id (lead_id),
			KEY platform (platform),
			KEY conversation_id (conversation_id(50)),
			KEY customer_phone (customer_phone(20)),
			KEY assigned_user_id (assigned_user_id),
			KEY started_at (started_at)
		) {$charset_collate};";

		// Messages table — individual messages within a conversation.
		$messages_table = $this->tables['messages'];
		$sql_messages = "CREATE TABLE {$messages_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			direction VARCHAR(20) NOT NULL DEFAULT 'inbound',
			message_id VARCHAR(255) NOT NULL DEFAULT '',
			text TEXT NOT NULL,
			message_type VARCHAR(50) NOT NULL DEFAULT 'text',
			media_url TEXT NOT NULL,
			status VARCHAR(50) NOT NULL DEFAULT 'received',
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY conversation_id (conversation_id),
			KEY message_id (message_id(80)),
			KEY direction (direction),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql_leads );
		dbDelta( $sql_tracking );
		dbDelta( $sql_bookings );
		dbDelta( $sql_notes );
		dbDelta( $sql_conversations );
		dbDelta( $sql_messages );

		// Migration: add visitor_id column to existing tables if upgrading.
		$this->maybe_add_visitor_id_column( $leads_table, 'leads' );
		$this->maybe_add_visitor_id_column( $tracking_table, 'tracking' );

		// Migration: add attribution columns to leads table (1.1.0 → 1.2.0).
		$this->maybe_add_attribution_columns( $leads_table );
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
	 * Add attribution columns to the leads table if they don't exist.
	 *
	 * This migration adds the permanent storage for gclid, gbraid, wbraid, and
	 * all UTM parameters directly on the lead row so reports and offline
	 * conversion uploads can be generated without joining the tracking table.
	 *
	 * @param string $table Leads table name.
	 */
	private function maybe_add_attribution_columns( $table ) {
		global $wpdb;

		$columns = array(
			'medium'       => "ADD COLUMN medium VARCHAR(100) NOT NULL DEFAULT '' AFTER lead_source",
			'gclid'        => "ADD COLUMN gclid VARCHAR(255) NOT NULL DEFAULT '' AFTER keyword",
			'gbraid'       => "ADD COLUMN gbraid VARCHAR(255) NOT NULL DEFAULT '' AFTER gclid",
			'wbraid'       => "ADD COLUMN wbraid VARCHAR(255) NOT NULL DEFAULT '' AFTER gbraid",
			'utm_source'   => "ADD COLUMN utm_source VARCHAR(255) NOT NULL DEFAULT '' AFTER wbraid",
			'utm_medium'   => "ADD COLUMN utm_medium VARCHAR(255) NOT NULL DEFAULT '' AFTER utm_source",
			'utm_campaign' => "ADD COLUMN utm_campaign VARCHAR(255) NOT NULL DEFAULT '' AFTER utm_medium",
			'utm_term'     => "ADD COLUMN utm_term VARCHAR(255) NOT NULL DEFAULT '' AFTER utm_campaign",
			'utm_content'  => "ADD COLUMN utm_content VARCHAR(255) NOT NULL DEFAULT '' AFTER utm_term",
		);

		foreach ( $columns as $column => $ddl ) {
			$exists = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) );
			if ( empty( $exists ) ) {
				$wpdb->query( "ALTER TABLE {$table} {$ddl}" );
			}
		}

		// Add index on gclid for offline conversion lookups.
		$index_exists = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM {$table} WHERE Key_name = %s", 'gclid' ) );
		if ( empty( $index_exists ) ) {
			$wpdb->query( "ALTER TABLE {$table} ADD INDEX gclid (gclid(50))" );
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
			'whatsapp_access_token'     => '',
			'whatsapp_phone_number_id'  => '',
			'whatsapp_verify_token'      => '',
			'whatsapp_business_number'  => '',
			'whatsapp_api_version'      => 'v18.0',
			'whatsapp_default_country_code' => '91',
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
