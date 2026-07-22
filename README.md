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

## Installation

1. Upload the `smart-lead-crm` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins menu
3. Go to **Smart Lead CRM > Settings** to configure your business type, WhatsApp mode, and Google Ads/GA4 IDs
4. Add conversion mappings in **Settings > Conversions** tab
5. Add form selectors in **Settings > Form Tracking** tab

## File Structure

### Root Files

| File | Status | Description |
|------|--------|-------------|
| `smart-lead-crm.php` | **Update** | Main plugin file — version 2.0.0, loads all classes |
| `README.md` | **New** | This file |
| `uninstall.php` | **Update** | Cleanup on uninstall |

### `includes/` Directory

| File | Status | Description |
|------|--------|-------------|
| `class-install.php` | **Update** | DB schema: added `slcrm_conversions` + `slcrm_form_tracking` tables, `email` column on leads, `organic_keyword` on tracking; seeds default conversions |
| `class-db.php` | **Update** | Added CRUD for conversions + form tracking, `get_lead_by_phone()` |
| `class-settings.php` | **Update** | Added `business_type`, `capture_organic_keywords`, `form_capture_name/email/phone` settings |
| `class-helper.php` | **Update** | Added 11 business types, 10 conversion presets, `get_conversion_label()` |
| `class-attribution.php` | **Update** | Added `extract_organic_keyword()` for search engine referrer parsing |
| `class-conversions.php` | **New** | Builds JS conversion config, generates gtag scripts |
| `class-tracker.php` | **Update** | Localizes conversions + forms config to `slcrmTracker` JS object |
| `class-ajax.php` | **Update** | Accepts name/email/form_name in auto lead, dynamic remarks, conversion + form tracking CRUD handlers |
| `class-admin.php` | **Update** | Enqueues admin JS/CSS, localizes admin nonce + ajax URL |
| `class-messaging.php` | **Existing** | WhatsApp send/receive (no changes) |
| `class-export.php` | **Existing** | CSV export (no changes) |

### `assets/js/` Directory

| File | Status | Description |
|------|--------|-------------|
| `tracker.js` | **Rewrite** | Generic `createLead()`, form tracking via CSS selectors, conversion firing, MutationObserver, link detection |
| `admin.js` | **Rewrite** | Settings tabs, conversion CRUD, form tracking CRUD, all existing lead/booking/note handlers |

### `assets/css/` Directory

| File | Status | Description |
|------|--------|-------------|
| `admin.css` | **Update** | Settings tabs, conversion/form tracking tables, responsive breakpoints |

### `admin/` Directory

| File | Status | Description |
|------|--------|-------------|
| `settings.php` | **Rewrite** | 5-tab settings: Business, WhatsApp, Tracking, Conversions, Form Tracking |
| `dashboard.php` | **Existing** | Dashboard stats (no changes) |
| `leads.php` | **Existing** | Lead list (no changes) |
| `lead-detail.php` | **Existing** | Lead detail view (no changes) |
| `setup.php` | **Existing** | Setup wizard (no changes) |

## New Files to Create

1. `includes/class-conversions.php` — Conversion management class
2. `README.md` — This documentation

## Files to Update

1. `smart-lead-crm.php` — Version 2.0.0, include `class-conversions.php`
2. `includes/class-install.php` — New tables + columns + seed data
3. `includes/class-db.php` — Conversion + form tracking CRUD
4. `includes/class-settings.php` — New settings fields
5. `includes/class-helper.php` — Business types + conversion presets
6. `includes/class-attribution.php` — Organic keyword extraction
7. `includes/class-tracker.php` — Localize conversion/form config
8. `includes/class-ajax.php` — Register 4 new AJAX actions, accept extra lead data
9. `includes/class-admin.php` — Enqueue updated scripts
10. `admin/settings.php` — 5-tab settings UI
11. `assets/js/tracker.js` — Full rewrite
12. `assets/js/admin.js` — Full rewrite
13. `assets/css/admin.css` — New styles

## Database Tables

### `slcrm_leads` (updated)
Added `email` column (varchar(255)).

### `slcrm_tracking` (updated)
Added `organic_keyword` column (varchar(255)).

### `slcrm_conversions` (new)
```sql
id, crm_action, label, google_ads_label, ga4_event, enabled, category, sort_order
```

### `slcrm_form_tracking` (new)
```sql
id, form_name, selector, event_type, crm_action, enabled, sort_order
```

## Version

2.0.0
