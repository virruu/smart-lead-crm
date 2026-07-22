(function () {
    'use strict';

    function setCookie(name, value, days) {
        var d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + d.toUTCString() + ';path=/';
    }

    function getCookie(name) {
        var n = name + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i].trim();
            if (c.indexOf(n) === 0) return decodeURIComponent(c.substring(n.length));
        }
        return '';
    }

    function generateUUID() {
        if (window.crypto && window.crypto.randomUUID) return window.crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function ensureVisitorId() {
        var id = getCookie('slcrm_visitor_id');
        if (!id || !/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(id)) {
            id = generateUUID();
            setCookie('slcrm_visitor_id', id, 365);
        }
        return id;
    }

    function captureUrlParams() {
        var params = new URLSearchParams(window.location.search);
        var captureKeys = ['gclid', 'gbraid', 'wbraid', 'utm_source', 'utm_campaign', 'utm_medium', 'utm_term', 'utm_content'];
        for (var i = 0; i < captureKeys.length; i++) {
            var val = params.get(captureKeys[i]);
            if (val) setCookie('slcrm_' + captureKeys[i], val, 90);
        }
        if (!getCookie('slcrm_landing_page')) setCookie('slcrm_landing_page', window.location.href, 90);
        if (!getCookie('slcrm_referer')) {
            var ref = document.referrer;
            setCookie('slcrm_referer', ref, 90);
            var kw = extractOrganicKeyword(ref);
            if (kw) setCookie('slcrm_organic_keyword', kw, 90);
        }
        if (!getCookie('slcrm_device')) {
            var ua = navigator.userAgent;
            var device = /tablet|ipad|playbook|silk/i.test(ua) ? 'Tablet' : /mobile|android|iphone|ipod|blackberry|opera mini/i.test(ua) ? 'Mobile' : 'Desktop';
            setCookie('slcrm_device', device, 90);
        }
        if (!getCookie('slcrm_browser')) {
            var ua2 = navigator.userAgent;
            var browser = ua2.indexOf('Edg') > -1 ? 'Edge' : ua2.indexOf('Chrome') > -1 ? 'Chrome' : ua2.indexOf('Firefox') > -1 ? 'Firefox' : ua2.indexOf('Safari') > -1 ? 'Safari' : ua2.indexOf('MSIE') > -1 || ua2.indexOf('Trident') > -1 ? 'IE' : 'Unknown';
            setCookie('slcrm_browser', browser, 90);
        }
    }

    function extractOrganicKeyword(referrer) {
        if (!referrer) return '';
        try {
            var url = new URL(referrer);
            var params = new URLSearchParams(url.search);
            var keys = ['q', 'query', 'p', 'wd', 'text', 'search'];
            for (var i = 0; i < keys.length; i++) {
                var val = params.get(keys[i]);
                if (val) return val;
            }
        } catch (e) {}
        return '';
    }

    function isWhatsAppLink(href) {
        return href && (href.indexOf('wa.me') > -1 || href.indexOf('api.whatsapp.com') > -1 || href.indexOf('whatsapp://') > -1);
    }

    function isTelLink(href) {
        return href && href.indexOf('tel:') === 0;
    }

    function isMailtoLink(href) {
        return href && href.indexOf('mailto:') === 0;
    }

    function isSmsLink(href) {
        return href && (href.indexOf('sms:') === 0 || href.indexOf('smsto:') === 0);
    }

    function isDirectionsLink(href) {
        if (!href) return false;
        return href.indexOf('maps.google.com') > -1 || href.indexOf('maps.apple.com') > -1 || href.indexOf('openstreetmap.org') > -1;
    }

    function getBusinessNumber() {
        var stored = document.querySelector('meta[name="slcrm-wa-number"]');
        if (stored) return stored.getAttribute('content').replace(/[^0-9]/g, '');
        if (typeof slcrmTracker !== 'undefined' && slcrmTracker.businessNumber) return slcrmTracker.businessNumber;
        return '';
    }

    var businessNumber = '';

    function extractPhoneFromWhatsApp(href) {
        var match = href.match(/(?:wa\.me\/|api\.whatsapp\.com\/send\?phone=|whatsapp:\/\/send\?phone=)([0-9]+)/);
        if (!match) return '';
        var phone = match[1];
        if (businessNumber && phone === businessNumber) return '';
        if (phone.length < 8) return '';
        return phone;
    }

    function extractPhoneFromTel(href) {
        var phone = href.replace(/[^0-9+]/g, '').replace(/^\+/, '');
        if (businessNumber && phone === businessNumber) return '';
        if (phone.length < 8) return '';
        return phone;
    }

    function extractEmailFromMailto(href) {
        var match = href.match(/mailto:([^?]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function extractPhoneFromSms(href) {
        var phone = href.replace(/[^0-9+]/g, '').replace(/^\+/, '');
        if (businessNumber && phone === businessNumber) return '';
        if (phone.length < 8) return '';
        return phone;
    }

    var leadFired = false;

    function getConversionConfig(action) {
        if (typeof slcrmTracker === 'undefined' || !slcrmTracker.conversions) return null;
        for (var i = 0; i < slcrmTracker.conversions.length; i++) {
            if (slcrmTracker.conversions[i].crm_action === action) return slcrmTracker.conversions[i];
        }
        return null;
    }

    function fireConversion(action) {
        var conv = getConversionConfig(action);
        if (!conv) return;
        if (typeof gtag === 'undefined') return;

        if (slcrmTracker.adsId && conv.ads_label) {
            gtag('event', 'conversion', { send_to: slcrmTracker.adsId + '/' + conv.ads_label });
        }
        if (slcrmTracker.ga4Id && conv.ga4_event) {
            gtag('event', conv.ga4_event, { send_to: slcrmTracker.ga4Id });
        }
    }

    function extractFormData(form) {
        var data = {};
        if (!form) return data;
        var fields = form.querySelectorAll('input, textarea, select');
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            var name = (field.name || field.id || '').toLowerCase();
            var type = (field.type || '').toLowerCase();
            var value = field.value || '';
            if (!name || type === 'hidden' || type === 'submit' || type === 'button') continue;
            if (name.indexOf('name') > -1 || name.indexOf('full') > -1 || name.indexOf('customer') > -1) {
                if (!data.name) data.name = value;
            }
            if (name.indexOf('email') > -1 || name.indexOf('mail') > -1) {
                if (!data.email) data.email = value;
            }
            if (name.indexOf('phone') > -1 || name.indexOf('mobile') > -1 || name.indexOf('tel') > -1 || name.indexOf('contact') > -1) {
                if (!data.phone) data.phone = value;
            }
        }
        return data;
    }

    function createLead(action, phone, extraData) {
        if (leadFired) return;
        leadFired = true;

        var data = new URLSearchParams();
        data.append('action', 'slcrm_auto_lead');
        data.append('nonce', (typeof slcrmTracker !== 'undefined') ? slcrmTracker.nonce : '');
        data.append('visitor_id', ensureVisitorId());
        data.append('lead_action', action);
        if (phone) data.append('phone', phone);
        if (extraData) {
            if (extraData.name) data.append('name', extraData.name);
            if (extraData.email) data.append('email', extraData.email);
            if (extraData.form_name) data.append('form_name', extraData.form_name);
            if (extraData.phone) data.append('phone', extraData.phone);
        }

        var cookieKeys = ['gclid', 'gbraid', 'wbraid', 'utm_source', 'utm_campaign', 'utm_medium', 'utm_term', 'utm_content', 'landing_page', 'referer', 'device', 'browser', 'organic_keyword'];
        for (var i = 0; i < cookieKeys.length; i++) {
            var val = getCookie('slcrm_' + cookieKeys[i]);
            if (val) data.append(cookieKeys[i], val);
        }

        var url = (typeof slcrmTracker !== 'undefined') ? slcrmTracker.ajaxUrl : '/wp-admin/admin-ajax.php';
        var payload = data.toString();

        if (navigator.sendBeacon) {
            var blob = new Blob([payload], { type: 'application/x-www-form-urlencoded' });
            navigator.sendBeacon(url, blob);
        } else {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, false);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(payload);
        }

        fireConversion(action);
    }

    function attachLinkListeners() {
        businessNumber = getBusinessNumber();
        var links = document.querySelectorAll('a[href]');
        for (var i = 0; i < links.length; i++) {
            (function (link) {
                if (link.dataset.slcrmBound) return;
                link.dataset.slcrmBound = '1';
                link.addEventListener('click', function () {
                    var href = link.getAttribute('href');
                    if (isWhatsAppLink(href)) {
                        createLead('whatsapp', extractPhoneFromWhatsApp(href));
                    } else if (isTelLink(href)) {
                        createLead('phone', extractPhoneFromTel(href));
                    } else if (isMailtoLink(href)) {
                        createLead('email', '', { email: extractEmailFromMailto(href) });
                    } else if (isSmsLink(href)) {
                        createLead('sms', extractPhoneFromSms(href));
                    } else if (isDirectionsLink(href)) {
                        createLead('directions');
                    }
                }, { once: true });
            })(links[i]);
        }
    }

    function attachFormListeners() {
        if (typeof slcrmTracker === 'undefined' || !slcrmTracker.forms) return;
        var forms = slcrmTracker.forms;
        for (var i = 0; i < forms.length; i++) {
            (function (config) {
                var el = document.querySelector(config.selector);
                if (!el || el.dataset.slcrmFormBound) return;
                el.dataset.slcrmFormBound = '1';

                var eventType = config.event_type || 'submit';
                el.addEventListener(eventType, function (e) {
                    var formData = extractFormData(eventType === 'submit' ? el : null);
                    formData.form_name = config.form_name;
                    createLead(config.crm_action, formData.phone || '', formData);
                }, { once: true });
            })(forms[i]);
        }
    }

    function init() {
        captureUrlParams();
        ensureVisitorId();
        attachLinkListeners();
        attachFormListeners();

        if (window.MutationObserver) {
            var observer = new MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    if (mutations[i].addedNodes.length) {
                        attachLinkListeners();
                        attachFormListeners();
                        break;
                    }
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
