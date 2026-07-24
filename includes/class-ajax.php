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
		add_action( 'wp_ajax_slcrm_save_conversion',    array( $this, 'save_conversion' ) );
		add_action( 'wp_ajax_slcrm_delete_conversion',  array( $this, 'delete_conversion' ) );
		add_action( 'wp_ajax_slcrm_save_form_tracking', array( $this, 'save_form_tracking' ) );
		add_action( 'wp_ajax_slcrm_delete_form_tracking', array( $this, 'delete_form_tracking' ) );
		add_action( 'wp_ajax_slcrm_quick_add_lead', array( $this, 'quick_add_lead' ) );
	}

	public function auto_create_lead() {
		check_ajax_referer( 'slcrm_public_nonce', 'nonce' );

		$visitor_id = sanitize_text_field( wp_unslash( $_POST['visitor_id'] ?? '' ) );
		$action_type = sanitize_text_field( wp_unslash( $_POST['lead_action'] ?? '' ) );
		$phone      = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$name       = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$form_name  = sanitize_text_field( wp_unslash( $_POST['form_name'] ?? '' ) );

		if ( ! $visitor_id ) wp_send_json_error( 'Missing visitor_id' );

		// Reject the business's own number — it appears in wa.me links that visitors click.
		if ( $phone ) {
			$business_number = preg_replace( '/[^0-9]/', '', slcrm_get_setting( 'whatsapp_business_number', '' ) );
			if ( $business_number && preg_replace( '/[^0-9]/', '', $phone ) === $business_number ) {
				$phone = '';
			}
		}

		$db         = slcrm_db();
		$helper     = slcrm_helper();
		$attribution = smart_lead_crm()->attribution;

		$signals = $this->collect_cookie_tracking();
		$signals['lead_action'] = $action_type;
		$attr = $attribution->resolve( $signals );

		$existing = $db->find_lead_by_visitor_today( $visitor_id );

		// Build dynamic remark from conversion label
		$conv_label = $helper->get_conversion_label( $action_type );
		$action_remark = $conv_label ? sprintf( 'Auto-created: %s', $conv_label ) : '';

		if ( $existing ) {
			$update = array();
			if ( $phone && ! $existing->phone ) {
				$update['phone'] = $phone;
			}
			if ( $email && empty( $existing->email ) ) {
				$update['email'] = $email;
			}
			if ( $name && 'Website Visitor' === $existing->name ) {
				$update['name'] = $name;
			}
			if ( $attribution->should_upgrade( $existing->lead_source, $attr['source'] ) ) {
				$update['lead_source'] = $attr['source'];
				$update['medium']      = $attr['medium'];
				$update['campaign']    = $attr['campaign'];
				$update['ad_group']    = $attr['ad_group'];
				$update['keyword']     = $attr['keyword'];
			}
			if ( $action_remark && ( empty( $existing->remarks ) || false !== strpos( $existing->remarks, 'Auto-created:' ) ) ) {
				$update['remarks'] = $form_name ? $action_remark . ' (' . $form_name . ')' : $action_remark;
			}
			if ( $action_type && empty( $existing->lead_action ) ) {
				$update['lead_action'] = $action_type;
			}
			if ( $form_name && empty( $existing->form_name ) ) {
				$update['form_name'] = $form_name;
			}
			$update['last_updated'] = current_time( 'mysql' );
			$db->update_lead( $existing->id, $update );

			$this->log_tracking( $existing->id, $visitor_id, $signals );
			wp_send_json_success( array( 'lead_id' => $existing->id, 'action' => 'updated' ) );
		}

		$remarks = $form_name ? $action_remark . ' (' . $form_name . ')' : ( $action_remark ?: 'Auto-created: website visit' );

		$lead_data = array(
			'name'         => $name ?: 'Website Visitor',
			'phone'        => $phone ?: '',
			'status'       => 'new_lead',
			'lead_action'  => $action_type,
			'form_name'    => $form_name,
			'remarks'      => $remarks,
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
		if ( $email ) {
			$lead_data['email'] = $email;
		}

		$lead_id = $db->insert_lead( $lead_data );
		if ( $lead_id ) {
			$this->log_tracking( $lead_id, $visitor_id, $signals );
			$lead_obj = $db->get_lead( $lead_id );
			if ( $lead_obj && $lead_obj->phone ) {
				smart_lead_crm()->messaging->send_auto_reply( $lead_obj );
			}
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
			'lead_id'         => $lead_id,
			'visitor_id'      => $visitor_id,
			'visit_time'      => current_time( 'mysql' ),
			'page_url'        => $signals['landing_page'] ?? '',
			'utm_source'      => $signals['utm_source'] ?? '',
			'utm_campaign'    => $signals['utm_campaign'] ?? '',
			'utm_medium'      => $signals['utm_medium'] ?? '',
			'utm_term'        => $signals['utm_term'] ?? '',
			'utm_content'     => $signals['utm_content'] ?? '',
			'gclid'           => $signals['gclid'] ?? '',
			'gbraid'          => $signals['gbraid'] ?? '',
			'wbraid'          => $signals['wbraid'] ?? '',
			'referer'         => $signals['referer'] ?? '',
			'device'          => $signals['device'] ?? '',
			'browser'         => $signals['browser'] ?? '',
			'ip_address'      => $helper->get_client_ip(),
			'organic_keyword' => $signals['organic_keyword'] ?? '',
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
		$fields = array( 'name', 'phone', 'email', 'status', 'lead_source', 'campaign', 'ad_group', 'keyword', 'booking_route', 'remarks' );
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

	/* ── Conversion management ─────────────────────────────── */

	public function save_conversion() {
		$this->verify_admin();
		$db = slcrm_db();

		$id          = absint( $_POST['id'] ?? 0 );
		$crm_action  = sanitize_text_field( wp_unslash( $_POST['crm_action'] ?? '' ) );
		$label       = sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) );
		$ads_label   = sanitize_text_field( wp_unslash( $_POST['google_ads_label'] ?? '' ) );
		$ga4_event   = sanitize_text_field( wp_unslash( $_POST['ga4_event'] ?? '' ) );
		$enabled     = isset( $_POST['enabled'] ) ? 1 : 0;
		$category    = sanitize_text_field( wp_unslash( $_POST['category'] ?? 'interaction' ) );
		$sort_order  = absint( $_POST['sort_order'] ?? 0 );

		if ( ! $crm_action || ! $label ) wp_send_json_error( 'Missing required fields' );

		$data = array(
			'crm_action'       => $crm_action,
			'label'            => $label,
			'google_ads_label' => $ads_label,
			'ga4_event'       => $ga4_event,
			'enabled'         => $enabled,
			'category'        => $category,
			'sort_order'      => $sort_order,
		);

		if ( $id ) {
			$db->update_conversion( $id, $data );
		} else {
			$id = $db->insert_conversion( $data );
		}

		wp_send_json_success( array( 'id' => $id ) );
	}

	public function delete_conversion() {
		$this->verify_admin();
		$id = absint( $_POST['id'] ?? 0 );
		if ( ! $id ) wp_send_json_error( 'Missing ID' );
		slcrm_db()->delete_conversion( $id );
		wp_send_json_success();
	}

	/* ── Form tracking management ──────────────────────────── */

	public function save_form_tracking() {
		$this->verify_admin();
		$db = slcrm_db();

		$id         = absint( $_POST['id'] ?? 0 );
		$form_name  = sanitize_text_field( wp_unslash( $_POST['form_name'] ?? '' ) );
		$selector   = sanitize_text_field( wp_unslash( $_POST['selector'] ?? '' ) );
		$event_type = sanitize_text_field( wp_unslash( $_POST['event_type'] ?? 'submit' ) );
		$crm_action = sanitize_text_field( wp_unslash( $_POST['crm_action'] ?? '' ) );
		$enabled    = isset( $_POST['enabled'] ) ? 1 : 0;
		$sort_order = absint( $_POST['sort_order'] ?? 0 );

		if ( ! $form_name || ! $selector || ! $crm_action ) wp_send_json_error( 'Missing required fields' );

		$data = array(
			'form_name'  => $form_name,
			'selector'   => $selector,
			'event_type' => $event_type,
			'crm_action' => $crm_action,
			'enabled'    => $enabled,
			'sort_order' => $sort_order,
		);

		if ( $id ) {
			$db->update_form_tracking( $id, $data );
		} else {
			$id = $db->insert_form_tracking( $data );
		}

		wp_send_json_success( array( 'id' => $id ) );
	}

	public function delete_form_tracking() {
		$this->verify_admin();
		$id = absint( $_POST['id'] ?? 0 );
		if ( ! $id ) wp_send_json_error( 'Missing ID' );
		slcrm_db()->delete_form_tracking( $id );
		wp_send_json_success();
	}

	/* ── Quick Add Lead ─────────────────────────────────────── */

	public function quick_add_lead() {
		$this->verify_admin();
		$name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$source = sanitize_text_field( wp_unslash( $_POST['lead_source'] ?? 'manual' ) );

		if ( ! $name && ! $phone ) wp_send_json_error( 'Name or phone required' );

		$data = array(
			'name'        => $name ?: 'Manual Lead',
			'phone'       => $phone,
			'email'       => $email,
			'status'      => 'new_lead',
			'lead_source' => $source,
			'lead_action' => 'manual',
			'remarks'     => 'Manually added',
		);

		$lead_id = slcrm_db()->insert_lead( $data );
		if ( ! $lead_id ) wp_send_json_error( 'Failed to create lead' );

		wp_send_json_success( array( 'lead_id' => $lead_id, 'redirect' => admin_url( 'admin.php?page=smart-lead-crm-leads&action=view&lead_id=' . $lead_id ) ) );
	}
}
