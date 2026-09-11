<?php
/**
 * Administration hooks and lazy screen coordination.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class Admin {
	/** @var Font_Repository */
	private $fonts;

	/** @var Element_Registry */
	private $elements;

	/** @var CSS_Generator */
	private $css;

	/** @var WordPress_Font_Library */
	private $wordpress_fonts;

	/** @var Admin_Fields|null */
	private $fields = null;

	/**
	 * @param Font_Repository $fonts Font repository.
	 * @param Element_Registry $elements Element registry.
	 * @param CSS_Generator $css CSS generator.
	 * @param WordPress_Font_Library $wordpress_fonts Core Font Library bridge.
	 */
	public function __construct( Font_Repository $fonts, Element_Registry $elements, CSS_Generator $css, WordPress_Font_Library $wordpress_fonts ) {
		$this->fonts           = $fonts;
		$this->elements        = $elements;
		$this->css             = $css;
		$this->wordpress_fonts = $wordpress_fonts;
	}

	/** @return void */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SBTY_FILE ), array( $this, 'action_links' ) );
	}


	/**
	 * Suggest privacy-policy text when a browser-loaded remote font is configured.
	 *
	 * @return void
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$has_remote = false;
		foreach ( $this->fonts->managed() as $font ) {
			if ( isset( $font['source'] ) && in_array( $font['source'], array( 'remote', 'legacy-remote' ), true ) ) {
				$has_remote = true;
				break;
			}
		}

		if ( ! $has_remote ) {
			return;
		}

		$content = '<p>' . esc_html__( 'A registered remote URL is loaded directly by visitors’ browsers. The remote host can receive normal web-request metadata such as IP address, referrer behavior, and user agent.', 'saeidbakhsh-typography-manager' ) . '</p>';
		wp_add_privacy_policy_content( 'Saeidbakhsh Typography Manager', wp_kses_post( $content ) );
	}


	/** @return void */
	public function register_menu() {
		global $submenu;

		$position = null;
		if ( isset( $submenu['themes.php'] ) && is_array( $submenu['themes.php'] ) ) {
			foreach ( array_values( $submenu['themes.php'] ) as $index => $item ) {
				if ( isset( $item[2] ) && 'font-library.php' === $item[2] ) {
					$position = $index + 1;
					break;
				}
			}
		}

		add_theme_page(
			__( 'Saeidbakhsh Typography Manager', 'saeidbakhsh-typography-manager' ),
			__( 'Typography Manager', 'saeidbakhsh-typography-manager' ),
			'manage_options',
			'saeidbakhsh-typography-manager',
			array( $this, 'render_page' ),
			$position
		);
	}

	/**
	 * Load scoped assets on this screen only.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'appearance_page_saeidbakhsh-typography-manager' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'sbty-admin', SBTY_URL . 'assets/admin.css', array(), SBTY_VERSION );
		wp_enqueue_script( 'sbty-admin-workspace', SBTY_URL . 'assets/admin-workspace.js', array(), SBTY_VERSION, true );
		wp_enqueue_script( 'sbty-admin', SBTY_URL . 'assets/admin.js', array( 'sbty-admin-workspace' ), SBTY_VERSION, true );
		wp_localize_script(
			'sbty-admin-workspace',
			'sbtyAdmin',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'saveNonce'          => wp_create_nonce( 'sbty_save_all' ),
				'unsaved'            => __( 'You have unsaved changes.', 'saeidbakhsh-typography-manager' ),
				'saving'             => __( 'Saving all changes…', 'saeidbakhsh-typography-manager' ),
				'saved'              => __( 'All changes saved.', 'saeidbakhsh-typography-manager' ),
				'saveError'          => __( 'Changes could not be saved. Your edits are still here; please try again.', 'saeidbakhsh-typography-manager' ),
				'invalidField'       => __( 'Check the highlighted field before saving.', 'saeidbakhsh-typography-manager' ),
				'emptyCss'           => __( 'No CSS is generated until you assign a font or style.', 'saeidbakhsh-typography-manager' ),
				'deleteConfirm'      => __( 'Remove this remote or legacy font record from the plugin? WordPress Font Library fonts are managed by WordPress.', 'saeidbakhsh-typography-manager' ),
				'resetConfirm'       => __( 'Reset every element assignment and custom rule?', 'saeidbakhsh-typography-manager' ),
				'copied'             => __( 'CSS copied.', 'saeidbakhsh-typography-manager' ),
				'navError'           => __( 'The section could not be loaded. Your edits are still here; please try again.', 'saeidbakhsh-typography-manager' ),
				'fontSearch'         => __( 'Search fonts…', 'saeidbakhsh-typography-manager' ),
				'noFontsFound'       => __( 'No matching fonts found.', 'saeidbakhsh-typography-manager' ),
				'elementSearch'      => __( 'Search elements or selectors…', 'saeidbakhsh-typography-manager' ),
				'noElementsFound'    => __( 'No matching elements found.', 'saeidbakhsh-typography-manager' ),
				/* translators: %d: Number of active typography settings. */
				'activeOne'          => __( '%d active', 'saeidbakhsh-typography-manager' ),
				/* translators: %d: Number of active typography settings. */
				'activeMany'         => __( '%d active', 'saeidbakhsh-typography-manager' ),
				'elementSearchIndex' => $this->element_search_index(),
			)
		);
	}

	/**
	 * Add a direct settings link on Plugins screen.
	 *
	 * @param array<int,string> $links Existing links.
	 * @return array<int,string>
	 */
	public function action_links( $links ) {
		$url = add_query_arg( 'page', 'saeidbakhsh-typography-manager', admin_url( 'themes.php' ) );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'saeidbakhsh-typography-manager' ) . '</a>' );

		return $links;
	}

	/** @return void */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'saeidbakhsh-typography-manager' ), '', array( 'response' => 403 ) );
		}

		$sbty_tabs = array(
			'typography' => __( 'Element typography', 'saeidbakhsh-typography-manager' ),
			'fonts'      => __( 'Font library', 'saeidbakhsh-typography-manager' ),
			'tools'      => __( 'Custom & tools', 'saeidbakhsh-typography-manager' ),
			'about'      => __( 'Guide', 'saeidbakhsh-typography-manager' ),
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab navigation; no state is changed.
		$sbty_tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'typography';
		$sbty_tab  = isset( $sbty_tabs[ $sbty_tab ] ) ? $sbty_tab : 'typography';
		$this->fields = null;
		require __DIR__ . '/admin/views/page.php';
	}

	/** @return void */
	private function render_element_search() {
		?>
		<div class="sefm-element-search sefm-subtabs-search">
			<label class="screen-reader-text" for="sefm-element-search-input"><?php esc_html_e( 'Search elements or selectors', 'saeidbakhsh-typography-manager' ); ?></label>
			<input id="sefm-element-search-input" type="search" autocomplete="off" spellcheck="false" placeholder="<?php esc_attr_e( 'Search elements or selectors…', 'saeidbakhsh-typography-manager' ); ?>" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="sefm-element-search-results">
			<div id="sefm-element-search-results" class="sefm-element-search-results" role="listbox" hidden></div>
		</div>
		<?php
	}

	/** @return void */
	private function render_typography() {
		require __DIR__ . '/admin/views/typography.php';
	}

	/** @return void */
	private function render_fonts() {
		require __DIR__ . '/admin/views/fonts.php';
	}

	/** @return void */
	private function render_tools() {
		require __DIR__ . '/admin/views/tools.php';
	}

	/** @return void */
	private function render_about() {
		require __DIR__ . '/admin/views/about.php';
	}

	/** @return void */
	private function render_notices() {
		require __DIR__ . '/admin/views/notices.php';
	}

	/**
	 * Render a plugin-owned message without WordPress's movable .notice class.
	 *
	 * @param string $type Notice type.
	 * @param string $message Notice text.
	 * @return void
	 */
	private function render_notice( $type, $message ) {
		$type = in_array( $type, array( 'success', 'error', 'warning', 'info' ), true ) ? $type : 'error';
		printf(
			'<div class="sefm-notice sefm-notice--%1$s" role="%2$s"><p>%3$s</p></div>',
			esc_attr( $type ),
			esc_attr( 'error' === $type ? 'alert' : 'status' ),
			esc_html( $message )
		);
	}

	/** @return string */
	private function page_url( $tab, $section = '' ) {
		$args = array(
			'page' => 'saeidbakhsh-typography-manager',
			'tab'  => sanitize_key( $tab ),
		);
		if ( '' !== $section ) {
			$args['section'] = sanitize_key( $section );
		}

		return add_query_arg( $args, admin_url( 'themes.php' ) );
	}


	/**
	 * Build a compact client-side search index for all registered element targets.
	 *
	 * @return array<int,array<string,string>>
	 */
	private function element_search_index() {
		$index = array();
		foreach ( $this->elements->groups() as $group_key => $group ) {
			if ( empty( $group['elements'] ) || ! is_array( $group['elements'] ) ) {
				continue;
			}

			$group_label = isset( $group['label'] ) ? (string) $group['label'] : '';
			foreach ( $group['elements'] as $key => $element ) {
				if ( empty( $element['label'] ) || empty( $element['selector'] ) ) {
					continue;
				}

				$index[] = array(
					'key'         => sanitize_key( $key ),
					'label'       => (string) $element['label'],
					'group'       => $group_label,
					'description' => isset( $element['description'] ) ? (string) $element['description'] : '',
					'selector'    => (string) $element['selector'],
					'url'         => $this->page_url( 'typography', $group_key ) . '#sbty-element-' . rawurlencode( sanitize_key( $key ) ),
				);
			}
		}

		return $index;
	}


	/** @return string */
	private function font_monogram( $name ) {
		if ( function_exists( 'mb_substr' ) ) {
			return mb_strtoupper( mb_substr( $name, 0, 2 ) );
		}

		return strtoupper( substr( $name, 0, 2 ) );
	}

	/** @return string */
	private function short_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$path = wp_parse_url( $url, PHP_URL_PATH );
		$name = $path ? basename( $path ) : '';

		return $host . ( $name ? '/' . $name : '' );
	}

	/** @return string */
	private function source_label( array $font ) {
		if ( isset( $font['source'] ) && 'wordpress' === $font['source'] ) {
			return __( 'WordPress', 'saeidbakhsh-typography-manager' );
		}
		if ( isset( $font['source'] ) && 'remote' === $font['source'] ) {
			return __( 'Remote URL', 'saeidbakhsh-typography-manager' );
		}
		if ( isset( $font['source'] ) && 'legacy-remote' === $font['source'] ) {
			return __( 'Legacy remote', 'saeidbakhsh-typography-manager' );
		}
		return __( 'Legacy local', 'saeidbakhsh-typography-manager' );
	}

	/** @return Admin_Fields */
	private function fields() {
		if ( null === $this->fields ) {
			require_once __DIR__ . '/class-admin-fields.php';
			$this->fields = new Admin_Fields( $this->fonts );
		}

		return $this->fields;
	}
}
