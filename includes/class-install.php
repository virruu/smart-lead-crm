<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Installer {

	public function activate() {
		$this->create_tables();
		$this->add_default_options();
		update_option( 'smart_lead_crm_db_version', SMART_LEAD_CRM_DB_VERSION );
		update_option( 'smart_lead_crm_install_date', current_time( 'mysql' ) );
	}

	public function deactivate() {
		wp_clear_scheduled_hook( 'smart_lead_crm_daily_maintenance' );
	}

	public function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		$leads = $wpdb->prefix . 'slcrm_leads';
		$sql_leads = "CREATE TABLE $leads (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL DEFAULT '',
			phone VARCHAR(50) NOT NULL DEFAULT '',
			email VARCHAR(255) NOT NULL DEFAULT '',
			status VARCHAR(30) NOT NULL DEFAULT 'new_lead',
			lead_source VARCHAR(50) NOT NULL DEFAULT 'direct',
			medium VARCHAR(100) NOT NULL DEFAULT '',
			campaign VARCHAR(255) NOT NULL DEFAULT '',
			ad_group VARCHAR(255) NOT NULL DEFAULT '',
			keyword VARCHAR(255) NOT NULL DEFAULT '',
			booking_route VARCHAR(255) NOT NULL DEFAULT '',
			booking_date DATE NULL DEFAULT NULL,
			follow_up_date DATE NULL DEFAULT NULL,
			gclid VARCHAR(255) NOT NULL DEFAULT '',
			gbraid VARCHAR(255) NOT NULL DEFAULT '',
			wbraid VARCHAR(255) NOT NULL DEFAULT '',
			utm_source VARCHAR(255) NOT NULL DEFAULT '',
			utm_campaign VARCHAR(255) NOT NULL DEFAULT '',
			utm_medium VARCHAR(255) NOT NULL DEFAULT '',
			utm_term VARCHAR(255) NOT NULL DEFAULT '',
			utm_content VARCHAR(255) NOT NULL DEFAULT '',
			landing_page TEXT NOT NULL DEFAULT '',
			referer TEXT NOT NULL DEFAULT '',
			device VARCHAR(50) NOT NULL DEFAULT '',
			browser VARCHAR(50) NOT NULL DEFAULT '',
			ip_address VARCHAR(100) NOT NULL DEFAULT '',
			customer_mobile VARCHAR(50) NOT NULL DEFAULT '',
			lead_action VARCHAR(50) NOT NULL DEFAULT '',
			form_name VARCHAR(100) NOT NULL DEFAULT '',
			visitor_id VARCHAR(36) NOT NULL DEFAULT '',
			remarks TEXT NOT NULL DEFAULT '',
			last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY phone (phone),
			KEY visitor_id (visitor_id),
			KEY status (status),
			KEY lead_source (lead_source),
			KEY created_at (created_at),
			KEY gclid (gclid),
			KEY campaign (campaign)
		) $charset;";
		dbDelta( $sql_leads );

		$tracking = $wpdb->prefix . 'slcrm_tracking';
		$sql_tracking = "CREATE TABLE $tracking (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			visitor_id VARCHAR(36) NOT NULL DEFAULT '',
			visit_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			page_url TEXT NOT NULL DEFAULT '',
			utm_source VARCHAR(255) NOT NULL DEFAULT '',
			utm_campaign VARCHAR(255) NOT NULL DEFAULT '',
			utm_medium VARCHAR(255) NOT NULL DEFAULT '',
			utm_term VARCHAR(255) NOT NULL DEFAULT '',
			utm_content VARCHAR(255) NOT NULL DEFAULT '',
			gclid VARCHAR(255) NOT NULL DEFAULT '',
			gbraid VARCHAR(255) NOT NULL DEFAULT '',
			wbraid VARCHAR(255) NOT NULL DEFAULT '',
			referer TEXT NOT NULL DEFAULT '',
			device VARCHAR(50) NOT NULL DEFAULT '',
			browser VARCHAR(50) NOT NULL DEFAULT '',
			ip_address VARCHAR(100) NOT NULL DEFAULT '',
			organic_keyword VARCHAR(255) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY lead_id (lead_id),
			KEY visitor_id (visitor_id)
		) $charset;";
		dbDelta( $sql_tracking );

		$bookings = $wpdb->prefix . 'slcrm_bookings';
		$sql_bookings = "CREATE TABLE $bookings (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			booking_type VARCHAR(50) NOT NULL DEFAULT 'local',
			route VARCHAR(255) NOT NULL DEFAULT '',
			fare DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			booking_date DATE NULL DEFAULT NULL,
			driver VARCHAR(255) NOT NULL DEFAULT '',
			status VARCHAR(30) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY lead_id (lead_id),
			KEY status (status)
		) $charset;";
		dbDelta( $sql_bookings );

		$notes = $wpdb->prefix . 'slcrm_notes';
		$sql_notes = "CREATE TABLE $notes (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			note TEXT NOT NULL,
			author_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY lead_id (lead_id)
		) $charset;";
		dbDelta( $sql_notes );

		$conversations = $wpdb->prefix . 'slcrm_conversations';
		$sql_conv = "CREATE TABLE $conversations (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			platform VARCHAR(50) NOT NULL DEFAULT 'whatsapp',
			conversation_id VARCHAR(100) NOT NULL DEFAULT '',
			customer_name VARCHAR(255) NOT NULL DEFAULT '',
			customer_phone VARCHAR(50) NOT NULL DEFAULT '',
			started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_message_at DATETIME NULL DEFAULT NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'open',
			assigned_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY conversation_id (conversation_id),
			KEY lead_id (lead_id)
		) $charset;";
		dbDelta( $sql_conv );

		$messages = $wpdb->prefix . 'slcrm_messages';
		$sql_msgs = "CREATE TABLE $messages (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			direction ENUM('inbound','outbound') NOT NULL DEFAULT 'inbound',
			message_id VARCHAR(100) NOT NULL DEFAULT '',
			text TEXT NOT NULL,
			message_type VARCHAR(50) NOT NULL DEFAULT 'text',
			media_url TEXT NOT NULL,
			status VARCHAR(50) NOT NULL DEFAULT 'received',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY message_id (message_id),
			KEY conversation_id (conversation_id)
		) $charset;";
		dbDelta( $sql_msgs );

		$conversions = $wpdb->prefix . 'slcrm_conversions';
		$sql_conv = "CREATE TABLE $conversions (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			crm_action VARCHAR(50) NOT NULL DEFAULT '',
			label VARCHAR(100) NOT NULL DEFAULT '',
			google_ads_label VARCHAR(100) NOT NULL DEFAULT '',
			ga4_event VARCHAR(100) NOT NULL DEFAULT '',
			enabled TINYINT(1) NOT NULL DEFAULT 1,
			category VARCHAR(30) NOT NULL DEFAULT 'interaction',
			sort_order INT(11) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY crm_action (crm_action)
		) $charset;";
		dbDelta( $sql_conv );

		$forms = $wpdb->prefix . 'slcrm_form_tracking';
		$sql_forms = "CREATE TABLE $forms (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			form_name VARCHAR(100) NOT NULL DEFAULT '',
			selector VARCHAR(255) NOT NULL DEFAULT '',
			event_type VARCHAR(30) NOT NULL DEFAULT 'submit',
			crm_action VARCHAR(50) NOT NULL DEFAULT '',
			enabled TINYINT(1) NOT NULL DEFAULT 1,
			sort_order INT(11) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset;";
		dbDelta( $sql_forms );
	}

	public function maybe_run_migrations() {
		$stored = get_option( 'smart_lead_crm_db_version' );
		if ( version_compare( $stored, SMART_LEAD_CRM_DB_VERSION, '<' ) ) {
			$this->create_tables();
			$this->seed_default_conversions();
			update_option( 'smart_lead_crm_db_version', SMART_LEAD_CRM_DB_VERSION );
		}
	}

	public function seed_default_conversions() {
		global $wpdb;
		$table = $wpdb->prefix . 'slcrm_conversions';
		$existing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
		if ( $existing > 0 ) return;

		$presets = smart_lead_crm()->helper->get_conversion_presets();
		$order = 0;
		foreach ( $presets as $p ) {
			$wpdb->insert( $table, array(
				'crm_action'        => $p['crm_action'],
				'label'              => $p['label'],
				'google_ads_label'   => '',
				'ga4_event'          => $p['ga4_event'],
				'enabled'            => $p['default_enabled'] ? 1 : 0,
				'category'           => $p['category'],
				'sort_order'         => $order++,
				'created_at'         => current_time( 'mysql' ),
			) );
		}
	}

	public function add_default_options() {
		$defaults = smart_lead_crm()->settings->get_defaults();
		$prefix   = smart_lead_crm()->settings->get_prefix();
		foreach ( $defaults as $key => $val ) {
			if ( false === get_option( $prefix . $key ) ) {
				add_option( $prefix . $key, $val );
			}
		}
	}
}
