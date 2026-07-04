<?php
/**
 * Attribution class - enterprise-grade lead source attribution engine.
 *
 * Determines the marketing source of a lead using a strict priority order so
 * that every visit is attributed to the most specific, highest-intent channel
 * that can be detected from the available tracking data.
 *
 * Priority order (highest first):
 *   1. Google Ads        — gclid / gbraid / wbraid present
 *   2. UTM Source        — utm_source parameter (maps to known networks)
 *   3. Google Business Profile — referer from maps.google.com / l.google.com
 *   4. Organic Search    — referer from a search engine
 *   5. Facebook          — referer from facebook.com / m.facebook.com / fb
 *   6. Instagram         — referer from instagram.com
 *   7. WhatsApp          — lead_action = whatsapp
 *   8. Referral          — referer from any other external site
 *   9. Direct            — no usable signal
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Attribution class.
 *
 * @package SmartLeadCRM
 */
class Smart_Lead_CRM_Attribution {

	/**
	 * Attribution result keys.
	 */
	const SOURCE_GOOGLE_ADS = 'google_ads';
	const SOURCE_UTM        = 'utm';
	const SOURCE_GBP        = 'gbp';
	const SOURCE_ORGANIC    = 'organic';
	const SOURCE_FACEBOOK   = 'facebook';
	const SOURCE_INSTAGRAM  = 'instagram';
	const SOURCE_WHATSAPP   = 'whatsapp';
	const SOURCE_REFERRAL   = 'referral';
	const SOURCE_DIRECT     = 'direct';
	const SOURCE_MANUAL     = 'manual';

	/**
	 * Known organic search engine referer patterns.
	 *
	 * @var array
	 */
	private $search_engines = array(
		'google.'         => 'Google',
		'bing.com'        => 'Bing',
		'yahoo.com'       => 'Yahoo',
		'duckduckgo.com'  => 'DuckDuckGo',
		'yandex.com'      => 'Yandex',
		'baidu.com'       => 'Baidu',
		'ecosia.org'      => 'Ecosia',
		'search.brave.com'=> 'Brave',
	);

	/**
	 * Google Business Profile / Maps referer patterns.
	 *
	 * @var array
	 */
	private $gbp_patterns = array(
		'maps.google.com',
		'www.google.com/maps',
		'google.com/maps',
		'l.google.com',
		'business.google.com',
	);

	/**
	 * Facebook referer patterns.
	 *
	 * @var array
	 */
	private $facebook_patterns = array(
		'facebook.com',
		'm.facebook.com',
		'web.facebook.com',
		'fb.com',
		'l.facebook.com',
	);

	/**
	 * Instagram referer patterns.
	 *
	 * @var array
	 */
	private $instagram_patterns = array(
		'instagram.com',
		'l.instagram.com',
	);

