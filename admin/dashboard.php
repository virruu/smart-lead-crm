<?php
/**
 * Dashboard admin page.
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$helper       = slcrm_helper();
$business_name = slcrm_get_setting( 'business_name', get_bloginfo( 'name' ) );
$db           = slcrm_db();

// Get top route and top campaign for today.
$today_start = current_time( 'Y-m-d 00:00:00' );
$today_end   = current_time( 'Y-m-d 23:59:59' );

$top_route = $db->get_report_data( current_time( 'Y-m-d' ), current_time( 'Y-m-d' ) );
$top_route_name = ( ! empty( $top_route['top_routes'] ) ) ? $top_route['top_routes'][0]->route : '—';
$top_campaign_name = ( ! empty( $top_route['top_campaigns'] ) ) ? $top_route['top_campaigns'][0]->campaign : '—';
?>
<div class="wrap slcrm-wrap">
	<h1 class="slcrm-title">
		<span class="dashicons dashicons-chart-area"></span>
		<?php esc_html_e( 'Smart Lead CRM', 'smart-lead-crm' ); ?>
	</h1>
	<p class="slcrm-subtitle">
		<?php
		/* translators: %s: Business name */
		printf( esc_html__( 'Business operating system for %s', 'smart-lead-crm' ), '<strong>' . esc_html( $business_name ) . '</strong>' );
		?>
	</p>

	<div class="slcrm-dashboard-grid">
		<div class="slcrm-stat-card slcrm-stat-leads">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-email-alt"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $stats['today_leads'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( "Today's Leads", 'smart-lead-crm' ); ?></div>
			</div>
		</div>

		<div class="slcrm-stat-card slcrm-stat-bookings">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-calendar-check"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $stats['today_bookings'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( "Today's Bookings", 'smart-lead-crm' ); ?></div>
			</div>
		</div>

		<div class="slcrm-stat-card slcrm-stat-revenue">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( $helper->format_currency( $stats['revenue'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></div>
			</div>
		</div>

		<div class="slcrm-stat-card slcrm-stat-conversion">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-chart-line"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( $stats['conversion'] ); ?>%</div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Conversion %', 'smart-lead-crm' ); ?></div>
			</div>
		</div>

		<div class="slcrm-stat-card slcrm-stat-avg-fare">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-tickets-alt"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( $helper->format_currency( $stats['avg_fare'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Average Fare', 'smart-lead-crm' ); ?></div>
			</div>
		</div>

		<div class="slcrm-stat-card slcrm-stat-repeat">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-groups"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $stats['repeat_customers'] ) ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( 'Repeat Customers', 'smart-lead-crm' ); ?></div>
			</div>
		</div>
	</div>

	<div class="slcrm-dashboard-grid slcrm-dashboard-grid-2">
		<div class="slcrm-stat-card slcrm-stat-route">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-location-alt"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( $top_route_name ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( "Today's Top Route", 'smart-lead-crm' ); ?></div>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-campaign">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-megaphone"></span></div>
			<div class="slcrm-stat-body">
				<div class="slcrm-stat-value"><?php echo esc_html( $top_campaign_name ); ?></div>
				<div class="slcrm-stat-label"><?php esc_html_e( "Today's Top Campaign", 'smart-lead-crm' ); ?></div>
			</div>
		</div>
	</div>

	<div class="slcrm-quick-actions">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads' ) ); ?>" class="button button-primary">
			<span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'View Leads', 'smart-lead-crm' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-reports' ) ); ?>" class="button">
			<span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Reports', 'smart-lead-crm' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-export' ) ); ?>" class="button">
			<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export', 'smart-lead-crm' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" class="button">
			<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'smart-lead-crm' ); ?>
		</a>
	</div>

	<div class="slcrm-info-banner">
		<span class="dashicons dashicons-info"></span>
		<?php esc_html_e( 'Tracking runs automatically on all pages. Leads are auto-created when visitors click WhatsApp or phone links. No shortcode or form needed.', 'smart-lead-crm' ); ?>
	</div>
</div>
