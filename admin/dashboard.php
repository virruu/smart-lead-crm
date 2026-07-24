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

	<?php
	$follow_ups = $db->get_follow_ups();
	$overdue_count = $db->get_overdue_count();
	if ( ! empty( $follow_ups ) ) :
	?>
	<div class="slcrm-card" style="margin-top:16px;border-left:4px solid var(--warning);">
		<div class="slcrm-section-header">
			<h3 style="color:var(--warning);">
				<span class="dashicons dashicons-bell"></span>
				<?php esc_html_e( 'Follow-up Reminders', 'smart-lead-crm' ); ?>
				<?php if ( $overdue_count > 0 ) : ?>
					<span style="background:var(--warning);color:#fff;font-size:11px;padding:2px 10px;border-radius:9999px;font-weight:700;margin-left:6px;"><?php echo esc_html( number_format_i18n( $overdue_count ) ); ?> <?php esc_html_e( 'overdue', 'smart-lead-crm' ); ?></span>
				<?php endif; ?>
			</h3>
		</div>
		<p style="color:var(--gray-500);font-size:13px;margin-bottom:12px;"><?php esc_html_e( 'These leads have been waiting more than 24 hours. Follow up to increase conversion.', 'smart-lead-crm' ); ?></p>
		<div style="display:flex;flex-direction:column;">
			<?php foreach ( $follow_ups as $fu ) :
				$hours_ago = round( ( time() - strtotime( $fu->last_updated ) ) / 3600 );
				$wa_link = $fu->phone ? 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $fu->phone ) : '';
			?>
			<div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--gray-100);">
				<div style="width:32px;height:32px;border-radius:50%;background:var(--warning);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">
					<span class="dashicons dashicons-clock" style="font-size:16px;width:16px;height:16px;"></span>
				</div>
				<div style="flex:1;min-width:0;">
					<div style="font-size:14px;font-weight:600;color:var(--gray-800);">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-lead-crm-leads&action=view&lead_id=' . $fu->id ) ); ?>" style="color:inherit;text-decoration:none;">
							#<?php echo esc_html( $fu->id ); ?> <?php echo esc_html( $fu->name ?: ( $fu->phone ?: 'Website Visitor' ) ); ?>
						</a>
					</div>
					<div style="font-size:12px;color:var(--gray-400);margin-top:2px;">
						<?php echo esc_html( $helper->get_status_label( $fu->status ) ); ?> · <?php echo esc_html( $helper->get_source_label( $fu->lead_source ) ); ?>
						· <?php printf( esc_html__( '%s hours ago', 'smart-lead-crm' ), esc_html( $hours_ago ) ); ?>
					</div>
				</div>
				<?php if ( $wa_link ) : ?>
				<a href="<?php echo esc_url( $wa_link ); ?>" target="_blank" class="slcrm-btn slcrm-btn-sm" style="background:var(--wa-green);color:#fff;flex-shrink:0;">
					<span class="dashicons dashicons-whatsapp"></span> <?php esc_html_e( 'Follow up', 'smart-lead-crm' ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php
	$campaigns = $db->get_revenue_by_campaign();
	$daily_spend = (float) slcrm_get_setting( 'google_ads_daily_spend', '0' );
	if ( ! empty( $campaigns ) && $daily_spend > 0 ) :
	?>
	<div class="slcrm-card" style="margin-top:16px;">
		<div class="slcrm-section-header">
			<h3><span class="dashicons dashicons-chart-line"></span> <?php esc_html_e( 'ROI by Campaign', 'smart-lead-crm' ); ?></h3>
		</div>
		<div style="overflow-x:auto;">
			<table class="slcrm-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Campaign', 'smart-lead-crm' ); ?></th>
						<th style="text-align:center;"><?php esc_html_e( 'Leads', 'smart-lead-crm' ); ?></th>
						<th style="text-align:center;"><?php esc_html_e( 'Bookings', 'smart-lead-crm' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'Revenue', 'smart-lead-crm' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'Est. Cost', 'smart-lead-crm' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'ROI', 'smart-lead-crm' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$total_leads = 0;
					foreach ( $campaigns as $c ) $total_leads += (int) $c->leads;
					foreach ( $campaigns as $c ) :
						$lead_share = $total_leads > 0 ? ( (int) $c->leads / $total_leads ) : 0;
						$est_cost = $daily_spend * 30 * $lead_share;
						$revenue = (float) $c->revenue;
						$roi = $est_cost > 0 ? ( ( $revenue - $est_cost ) / $est_cost * 100 ) : 0;
						$roi_color = $roi >= 0 ? 'var(--success)' : 'var(--error)';
					?>
					<tr>
						<td style="font-weight:600;"><?php echo esc_html( $c->campaign ); ?></td>
						<td style="text-align:center;"><?php echo esc_html( number_format_i18n( $c->leads ) ); ?></td>
						<td style="text-align:center;"><?php echo esc_html( number_format_i18n( $c->bookings ) ); ?></td>
						<td style="text-align:right;font-weight:600;"><?php echo esc_html( slcrm_format_currency( $revenue ) ); ?></td>
						<td style="text-align:right;color:var(--gray-500);"><?php echo esc_html( slcrm_format_currency( $est_cost ) ); ?></td>
						<td style="text-align:right;font-weight:700;color:<?php echo esc_attr( $roi_color ); ?>;"><?php echo esc_html( number_format_i18n( $roi, 1 ) ); ?>%</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p style="color:var(--gray-400);font-size:12px;margin-top:8px;"><?php esc_html_e( 'Estimated cost is based on your daily ad spend setting, proportional to lead volume per campaign. Revenue is based on booking fares.', 'smart-lead-crm' ); ?></p>
	</div>
	<?php endif; ?>

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
						$action_label = $lead->lead_action ? $helper->get_conversion_label( $lead->lead_action ) : '';
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
								<?php if ( $action_label ) : ?>
									<span class="dashicons dashicons-bell" style="font-size:11px;width:11px;height:11px;vertical-align:middle;color:var(--primary-500);"></span>
									<?php echo esc_html( $action_label ); ?>
									<?php if ( $lead->form_name ) echo ' — ' . esc_html( $lead->form_name ); ?>
								<?php elseif ( $lead->remarks ) : ?>
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
			<?php
			$today_triggers = $db->get_today_triggers();
			if ( ! empty( $today_triggers ) ) :
			?>
			<div class="slcrm-card">
				<div class="slcrm-section-header">
					<h3><span class="dashicons dashicons-bell"></span> <?php esc_html_e( "Today's Triggers", 'smart-lead-crm' ); ?></h3>
				</div>
				<div style="display:flex;flex-wrap:wrap;gap:8px;">
					<?php foreach ( $today_triggers as $t ) : ?>
						<span style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:var(--primary-50);border:1px solid var(--primary-200);border-radius:9999px;font-size:13px;font-weight:600;color:var(--primary-700);">
							<span class="dashicons dashicons-<?php echo esc_attr( $t->lead_action === 'whatsapp' ? 'whatsapp' : ( $t->lead_action === 'phone' ? 'phone' : 'feedback' ) ); ?>" style="font-size:14px;width:14px;height:14px;"></span>
							<?php echo esc_html( $helper->get_conversion_label( $t->lead_action ) ); ?>
							<span style="background:var(--primary-500);color:#fff;font-size:11px;padding:1px 8px;border-radius:9999px;font-weight:700;"><?php echo esc_html( number_format_i18n( $t->count ) ); ?></span>
						</span>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

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

<button class="slcrm-fab" id="slcrm-quick-add-fab" title="<?php esc_attr_e( 'Add Lead', 'smart-lead-crm' ); ?>">
	<span class="dashicons dashicons-plus-alt2"></span>
</button>
<span class="slcrm-fab-label"><?php esc_html_e( 'Quick Add Lead', 'smart-lead-crm' ); ?></span>

<div class="slcrm-modal-overlay" id="slcrm-quick-add-modal">
	<div class="slcrm-modal">
		<div class="slcrm-modal-header">
			<h3><span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'Quick Add Lead', 'smart-lead-crm' ); ?></h3>
			<button class="slcrm-modal-close" type="button"><span class="dashicons dashicons-no-alt"></span></button>
		</div>
		<div class="slcrm-modal-body">
			<div class="slcrm-form-field">
				<label for="slcrm-qa-name"><?php esc_html_e( 'Customer Name', 'smart-lead-crm' ); ?></label>
				<input type="text" id="slcrm-qa-name" placeholder="John Doe" />
			</div>
			<div class="slcrm-form-field">
				<label for="slcrm-qa-phone"><?php esc_html_e( 'Phone Number', 'smart-lead-crm' ); ?></label>
				<input type="text" id="slcrm-qa-phone" placeholder="919876543210" />
			</div>
			<div class="slcrm-form-field">
				<label for="slcrm-qa-email"><?php esc_html_e( 'Email (optional)', 'smart-lead-crm' ); ?></label>
				<input type="email" id="slcrm-qa-email" placeholder="customer@example.com" />
			</div>
			<div class="slcrm-form-field">
				<label for="slcrm-qa-source"><?php esc_html_e( 'Lead Source', 'smart-lead-crm' ); ?></label>
				<select id="slcrm-qa-source">
					<option value="manual"><?php esc_html_e( 'Manual', 'smart-lead-crm' ); ?></option>
					<?php foreach ( $helper->get_lead_sources() as $k => $l ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="slcrm-modal-footer">
			<button class="slcrm-btn slcrm-btn-outline slcrm-modal-close" type="button"><?php esc_html_e( 'Cancel', 'smart-lead-crm' ); ?></button>
			<button class="slcrm-btn slcrm-btn-primary" id="slcrm-qa-save" type="button"><?php esc_html_e( 'Add Lead', 'smart-lead-crm' ); ?></button>
		</div>
	</div>
</div>
