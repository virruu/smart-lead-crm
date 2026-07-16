<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$helper   = slcrm_helper();
$settings = smart_lead_crm()->settings;
$mode     = $settings->get_whatsapp_mode();
$can_send = ( 'app_mode' !== $mode ) && $settings->is_cloud_api_configured();
$db       = slcrm_db();
$action   = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
$statuses = $helper->get_lead_statuses();
$sources  = $helper->get_lead_sources();

/* ═══ DETAIL VIEW ═══ */
if ( 'view' === $action ) {
	$lead_id = absint( $_GET['lead_id'] ?? 0 );
	$lead    = $lead_id ? $db->get_lead( $lead_id ) : null;
	$tracking = $lead ? $db->get_tracking( $lead_id )    : array();
	$bookings = $lead ? $db->get_bookings( $lead_id )    : array();
	$notes    = $lead ? $db->get_notes( $lead_id )       : array();
	$conversations = array();
	$all_messages  = array();
	$timeline      = array();

	if ( $lead && smart_lead_crm()->messaging && smart_lead_crm()->messaging->conversation ) {
		$conv_obj = smart_lead_crm()->messaging->conversation;
		$conversations = $conv_obj->get_conversations_by_lead( $lead_id );
		$all_messages  = $conv_obj->get_messages_by_lead( $lead_id );
	}

	if ( $lead ) {
		foreach ( $tracking as $v ) {
			$timeline[] = array( 'time'=>$v->visit_time, 'type'=>'visit', 'label'=>__('Site Visit','smart-lead-crm'), 'detail'=>$v->utm_source ? sprintf(__('From %s','smart-lead-crm'), esc_html($v->utm_source)) : __('Direct visit','smart-lead-crm') );
		}
		foreach ( $all_messages as $m ) {
			$timeline[] = array( 'time'=>$m->created_at, 'type'=>'message', 'label'=>'inbound'===$m->direction?__('Customer Message','smart-lead-crm'):__('Staff Reply','smart-lead-crm'), 'detail'=>$m->text, 'direction'=>$m->direction );
		}
		foreach ( $bookings as $b ) {
			$timeline[] = array( 'time'=>$b->created_at, 'type'=>'booking', 'label'=>__('Booking Created','smart-lead-crm'), 'detail'=>$b->route.' — '.$helper->format_currency($b->fare) );
		}
		foreach ( $notes as $n ) {
			$timeline[] = array( 'time'=>$n->created_at, 'type'=>'note', 'label'=>__('Note Added','smart-lead-crm'), 'detail'=>$n->note );
		}
		usort( $timeline, function($a,$b){ return strtotime($a['time'])-strtotime($b['time']); } );
	}

	$ltv = 0;
	foreach ( $bookings as $b ) { if ( 'booked'===$b->status ) $ltv += (float)$b->fare; }
	$wa_no = $lead ? preg_replace('/[^0-9]/','',$lead->phone) : '';
	$initials = $lead ? strtoupper(substr($lead->name?:$lead->phone,0,1)) : '?';
	?>
	<div class="wrap slcrm-wrap">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads' ) ); ?>" class="slcrm-back-btn">
			<span class="dashicons dashicons-arrow-left-alt2"></span> <?php esc_html_e( 'Back to Leads', 'smart-lead-crm' ); ?>
		</a>

		<?php if ( ! $lead ) : ?>
			<div class="slcrm-card slcrm-empty-state"><span class="dashicons dashicons-warning"></span><h3><?php esc_html_e( 'Lead not found.', 'smart-lead-crm' ); ?></h3></div>
		<?php else : ?>

		<div class="slcrm-lead-detail">
			<div class="slcrm-lead-main">

				<!-- Profile -->
				<div class="slcrm-card">
					<div class="slcrm-lead-header">
						<div class="slcrm-lead-avatar"><?php echo esc_html( $initials ); ?></div>
						<div class="slcrm-lead-header-info">
							<h2>
								<span class="slcrm-lead-id">Lead #<?php echo esc_html( $lead->id ); ?></span>
								<?php echo esc_html( $lead->name ?: 'Website Visitor' ); ?>
								<span class="slcrm-status-badge slcrm-status-<?php echo esc_attr( $lead->status ); ?>"><?php echo esc_html( $helper->get_status_label( $lead->status ) ); ?></span>
							</h2>
							<div class="slcrm-lead-phone">
								<?php if ( $lead->phone ) : ?>
									<span class="dashicons dashicons-phone" style="font-size:14px;width:14px;height:14px;"></span>
									<span><?php echo esc_html( $lead->phone ); ?></span>
								<?php else : ?>
									<span style="color:var(--gray-400);font-style:italic;font-size:13px;"><?php esc_html_e( 'Phone number will appear when customer messages you on WhatsApp', 'smart-lead-crm' ); ?></span>
								<?php endif; ?>
								<?php if ( $wa_no ) : ?>
									<a href="https://wa.me/<?php echo esc_attr( $wa_no ); ?>" target="_blank" class="slcrm-wa-link">
										<span class="dashicons dashicons-whatsapp"></span> <?php esc_html_e( 'Chat on WhatsApp', 'smart-lead-crm' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
						<?php if ( $wa_no ) : ?>
						<a href="https://wa.me/<?php echo esc_attr( $wa_no ); ?>" target="_blank" class="slcrm-wa-chat-btn button">
							<span class="dashicons dashicons-whatsapp"></span> <?php esc_html_e( 'Open WhatsApp', 'smart-lead-crm' ); ?>
						</a>
						<?php endif; ?>
					</div>

					<div class="slcrm-intelligence-grid">
						<div class="slcrm-intel-item"><span class="slcrm-intel-label"><?php esc_html_e( 'Conversations', 'smart-lead-crm' ); ?></span><span class="slcrm-intel-value"><?php echo esc_html( number_format_i18n( count( $conversations ) ) ); ?></span></div>
						<div class="slcrm-intel-item"><span class="slcrm-intel-label"><?php esc_html_e( 'Messages', 'smart-lead-crm' ); ?></span><span class="slcrm-intel-value"><?php echo esc_html( number_format_i18n( count( $all_messages ) ) ); ?></span></div>
						<div class="slcrm-intel-item"><span class="slcrm-intel-label"><?php esc_html_e( 'Last Contacted', 'smart-lead-crm' ); ?></span><span class="slcrm-intel-value" style="font-size:13px;padding-top:4px;"><?php echo esc_html( $all_messages ? $helper->format_date( end( $all_messages )->created_at, 'M j, g:i a' ) : '—' ); ?></span></div>
						<div class="slcrm-intel-item"><span class="slcrm-intel-label"><?php esc_html_e( 'Customer LTV', 'smart-lead-crm' ); ?></span><span class="slcrm-intel-value slcrm-currency"><?php echo esc_html( $helper->format_currency( $ltv ) ); ?></span></div>
					</div>

					<div id="slcrm-save-notice" class="slcrm-notice" style="margin-bottom:12px;"></div>
					<table class="slcrm-detail-table">
						<tr><th><?php esc_html_e( 'Status', 'smart-lead-crm' ); ?></th><td><select id="slcrm-lead-status" data-lead-id="<?php echo esc_attr( $lead->id ); ?>"><?php foreach ( $statuses as $k=>$l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $lead->status, $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></td></tr>
						<tr><th><?php esc_html_e( 'Lead Source', 'smart-lead-crm' ); ?></th><td><select id="slcrm-lead-source"><?php foreach ( $sources as $k=>$l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $lead->lead_source, $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></td></tr>
						<tr><th><?php esc_html_e( 'Campaign', 'smart-lead-crm' ); ?></th><td><input type="text" id="slcrm-lead-campaign" value="<?php echo esc_attr( $lead->campaign ); ?>" /></td></tr>
						<tr><th><?php esc_html_e( 'Ad Group', 'smart-lead-crm' ); ?></th><td><input type="text" id="slcrm-lead-ad-group" value="<?php echo esc_attr( $lead->ad_group ); ?>" /></td></tr>
						<tr><th><?php esc_html_e( 'Keyword', 'smart-lead-crm' ); ?></th><td><input type="text" id="slcrm-lead-keyword" value="<?php echo esc_attr( $lead->keyword ); ?>" /></td></tr>
						<tr><th><?php esc_html_e( 'Booking Route', 'smart-lead-crm' ); ?></th><td><input type="text" id="slcrm-lead-route" value="<?php echo esc_attr( $lead->booking_route ); ?>" placeholder="e.g. HBL – BLR Airport" /></td></tr>
						<tr><th><?php esc_html_e( 'Booking Date', 'smart-lead-crm' ); ?></th><td><input type="date" id="slcrm-lead-booking-date" value="<?php echo esc_attr( $lead->booking_date ?: '' ); ?>" /></td></tr>
						<tr><th><?php esc_html_e( 'Follow-up Date', 'smart-lead-crm' ); ?></th><td><input type="date" id="slcrm-lead-follow-up-date" value="<?php echo esc_attr( $lead->follow_up_date ?: '' ); ?>" /></td></tr>
						<tr><th><?php esc_html_e( 'Remarks / Last Msg', 'smart-lead-crm' ); ?></th><td><textarea id="slcrm-lead-remarks" rows="3"><?php echo esc_textarea( $lead->remarks ); ?></textarea></td></tr>
					</table>
					<div style="display:flex;gap:10px;margin-top:16px;">
						<button id="slcrm-save-lead" class="slcrm-btn slcrm-btn-primary" data-lead-id="<?php echo esc_attr( $lead->id ); ?>"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Save Changes', 'smart-lead-crm' ); ?></button>
						<button id="slcrm-delete-lead" class="slcrm-btn slcrm-btn-danger" data-lead-id="<?php echo esc_attr( $lead->id ); ?>"><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete Lead', 'smart-lead-crm' ); ?></button>
					</div>
				</div>

				<!-- Attribution -->
				<div class="slcrm-card">
					<div class="slcrm-section-header"><h3><span class="dashicons dashicons-chart-area"></span> <?php esc_html_e( 'Attribution', 'smart-lead-crm' ); ?></h3></div>
					<table class="slcrm-detail-table">
						<tr><th><?php esc_html_e( 'Source', 'smart-lead-crm' ); ?></th><td><span class="slcrm-source-badge slcrm-source-<?php echo esc_attr( $lead->lead_source ); ?>"><?php echo esc_html( $helper->get_source_label( $lead->lead_source ) ); ?></span><?php if ( $lead->medium ) : ?><span style="color:var(--gray-400);font-size:12px;margin-left:6px;"><?php echo esc_html( $lead->medium ); ?></span><?php endif; ?></td></tr>
						<?php if ( $lead->gclid ) : ?><tr><th>GCLID</th><td><code style="font-size:11px;"><?php echo esc_html( $lead->gclid ); ?></code></td></tr><?php endif; ?>
						<?php if ( $lead->gbraid ) : ?><tr><th>GBRAID</th><td><code style="font-size:11px;"><?php echo esc_html( $lead->gbraid ); ?></code></td></tr><?php endif; ?>
						<?php if ( $lead->wbraid ) : ?><tr><th>WBRAID</th><td><code style="font-size:11px;"><?php echo esc_html( $lead->wbraid ); ?></code></td></tr><?php endif; ?>
						<?php if ( $lead->utm_source ) : ?><tr><th>UTM Source</th><td><?php echo esc_html( $lead->utm_source ); ?></td></tr><?php endif; ?>
						<?php if ( $lead->utm_campaign ) : ?><tr><th>UTM Campaign</th><td><?php echo esc_html( $lead->utm_campaign ); ?></td></tr><?php endif; ?>
						<?php if ( $lead->utm_medium ) : ?><tr><th>UTM Medium</th><td><?php echo esc_html( $lead->utm_medium ); ?></td></tr><?php endif; ?>
						<?php if ( $lead->utm_term ) : ?><tr><th>UTM Term</th><td><?php echo esc_html( $lead->utm_term ); ?></td></tr><?php endif; ?>
						<?php if ( $lead->landing_page ) : ?><tr><th><?php esc_html_e( 'Landing Page', 'smart-lead-crm' ); ?></th><td><a href="<?php echo esc_url( $lead->landing_page ); ?>" target="_blank" style="word-break:break-all;font-size:12px;"><?php echo esc_html( $lead->landing_page ); ?></a></td></tr><?php endif; ?>
						<?php if ( $lead->referer ) : ?><tr><th><?php esc_html_e( 'Referer', 'smart-lead-crm' ); ?></th><td style="font-size:12px;"><?php echo esc_html( $lead->referer ); ?></td></tr><?php endif; ?>
						<tr><th><?php esc_html_e( 'Device', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->device ?: '—' ); ?> / <?php echo esc_html( $lead->browser ?: '—' ); ?></td></tr>
						<tr><th><?php esc_html_e( 'IP', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $lead->ip_address ?: '—' ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Created', 'smart-lead-crm' ); ?></th><td><?php echo esc_html( $helper->format_date( $lead->created_at, 'M j, Y g:i a' ) ); ?></td></tr>
					</table>
				</div>

				<!-- Bookings -->
				<div class="slcrm-card">
					<div class="slcrm-section-header"><h3><span class="dashicons dashicons-calendar-check"></span> <?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></h3></div>
					<?php if ( empty( $bookings ) ) : ?>
						<p style="color:var(--gray-400);font-size:13px;"><?php esc_html_e( 'No bookings yet.', 'smart-lead-crm' ); ?></p>
					<?php else : foreach ( $bookings as $bk ) : ?>
					<div class="slcrm-booking-card">
						<div class="slcrm-booking-header">
							<div><span class="slcrm-booking-route"><?php echo esc_html( $bk->route ); ?></span><span style="font-size:11px;color:var(--gray-400);margin-left:8px;"><?php echo esc_html( $helper->get_booking_type_label( $bk->booking_type ) ); ?></span></div>
							<span class="slcrm-booking-fare"><?php echo esc_html( $helper->format_currency( $bk->fare ) ); ?></span>
						</div>
						<div class="slcrm-booking-meta">
							<?php if ( $bk->booking_date ) : ?><span><span class="dashicons dashicons-calendar-alt" style="font-size:12px;width:12px;height:12px;vertical-align:middle;"></span> <?php echo esc_html( $helper->format_date( $bk->booking_date ) ); ?></span><?php endif; ?>
							<?php if ( $bk->driver ) : ?><span><span class="dashicons dashicons-businessman" style="font-size:12px;width:12px;height:12px;vertical-align:middle;"></span> <?php echo esc_html( $bk->driver ); ?></span><?php endif; ?>
							<select class="slcrm-booking-status" data-booking-id="<?php echo esc_attr( $bk->id ); ?>" style="font-size:12px;padding:2px 6px;border:1px solid var(--gray-200);border-radius:4px;">
								<?php foreach ( array( 'pending'=>'Pending', 'booked'=>'Booked', 'completed'=>'Completed', 'cancelled'=>'Cancelled' ) as $bk_k=>$bk_l ) : ?>
									<option value="<?php echo esc_attr( $bk_k ); ?>" <?php selected( $bk->status, $bk_k ); ?>><?php echo esc_html( $bk_l ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<?php endforeach; endif; ?>
					<div class="slcrm-add-form" style="margin-top:16px;">
						<h4><?php esc_html_e( 'Add Booking', 'smart-lead-crm' ); ?></h4>
						<div class="slcrm-form-row">
							<select id="slcrm-booking-type"><?php foreach ( $helper->get_booking_types() as $bk_k=>$bk_l ) : ?><option value="<?php echo esc_attr( $bk_k ); ?>"><?php echo esc_html( $bk_l ); ?></option><?php endforeach; ?></select>
							<input type="text" id="slcrm-booking-route" placeholder="<?php esc_attr_e( 'Route', 'smart-lead-crm' ); ?>" />
							<input type="number" id="slcrm-booking-fare" placeholder="<?php esc_attr_e( 'Fare ₹', 'smart-lead-crm' ); ?>" step="0.01" />
							<input type="date" id="slcrm-booking-date" />
							<input type="text" id="slcrm-booking-driver" placeholder="<?php esc_attr_e( 'Driver Name', 'smart-lead-crm' ); ?>" />
							<select id="slcrm-booking-status-add"><option value="pending"><?php esc_html_e( 'Pending', 'smart-lead-crm' ); ?></option><option value="booked"><?php esc_html_e( 'Booked', 'smart-lead-crm' ); ?></option><option value="completed"><?php esc_html_e( 'Completed', 'smart-lead-crm' ); ?></option><option value="cancelled"><?php esc_html_e( 'Cancelled', 'smart-lead-crm' ); ?></option></select>
						</div>
						<button id="slcrm-add-booking" class="slcrm-btn slcrm-btn-success slcrm-btn-sm" data-lead-id="<?php echo esc_attr( $lead->id ); ?>"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Booking', 'smart-lead-crm' ); ?></button>
					</div>
				</div>

				<!-- WhatsApp Conversation -->
				<div class="slcrm-card">
					<div class="slcrm-section-header"><h3><span class="dashicons dashicons-whatsapp" style="color:var(--wa-green);"></span> <?php esc_html_e( 'WhatsApp Conversation', 'smart-lead-crm' ); ?></h3></div>
					<?php if ( empty( $conversations ) ) : ?>
						<div class="slcrm-banner slcrm-banner-info"><span class="dashicons dashicons-info"></span><div><?php esc_html_e( 'No WhatsApp messages yet. Messages appear here once the customer contacts you via WhatsApp.', 'smart-lead-crm' ); ?></div></div>
					<?php else :
						$conv = $conversations[0];
						$conv_messages = $conv_obj->get_messages_by_conversation( $conv->id );
					?>
					<div id="slcrm-conv-thread" class="slcrm-conv-thread">
						<?php foreach ( $conv_messages as $msg ) : ?>
							<div class="slcrm-msg slcrm-msg-<?php echo 'inbound'===$msg->direction?'in':'out'; ?>">
								<div class="slcrm-msg-bubble"><?php echo nl2br( esc_html( $msg->text ) ); ?><div class="slcrm-msg-time"><?php echo esc_html( $helper->format_date( $msg->created_at, 'g:i a' ) ); ?></div></div>
							</div>
						<?php endforeach; ?>
					</div>
					<?php if ( $can_send ) : ?>
					<div class="slcrm-conv-reply-form">
						<textarea id="slcrm-conv-reply" rows="1" placeholder="<?php esc_attr_e( 'Type a reply…', 'smart-lead-crm' ); ?>"></textarea>
						<button id="slcrm-send-reply" class="slcrm-btn slcrm-btn-success" data-lead-id="<?php echo esc_attr( $lead->id ); ?>" data-conversation-id="<?php echo esc_attr( $conv->id ); ?>"><?php esc_html_e( 'Send', 'smart-lead-crm' ); ?></button>
					</div>
					<?php else : ?>
					<div class="slcrm-conv-reply-disabled"><span class="dashicons dashicons-info"></span><?php esc_html_e( 'In App Mode the CRM captures messages but does not send replies. Use "Open WhatsApp" above to reply from your phone.', 'smart-lead-crm' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-whatsapp' ) ); ?>" style="font-weight:600;margin-left:4px;"><?php esc_html_e( 'Change mode', 'smart-lead-crm' ); ?></a></div>
					<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

			<!-- Sidebar -->
			<div class="slcrm-lead-sidebar">
				<div class="slcrm-card">
					<div class="slcrm-section-header"><h3><span class="dashicons dashicons-editor-paste-text"></span> <?php esc_html_e( 'Notes', 'smart-lead-crm' ); ?></h3></div>
					<div id="slcrm-notes-list">
						<?php if ( empty( $notes ) ) : ?><p style="color:var(--gray-400);font-size:13px;"><?php esc_html_e( 'No notes yet.', 'smart-lead-crm' ); ?></p><?php else : foreach ( $notes as $note ) : ?>
						<div class="slcrm-note-item"><?php echo esc_html( $note->note ); ?><div class="slcrm-note-meta"><span><?php echo esc_html( $helper->format_date( $note->created_at, 'M j, Y g:i a' ) ); ?></span></div></div>
						<?php endforeach; endif; ?>
					</div>
					<div class="slcrm-add-form" style="margin-top:12px;">
						<textarea id="slcrm-note-text" rows="3" placeholder="<?php esc_attr_e( 'Add an internal note…', 'smart-lead-crm' ); ?>" style="width:100%;padding:8px;border:1.5px solid var(--gray-200);border-radius:6px;font-size:13px;"></textarea>
						<button id="slcrm-add-note" class="slcrm-btn slcrm-btn-outline slcrm-btn-sm" style="margin-top:8px;" data-lead-id="<?php echo esc_attr( $lead->id ); ?>"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Note', 'smart-lead-crm' ); ?></button>
					</div>
				</div>

				<div class="slcrm-card">
					<div class="slcrm-section-header"><h3><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Activity Timeline', 'smart-lead-crm' ); ?></h3></div>
					<?php if ( empty( $timeline ) ) : ?><p style="color:var(--gray-400);font-size:13px;"><?php esc_html_e( 'No activity recorded yet.', 'smart-lead-crm' ); ?></p><?php else : ?>
					<div class="slcrm-timeline">
						<?php foreach ( array_reverse( $timeline ) as $event ) :
							$icons = array( 'visit'=>'external', 'message'=>'format-chat', 'booking'=>'calendar-check', 'note'=>'editor-paste-text' );
						?>
						<div class="slcrm-timeline-item">
							<div class="slcrm-timeline-dot slcrm-timeline-dot-<?php echo esc_attr( $event['type'] ); ?>"><span class="dashicons dashicons-<?php echo esc_attr( $icons[ $event['type'] ] ?? 'marker' ); ?>"></span></div>
							<div class="slcrm-timeline-body">
								<div class="slcrm-timeline-header"><span class="slcrm-timeline-label"><?php echo esc_html( $event['label'] ); ?></span><span class="slcrm-timeline-time"><?php echo esc_html( $helper->format_date( $event['time'], 'M j, g:i a' ) ); ?></span></div>
								<?php if ( ! empty( $event['detail'] ) ) : ?><div class="slcrm-timeline-detail"><?php echo esc_html( wp_trim_words( $event['detail'], 15, '…' ) ); ?></div><?php endif; ?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $tracking ) ) : ?>
				<div class="slcrm-card">
					<div class="slcrm-section-header"><h3><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Site Visits', 'smart-lead-crm' ); ?></h3></div>
					<?php foreach ( array_slice( $tracking, 0, 5 ) as $visit ) : ?>
					<div style="font-size:12px;padding:8px 0;border-bottom:1px solid var(--gray-100);">
						<div style="color:var(--gray-600);"><?php echo esc_html( $helper->format_date( $visit->visit_time, 'M j, g:i a' ) ); ?></div>
						<?php if ( $visit->utm_source ) : ?><div style="color:var(--gray-400);margin-top:2px;"><?php echo esc_html( $visit->utm_source ); ?> / <?php echo esc_html( $visit->utm_campaign ?: '—' ); ?></div><?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php
	return;
}

/* ═══ LIST VIEW ═══ */
$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
$f_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$f_source = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : '';
$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page = 25;

$args = array( 'number'=>$per_page, 'offset'=>($paged-1)*$per_page, 'search'=>$search, 'status'=>$f_status, 'source'=>$f_source, 'orderby'=>'last_updated', 'order'=>'DESC' );
$leads = $db->get_leads( $args );
$total = $db->count_leads( $args );
$total_pages = (int) ceil( $total / $per_page );
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title"><span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'Leads', 'smart-lead-crm' ); ?>
		<span style="font-size:14px;font-weight:400;color:var(--gray-400);margin-left:8px;"><?php echo esc_html( number_format_i18n( $total ) ); ?> total</span>
	</h1>
	<p class="slcrm-subtitle"><?php esc_html_e( 'Every customer who has contacted you. Sorted by most recent activity.', 'smart-lead-crm' ); ?></p>

	<form method="get" action="">
		<input type="hidden" name="page" value="smart-lead-crm-leads" />
		<div class="slcrm-filters">
			<div class="slcrm-filter-group"><label><?php esc_html_e( 'Search', 'smart-lead-crm' ); ?></label><input type="search" name="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Name, phone, route…', 'smart-lead-crm' ); ?>" /></div>
			<div class="slcrm-filter-group"><label><?php esc_html_e( 'Status', 'smart-lead-crm' ); ?></label><select name="status"><option value=""><?php esc_html_e( 'All Statuses', 'smart-lead-crm' ); ?></option><?php foreach ( $statuses as $k=>$l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f_status, $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></div>
			<div class="slcrm-filter-group"><label><?php esc_html_e( 'Source', 'smart-lead-crm' ); ?></label><select name="source"><option value=""><?php esc_html_e( 'All Sources', 'smart-lead-crm' ); ?></option><?php foreach ( $sources as $k=>$l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f_source, $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></div>
			<div class="slcrm-filter-actions">
				<button type="submit" class="slcrm-btn slcrm-btn-primary slcrm-btn-sm"><span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Filter', 'smart-lead-crm' ); ?></button>
				<?php if ( $search || $f_status || $f_source ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads' ) ); ?>" class="slcrm-btn slcrm-btn-outline slcrm-btn-sm"><?php esc_html_e( 'Clear', 'smart-lead-crm' ); ?></a><?php endif; ?>
			</div>
		</div>
	</form>

	<?php if ( empty( $leads ) ) : ?>
		<div class="slcrm-card slcrm-empty-state">
			<span class="dashicons dashicons-email-alt"></span>
			<h3><?php esc_html_e( 'No leads found', 'smart-lead-crm' ); ?></h3>
			<p><?php esc_html_e( 'Leads appear automatically when customers message you or click a WhatsApp/phone link.', 'smart-lead-crm' ); ?></p>
		</div>
	<?php else : ?>
	<table class="slcrm-table">
		<thead><tr>
			<th><?php esc_html_e( 'Lead #', 'smart-lead-crm' ); ?></th>
			<th><?php esc_html_e( 'Customer', 'smart-lead-crm' ); ?></th>
			<th><?php esc_html_e( 'Phone', 'smart-lead-crm' ); ?></th>
			<th><?php esc_html_e( 'Status', 'smart-lead-crm' ); ?></th>
			<th><?php esc_html_e( 'Source', 'smart-lead-crm' ); ?></th>
			<th><?php esc_html_e( 'Last Message', 'smart-lead-crm' ); ?></th>
			<th><?php esc_html_e( 'Last Updated', 'smart-lead-crm' ); ?></th>
			<th></th>
		</tr></thead>
		<tbody>
			<?php foreach ( $leads as $lead ) : $wa_no = preg_replace('/[^0-9]/','',$lead->phone); ?>
			<tr>
				<td><span class="slcrm-lead-id-cell">#<?php echo esc_html( $lead->id ); ?></span></td>
				<td><strong style="color:var(--gray-800);"><?php echo esc_html( $lead->name ?: 'Website Visitor' ); ?></strong></td>
				<td><?php if ( $wa_no ) : ?><a href="https://wa.me/<?php echo esc_attr( $wa_no ); ?>" target="_blank" class="slcrm-wa-link"><span class="dashicons dashicons-whatsapp"></span> <?php echo esc_html( $lead->phone ); ?></a><?php else : ?><span style="color:var(--gray-300);font-style:italic;font-size:12px;"><?php esc_html_e( 'No phone yet', 'smart-lead-crm' ); ?></span><?php endif; ?></td>
				<td><span class="slcrm-status-badge slcrm-status-<?php echo esc_attr( $lead->status ); ?>"><?php echo esc_html( $helper->get_status_label( $lead->status ) ); ?></span></td>
				<td><span class="slcrm-source-badge slcrm-source-<?php echo esc_attr( $lead->lead_source ); ?>"><?php echo esc_html( $helper->get_source_label( $lead->lead_source ) ); ?></span></td>
				<td style="color:var(--gray-400);font-size:12px;font-style:italic;"><?php echo esc_html( $lead->remarks ? wp_trim_words( $lead->remarks, 8, '…' ) : '—' ); ?></td>
				<td style="color:var(--gray-400);font-size:12px;"><?php echo esc_html( $helper->format_date( $lead->last_updated, 'M j, g:i a' ) ); ?></td>
				<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads&action=view&lead_id=' . $lead->id ) ); ?>" class="slcrm-btn slcrm-btn-outline slcrm-btn-sm"><?php esc_html_e( 'View', 'smart-lead-crm' ); ?></a></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php if ( $total_pages > 1 ) : ?>
		<div class="slcrm-pagination"><?php echo paginate_links( array( 'base'=>add_query_arg('paged','%#%'), 'format'=>'', 'current'=>$paged, 'total'=>$total_pages, 'prev_text'=>'&laquo;', 'next_text'=>'&raquo;' ) ); ?></div>
	<?php endif; ?>
	<?php endif; ?>
</div>
