<?php
/**
 * Leads admin page - list, search, view, edit, delete leads.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$helper   = slcrm_helper();
$db       = slcrm_db();
$action   = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
$lead_id  = isset( $_GET['lead_id'] ) ? absint( $_GET['lead_id'] ) : 0;
$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$status   = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$source   = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : '';
$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page = 20;

// Detail view.
if ( 'view' === $action && $lead_id ) {
	$lead          = $db->get_lead( $lead_id );
	$tracking      = $db->get_tracking( $lead_id );
	$bookings      = $db->get_bookings( $lead_id );
	$notes         = $db->get_notes( $lead_id );
	$statuses      = $helper->get_lead_statuses();
	$sources       = $helper->get_lead_sources();
	$types         = $helper->get_booking_types();
	$conversations = array();
	$all_messages  = array();
	$timeline      = array();
	if ( smart_lead_crm()->messaging ) {
		$conversations = smart_lead_crm()->messaging->conversation->get_conversations_by_lead( $lead_id );
		$all_messages  = smart_lead_crm()->messaging->conversation->get_messages_by_lead( $lead_id );
	}

	// Build unified timeline: tracking visits + messages + bookings + notes, sorted by time.
	foreach ( $tracking as $visit ) {
		$timeline[] = array(
			'time'    => $visit->visit_time,
			'type'    => 'visit',
			'icon'    => 'external',
			'label'   => __( 'Site Visit', 'smart-lead-crm' ),
			'detail'  => $visit->utm_source ? sprintf( __( 'From %s', 'smart-lead-crm' ), $visit->utm_source ) : __( 'Direct visit', 'smart-lead-crm' ),
		);
	}
	foreach ( $all_messages as $msg ) {
		$timeline[] = array(
			'time'    => $msg->created_at,
			'type'    => 'message',
			'icon'    => 'format-chat',
			'label'   => 'inbound' === $msg->direction ? __( 'Customer', 'smart-lead-crm' ) : __( 'Staff', 'smart-lead-crm' ),
			'detail'  => $msg->text,
			'direction' => $msg->direction,
		);
	}
	foreach ( $bookings as $booking ) {
		$timeline[] = array(
			'time'    => $booking->created_at,
			'type'    => 'booking',
			'icon'    => 'calendar-check',
			'label'   => __( 'Booking Created', 'smart-lead-crm' ),
			'detail'  => $booking->route . ' — ' . $helper->format_currency( $booking->fare ),
		);
	}
	foreach ( $notes as $note ) {
		$timeline[] = array(
			'time'    => $note->created_at,
			'type'    => 'note',
			'icon'    => 'format-aside',
			'label'   => __( 'Note', 'smart-lead-crm' ),
			'detail'  => $note->note,
		);
	}
	// Sort timeline by time ascending.
	usort( $timeline, function( $a, $b ) {
		return strtotime( $a['time'] ) - strtotime( $b['time'] );
	} );

	// Compute customer intelligence stats.
	$total_conversations = count( $conversations );
	$total_messages      = count( $all_messages );
	$last_contacted      = ! empty( $all_messages ) ? end( $all_messages )->created_at : ( $lead ? $lead->last_updated : '' );
	$customer_ltv        = 0;
	foreach ( $bookings as $b ) {
		if ( 'booked' === $b->status ) {
			$customer_ltv += (float) $b->fare;
		}
	}
	$wa_number = $lead ? preg_replace( '/[^0-9]/', '', $lead->phone ) : '';
	?>
	<div class="wrap slcrm-wrap">
		<h1 class="slcrm-title">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads' ) ); ?>" class="slcrm-back-btn">&larr; <?php esc_html_e( 'Back to Leads', 'smart-lead-crm' ); ?></a>
		</h1>

		<?php if ( ! $lead ) : ?>
			<p><?php esc_html_e( 'Lead not found.', 'smart-lead-crm' ); ?></p>
		<?php else : ?>
		<div class="slcrm-lead-detail">
			<div class="slcrm-lead-main">
				<div class="slcrm-card">
					<h2>
						<span class="slcrm-lead-id">Lead #<?php echo esc_html( $lead->id ); ?></span>
						<?php echo esc_html( $lead->name ? $lead->name : $lead->phone ); ?>
						<span class="slcrm-status-badge slcrm-status-<?php echo esc_attr( $lead->status ); ?>"><?php echo esc_html( $helper->get_status_label( $lead->status ) ); ?></span>
					</h2>
					<?php if ( ! empty( $wa_number ) ) : ?>
					<a href="https://wa.me/<?php echo esc_attr( $wa_number ); ?>" target="_blank" class="button button-primary slcrm-wa-chat-btn">
						<span class="dashicons dashicons-whatsapp"></span> <?php esc_html_e( 'Chat on WhatsApp', 'smart-lead-crm' ); ?>
					</a>
					<?php endif; ?>
					<div class="slcrm-intelligence-grid">
						<div class="slcrm-intel-item">
							<span class="slcrm-intel-label"><?php esc_html_e( 'Total Conversations', 'smart-lead-crm' ); ?></span>
							<span class="slcrm-intel-value"><?php echo esc_html( number_format_i18n( $total_conversations ) ); ?></span>
						</div>
						<div class="slcrm-intel-item">
							<span class="slcrm-intel-label"><?php esc_html_e( 'Total Messages', 'smart-lead-crm' ); ?></span>
							<span class="slcrm-intel-value"><?php echo esc_html( number_format_i18n( $total_messages ) ); ?></span>
						</div>
						<div class="slcrm-intel-item">
							<span class="slcrm-intel-label"><?php esc_html_e( 'Last Contacted', 'smart-lead-crm' ); ?></span>
							<span class="slcrm-intel-value"><?php echo esc_html( $last_contacted ? $helper->format_date( $last_contacted, 'M j, Y g:i a' ) : 'â' ); ?></span>
						</div>
						<div class="slcrm-intel-item">
							<span class="slcrm-intel-label"><?php esc_html_e( 'Customer LTV', 'smart-lead-crm' ); ?></span>
							<span class="slcrm-intel-value"><?php echo esc_html( $helper->format_currency( $customer_ltv ) ); ?></span>
						</div>
					</div>
					<table class="slcrm-detail-table">
						<tr><th><?php esc_html_e( 'Phone', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->phone ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Email', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->email ? $lead->email : '—' ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Status', 'smart-lead-crm' ); ?></th><td>
							<select id="slcrm-lead-status" data-lead-id="<?php echo esc_attr( $lead->id ); ?>">
								<?php foreach ( $statuses as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $lead->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td></tr>
						<tr><th><?php esc_html_e( 'Lead Source', 'smart-lead-crm' ); ?></th><td>
							<select id="slcrm-lead-source" data-lead-id="<?php echo esc_attr( $lead->id ); ?>">
								<?php foreach ( $sources as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $lead->lead_source, $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td></tr>
						<tr><th><?php esc_html_e( 'Source', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $helper->get_source_label( $lead->lead_source ) ); ?> <span class="description">(<?php echo esc_html( $lead->medium ? $lead->medium : '—' ); ?>)</span></td></tr>
						<tr><th><?php esc_html_e( 'Campaign', 'smart-lead-crm' ); ?></th><td><input type="text" id="slcrm-lead-campaign" value="<?php echo esc_attr( $lead->campaign ); ?>" /></td></tr>
						<tr><th><?php esc_html_e( 'Ad Group', 'smart-lead-crm' ); ?></th><td><input type="text" id="slcrm-lead-ad-group" value="<?php echo esc_attr( $lead->ad_group ); ?>" /></td></tr>
						<tr><th><?php esc_html_e( 'Keyword', 'smart-lead-crm' ); ?></th><td><input type="text" id="slcrm-lead-keyword" value="<?php echo esc_attr( $lead->keyword ); ?>" /></td></tr>
						<tr><th><?php esc_html_e( 'GCLID', 'smart-lead-crm' ); ?></th><td><code><?php echo esc_html( $lead->gclid ? substr( $lead->gclid, 0, 40 ) . '…' : '—' ); ?></code></td></tr>
						<tr><th><?php esc_html_e( 'GBRAID', 'smart-lead-crm' ); ?></th><td><code><?php echo esc_html( $lead->gbraid ? substr( $lead->gbraid, 0, 40 ) . '…' : '—' ); ?></code></td></tr>
						<tr><th><?php esc_html_e( 'WBRAID', 'smart-lead-crm' ); ?></th><td><code><?php echo esc_html( $lead->wbraid ? substr( $lead->wbraid, 0, 40 ) . '…' : '—' ); ?></code></td></tr>
						<tr><th><?php esc_html_e( 'UTM Source', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->utm_source ? $lead->utm_source : '—' ); ?></td></tr>
						<tr><th><?php esc_html_e( 'UTM Medium', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->utm_medium ? $lead->utm_medium : '—' ); ?></td></tr>
						<tr><th><?php esc_html_e( 'UTM Campaign', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->utm_campaign ? $lead->utm_campaign : '—' ); ?></td></tr>
						<tr><th><?php esc_html_e( 'UTM Term', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->utm_term ? $lead->utm_term : '—' ); ?></td></tr>
						<tr><th><?php esc_html_e( 'UTM Content', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->utm_content ? $lead->utm_content : '—' ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Booking Route', 'smart-lead-crm' ); ?></th><td><input type="text" id="slcrm-lead-route" value="<?php echo esc_attr( $lead->booking_route ); ?>" /></td></tr>
						<tr><th><?php esc_html_e( 'Booking Date', 'smart-lead-crm' ); ?></th><td><input type="date" id="slcrm-lead-booking-date" value="<?php echo esc_attr( $lead->booking_date ? $lead->booking_date : '' ); ?>" /></td></tr>
						<tr><th><?php esc_html_e( 'Follow-up Date', 'smart-lead-crm' ); ?></th><td><input type="date" id="slcrm-lead-follow-up-date" value="<?php echo esc_attr( isset( $lead->follow_up_date ) && $lead->follow_up_date ? $lead->follow_up_date : '' ); ?>" /></td></tr>
						<tr><th><?php esc_html_e( 'Landing Page', 'smart-lead-crm' ); ?></th><td><a href="<?php echo esc_url( $lead->landing_page ); ?>" target="_blank"><?php echo esc_html( $lead->landing_page ); ?></a></td></tr>
						<tr><th><?php esc_html_e( 'Device', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->device ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Browser', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->browser ); ?></td></tr>
						<tr><th><?php esc_html_e( 'IP Address', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->ip ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Referer', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->referer ? $lead->referer : '—' ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Created', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $helper->format_date( $lead->created_at, 'M j, Y g:i a' ) ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Remarks', 'smart-lead-crm' ); ?></th><td><textarea id="slcrm-lead-remarks" rows="3"><?php echo esc_textarea( $lead->remarks ); ?></textarea></td></tr>
					</table>
					<div class="slcrm-detail-actions">
						<button class="button button-primary" id="slcrm-save-lead" data-lead-id="<?php echo esc_attr( $lead->id ); ?>"><?php esc_html_e( 'Save Changes', 'smart-lead-crm' ); ?></button>
						<button class="button button-link-delete" id="slcrm-delete-lead" data-lead-id="<?php echo esc_attr( $lead->id ); ?>"><?php esc_html_e( 'Delete Lead', 'smart-lead-crm' ); ?></button>
					</div>
				</div>

				<div class="slcrm-card">
					<h2><?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></h2>
					<?php if ( $bookings ) : ?>
					<table class="slcrm-table">
						<thead><tr><th><?php esc_html_e( 'Type', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Route', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Fare', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Date', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Driver', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Status', 'smart-lead-crm' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $bookings as $booking ) : ?>
							<tr>
								<td><?php echo esc_html( $helper->get_booking_type_label( $booking->booking_type ) ); ?></td>
								<td><?php echo esc_html( $booking->route ); ?></td>
								<td><?php echo esc_html( $helper->format_currency( $booking->fare ) ); ?></td>
								<td><?php echo esc_html( $helper->format_date( $booking->booking_date ) ); ?></td>
								<td><?php echo esc_html( $booking->driver ? $booking->driver : '—' ); ?></td>
								<td>
									<select class="slcrm-booking-status" data-booking-id="<?php echo esc_attr( $booking->id ); ?>" data-lead-id="<?php echo esc_attr( $lead->id ); ?>">
										<?php foreach ( $statuses as $key => $label ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $booking->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<?php else : ?>
						<p><?php esc_html_e( 'No bookings yet.', 'smart-lead-crm' ); ?></p>
					<?php endif; ?>

					<h3><?php esc_html_e( 'Add Booking', 'smart-lead-crm' ); ?></h3>
					<div class="slcrm-booking-form">
						<select id="slcrm-booking-type">
							<?php foreach ( $types as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<input type="text" id="slcrm-booking-route" placeholder="<?php esc_attr_e( 'Route', 'smart-lead-crm' ); ?>" />
						<input type="number" id="slcrm-booking-fare" placeholder="<?php esc_attr_e( 'Fare', 'smart-lead-crm' ); ?>" step="0.01" />
						<input type="date" id="slcrm-booking-date" />
						<input type="text" id="slcrm-booking-driver" placeholder="<?php esc_attr_e( 'Driver', 'smart-lead-crm' ); ?>" />
						<select id="slcrm-booking-status">
							<option value="pending"><?php esc_html_e( 'Pending', 'smart-lead-crm' ); ?></option>
							<option value="booked"><?php esc_html_e( 'Booked', 'smart-lead-crm' ); ?></option>
							<option value="cancelled"><?php esc_html_e( 'Cancelled', 'smart-lead-crm' ); ?></option>
						</select>
						<button class="button button-primary" id="slcrm-add-booking" data-lead-id="<?php echo esc_attr( $lead->id ); ?>"><?php esc_html_e( 'Add', 'smart-lead-crm' ); ?></button>
					</div>
				</div>

				<div class="slcrm-card">
					<h2><?php esc_html_e( 'Follow-up Notes', 'smart-lead-crm' ); ?></h2>
					<div class="slcrm-notes-list" id="slcrm-notes-list">
						<?php if ( $notes ) : foreach ( $notes as $note ) : ?>
							<div class="slcrm-note">
								<div class="slcrm-note-text"><?php echo esc_html( $note->note ); ?></div>
								<div class="slcrm-note-meta"><?php echo esc_html( $helper->format_date( $note->created_at, 'M j, Y g:i a' ) ); ?></div>
							</div>
						<?php endforeach; else : ?>
							<p><?php esc_html_e( 'No notes yet.', 'smart-lead-crm' ); ?></p>
						<?php endif; ?>
					</div>
					<div class="slcrm-note-form">
						<textarea id="slcrm-note-text" rows="2" placeholder="<?php esc_attr_e( 'Add a follow-up note...', 'smart-lead-crm' ); ?>"></textarea>
						<button class="button" id="slcrm-add-note" data-lead-id="<?php echo esc_attr( $lead->id ); ?>"><?php esc_html_e( 'Add Note', 'smart-lead-crm' ); ?></button>
					</div>
				</div>

				<div class="slcrm-card slcrm-timeline-card">
					<h2><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Lead Timeline', 'smart-lead-crm' ); ?></h2>
					<div class="slcrm-timeline">
						<?php if ( $timeline ) : foreach ( $timeline as $event ) : ?>
							<div class="slcrm-timeline-item slcrm-timeline-<?php echo esc_attr( $event['type'] ); ?>">
								<div class="slcrm-timeline-dot"><span class="dashicons dashicons-<?php echo esc_attr( $event['icon'] ); ?>"></span></div>
								<div class="slcrm-timeline-content">
									<div class="slcrm-timeline-time"><?php echo esc_html( $helper->format_date( $event['time'], 'M j, Y g:i a' ) ); ?></div>
									<div class="slcrm-timeline-label"><?php echo esc_html( $event['label'] ); ?></div>
									<?php if ( ! empty( $event['detail'] ) ) : ?>
										<div class="slcrm-timeline-detail <?php echo isset( $event['direction'] ) ? 'slcrm-timeline-' . esc_attr( $event['direction'] ) : ''; ?>"><?php echo esc_html( $event['detail'] ); ?></div>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; else : ?>
							<p><?php esc_html_e( 'No activity yet.', 'smart-lead-crm' ); ?></p>
					<?php endif; ?>
					</div>
				</div>

				<?php if ( $conversations ) : ?>
				<div class="slcrm-card slcrm-conv-card">
					<h2><span class="dashicons dashicons-format-chat"></span> <?php esc_html_e( 'Conversation', 'smart-lead-crm' ); ?>
						<?php if ( $conversations[0]->platform ) : ?>
							<span class="slcrm-platform-badge slcrm-platform-<?php echo esc_attr( $conversations[0]->platform ); ?>"><?php echo esc_html( ucfirst( $conversations[0]->platform ) ); ?></span>
						<?php endif; ?>
					</h2>
					<?php
					$conv      = $conversations[0];
					$conv_msgs = smart_lead_crm()->messaging->conversation->get_messages( $conv->id );
					$assignees = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author' ), 'number' => 50 ) );
					?>
					<div class="slcrm-conv-meta">
						<span><strong><?php esc_html_e( 'Customer:', 'smart-lead-crm' ); ?></strong> <?php echo esc_html( $conv->customer_name ? $conv->customer_name : '—' ); ?></span>
						<span><strong><?php esc_html_e( 'Phone:', 'smart-lead-crm' ); ?></strong> <?php echo esc_html( $conv->customer_phone ? $conv->customer_phone : '—' ); ?></span>
						<span><strong><?php esc_html_e( 'Started:', 'smart-lead-crm' ); ?></strong> <?php echo esc_html( $helper->format_date( $conv->started_at, 'M j, Y g:i a' ) ); ?></span>
						<span><strong><?php esc_html_e( 'Last Message:', 'smart-lead-crm' ); ?></strong> <?php echo esc_html( $helper->format_date( $conv->last_message_at, 'M j, Y g:i a' ) ); ?></span>
					</div>
					<div class="slcrm-conv-assign">
						<label><?php esc_html_e( 'Assigned To:', 'smart-lead-crm' ); ?></label>
						<select id="slcrm-conv-assign" data-conversation-id="<?php echo esc_attr( $conv->id ); ?>">
							<option value="0"><?php esc_html_e( 'Unassigned', 'smart-lead-crm' ); ?></option>
							<?php foreach ( $assignees as $user ) : ?>
								<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $conv->assigned_user_id, $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="slcrm-conv-thread" id="slcrm-conv-thread">
						<?php if ( $conv_msgs ) : foreach ( $conv_msgs as $msg ) : ?>
							<div class="slcrm-conv-msg slcrm-conv-<?php echo esc_attr( $msg->direction ); ?>">
								<div class="slcrm-conv-msg-type"><?php echo esc_html( ucfirst( $msg->message_type ) ); ?></div>
								<div class="slcrm-conv-msg-body"><?php echo esc_html( $msg->text ); ?></div>
								<div class="slcrm-conv-msg-meta"><?php echo esc_html( $helper->format_date( $msg->created_at, 'M j, Y g:i a' ) ); ?></div>
							</div>
						<?php endforeach; else : ?>
							<p><?php esc_html_e( 'No messages yet.', 'smart-lead-crm' ); ?></p>
					<?php endif; ?>
					</div>
					<div class="slcrm-conv-reply-form">
						<textarea id="slcrm-conv-reply" rows="2" placeholder="<?php esc_attr_e( 'Type a reply...', 'smart-lead-crm' ); ?>"></textarea>
						<button class="button button-primary" id="slcrm-send-reply" data-lead-id="<?php echo esc_attr( $lead->id ); ?>" data-conversation-id="<?php echo esc_attr( $conv->id ); ?>"><?php esc_html_e( 'Send Reply', 'smart-lead-crm' ); ?></button>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<div class="slcrm-lead-sidebar">
				<div class="slcrm-card">
					<h2><?php esc_html_e( 'Tracking History', 'smart-lead-crm' ); ?></h2>
					<?php if ( $tracking ) : foreach ( $tracking as $visit ) : ?>
						<div class="slcrm-visit">
							<div class="slcrm-visit-time"><?php echo esc_html( $helper->format_date( $visit->visit_time, 'M j, Y g:i a' ) ); ?></div>
							<?php if ( $visit->gclid ) : ?><div><strong>GCLID:</strong> <?php echo esc_html( substr( $visit->gclid, 0, 20 ) ); ?>...</div><?php endif; ?>
							<?php if ( $visit->gbraid ) : ?><div><strong>GBRAID:</strong> <?php echo esc_html( substr( $visit->gbraid, 0, 20 ) ); ?>...</div><?php endif; ?>
							<?php if ( $visit->wbraid ) : ?><div><strong>WBRAID:</strong> <?php echo esc_html( substr( $visit->wbraid, 0, 20 ) ); ?>...</div><?php endif; ?>
							<?php if ( $visit->utm_source ) : ?><div><strong>UTM Source:</strong> <?php echo esc_html( $visit->utm_source ); ?></div><?php endif; ?>
							<?php if ( $visit->utm_campaign ) : ?><div><strong>UTM Campaign:</strong> <?php echo esc_html( $visit->utm_campaign ); ?></div><?php endif; ?>
							<?php if ( $visit->utm_medium ) : ?><div><strong>UTM Medium:</strong> <?php echo esc_html( $visit->utm_medium ); ?></div><?php endif; ?>
							<div><strong>Device:</strong> <?php echo esc_html( $visit->device ); ?> / <?php echo esc_html( $visit->browser ); ?></div>
						</div>
					<?php endforeach; else : ?>
						<p><?php esc_html_e( 'No tracking data.', 'smart-lead-crm' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php
	return;
}

// List view.
$args = array(
	'number'  => $per_page,
	'offset'  => ( $paged - 1 ) * $per_page,
	'search'  => $search,
	'status'  => $status,
	'source'  => $source,
	'orderby' => 'last_updated',
	'order'   => 'DESC',
);

$leads     = $db->get_leads( $args );
$total     = $db->count_leads( $args );
$pages     = ceil( $total / $per_page );
$statuses  = $helper->get_lead_statuses();
$sources   = $helper->get_lead_sources();
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title">
		<span class="dashicons dashicons-email-alt"></span>
		<?php esc_html_e( 'Leads', 'smart-lead-crm' ); ?>
	</h1>

	<div class="slcrm-leads-toolbar">
		<form method="get" class="slcrm-search-form">
			<input type="hidden" name="page" value="smart-lead-crm-leads" />
			<input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search phone, name, route, campaign...', 'smart-lead-crm' ); ?>" class="slcrm-search-input" />
			<select name="status">
				<option value=""><?php esc_html_e( 'All Statuses', 'smart-lead-crm' ); ?></option>
				<?php foreach ( $statuses as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="source">
				<option value=""><?php esc_html_e( 'All Sources', 'smart-lead-crm' ); ?></option>
				<?php foreach ( $sources as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $source, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'smart-lead-crm' ); ?></button>
		</form>
	</div>

	<?php if ( $leads ) : ?>
	<table class="slcrm-table slcrm-leads-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Lead #', 'smart-lead-crm' ); ?></th>
				<th><?php esc_html_e( 'Name', 'smart-lead-crm' ); ?></th>
				<th><?php esc_html_e( 'Phone', 'smart-lead-crm' ); ?></th>
				<th><?php esc_html_e( 'Status', 'smart-lead-crm' ); ?></th>
				<th><?php esc_html_e( 'Source', 'smart-lead-crm' ); ?></th>
				<th><?php esc_html_e( 'Last Message', 'smart-lead-crm' ); ?></th>
				<th><?php esc_html_e( 'Last Updated', 'smart-lead-crm' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'smart-lead-crm' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $leads as $lead ) : ?>
			<tr>
				<td><strong>#<?php echo esc_html( $lead->id ); ?></strong></td>
				<td><?php echo esc_html( $lead->name ? $lead->name : '—' ); ?></td>
				<td>
					<a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $lead->phone ) ); ?>" target="_blank" title="<?php esc_attr_e( 'Chat on WhatsApp', 'smart-lead-crm' ); ?>">
						<?php echo esc_html( $lead->phone ); ?>
					</a>
				</td>
				<td><span class="slcrm-status-badge slcrm-status-<?php echo esc_attr( $lead->status ); ?>"><?php echo esc_html( $helper->get_status_label( $lead->status ) ); ?></span></td>
				<td><?php echo esc_html( $helper->get_source_label( $lead->lead_source ) ); ?></td>
				<td class="slcrm-last-message"><?php echo esc_html( $lead->remarks ? wp_trim_words( $lead->remarks, 8, '…' ) : '—' ); ?></td>
				<td><?php echo esc_html( $helper->format_date( $lead->last_updated, 'M j, Y g:i a' ) ); ?></td>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads&action=view&lead_id=' . $lead->id ) ); ?>" class="button button-small"><?php esc_html_e( 'View', 'smart-lead-crm' ); ?></a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<div class="slcrm-pagination">
		<?php
		echo wp_kses_post(
			paginate_links(
				array(
					'base'      => add_query_arg( array( 'paged' => '%#%' ) ),
					'format'    => '',
					'current'   => $paged,
					'total'     => $pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				)
			)
		);
		?>
	</div>
	<?php else : ?>
		<p><?php esc_html_e( 'No leads yet. Leads are automatically created when visitors click WhatsApp or phone (tel:) links on your site. Make sure your site has wa.me or tel: links and the tracker is running.', 'smart-lead-crm' ); ?></p>
	<?php endif; ?>
</div>
