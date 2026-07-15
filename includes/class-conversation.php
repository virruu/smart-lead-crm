<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Smart_Lead_CRM_Conversation {

	private $wpdb;
	private $tables;

	public function __construct() {
		global $wpdb;
		$this->wpdb   = $wpdb;
		$this->tables = array(
			'conversations' => $wpdb->prefix . 'slcrm_conversations',
			'messages'      => $wpdb->prefix . 'slcrm_messages',
		);
	}

	public function upsert_conversation( $data ) {
		$defaults = array(
			'lead_id'         => 0,
			'platform'        => 'whatsapp',
			'conversation_id' => '',
			'customer_name'   => '',
			'customer_phone'  => '',
			'started_at'      => current_time( 'mysql' ),
			'last_message_at' => current_time( 'mysql' ),
			'status'          => 'open',
			'assigned_user_id' => 0,
		);
		$data = wp_parse_args( $data, $defaults );

		$existing = $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT id FROM {$this->tables['conversations']} WHERE conversation_id = %s", $data['conversation_id']
		) );

		if ( $existing ) {
			$this->wpdb->update(
				$this->tables['conversations'],
				array(
					'last_message_at' => current_time( 'mysql' ),
					'customer_name'   => $data['customer_name'],
					'customer_phone'  => $data['customer_phone'],
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
			return (int) $existing->id;
		}

		$result = $this->wpdb->insert( $this->tables['conversations'], $data,
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ) );
		return $result ? (int) $this->wpdb->insert_id : 0;
	}

	public function get_conversation( $platform_conv_id ) {
		return $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['conversations']} WHERE conversation_id = %s", $platform_conv_id
		) );
	}

	public function get_conversation_by_id( $id ) {
		return $this->wpdb->get_row( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['conversations']} WHERE id = %d", $id
		) );
	}

	public function get_conversations_by_lead( $lead_id ) {
		return $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['conversations']} WHERE lead_id = %d ORDER BY started_at DESC", $lead_id
		) );
	}

	public function insert_message( $data ) {
		$defaults = array(
			'conversation_id' => 0,
			'direction'       => 'inbound',
			'message_id'      => '',
			'text'            => '',
			'message_type'    => 'text',
			'media_url'       => '',
			'status'          => 'received',
			'created_at'      => current_time( 'mysql' ),
		);
		$data = wp_parse_args( $data, $defaults );
		$result = $this->wpdb->insert( $this->tables['messages'], $data,
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
		return $result ? (int) $this->wpdb->insert_id : false;
	}

	public function get_messages_by_lead( $lead_id ) {
		return $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT m.* FROM {$this->tables['messages']} m
			 INNER JOIN {$this->tables['conversations']} c ON m.conversation_id = c.id
			 WHERE c.lead_id = %d ORDER BY m.created_at ASC", $lead_id
		) );
	}

	public function get_messages_by_conversation( $conv_id ) {
		return $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT * FROM {$this->tables['messages']} WHERE conversation_id = %d ORDER BY created_at ASC", $conv_id
		) );
	}

	public function message_exists( $message_id ) {
		if ( empty( $message_id ) ) return false;
		$count = $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tables['messages']} WHERE message_id = %s", $message_id
		) );
		return $count > 0;
	}

	public function assign_to_user( $conv_id, $user_id ) {
		return (bool) $this->wpdb->update(
			$this->tables['conversations'],
			array( 'assigned_user_id' => (int) $user_id ),
			array( 'id' => (int) $conv_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	public function get_stats( $start, $end ) {
		$total_inbound = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tables['messages']} WHERE direction = 'inbound' AND created_at BETWEEN %s AND %s",
			$start, $end
		) );
		$total_outbound = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tables['messages']} WHERE direction = 'outbound' AND created_at BETWEEN %s AND %s",
			$start, $end
		) );
		$unique_senders = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(DISTINCT customer_phone) FROM {$this->tables['conversations']} WHERE started_at BETWEEN %s AND %s AND customer_phone != ''",
			$start, $end
		) );
		$conversations_started = (int) $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tables['conversations']} WHERE started_at BETWEEN %s AND %s",
			$start, $end
		) );
		return array(
			'total_inbound'         => $total_inbound,
			'total_outbound'        => $total_outbound,
			'unique_senders'        => $unique_senders,
			'conversations_started' => $conversations_started,
		);
	}
}
