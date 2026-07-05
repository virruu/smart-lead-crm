<?php
/**
 * Reports admin page - revenue, bookings, conversion, top routes/campaigns/landing pages.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$helper = slcrm_helper();
$db     = slcrm_db();

// Date range (default: last 30 days).
$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : current_time( 'Y-m-d' );
$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : date( 'Y-m-d', strtotime( '-30 days' ) );

$report = $db->get_report_data( $start_date, $end_date );

// Conversation/messaging stats.
$msg_stats = array( 'total_inbound' => 0, 'total_outbound' => 0, 'unique_senders' => 0, 'conversations_started' => 0 );
if ( smart_lead_crm()->messaging ) {
	$msg_stats = smart_lead_crm()->messaging->conversation->get_stats( $start_date . ' 00:00:00', $end_date . ' 23:59:59' );
}
$response_rate = $msg_stats['total_inbound'] > 0 ? round( ( $msg_stats['total_outbound'] / $msg_stats['total_inbound'] ) * 100, 1 ) : 0;
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title">
		<span class="dashicons dashicons-chart-bar"></span>
		<?php esc_html_e( 'Reports', 'smart-lead-crm' ); ?>
	</h1>

	<div class="slcrm-reports-toolbar">
		<form method="get" class="slcrm-date-form">
			<input type="hidden" name="page" value="smart-lead-crm-reports" />
			<label><?php esc_html_e( 'From:', 'smart-lead-crm' ); ?> <input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" /></label>
			<label><?php esc_html_e( 'To:', 'smart-lead-crm' ); ?> <input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" /></label>
			<button type="submit" class="button"><?php esc_html_e( 'Apply', 'smart-lead-crm' ); ?></button>
		</form>
	</div>

	<div class="slcrm-dashboard-grid">
		<div class="slcrm-stat-card slcrm-stat-leads">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-email-alt"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $report['total_leads'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Total Leads', 'smart-lead-crm' ); ?></div>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-bookings">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-calendar-check"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $report['total_bookings'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></div>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-revenue">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( $helper->format_currency( $report['revenue'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></div>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-conversion">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-chart-line"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( $report['conversion'] ); ?>%</div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Conversion %', 'smart-lead-crm' ); ?></div>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-avg-fare">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-tickets-alt"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( $helper->format_currency( $report['avg_fare'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Average Fare', 'smart-lead-crm' ); ?></div>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-repeat">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-groups"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $report['repeat_customers'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Repeat Customers', 'smart-lead-crm' ); ?></div>
			</div>
		</div>
	</div>

	<div class="slcrm-dashboard-grid">
		<div class="slcrm-stat-card slcrm-stat-google-ads">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-google"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $report['google_ads_conversions'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Google Ads Conversions', 'smart-lead-crm' ); ?></div>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-google-ads-rev">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( $helper->format_currency( $report['google_ads_revenue'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Google Ads Revenue', 'smart-lead-crm' ); ?></div>
			</div>
		</div>
	</div>

	<div class="slcrm-card slcrm-funnel-card">
		<h2><span class="dashicons dashicons-filter"></span> <?php esc_html_e( 'Conversion Funnel', 'smart-lead-crm' ); ?></h2>
		<div class="slcrm-funnel">
			<div class="slcrm-funnel-stage">
				<div class="slcrm-funnel-icon"><span class="dashicons dashicons-google"></span></div>
				<div class="slcrm-funnel-body">
					<div class="slcrm-funnel-value"><?php echo esc_html( number_format_i18n( $report['total_leads'] ) ); ?></div>
					<div class="slcrm-funnel-label"><?php esc_html_e( 'Google Ads Clicks → Leads', 'smart-lead-crm' ); ?></div>
				</div>
			</div>
			<div class="slcrm-funnel-arrow">&darr;</div>
			<div class="slcrm-funnel-stage">
				<div class="slcrm-funnel-icon"><span class="dashicons dashicons-format-chat"></span></div>
				<div class="slcrm-funnel-body">
					<div class="slcrm-funnel-value"><?php echo esc_html( number_format_i18n( $msg_stats['conversations_started'] ) ); ?></div>
					<div class="slcrm-funnel-label"><?php esc_html_e( 'Conversations Started', 'smart-lead-crm' ); ?></div>
				</div>
			</div>
			<div class="slcrm-funnel-arrow">&darr;</div>
			<div class="slcrm-funnel-stage">
				<div class="slcrm-funnel-icon"><span class="dashicons dashicons-calendar-check"></span></div>
				<div class="slcrm-funnel-body">
					<div class="slcrm-funnel-value"><?php echo esc_html( number_format_i18n( $report['total_bookings'] ) ); ?></div>
					<div class="slcrm-funnel-label"><?php esc_html_e( 'Bookings Created', 'smart-lead-crm' ); ?></div>
				</div>
			</div>
			<div class="slcrm-funnel-arrow">&darr;</div>
			<div class="slcrm-funnel-stage">
				<div class="slcrm-funnel-icon"><span class="dashicons dashicons-money-alt"></span></div>
				<div class="slcrm-funnel-body">
					<div class="slcrm-funnel-value"><?php echo esc_html( $helper->format_currency( $report['revenue'] ) ); ?></div>
					<div class="slcrm-funnel-label"><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></div>
				</div>
			</div>
		</div>
	</div>

	<div class="slcrm-dashboard-grid">
		<div class="slcrm-stat-card slcrm-stat-msg-inbound">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-format-chat"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $msg_stats['total_inbound'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Messages Received', 'smart-lead-crm' ); ?></div>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-msg-outbound">
				<div class="slcrm-stat-icon"><span class="dashicons dashicons-megaphone"></span></div>
				<div class="slcrm-stat-body">
					<div class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $msg_stats['total_outbound'] ) ); ?></div>
					<div class="slcrm-stat-label"><?php esc_html_e( 'Replies Sent', 'smart-lead-crm' ); ?></div>
				</div>
			</div>
			<div class="slcrm-stat-card slcrm-stat-msg-senders">
				<div class="slcrm-stat-icon"><span class="dashicons dashicons-groups"></span></div>
				<div class="slcrm-stat-body">
					<div class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $msg_stats['unique_senders'] ) ); ?></div>
					<div class="slcrm-stat-label"><?php esc_html_e( 'Unique Senders', 'smart-lead-crm' ); ?></div>
				</div>
			</div>
			<div class="slcrm-stat-card slcrm-stat-msg-response">
				<div class="slcrm-stat-icon"><span class="dashicons dashicons-chart-line"></span></div>
				<div class="slcrm-stat-body">
					<div class="slcrm-stat-value"><?php echo esc_html( $response_rate ); ?>%</div>
					<div class="slcrm-stat-label"><?php esc_html_e( 'Response Rate', 'smart-lead-crm' ); ?></div>
				</div>
			</div>
	</div>

	<div class="slcrm-reports-grid">
		<div class="slcrm-card">
			<h2><?php esc_html_e( 'Top Routes', 'smart-lead-crm' ); ?></h2>
			<?php if ( $report['top_routes'] ) : ?>
			<table class="slcrm-table">
				<thead><tr><th><?php esc_html_e( 'Route', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $report['top_routes'] as $row ) : ?>
					<tr><td><?php echo esc_html( $row->route ); ?></td><td><?php echo esc_html( $row->count ); ?></td><td><?php echo esc_html( $helper->format_currency( $row->revenue ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?><p><?php esc_html_e( 'No data.', 'smart-lead-crm' ); ?></p><?php endif; ?>
		</div>

		<div class="slcrm-card">
			<h2><?php esc_html_e( 'Campaign ROI', 'smart-lead-crm' ); ?></h2>
			<?php if ( ! empty( $report['campaign_roi'] ) ) : ?>
			<table class="slcrm-table">
				<thead><tr><th><?php esc_html_e( 'Campaign', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Leads', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Conv %', 'smart-lead-crm' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $report['campaign_roi'] as $row ) : ?>
					<?php $conv = $row->leads > 0 ? round( ( $row->bookings / $row->leads ) * 100, 1 ) : 0; ?>
					<tr><td><?php echo esc_html( $row->campaign ); ?></td><td><?php echo esc_html( $row->leads ); ?></td><td><?php echo esc_html( $row->bookings ); ?></td><td><?php echo esc_html( $helper->format_currency( $row->revenue ) ); ?></td><td><?php echo esc_html( $conv ); ?>%</td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?><p><?php esc_html_e( 'No campaign data. Make sure your Google Ads final URLs include utm_campaign or gclid parameters.', 'smart-lead-crm' ); ?></p><?php endif; ?>
		</div>

		<div class="slcrm-card">
			<h2><?php esc_html_e( 'Keyword ROI', 'smart-lead-crm' ); ?></h2>
			<?php if ( ! empty( $report['keyword_roi'] ) ) : ?>
			<table class="slcrm-table">
				<thead><tr><th><?php esc_html_e( 'Keyword', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Leads', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $report['keyword_roi'] as $row ) : ?>
					<tr><td><?php echo esc_html( $row->keyword ); ?></td><td><?php echo esc_html( $row->leads ); ?></td><td><?php echo esc_html( $row->bookings ); ?></td><td><?php echo esc_html( $helper->format_currency( $row->revenue ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?><p><?php esc_html_e( 'No keyword data. Add utm_term to your ad URLs to track keyword ROI.', 'smart-lead-crm' ); ?></p><?php endif; ?>
		</div>

		<div class="slcrm-card">
			<h2><?php esc_html_e( 'Top Landing Pages', 'smart-lead-crm' ); ?></h2>
			<?php if ( $report['top_landing_pages'] ) : ?>
			<table class="slcrm-table">
				<thead><tr><th><?php esc_html_e( 'Landing Page', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Leads', 'smart-lead-crm' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $report['top_landing_pages'] as $row ) : ?>
					<tr><td><a href="<?php echo esc_url( $row->landing_page ); ?>" target="_blank"><?php echo esc_html( substr( $row->landing_page, 0, 50 ) ); ?>...</a></td><td><?php echo esc_html( $row->count ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?><p><?php esc_html_e( 'No data.', 'smart-lead-crm' ); ?></p><?php endif; ?>
		</div>

		<div class="slcrm-card">
			<h2><?php esc_html_e( 'Source Performance (Revenue)', 'smart-lead-crm' ); ?></h2>
			<?php if ( ! empty( $report['source_revenue'] ) ) : ?>
			<table class="slcrm-table">
				<thead><tr><th><?php esc_html_e( 'Source', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Leads', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $report['source_revenue'] as $row ) : ?>
					<tr><td><?php echo esc_html( $helper->get_source_label( $row->lead_source ) ); ?></td><td><?php echo esc_html( $row->leads ); ?></td><td><?php echo esc_html( $row->bookings ); ?></td><td><?php echo esc_html( $helper->format_currency( $row->revenue ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?><p><?php esc_html_e( 'No source data yet.', 'smart-lead-crm' ); ?></p><?php endif; ?>
		</div>

		<div class="slcrm-card">
			<h2><?php esc_html_e( 'Leads by Status', 'smart-lead-crm' ); ?></h2>
			<?php if ( $report['by_status'] ) : ?>
			<table class="slcrm-table">
				<thead><tr><th><?php esc_html_e( 'Status', 'smart-lead-crm' ); ?></th><th><?php esc_html_e( 'Count', 'smart-lead-crm' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $report['by_status'] as $row ) : ?>
					<tr><td><span class="slcrm-status-badge slcrm-status-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( $helper->get_status_label( $row->status ) ); ?></span></td><td><?php echo esc_html( $row->count ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?><p><?php esc_html_e( 'No data.', 'smart-lead-crm' ); ?></p><?php endif; ?>
		</div>
	</div>
</div>
