<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Conversions {

	public function get_config() {
		$db = slcrm_db();
		$conversions = $db->get_enabled_conversions();
		$forms = $db->get_enabled_forms();

		$conv_map = array();
		foreach ( $conversions as $c ) {
			$conv_map[] = array(
				'crm_action'  => $c->crm_action,
				'label'       => $c->label,
				'ga4_event'   => $c->ga4_event,
				'ads_label'   => $c->google_ads_label,
			);
		}

		$form_map = array();
		foreach ( $forms as $f ) {
			$form_map[] = array(
				'selector'   => $f->selector,
				'event_type' => $f->event_type,
				'crm_action' => $f->crm_action,
				'form_name'  => $f->form_name,
			);
		}

		return array(
			'conversions' => $conv_map,
			'forms'       => $form_map,
			'ads_id'      => slcrm_get_setting( 'google_ads_conversion_id', '' ),
			'ga4_id'      => slcrm_get_setting( 'ga4_measurement_id', '' ),
		);
	}

	public function fire_conversion( $crm_action, $lead_id = 0 ) {
		$db = slcrm_db();
		$conversions = $db->get_enabled_conversions();
		$ads_id = slcrm_get_setting( 'google_ads_conversion_id', '' );
		$ga4_id = slcrm_get_setting( 'ga4_measurement_id', '' );

		$matched = null;
		foreach ( $conversions as $c ) {
			if ( $c->crm_action === $crm_action ) {
				$matched = $c;
				break;
			}
		}

		$scripts = array();

		if ( $matched && $ads_id && $matched->google_ads_label ) {
			$scripts[] = sprintf(
				"gtag('event','conversion',{'send_to':'%s/%s'});",
				esc_js( $ads_id ),
				esc_js( $matched->google_ads_label )
			);
		}

		if ( $matched && $ga4_id && $matched->ga4_event ) {
			$scripts[] = sprintf(
				"gtag('event','%s',{'send_to':'%s'});",
				esc_js( $matched->ga4_event ),
				esc_js( $ga4_id )
			);
		}

		return $scripts;
	}
}
