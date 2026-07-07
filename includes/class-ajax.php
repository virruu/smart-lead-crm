<?php
/**
 * AJAX class - handles lead capture and lead management AJAX requests.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX class.
 *
 * @package SmartLeadCRM
 */
class Smart_Lead_CRM_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Frontend automatic lead creation (no auth, no form, triggered by tracker.js).
		add_action( 'wp_ajax_slcrm_auto_lead', array( $this, 'auto_create_lead' ) );
		add_action( 'wp_ajax_nopriv_slcrm_auto_lead', array( $this, 'auto_create_lead' ) );

		// Frontend lead capture (no auth required, uses public nonce).
		add_action( 'wp_ajax_slcrm_submit_lead', array( $this, 'submit_lead' ) );
		add_action( 'wp_ajax_nopriv_slcrm_submit_lead', array( $this, 'submit_lead' ) );

		// Admin lead management.
		add_action( 'wp_ajax_slcrm_update_lead', array( $this, 'update_lead' ) );
		add_action( 'wp_ajax_slcrm_delete_lead', array( $this, 'delete_lead' ) );
		add_action( 'wp_ajax_slcrm_add_note', array( $this, 'add_note' ) );
		add_action( 'wp_ajax_slcrm_add_booking', array( $this, 'add_booking' ) );
		add_action( 'wp_ajax_slcrm_update_booking', array( $this, 'update_booking' ) );
	}

	/**
	 * Collect tracking signals from the current POST request (sent by tracker.js).
	 *
	 * @return array Normalized tracking signals.
	 */
	private function collect_post_tracking() {
		$fields = array(
			'gclid'        => 'sanitize_text_field',
			'gbraid'       => 'sanitize_text_field',
			'wbraid'       => 'sanitize_text_field',
			'utm_source'   => 'sanitize_text_field',
			'utm_medium'   => 'sanitize_text_field',
			'utm_campaign' => 'sanitize_text_field',
			'utm_term'     => 'sanitize_text_field',
			'utm_content'  => 'sanitize_text_field',
			'landing_page' => 'esc_url_raw',
			'referer'      => 'esc_url_raw',
			'device'       => 'sanitize_text_field',
			'browser'      => 'sanitize_text_field',
		);

		$data = array();
		foreach ( $fields as $key => $sanitize ) {
			$data[ $key ] = isset( $_POST[ $key ] ) ? $sanitize( wp_unslash( $_POST[ $key ] ) ) : '';
		}
		return $data;
	}

	/**
	 * Collect tracking signals from cookies (used by the form submit flow).
	 *
	 * @return array Normalized tracking signals.
	 */
	private function collect_cookie_tracking() {
		$tracker = smart_lead_crm()->tracker;
		$t       = $tracker->get_tracking_data();

		return array(
			'gclid'        => isset( $t['slcrm_gclid'] ) ? $t['slcrm_gclid'] : '',
			'gbraid'       => isset( $t['slcrm_gbraid'] ) ? $t['slcrm_gbraid'] : '',
			'wbraid'       => isset( $t['slcrm_wbraid'] ) ? $t['slcrm_wbraid'] : '',
			'utm_source'   => isset( $t['slcrm_utm_source'] ) ? $t['slcrm_utm_source'] : '',
			'utm_medium'   => isset( $t['slcrm_utm_medium'] ) ? $t['slcrm_utm_medium'] : '',
			'utm_campaign' => isset( $t['slcrm_utm_campaign'] ) ? $t['slcrm_utm_campaign'] : '',
			'utm_term'     => isset( $t['slcrm_utm_term'] ) ? $t['slcrm_utm_term'] : '',
			'utm_content'  => isset( $t['slcrm_utm_content'] ) ? $t['slcrm_utm_content'] : '',
			'landing_page' => isset( $t['slcrm_landing_page'] ) ? $t['slcrm_landing_page'] : '',
			'referer'      => isset( $t['slcrm_referer'] ) ? $t['slcrm_referer'] : '',
			'device'       => isset( $t['slcrm_device'] ) ? $t['slcrm_device'] : '',
			'browser'      => isset( $t['slcrm_browser'] ) ? $t['slcrm_browser'] : '',
		);
	}

	/**
	 * Auto-create a lead when a visitor clicks a WhatsApp or tel: link.
	 *
	 * Triggered by tracker.js. No form submission required.
	 * Deduplicates by visitor_id — one lead per visitor per day.
	 * On return visits, upgrades the lead's attribution if a higher-priority
	 * source is detected and persists the latest tracking values.
	 */
	public function auto_create_lead() {
		check_ajax_referer( 'slcrm_public_nonce', 'nonce' );

		$visitor_id  = isset( $_POST['visitor_id'] ) ? sanitize_text_field( wp_unslash( $_POST['visitor_id'] ) ) : '';
		$lead_action = isset( $_POST['lead_action'] ) ? sanitize_text_field( wp_unslash( $_POST['lead_action'] ) ) : '';
		$phone       = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		if ( empty( $visitor_id ) ) {
			wp_send_json_error( array( 'message' => 'No visitor ID.' ) );
		}

		$plugin      = smart_lead_crm();
		$helper      = $plugin->helper;
		$db          = $plugin->db;
		$attribution = $plugin->attribution;

		$tracking = $this->collect_post_tracking();

		// Resolve attribution using the full priority engine.
		$signals             = $tracking;
		$signals['lead_action'] = $lead_action;
		$attrib = $attribution->resolve( $signals );

		// Dedup: check if this visitor already has a lead created today.
		$existing = $db->find_lead_by_visitor_today( $visitor_id );
		if ( $existing ) {
			$update_data = array();

			// Update phone if we now have one and it was empty.
			if ( ! empty( $phone ) && empty( $existing->phone ) ) {
				$update_data['phone'] = $phone;
			}

			// Upgrade attribution if the new source is higher priority.
			if ( $attribution->should_upgrade( $existing->lead_source, $attrib['source'] ) ) {
				$update_data['lead_source'] = $attrib['source'];
				$update_data['medium']      = $attrib['medium'];
				$update_data['campaign']    = $attrib['campaign'];
				$update_data['ad_group']     = $attrib['ad_group'];
				$update_data['keyword']     = $attrib['keyword'];
			}

			// Always persist the latest tracking values so the lead row stays
			// complete for reporting and offline conversion uploads.
			$update_data['gclid']        = $tracking['gclid'];
			$update_data['gbraid']       = $tracking['gbraid'];
			$update_data['wbraid']       = $tracking['wbraid'];
			$update_data['utm_source']   = $tracking['utm_source'];
			$update_data['utm_medium']   = $tracking['utm_medium'];
			$update_data['utm_campaign'] = $tracking['utm_campaign'];
			$update_data['utm_term']     = $tracking['utm_term'];
			$update_data['utm_content']  = $tracking['utm_content'];

			if ( ! empty( $update_data ) ) {
				$db->update_lead( $existing->id, $update_data );
			}

			// Still log the tracking visit.
			$this->log_tracking( $existing->id, $visitor_id, $helper, $tracking );
			wp_send_json_success( array( 'message' => 'Lead already exists.', 'lead_id' => $existing->id ) );
		}

		// Build lead data with full attribution stored permanently on the lead row.
		$lead_data = array(
			'visitor_id'    => $visitor_id,
			'phone'         => $phone,
			'status'        => 'pending',
			'lead_source'   => $attrib['source'],
			'medium'        => $attrib['medium'],
			'campaign'      => $attrib['campaign'],
			'ad_group'      => $attrib['ad_group'],
			'keyword'       => $attrib['keyword'],
			'gclid'         => $tracking['gclid'],
			'gbraid'        => $tracking['gbraid'],
			'wbraid'        => $tracking['wbraid'],
			'utm_source'    => $tracking['utm_source'],
			'utm_medium'    => $tracking['utm_medium'],
			'utm_campaign'  => $tracking['utm_campaign'],
			'utm_term'      => $tracking['utm_term'],
			'utm_content'   => $tracking['utm_content'],
			'landing_page'  => $tracking['landing_page'],
			'device'        => $tracking['device'],
			'browser'       => $tracking['browser'],
			'ip'            => $helper->get_client_ip(),
			'referer'       => $tracking['referer'],
			'remarks'       => ( 'whatsapp' === $lead_action ) ? 'Auto-created: WhatsApp click' : ( 'call' === $lead_action ? 'Auto-created: Phone call click' : 'Auto-created' ),
		);

		$lead_id = $db->insert_lead( $lead_data );

		if ( ! $lead_id ) {
			wp_send_json_error( array( 'message' => 'Failed to create lead.' ) );
		}

		// Store tracking record (visit history).
		$tracking_data = array(
			'lead_id'       => $lead_id,
			'visitor_id'    => $visitor_id,
			'gclid'         => $tracking['gclid'],
			'gbraid'        => $tracking['gbraid'],
			'wbraid'        => $tracking['wbraid'],
			'utm_source'    => $tracking['utm_source'],
			'utm_medium'    => $tracking['utm_medium'],
			'utm_campaign'  => $tracking['utm_campaign'],
			'utm_term'      => $tracking['utm_term'],
			'utm_content'   => $tracking['utm_content'],
			'landing_page'  => $tracking['landing_page'],
			'referer'       => $tracking['referer'],
			'device'        => $tracking['device'],
			'browser'       => $tracking['browser'],
			'ip'            => $helper->get_client_ip(),
		);
		$db->insert_tracking( $tracking_data );

		$helper->log( 'Auto-lead created: ID ' . $lead_id . ' visitor=' . substr( $visitor_id, 0, 8 ) . ' action=' . $lead_action . ' source=' . $attrib['source'] );

		wp_send_json_success( array( 'message' => 'Lead created.', 'lead_id' => $lead_id ) );
	}

	/**
	 * Log a tracking visit for an existing lead (dedup case).
	 *
	 * @param int                  $lead_id    Lead ID.
	 * @param string               $visitor_id Visitor ID.
	 * @param Smart_Lead_CRM_Helper $helper     Helper instance.
	 * @param array                $tracking   Pre-collected tracking data.
	 */
	private function log_tracking( $lead_id, $visitor_id, $helper, $tracking = null ) {
		if ( null === $tracking ) {
			$tracking = $this->collect_post_tracking();
		}

		smart_lead_crm()->db->insert_tracking( array(
			'lead_id'       => $lead_id,
			'visitor_id'    => $visitor_id,
			'gclid'         => $tracking['gclid'],
			'gbraid'        => $tracking['gbraid'],
			'wbraid'        => $tracking['wbraid'],
			'utm_source'    => $tracking['utm_source'],
			'utm_medium'    => $tracking['utm_medium'],
			'utm_campaign'  => $tracking['utm_campaign'],
			'utm_term'      => $tracking['utm_term'],
			'utm_content'   => $tracking['utm_content'],
			'landing_page'  => $tracking['landing_page'],
			'referer'       => $tracking['referer'],
			'device'        => $tracking['device'],
			'browser'       => $tracking['browser'],
			'ip'            => $helper->get_client_ip(),
		) );
	}

	/**
	 * Submit a new lead from the frontend form.
	 */
	public function submit_lead() {
		check_ajax_referer( 'slcrm_public_nonce', 'nonce' );

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Phone number is required.', 'smart-lead-crm' ) ) );
		}

		$plugin      = smart_lead_crm();
		$helper      = $plugin->helper;
		$db          = $plugin->db;
		$attribution = $plugin->attribution;

		// Get tracking data from cookies.
		$tracking = $this->collect_cookie_tracking();

		// Resolve attribution.
		$attrib = $attribution->resolve( $tracking );

		$lead_data = array(
			'phone'         => $phone,
			'name'          => $name,
			'email'         => $email,
			'status'        => 'pending',
			'lead_source'   => $attrib['source'],
			'medium'        => $attrib['medium'],
			'campaign'      => $attrib['campaign'],
			'ad_group'      => $attrib['ad_group'],
			'keyword'       => $attrib['keyword'],
			'gclid'         => $tracking['gclid'],
			'gbraid'        => $tracking['gbraid'],
			'wbraid'        => $tracking['wbraid'],
			'utm_source'    => $tracking['utm_source'],
			'utm_medium'    => $tracking['utm_medium'],
			'utm_campaign'  => $tracking['utm_campaign'],
			'utm_term'      => $tracking['utm_term'],
			'utm_content'   => $tracking['utm_content'],
			'landing_page'  => $tracking['landing_page'],
			'device'        => $tracking['device'],
			'browser'       => $tracking['browser'],
			'ip'            => $helper->get_client_ip(),
			'referer'       => $tracking['referer'],
		);

		$lead_id = $db->insert_lead( $lead_data );

		if ( ! $lead_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save lead. Please try again.', 'smart-lead-crm' ) ) );
		}

		// Store tracking record.
		$tracking_data = array(
			'lead_id'       => $lead_id,
			'gclid'         => $tracking['gclid'],
			'gbraid'        => $tracking['gbraid'],
			'wbraid'        => $tracking['wbraid'],
			'utm_source'    => $tracking['utm_source'],
			'utm_medium'    => $tracking['utm_medium'],
			'utm_campaign'  => $tracking['utm_campaign'],
			'utm_term'      => $tracking['utm_term'],
			'utm_content'   => $tracking['utm_content'],
			'landing_page'  => $tracking['landing_page'],
			'referer'       => $tracking['referer'],
			'device'        => $tracking['device'],
			'browser'       => $tracking['browser'],
			'ip'            => $helper->get_client_ip(),
		);
		$db->insert_tracking( $tracking_data );

		$helper->log( 'New lead captured: ID ' . $lead_id . ' from ' . $phone . ' source=' . $attrib['source'] );

		wp_send_json_success( array(
			'message' => __( 'Thank you! We will contact you soon.', 'smart-lead-crm' ),
			'lead_id' => $lead_id,
		) );
	}

	/**
	 * Update a lead (admin).
	 */
	public function update_lead() {
		check_ajax_referer( 'slcrm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'smart-lead-crm' ) ) );
		}

		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		if ( ! $lead_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid lead ID.', 'smart-lead-crm' ) ) );
		}

		$fields = array( 'name', 'phone', 'email', 'status', 'lead_source', 'medium', 'campaign', 'ad_group', 'keyword', 'booking_route', 'remarks' );
		$data   = array();
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			}
		}

		if ( isset( $_POST['booking_date'] ) ) {
			$data['booking_date'] = sanitize_text_field( wp_unslash( $_POST['booking_date'] ) );
		}

		if ( isset( $_POST['follow_up_date'] ) ) {
			$data['follow_up_date'] = sanitize_text_field( wp_unslash( $_POST['follow_up_date'] ) );
		}

		$result = smart_lead_crm()->db->update_lead( $lead_id, $data );

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to update lead.', 'smart-lead-crm' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Lead updated successfully.', 'smart-lead-crm' ) ) );
	}

	/**
	 * Delete a lead (admin).
	 */
	public function delete_lead() {
		check_ajax_referer( 'slcrm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'smart-lead-crm' ) ) );
		}

		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		if ( ! $lead_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid lead ID.', 'smart-lead-crm' ) ) );
		}

		$db = smart_lead_crm()->db;
		$db->delete_lead( $lead_id );

		wp_send_json_success( array( 'message' => __( 'Lead deleted.', 'smart-lead-crm' ) ) );
	}

	/**
	 * Add a note to a lead (admin).
	 */
	public function add_note() {
		check_ajax_referer( 'slcrm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'smart-lead-crm' ) ) );
		}

		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$note    = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

		if ( ! $lead_id || empty( $note ) ) {
			wp_send_json_error( array( 'message' => __( 'Lead ID and note are required.', 'smart-lead-crm' ) ) );
		}

		$result = smart_lead_crm()->db->insert_note( array(
			'lead_id'   => $lead_id,
			'note'      => $note,
			'author_id' => get_current_user_id(),
		) );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to add note.', 'smart-lead-crm' ) ) );
		}

		wp_send_json_success( array(
			'message'   => __( 'Note added.', 'smart-lead-crm' ),
			'note_id'   => $result,
			'author'    => wp_get_current_user()->display_name,
			'created_at' => current_time( 'M j, Y g:i a' ),
		) );
	}

	/**
	 * Add a booking to a lead (admin).
	 */
	public function add_booking() {
		check_ajax_referer( 'slcrm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'smart-lead-crm' ) ) );
		}

		$lead_id      = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$booking_type = isset( $_POST['booking_type'] ) ? sanitize_text_field( wp_unslash( $_POST['booking_type'] ) ) : '';
		$route        = isset( $_POST['route'] ) ? sanitize_text_field( wp_unslash( $_POST['route'] ) ) : '';
		$fare         = isset( $_POST['fare'] ) ? floatval( $_POST['fare'] ) : 0;
		$booking_date = isset( $_POST['booking_date'] ) ? sanitize_text_field( wp_unslash( $_POST['booking_date'] ) ) : '';
		$driver       = isset( $_POST['driver'] ) ? sanitize_text_field( wp_unslash( $_POST['driver'] ) ) : '';
		$status       = isset( $_POST['booking_status'] ) ? sanitize_text_field( wp_unslash( $_POST['booking_status'] ) ) : 'pending';

		if ( ! $lead_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid lead ID.', 'smart-lead-crm' ) ) );
		}

		$result = smart_lead_crm()->db->insert_booking( array(
			'lead_id'      => $lead_id,
			'booking_type' => $booking_type,
			'route'        => $route,
			'fare'         => $fare,
			'booking_date' => $booking_date,
			'driver'       => $driver,
			'status'       => $status,
		) );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to add booking.', 'smart-lead-crm' ) ) );
		}

		// If booking is marked as booked, update lead status too.
		if ( 'booked' === $status ) {
			smart_lead_crm()->db->update_lead( $lead_id, array( 'status' => 'booked' ) );
		}

		wp_send_json_success( array( 'message' => __( 'Booking added.', 'smart-lead-crm' ) ) );
	}

	/**
	 * Update a booking status (admin).
	 */
	public function update_booking() {
		check_ajax_referer( 'slcrm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'smart-lead-crm' ) ) );
		}

		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		$status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$lead_id    = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;

		if ( ! $booking_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid booking ID.', 'smart-lead-crm' ) ) );
		}

		$db = smart_lead_crm()->db;
		$db->update_booking( $booking_id, array( 'status' => $status ) );

		if ( 'booked' === $status && $lead_id ) {
			$db->update_lead( $lead_id, array( 'status' => 'booked' ) );
		}

		wp_send_json_success( array( 'message' => __( 'Booking updated.', 'smart-lead-crm' ) ) );
	}
}
