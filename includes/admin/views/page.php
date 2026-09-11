<?php
/**
 * Page administration view.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

/** @var Admin $this */
?>
<div class="wrap sefm-wrap" dir="<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>">
	<header class="sefm-header">
		<div class="sefm-heading">
			<p class="sefm-kicker"><?php esc_html_e( 'Element typography', 'saeidbakhsh-typography-manager' ); ?></p>
			<h1><?php esc_html_e( 'Saeidbakhsh Typography Manager', 'saeidbakhsh-typography-manager' ); ?></h1>
			<p class="sefm-lead"><?php esc_html_e( 'Assign precise typography to each HTML, WordPress or WooCommerce element.', 'saeidbakhsh-typography-manager' ); ?></p>
			<p class="sefm-developer"><?php esc_html_e( 'Developer:', 'saeidbakhsh-typography-manager' ); ?> <a href="<?php echo esc_url( 'https://profiles.wordpress.org/saeidbakhsh' ); ?>" target="_blank" rel="noopener noreferrer">Ahmadreza Saeidbakhsh</a></p>
		</div>
		<div class="sefm-version" aria-label="<?php esc_attr_e( 'Plugin version', 'saeidbakhsh-typography-manager' ); ?>">
			<span>ST</span>
			<strong><?php echo esc_html( SBTY_VERSION ); ?></strong>
		</div>
	</header>

	<nav class="sefm-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'saeidbakhsh-typography-manager' ); ?>">
		<?php foreach ( $sbty_tabs as $sbty_key => $sbty_label ) : ?>
			<a class="<?php echo esc_attr( $sbty_key === $sbty_tab ? 'is-active' : '' ); ?>" href="<?php echo esc_url( $this->page_url( $sbty_key ) ); ?>"<?php if ( $sbty_key === $sbty_tab ) : ?> aria-current="page"<?php endif; ?>>
				<span><?php echo esc_html( $sbty_label ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="sefm-notices"><?php $this->render_notices(); ?></div>

	<main class="sefm-main">
		<?php
		switch ( $sbty_tab ) {
			case 'fonts':
				$this->render_fonts();
				break;
			case 'tools':
				$this->render_tools();
				break;
			case 'about':
				$this->render_about();
				break;
			default:
				$this->render_typography();
				break;
		}
		?>
	</main>
	<div class="sefm-workspace-save" hidden>
		<span id="sbty-save-status" role="status" aria-live="polite"></span>
		<button type="button" class="button button-primary" id="sbty-save-all" disabled><?php esc_html_e( 'Save all changes', 'saeidbakhsh-typography-manager' ); ?></button>
	</div>

</div>
<?php
