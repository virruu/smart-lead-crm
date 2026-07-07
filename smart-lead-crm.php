<?php
/**
 * Plugin Name:       Smart Lead CRM
 * Plugin URI:        https://example.com/smart-lead-crm
 * Description:       A business operating system for lead capture, tracking, and reporting. Automatically captures GCLID, GBRAID, WBRAID, UTM parameters, device, browser, and referrer data.
 * Version:           1.2.0
 * Author:            Smart Lead CRM
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       smart-lead-crm
 * Domain Path:       /languages
 *
 * @package SmartLeadCRM
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'SMART_LEAD_CRM_VERSION', '1.4.0' );
define( 'SMART_LEAD_CRM_DB_VERSION', '1.4.0' );
define( 'SMART_LEAD_CRM_PLUGIN_FILE', __FILE__ );
define( 'SMART_LEAD_CRM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SMART_LEAD_CRM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SMART_LEAD_CRM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin bootstrap class.
 *
 * @package SmartLeadCRM
 */
final class Smart_Lead_CRM {

	/**
	 * Single instance of the plugin.
	 *
	 * @var Smart_Lead_CRM|null
	 */
	private static $instance = null;

	/**
	 * Loader instance.
	 *
	 * @var Smart_Lead_CRM_Loader|null
	 */
	public $loader = null;

	/**
	 * Installer instance.
	 *
	 * @var Smart_Lead_CRM_Installer|null
	 */
	public $installer = null;

	/**
	 * Database instance.
	 *
	 * @var Smart_Lead_CRM_DB|null
	 */
	public $db = null;

	/**
	 * Admin instance.
	 *
	 * @var Smart_Lead_CRM_Admin|null
	 */
	public $admin = null;

	/**
	 * Settings instance.
	 *
	 * @var Smart_Lead_CRM_Settings|null
	 */
	public $settings = null;

	/**
	 * Tracker instance.
	 *
	 * @var Smart_Lead_CRM_Tracker|null
	 */
	public $tracker = null;

	/**
	 * Helper instance.
	 *
	 * @var Smart_Lead_CRM_Helper|null
	 */
	public $helper = null;

	/**
	 * AJAX instance.
	 *
	 * @var Smart_Lead_CRM_Ajax|null
	 */
	public $ajax = null;

	/**
	 * Export instance.
	 *
	 * @var Smart_Lead_CRM_Export|null
	 */
	public $export = null;

	/**
	 * Attribution instance.
	 *
	 * @var Smart_Lead_CRM_Attribution|null
	 */
	public $attribution = null;

	/**
	 * Messaging instance (WhatsApp today, Messenger/Telegram tomorrow).
	 *
	 * @var Smart_Lead_CRM_Messaging|null
	 */
	public $messaging = null;

	/**
	 * Get the single instance.
	 *
	 * @return Smart_Lead_CRM
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->define_constants();
			self::$instance->includes();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Define additional constants (kept here for clarity).
	 */
	private function define_constants() {
		// Already defined at the top of the file.
	}

	/**
	 * Load required files.
	 */
	private function includes() {
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-loader.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-install.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-db.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-admin.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-settings.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-tracker.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-helper.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-ajax.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-export.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-attribution.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-conversation.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/class-messaging.php';
		require_once SMART_LEAD_CRM_PLUGIN_DIR . 'includes/functions.php';
	}

	/**
	 * Initialize components.
	 */
	private function init() {
		$this->loader   = new Smart_Lead_CRM_Loader();
		$this->helper   = new Smart_Lead_CRM_Helper();
		$this->db       = new Smart_Lead_CRM_DB();
		$this->installer = new Smart_Lead_CRM_Installer();
		$this->settings = new Smart_Lead_CRM_Settings();
		$this->admin    = new Smart_Lead_CRM_Admin();
		$this->tracker  = new Smart_Lead_CRM_Tracker();
		$this->ajax     = new Smart_Lead_CRM_Ajax();
		$this->export   = new Smart_Lead_CRM_Export();
		$this->attribution = new Smart_Lead_CRM_Attribution();
		$this->messaging   = new Smart_Lead_CRM_Messaging();

		// Activation / deactivation hooks.
		register_activation_hook( SMART_LEAD_CRM_PLUGIN_FILE, array( $this->installer, 'activate' ) );
		register_deactivation_hook( SMART_LEAD_CRM_PLUGIN_FILE, array( $this->installer, 'deactivate' ) );

		// Run components.
		$this->loader->run();
	}

	/**
	 * Cloning is forbidden.
	 */
	private function __clone() {}

	/**
	 * Unserializing is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __METHOD__, esc_html__( 'Cheatin&#8217; huh?', 'smart-lead-crm' ), '1.0.0' );
	}
}

/**
 * Initialize the plugin.
 *
 * @return Smart_Lead_CRM
 */
function smart_lead_crm() {
	return Smart_Lead_CRM::instance();
}

// Kick off.
smart_lead_crm();