	/**
	 * Resolve the full attribution for a set of tracking signals.
	 *
	 * Returns an associative array with:
	 *   - 'source'      : normalized source key (google_ads, organic, gbp, ...)
	 *   - 'source_label': human-readable label
	 *   - 'medium'      : detected medium (cpc, organic, referral, direct, ...)
	 *   - 'campaign'    : utm_campaign if present
	 *   - 'ad_group'    : utm_content mapped to ad group if present
	 *   - 'keyword'     : utm_term if present
	 *
	 * @param array $signals Tracking signals. Keys: gclid, gbraid, wbraid,
	 *                       utm_source, utm_medium, utm_campaign, utm_term,
	 *                       utm_content, referer, lead_action.
	 * @return array
	 */
	public function resolve( array $signals ) {
		$gclid        = isset( $signals['gclid'] ) ? trim( (string) $signals['gclid'] ) : '';
		$gbraid       = isset( $signals['gbraid'] ) ? trim( (string) $signals['gbraid'] ) : '';
		$wbraid       = isset( $signals['wbraid'] ) ? trim( (string) $signals['wbraid'] ) : '';
		$utm_source   = isset( $signals['utm_source'] ) ? trim( (string) $signals['utm_source'] ) : '';
		$utm_medium   = isset( $signals['utm_medium'] ) ? trim( (string) $signals['utm_medium'] ) : '';
		$utm_campaign = isset( $signals['utm_campaign'] ) ? trim( (string) $signals['utm_campaign'] ) : '';
		$utm_term     = isset( $signals['utm_term'] ) ? trim( (string) $signals['utm_term'] ) : '';
		$utm_content  = isset( $signals['utm_content'] ) ? trim( (string) $signals['utm_content'] ) : '';
		$referer      = isset( $signals['referer'] ) ? trim( (string) $signals['referer'] ) : '';
		$lead_action  = isset( $signals['lead_action'] ) ? trim( (string) $signals['lead_action'] ) : '';

		$result = array(
			'source'       => self::SOURCE_DIRECT,
			'source_label' => 'Direct',
			'medium'       => 'direct',
			'campaign'     => $utm_campaign,
			'ad_group'     => '',
			'keyword'      => $utm_term,
		);

		// 1. Google Ads — any of gclid / gbraid / wbraid means paid Google traffic.
		if ( '' !== $gclid || '' !== $gbraid || '' !== $wbraid ) {
			$result['source']       = self::SOURCE_GOOGLE_ADS;
			$result['source_label'] = 'Google Ads';
			$result['medium']       = 'cpc';
			// utm_content often carries the ad group id on Google Ads.
			$result['ad_group'] = $utm_content;
			return $result;
		}

		// 2. UTM source — explicit campaign tagging wins over referer guessing.
		if ( '' !== $utm_source ) {
			$src = strtolower( $utm_source );

			// Google-tagged UTMs that are NOT ads (no gclid) are organic/paid search
			// depending on medium. If medium is cpc/ppc, treat as google_ads.
			if ( false !== strpos( $src, 'google' ) ) {
				$med = strtolower( $utm_medium );
				if ( in_array( $med, array( 'cpc', 'ppc', 'paid' ), true ) ) {
					$result['source']       = self::SOURCE_GOOGLE_ADS;
					$result['source_label'] = 'Google Ads';
					$result['medium']       = 'cpc';
				} else {
					$result['source']       = self::SOURCE_ORGANIC;
					$result['source_label'] = 'Organic Search';
					$result['medium']       = 'organic';
				}
				$result['ad_group'] = $utm_content;
				return $result;
			}

			if ( false !== strpos( $src, 'facebook' ) || false !== strpos( $src, 'fb' ) ) {
				$result['source']       = self::SOURCE_FACEBOOK;
				$result['source_label'] = 'Facebook';
				$result['medium']       = '' !== $utm_medium ? $utm_medium : 'social';
				$result['ad_group']     = $utm_content;
				return $result;
			}

			if ( false !== strpos( $src, 'instagram' ) || false !== strpos( $src, 'ig' ) ) {
				$result['source']       = self::SOURCE_INSTAGRAM;
				$result['source_label'] = 'Instagram';
				$result['medium']       = '' !== $utm_medium ? $utm_medium : 'social';
				$result['ad_group']     = $utm_content;
				return $result;
			}

			// Any other utm_source is a referral/campaign.
			$result['source']       = self::SOURCE_REFERRAL;
			$result['source_label'] = 'Referral';
			$result['medium']       = '' !== $utm_medium ? $utm_medium : 'referral';
			$result['ad_group']     = $utm_content;
			return $result;
		}

		// 3. Google Business Profile — check referer before generic organic.
		if ( '' !== $referer ) {
			$ref = strtolower( $referer );

			if ( $this->matches_any( $ref, $this->gbp_patterns ) ) {
				$result['source']       = self::SOURCE_GBP;
				$result['source_label'] = 'Google Business Profile';
				$result['medium']       = 'local';
				return $result;
			}

			// 4. Organic search.
			$engine = $this->matches_search_engine( $ref );
			if ( false !== $engine ) {
				$result['source']       = self::SOURCE_ORGANIC;
				$result['source_label'] = 'Organic Search';
				$result['medium']       = 'organic';
				return $result;
			}

			// 5. Facebook.
			if ( $this->matches_any( $ref, $this->facebook_patterns ) ) {
				$result['source']       = self::SOURCE_FACEBOOK;
				$result['source_label'] = 'Facebook';
				$result['medium']       = 'social';
				return $result;
			}

			// 6. Instagram.
			if ( $this->matches_any( $ref, $this->instagram_patterns ) ) {
				$result['source']       = self::SOURCE_INSTAGRAM;
				$result['source_label'] = 'Instagram';
				$result['medium']       = 'social';
				return $result;
			}

			// 8. Referral — any other external referer.
			if ( ! $this->is_internal_referer( $ref ) ) {
				$result['source']       = self::SOURCE_REFERRAL;
				$result['source_label'] = 'Referral';
				$result['medium']       = 'referral';
				return $result;
			}
		}

		// 7. WhatsApp action with no other signal.
		if ( 'whatsapp' === $lead_action ) {
			$result['source']       = self::SOURCE_WHATSAPP;
			$result['source_label'] = 'WhatsApp Direct';
			$result['medium']       = 'direct';
			return $result;
		}

		// 9. Direct — nothing detectable.
		$result['source']       = self::SOURCE_DIRECT;
		$result['source_label'] = 'Direct';
		$result['medium']       = 'direct';
		return $result;
	}

