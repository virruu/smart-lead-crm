<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Attribution {

	private $rank = array(
		'google_ads' => 1, 'utm'      => 2,  'gbp'       => 3,
		'organic'    => 4, 'facebook' => 5,  'instagram' => 6,
		'whatsapp'   => 7, 'referral' => 8,  'direct'    => 9, 'manual' => 10,
	);

	public function resolve( $signals ) {
		$gclid     = $signals['gclid']     ?? '';
		$gbraid    = $signals['gbraid']    ?? '';
		$wbraid    = $signals['wbraid']    ?? '';
		$utm_src   = $signals['utm_source'] ?? '';
		$utm_med   = $signals['utm_medium'] ?? '';
		$utm_camp  = $signals['utm_campaign'] ?? '';
		$utm_term  = $signals['utm_term'] ?? '';
		$referer   = $signals['referer']   ?? '';
		$action    = $signals['lead_action'] ?? '';

		$source = 'direct';
		$medium = '';
		$campaign = $utm_camp;
		$ad_group = '';
		$keyword = $utm_term;

		// 1. Google Ads
		if ( $gclid || $gbraid || $wbraid ) {
			$source = 'google_ads';
			$medium = 'cpc';
		}
		// 2. UTM
		elseif ( $utm_src ) {
			$src_low = strtolower( $utm_src );
			if ( 'google' === $src_low && 'cpc' === strtolower( $utm_med ) ) {
				$source = 'google_ads'; $medium = 'cpc';
			} elseif ( 'google' === $src_low ) {
				$source = 'organic'; $medium = $utm_med ?: 'organic';
			} elseif ( in_array( $src_low, array( 'facebook','fb' ), true ) ) {
				$source = 'facebook'; $medium = $utm_med ?: 'social';
			} elseif ( in_array( $src_low, array( 'instagram','ig' ), true ) ) {
				$source = 'instagram'; $medium = $utm_med ?: 'social';
			} else {
				$source = 'referral'; $medium = $utm_med ?: 'referral';
			}
		}
		// 3. GBP
		elseif ( $this->matches_any( $referer, array( 'maps.google.com', 'l.google.com', 'business.google.com' ) ) ) {
			$source = 'gbp'; $medium = 'local';
		}
		// 4. Organic search
		elseif ( $this->matches_search_engine( $referer ) ) {
			$source = 'organic'; $medium = 'organic';
		}
		// 5. Facebook
		elseif ( $this->matches_any( $referer, array( 'facebook.com', 'm.facebook.com', 'fb.com', 'l.facebook.com' ) ) ) {
			$source = 'facebook'; $medium = 'social';
		}
		// 6. Instagram
		elseif ( $this->matches_any( $referer, array( 'instagram.com', 'l.instagram.com' ) ) ) {
			$source = 'instagram'; $medium = 'social';
		}
		// 7. WhatsApp action
		elseif ( 'whatsapp' === $action ) {
			$source = 'whatsapp'; $medium = 'chat';
		}
		// 8. Referral
		elseif ( $referer && ! $this->is_internal_referer( $referer ) ) {
			$source = 'referral'; $medium = 'referral';
		}

		$label = smart_lead_crm()->helper->get_source_label( $source );

		return array(
			'source'       => $source,
			'source_label' => $label,
			'medium'       => $medium,
			'campaign'     => $campaign,
			'ad_group'     => $ad_group,
			'keyword'      => $keyword,
		);
	}

	public function should_upgrade( $current, $new ) {
		$cur = $this->rank[ $current ] ?? 99;
		$nw  = $this->rank[ $new ]     ?? 99;
		return $nw < $cur;
	}

	private function matches_any( $haystack, $needles ) {
		$hay = strtolower( $haystack );
		foreach ( $needles as $n ) {
			if ( false !== strpos( $hay, strtolower( $n ) ) ) return true;
		}
		return false;
	}

	private function matches_search_engine( $referer ) {
		$engines = array( 'google.', 'bing.com', 'yahoo.com', 'duckduckgo.com', 'yandex.', 'baidu.com', 'ask.com', 'aol.com' );
		return $this->matches_any( $referer, $engines );
	}

	private function is_internal_referer( $referer ) {
		$home = wp_parse_url( home_url(), PHP_URL_HOST );
		$ref  = wp_parse_url( $referer, PHP_URL_HOST );
		return $home && $ref && $home === $ref;
	}
}
