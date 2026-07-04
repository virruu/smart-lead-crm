<?php
/**
 * Tracker class - captures GCLID, GBRAID, WBRAID, UTM, cookies, sessions.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracker class.
 *
 * @package SmartLeadCRM
 */
class Smart_Lead_CRM_Tracker {

	/**
	 * Cookie names to track.
	 *
	 * @var array
	 */
	private $cookie_keys = array(
		'slcrm_gclid',
		'slcrm_gbraid',
		'slcrm_wbraid',
		'slcrm_utm_source',
		'slcrm_utm_campaign',
		'slcrm_utm_medium',
		'slcrm_utm_term',
		'slcrm_utm_content',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'capture_tracking_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_tracker_script' ) );
	}

	/**
	 * Capture tracking data from URL parameters and store in cookies.
	 * Also generates a persistent visitor_id (UUID v4, 365 days).
	 */
	public function capture_tracking_data() {
		if ( is_admin() ) {
			return;
		}

		// Generate or retrieve visitor_id (365 days).
		$visitor_id = isset( $_COOKIE['slcrm_visitor_id'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['slcrm_visitor_id'] ) ) : '';
		if ( empty( $visitor_id ) || ! $this->is_valid_uuid( $visitor_id ) ) {
			$visitor_id = wp_generate_uuid4();
			$this->set_cookie( 'slcrm_visitor_id', $visitor_id, time() + ( 365 * DAY_IN_SECONDS ) );
		}

		$settings = smart_lead_crm()->settings;
		$duration = (int) $settings->get( 'cookie_duration', 90 );
		$duration = max( 1, min( 365, $duration ) );
		$expire   = time() + ( $duration * DAY_IN_SECONDS );

		// Capture GCLID / GBRAID / WBRAID.
		if ( 'yes' === $settings->get( 'capture_gclid', 'yes' ) ) {
			$this->set_cookie_from_param( 'gclid', 'slcrm_gclid', $expire );
			$this->set_cookie_from_param( 'gbraid', 'slcrm_gbraid', $expire );
			$this->set_cookie_from_param( 'wbraid', 'slcrm_wbraid', $expire );
		}

		// Capture UTM parameters.
		if ( 'yes' === $settings->get( 'capture_utm', 'yes' ) ) {
			$this->set_cookie_from_param( 'utm_source', 'slcrm_utm_source', $expire );
			$this->set_cookie_from_param( 'utm_campaign', 'slcrm_utm_campaign', $expire );
			$this->set_cookie_from_param( 'utm_medium', 'slcrm_utm_medium', $expire );
			$this->set_cookie_from_param( 'utm_term', 'slcrm_utm_term', $expire );
			$this->set_cookie_from_param( 'utm_content', 'slcrm_utm_content', $expire );
		}

		// Store landing page and referer in cookies (first visit only).
		if ( ! isset( $_COOKIE['slcrm_landing_page'] ) ) {
			$landing_page = $this->get_current_url();
			$this->set_cookie( 'slcrm_landing_page', $landing_page, $expire );
		}

		if ( ! isset( $_COOKIE['slcrm_referer'] ) ) {
			$referer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
			$this->set_cookie( 'slcrm_referer', $referer, $expire );
		}

		// Store device and browser info.
		if ( ! isset( $_COOKIE['slcrm_device'] ) ) {
			$this->set_cookie( 'slcrm_device', $this->detect_device(), $expire );
		}
		if ( ! isset( $_COOKIE['slcrm_browser'] ) ) {
			$this->set_cookie( 'slcrm_browser', $this->detect_browser(), $expire );
		}
	}

	/**
	 * Check if a string is a valid UUID v4.
	 *
	 * @param string $uuid UUID string.
	 * @return bool
	 */
	private function is_valid_uuid( $uuid ) {
		return (bool) preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid );
	}

	/**
	 * Set a cookie from a URL parameter if present.
	 *
	 * @param string $param  URL parameter name.
	 * @param string $cookie Cookie name.
	 * @param int    $expire Expiration timestamp.
	 */
	private function set_cookie_from_param( $param, $cookie, $expire ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET[ $param ] ) && ! empty( $_GET[ $param ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$value = sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
			$this->set_cookie( $cookie, $value, $expire );
		}
	}

	/**
	 * Set a cookie safely.
	 *
	 * @param string $name   Cookie name.
	 * @param string $value  Cookie value.
	 * @param int    $expire Expiration timestamp.
	 */
	private function set_cookie( $name, $value, $expire ) {
		if ( headers_sent() ) {
			return;
		}
		setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
		$_COOKIE[ $name ] = $value;
	}

	/**
	 * Get all tracking data from cookies.
	 *
	 * @return array
	 */
	public function get_tracking_data() {
		$data = array();
		foreach ( $this->cookie_keys as $key ) {
			$data[ $key ] = isset( $_COOKIE[ $key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) ) : '';
		}
		$data['slcrm_landing_page'] = isset( $_COOKIE['slcrm_landing_page'] ) ? sanitize_url( wp_unslash( $_COOKIE['slcrm_landing_page'] ) ) : '';
		$data['slcrm_referer']      = isset( $_COOKIE['slcrm_referer'] ) ? sanitize_url( wp_unslash( $_COOKIE['slcrm_referer'] ) ) : '';
		$data['slcrm_device']       = isset( $_COOKIE['slcrm_device'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['slcrm_device'] ) ) : '';
		$data['slcrm_browser']      = isset( $_COOKIE['slcrm_browser'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['slcrm_browser'] ) ) : '';
		return $data;
	}

	/**
	 * Get the current full URL.
	 *
	 * @return string
	 */
	private function get_current_url() {
		$scheme = ( ! empty( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) ? 'https' : 'http';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		return $scheme . '://' . $host . $uri;
	}

	/**
	 * Detect device type from user agent.
	 *
	 * @return string
	 */
	public function detect_device() {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( empty( $user_agent ) ) {
			return 'Unknown';
		}
		if ( preg_match( '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $user_agent ) ) {
			return 'Mobile';
		}
		if ( preg_match( '/android|ipad|playbook|silk/i', $user_agent ) ) {
			return 'Tablet';
		}
		return 'Desktop';
	}

	/**
	 * Detect browser from user agent.
	 *
	 * @return string
	 */
	public function detect_browser() {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( empty( $user_agent ) ) {
			return 'Unknown';
		}
		if ( strpos( $user_agent, 'Edg' ) !== false ) {
			return 'Microsoft Edge';
		} elseif ( strpos( $user_agent, 'Chrome' ) !== false ) {
			return 'Google Chrome';
		} elseif ( strpos( $user_agent, 'Firefox' ) !== false ) {
			return 'Mozilla Firefox';
		} elseif ( strpos( $user_agent, 'Safari' ) !== false ) {
			return 'Safari';
		} elseif ( strpos( $user_agent, 'MSIE' ) !== false || strpos( $user_agent, 'Trident' ) !== false ) {
			return 'Internet Explorer';
		} elseif ( strpos( $user_agent, 'Opera' ) !== false || strpos( $user_agent, 'OPR' ) !== false ) {
			return 'Opera';
		}
		return 'Unknown';
	}

	/**
	 * Maybe enqueue a small JS tracker script on the frontend.
	 */
	public function maybe_enqueue_tracker_script() {
		if ( is_admin() ) {
			return;
		}
		wp_enqueue_script(
			'smart-lead-crm-tracker',
			SMART_LEAD_CRM_PLUGIN_URL . 'assets/js/tracker.js',
			array(),
			SMART_LEAD_CRM_VERSION,
			true
		);

		wp_localize_script(
			'smart-lead-crm-tracker',
			'slcrmTracker',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'slcrm_public_nonce' ),
			)
		);
	}
}
