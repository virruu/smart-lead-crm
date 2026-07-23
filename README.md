# Smart Lead CRM

A WordPress plugin that captures leads from website interactions (WhatsApp clicks, phone calls, emails, SMS, directions, form submissions), tracks attribution data (GCLID, UTM, organic keywords), and fires Google Ads/GA4 conversions automatically.

## Features

- **Multi-action lead capture**: WhatsApp clicks, phone calls, email, SMS, directions, and any form
- **Attribution tracking**: GCLID, GBRAID, WBRAID, UTM parameters, organic search keywords, landing page, referrer, device, browser
- **Conversion tracking**: Map CRM actions to Google Ads conversion labels and GA4 events
- **Form tracking**: Track any form by CSS selector (Contact Form 7, Elementor, WPForms, Fluent Forms, Gravity Forms, custom HTML)
- **11 business types**: Taxi, Tours, Real Estate, Clinic, Restaurant, Education, Salon, Legal, E-commerce, Services, Other
- **10 conversion presets**: Pre-configured for common actions
- **WhatsApp integration**: App Mode, Cloud API, or Coexistence
- **Lead management**: Status, source, campaign, remarks, notes, bookings
- **Admin dashboard**: Stats, funnel, lead list, detail view, settings
- **Auto-detect customer name & mobile**: Name and phone are auto-extracted from WhatsApp/tel links and form fields; manually editable on the lead detail page
- **Organic keyword handling**: Extracts keywords from search engine referrers (Bing, Yahoo, Baidu, Yandex, DuckDuckGo); shows "(not provided)" for Google organic searches since Google encrypts these terms since 2013

## Installation

1. Upload the `smart-lead-crm` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins menu
3. Go to **Smart Lead CRM > Settings** to configure your business type, WhatsApp mode, and Google Ads/GA4 IDs
4. Add conversion mappings in **Settings > Conversions** tab
5. Add form selectors in **Settings > Form Tracking** tab

## How Keyword Capture Works

### Google Ads (UTM Term)
When a visitor arrives via a Google Ads URL with `utm_term=cabsnearme`, the plugin captures this value into the `keyword` field on the lead. This works automatically — no external setup needed.

### Organic Search Keywords
- **Bing, Yahoo, Baidu, Yandex, DuckDuckGo**: The plugin extracts the search query from the referrer URL's `q`/`query`/`p`/`wd` parameters.
- **Google**: Since 2013, Google encrypts organic search queries and does not pass the keyword in the referrer. The plugin shows "(not provided)" for these visits. This is a Google limitation, not a plugin bug — no CRM or analytics tool can recover these keywords.
- **No Google Analytics needed**: The plugin does NOT require GA4 or Google Analytics for keyword capture. It parses referrer URLs directly.

## How Customer Name & Mobile Auto-Detection Works

- **WhatsApp clicks**: The phone number is extracted from the `wa.me/<phone>` or `whatsapp://send?phone=<phone>` URL
- **Phone link clicks**: Extracted from `tel:<phone>` links
- **Form submissions**: The plugin auto-detects name, email, and phone from form fields by matching field names (name, full name, customer name, email, mail, phone, mobile, tel, contact)
- **Manual edit**: On the lead detail page, you can manually enter or edit the customer name, phone number, and email at any time

## Settings That Save Properly

All settings save correctly, including checkboxes:
- GCLID capture (checkbox)
- UTM capture (checkbox)
- Organic keyword capture (checkbox)
- Debug logging (checkbox)
- Business name, type, Google Ads ID, GA4 ID (text fields)
- WhatsApp connection mode, tokens, business number (text fields)
- Cookie duration (number)

## File Structure

### Root Files

| File | Description |
|------|-------------|
| `smart-lead-crm.php` | Main plugin file — version 2.0.1, loads all classes |
| `README.md` | This file |
| `uninstall.php` | Cleanup on uninstall |

### `includes/` Directory

| File | Description |
|------|-------------|
| `class-install.php` | DB schema: `slcrm_conversions` + `slcrm_form_tracking` tables, `email` column on leads, `organic_keyword` on tracking; seeds default conversions |
| `class-db.php` | CRUD for leads, tracking, bookings, notes, conversions, form tracking |
| `class-settings.php` | All settings with proper sanitize callbacks for checkboxes |
| `class-helper.php` | 11 business types, 10 conversion presets, label helpers |
| `class-attribution.php` | Organic keyword extraction with "(not provided)" handling for Google |
| `class-conversions.php` | Builds JS conversion config, generates gtag scripts |
| `class-tracker.php` | Localizes conversions + forms config to `slcrmTracker` JS object |
| `class-ajax.php` | 4 new AJAX actions registered: save/delete conversion, save/delete form tracking; accepts name/email/form_name in auto lead |
| `class-admin.php` | Enqueues admin JS/CSS, localizes admin nonce + ajax URL |
| `class-messaging.php` | WhatsApp send/receive |
| `class-export.php` | CSV export |

### `assets/js/` Directory

| File | Description |
|------|-------------|
| `tracker.js` | Generic `createLead()`, form tracking via CSS selectors, conversion firing, MutationObserver, link detection |
| `admin.js` | Settings tabs, conversion CRUD with delete, form tracking CRUD with delete, lead/booking/note handlers, name/phone/email editing |

### `assets/css/` Directory

| File | Description |
|------|-------------|
| `admin.css` | Settings tabs, conversion/form tracking tables, responsive breakpoints |

### `admin/` Directory

| File | Description |
|------|-------------|
| `settings.php` | 5-tab settings: Business, WhatsApp, Tracking, Conversions, Form Tracking |
| `dashboard.php` | Dashboard stats |
| `leads.php` | Lead list + lead detail with editable name/phone/email |
| `reports.php` | Reports view |
| `export.php` | CSV export UI |
| `whatsapp.php` | WhatsApp settings |

## Database Tables

### `slcrm_leads`
```sql
id, name, phone, email, status, lead_source, medium, campaign, ad_group, keyword,
booking_route, booking_date, follow_up_date, gclid, gbraid, wbraid,
utm_source, utm_campaign, utm_medium, utm_term, utm_content,
landing_page, referer, device, browser, ip_address, customer_mobile,
visitor_id, remarks, last_updated, created_at
```

### `slcrm_tracking`
```sql
id, lead_id, visitor_id, visit_time, page_url,
utm_source, utm_campaign, utm_medium, utm_term, utm_content,
gclid, gbraid, wbraid, referer, device, browser, ip_address,
organic_keyword, created_at
```

### `slcrm_conversions`
```sql
id, crm_action, label, google_ads_label, ga4_event, enabled, category, sort_order
```

### `slcrm_form_tracking`
```sql
id, form_name, selector, event_type, crm_action, enabled, sort_order
```

## Version

2.0.1
