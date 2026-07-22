(function ($) {
    'use strict';

    var nonce   = (typeof slcrmAdmin !== 'undefined') ? slcrmAdmin.nonce : '';
    var ajaxUrl = (typeof slcrmAdmin !== 'undefined') ? slcrmAdmin.ajaxUrl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');

    function showNotice(el, type, message) {
        el.removeClass('slcrm-notice-success slcrm-notice-error slcrm-notice-loading')
          .addClass('slcrm-notice-' + type)
          .text(message).stop(true).fadeIn(200);
        if (type !== 'loading') setTimeout(function () { el.fadeOut(400); }, 3500);
    }

    function escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // Stat card entrance animation
    $('.slcrm-stat-card').each(function (i) {
        var $el = $(this);
        $el.css({ opacity: 0, transform: 'translateY(12px)' });
        setTimeout(function () {
            $el.css({ transition: 'opacity .35s, transform .35s', opacity: 1, transform: 'translateY(0)' });
        }, 50 + i * 60);
    });

    // Settings tabs
    $(document).on('click', '.slcrm-settings-tab', function (e) {
        e.preventDefault();
        var target = $(this).data('tab');
        $('.slcrm-settings-tab').removeClass('active');
        $(this).addClass('active');
        $('.slcrm-tab-panel').removeClass('active');
        $('#slcrm-tab-' + target).addClass('active');
    });
    if ($('.slcrm-settings-tab').length) $('.slcrm-settings-tab').first().trigger('click');

    // Mode card click (settings radio)
    $(document).on('click', '.slcrm-mode-card', function () {
        var $card = $(this);
        $card.find('input[type="radio"]').prop('checked', true);
        $card.closest('.slcrm-mode-cards').find('.slcrm-mode-card').removeClass('slcrm-mode-card--active');
        $card.addClass('slcrm-mode-card--active');
    });

    // Save lead
    var $saveNotice = $('#slcrm-save-notice');
    $(document).on('click', '#slcrm-save-lead', function (e) {
        e.preventDefault();
        var leadId = $(this).data('lead-id');
        showNotice($saveNotice, 'loading', 'Saving…');
        $.post(ajaxUrl, {
            action: 'slcrm_update_lead', nonce: nonce, lead_id: leadId,
            status: $('#slcrm-lead-status').val(),
            lead_source: $('#slcrm-lead-source').val(),
            campaign: $('#slcrm-lead-campaign').val(),
            ad_group: $('#slcrm-lead-ad-group').val(),
            keyword: $('#slcrm-lead-keyword').val(),
            booking_route: $('#slcrm-lead-route').val(),
            booking_date: $('#slcrm-lead-booking-date').val(),
            follow_up_date: $('#slcrm-lead-follow-up-date').val(),
            remarks: $('#slcrm-lead-remarks').val()
        }).done(function (res) {
            if (res.success) {
                showNotice($saveNotice, 'success', 'Saved successfully.');
            } else {
                showNotice($saveNotice, 'error', res.data || 'Save failed.');
            }
        }).fail(function () { showNotice($saveNotice, 'error', 'Network error.'); });
    });

    // Quick status change
    $(document).on('change', '#slcrm-lead-status', function () {
        var leadId = $(this).data('lead-id');
        if (!leadId) return;
        $.post(ajaxUrl, { action: 'slcrm_update_lead', nonce: nonce, lead_id: leadId, status: $(this).val() });
    });

    // Delete lead
    $(document).on('click', '#slcrm-delete-lead', function (e) {
        e.preventDefault();
        var msg = (typeof slcrmAdmin !== 'undefined' && slcrmAdmin.confirmDelete) ? slcrmAdmin.confirmDelete : 'Delete this lead?';
        if (!window.confirm(msg)) return;
        var leadId = $(this).data('lead-id');
        $.post(ajaxUrl, { action: 'slcrm_delete_lead', nonce: nonce, lead_id: leadId }).done(function (res) {
            if (res.success && typeof slcrmAdmin !== 'undefined') window.location.href = slcrmAdmin.leadsUrl;
        });
    });

    // Add note
    $(document).on('click', '#slcrm-add-note', function (e) {
        e.preventDefault();
        var $btn = $(this), text = $('#slcrm-note-text').val().trim(), leadId = $btn.data('lead-id');
        if (!text) return;
        $btn.prop('disabled', true);
        $.post(ajaxUrl, { action: 'slcrm_add_note', nonce: nonce, lead_id: leadId, note: text }).done(function (res) {
            if (res.success) {
                var now = new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
                $('#slcrm-notes-list').prepend('<div class="slcrm-note-item">' + escHtml(text) + '<div class="slcrm-note-meta"><span>' + now + '</span></div></div>');
                $('#slcrm-note-text').val('');
            }
        }).always(function () { $btn.prop('disabled', false); });
    });

    // Add booking
    $(document).on('click', '#slcrm-add-booking', function (e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post(ajaxUrl, {
            action: 'slcrm_add_booking', nonce: nonce, lead_id: $btn.data('lead-id'),
            booking_type: $('#slcrm-booking-type').val(), route: $('#slcrm-booking-route').val(),
            fare: $('#slcrm-booking-fare').val(), booking_date: $('#slcrm-booking-date').val(),
            driver: $('#slcrm-booking-driver').val(), status: $('#slcrm-booking-status-add').val()
        }).done(function (res) { if (res.success) window.location.reload(); }).always(function () { $btn.prop('disabled', false); });
    });

    // Update booking status
    $(document).on('change', '.slcrm-booking-status', function () {
        var $sel = $(this);
        $.post(ajaxUrl, { action: 'slcrm_update_booking', nonce: nonce, booking_id: $sel.data('booking-id'), status: $sel.val() }).done(function (res) {
            if (res.success) {
                var flash = $sel.closest('.slcrm-booking-card');
                flash.css('background', '#f0fdf4');
                setTimeout(function () { flash.css('background', ''); }, 1000);
            }
        });
    });

    // Send reply
    $(document).on('click', '#slcrm-send-reply', function (e) {
        e.preventDefault();
        var $btn = $(this), text = $('#slcrm-conv-reply').val().trim();
        if (!text) return;
        $btn.prop('disabled', true).text('Sending…');
        $.post(ajaxUrl, {
            action: 'slcrm_send_reply', nonce: nonce,
            lead_id: $btn.data('lead-id'), conversation_id: $btn.data('conversation-id'), message: text
        }).done(function (res) {
            if (res.success) {
                var timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                var $thread = $('#slcrm-conv-thread');
                $thread.append('<div class="slcrm-msg slcrm-msg-out"><div class="slcrm-msg-bubble">' + escHtml(text) + '<div class="slcrm-msg-time">' + timeStr + '</div></div></div>');
                $thread.scrollTop($thread[0].scrollHeight);
                $('#slcrm-conv-reply').val('');
            }
        }).always(function () { $btn.prop('disabled', false).text('Send'); });
    });

    // Copy webhook URL
    $(document).on('click', '.slcrm-copy-btn', function (e) {
        e.preventDefault();
        var text = $(this).data('copy'), $btn = $(this);
        navigator.clipboard.writeText(text).then(function () {
            var orig = $btn.html();
            $btn.html('<span class="dashicons dashicons-yes-alt"></span> Copied!').addClass('copied');
            setTimeout(function () { $btn.html(orig).removeClass('copied'); }, 2000);
        });
    });

    // Scroll conv thread to bottom on load
    var $thread = $('#slcrm-conv-thread');
    if ($thread.length) $thread.scrollTop($thread[0].scrollHeight);

    // Conv reply auto-resize
    $(document).on('input', '#slcrm-conv-reply', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });

    // Filter form enter key
    $(document).on('keypress', '.slcrm-filters input', function (e) {
        if (e.which === 13) $(this).closest('form').submit();
    });

    /* ── Conversion management ────────────────────────────── */

    function saveConversion($row) {
        var id = $row.data('id') || 0;
        $.post(ajaxUrl, {
            action: 'slcrm_save_conversion', nonce: nonce, id: id,
            crm_action: $row.find('.slcrm-conv-action').val(),
            label: $row.find('.slcrm-conv-label').val(),
            google_ads_label: $row.find('.slcrm-conv-ads').val(),
            ga4_event: $row.find('.slcrm-conv-ga4').val(),
            enabled: $row.find('.slcrm-conv-enabled').is(':checked') ? 1 : 0,
            category: $row.find('.slcrm-conv-category').val()
        }).done(function (res) {
            if (res.success) {
                $row.data('id', res.data.id);
                var $btn = $row.find('.slcrm-conv-save');
                var orig = $btn.text();
                $btn.text('Saved!').addClass('slcrm-btn-success');
                setTimeout(function () { $btn.text(orig).removeClass('slcrm-btn-success'); }, 1500);
            }
        });
    }

    $(document).on('click', '.slcrm-conv-save', function (e) {
        e.preventDefault();
        saveConversion($(this).closest('tr'));
    });

    $(document).on('click', '#slcrm-add-conversion', function (e) {
        e.preventDefault();
        var $body = $('#slcrm-conversions-body');
        var $row = $(
            '<tr data-id="0">' +
            '<td><input type="checkbox" class="slcrm-conv-enabled" checked /></td>' +
            '<td><input type="text" class="slcrm-conv-label" placeholder="e.g. Quote Request" /></td>' +
            '<td><input type="text" class="slcrm-conv-action" placeholder="e.g. quote" /></td>' +
            '<td><input type="text" class="slcrm-conv-ads" placeholder="e.g. AbcD1234" /></td>' +
            '<td><input type="text" class="slcrm-conv-ga4" placeholder="e.g. quote_request" /></td>' +
            '<td><select class="slcrm-conv-category"><option value="interaction">Interaction</option><option value="form">Form</option><option value="custom">Custom</option></select></td>' +
            '<td><button class="slcrm-btn slcrm-btn-outline slcrm-btn-sm slcrm-conv-save">Save</button></td>' +
            '</tr>'
        );
        $body.append($row);
        $row.find('input:first').focus();
    });

    /* ── Form tracking management ──────────────────────────── */

    function saveFormTracking($row) {
        var id = $row.data('id') || 0;
        $.post(ajaxUrl, {
            action: 'slcrm_save_form_tracking', nonce: nonce, id: id,
            form_name: $row.find('.slcrm-form-name').val(),
            selector: $row.find('.slcrm-form-selector').val(),
            event_type: $row.find('.slcrm-form-event').val(),
            crm_action: $row.find('.slcrm-form-action').val(),
            enabled: $row.find('.slcrm-form-enabled').is(':checked') ? 1 : 0
        }).done(function (res) {
            if (res.success) {
                $row.data('id', res.data.id);
                var $btn = $row.find('.slcrm-form-save');
                var orig = $btn.text();
                $btn.text('Saved!').addClass('slcrm-btn-success');
                setTimeout(function () { $btn.text(orig).removeClass('slcrm-btn-success'); }, 1500);
            }
        });
    }

    $(document).on('click', '.slcrm-form-save', function (e) {
        e.preventDefault();
        saveFormTracking($(this).closest('tr'));
    });

    $(document).on('click', '#slcrm-add-form', function (e) {
        e.preventDefault();
        var $body = $('#slcrm-forms-body');
        var $row = $(
            '<tr data-id="0">' +
            '<td><input type="checkbox" class="slcrm-form-enabled" checked /></td>' +
            '<td><input type="text" class="slcrm-form-name" placeholder="e.g. Tour Enquiry" /></td>' +
            '<td><input type="text" class="slcrm-form-selector" placeholder="#tour-form" /></td>' +
            '<td><select class="slcrm-form-event"><option value="submit">submit</option><option value="click">click</option><option value="change">change</option></select></td>' +
            '<td><input type="text" class="slcrm-form-action" placeholder="e.g. tour" /></td>' +
            '<td><button class="slcrm-btn slcrm-btn-outline slcrm-btn-sm slcrm-form-save">Save</button></td>' +
            '</tr>'
        );
        $body.append($row);
        $row.find('input:first').focus();
    });

}(jQuery));
