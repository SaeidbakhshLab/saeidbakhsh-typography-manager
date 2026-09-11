<?php
/**
 * Fonts administration view.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

/** @var Admin $this */
$sbty_managed       = $this->fonts->managed();
$sbty_wordpress     = $this->fonts->wordpress();
$sbty_all_fonts     = array_merge( $sbty_managed, $sbty_wordpress );
$sbty_library_url   = $this->wordpress_fonts->management_url();
$sbty_library_ready = $this->wordpress_fonts->is_available();
?>
<div class="sefm-split">
	<section class="sefm-panel">
		<div class="sefm-panel-head">
			<div><p class="sefm-index">02</p><h2><?php esc_html_e( 'WordPress Font Library', 'saeidbakhsh-typography-manager' ); ?></h2></div>
			<p><?php esc_html_e( 'Local font files are installed and managed only by WordPress Core.', 'saeidbakhsh-typography-manager' ); ?></p>
		</div>
		<div class="sefm-source-grid">
			<div class="sefm-source-card">
				<span class="sefm-source-mark">WP</span>
				<h3><?php esc_html_e( 'Install or upload local fonts', 'saeidbakhsh-typography-manager' ); ?></h3>
				<p><?php esc_html_e( 'Use the official WordPress Font Library to upload WOFF2, WOFF, TTF or OTF files, or install fonts from a registered font collection. This plugin does not process local uploads.', 'saeidbakhsh-typography-manager' ); ?></p>
				<?php if ( '' !== $sbty_library_url ) : ?>
					<p class="sefm-button-row"><a class="button button-primary" href="<?php echo esc_url( $sbty_library_url ); ?>"><?php esc_html_e( 'Open WordPress Font Library', 'saeidbakhsh-typography-manager' ); ?></a></p>
				<?php elseif ( $sbty_library_ready ) : ?>
					<p class="sefm-inline-warning"><?php esc_html_e( 'WordPress Font Library storage is available, but this WordPress/theme combination does not expose a Core font-management screen. WordPress 7.0 or newer provides Appearance > Fonts for both classic and block themes.', 'saeidbakhsh-typography-manager' ); ?></p>
				<?php else : ?>
					<p class="sefm-inline-warning"><?php esc_html_e( 'WordPress Font Library is unavailable on this installation. Update WordPress to a supported version.', 'saeidbakhsh-typography-manager' ); ?></p>
				<?php endif; ?>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sefm-source-card">
				<input type="hidden" name="action" value="sbty_register_remote_font">
				<?php wp_nonce_field( 'sbty_register_remote_font' ); ?>
				<span class="sefm-source-mark">URL</span>
				<h3><?php esc_html_e( 'Register a remote font URL', 'saeidbakhsh-typography-manager' ); ?></h3>
				<label class="sefm-field">
					<span><?php esc_html_e( 'Direct HTTPS font URL', 'saeidbakhsh-typography-manager' ); ?></span>
					<input type="url" name="sbty_font_url" placeholder="https://cdn.example.com/font.woff2?v=1" required dir="ltr" inputmode="url">
					<small><?php esc_html_e( 'The plugin stores only this URL and never downloads, proxies, or writes the remote file. The CDN must allow cross-origin font requests from your site.', 'saeidbakhsh-typography-manager' ); ?></small>
				</label>
				<?php $this->fields()->render_remote_font_meta_fields(); ?>
				<?php submit_button( __( 'Register remote URL', 'saeidbakhsh-typography-manager' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
	</section>

	<?php $sbty_font_count = count( $sbty_all_fonts ); ?>
	<section class="sefm-panel">
		<div class="sefm-panel-head sefm-panel-head--available-fonts<?php echo $sbty_font_count > 0 ? ' has-search' : ''; ?>">
			<div class="sefm-panel-title">
				<p class="sefm-index">03</p>
				<h2><?php esc_html_e( 'Available fonts', 'saeidbakhsh-typography-manager' ); ?></h2>
				<?php /* translators: %d: Number of available font faces. */ ?>
				<p class="sefm-panel-count"><?php echo esc_html( sprintf( _n( '%d font face available', '%d font faces available', $sbty_font_count, 'saeidbakhsh-typography-manager' ), $sbty_font_count ) ); ?></p>
			</div>
			<?php if ( $sbty_font_count > 0 ) : ?>
				<div class="sefm-available-font-search">
					<label for="sefm-available-font-search-input"><?php esc_html_e( 'Search available fonts', 'saeidbakhsh-typography-manager' ); ?></label>
					<div class="sefm-available-font-search-control">
						<input type="search" id="sefm-available-font-search-input" autocomplete="off" spellcheck="false" placeholder="<?php esc_attr_e( 'Search fonts…', 'saeidbakhsh-typography-manager' ); ?>" aria-controls="sefm-available-font-list">
						<button type="button" class="sefm-available-font-clear" data-sefm-font-search-clear aria-label="<?php esc_attr_e( 'Clear font search', 'saeidbakhsh-typography-manager' ); ?>" aria-controls="sefm-available-font-list" hidden><span aria-hidden="true">&times;</span></button>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( empty( $sbty_all_fonts ) ) : ?>
			<div class="sefm-empty"><strong><?php esc_html_e( 'No font faces are available yet.', 'saeidbakhsh-typography-manager' ); ?></strong><span><?php esc_html_e( 'Install a local font with WordPress Font Library or register a direct remote URL above.', 'saeidbakhsh-typography-manager' ); ?></span></div>
		<?php else : ?>
			<div id="sefm-available-font-list" class="sefm-font-list<?php echo $sbty_font_count > 5 ? ' sefm-font-list--scroll' : ''; ?>" data-sefm-available-font-list>
				<?php foreach ( $sbty_all_fonts as $sbty_font ) : ?>
					<article class="sefm-font-row" data-sefm-font-row data-search="<?php echo esc_attr( $sbty_font['name'] ); ?>">
						<div class="sefm-font-monogram"><?php echo esc_html( $this->font_monogram( $sbty_font['name'] ) ); ?></div>
						<div class="sefm-font-info">
							<h3><?php echo esc_html( $sbty_font['name'] ); ?></h3>
							<p><?php echo esc_html( strtoupper( $sbty_font['format'] ) . ' / ' . $sbty_font['weight'] . ' / ' . $sbty_font['style'] ); ?></p>
							<a href="<?php echo esc_url( $sbty_font['url'] ); ?>" target="_blank" rel="noopener noreferrer" dir="ltr"><?php echo esc_html( $this->short_url( $sbty_font['url'] ) ); ?></a>
						</div>
						<span class="sefm-chip"><?php echo esc_html( $this->source_label( $sbty_font ) ); ?></span>
						<?php if ( ! empty( $sbty_font['readonly'] ) ) : ?>
							<span class="sefm-readonly"><?php esc_html_e( 'Managed by WordPress', 'saeidbakhsh-typography-manager' ); ?></span>
						<?php else : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sefm-font-action" data-sefm-confirm-form="delete">
								<input type="hidden" name="action" value="sbty_delete_font">
								<input type="hidden" name="font_id" value="<?php echo esc_attr( $sbty_font['id'] ); ?>">
								<?php wp_nonce_field( 'sbty_delete_font' ); ?>
								<button type="submit" class="sefm-delete"><?php esc_html_e( 'Remove', 'saeidbakhsh-typography-manager' ); ?></button>
							</form>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
				<p class="sefm-font-list-empty" data-sefm-font-list-empty role="status" hidden><?php esc_html_e( 'No matching fonts found.', 'saeidbakhsh-typography-manager' ); ?></p>
			</div>
		<?php endif; ?>
	</section>
</div>
<?php
