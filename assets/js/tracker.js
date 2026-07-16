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
        // First-visit cookies
        if (!getCookie('slcrm_landing_page')) setCookie('slcrm_landing_page', window.location.href, 90);
        if (!getCookie('slcrm_referer')) setCookie('slcrm_referer', document.referrer, 90);

        // Device + browser
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

    function isWhatsAppLink(href) {
        return href && (href.indexOf('wa.me') > -1 || href.indexOf('api.whatsapp.com') > -1 || href.indexOf('whatsapp://') > -1);
    }

    function isTelLink(href) {
        return href && href.indexOf('tel:') === 0;
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

    var leadFired = false;

    function createLead(action, phone) {
        if (leadFired) return;
        leadFired = true;

        var data = new URLSearchParams();
        data.append('action', 'slcrm_auto_lead');
        data.append('nonce', (typeof slcrmTracker !== 'undefined') ? slcrmTracker.nonce : '');
        data.append('visitor_id', ensureVisitorId());
        data.append('lead_action', action);
        if (phone) data.append('phone', phone);

        // Collect all cookies
        var cookieKeys = ['gclid', 'gbraid', 'wbraid', 'utm_source', 'utm_campaign', 'utm_medium', 'utm_term', 'utm_content', 'landing_page', 'referer', 'device', 'browser'];
        for (var i = 0; i < cookieKeys.length; i++) {
            var val = getCookie('slcrm_' + cookieKeys[i]);
            if (val) data.append(cookieKeys[i], val);
        }

        var url = (typeof slcrmTracker !== 'undefined') ? slcrmTracker.ajaxUrl : '/wp-admin/admin-ajax.php';
        var payload = data.toString();

        // Use sendBeacon for unload-safe delivery, fall back to sync XHR
        if (navigator.sendBeacon) {
            var blob = new Blob([payload], { type: 'application/x-www-form-urlencoded' });
            navigator.sendBeacon(url, blob);
        } else {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, false);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(payload);
        }
    }

    function attachLinkListeners() {
        businessNumber = getBusinessNumber();
        var links = document.querySelectorAll('a[href]');
        for (var i = 0; i < links.length; i++) {
            (function (link) {
                link.addEventListener('click', function () {
                    var href = link.getAttribute('href');
                    if (isWhatsAppLink(href)) {
                        createLead('whatsapp', extractPhoneFromWhatsApp(href));
                    } else if (isTelLink(href)) {
                        createLead('phone', extractPhoneFromTel(href));
                    }
                }, { once: true });
            })(links[i]);
        }
    }

    function init() {
        captureUrlParams();
        ensureVisitorId();
        attachLinkListeners();

        // Watch for dynamically added links
        if (window.MutationObserver) {
            var observer = new MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    if (mutations[i].addedNodes.length) {
                        attachLinkListeners();
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
