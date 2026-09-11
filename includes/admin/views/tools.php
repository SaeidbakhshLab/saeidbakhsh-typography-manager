<?php
/**
 * Tools administration view.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

/** @var Admin $this */
$sbty_settings = $this->fonts->settings();
$sbty_rules    = is_array( $sbty_settings['custom_rules'] ) ? $sbty_settings['custom_rules'] : array();
if ( empty( $sbty_rules ) ) {
	$sbty_rules[] = array();
}
?>
<section class="sefm-panel">
	<div class="sefm-panel-head">
		<div><p class="sefm-index">04</p><h2><?php esc_html_e( 'Custom selectors', 'saeidbakhsh-typography-manager' ); ?></h2></div>
		<p><?php esc_html_e( 'Use this only for theme or plugin classes that are not covered by the element groups.', 'saeidbakhsh-typography-manager' ); ?></p>
	</div>
	<form method="post" action="options.php" class="sefm-form" data-sbty-settings-form>
		<?php settings_fields( 'sbty_settings_group' ); ?>
		<input type="hidden" name="sbty_settings[custom_rules_present]" value="1">
		<div id="sefm-custom-rules" class="sefm-custom-rules">
			<?php foreach ( $sbty_rules as $sbty_index => $sbty_rule ) : ?>
				<?php $this->fields()->render_custom_rule( $sbty_index, $sbty_rule ); ?>
			<?php endforeach; ?>
		</div>
		<template id="sefm-custom-template"><?php $this->fields()->render_custom_rule( '__INDEX__', array() ); ?></template>
		<button type="button" class="button sefm-add-rule" id="sefm-add-rule"><?php esc_html_e( 'Add selector', 'saeidbakhsh-typography-manager' ); ?></button>


		<fieldset class="sefm-priority">
			<legend><?php esc_html_e( 'Font priority', 'saeidbakhsh-typography-manager' ); ?></legend>
			<p><?php esc_html_e( 'Choose how strongly plugin typography should override themes, builders and other plugins.', 'saeidbakhsh-typography-manager' ); ?></p>
			<div class="sefm-priority-grid">
				<?php $sbty_mode = isset( $sbty_settings['priority_mode'] ) ? $sbty_settings['priority_mode'] : 'strong'; ?>
				<label>
					<input type="radio" name="sbty_settings[priority_mode]" value="standard" <?php checked( $sbty_mode, 'standard' ); ?>>
					<span><strong><?php esc_html_e( 'Standard', 'saeidbakhsh-typography-manager' ); ?></strong><small><?php esc_html_e( 'Uses the normal CSS cascade. Best when the theme does not force fonts.', 'saeidbakhsh-typography-manager' ); ?></small></span>
				</label>
				<label>
					<input type="radio" name="sbty_settings[priority_mode]" value="strong" <?php checked( $sbty_mode, 'strong' ); ?>>
					<span><strong><?php esc_html_e( 'Strong', 'saeidbakhsh-typography-manager' ); ?></strong><small><?php esc_html_e( 'Default. Adds !important and overrides theme rules plus normal inline styles without a DOM observer.', 'saeidbakhsh-typography-manager' ); ?></small></span>
				</label>
				<label>
					<input type="radio" name="sbty_settings[priority_mode]" value="maximum" <?php checked( $sbty_mode, 'maximum' ); ?>>
					<span><strong><?php esc_html_e( 'Maximum', 'saeidbakhsh-typography-manager' ); ?></strong><small><?php esc_html_e( 'Aggressive. Also enforces saved rules against inline !important and dynamic page-builder changes.', 'saeidbakhsh-typography-manager' ); ?></small></span>
				</label>
			</div>
		</fieldset>

		<div class="sefm-local-save"><?php submit_button( __( 'Save changes', 'saeidbakhsh-typography-manager' ), 'primary', 'submit', false ); ?></div>
	</form>
</section>

<div class="sefm-tools-grid">
	<section class="sefm-panel sefm-code-panel">
		<div class="sefm-panel-head"><div><p class="sefm-index">05</p><h2><?php esc_html_e( 'Generated CSS', 'saeidbakhsh-typography-manager' ); ?></h2></div></div>
		<?php $sbty_generated = $this->css->generate(); ?>
		<pre id="sefm-css-preview" dir="ltr"><code><?php echo '' !== $sbty_generated ? esc_html( $sbty_generated ) : esc_html__( 'No CSS is generated until you assign a font or style.', 'saeidbakhsh-typography-manager' ); ?></code></pre>
		<button type="button" class="button" id="sefm-copy-css" <?php disabled( '' === $sbty_generated ); ?>><?php esc_html_e( 'Copy CSS', 'saeidbakhsh-typography-manager' ); ?></button>
	</section>

	<section class="sefm-panel sefm-danger-panel">
		<div class="sefm-panel-head"><div><p class="sefm-index">06</p><h2><?php esc_html_e( 'Reset assignments', 'saeidbakhsh-typography-manager' ); ?></h2></div></div>
		<p><?php esc_html_e( 'Clears all element assignments and custom selectors. WordPress Font Library and registered remote fonts remain available.', 'saeidbakhsh-typography-manager' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sbty_reset_settings">
			<?php wp_nonce_field( 'sbty_reset_settings' ); ?>
			<button type="submit" class="button sefm-reset" data-sefm-confirm="reset"><?php esc_html_e( 'Reset typography settings', 'saeidbakhsh-typography-manager' ); ?></button>
		</form>
	</section>
</div>
<?php
