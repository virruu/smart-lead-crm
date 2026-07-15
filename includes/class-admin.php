<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Admin {

	public function __construct() {
		add_action( 'admin_menu',            array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menus() {
		add_menu_page( 'Smart Lead CRM', 'Smart Lead CRM', 'manage_options', 'smart-lead-crm',
			array( $this, 'render_dashboard' ), 'dashicons-chart-area', 30 );

		add_submenu_page( 'smart-lead-crm', 'Dashboard', 'Dashboard', 'manage_options', 'smart-lead-crm',
			array( $this, 'render_dashboard' ) );
		add_submenu_page( 'smart-lead-crm', 'Leads',     'Leads',     'manage_options', 'smart-lead-crm-leads',
			array( $this, 'render_leads' ) );
		add_submenu_page( 'smart-lead-crm', 'WhatsApp',  'WhatsApp',  'manage_options', 'smart-lead-crm-whatsapp',
			array( $this, 'render_whatsapp' ) );
		add_submenu_page( 'smart-lead-crm', 'Reports',   'Reports',   'manage_options', 'smart-lead-crm-reports',
			array( $this, 'render_reports' ) );
		add_submenu_page( 'smart-lead-crm', 'Export',    'Export',    'manage_options', 'smart-lead-crm-export',
			array( $this, 'render_export' ) );
		add_submenu_page( 'smart-lead-crm', 'Settings',  'Settings',  'manage_options', 'smart-lead-crm-settings',
			array( $this, 'render_settings' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'smart-lead-crm' ) ) return;

		wp_enqueue_style( 'slcrm-admin', SMART_LEAD_CRM_PLUGIN_URL . 'assets/css/admin.css', array(), SMART_LEAD_CRM_VERSION );
		wp_enqueue_script( 'slcrm-admin', SMART_LEAD_CRM_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), SMART_LEAD_CRM_VERSION, true );
		wp_localize_script( 'slcrm-admin', 'slcrmAdmin', array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'slcrm_admin_nonce' ),
			'leadsUrl'      => admin_url( 'admin.php?page=smart-lead-crm-leads' ),
			'confirmDelete' => __( 'Delete this lead and all associated data?', 'smart-lead-crm' ),
		) );
		wp_enqueue_style( 'dashicons' );
	}

	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		include SMART_LEAD_CRM_PLUGIN_DIR . 'admin/dashboard.php';
	}

	public function render_leads() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		include SMART_LEAD_CRM_PLUGIN_DIR . 'admin/leads.php';
	}

	public function render_whatsapp() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		smart_lead_crm()->settings->render_whatsapp_page();
	}

	public function render_reports() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		include SMART_LEAD_CRM_PLUGIN_DIR . 'admin/reports.php';
	}

	public function render_export() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		include SMART_LEAD_CRM_PLUGIN_DIR . 'admin/export.php';
	}

	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		smart_lead_crm()->settings->render_settings_page();
	}
}
