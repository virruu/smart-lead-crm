<?php
/**
 * Conversation storage — channel-agnostic message thread persistence.
 *
 * Stores conversations and individual messages in dedicated tables so the
 * leads table stays focused on lead/attribution data. Each conversation
 * belongs to a lead and is tagged with a platform (whatsapp, messenger,
 * instagram, telegram, sms, email). This design lets the CRM support
 * multiple communication channels without redesigning the database.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conversation class — manages conversations and messages tables.
 *
 * @package SmartLeadCRM
 */
class Smart_Lead_CRM_Conversation {

	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * Table names.
	 *
	 * @var array
	 */
	protected $tables = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb   = $wpdb;
		$this->tables = array(
			'conversations' => $wpdb->prefix . 'slcrm_conversations',
			'messages'       => $wpdb->prefix . 'slcrm_messages',
		);
	}

	/**
	 * Get a table name.
	 *
	 * @param string $key Table key.
	 * @return string
	 */
	public function table( $key ) {
		return isset( $this->tables[ $key ] ) ? $this->tables[ $key ] : '';
	}

	// ── Conversations ───────────────────────────────────────────────

	/**
	 * Insert or update a conversation record.
	 *
	 * @param array $data Conversation data.
	 * @return int|false Conversation ID.
	 */
	public function upsert_conversation( $data ) {
		$conversation_id = isset( $data['conversation_id'] ) ? $data['conversation_id'] : '';
		if ( empty( $conversation_id ) ) {
			return false;
		}

		$existing = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT id FROM {$this->tables['conversations']} WHERE conversation_id = %s",
				$conversation_id
			)
		);

		if ( $existing ) {
			$this->wpdb->update(
				$this->tables['conversations'],
				array(
					'last_message_at' => isset( $data['last_message_at'] ) ? $data['last_message_at'] : current_time( 'mysql' ),
					'status'          => isset( $data['status'] ) ? $data['status'] : 'active',
					'customer_name'   => isset( $data['customer_name'] ) ? $data['customer_name'] : '',
					'customer_phone'  => isset( $data['customer_phone'] ) ? $data['customer_phone'] : '',
				),
				array( 'id' => $existing ),
				array( '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
			return (int) $existing;
		}

		$defaults = array(
			'platform'        => 'whatsapp',
			'started_at'      => current_time( 'mysql' ),
			'last_message_at' => current_time( 'mysql' ),
			'status'          => 'active',
			'assigned_user_id' => 0,
		);
		$data = wp_parse_args( $data, $defaults );

		$format = $this->get_conversation_format( $data );
		$this->wpdb->insert( $this->tables['conversations'], $data, $format );
		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Get a conversation by its platform conversation ID.
	 *
	 * @param string $conversation_id Platform conversation ID.
	 * @return object|null
	 */
	public function get_conversation( $conversation_id ) {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->tables['conversations']} WHERE conversation_id = %s",
				$conversation_id
			)
		);
	}

	/**
	 * Get conversations for a lead.
	 *
	 * @param int $lead_id Lead ID.
	 * @return array
	 */
	public function get_conversations_by_lead( $lead_id ) {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->tables['conversations']} WHERE lead_id = %d ORDER BY started_at DESC",
				$lead_id
			)
		);
	}

	/**
	 * Assign a conversation to a WordPress user.
	 *
	 * @param int $conversation_id  Conversation row ID.
	 * @param int $assigned_user_id WordPress user ID.
	 * @return int|false
	 */
	public function assign_to_user( $conversation_id, $assigned_user_id ) {
		return $this->wpdb->update(
			$this->tables['conversations'],
			array( 'assigned_user_id' => (int) $assigned_user_id ),
			array( 'id' => (int) $conversation_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	// ── Messages ───────────────────────────────────────────────────

	/**
	 * Insert a message record.
	 *
	 * @param array $data Message data.
	 * @return int|false Message row ID.
	 */
	public function insert_message( $data ) {
		$defaults = array(
			'created_at' => current_time( 'mysql' ),
		);
		$data = wp_parse_args( $data, $defaults );

		$format = $this->get_message_format( $data );
		$this->wpdb->insert( $this->tables['messages'], $data, $format );
		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Get all messages for a conversation, ordered chronologically.
	 *
	 * @param int $conversation_id Conversation row ID.
	 * @return array
	 */
	public function get_messages( $conversation_id ) {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->tables['messages']} WHERE conversation_id = %d ORDER BY created_at ASC",
				$conversation_id
			)
		);
	}

	/**
	 * Get all messages for a lead across all its conversations.
	 *
	 * @param int $lead_id Lead ID.
	 * @return array
	 */
	public function get_messages_by_lead( $lead_id ) {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT m.* FROM {$this->tables['messages']} m
				 INNER JOIN {$this->tables['conversations']} c ON m.conversation_id = c.id
				 WHERE c.lead_id = %d ORDER BY m.created_at ASC",
				$lead_id
			)
		);
	}

	/**
	 * Get the most recent message for a lead.
	 *
	 * @param int $lead_id Lead ID.
	 * @return object|null
	 */
	public function get_last_message( $lead_id ) {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT m.* FROM {$this->tables['messages']} m
				 INNER JOIN {$this->tables['conversations']} c ON m.conversation_id = c.id
				 WHERE c.lead_id = %d ORDER BY m.created_at DESC LIMIT 1",
				$lead_id
			)
		);
	}

	/**
	 * Count messages for a lead.
	 *
	 * @param int $lead_id Lead ID.
	 * @return int
	 */
	public function count_messages( $lead_id ) {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->tables['messages']} m
				 INNER JOIN {$this->tables['conversations']} c ON m.conversation_id = c.id
				 WHERE c.lead_id = %d",
				$lead_id
			)
		);
	}

	/**
	 * Check if a platform message ID already exists (dedup).
	 *
	 * @param string $message_id Platform message ID.
	 * @return bool
	 */
	public function message_exists( $message_id ) {
		$count = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->tables['messages']} WHERE message_id = %s",
				$message_id
			)
		);
		return $count > 0;
	}

	/**
	 * Update message delivery/read status.
	 *
	 * @param string $message_id Platform message ID.
	 * @param string $status     New status (sent, delivered, read).
	 * @return int|false
	 */
	public function update_message_status( $message_id, $status ) {
		return $this->wpdb->update(
			$this->tables['messages'],
			array( 'status' => $status ),
			array( 'message_id' => $message_id ),
			array( '%s' ),
			array( '%s' )
		);
	}

	// ── Stats ──────────────────────────────────────────────────────

	/**
	 * Get message count and response stats for a date range.
	 *
	 * @param string $start Start datetime.
	 * @param string $end   End datetime.
	 * @return array
	 */
	public function get_stats( $start, $end ) {
		$stats = array(
			'total_inbound'  => 0,
			'total_outbound' => 0,
			'unique_senders' => 0,
			'conversations_started' => 0,
		);

		$stats['total_inbound'] = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->tables['messages']} WHERE direction = 'inbound' AND created_at BETWEEN %s AND %s",
				$start,
				$end
			)
		);

		$stats['total_outbound'] = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->tables['messages']} WHERE direction = 'outbound' AND created_at BETWEEN %s AND %s",
				$start,
				$end
			)
		);

		$stats['unique_senders'] = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(DISTINCT customer_phone) FROM {$this->tables['conversations']} WHERE started_at BETWEEN %s AND %s",
				$start,
				$end
			)
		);

		$stats['conversations_started'] = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->tables['conversations']} WHERE started_at BETWEEN %s AND %s",
				$start,
				$end
			)
		);

		return $stats;
	}

	// ── Format helpers ─────────────────────────────────────────────

	/**
	 * Get format array for conversation data.
	 *
	 * @param array $data Data array.
	 * @return array
	 */
	private function get_conversation_format( $data ) {
		$format_map = array(
			'id'                => '%d',
			'lead_id'           => '%d',
			'platform'          => '%s',
			'conversation_id'   => '%s',
			'customer_name'     => '%s',
			'customer_phone'    => '%s',
			'started_at'        => '%s',
			'last_message_at'   => '%s',
			'status'            => '%s',
			'assigned_user_id'  => '%d',
		);
		return $this->build_format( $data, $format_map );
	}

	/**
	 * Get format array for message data.
	 *
	 * @param array $data Data array.
	 * @return array
	 */
	private function get_message_format( $data ) {
		$format_map = array(
			'id'              => '%d',
			'conversation_id' => '%d',
			'direction'       => '%s',
			'message_id'      => '%s',
			'text'            => '%s',
			'message_type'    => '%s',
			'media_url'       => '%s',
			'status'          => '%s',
			'created_at'      => '%s',
		);
		return $this->build_format( $data, $format_map );
	}

	/**
	 * Build a wpdb format array from a data array and format map.
	 *
	 * @param array $data      Data array.
	 * @param array $format_map Format map.
	 * @return array
	 */
	private function build_format( $data, $format_map ) {
		$format = array();
		foreach ( $data as $key => $value ) {
			if ( isset( $format_map[ $key ] ) ) {
				$format[] = $format_map[ $key ];
			}
		}
		return $format;
	}
}
