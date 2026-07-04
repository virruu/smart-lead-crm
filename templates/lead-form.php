<?php
/**
 * Lead form template (frontend shortcode).
 *
 * @package SmartLeadCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="slcrm-lead-form-wrap">
	<form class="slcrm-lead-form" id="slcrm-lead-form">
		<?php if ( ! empty( $atts['title'] ) ) : ?>
			<h3 class="slcrm-form-title"><?php echo esc_html( $atts['title'] ); ?></h3>
		<?php endif; ?>

		<div class="slcrm-form-field">
			<label for="slcrm-phone"><?php esc_html_e( 'Phone Number', 'smart-lead-crm' ); ?> <span class="required">*</span></label>
			<input type="tel" id="slcrm-phone" name="phone" required placeholder="<?php esc_attr_e( 'Your phone number', 'smart-lead-crm' ); ?>" />
		</div>

		<?php if ( 'yes' === $atts['show_name'] ) : ?>
		<div class="slcrm-form-field">
			<label for="slcrm-name"><?php esc_html_e( 'Name', 'smart-lead-crm' ); ?></label>
			<input type="text" id="slcrm-name" name="name" placeholder="<?php esc_attr_e( 'Your name', 'smart-lead-crm' ); ?>" />
		</div>
		<?php endif; ?>

		<?php if ( 'yes' === $atts['show_email'] ) : ?>
		<div class="slcrm-form-field">
			<label for="slcrm-email"><?php esc_html_e( 'Email', 'smart-lead-crm' ); ?></label>
			<input type="email" id="slcrm-email" name="email" placeholder="<?php esc_attr_e( 'Your email', 'smart-lead-crm' ); ?>" />
		</div>
		<?php endif; ?>

		<div class="slcrm-form-field">
			<button type="submit" class="slcrm-submit-btn"><?php echo esc_html( $atts['button_text'] ); ?></button>
		</div>

		<div class="slcrm-form-message" id="slcrm-form-message" style="display:none;"></div>
	</form>
</div>
