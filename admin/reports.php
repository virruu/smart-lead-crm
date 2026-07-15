<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$helper = slcrm_helper();
$db     = slcrm_db();

$end_date   = isset( $_GET['end_date'] )   ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) )   : current_time( 'Y-m-d' );
$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : gmdate( 'Y-m-d', strtotime( '-30 days', strtotime( $end_date ) ) );

$data = $db->get_report_data( $start_date, $end_date );

$msg_stats = array( 'total_inbound' => 0, 'total_outbound' => 0, 'unique_senders' => 0, 'conversations_started' => 0 );
if ( smart_lead_crm()->messaging && smart_lead_crm()->messaging->conversation ) {
	$msg_stats = smart_lead_crm()->messaging->conversation->get_stats( $start_date . ' 00:00:00', $end_date . ' 23:59:59' );
}
$response_rate = $msg_stats['total_inbound'] > 0 ? round( ( $msg_stats['total_outbound'] / $msg_stats['total_inbound'] ) * 100 ) : 0;
$funnel_pct_2 = $data['total_leads'] > 0 ? round( ( $msg_stats['conversations_started'] / $data['total_leads'] ) * 100 ) : 0;
$funnel_pct_3 = $data['total_leads'] > 0 ? round( ( $data['total_bookings'] / $data['total_leads'] ) * 100 ) : 0;
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title"><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Reports', 'smart-lead-crm' ); ?></h1>
	<p class="slcrm-subtitle"><?php esc_html_e( 'Analyze lead performance, WhatsApp engagement, and revenue attribution.', 'smart-lead-crm' ); ?></p>

	<form method="get" action="">
		<input type="hidden" name="page" value="smart-lead-crm-reports" />
		<div class="slcrm-date-range">
			<div><label><?php esc_html_e( 'From', 'smart-lead-crm' ); ?></label><input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" /></div>
			<div><label><?php esc_html_e( 'To', 'smart-lead-crm' ); ?></label><input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" /></div>
			<div style="display:flex;gap:8px;align-items:flex-end;">
				<button type="submit" class="slcrm-btn slcrm-btn-primary slcrm-btn-sm"><span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Apply', 'smart-lead-crm' ); ?></button>
				<?php foreach ( array( array(7,'Last 7 days'), array(30,'Last 30 days'), array(90,'Last 90 days') ) as $q ) :
					$qs = gmdate( 'Y-m-d', strtotime( '-' . $q[0] . ' days' ) ); $qe = current_time( 'Y-m-d' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-reports&start_date=' . $qs . '&end_date=' . $qe ) ); ?>" class="slcrm-btn slcrm-btn-outline slcrm-btn-sm"><?php echo esc_html( $q[1] ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</form>

	<div class="slcrm-grid slcrm-grid-4" style="margin-bottom:16px;">
		<div class="slcrm-stat-card slcrm-stat-leads"><div class="slcrm-stat-icon"><span class="dashicons dashicons-email-alt"></span></div><div class="slcrm-stat-body"><span class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $data['total_leads'] ) ); ?></span><span class="slcrm-stat-label"><?php esc_html_e( 'Total Leads', 'smart-lead-crm' ); ?></span></div></div>
		<div class="slcrm-stat-card slcrm-stat-bookings"><div class="slcrm-stat-icon"><span class="dashicons dashicons-calendar-check"></span></div><div class="slcrm-stat-body"><span class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $data['total_bookings'] ) ); ?></span><span class="slcrm-stat-label"><?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></span></div></div>
		<div class="slcrm-stat-card slcrm-stat-revenue"><div class="slcrm-stat-icon"><span class="dashicons dashicons-money-alt"></span></div><div class="slcrm-stat-body"><span class="slcrm-stat-value"><?php echo esc_html( $helper->format_currency( $data['revenue'] ) ); ?></span><span class="slcrm-stat-label"><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></span></div></div>
		<div class="slcrm-stat-card slcrm-stat-conversion"><div class="slcrm-stat-icon"><span class="dashicons dashicons-chart-line"></span></div><div class="slcrm-stat-body"><span class="slcrm-stat-value"><?php echo esc_html( $data['conversion'] ); ?>%</span><span class="slcrm-stat-label"><?php esc_html_e( 'Conversion Rate', 'smart-lead-crm' ); ?></span></div></div>
		<div class="slcrm-stat-card slcrm-stat-avg-fare"><div class="slcrm-stat-icon"><span class="dashicons dashicons-tickets-alt"></span></div><div class="slcrm-stat-body"><span class="slcrm-stat-value"><?php echo esc_html( $helper->format_currency( $data['avg_fare'] ) ); ?></span><span class="slcrm-stat-label"><?php esc_html_e( 'Avg Fare', 'smart-lead-crm' ); ?></span></div></div>
		<div class="slcrm-stat-card slcrm-stat-repeat"><div class="slcrm-stat-icon"><span class="dashicons dashicons-groups"></span></div><div class="slcrm-stat-body"><span class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $data['repeat_customers'] ) ); ?></span><span class="slcrm-stat-label"><?php esc_html_e( 'Repeat Customers', 'smart-lead-crm' ); ?></span></div></div>
		<div class="slcrm-stat-card slcrm-stat-wa-leads"><div class="slcrm-stat-icon"><span class="dashicons dashicons-whatsapp"></span></div><div class="slcrm-stat-body"><span class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $msg_stats['total_inbound'] ) ); ?></span><span class="slcrm-stat-label"><?php esc_html_e( 'Messages Received', 'smart-lead-crm' ); ?></span></div></div>
		<div class="slcrm-stat-card slcrm-stat-messages"><div class="slcrm-stat-icon"><span class="dashicons dashicons-whatsapp"></span></div><div class="slcrm-stat-body"><span class="slcrm-stat-value"><?php echo esc_html( $response_rate ); ?>%</span><span class="slcrm-stat-label"><?php esc_html_e( 'Response Rate', 'smart-lead-crm' ); ?></span></div></div>
	</div>

	<div class="slcrm-grid slcrm-grid-2" style="margin-bottom:16px;">
		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-filter"></span> <?php esc_html_e( 'Conversion Funnel', 'smart-lead-crm' ); ?></h3></div>
			<div class="slcrm-funnel">
				<div class="slcrm-funnel-stage"><span class="slcrm-funnel-value"><?php echo esc_html( number_format_i18n( $data['google_ads_conversions'] ) ); ?></span><span class="slcrm-funnel-label"><?php esc_html_e( 'Google Ads Clicks', 'smart-lead-crm' ); ?></span></div>
				<div class="slcrm-funnel-stage"><span class="slcrm-funnel-value"><?php echo esc_html( number_format_i18n( $data['total_leads'] ) ); ?></span><span class="slcrm-funnel-label"><?php esc_html_e( 'Leads', 'smart-lead-crm' ); ?></span></div>
				<div class="slcrm-funnel-stage"><span class="slcrm-funnel-value"><?php echo esc_html( number_format_i18n( $msg_stats['conversations_started'] ) ); ?></span><span class="slcrm-funnel-label"><?php esc_html_e( 'Conversations', 'smart-lead-crm' ); ?></span><?php if ( $funnel_pct_2 ) : ?><span class="slcrm-funnel-pct"><?php echo esc_html( $funnel_pct_2 ); ?>%</span><?php endif; ?></div>
				<div class="slcrm-funnel-stage"><span class="slcrm-funnel-value"><?php echo esc_html( number_format_i18n( $data['total_bookings'] ) ); ?></span><span class="slcrm-funnel-label"><?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></span><?php if ( $funnel_pct_3 ) : ?><span class="slcrm-funnel-pct"><?php echo esc_html( $funnel_pct_3 ); ?>%</span><?php endif; ?></div>
			</div>
		</div>

		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-whatsapp" style="color:var(--wa-green);"></span> <?php esc_html_e( 'WhatsApp Activity', 'smart-lead-crm' ); ?></h3></div>
			<table class="slcrm-detail-table">
				<tr><th><?php esc_html_e( 'Messages Received', 'smart-lead-crm' ); ?></th><td><strong><?php echo esc_html( number_format_i18n( $msg_stats['total_inbound'] ) ); ?></strong></td></tr>
				<tr><th><?php esc_html_e( 'Replies Sent', 'smart-lead-crm' ); ?></th><td><strong><?php echo esc_html( number_format_i18n( $msg_stats['total_outbound'] ) ); ?></strong></td></tr>
				<tr><th><?php esc_html_e( 'Unique Customers', 'smart-lead-crm' ); ?></th><td><strong><?php echo esc_html( number_format_i18n( $msg_stats['unique_senders'] ) ); ?></strong></td></tr>
				<tr><th><?php esc_html_e( 'Conversations Started', 'smart-lead-crm' ); ?></th><td><strong><?php echo esc_html( number_format_i18n( $msg_stats['conversations_started'] ) ); ?></strong></td></tr>
				<tr><th><?php esc_html_e( 'Response Rate', 'smart-lead-crm' ); ?></th><td><strong><?php echo esc_html( $response_rate ); ?>%</strong></td></tr>
			</table>
		</div>
	</div>

	<div class="slcrm-grid slcrm-grid-2" style="margin-bottom:16px;">
		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-megaphone"></span> <?php esc_html_e( 'Campaign ROI', 'smart-lead-crm' ); ?></h3></div>
			<?php if ( empty( $data['campaign_roi'] ) ) : ?><p style="color:var(--gray-400);font-size:13px;"><?php esc_html_e( 'No campaign data in this period.', 'smart-lead-crm' ); ?></p><?php else : ?>
			<table class="slcrm-table"><thead><tr><th><?php esc_html_e( 'Campaign', 'smart-lead-crm' ); ?></th><th class="slcrm-num"><?php esc_html_e( 'Leads', 'smart-lead-crm' ); ?></th><th class="slcrm-num"><?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></th><th class="slcrm-num"><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></th><th class="slcrm-num"><?php esc_html_e( 'Conv%', 'smart-lead-crm' ); ?></th></tr></thead><tbody>
			<?php foreach ( $data['campaign_roi'] as $r ) : ?><tr><td><?php echo esc_html( $r->campaign ?: __( '(none)', 'smart-lead-crm' ) ); ?></td><td class="slcrm-num"><?php echo esc_html( number_format_i18n( $r->leads ) ); ?></td><td class="slcrm-num"><?php echo esc_html( number_format_i18n( $r->bookings ) ); ?></td><td class="slcrm-num slcrm-currency"><?php echo esc_html( $helper->format_currency( $r->revenue ) ); ?></td><td class="slcrm-num"><?php echo esc_html( $r->conversion_pct ); ?>%</td></tr><?php endforeach; ?>
			</tbody></table>
			<?php endif; ?>
		</div>

		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'Source Performance', 'smart-lead-crm' ); ?></h3></div>
			<?php if ( empty( $data['by_source'] ) ) : ?><p style="color:var(--gray-400);font-size:13px;"><?php esc_html_e( 'No source data in this period.', 'smart-lead-crm' ); ?></p><?php else : ?>
			<table class="slcrm-table"><thead><tr><th><?php esc_html_e( 'Source', 'smart-lead-crm' ); ?></th><th class="slcrm-num"><?php esc_html_e( 'Leads', 'smart-lead-crm' ); ?></th></tr></thead><tbody>
			<?php foreach ( $data['by_source'] as $r ) : ?><tr><td><span class="slcrm-source-badge slcrm-source-<?php echo esc_attr( $r->lead_source ); ?>"><?php echo esc_html( slcrm_helper()->get_source_label( $r->lead_source ) ); ?></span></td><td class="slcrm-num"><?php echo esc_html( number_format_i18n( $r->count ) ); ?></td></tr><?php endforeach; ?>
			</tbody></table>
			<?php endif; ?>
		</div>
	</div>

	<div class="slcrm-grid slcrm-grid-2">
		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-location-alt"></span> <?php esc_html_e( 'Top Routes', 'smart-lead-crm' ); ?></h3></div>
			<?php if ( empty( $data['top_routes'] ) ) : ?><p style="color:var(--gray-400);font-size:13px;"><?php esc_html_e( 'No booking data in this period.', 'smart-lead-crm' ); ?></p><?php else : ?>
			<table class="slcrm-table"><thead><tr><th><?php esc_html_e( 'Route', 'smart-lead-crm' ); ?></th><th class="slcrm-num"><?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></th><th class="slcrm-num"><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></th></tr></thead><tbody>
			<?php foreach ( $data['top_routes'] as $r ) : ?><tr><td><?php echo esc_html( $r->route ); ?></td><td class="slcrm-num"><?php echo esc_html( number_format_i18n( $r->bookings ) ); ?></td><td class="slcrm-num slcrm-currency"><?php echo esc_html( $helper->format_currency( $r->revenue ) ); ?></td></tr><?php endforeach; ?>
			</tbody></table>
			<?php endif; ?>
		</div>

		<div class="slcrm-card">
			<div class="slcrm-section-header"><h3><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Keyword ROI', 'smart-lead-crm' ); ?></h3></div>
			<?php if ( empty( $data['keyword_roi'] ) ) : ?><p style="color:var(--gray-400);font-size:13px;"><?php esc_html_e( 'No keyword data in this period.', 'smart-lead-crm' ); ?></p><?php else : ?>
			<table class="slcrm-table"><thead><tr><th><?php esc_html_e( 'Keyword', 'smart-lead-crm' ); ?></th><th class="slcrm-num"><?php esc_html_e( 'Leads', 'smart-lead-crm' ); ?></th><th class="slcrm-num"><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></th></tr></thead><tbody>
			<?php foreach ( $data['keyword_roi'] as $r ) : ?><tr><td><?php echo esc_html( $r->keyword ?: __( '(none)', 'smart-lead-crm' ) ); ?></td><td class="slcrm-num"><?php echo esc_html( number_format_i18n( $r->leads ) ); ?></td><td class="slcrm-num slcrm-currency"><?php echo esc_html( $helper->format_currency( $r->revenue ) ); ?></td></tr><?php endforeach; ?>
			</tbody></table>
			<?php endif; ?>
		</div>
	</div>
</div>
