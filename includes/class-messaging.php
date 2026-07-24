<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Messaging {

	public $conversation = null;

	public function __construct() {
		$this->conversation = new Smart_Lead_CRM_Conversation();
		add_action( 'rest_api_init', array( $this, 'register_webhook' ) );
		add_action( 'wp_ajax_slcrm_send_reply',          array( $this, 'send_reply_ajax' ) );
		add_action( 'wp_ajax_slcrm_assign_conversation', array( $this, 'assign_conversation_ajax' ) );
	}

	public function register_webhook() {
		register_rest_route( 'slcrm/v1', '/webhook', array(
			'methods'             => array( 'GET', 'POST' ),
			'callback'            => array( $this, 'handle_webhook' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function handle_webhook( WP_REST_Request $request ) {
		$method = $request->get_method();

		if ( 'GET' === $method ) {
			// Parse raw query string to preserve dots in hub.* params
			$qs = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
			parse_str( $qs, $params );
			$mode   = $params['hub.mode'] ?? '';
			$token  = $params['hub.verify_token'] ?? '';
			$chal   = $params['hub.challenge'] ?? '';

			if ( 'subscribe' === $mode && $token === slcrm_get_setting( 'whatsapp_verify_token', '' ) ) {
				echo esc_html( $chal );
				exit;
			}
			return new WP_REST_Response( array( 'error' => 'Invalid verification' ), 403 );
		}

		// POST — inbound message
		$body = json_decode( $request->get_body(), true );
		if ( empty( $body['entry'] ) ) {
			return new WP_REST_Response( array( 'status' => 'no_entry' ), 200 );
		}

		foreach ( $body['entry'] as $entry ) {
			if ( empty( $entry['changes'] ) ) continue;
			foreach ( $entry['changes'] as $change ) {
				if ( empty( $change['value'] ) ) continue;
				$this->process_inbound_message( $change['value'] );
			}
		}

		return new WP_REST_Response( array( 'status' => 'ok' ), 200 );
	}

	public function process_inbound_message( $value ) {
		if ( empty( $value['messages'] ) ) return;

		// Build phone -> name map from contacts block
		$contact_map = array();
		if ( ! empty( $value['contacts'] ) ) {
			foreach ( $value['contacts'] as $contact ) {
				$phone = $contact['wa_id'] ?? '';
				$name  = $contact['profile']['name'] ?? '';
				if ( $phone ) {
					$contact_map[ $phone ] = $name;
				}
			}
		}

		$business_phone_id = $value['metadata']['phone_number_id'] ?? '';

		foreach ( $value['messages'] as $msg ) {
			$message_id = $msg['id'] ?? '';
			if ( $this->conversation->message_exists( $message_id ) ) continue;

			$from    = $msg['from'] ?? '';
			$name    = $contact_map[ $from ] ?? '';
			$text    = $this->extract_message_content( $msg );
			$msg_type = $msg['type'] ?? 'text';

			if ( ! $from ) continue;

			$lead = $this->find_or_create_lead( $from, $name, $text );
			if ( ! $lead ) continue;

			$platform_conv_id = 'wa_' . md5( $lead->id . $business_phone_id );

			$conv_id = $this->conversation->upsert_conversation( array(
				'lead_id'         => $lead->id,
				'conversation_id' => $platform_conv_id,
				'customer_name'   => $name ?: $lead->name,
				'customer_phone'  => $from,
			) );

			if ( ! $conv_id ) {
				$conv = $this->conversation->get_conversation( $platform_conv_id );
				$conv_id = $conv ? (int) $conv->id : 0;
			}
			if ( ! $conv_id ) continue;

			$this->conversation->insert_message( array(
				'conversation_id' => $conv_id,
				'direction'       => 'inbound',
				'message_id'      => $message_id,
				'text'            => $text,
				'message_type'    => $msg_type,
			) );

			do_action( 'slcrm_message_received', $lead, $text, $from );
		}
	}

	public function extract_message_content( $msg ) {
		$type = $msg['type'] ?? 'text';

		switch ( $type ) {
			case 'text':
				return $msg['text']['body'] ?? '';
			case 'image':
				$cap = $msg['image']['caption'] ?? '';
				return $cap ? '[Image] ' . $cap : '[Image]';
			case 'audio':
				return '[Audio message]';
			case 'video':
				$cap = $msg['video']['caption'] ?? '';
				return $cap ? '[Video] ' . $cap : '[Video]';
			case 'document':
				$cap = $msg['document']['caption'] ?? '';
				return $cap ? '[Document] ' . $cap : '[Document]';
			case 'location':
				$lat = $msg['location']['latitude'] ?? '';
				$lng = $msg['location']['longitude'] ?? '';
				return "[Location] $lat,$lng";
			case 'contacts':
				return '[Contacts]';
			case 'sticker':
				return '[Sticker]';
			case 'reaction':
				return '[Reaction: ' . ( $msg['reaction']['emoji'] ?? '' ) . ']';
			default:
				return '[' . ucfirst( $type ) . ']';
		}
	}

	public function find_or_create_lead( $phone, $name, $text ) {
		$db        = slcrm_db();
		$norm      = $this->normalize_phone( $phone );
		$existing  = $db->find_lead_by_phone( $norm );

		if ( ! $existing ) {
			$existing = $db->find_lead_by_phone_partial( $norm );
		}

		if ( $existing ) {
			$update = array( 'last_updated' => current_time( 'mysql' ) );
			if ( ! $existing->name && $name ) {
				$update['name'] = $name;
			}
			if ( $text ) {
				$update['remarks'] = $text;
			}
			// Revive cancelled/booked back to follow-up
			if ( in_array( $existing->status, array( 'cancelled', 'booked' ), true ) ) {
				$update['status'] = 'follow-up';
			}
			$db->update_lead( $existing->id, $update );
			return $db->get_lead( $existing->id );
		}

		$lead_id = $db->insert_lead( array(
			'name'         => $name ?: $phone,
			'phone'        => $norm,
			'status'       => 'new_lead',
			'lead_source'  => 'whatsapp',
			'medium'       => 'chat',
			'remarks'      => $text,
			'created_at'   => current_time( 'mysql' ),
			'last_updated' => current_time( 'mysql' ),
		) );

		return $lead_id ? $db->get_lead( $lead_id ) : null;
	}

	public function send_message( $to, $body ) {
		$mode = slcrm_get_setting( 'whatsapp_connection_mode', 'app_mode' );

		if ( 'app_mode' === $mode ) {
			return array( 'success' => false, 'mode' => 'app_mode', 'deep_link' => $this->build_wa_link( $to, $body ) );
		}

		$token    = slcrm_get_setting( 'whatsapp_access_token', '' );
		$phone_id = slcrm_get_setting( 'whatsapp_phone_number_id', '' );
		$version  = slcrm_get_setting( 'whatsapp_api_version', 'v18.0' );

		if ( ! $token || ! $phone_id ) return array( 'success' => false, 'mode' => $mode, 'error' => 'missing_credentials' );

		$response = wp_remote_post( "https://graph.facebook.com/{$version}/{$phone_id}/messages", array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'messaging_product' => 'whatsapp',
				'to'                => $to,
				'type'              => 'text',
				'text'              => array( 'body' => $body ),
			) ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) return array( 'success' => false, 'mode' => $mode, 'error' => $response->get_error_message() );

		$code = wp_remote_retrieve_response_code( $response );
		$resp_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 ) {
			return array( 'success' => true, 'mode' => $mode, 'message_id' => $resp_body['messages'][0]['id'] ?? '' );
		}
		return array( 'success' => false, 'mode' => $mode, 'error' => $resp_body['error']['message'] ?? "HTTP {$code}" );
	}

	public function build_wa_link( $phone, $text = '' ) {
		$business = slcrm_get_setting( 'whatsapp_business_number', '' );
		$from = $business ? $business : '';
		$url = 'https://wa.me/' . $phone;
		if ( $text ) $url .= '?text=' . rawurlencode( $text );
		return $url;
	}

	public function send_template_message( $to, $template_name, $language = 'en', $components = array() ) {
		$token    = slcrm_get_setting( 'whatsapp_access_token', '' );
		$phone_id = slcrm_get_setting( 'whatsapp_phone_number_id', '' );
		$version  = slcrm_get_setting( 'whatsapp_api_version', 'v18.0' );

		if ( ! $token || ! $phone_id ) return array( 'success' => false, 'error' => 'missing_credentials' );

		$body = array(
			'messaging_product' => 'whatsapp',
			'to'                => $to,
			'type'              => 'template',
			'template'          => array(
				'name'       => $template_name,
				'language'   => array( 'code' => $language ),
				'components' => $components,
			),
		);

		$response = wp_remote_post( "https://graph.facebook.com/{$version}/{$phone_id}/messages", array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) return array( 'success' => false, 'error' => $response->get_error_message() );

		$code = wp_remote_retrieve_response_code( $response );
		$resp_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 ) {
			return array( 'success' => true, 'message_id' => $resp_body['messages'][0]['id'] ?? '' );
		}
		return array( 'success' => false, 'error' => $resp_body['error']['message'] ?? "HTTP {$code}" );
	}

	public function send_auto_reply( $lead ) {
		if ( ! $lead ) return;

		$auto_reply_enabled = slcrm_get_setting( 'auto_reply_enabled', 'no' );
		if ( 'yes' !== $auto_reply_enabled ) return;

		$phone = preg_replace( '/[^0-9]/', '', $lead->phone );
		if ( strlen( $phone ) < 8 ) return;

		$business_name = slcrm_get_setting( 'business_name', get_bloginfo( 'name' ) );
		$template      = slcrm_get_setting( 'auto_reply_template', '' );
		if ( ! $template ) {
			$template = "Hi! Thanks for reaching out to {business_name}. We'll get back to you within 10 minutes. For immediate assistance, call us directly.";
		}

		$message = str_replace( '{business_name}', $business_name, $template );
		$message = str_replace( '{customer_name}', $lead->name ?: '', $message );

		$mode = slcrm_get_setting( 'whatsapp_connection_mode', 'app_mode' );
		if ( 'app_mode' === $mode ) return;

		$result = $this->send_message( $phone, $message );
		if ( is_array( $result ) && $result['success'] ) {
			$conv_id = $this->get_or_create_conversation( $lead );
			if ( $conv_id ) {
				$this->conversation->insert_message( array(
					'conversation_id' => $conv_id,
					'direction'       => 'outbound',
					'text'            => $message,
					'message_type'    => 'text',
					'status'          => 'sent',
				) );
			}
		}
	}

	private function get_or_create_conversation( $lead ) {
		$business_phone_id = slcrm_get_setting( 'whatsapp_phone_number_id', '' );
		$platform_conv_id = 'wa_' . md5( $lead->id . $business_phone_id );
		$conv = $this->conversation->get_conversation( $platform_conv_id );
		if ( $conv ) return (int) $conv->id;
		return $this->conversation->upsert_conversation( array(
			'lead_id'         => $lead->id,
			'conversation_id' => $platform_conv_id,
			'customer_name'   => $lead->name,
			'customer_phone'  => $lead->phone,
		) );
	}

	public function normalize_phone( $phone ) {
		$digits = preg_replace( '/[^0-9]/', '', $phone );
		if ( str_starts_with( $digits, '00' ) ) {
			$digits = substr( $digits, 2 );
		}
		$cc = slcrm_get_setting( 'whatsapp_default_country_code', '91' );
		if ( strlen( $digits ) === 10 ) {
			$digits = $cc . $digits;
		} elseif ( str_starts_with( $digits, '0' ) && strlen( $digits ) > 10 ) {
			$digits = $cc . substr( $digits, 1 );
		}
		return '+' . $digits;
	}

	public function send_reply_ajax() {
		check_ajax_referer( 'slcrm_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );

		$lead_id = absint( $_POST['lead_id'] ?? 0 );
		$conv_id = absint( $_POST['conversation_id'] ?? 0 );
		$text    = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

		if ( ! $lead_id || ! $text ) wp_send_json_error( 'Missing parameters' );

		$lead = slcrm_db()->get_lead( $lead_id );
		if ( ! $lead ) wp_send_json_error( 'Lead not found' );

		$to = preg_replace( '/[^0-9]/', '', $lead->phone );

		$result = $this->send_message( $to, $text );

		if ( is_array( $result ) && $result['success'] ) {
			$this->conversation->insert_message( array(
				'conversation_id' => $conv_id,
				'direction'       => 'outbound',
				'text'            => $text,
				'message_type'    => 'text',
				'status'          => 'sent',
			) );
			$this->wpdb_update_conv_time( $conv_id );
			wp_send_json_success( array( 'sent' => true, 'message_id' => $result['message_id'] ?? '' ) );
		} elseif ( is_array( $result ) && 'app_mode' === ( $result['mode'] ?? '' ) ) {
			$this->conversation->insert_message( array(
				'conversation_id' => $conv_id,
				'direction'       => 'outbound',
				'text'            => $text,
				'message_type'    => 'text',
				'status'          => 'pending',
			) );
			$this->wpdb_update_conv_time( $conv_id );
			wp_send_json_success( array( 'sent' => false, 'deep_link' => $result['deep_link'] ?? '', 'mode' => 'app_mode' ) );
		} else {
			$error = is_array( $result ) ? ( $result['error'] ?? 'Unknown error' ) : 'Failed to send';
			wp_send_json_error( $error );
		}
	}

	public function assign_conversation_ajax() {
		check_ajax_referer( 'slcrm_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );

		$conv_id  = absint( $_POST['conversation_id'] ?? 0 );
		$user_id  = absint( $_POST['assigned_user_id'] ?? 0 );

		if ( ! $conv_id ) wp_send_json_error( 'Missing conversation ID' );

		$this->conversation->assign_to_user( $conv_id, $user_id );
		wp_send_json_success();
	}

	private function wpdb_update_conv_time( $conv_id ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'slcrm_conversations',
			array( 'last_message_at' => current_time( 'mysql' ) ),
			array( 'id' => $conv_id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}
