=== Smart Lead CRM ===
Contributors: smartleadcrm
Tags: crm, leads, tracking, google ads, gclid, utm, conversion, booking, customer match
Requires at least: 5.6
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A business operating system for lead capture, tracking, management, reporting, and Google Ads export. Automatically captures GCLID, GBRAID, WBRAID, UTM parameters, device, browser, and referrer data.

== Description ==

Smart Lead CRM is a commercial-grade WordPress plugin that turns your website into a business operating system. It automatically captures Google Ads tracking parameters (GCLID, GBRAID, WBRAID), UTM parameters, landing page, device, browser, and referrer data — no setup needed.

= Features =

* **Automatic tracking** of GCLID, GBRAID, WBRAID, and UTM parameters via cookies (90-day default, configurable)
* **Lead capture form** via shortcode `[slcrm_lead_form]` with AJAX submission
* **Lead management** — view, edit, delete, change status, add follow-up notes, add bookings
* **Relational database design** (leads, tracking, bookings, notes) for performance at scale
* **Dashboard** with Today's Leads, Today's Bookings, Revenue, Conversion %, Average Fare, Repeat Customers, Top Route, Top Campaign
* **Reports** with date range filtering — revenue, bookings, conversion, average fare, repeat customers, top routes, top campaigns, top landing pages, leads by source, leads by status
* **Google Ads Offline Conversion export** — one-click Google-ready CSV with GCLID/GBRAID/WBRAID
* **Customer Match export** — one-click Google-ready CSV with SHA-256 hashed emails and phone numbers
* **Search** by phone, name, route, campaign, status, source
* **Settings page** for business name, Google Ads conversion ID, GA4 measurement ID, cookie duration, and debug mode
* **Secure** — nonces, prepared statements, capability checks, sanitization, escaping, AJAX security
* **Mobile-friendly** admin interface

= Lead Sources =

Google Ads, Organic, Google Business Profile, Facebook, Instagram, WhatsApp Direct, Referral, Manual

= Lead Statuses =

Pending, Contacted, Booked, Cancelled, Follow-up

= Booking Types =

Airport, Outstation, Local, Hourly Rental, Railway, Corporate, Tour

== Installation ==

1. Upload the `smart-lead-crm` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Smart Lead CRM > Settings to configure your business details
4. Add the shortcode `[slcrm_lead_form]` to any page to start capturing leads
5. The tracker runs automatically on all frontend pages

== Frequently Asked Questions ==

= Does this plugin create its own database tables? =

Yes. It uses a relational design with four tables: leads, tracking, bookings, and notes. This is faster than using wp_posts when you have 50,000+ leads.

= How do I capture leads? =

Add the shortcode `[slcrm_lead_form]` to any page or post. The form submits via AJAX and automatically attaches all tracking data from cookies.

= How do I export Google Ads offline conversions? =

Go to Smart Lead CRM > Export and click "Export Offline Conversions". Only leads with GCLID/GBRAID/WBRAID data (from Google Ads) will be included. Upload the CSV to Google Ads > Tools > Conversions > Uploads.

= How do I use Customer Match? =

Go to Smart Lead CRM > Export and click "Export Customer Match". Phone numbers are normalized to international format and hashed with SHA-256. Upload to Google Ads > Tools > Audience Manager > New Audience > Customer list.

== Changelog ==

= 1.0.0 =
* Initial release with all 5 phases
* Plugin framework with PSR-style class loading
* Four relational database tables (leads, tracking, bookings, notes)
* Admin menu with Dashboard, Leads, Reports, Export, and Settings
* Automatic tracking of GCLID, GBRAID, WBRAID, UTM parameters, device, browser, referrer
* Cookie-based tracking with configurable duration
* Lead capture form via shortcode with AJAX submission
* Lead management — view, edit, delete, status changes, notes, bookings
* Reports with date range filtering and top routes/campaigns/landing pages
* Google Ads offline conversion export (Google-ready CSV)
* Customer Match export (SHA-256 hashed CSV)
* Dashboard with six stat cards plus top route and top campaign
* Database versioning for automatic upgrades