	/**
	 * Check whether a referer string matches any pattern in a list.
	 *
	 * @param string $haystack Lowercased referer.
	 * @param array  $patterns Patterns to match.
	 * @return bool
	 */
	private function matches_any( $haystack, array $patterns ) {
		foreach ( $patterns as $pattern ) {
			if ( false !== strpos( $haystack, $pattern ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check whether a referer is a known search engine.
	 *
	 * @param string $referer Lowercased referer.
	 * @return string|false Engine name or false.
	 */
	private function matches_search_engine( $referer ) {
		foreach ( $this->search_engines as $pattern => $name ) {
			if ( false !== strpos( $referer, $pattern ) ) {
				return $name;
			}
		}
		return false;
	}

	/**
	 * Check whether a referer is internal (same site).
	 *
	 * @param string $referer Lowercased referer.
	 * @return bool
	 */
	private function is_internal_referer( $referer ) {
		$host = wp_parse_url( $referer, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return false;
		}
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		return ( $host === $site_host );
	}

	/**
	 * Decide whether a new attribution should overwrite an existing lead's
	 * attribution. We upgrade to a higher-priority source but never downgrade.
	 *
	 * Priority rank: google_ads(1) > utm(2) > gbp(3) > organic(4) >
	 *                facebook(5) > instagram(6) > whatsapp(7) > referral(8) >
	 *                direct(9) > manual(10)
	 *
	 * @param string $current Current source key on the lead.
	 * @param string $new      Newly resolved source key.
	 * @return bool True if the lead should be updated with the new source.
	 */
	public function should_upgrade( $current, $new ) {
		$rank = array(
			self::SOURCE_GOOGLE_ADS => 1,
			self::SOURCE_UTM        => 2,
			self::SOURCE_GBP        => 3,
			self::SOURCE_ORGANIC    => 4,
			self::SOURCE_FACEBOOK   => 5,
			self::SOURCE_INSTAGRAM  => 6,
			self::SOURCE_WHATSAPP   => 7,
			self::SOURCE_REFERRAL   => 8,
			self::SOURCE_DIRECT     => 9,
			self::SOURCE_MANUAL     => 10,
		);

		$current_rank = isset( $rank[ $current ] ) ? $rank[ $current ] : 10;
		$new_rank     = isset( $rank[ $new ] ) ? $rank[ $new ] : 10;

		return $new_rank < $current_rank;
	}
}
