<?php
/**
 * Admin class - creates menus, dashboard, and enqueues assets.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 *
 * @package SmartLeadCRM
 */
class Smart_Lead_CRM_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register admin menus.
	 */
	public function register_menus() {
		// Main Dashboard menu.
		add_menu_page(
			__( 'Smart Lead CRM', 'smart-lead-crm' ),
			__( 'Smart Lead CRM', 'smart-lead-crm' ),
			'manage_options',
			'smart-lead-crm',
			array( $this, 'render_dashboard' ),
			'dashicons-chart-area',
			26
		);

		// Dashboard submenu (same as main).
		add_submenu_page(
			'smart-lead-crm',
			__( 'Dashboard', 'smart-lead-crm' ),
			__( 'Dashboard', 'smart-lead-crm' ),
			'manage_options',
			'smart-lead-crm',
			array( $this, 'render_dashboard' )
		);

		// Leads submenu.
		add_submenu_page(
			'smart-lead-crm',
			__( 'Leads', 'smart-lead-crm' ),
			__( 'Leads', 'smart-lead-crm' ),
			'manage_options',
			'smart-lead-crm-leads',
			array( $this, 'render_leads' )
		);

		// Reports submenu.
		add_submenu_page(
			'smart-lead-crm',
			__( 'Reports', 'smart-lead-crm' ),
			__( 'Reports', 'smart-lead-crm' ),
			'manage_options',
			'smart-lead-crm-reports',
			array( $this, 'render_reports' )
		);

		// Export submenu.
		add_submenu_page(
			'smart-lead-crm',
			__( 'Export', 'smart-lead-crm' ),
			__( 'Export', 'smart-lead-crm' ),
			'manage_options',
			'smart-lead-crm-export',
			array( $this, 'render_export' )
		);

		// Settings submenu.
		add_submenu_page(
			'smart-lead-crm',
			__( 'Settings', 'smart-lead-crm' ),
			__( 'Settings', 'smart-lead-crm' ),
			'manage_options',
			'smart-lead-crm-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Render the dashboard page.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'smart-lead-crm' ) );
		}
		$plugin = smart_lead_crm();
		$stats  = $plugin->db->get_dashboard_stats();
		include SMART_LEAD_CRM_PLUGIN_DIR . 'admin/dashboard.php';
	}

	/**
	 * Render the leads page.
	 */
	public function render_leads() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'smart-lead-crm' ) );
		}
		include SMART_LEAD_CRM_PLUGIN_DIR . 'admin/leads.php';
	}

	/**
	 * Render the reports page.
	 */
	public function render_reports() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'smart-lead-crm' ) );
		}
		include SMART_LEAD_CRM_PLUGIN_DIR . 'admin/reports.php';
	}

	/**
	 * Render the export page.
	 */
	public function render_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'smart-lead-crm' ) );
		}
		include SMART_LEAD_CRM_PLUGIN_DIR . 'admin/export.php';
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'smart-lead-crm' ) );
		}
		$settings = smart_lead_crm()->settings;
		$settings->render_settings_page();
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		// Only load on our pages.
		if ( false === strpos( $hook, 'smart-lead-crm' ) ) {
			return;
		}

		wp_enqueue_style(
			'smart-lead-crm-admin',
			SMART_LEAD_CRM_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			SMART_LEAD_CRM_VERSION
		);

		wp_enqueue_script(
			'smart-lead-crm-admin',
			SMART_LEAD_CRM_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			SMART_LEAD_CRM_VERSION,
			true
		);

		wp_localize_script(
			'smart-lead-crm-admin',
			'slcrmAdmin',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'slcrm_admin_nonce' ),
				'leadsUrl'     => admin_url( 'admin.php?page=smart-lead-crm-leads' ),
				'confirmDelete' => __( 'Delete this lead and all related data?', 'smart-lead-crm' ),
			)
		);
	}

	/**
	 * Enqueue frontend assets for the lead form shortcode.
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style(
			'smart-lead-crm-frontend',
			SMART_LEAD_CRM_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			SMART_LEAD_CRM_VERSION
		);
	}
}
