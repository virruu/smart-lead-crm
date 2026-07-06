<?php
/**
 * Messaging integration — channel-agnostic webhook receiver and message sender.
 *
 * Today this handles WhatsApp Business API (Meta Cloud API). The architecture
 * is designed so that Messenger, Instagram DM, Telegram, or SMS can be added
 * later without changing the database schema or the conversation UI.
 *
 * Webhook URL: https://yoursite.com/wp-json/slcrm/v1/webhook
 *
 * Flow:
 *   REST endpoint → Verify token (GET) / Parse payload (POST) →
 *   Normalize phone → Match lead (exact → E164 → last 10 digits) →
 *   Upsert conversation + message → Fire action
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Messaging class — handles inbound webhooks and outbound replies.
 *
 * @package SmartLeadCRM
 */
class Smart_Lead_CRM_Messaging {

	/**
	 * Conversation storage instance.
	 *
	 * @var Smart_Lead_CRM_Conversation|null
	 */
	public $conversation = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->conversation = new Smart_Lead_CRM_Conversation();

		// Register the REST API webhook endpoint — no rewrite rules needed.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Admin AJAX for sending replies from the lead detail screen.
		add_action( 'wp_ajax_slcrm_send_reply', array( $this, 'send_reply_ajax' ) );

		// Admin AJAX for assigning a conversation to a WP user.
		add_action( 'wp_ajax_slcrm_assign_conversation', array( $this, 'assign_conversation_ajax' ) );
	}

	// ── REST API endpoint ──────────────────────────────────────────

	/**
	 * Register the REST route for the webhook.
	 *
	 * Endpoint: /wp-json/slcrm/v1/webhook
	 * GET  — Meta token verification handshake.
	 * POST — Inbound message payload.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'slcrm/v1',
			'/webhook',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST API callback — routes GET to verification, POST to processing.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function handle_webhook( WP_REST_Request $request ) {
		if ( 'GET' === $request->get_method() ) {
			return $this->verify_webhook( $request );
		}

		if ( 'POST' === $request->get_method() ) {
			return $this->receive_webhook( $request );
		}

		return new WP_REST_Response( array( 'error' => 'Method not allowed' ), 405 );
	}

	/**
	 * Meta webhook verification handshake (GET).
	 *
	 * Meta sends hub.mode, hub.verify_token, hub.challenge as query params
	 * with literal dots. PHP converts dots to underscores in $_GET and
	 * WP_REST_Request::get_query_params(), so we must parse the raw query
	 * string ourselves to preserve the dots.
	 *
	 * @param WP_REST_Request $request Incoming GET request.
	 * @return WP_REST_Response
	 */
	private function verify_webhook( WP_REST_Request $request ) {
		$verify_token = $this->get_setting( 'whatsapp_verify_token' );

		// PHP converts dots to underscores in $_GET / get_query_params().
		// Parse QUERY_STRING directly so hub.mode, hub.verify_token, hub.challenge
		// are preserved with their original dot names.
		$params = $this->parse_raw_query_string();

		$mode      = isset( $params['hub.mode'] ) ? sanitize_text_field( $params['hub.mode'] ) : '';
		$token     = isset( $params['hub.verify_token'] ) ? sanitize_text_field( $params['hub.verify_token'] ) : '';
		$challenge = isset( $params['hub.challenge'] ) ? sanitize_text_field( $params['hub.challenge'] ) : '';

		if ( 'subscribe' === $mode && hash_equals( $verify_token, $token ) && '' !== $challenge ) {
			// Meta requires a plain-text 200 response containing only the challenge.
			status_header( 200 );
			header( 'Content-Type: text/plain' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $challenge;
			exit;
		}

		return new WP_REST_Response(
			array( 'error' => 'Verification failed. Check your verify token in Smart Lead CRM Settings.' ),
			403
		);
	}

	/**
	 * Parse $_SERVER['QUERY_STRING'] manually to preserve dots in param names.
	 *
	 * PHP's built-in query-string parser silently replaces dots (and spaces) in
	 * key names with underscores. Meta's webhook handshake uses hub.mode,
	 * hub.verify_token, and hub.challenge — so we must bypass the built-in
	 * parsing entirely.
	 *
	 * @return array Associative array of raw query params with dots intact.
	 */
	private function parse_raw_query_string() {
		$raw    = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$result = array();

		if ( '' === $raw ) {
			return $result;
		}

		foreach ( explode( '&', $raw ) as $pair ) {
			$parts = explode( '=', $pair, 2 );
			if ( count( $parts ) !== 2 ) {
				continue;
			}
			$key         = urldecode( $parts[0] );
			$value       = urldecode( $parts[1] );
			$result[ $key ] = $value;
		}

		return $result;
	}

	/**
	 * Receive and process an incoming webhook POST from Meta.
	 *
	 * @param WP_REST_Request $request Incoming POST request.
	 * @return WP_REST_Response
	 */
	private function receive_webhook( WP_REST_Request $request ) {
		$raw     = $request->get_body();
		$payload = json_decode( $raw, true );

		if ( empty( $payload ) || ! isset( $payload['entry'] ) ) {
			return new WP_REST_Response( array( 'status' => 'ignored' ), 200 );
		}

		foreach ( $payload['entry'] as $entry ) {
			if ( ! isset( $entry['changes'] ) ) {
				continue;
			}
			foreach ( $entry['changes'] as $change ) {
				if ( ! isset( $change['value']['messages'] ) ) {
					continue;
				}
				$this->process_inbound_message( $change['value'] );
			}
		}

		// Always return 200 immediately so Meta doesn't retry.
		return new WP_REST_Response( array( 'status' => 'received' ), 200 );
	}

	// ── Inbound message processing ─────────────────────────────────

	/**
	 * Process a single inbound WhatsApp message from the webhook payload.
	 *
	 * @param array $value The 'value' block from the webhook change.
	 */
	private function process_inbound_message( $value ) {
		$messages = isset( $value['messages'] ) ? $value['messages'] : array();
		$contacts = isset( $value['contacts'] ) ? $value['contacts'] : array();
		$metadata = isset( $value['metadata'] ) ? $value['metadata'] : array();

		if ( empty( $messages ) ) {
			return;
		}

		// Build a phone → name lookup from the contacts block.
		$contact_map = array();
		foreach ( $contacts as $contact ) {
			$phone = isset( $contact['wa_id'] ) ? $contact['wa_id'] : '';
			$name  = isset( $contact['profile']['name'] ) ? $contact['profile']['name'] : '';
			if ( $phone ) {
				$contact_map[ $phone ] = $name;
			}
		}

		$business_phone_id = isset( $metadata['phone_number_id'] ) ? $metadata['phone_number_id'] : '';

		foreach ( $messages as $message ) {
			$sender_phone = isset( $message['from'] ) ? $message['from'] : '';
			$message_id   = isset( $message['id'] ) ? $message['id'] : '';
			$timestamp    = isset( $message['timestamp'] ) ? $message['timestamp'] : '';
			$message_type = isset( $message['type'] ) ? $message['type'] : 'text';

			// Dedup: skip if we already stored this message ID.
			if ( $this->conversation->message_exists( $message_id ) ) {
				continue;
			}

			// Extract text body and media URL based on message type.
			$text      = '';
			$media_url = '';
			$text      = $this->extract_message_content( $message, $message_type );

			$sender_name = isset( $contact_map[ $sender_phone ] ) ? $contact_map[ $sender_phone ] : '';

			// Convert WhatsApp timestamp (unix) to MySQL datetime.
			$wa_timestamp = ! empty( $timestamp ) ? gmdate( 'Y-m-d H:i:s', (int) $timestamp ) : current_time( 'mysql' );

			// Match or create lead.
			$lead = $this->find_or_create_lead( $sender_phone, $sender_name, $text );

			if ( ! $lead ) {
				continue;
			}

			// Generate a conversation ID (synthesized from lead + business number).
			$platform_conv_id = 'wa_' . md5( $lead->id . '_' . $business_phone_id );

			// Upsert the conversation record.
			$conv_row_id = $this->conversation->upsert_conversation( array(
				'lead_id'         => $lead->id,
				'platform'        => 'whatsapp',
				'conversation_id' => $platform_conv_id,
				'customer_name'   => $sender_name,
				'customer_phone'  => $this->normalize_phone( $sender_phone ),
				'last_message_at' => $wa_timestamp,
				'status'          => 'active',
			) );

			if ( ! $conv_row_id ) {
				$conv_row = $this->conversation->get_conversation( $platform_conv_id );
				$conv_row_id = $conv_row ? $conv_row->id : 0;
			}

			if ( ! $conv_row_id ) {
				continue;
			}

			// Store the message.
			$this->conversation->insert_message( array(
				'conversation_id' => $conv_row_id,
				'direction'       => 'inbound',
				'message_id'      => $message_id,
				'text'            => $text,
				'message_type'    => $message_type,
				'media_url'       => $media_url,
				'status'          => 'received',
				'created_at'      => $wa_timestamp,
			) );

			// Fire action so other code can react (auto-reply, notifications, etc.).
			do_action( 'slcrm_message_received', $lead->id, $message, $sender_phone, $sender_name, 'whatsapp' );
		}
	}

	/**
	 * Extract text content and media URL from a message based on its type.
	 *
	 * Handles text, image, audio, video, document, location, contact, sticker.
	 *
	 * @param array  $message      Raw message array from webhook.
	 * @param string $message_type Message type.
	 * @return string Text representation of the message content.
	 */
	private function extract_message_content( $message, $message_type ) {
		switch ( $message_type ) {
			case 'text':
				return isset( $message['text']['body'] ) ? sanitize_textarea_field( $message['text']['body'] ) : '';

			case 'image':
				$caption = isset( $message['image']['caption'] ) ? sanitize_text_field( $message['image']['caption'] ) : '';
				return '[Image' . ( $caption ? ': ' . $caption : '' ) . ']';

			case 'audio':
				return '[Audio message]';

			case 'video':
				$caption = isset( $message['video']['caption'] ) ? sanitize_text_field( $message['video']['caption'] ) : '';
				return '[Video' . ( $caption ? ': ' . $caption : '' ) . ']';

			case 'document':
				$filename = isset( $message['document']['filename'] ) ? sanitize_text_field( $message['document']['filename'] ) : '';
				return '[Document' . ( $filename ? ': ' . $filename : '' ) . ']';

			case 'location':
				$lat = isset( $message['location']['latitude'] ) ? $message['location']['latitude'] : '';
				$lng = isset( $message['location']['longitude'] ) ? $message['location']['longitude'] : '';
				$name = isset( $message['location']['name'] ) ? sanitize_text_field( $message['location']['name'] ) : '';
				return '[Location' . ( $name ? ': ' . $name : '' ) . ' (' . $lat . ', ' . $lng . ')]';

			case 'contacts':
				return '[Contact card]';

			case 'sticker':
				return '[Sticker]';

			case 'reaction':
				$emoji = isset( $message['reaction']['emoji'] ) ? $message['reaction']['emoji'] : '';
				return '[Reaction: ' . $emoji . ']';

			default:
				return '[' . ucfirst( $message_type ) . ' message]';
		}
	}

	// ── Lead matching ──────────────────────────────────────────────

	/**
	 * Find an existing lead by phone number, or create a new one.
	 *
	 * Matching order:
	 *   1. Exact match (normalized)
	 *   2. E164 normalized match
	 *   3. Last 10 digits fuzzy match
	 *
	 * @param string $phone Raw phone from WhatsApp (e.g. 919876543210).
	 * @param string $name  Sender's WhatsApp profile name.
	 * @param string $text  First message body.
	 * @return object|null Lead row or null on failure.
	 */
	private function find_or_create_lead( $phone, $name, $text ) {
		$db          = smart_lead_crm()->db;
		$helper      = smart_lead_crm()->helper;
		$attribution = smart_lead_crm()->attribution;

		$normalized = $this->normalize_phone( $phone );
		$e164       = $this->normalize_e164( $phone );

		// Step 1: Exact match on normalized phone.
		$lead = $db->find_lead_by_phone( $normalized );
		if ( $lead ) {
			return $lead;
		}

		// Step 2: E164 normalized match.
		if ( $e164 && $e164 !== $normalized ) {
			$lead = $db->find_lead_by_phone( $e164 );
			if ( $lead ) {
				return $lead;
			}
		}

		// Step 3: Last 10 digits fuzzy match.
		$lead = $db->find_lead_by_phone_partial( $normalized );
		if ( $lead ) {
			return $lead;
		}

		// No match — create a new lead attributed to WhatsApp.
		$signals = array( 'lead_action' => 'whatsapp' );
		$attrib  = $attribution->resolve( $signals );

		$lead_data = array(
			'phone'       => $normalized,
			'name'        => $name,
			'status'      => 'pending',
			'lead_source' => $attrib['source'],
			'medium'      => $attrib['medium'],
			'remarks'     => 'Auto-created from WhatsApp message',
		);

		$lead_id = $db->insert_lead( $lead_data );
		if ( ! $lead_id ) {
			return null;
		}

		$helper->log( 'WhatsApp lead created: ID ' . $lead_id . ' phone=' . $normalized . ' name=' . $name );
		return $db->get_lead( $lead_id );
	}

	// ── Phone normalization ───────────────────────────────────────

	/**
	 * Normalize a phone number to a consistent format (digits only).
	 *
	 * @param string $phone Raw phone number.
	 * @return string Normalized phone (digits only).
	 */
	public function normalize_phone( $phone ) {
		$phone = preg_replace( '/[^0-9]/', '', $phone );

		// If the number starts with 00 (international prefix), strip to bare country code.
		if ( 0 === strpos( $phone, '00' ) ) {
			$phone = substr( $phone, 2 );
		}

		// If a local number (starts with 0), replace leading 0 with default country code.
		$default_cc = $this->get_setting( 'whatsapp_default_country_code' );
		if ( '' !== $default_cc && 0 === strpos( $phone, '0' ) ) {
			$phone = $default_cc . substr( $phone, 1 );
		}

		return $phone;
	}

	/**
	 * Normalize a phone number to E164 format (+CCNNNNNNNNN).
	 *
	 * @param string $phone Raw phone number.
	 * @return string E164 formatted phone, or empty string on failure.
	 */
	public function normalize_e164( $phone ) {
		$normalized = $this->normalize_phone( $phone );
		if ( empty( $normalized ) ) {
			return '';
		}
		return '+' . $normalized;
	}

	// ── Outbound messaging ─────────────────────────────────────────

	/**
	 * Send a WhatsApp text message via the Meta Cloud API.
	 *
	 * @param string $to   Recipient phone (digits only, with country code).
	 * @param string $body Message body.
	 * @return array|false API response or false on failure.
	 */
	public function send_message( $to, $body ) {
		$access_token    = $this->get_setting( 'whatsapp_access_token' );
		$phone_number_id = $this->get_setting( 'whatsapp_phone_number_id' );

		if ( empty( $access_token ) || empty( $phone_number_id ) ) {
			return false;
		}

		$api_version = $this->get_setting( 'whatsapp_api_version' );
		if ( empty( $api_version ) ) {
			$api_version = 'v18.0';
		}

		$url = "https://graph.facebook.com/{$api_version}/{$phone_number_id}/messages";

		$payload = array(
			'messaging_product' => 'whatsapp',
			'to'                => $to,
			'type'              => 'text',
			'text'              => array(
				'body' => $body,
			),
		);

		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			smart_lead_crm()->helper->log( 'WhatsApp send error: ' . $response->get_error_message() );
			return false;
		}

		$body_raw = wp_remote_retrieve_body( $response );
		return json_decode( $body_raw, true );
	}

	// ── AJAX handlers ──────────────────────────────────────────────

	/**
	 * AJAX handler for sending a reply from the admin lead detail screen.
	 */
	public function send_reply_ajax() {
		check_ajax_referer( 'slcrm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'smart-lead-crm' ) ) );
		}

		$lead_id         = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$conversation_id = isset( $_POST['conversation_id'] ) ? absint( $_POST['conversation_id'] ) : 0;
		$body            = isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) : '';

		if ( ! $lead_id || empty( $body ) ) {
			wp_send_json_error( array( 'message' => __( 'Lead ID and message body are required.', 'smart-lead-crm' ) ) );
		}

		$lead = smart_lead_crm()->db->get_lead( $lead_id );
		if ( ! $lead ) {
			wp_send_json_error( array( 'message' => __( 'Lead not found.', 'smart-lead-crm' ) ) );
		}

		// Find the conversation for this lead.
		$conversations = $this->conversation->get_conversations_by_lead( $lead_id );
		if ( empty( $conversations ) ) {
			wp_send_json_error( array( 'message' => __( 'No conversation found for this lead.', 'smart-lead-crm' ) ) );
		}

		$conv = $conversations[0];
		$to   = $this->normalize_phone( $conv->customer_phone ? $conv->customer_phone : $lead->phone );
		if ( empty( $to ) ) {
			wp_send_json_error( array( 'message' => __( 'No phone number on this lead.', 'smart-lead-crm' ) ) );
		}

		$response = $this->send_message( $to, $body );

		if ( false === $response || ! isset( $response['messages'][0]['id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to send message. Check your API credentials in Settings.', 'smart-lead-crm' ) ) );
		}

		$message_id = $response['messages'][0]['id'];

		// Store the outbound message in the conversation thread.
		$this->conversation->insert_message( array(
			'conversation_id' => $conv->id,
			'direction'       => 'outbound',
			'message_id'      => $message_id,
			'text'            => $body,
			'message_type'    => 'text',
			'status'          => 'sent',
			'created_at'      => current_time( 'mysql' ),
		) );

		// Update conversation's last_message_at.
		$this->conversation->upsert_conversation( array(
			'conversation_id'  => $conv->conversation_id,
			'last_message_at'  => current_time( 'mysql' ),
			'customer_name'     => $conv->customer_name,
			'customer_phone'    => $conv->customer_phone,
			'status'            => $conv->status,
		) );

		wp_send_json_success( array(
			'message'    => __( 'Message sent.', 'smart-lead-crm' ),
			'message_id' => $message_id,
			'timestamp'  => current_time( 'M j, Y g:i a' ),
			'body'       => $body,
		) );
	}

	/**
	 * AJAX handler for assigning a conversation to a WordPress user.
	 */
	public function assign_conversation_ajax() {
		check_ajax_referer( 'slcrm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'smart-lead-crm' ) ) );
		}

		$conversation_id = isset( $_POST['conversation_id'] ) ? absint( $_POST['conversation_id'] ) : 0;
		$user_id         = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( ! $conversation_id ) {
			wp_send_json_error( array( 'message' => __( 'Conversation ID required.', 'smart-lead-crm' ) ) );
		}

		$this->conversation->assign_to_user( $conversation_id, $user_id );

		$user = $user_id ? get_userdata( $user_id ) : null;
		wp_send_json_success( array(
			'message'    => __( 'Conversation assigned.', 'smart-lead-crm' ),
			'assigned_to' => $user ? $user->display_name : __( 'Unassigned', 'smart-lead-crm' ),
		) );
	}

	// ── Settings helper ────────────────────────────────────────────

	/**
	 * Get a setting value.
	 *
	 * @param string $key Setting key (without prefix).
	 * @return string
	 */
	private function get_setting( $key ) {
		return get_option( 'smart_lead_crm_' . $key, '' );
	}
}
