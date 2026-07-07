/**
 * Smart Lead CRM - Admin JavaScript
 *
 * @package SmartLeadCRM
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		// Animate stat cards on load.
		$('.slcrm-stat-card').each(function (index) {
			var $card = $(this);
			$card.css('opacity', 0);
			setTimeout(function () {
				$card.css({
					opacity: 1,
					transition: 'opacity 0.4s ease'
				});
			}, index * 60);
		});

		// Save lead details.
		$('#slcrm-save-lead').on('click', function (e) {
			e.preventDefault();
			var leadId = $(this).data('lead-id');
			var $btn = $(this);
			$btn.prop('disabled', true);

			$.post(slcrmAdmin.ajaxUrl, {
				action: 'slcrm_update_lead',
				nonce: slcrmAdmin.nonce,
				lead_id: leadId,
				name: $('#slcrm-lead-campaign').closest('.slcrm-card').find('h2').text(),
				status: $('#slcrm-lead-status').val(),
				lead_source: $('#slcrm-lead-source').val(),
				campaign: $('#slcrm-lead-campaign').val(),
				booking_route: $('#slcrm-lead-route').val(),
				booking_date: $('#slcrm-lead-booking-date').val(),
				follow_up_date: $('#slcrm-lead-follow-up-date').val(),
				remarks: $('#slcrm-lead-remarks').val()
			}, function (response) {
				$btn.prop('disabled', false);
				if (response.success) {
					showNotice($btn, response.data.message, 'success');
				} else {
					showNotice($btn, response.data.message, 'error');
				}
			});
		});

		// Delete lead.
		$('#slcrm-delete-lead').on('click', function (e) {
			e.preventDefault();
			if (!confirm(slcrmAdmin.confirmDelete || 'Delete this lead and all related data?')) {
				return;
			}
			var leadId = $(this).data('lead-id');
			$.post(slcrmAdmin.ajaxUrl, {
				action: 'slcrm_delete_lead',
				nonce: slcrmAdmin.nonce,
				lead_id: leadId
			}, function (response) {
				if (response.success) {
					window.location.href = slcrmAdmin.leadsUrl || (slcrmAdmin.ajaxUrl.replace('admin-ajax.php', 'admin.php') + '?page=smart-lead-crm-leads');
				} else {
					alert(response.data.message);
				}
			});
		});

		// Add note.
		$('#slcrm-add-note').on('click', function (e) {
			e.preventDefault();
			var leadId = $(this).data('lead-id');
			var note = $('#slcrm-note-text').val();
			if (!note) return;

			$.post(slcrmAdmin.ajaxUrl, {
				action: 'slcrm_add_note',
				nonce: slcrmAdmin.nonce,
				lead_id: leadId,
				note: note
			}, function (response) {
				if (response.success) {
					$('#slcrm-notes-list').prepend(
						'<div class="slcrm-note">' +
						'<div class="slcrm-note-text">' + escapeHtml(note) + '</div>' +
						'<div class="slcrm-note-meta">' + response.data.created_at + ' — ' + escapeHtml(response.data.author) + '</div>' +
						'</div>'
					);
					$('#slcrm-note-text').val('');
				} else {
					alert(response.data.message);
				}
			});
		});

		// Add booking.
		$('#slcrm-add-booking').on('click', function (e) {
			e.preventDefault();
			var leadId = $(this).data('lead-id');
			$.post(slcrmAdmin.ajaxUrl, {
				action: 'slcrm_add_booking',
				nonce: slcrmAdmin.nonce,
				lead_id: leadId,
				booking_type: $('#slcrm-booking-type').val(),
				route: $('#slcrm-booking-route').val(),
				fare: $('#slcrm-booking-fare').val(),
				booking_date: $('#slcrm-booking-date').val(),
				driver: $('#slcrm-booking-driver').val(),
				booking_status: $('#slcrm-booking-status').val()
			}, function (response) {
				if (response.success) {
					alert(response.data.message);
					window.location.reload();
				} else {
					alert(response.data.message);
				}
			});
		});

		// Update booking status.
		$(document).on('change', '.slcrm-booking-status', function () {
			var bookingId = $(this).data('booking-id');
			var leadId = $(this).data('lead-id');
			var status = $(this).val();
			$.post(slcrmAdmin.ajaxUrl, {
				action: 'slcrm_update_booking',
				nonce: slcrmAdmin.nonce,
				booking_id: bookingId,
				lead_id: leadId,
				status: status
			}, function (response) {
				if (!response.success) {
					alert(response.data.message);
				}
			});
		});

		// Update lead status from dropdown (quick change).
		$(document).on('change', '#slcrm-lead-status', function () {
			var leadId = $(this).data('lead-id');
			$.post(slcrmAdmin.ajaxUrl, {
				action: 'slcrm_update_lead',
				nonce: slcrmAdmin.nonce,
				lead_id: leadId,
				status: $(this).val()
			}, function (response) {
				if (!response.success) {
					alert(response.data.message);
				}
			});
		});

		// Frontend lead form submission.
		$(document).on('submit', '#slcrm-lead-form', function (e) {
			e.preventDefault();
			var $form = $(this);
			var $btn = $form.find('.slcrm-submit-btn');
			var $msg = $('#slcrm-form-message');
			$btn.prop('disabled', true).text('Submitting...');

			$.post(slcrmTracker.ajaxUrl, {
				action: 'slcrm_submit_lead',
				nonce: slcrmTracker.nonce,
				phone: $form.find('#slcrm-phone').val(),
				name: $form.find('#slcrm-name').val(),
				email: $form.find('#slcrm-email').val()
			}, function (response) {
				$btn.prop('disabled', false).text($btn.data('original-text') || 'Submit');
				if (response.success) {
					$msg.removeClass('error').addClass('success').text(response.data.message).show();
					$form[0].reset();
				} else {
					$msg.removeClass('success').addClass('error').text(response.data.message).show();
				}
			}).fail(function () {
				$btn.prop('disabled', false);
				$msg.removeClass('success').addClass('error').text('An error occurred. Please try again.').show();
			});
		});

		// Send conversation reply (channel-agnostic).
		$('#slcrm-send-reply').on('click', function (e) {
			e.preventDefault();
			var leadId = $(this).data('lead-id');
			var convId = $(this).data('conversation-id');
			var body = $('#slcrm-conv-reply').val();
			if (!body) return;
			var $btn = $(this);
			$btn.prop('disabled', true);

			$.post(slcrmAdmin.ajaxUrl, {
				action: 'slcrm_send_reply',
				nonce: slcrmAdmin.nonce,
				lead_id: leadId,
				conversation_id: convId,
				body: body
			}, function (response) {
				$btn.prop('disabled', false);
				if (response.success) {
					$('#slcrm-conv-thread').append(
						'<div class="slcrm-conv-msg slcrm-conv-outbound">' +
						'<div class="slcrm-conv-msg-type">Text</div>' +
						'<div class="slcrm-conv-msg-body">' + escapeHtml(response.data.body) + '</div>' +
						'<div class="slcrm-conv-msg-meta">' + response.data.timestamp + '</div>' +
						'</div>'
					);
					$('#slcrm-conv-reply').val('');
					$('#slcrm-conv-thread').scrollTop($('#slcrm-conv-thread')[0].scrollHeight);
				} else {
					alert(response.data.message);
				}
			});
		});

		// Assign conversation to a WP user.
		$('#slcrm-conv-assign').on('change', function () {
			var convId = $(this).data('conversation-id');
			var userId = $(this).val();
			$.post(slcrmAdmin.ajaxUrl, {
				action: 'slcrm_assign_conversation',
				nonce: slcrmAdmin.nonce,
				conversation_id: convId,
				user_id: userId
			}, function (response) {
				if (response.success) {
					// Optionally show a brief confirmation.
				}
			});
		});

		// Store original button text.
		$('.slcrm-submit-btn').each(function () {
			$(this).data('original-text', $(this).text());
		});

		function showNotice($el, message, type) {
			var $notice = $('<div class="slcrm-form-message ' + type + '">' + escapeHtml(message) + '</div>');
			$el.after($notice);
			setTimeout(function () { $notice.fadeOut(300, function () { $(this).remove(); }); }, 3000);
		}

		function escapeHtml(str) {
			if (!str) return '';
			return String(str)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		}
	});

})(jQuery);
