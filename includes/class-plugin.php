<?php
/**
 * Main plugin coordinator.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	const LEGACY_PLUGIN          = 'saeidbakhsh-element-font-manager/saeidbakhsh-element-font-manager.php';
	const LEGACY_FONTS_OPTION    = 'sefm_fonts';
	const LEGACY_SETTINGS_OPTION = 'sefm_settings';

	/** @var Plugin|null */
	private static $instance = null;

	/** @var Font_Repository */
	private $fonts;

	/** @var Element_Registry */
	private $elements;

	/** @var CSS_Generator */
	private $css;

	/** @var CSS_Cache */
	private $css_cache;

	/** @return void */
	public static function boot() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
	}

	/**
	 * Create defaults and migrate renamed-plugin options without overwriting data.
	 * Network activation migrates each existing site independently.
	 *
	 * @param bool $network_wide Whether activated network-wide.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$offset = 0;
			$batch  = 100;
			do {
				$site_ids = get_sites(
					array(
						'fields' => 'ids',
						'number' => $batch,
						'offset' => $offset,
					)
				);

				foreach ( $site_ids as $site_id ) {
					switch_to_blog( (int) $site_id );
					self::activate_site();
					restore_current_blog();
				}

				$offset += $batch;
			} while ( count( $site_ids ) === $batch );

			return;
		}

		self::activate_site();
	}

	/** @return void */
	private static function activate_site() {
		if ( false === get_option( Font_Repository::FONTS_OPTION, false ) ) {
			$legacy_fonts = get_option( self::LEGACY_FONTS_OPTION, array() );
			add_option( Font_Repository::FONTS_OPTION, is_array( $legacy_fonts ) ? $legacy_fonts : array(), '', false );
		}

		if ( false === get_option( Font_Repository::SETTINGS_OPTION, false ) ) {
			$legacy_settings = get_option( self::LEGACY_SETTINGS_OPTION, Font_Repository::default_settings() );
			add_option( Font_Repository::SETTINGS_OPTION, is_array( $legacy_settings ) ? $legacy_settings : Font_Repository::default_settings(), '', false );
		}

		self::migrate_registry_assignments();
	}


	/**
	 * Preserve saved typography when WordPress-specific selectors move out of
	 * generic HTML groups. Run only on activation or a compiled-version upgrade,
	 * not on every frontend request. The transformation remains idempotent.
	 *
	 * @return void
	 */
	public static function migrate_registry_assignments() {
		$settings = get_option( Font_Repository::SETTINGS_OPTION, array() );
		if ( ! is_array( $settings ) || empty( $settings['assignments'] ) || ! is_array( $settings['assignments'] ) ) {
			return;
		}

		$assignments = $settings['assignments'];
		$changed     = false;

		$changed = self::split_assignment(
			$assignments,
			'nav',
			'wp_navigation',
			array( 'nav', 'nav a' ),
			array( '.menu', '.menu a', '.wp-block-navigation', '.wp-block-navigation-item__content', '.wp-block-navigation-item__label' )
		) || $changed;

		$changed = self::split_assignment(
			$assignments,
			'buttons',
			'wp_buttons',
			array( 'button', 'input[type="button"]', 'input[type="submit"]', 'input[type="reset"]' ),
			array( '.wp-element-button', '.wp-block-button__link' )
		) || $changed;

		$changed = self::split_assignment(
			$assignments,
			'aside',
			'widgets',
			array( 'aside' ),
			array( '.widget' ),
			true
		) || $changed;

		if ( $changed ) {
			$settings['assignments'] = $assignments;
			update_option( Font_Repository::SETTINGS_OPTION, $settings, false );
		}
	}

	/**
	 * Split selectors from one existing assignment into a new destination rule.
	 * Existing destination rules always win, so an administrator's more specific
	 * WordPress setting is never overwritten by the compatibility migration.
	 *
	 * @param array<string,mixed> $assignments Assignment map passed by reference.
	 * @param string $source_key Existing rule key.
	 * @param string $target_key New/existing destination rule key.
	 * @param array<int,string> $source_selectors Selectors retained by source.
	 * @param array<int,string> $target_selectors Selectors moved to destination.
	 * @param bool $limit_new_target Whether a selector-less legacy rule should only enable moved selectors.
	 * @return bool Whether the assignment map changed.
	 */
	private static function split_assignment( array &$assignments, $source_key, $target_key, array $source_selectors, array $target_selectors, $limit_new_target = false ) {
		if ( empty( $assignments[ $source_key ] ) || ! is_array( $assignments[ $source_key ] ) ) {
			return false;
		}

		$changed = false;
		$source  = $assignments[ $source_key ];
		$has_selector_state = isset( $source['selectors'] ) && is_array( $source['selectors'] );
		$selected           = $has_selector_state
			? array_values(
				array_unique(
					array_filter(
						array_map(
							static function ( $value ) {
								return is_scalar( $value ) ? (string) $value : '';
							},
							$source['selectors']
						),
						'strlen'
					)
				)
			)
			: array_merge( $source_selectors, $target_selectors );

		if ( $has_selector_state ) {
			$retained = array_values( array_intersect( $source_selectors, $selected ) );
			if ( $retained !== $source['selectors'] ) {
				$assignments[ $source_key ]['selectors'] = $retained;
				$changed = true;
			}
		}

		if ( ! isset( $assignments[ $target_key ] ) ) {
			$target = $source;
			$moved  = array_values( array_intersect( $target_selectors, $selected ) );
			if ( $has_selector_state || $limit_new_target ) {
				$target['selectors'] = $moved;
			}
			$assignments[ $target_key ] = $target;
			$changed = true;
		}

		return $changed;
	}

	/**
	 * Wire small, independent services together.
	 */
	private function __construct() {
		$wordpress_fonts = new WordPress_Font_Library();
		$wordpress_fonts->register_hooks();
		$this->fonts     = new Font_Repository( $wordpress_fonts );
		$this->elements  = new Element_Registry();
		$this->css       = new CSS_Generator( $this->fonts, $this->elements );
		$this->css_cache = new CSS_Cache( $this->css );

		// Includes options.php, admin-post.php and admin AJAX; frontend visits
		// do not need the settings validator, mutation handlers or screen code.
		if ( is_admin() ) {
			require_once SBTY_PATH . 'includes/class-settings.php';
			require_once SBTY_PATH . 'includes/class-font-handler.php';
			require_once SBTY_PATH . 'includes/class-admin.php';

			$settings = new Settings( $this->fonts, $this->elements );
			$handler  = new Font_Handler( $this->fonts );
			$admin    = new Admin( $this->fonts, $this->elements, $this->css, $wordpress_fonts );
			$settings->register_hooks();
			$handler->register_hooks();
			$admin->register_hooks();
		}
		$this->css_cache->register_hooks();
		Cache_Compatibility::register_hooks();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_css' ), 99 );
	}


	/**
	 * Enqueue versioned static CSS, or its compiled inline fallback when unavailable.
	 *
	 * @return void
	 */
	public function enqueue_frontend_css() {
		$output        = $this->css_cache->output();
		$cached_url    = $output['url'];
		$runtime_rules = $output['runtime'];

		if ( '' === $cached_url && '' === $output['css'] && empty( $runtime_rules ) ) {
			return;
		}

		if ( '' !== $cached_url ) {
			// Content hash is in the filename, so stripping query strings remains safe.
			wp_enqueue_style( 'sbty-typography', CDN_Compatibility::asset_url( $cached_url ), array(), SBTY_VERSION );
		} elseif ( '' !== $output['css'] ) {
			wp_register_style( 'sbty-typography', false, array(), SBTY_VERSION );
			wp_enqueue_style( 'sbty-typography' );
			// Prevent a filtered CSS string from closing the inline style element.
			wp_add_inline_style( 'sbty-typography', str_replace( '<', '\\3c ', $output['css'] ) );
		}

		if ( ! empty( $runtime_rules ) ) {
			wp_enqueue_script( 'sbty-font-enforcer', SBTY_URL . 'assets/frontend.js', array(), SBTY_VERSION, true );
			wp_localize_script(
				'sbty-font-enforcer',
				'sbtyFontRuntime',
				array( 'rules' => $runtime_rules )
			);
		}
	}
}
