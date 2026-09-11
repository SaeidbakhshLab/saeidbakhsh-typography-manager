<?php
/**
 * About administration view.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

/** @var Admin $this */
$sbty_groups = $this->elements->groups();
$sbty_count  = 0;
foreach ( $sbty_groups as $sbty_group ) {
	$sbty_count += count( $sbty_group['elements'] );
}
?>
<section class="sefm-panel">
	<div class="sefm-panel-head">
		<div><p class="sefm-index">07</p><h2><?php esc_html_e( 'Quick guide', 'saeidbakhsh-typography-manager' ); ?></h2></div>
		<?php /* translators: %d: Number of ready-made element targets. */ ?>
		<p><?php echo esc_html( sprintf( __( '%d ready-made element targets are available.', 'saeidbakhsh-typography-manager' ), $sbty_count ) ); ?></p>
	</div>
	<div class="sefm-steps">
		<article><span>1</span><h3><?php esc_html_e( 'Add fonts', 'saeidbakhsh-typography-manager' ); ?></h3><p><?php esc_html_e( 'Install local fonts with WordPress Font Library, or register a direct HTTPS CDN font URL.', 'saeidbakhsh-typography-manager' ); ?></p></article>
		<article><span>2</span><h3><?php esc_html_e( 'Assign', 'saeidbakhsh-typography-manager' ); ?></h3><p><?php esc_html_e( 'Adjust typography across element groups, then select Save all changes.', 'saeidbakhsh-typography-manager' ); ?></p></article>
		<article><span>3</span><h3><?php esc_html_e( 'Refine', 'saeidbakhsh-typography-manager' ); ?></h3><p><?php esc_html_e( 'Add custom theme selectors only when a ready-made target is not specific enough.', 'saeidbakhsh-typography-manager' ); ?></p></article>
	</div>

	<div class="sefm-notes-grid">
		<div><h3><?php esc_html_e( 'Cascade behavior', 'saeidbakhsh-typography-manager' ); ?></h3><p><?php esc_html_e( 'Each value selected for Entire site has plugin-level priority. Values left unchanged can still be customized for headings, forms, WordPress or WooCommerce elements, or custom selectors.', 'saeidbakhsh-typography-manager' ); ?></p></div>
		<div><h3><?php esc_html_e( 'Performance', 'saeidbakhsh-typography-manager' ); ?></h3><p><?php esc_html_e( 'Only fonts used by a saved rule are declared. The small enforcement script loads only in Maximum mode with an active rule.', 'saeidbakhsh-typography-manager' ); ?></p></div>
			<div><h3><?php esc_html_e( 'Remote font privacy', 'saeidbakhsh-typography-manager' ); ?></h3><p><?php esc_html_e( 'A registered remote URL is loaded directly by visitors’ browsers. The remote host can receive normal web-request metadata such as IP address, referrer behavior, and user agent.', 'saeidbakhsh-typography-manager' ); ?></p></div>
		<div><h3><?php esc_html_e( 'Ownership boundary', 'saeidbakhsh-typography-manager' ); ?></h3><p><?php esc_html_e( 'Local font files belong to WordPress Font Library. Removing or uninstalling this plugin does not delete Core-managed font files.', 'saeidbakhsh-typography-manager' ); ?></p></div>
	</div>
</section>
<?php
