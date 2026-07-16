<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$helper        = slcrm_helper();
$settings      = smart_lead_crm()->settings;
$db            = slcrm_db();
$business_name = slcrm_get_setting( 'business_name', get_bloginfo( 'name' ) );
$mode          = $settings->get_whatsapp_mode();
$mode_label    = $settings->get_whatsapp_mode_label();
$cloud_ready   = $settings->is_cloud_api_configured();

$stats       = $db->get_dashboard_stats();
$report_data = $db->get_report_data( current_time( 'Y-m-d' ), current_time( 'Y-m-d' ) );

$top_route    = ! empty( $report_data['top_routes'] )    ? $report_data['top_routes'][0]->route       : '—';
$top_campaign = ! empty( $report_data['top_campaigns'] ) ? $report_data['top_campaigns'][0]->campaign : '—';
$wa_leads     = $db->count_leads( array( 'source' => 'whatsapp' ) );
$recent_leads = $db->get_leads( array( 'number' => 5, 'orderby' => 'last_updated', 'order' => 'DESC' ) );

$mode_meta = array(
	'app_mode'    => array( 'icon' => 'phone', 'color' => 'var(--wa-green)', 'bg' => '#f0fdf4' ),
	'cloud_api'   => array( 'icon' => 'cloud', 'color' => 'var(--primary-600)', 'bg' => 'var(--primary-50)' ),
	'coexistence' => array( 'icon' => 'sync',  'color' => '#7c3aed', 'bg' => '#faf5ff' ),
);
$mm = $mode_meta[ $mode ] ?? $mode_meta['app_mode'];
?>
<div class="wrap slcrm-wrap">

	<h1 class="slcrm-title">
		<span class="dashicons dashicons-chart-area"></span>
		<?php esc_html_e( 'Smart Lead CRM', 'smart-lead-crm' ); ?>
	</h1>
	<p class="slcrm-subtitle">
		<?php printf( esc_html__( 'Business operating system for %s', 'smart-lead-crm' ), '<strong>' . esc_html( $business_name ) . '</strong>' ); ?>
	</p>

	<div class="slcrm-quick-actions" style="margin-bottom:24px;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-whatsapp' ) ); ?>"
		   style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:<?php echo esc_attr( $mm['bg'] ); ?>;border:1.5px solid <?php echo esc_attr( $mm['color'] ); ?>;border-radius:9999px;font-size:13px;font-weight:600;color:<?php echo esc_attr( $mm['color'] ); ?>;text-decoration:none;">
			<span class="dashicons dashicons-<?php echo esc_attr( $mm['icon'] ); ?>" style="font-size:16px;width:16px;height:16px;"></span>
			<?php echo esc_html( $mode_label ); ?>
			<?php if ( 'app_mode' !== $mode && ! $cloud_ready ) : ?>
				<span style="background:var(--warning-500);color:#fff;font-size:10px;padding:1px 6px;border-radius:9999px;font-weight:700;">SETUP NEEDED</span>
			<?php elseif ( 'app_mode' !== $mode && $cloud_ready ) : ?>
				<span style="background:var(--success-500);color:#fff;font-size:10px;padding:1px 6px;border-radius:9999px;font-weight:700;">CONNECTED</span>
			<?php endif; ?>
		</a>
	</div>

	<div class="slcrm-grid slcrm-grid-4" style="margin-bottom:16px;">
		<div class="slcrm-stat-card slcrm-stat-leads">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-email-alt"></span></div>
			<div class="slcrm-stat-body">
				<span class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $stats['today_leads'] ) ); ?></span>
				<span class="slcrm-stat-label"><?php esc_html_e( "Today's Leads", 'smart-lead-crm' ); ?></span>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-bookings">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-calendar-check"></span></div>
			<div class="slcrm-stat-body">
				<span class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $stats['today_bookings'] ) ); ?></span>
				<span class="slcrm-stat-label"><?php esc_html_e( "Today's Bookings", 'smart-lead-crm' ); ?></span>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-revenue">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
			<div class="slcrm-stat-body">
				<span class="slcrm-stat-value"><?php echo esc_html( $helper->format_currency( $stats['revenue'] ) ); ?></span>
				<span class="slcrm-stat-label"><?php esc_html_e( 'Total Revenue', 'smart-lead-crm' ); ?></span>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-conversion">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-chart-line"></span></div>
			<div class="slcrm-stat-body">
				<span class="slcrm-stat-value"><?php echo esc_html( $stats['conversion'] ); ?>%</span>
				<span class="slcrm-stat-label"><?php esc_html_e( 'Conversion Rate', 'smart-lead-crm' ); ?></span>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-avg-fare">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-tickets-alt"></span></div>
			<div class="slcrm-stat-body">
				<span class="slcrm-stat-value"><?php echo esc_html( $helper->format_currency( $stats['avg_fare'] ) ); ?></span>
				<span class="slcrm-stat-label"><?php esc_html_e( 'Avg Fare', 'smart-lead-crm' ); ?></span>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-repeat">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-groups"></span></div>
			<div class="slcrm-stat-body">
				<span class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $stats['repeat_customers'] ) ); ?></span>
				<span class="slcrm-stat-label"><?php esc_html_e( 'Repeat Customers', 'smart-lead-crm' ); ?></span>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-wa-leads">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-whatsapp"></span></div>
			<div class="slcrm-stat-body">
				<span class="slcrm-stat-value"><?php echo esc_html( number_format_i18n( $wa_leads ) ); ?></span>
				<span class="slcrm-stat-label"><?php esc_html_e( 'WhatsApp Leads', 'smart-lead-crm' ); ?></span>
			</div>
		</div>
		<div class="slcrm-stat-card slcrm-stat-route">
			<div class="slcrm-stat-icon"><span class="dashicons dashicons-location-alt"></span></div>
			<div class="slcrm-stat-body">
				<span class="slcrm-stat-value" style="font-size:16px;padding-top:4px;"><?php echo esc_html( $top_route ); ?></span>
				<span class="slcrm-stat-label"><?php esc_html_e( "Today's Top Route", 'smart-lead-crm' ); ?></span>
			</div>
		</div>
	</div>

	<div class="slcrm-grid slcrm-grid-2" style="margin-top:8px;">
		<div class="slcrm-card">
			<div class="slcrm-section-header">
				<h3><span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'Recent Leads', 'smart-lead-crm' ); ?></h3>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads' ) ); ?>" class="slcrm-btn slcrm-btn-outline slcrm-btn-sm"><?php esc_html_e( 'View All', 'smart-lead-crm' ); ?></a>
			</div>
			<?php if ( empty( $recent_leads ) ) : ?>
				<div class="slcrm-empty-state" style="padding:32px;">
					<span class="dashicons dashicons-email-alt"></span>
					<h3><?php esc_html_e( 'No leads yet', 'smart-lead-crm' ); ?></h3>
					<p><?php esc_html_e( 'Leads appear automatically when customers message you on WhatsApp or click a WhatsApp/phone link.', 'smart-lead-crm' ); ?></p>
				</div>
			<?php else : ?>
				<div style="display:flex;flex-direction:column;">
					<?php foreach ( $recent_leads as $lead ) :
						$initials = strtoupper( substr( $lead->name ?: $lead->phone, 0, 1 ) );
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads&action=view&lead_id=' . $lead->id ) ); ?>"
					   style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--gray-100);text-decoration:none;color:inherit;">
						<div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary-500),var(--accent-500));color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0;">
							<?php echo esc_html( $initials ); ?>
						</div>
						<div style="flex:1;min-width:0;">
							<div style="font-size:14px;font-weight:600;color:var(--gray-800);display:flex;align-items:center;gap:6px;">
								<span class="slcrm-lead-id">#<?php echo esc_html( $lead->id ); ?></span>
								<?php echo esc_html( $lead->name ?: ( $lead->phone ?: 'Website Visitor' ) ); ?>
							</div>
							<div style="font-size:12px;color:var(--gray-400);margin-top:2px;">
								<?php if ( $lead->remarks ) : ?>
									<?php echo esc_html( wp_trim_words( $lead->remarks, 7, '…' ) ); ?>
								<?php elseif ( $lead->phone ) : ?>
									<span class="dashicons dashicons-phone" style="font-size:11px;width:11px;height:11px;vertical-align:middle;"></span> <?php echo esc_html( $lead->phone ); ?>
								<?php else : ?>
									<?php esc_html_e( 'Waiting for WhatsApp message…', 'smart-lead-crm' ); ?>
								<?php endif; ?>
							</div>
						</div>
						<div style="text-align:right;flex-shrink:0;">
							<span class="slcrm-status-badge slcrm-status-<?php echo esc_attr( $lead->status ); ?>"><?php echo esc_html( $helper->get_status_label( $lead->status ) ); ?></span>
							<div style="font-size:11px;color:var(--gray-400);margin-top:4px;"><?php echo esc_html( $helper->format_date( $lead->last_updated, 'M j' ) ); ?></div>
						</div>
					</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div style="display:flex;flex-direction:column;gap:16px;">
			<div class="slcrm-card">
				<div class="slcrm-section-header">
					<h3><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Quick Actions', 'smart-lead-crm' ); ?></h3>
				</div>
				<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads' ) ); ?>" class="slcrm-btn slcrm-btn-primary">
						<span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'All Leads', 'smart-lead-crm' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-whatsapp' ) ); ?>" class="slcrm-btn" style="background:var(--wa-green);color:#fff;">
						<span class="dashicons dashicons-whatsapp"></span> <?php esc_html_e( 'WhatsApp', 'smart-lead-crm' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-reports' ) ); ?>" class="slcrm-btn slcrm-btn-outline">
						<span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Reports', 'smart-lead-crm' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-export' ) ); ?>" class="slcrm-btn slcrm-btn-outline">
						<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export', 'smart-lead-crm' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-settings' ) ); ?>" class="slcrm-btn slcrm-btn-outline" style="grid-column:span 2;">
						<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'smart-lead-crm' ); ?>
					</a>
				</div>
			</div>

			<div class="slcrm-card" style="background:linear-gradient(135deg,#1e293b,#334155);border-color:transparent;">
				<div style="display:flex;align-items:center;gap:12px;">
					<div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;">
						<span class="dashicons dashicons-megaphone" style="font-size:22px;width:22px;height:22px;color:#fff;"></span>
					</div>
					<div>
						<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.5);margin-bottom:4px;"><?php esc_html_e( "Today's Top Campaign", 'smart-lead-crm' ); ?></div>
						<div style="font-size:18px;font-weight:700;color:#fff;"><?php echo esc_html( $top_campaign ); ?></div>
					</div>
				</div>
			</div>

			<div class="slcrm-banner slcrm-banner-info">
				<span class="dashicons dashicons-info"></span>
				<div><?php esc_html_e( 'Lead capture is fully automated. Leads are auto-created when visitors click WhatsApp or phone links, and when customers message your WhatsApp Business number. No forms needed.', 'smart-lead-crm' ); ?></div>
			</div>
		</div>
	</div>
</div>
