<?php
/**
 * Typography administration view.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

/** @var Admin $this */
$sbty_groups  = $this->elements->groups();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only section navigation; no state is changed.
$sbty_section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : 'global';
$sbty_section = isset( $sbty_groups[ $sbty_section ] ) ? $sbty_section : 'global';
$sbty_current = $sbty_groups[ $sbty_section ];
$sbty_settings = $this->fonts->settings();
?>
<section class="sefm-panel">
	<div class="sefm-panel-head">
		<div>
			<p class="sefm-index">01</p>
			<h2><?php esc_html_e( 'Element typography', 'saeidbakhsh-typography-manager' ); ?></h2>
		</div>
		<p><?php esc_html_e( 'Entire site values have the highest priority inside the enabled *, Icon and SVG targets. Other rules can control targets you leave disabled.', 'saeidbakhsh-typography-manager' ); ?></p>
	</div>


	<div class="sefm-subtabs-bar">
		<nav class="sefm-subtabs" aria-label="<?php esc_attr_e( 'Element groups', 'saeidbakhsh-typography-manager' ); ?>">
			<?php foreach ( $sbty_groups as $sbty_key => $sbty_group ) : ?>
				<a class="<?php echo esc_attr( $sbty_key === $sbty_section ? 'is-active' : '' ); ?>" href="<?php echo esc_url( $this->page_url( 'typography', $sbty_key ) ); ?>"<?php if ( $sbty_key === $sbty_section ) : ?> aria-current="page"<?php endif; ?>><?php echo esc_html( $sbty_group['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php $this->render_element_search(); ?>
	</div>

	<form method="post" action="options.php" class="sefm-form" data-sbty-settings-form>
		<?php settings_fields( 'sbty_settings_group' ); ?>
		<?php
		$sbty_rule_columns = array( array(), array() );
		$sbty_rule_order   = 0;
		foreach ( $sbty_current['elements'] as $sbty_key => $sbty_element ) {
			$sbty_rule_columns[ $sbty_rule_order % 2 ][] = array(
				'key'     => $sbty_key,
				'element' => $sbty_element,
				'order'   => $sbty_rule_order,
			);
			++$sbty_rule_order;
		}
		?>
		<div class="sefm-rule-grid">
			<?php foreach ( $sbty_rule_columns as $sbty_column ) : ?>
				<div class="sefm-rule-column">
					<?php foreach ( $sbty_column as $sbty_rule ) : ?>
						<?php
						$sbty_key     = $sbty_rule['key'];
						$sbty_element = $sbty_rule['element'];
						$sbty_value   = isset( $sbty_settings['assignments'][ $sbty_key ] ) ? $sbty_settings['assignments'][ $sbty_key ] : array();
						?>
						<article id="sbty-element-<?php echo esc_attr( $sbty_key ); ?>" class="sefm-rule-card" data-sefm-element-key="<?php echo esc_attr( $sbty_key ); ?>" style="--sefm-rule-order: <?php echo esc_attr( (string) $sbty_rule['order'] ); ?>;">
							<div class="sefm-rule-title">
								<div>
									<h3><?php echo esc_html( $sbty_element['label'] ); ?></h3>
									<p><?php echo esc_html( $sbty_element['description'] ); ?></p>
								</div>
								<?php $this->fields()->render_selector_toggles( $sbty_key, $sbty_element, $sbty_value ); ?>
							</div>
							<?php $this->fields()->render_assignment_fields( 'sbty_settings[assignments][' . $sbty_key . ']', $sbty_value ); ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="sefm-sticky-save sefm-local-save">
			<span><?php echo esc_html( $sbty_current['label'] ); ?></span>
			<?php submit_button( __( 'Save changes', 'saeidbakhsh-typography-manager' ), 'primary', 'submit', false ); ?>
		</div>
	</form>
</section>
<?php
