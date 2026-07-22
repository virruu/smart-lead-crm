<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Tracker {

	public function __construct() {
		add_action( 'template_redirect',  array( $this, 'capture_tracking_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_tracker_script' ) );
	}

	public function capture_tracking_data() {
		if ( is_admin() ) return;

		$duration = (int) slcrm_get_setting( 'cookie_duration', 90 );
		$duration = max( 1, min( 365, $duration ) );

		// Visitor ID (365-day cookie)
		$visitor_id = isset( $_COOKIE['slcrm_visitor_id'] )
			? sanitize_text_field( wp_unslash( $_COOKIE['slcrm_visitor_id'] ) )
			: '';
		if ( ! $visitor_id || ! $this->is_valid_uuid( $visitor_id ) ) {
			$visitor_id = $this->generate_uuid();
		}
		setcookie( 'slcrm_visitor_id', $visitor_id, time() + ( 365 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );

		// GCLID / GBRAID / WBRAID
		if ( 'yes' === slcrm_get_setting( 'capture_gclid', 'yes' ) ) {
			foreach ( array( 'gclid', 'gbraid', 'wbraid' ) as $param ) {
				$val = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : '';
				if ( $val ) {
					setcookie( 'slcrm_' . $param, $val, time() + ( $duration * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
				}
			}
		}

		// UTM params
		if ( 'yes' === slcrm_get_setting( 'capture_utm', 'yes' ) ) {
			foreach ( array( 'utm_source', 'utm_campaign', 'utm_medium', 'utm_term', 'utm_content' ) as $param ) {
				$val = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : '';
				if ( $val ) {
					setcookie( 'slcrm_' . $param, $val, time() + ( $duration * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
				}
			}
		}

		// First-visit cookies
		if ( ! isset( $_COOKIE['slcrm_landing_page'] ) ) {
			setcookie( 'slcrm_landing_page', $this->current_url(), time() + ( $duration * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
		}
		if ( ! isset( $_COOKIE['slcrm_referer'] ) ) {
			$ref = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
			setcookie( 'slcrm_referer', $ref, time() + ( $duration * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );

			// Capture organic search keyword from referer
			if ( 'yes' === slcrm_get_setting( 'capture_organic_keywords', 'yes' ) ) {
				$kw = smart_lead_crm()->attribution->extract_organic_keyword( $ref );
				if ( $kw ) {
					setcookie( 'slcrm_organic_keyword', $kw, time() + ( $duration * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
				}
			}
		}

		// Device + browser
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( ! isset( $_COOKIE['slcrm_device'] ) ) {
			setcookie( 'slcrm_device', self::detect_device( $ua ), time() + ( $duration * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
		}
		if ( ! isset( $_COOKIE['slcrm_browser'] ) ) {
			setcookie( 'slcrm_browser', self::detect_browser( $ua ), time() + ( $duration * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	public static function get_tracking_data() {
		$keys = array( 'visitor_id', 'gclid', 'gbraid', 'wbraid', 'utm_source', 'utm_campaign', 'utm_medium', 'utm_term', 'utm_content', 'landing_page', 'referer', 'device', 'browser', 'organic_keyword' );
		$data = array();
		foreach ( $keys as $k ) {
			$ck = 'slcrm_' . $k;
			$data[ $k ] = isset( $_COOKIE[ $ck ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $ck ] ) ) : '';
		}
		return $data;
	}

	public function maybe_enqueue_tracker_script() {
		if ( is_admin() ) return;
		wp_enqueue_script( 'slcrm-tracker', SMART_LEAD_CRM_PLUGIN_URL . 'assets/js/tracker.js', array(), SMART_LEAD_CRM_VERSION, true );

		$wa_number = preg_replace( '/[^0-9]/', '', slcrm_get_setting( 'whatsapp_business_number', '' ) );

		$conversions_obj = new Smart_Lead_CRM_Conversions();
		$config = $conversions_obj->get_config();

		wp_localize_script( 'slcrm-tracker', 'slcrmTracker', array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'slcrm_public_nonce' ),
			'businessNumber' => $wa_number,
			'conversions'    => $config['conversions'],
			'forms'          => $config['forms'],
			'adsId'          => $config['ads_id'],
			'ga4Id'          => $config['ga4_id'],
		) );

		if ( $wa_number ) {
			add_action( 'wp_head', function() use ( $wa_number ) {
				echo '<meta name="slcrm-wa-number" content="' . esc_attr( $wa_number ) . '" />' . "\n";
			}, 1 );
		}
	}

	public static function detect_device( $ua ) {
		if ( empty( $ua ) ) return 'Desktop';
		if ( preg_match( '/(tablet|ipad|playbook|silk)/i', $ua ) ) return 'Tablet';
		if ( preg_match( '/(mobile|android|iphone|ipod|blackberry|opera mini|silk)/i', $ua ) ) return 'Mobile';
		return 'Desktop';
	}

	public static function detect_browser( $ua ) {
		if ( empty( $ua ) ) return 'Unknown';
		if ( false !== strpos( $ua, 'Edg' ) )   return 'Edge';
		if ( false !== strpos( $ua, 'Chrome' ) ) return 'Chrome';
		if ( false !== strpos( $ua, 'Firefox' ) ) return 'Firefox';
		if ( false !== strpos( $ua, 'Safari' ) )  return 'Safari';
		if ( false !== strpos( $ua, 'MSIE' ) || false !== strpos( $ua, 'Trident' ) ) return 'IE';
		if ( false !== strpos( $ua, 'Opera' ) || false !== strpos( $ua, 'OPR' ) ) return 'Opera';
		return 'Unknown';
	}

	private function current_url() {
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		return $scheme . '://' . $host . $uri;
	}

	private function is_valid_uuid( $uuid ) {
		return (bool) preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid );
	}

	private function generate_uuid() {
		return sprintf(
			'%04x%04x-%04x-4%03x-%04x-%04x%04x%04x',
			mt_rand(0,0xffff), mt_rand(0,0xffff),
			mt_rand(0,0xffff),
			mt_rand(0,0xfff),
			mt_rand(0,0x3fff)|0x8000,
			mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
		);
	}
}
