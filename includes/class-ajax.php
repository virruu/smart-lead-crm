<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Ajax {

	public function __construct() {
		// Public
		add_action( 'wp_ajax_slcrm_auto_lead',    array( $this, 'auto_create_lead' ) );
		add_action( 'wp_ajax_nopriv_slcrm_auto_lead', array( $this, 'auto_create_lead' ) );

		// Admin
		add_action( 'wp_ajax_slcrm_update_lead',       array( $this, 'update_lead' ) );
		add_action( 'wp_ajax_slcrm_delete_lead',       array( $this, 'delete_lead' ) );
		add_action( 'wp_ajax_slcrm_add_note',          array( $this, 'add_note' ) );
		add_action( 'wp_ajax_slcrm_add_booking',       array( $this, 'add_booking' ) );
		add_action( 'wp_ajax_slcrm_update_booking',    array( $this, 'update_booking' ) );
	}

	public function auto_create_lead() {
		check_ajax_referer( 'slcrm_public_nonce', 'nonce' );

		$visitor_id = sanitize_text_field( wp_unslash( $_POST['visitor_id'] ?? '' ) );
		$action_type = sanitize_text_field( wp_unslash( $_POST['lead_action'] ?? '' ) );
		$phone      = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );

		if ( ! $visitor_id ) wp_send_json_error( 'Missing visitor_id' );

		$db         = slcrm_db();
		$helper     = slcrm_helper();
		$attribution = smart_lead_crm()->attribution;

		$signals = $this->collect_cookie_tracking();
		$signals['lead_action'] = $action_type;
		$attr = $attribution->resolve( $signals );

		$existing = $db->find_lead_by_visitor_today( $visitor_id );

		if ( $existing ) {
			$update = array();
			if ( $phone && ! $existing->phone ) {
				$update['phone'] = $phone;
			}
			if ( $attribution->should_upgrade( $existing->lead_source, $attr['source'] ) ) {
				$update['lead_source'] = $attr['source'];
				$update['medium']      = $attr['medium'];
				$update['campaign']    = $attr['campaign'];
				$update['ad_group']    = $attr['ad_group'];
				$update['keyword']     = $attr['keyword'];
			}
			$update['last_updated'] = current_time( 'mysql' );
			$db->update_lead( $existing->id, $update );

			$this->log_tracking( $existing->id, $visitor_id, $signals );
			wp_send_json_success( array( 'lead_id' => $existing->id, 'action' => 'updated' ) );
		}

		$lead_data = array(
			'name'         => $phone ? 'Visitor (' . $phone . ')' : 'Website Visitor',
			'phone'        => $phone ?: '',
			'status'       => 'new_lead',
			'lead_source'  => $attr['source'],
			'medium'       => $attr['medium'],
			'campaign'     => $attr['campaign'],
			'ad_group'     => $attr['ad_group'],
			'keyword'      => $attr['keyword'],
			'gclid'        => $signals['gclid'] ?? '',
			'gbraid'       => $signals['gbraid'] ?? '',
			'wbraid'       => $signals['wbraid'] ?? '',
			'utm_source'   => $signals['utm_source'] ?? '',
			'utm_campaign' => $signals['utm_campaign'] ?? '',
			'utm_medium'   => $signals['utm_medium'] ?? '',
			'utm_term'     => $signals['utm_term'] ?? '',
			'utm_content'  => $signals['utm_content'] ?? '',
			'landing_page' => $signals['landing_page'] ?? '',
			'referer'      => $signals['referer'] ?? '',
			'device'       => $signals['device'] ?? '',
			'browser'      => $signals['browser'] ?? '',
			'ip_address'   => $helper->get_client_ip(),
			'visitor_id'   => $visitor_id,
			'created_at'   => current_time( 'mysql' ),
			'last_updated' => current_time( 'mysql' ),
		);

		$lead_id = $db->insert_lead( $lead_data );
		if ( $lead_id ) {
			$this->log_tracking( $lead_id, $visitor_id, $signals );
		}

		$helper->log( "Auto lead created: lead_id=$lead_id, visitor=$visitor_id, source={$attr['source']}" );
		wp_send_json_success( array( 'lead_id' => $lead_id, 'action' => 'created' ) );
	}

	private function collect_cookie_tracking() {
		return Smart_Lead_CRM_Tracker::get_tracking_data();
	}

	private function log_tracking( $lead_id, $visitor_id, $signals ) {
		$helper = slcrm_helper();
		slcrm_db()->insert_tracking( array(
			'lead_id'      => $lead_id,
			'visitor_id'   => $visitor_id,
			'visit_time'   => current_time( 'mysql' ),
			'page_url'     => $signals['landing_page'] ?? '',
			'utm_source'   => $signals['utm_source'] ?? '',
			'utm_campaign' => $signals['utm_campaign'] ?? '',
			'utm_medium'   => $signals['utm_medium'] ?? '',
			'utm_term'     => $signals['utm_term'] ?? '',
			'utm_content'  => $signals['utm_content'] ?? '',
			'gclid'        => $signals['gclid'] ?? '',
			'gbraid'       => $signals['gbraid'] ?? '',
			'wbraid'       => $signals['wbraid'] ?? '',
			'referer'      => $signals['referer'] ?? '',
			'device'       => $signals['device'] ?? '',
			'browser'      => $signals['browser'] ?? '',
			'ip_address'   => $helper->get_client_ip(),
		) );
	}

	/* ── Admin handlers ────────────────────────────────────── */

	private function verify_admin() {
		check_ajax_referer( 'slcrm_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
	}

	public function update_lead() {
		$this->verify_admin();
		$id = absint( $_POST['lead_id'] ?? 0 );
		if ( ! $id ) wp_send_json_error( 'Missing lead ID' );

		$data = array();
		$fields = array( 'status', 'lead_source', 'campaign', 'ad_group', 'keyword', 'booking_route', 'remarks' );
		foreach ( $fields as $f ) {
			if ( isset( $_POST[ $f ] ) ) {
				$data[ $f ] = sanitize_text_field( wp_unslash( $_POST[ $f ] ) );
			}
		}
		if ( isset( $_POST['booking_date'] ) ) {
			$data['booking_date'] = sanitize_text_field( wp_unslash( $_POST['booking_date'] ) ) ?: null;
		}
		if ( isset( $_POST['follow_up_date'] ) ) {
			$data['follow_up_date'] = sanitize_text_field( wp_unslash( $_POST['follow_up_date'] ) ) ?: null;
		}
		$data['last_updated'] = current_time( 'mysql' );

		slcrm_db()->update_lead( $id, $data );
		wp_send_json_success( array( 'updated' => true ) );
	}

	public function delete_lead() {
		$this->verify_admin();
		$id = absint( $_POST['lead_id'] ?? 0 );
		if ( ! $id ) wp_send_json_error( 'Missing lead ID' );

		slcrm_db()->delete_lead_cascade( $id );
		wp_send_json_success( array( 'deleted' => true ) );
	}

	public function add_note() {
		$this->verify_admin();
		$lead_id = absint( $_POST['lead_id'] ?? 0 );
		$note    = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
		if ( ! $lead_id || ! $note ) wp_send_json_error( 'Missing data' );

		$id = slcrm_db()->insert_note( array(
			'lead_id' => $lead_id,
			'note'    => $note,
		) );
		wp_send_json_success( array( 'note_id' => $id ) );
	}

	public function add_booking() {
		$this->verify_admin();
		$lead_id = absint( $_POST['lead_id'] ?? 0 );
		if ( ! $lead_id ) wp_send_json_error( 'Missing lead ID' );

		$data = array(
			'lead_id'      => $lead_id,
			'booking_type' => sanitize_text_field( wp_unslash( $_POST['booking_type'] ?? 'local' ) ),
			'route'        => sanitize_text_field( wp_unslash( $_POST['route'] ?? '' ) ),
			'fare'         => (float) ( $_POST['fare'] ?? 0 ),
			'booking_date' => sanitize_text_field( wp_unslash( $_POST['booking_date'] ?? '' ) ) ?: null,
			'driver'       => sanitize_text_field( wp_unslash( $_POST['driver'] ?? '' ) ),
			'status'       => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'pending' ) ),
		);

		$booking_id = slcrm_db()->insert_booking( $data );

		// Auto-set lead to booked if booking status is booked
		if ( $booking_id && 'booked' === $data['status'] ) {
			slcrm_db()->update_lead( $lead_id, array( 'status' => 'booked' ) );
		}

		wp_send_json_success( array( 'booking_id' => $booking_id ) );
	}

	public function update_booking() {
		$this->verify_admin();
		$booking_id = absint( $_POST['booking_id'] ?? 0 );
		$status     = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
		if ( ! $booking_id ) wp_send_json_error( 'Missing booking ID' );

		slcrm_db()->update_booking( $booking_id, array( 'status' => $status ) );

		if ( 'booked' === $status ) {
			$booking = slcrm_db()->get_booking( $booking_id );
			if ( $booking ) {
				slcrm_db()->update_lead( $booking->lead_id, array( 'status' => 'booked' ) );
			}
		}

		wp_send_json_success();
	}
}
