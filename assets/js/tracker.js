/**
 * Smart Lead CRM - Frontend Tracker
 *
 * 1. Generates a persistent visitor_id (UUID v4, 365-day cookie)
 * 2. Captures GCLID, GBRAID, WBRAID, UTM parameters into cookies
 * 3. Detects clicks on tel: links, wa.me, and api.whatsapp.com links
 * 4. Auto-creates a lead via AJAX when a visitor clicks WhatsApp or call
 *
 * Flow: Visitor → Google Ads → Landing Page → Clicks WhatsApp → Lead auto-created
 *
 * @package SmartLeadCRM
 */

(function () {
	'use strict';

	// --- Cookie helpers ---

	function setCookie(name, value, days) {
		var expires = '';
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			expires = '; expires=' + date.toUTCString();
		}
		document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/';
	}

	function getCookie(name) {
		var nameEQ = name + '=';
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) === ' ') c = c.substring(1, c.length);
			if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
		}
		return null;
	}

	// --- UUID v4 generator (RFC 4122 compliant) ---

	function generateUUID() {
		if (window.crypto && window.crypto.randomUUID) {
			return window.crypto.randomUUID();
		}
		// Fallback for older browsers.
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
			var r = Math.random() * 16 | 0;
			var v = c === 'x' ? r : (r & 0x3 | 0x8);
			return v.toString(16);
		});
	}

	// --- Visitor ID: persistent for 365 days ---

	var VISITOR_COOKIE = 'slcrm_visitor_id';
	var VISITOR_DAYS = 365;

	function ensureVisitorId() {
		var visitorId = getCookie(VISITOR_COOKIE);
		if (!visitorId) {
			visitorId = generateUUID();
			setCookie(VISITOR_COOKIE, visitorId, VISITOR_DAYS);
		}
		return visitorId;
	}

	var visitorId = ensureVisitorId();

	// --- URL parameter capture ---

	var urlParams = [
		'gclid', 'gbraid', 'wbraid',
		'utm_source', 'utm_campaign', 'utm_medium', 'utm_term', 'utm_content'
	];

	var cookieNames = {
		'gclid': 'slcrm_gclid',
		'gbraid': 'slcrm_gbraid',
		'wbraid': 'slcrm_wbraid',
		'utm_source': 'slcrm_utm_source',
		'utm_campaign': 'slcrm_utm_campaign',
		'utm_medium': 'slcrm_utm_medium',
		'utm_term': 'slcrm_utm_term',
		'utm_content': 'slcrm_utm_content'
	};

	var TRACKING_DAYS = 90;

	function getQueryParam(name) {
		var params = new URLSearchParams(window.location.search);
		return params.get(name);
	}

	urlParams.forEach(function (param) {
		var value = getQueryParam(param);
		if (value) {
			setCookie(cookieNames[param], value, TRACKING_DAYS);
		}
	});

	// Store landing page on first visit.
	if (!getCookie('slcrm_landing_page')) {
		setCookie('slcrm_landing_page', window.location.href, TRACKING_DAYS);
	}

	// Store referer on first visit.
	if (!getCookie('slcrm_referer') && document.referrer) {
		setCookie('slcrm_referer', document.referrer, TRACKING_DAYS);
	}

	// Detect and store device.
	if (!getCookie('slcrm_device')) {
		setCookie('slcrm_device', detectDevice(), TRACKING_DAYS);
	}

	// Detect and store browser.
	if (!getCookie('slcrm_browser')) {
		setCookie('slcrm_browser', detectBrowser(), TRACKING_DAYS);
	}

	function detectDevice() {
		var ua = navigator.userAgent || '';
		if (/iPad|Tablet|Android(?!.*Mobile)/i.test(ua)) return 'Tablet';
		if (/Mobile|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua)) return 'Mobile';
		return 'Desktop';
	}

	function detectBrowser() {
		var ua = navigator.userAgent || '';
		if (ua.indexOf('Edg') > -1) return 'Microsoft Edge';
		if (ua.indexOf('Chrome') > -1) return 'Google Chrome';
		if (ua.indexOf('Firefox') > -1) return 'Mozilla Firefox';
		if (ua.indexOf('Safari') > -1) return 'Safari';
		if (ua.indexOf('MSIE') > -1 || ua.indexOf('Trident') > -1) return 'Internet Explorer';
		if (ua.indexOf('Opera') > -1 || ua.indexOf('OPR') > -1) return 'Opera';
		return 'Unknown';
	}

	// --- Collect all tracking data from cookies ---

	function collectTrackingData() {
		return {
			visitor_id: visitorId,
			gclid: getCookie('slcrm_gclid') || '',
			gbraid: getCookie('slcrm_gbraid') || '',
			wbraid: getCookie('slcrm_wbraid') || '',
			utm_source: getCookie('slcrm_utm_source') || '',
			utm_campaign: getCookie('slcrm_utm_campaign') || '',
			utm_medium: getCookie('slcrm_utm_medium') || '',
			utm_term: getCookie('slcrm_utm_term') || '',
			utm_content: getCookie('slcrm_utm_content') || '',
			landing_page: getCookie('slcrm_landing_page') || window.location.href,
			referer: getCookie('slcrm_referer') || '',
			device: getCookie('slcrm_device') || detectDevice(),
			browser: getCookie('slcrm_browser') || detectBrowser()
		};
	}

	// --- Lead detection: tel: and WhatsApp links ---

	// Track if we've already fired a lead for this page view to avoid duplicates.
	var leadFired = false;

	function isWhatsAppLink(href) {
		if (!href) return false;
		var h = href.toLowerCase();
		return h.indexOf('wa.me') > -1 ||
		       h.indexOf('api.whatsapp.com') > -1 ||
		       h.indexOf('whatsapp://send') > -1;
	}

	function isTelLink(href) {
		if (!href) return false;
		return href.toLowerCase().indexOf('tel:') === 0;
	}

	function extractPhoneFromTel(href) {
		return href.replace(/^tel:/i, '').replace(/[^0-9+]/g, '');
	}

	function extractPhoneFromWhatsApp(href) {
		// wa.me/919876543210 or api.whatsapp.com/send?phone=919876543210
		var match = href.match(/wa\.me\/(\+?[0-9]+)/i);
		if (match) return match[1];
		match = href.match(/[?&]phone=([0-9+]+)/i);
		if (match) return match[1];
		return '';
	}

	function createLead(action, phone) {
		if (leadFired) return;
		leadFired = true;

		var data = collectTrackingData();
		data.action = 'slcrm_auto_lead';
		data.nonce = (window.slcrmTracker && slcrmTracker.nonce) || '';
		data.lead_action = action; // 'whatsapp' or 'call'
		data.phone = phone || '';

		// Use navigator.sendBeacon if available (works even if page unloads).
		if (navigator.sendBeacon) {
			var formData = new FormData();
			Object.keys(data).forEach(function (key) {
				formData.append(key, data[key]);
			});
			navigator.sendBeacon(slcrmTracker.ajaxUrl, formData);
		} else {
			// Fallback to synchronous XHR.
			var xhr = new XMLHttpRequest();
			xhr.open('POST', slcrmTracker.ajaxUrl, false);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			var params = [];
			Object.keys(data).forEach(function (key) {
				params.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
			});
			xhr.send(params.join('&'));
		}
	}

	// --- Attach click listeners to all links ---

	function attachLinkListeners() {
		var links = document.querySelectorAll('a[href]');

		links.forEach(function (link) {
			link.addEventListener('click', function (e) {
				var href = link.getAttribute('href') || '';

				if (isWhatsAppLink(href)) {
					var phone = extractPhoneFromWhatsApp(href);
					createLead('whatsapp', phone);
				} else if (isTelLink(href)) {
					var telPhone = extractPhoneFromTel(href);
					createLead('call', telPhone);
				}
			});
		});
	}

	// Run when DOM is ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', attachLinkListeners);
	} else {
		attachLinkListeners();
	}

	// Also watch for dynamically added links (e.g., lazy-loaded content).
	if (window.MutationObserver) {
		var observer = new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				mutation.addedNodes.forEach(function (node) {
					if (node.nodeType === 1 && node.tagName === 'A' && node.getAttribute('href')) {
						node.addEventListener('click', function () {
							var href = node.getAttribute('href') || '';
							if (isWhatsAppLink(href)) {
								createLead('whatsapp', extractPhoneFromWhatsApp(href));
							} else if (isTelLink(href)) {
								createLead('call', extractPhoneFromTel(href));
							}
						});
					}
				});
			});
		});

		observer.observe(document.body, { childList: true, subtree: true });
	}

})();
