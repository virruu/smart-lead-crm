<?php
/**
 * Plugin Name: Smart Lead CRM
 * Plugin URI:  https://smartleadcrm.com
 * Description: WhatsApp-first CRM with multi-mode connection, full attribution tracking, and customer intelligence. Fully automated lead capture — no forms needed.
 * Version:     1.4.0
 * Author:      Smart Lead CRM
 * License:     GPL-2.0+
 * Text Domain: smart-lead-crm
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SMART_LEAD_CRM_VERSION',    '1.4.0' );
define( 'SMART_LEAD_CRM_DB_VERSION', '1.4.0' );
define( 'SMART_LEAD_CRM_PLUGIN_FILE', __FILE__ );
define( 'SMART_LEAD_CRM_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'SMART_LEAD_CRM_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'SMART_LEAD_CRM_BASENAME',    plugin_basename( __FILE__ ) );

final class Smart_Lead_CRM {

	private static $instance = null;

	public $loader; public $installer; public $db; public $admin;
	public $settings; public $tracker; public $helper; public $ajax;
	public $export; public $attribution; public $messaging;

	public static function instance() {
		if ( null === self::$instance ) self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->init();
	}

	private function includes() {
		$d = SMART_LEAD_CRM_PLUGIN_DIR . 'includes/';
		require_once $d . 'class-loader.php';
		require_once $d . 'class-install.php';
		require_once $d . 'class-db.php';
		require_once $d . 'class-settings.php';
		require_once $d . 'class-helper.php';
		require_once $d . 'class-attribution.php';
		require_once $d . 'class-tracker.php';
		require_once $d . 'class-ajax.php';
		require_once $d . 'class-export.php';
		require_once $d . 'class-conversation.php';
		require_once $d . 'class-messaging.php';
		require_once $d . 'class-admin.php';
		require_once $d . 'functions.php';
	}

	private function init() {
		$this->loader      = new Smart_Lead_CRM_Loader();
		$this->installer   = new Smart_Lead_CRM_Installer();
		$this->db          = new Smart_Lead_CRM_DB();
		$this->settings    = new Smart_Lead_CRM_Settings();
		$this->helper      = new Smart_Lead_CRM_Helper();
		$this->attribution = new Smart_Lead_CRM_Attribution();
		$this->tracker     = new Smart_Lead_CRM_Tracker();
		$this->ajax        = new Smart_Lead_CRM_Ajax();
		$this->export      = new Smart_Lead_CRM_Export();
		$this->messaging   = new Smart_Lead_CRM_Messaging();
		$this->admin       = new Smart_Lead_CRM_Admin();

		register_activation_hook( SMART_LEAD_CRM_PLUGIN_FILE,   array( $this->installer, 'activate' ) );
		register_deactivation_hook( SMART_LEAD_CRM_PLUGIN_FILE, array( $this->installer, 'deactivate' ) );

		$this->loader->run();
	}
}

function smart_lead_crm() {
	return Smart_Lead_CRM::instance();
}
smart_lead_crm();
