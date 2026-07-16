=== Smart Lead CRM ===
Contributors: smartleadcrm
Tags: crm, leads, whatsapp, tracking, google ads, gclid, utm, conversion, booking
Requires at least: 5.6
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WhatsApp-first CRM with multi-mode connection, full attribution tracking, and customer intelligence. Fully automated lead capture — no forms needed.

== Description ==

Smart Lead CRM is a commercial-grade WordPress plugin that turns your website into a WhatsApp-first business operating system. It automatically captures every inbound WhatsApp message as a lead — no forms, no manual data entry. Includes full Google Ads attribution tracking (GCLID, GBRAID, WBRAID, UTM), customer intelligence, and reporting.

= Key Features =

* **WhatsApp auto lead capture** — every inbound message to your business number auto-creates a lead with full contact history
* **Three WhatsApp connection modes** — Business App mode, Cloud API mode, and Coexistence (both simultaneously)
* **Meta Cloud API integration** — send and receive messages directly from the CRM
* **Automatic tracking** of GCLID, GBRAID, WBRAID, and UTM parameters via cookies (90-day default, configurable)
* **9-level source attribution** — Google Ads, Organic, Google Business Profile, Facebook, Instagram, WhatsApp Direct, Referral, Direct, Manual
* **Lead management** — view, edit, delete, change status, add follow-up notes, add bookings
* **Customer intelligence** — repeat customer detection, visit history, source timeline, booking history
* **Dashboard** with Today's Leads, Today's Bookings, Revenue, Conversion %, Average Fare, Repeat Customers, Top Route, Top Campaign
* **Reports** with date range filtering — revenue, bookings, conversion, average fare, repeat customers, top routes, top campaigns, top landing pages, leads by source, leads by status
* **Google Ads Offline Conversion export** — one-click Google-ready CSV with GCLID/GBRAID/WBRAID
* **Customer Match export** — one-click Google-ready CSV with SHA-256 hashed emails and phone numbers
* **Relational database** — 6 tables (leads, tracking, bookings, notes, conversations, messages) for performance at scale
* **Secure** — nonces, prepared statements, capability checks, sanitization, escaping, AJAX security

= WhatsApp Modes =

* **Business App Mode** — Keep using your phone's WhatsApp Business App. CRM auto-captures every inbound message as a lead. You reply from your phone.
* **Cloud API Mode** — Official Meta WhatsApp Cloud API. Send and receive messages directly from the CRM. Team inbox ready, automation & AI ready, marketing campaigns.
* **Coexistence Mode** — Use the WhatsApp Business App on your phone AND the Cloud API simultaneously. Messages sync both ways.

= Lead Sources =

Google Ads, Organic, Google Business Profile, Facebook, Instagram, WhatsApp Direct, Referral, Direct, Manual

= Lead Statuses =

New Lead, Contacted, Booked, Cancelled, Follow-up

= Booking Types =

Airport, Outstation, Local, Hourly Rental, Railway, Corporate, Tour

== Installation ==

1. Upload the `smart-lead-crm` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Smart Lead CRM > Settings to configure your business name and WhatsApp number
4. Go to Smart Lead CRM > WhatsApp Connection to set up your Meta App webhook
5. The tracker runs automatically on all frontend pages — no shortcode or form needed

== Frequently Asked Questions ==

= How does lead capture work without a form? =

The plugin tracks visitor clicks on WhatsApp links (`wa.me/`, `whatsapp://send`) and phone links (`tel:`) on your website. When a visitor clicks, a lead is created with all their attribution data. Additionally, when a customer messages your WhatsApp Business number, the Meta webhook auto-creates a lead in the CRM.

= How do I set up WhatsApp auto-capture? =

Go to Smart Lead CRM > WhatsApp Connection. The page has a complete step-by-step guide for configuring your Meta App, webhook, and business number. You need a Meta Developers account (free) and the WhatsApp product added to your app.

= Does this plugin create its own database tables? =

Yes. It uses a relational design with six tables: leads, tracking, bookings, notes, conversations, and messages. This is faster than using wp_posts when you have 50,000+ leads.

= How do I export Google Ads offline conversions? =

Go to Smart Lead CRM > Export and click "Export Offline Conversions". Only leads with GCLID/GBRAID/WBRAID data (from Google Ads) will be included. Upload the CSV to Google Ads > Tools > Conversions > Uploads.

== Changelog ==

= 1.4.0 =
* WhatsApp Business App mode — auto-capture every inbound message as a lead
* Meta Cloud API integration — send and receive from CRM
* Coexistence mode — phone app + Cloud API simultaneously
* Three WhatsApp connection modes with visual mode selector
* Complete Meta App setup guide in WhatsApp tab
* 9-level source attribution (was 8)
* Customer intelligence: repeat detection, visit history, source timeline
* New database tables: conversations, messages (now 6 total)
* Webhook endpoint at /wp-json/slcrm/v1/webhook
* Removed lead capture form shortcode (auto-capture replaces it)
* CSS design system rewrite
* Dashboard, Leads, Reports, Settings UI refresh

= 1.0.0 =
* Initial release
* Plugin framework with class-based loading
* Four relational database tables (leads, tracking, bookings, notes)
* Admin menu with Dashboard, Leads, Reports, Export, and Settings
* Automatic tracking of GCLID, GBRAID, WBRAID, UTM parameters
* Lead capture form via shortcode with AJAX submission
* Google Ads offline conversion export
* Customer Match export (SHA-256 hashed CSV)
