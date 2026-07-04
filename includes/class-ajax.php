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
	 * Auto-create a lead when a visitor clicks a WhatsApp or tel: link.
	 *
	 * Triggered by tracker.js. No form submission required.
	 * Deduplicates by visitor_id — one lead per visitor per day.
	 */
	public function auto_create_lead() {
		check_ajax_referer( 'slcrm_public_nonce', 'nonce' );

		$visitor_id = isset( $_POST['visitor_id'] ) ? sanitize_text_field( wp_unslash( $_POST['visitor_id'] ) ) : '';
		$lead_action = isset( $_POST['lead_action'] ) ? sanitize_text_field( wp_unslash( $_POST['lead_action'] ) ) : '';
		$phone       = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		if ( empty( $visitor_id ) ) {
			wp_send_json_error( array( 'message' => 'No visitor ID.' ) );
		}

		$plugin  = smart_lead_crm();
		$helper  = $plugin->helper;
		$db      = $plugin->db;

		// Dedup: check if this visitor already has a lead created today.
		$existing = $db->find_lead_by_visitor_today( $visitor_id );
		if ( $existing ) {
			// Update the existing lead's phone if we now have one and it was empty.
			if ( ! empty( $phone ) && empty( $existing->phone ) ) {
				$db->update_lead( $existing->id, array( 'phone' => $phone ) );
			}
			// Still log the tracking visit.
			$this->log_tracking( $existing->id, $visitor_id, $helper );
			wp_send_json_success( array( 'message' => 'Lead already exists.', 'lead_id' => $existing->id ) );
		}

		// Collect tracking data from POST (sent by tracker.js from cookies).
		$gclid        = isset( $_POST['gclid'] ) ? sanitize_text_field( wp_unslash( $_POST['gclid'] ) ) : '';
		$gbraid       = isset( $_POST['gbraid'] ) ? sanitize_text_field( wp_unslash( $_POST['gbraid'] ) ) : '';
		$wbraid       = isset( $_POST['wbraid'] ) ? sanitize_text_field( wp_unslash( $_POST['wbraid'] ) ) : '';
		$utm_source   = isset( $_POST['utm_source'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_source'] ) ) : '';
		$utm_medium   = isset( $_POST['utm_medium'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_medium'] ) ) : '';
		$utm_campaign = isset( $_POST['utm_campaign'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_campaign'] ) ) : '';
		$utm_term     = isset( $_POST['utm_term'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_term'] ) ) : '';
		$utm_content   = isset( $_POST['utm_content'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_content'] ) ) : '';
		$landing_page = isset( $_POST['landing_page'] ) ? esc_url_raw( wp_unslash( $_POST['landing_page'] ) ) : '';
		$referer      = isset( $_POST['referer'] ) ? esc_url_raw( wp_unslash( $_POST['referer'] ) ) : '';
		$device       = isset( $_POST['device'] ) ? sanitize_text_field( wp_unslash( $_POST['device'] ) ) : '';
		$browser      = isset( $_POST['browser'] ) ? sanitize_text_field( wp_unslash( $_POST['browser'] ) ) : '';

		// Determine source.
		$source = $this->determine_source_from_data( $gclid, $utm_source, $referer );

		// Determine lead source label for the action.
		if ( 'whatsapp' === $lead_action ) {
			$source = empty( $source ) ? 'whatsapp' : $source;
		} elseif ( 'call' === $lead_action ) {
			$source = empty( $source ) ? 'manual' : $source;
		}

		$lead_data = array(
			'visitor_id'    => $visitor_id,
			'phone'         => $phone,
			'status'        => 'pending',
			'lead_source'   => $source,
			'campaign'      => $utm_campaign,
			'landing_page'  => $landing_page,
			'device'        => $device,
			'browser'       => $browser,
			'ip'            => $helper->get_client_ip(),
			'referer'       => $referer,
			'remarks'       => ( 'whatsapp' === $lead_action ) ? 'Auto-created: WhatsApp click' : ( 'call' === $lead_action ? 'Auto-created: Phone call click' : 'Auto-created' ),
		);

		$lead_id = $db->insert_lead( $lead_data );

		if ( ! $lead_id ) {
			wp_send_json_error( array( 'message' => 'Failed to create lead.' ) );
		}

		// Store tracking record.
		$tracking_data = array(
			'lead_id'       => $lead_id,
			'visitor_id'    => $visitor_id,
			'gclid'         => $gclid,
			'gbraid'        => $gbraid,
			'wbraid'        => $wbraid,
			'utm_source'    => $utm_source,
			'utm_medium'    => $utm_medium,
			'utm_campaign'  => $utm_campaign,
			'utm_term'      => $utm_term,
			'utm_content'   => $utm_content,
			'landing_page'  => $landing_page,
			'referer'       => $referer,
			'device'        => $device,
			'browser'       => $browser,
			'ip'            => $helper->get_client_ip(),
		);
		$db->insert_tracking( $tracking_data );

		$helper->log( 'Auto-lead created: ID ' . $lead_id . ' visitor=' . substr( $visitor_id, 0, 8 ) . ' action=' . $lead_action );

		wp_send_json_success( array( 'message' => 'Lead created.', 'lead_id' => $lead_id ) );
	}

	/**
	 * Log a tracking visit for an existing lead (dedup case).
	 *
	 * @param int                  $lead_id    Lead ID.
	 * @param string               $visitor_id Visitor ID.
	 * @param Smart_Lead_CRM_Helper $helper     Helper instance.
	 */
	private function log_tracking( $lead_id, $visitor_id, $helper ) {
		$gclid        = isset( $_POST['gclid'] ) ? sanitize_text_field( wp_unslash( $_POST['gclid'] ) ) : '';
		$gbraid       = isset( $_POST['gbraid'] ) ? sanitize_text_field( wp_unslash( $_POST['gbraid'] ) ) : '';
		$wbraid       = isset( $_POST['wbraid'] ) ? sanitize_text_field( wp_unslash( $_POST['wbraid'] ) ) : '';
		$utm_source   = isset( $_POST['utm_source'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_source'] ) ) : '';
		$utm_medium   = isset( $_POST['utm_medium'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_medium'] ) ) : '';
		$utm_campaign = isset( $_POST['utm_campaign'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_campaign'] ) ) : '';
		$utm_term     = isset( $_POST['utm_term'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_term'] ) ) : '';
		$utm_content   = isset( $_POST['utm_content'] ) ? sanitize_text_field( wp_unslash( $_POST['utm_content'] ) ) : '';
		$landing_page = isset( $_POST['landing_page'] ) ? esc_url_raw( wp_unslash( $_POST['landing_page'] ) ) : '';
		$referer      = isset( $_POST['referer'] ) ? esc_url_raw( wp_unslash( $_POST['referer'] ) ) : '';
		$device       = isset( $_POST['device'] ) ? sanitize_text_field( wp_unslash( $_POST['device'] ) ) : '';
		$browser      = isset( $_POST['browser'] ) ? sanitize_text_field( wp_unslash( $_POST['browser'] ) ) : '';

		smart_lead_crm()->db->insert_tracking( array(
			'lead_id'       => $lead_id,
			'visitor_id'    => $visitor_id,
			'gclid'         => $gclid,
			'gbraid'        => $gbraid,
			'wbraid'        => $wbraid,
			'utm_source'    => $utm_source,
			'utm_medium'    => $utm_medium,
			'utm_campaign'  => $utm_campaign,
			'utm_term'      => $utm_term,
			'utm_content'   => $utm_content,
			'landing_page'  => $landing_page,
			'referer'       => $referer,
			'device'        => $device,
			'browser'       => $browser,
			'ip'            => $helper->get_client_ip(),
		) );
	}

	/**
	 * Determine lead source from tracking data (server-side version).
	 *
	 * @param string $gclid      GCLID value.
	 * @param string $utm_source UTM source.
	 * @param string $referer    Referrer.
	 * @return string
	 */
	private function determine_source_from_data( $gclid, $utm_source, $referer ) {
		if ( ! empty( $gclid ) ) {
			return 'google_ads';
		}
		if ( ! empty( $utm_source ) ) {
			$source = strtolower( $utm_source );
			if ( strpos( $source, 'facebook' ) !== false ) return 'facebook';
			if ( strpos( $source, 'instagram' ) !== false ) return 'instagram';
			if ( strpos( $source, 'google' ) !== false ) return 'organic';
			return 'referral';
		}
		$ref = strtolower( $referer );
		if ( strpos( $ref, 'google' ) !== false ) return 'organic';
		if ( strpos( $ref, 'facebook' ) !== false ) return 'facebook';
		if ( strpos( $ref, 'instagram' ) !== false ) return 'instagram';
		return '';
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

		$plugin  = smart_lead_crm();
		$tracker = $plugin->tracker;
		$helper  = $plugin->helper;
		$db      = $plugin->db;

		// Get tracking data from cookies.
		$tracking = $tracker->get_tracking_data();

		$lead_data = array(
			'phone'         => $phone,
			'name'          => $name,
			'email'         => $email,
			'status'        => 'pending',
			'lead_source'   => $this->determine_source( $tracking ),
			'campaign'      => $tracking['slcrm_utm_campaign'],
			'landing_page'  => $tracking['slcrm_landing_page'],
			'device'        => $tracking['slcrm_device'],
			'browser'       => $tracking['slcrm_browser'],
			'ip'            => $helper->get_client_ip(),
			'referer'       => $tracking['slcrm_referer'],
		);

		$lead_id = $db->insert_lead( $lead_data );

		if ( ! $lead_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save lead. Please try again.', 'smart-lead-crm' ) ) );
		}

		// Store tracking record.
		$tracking_data = array(
			'lead_id'       => $lead_id,
			'gclid'         => $tracking['slcrm_gclid'],
			'gbraid'        => $tracking['slcrm_gbraid'],
			'wbraid'        => $tracking['slcrm_wbraid'],
			'utm_source'    => $tracking['slcrm_utm_source'],
			'utm_medium'    => $tracking['slcrm_utm_medium'],
			'utm_campaign'  => $tracking['slcrm_utm_campaign'],
			'utm_term'      => $tracking['slcrm_utm_term'],
			'utm_content'   => $tracking['slcrm_utm_content'],
			'landing_page'  => $tracking['slcrm_landing_page'],
			'referer'       => $tracking['slcrm_referer'],
			'device'        => $tracking['slcrm_device'],
			'browser'       => $tracking['slcrm_browser'],
			'ip'            => $helper->get_client_ip(),
		);
		$db->insert_tracking( $tracking_data );

		$helper->log( 'New lead captured: ID ' . $lead_id . ' from ' . $phone );

		wp_send_json_success( array(
			'message' => __( 'Thank you! We will contact you soon.', 'smart-lead-crm' ),
			'lead_id' => $lead_id,
		) );
	}

	/**
	 * Determine lead source from tracking data.
	 *
	 * @param array $tracking Tracking data.
	 * @return string
	 */
	private function determine_source( $tracking ) {
		if ( ! empty( $tracking['slcrm_gclid'] ) ) {
			return 'google_ads';
		}
		if ( ! empty( $tracking['slcrm_utm_source'] ) ) {
			$source = strtolower( $tracking['slcrm_utm_source'] );
			if ( strpos( $source, 'facebook' ) !== false ) {
				return 'facebook';
			}
			if ( strpos( $source, 'instagram' ) !== false ) {
				return 'instagram';
			}
			if ( strpos( $source, 'google' ) !== false ) {
				return 'organic';
			}
			return 'referral';
		}
		$referer = strtolower( $tracking['slcrm_referer'] );
		if ( strpos( $referer, 'google' ) !== false ) {
			return 'organic';
		}
		if ( strpos( $referer, 'facebook' ) !== false ) {
			return 'facebook';
		}
		if ( strpos( $referer, 'instagram' ) !== false ) {
			return 'instagram';
		}
		return 'manual';
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

		$fields = array( 'name', 'phone', 'email', 'status', 'lead_source', 'campaign', 'booking_route', 'remarks' );
		$data   = array();
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			}
		}

		if ( isset( $_POST['booking_date'] ) ) {
			$data['booking_date'] = sanitize_text_field( wp_unslash( $_POST['booking_date'] ) );
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
