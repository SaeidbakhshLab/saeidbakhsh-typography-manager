<?php
/**
 * Capability- and nonce-protected plugin font actions.
 *
 * Local font installation is intentionally delegated to WordPress Font Library.
 * This class only registers administrator-supplied remote HTTPS font URLs and
 * performs plugin-owned settings mutations.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class Font_Handler {
	/** @var Font_Repository */
	private $fonts;

	/** @param Font_Repository $fonts Font repository. */
	public function __construct( Font_Repository $fonts ) {
		$this->fonts = $fonts;
	}

	/** @return void */
	public function register_hooks() {
		add_action( 'admin_post_sbty_register_remote_font', array( $this, 'register_remote_font' ) );
		add_action( 'admin_post_sbty_delete_font', array( $this, 'delete_font' ) );
		add_action( 'admin_post_sbty_reset_settings', array( $this, 'reset_settings' ) );
	}

	/**
	 * Register a direct HTTPS font-file URL without downloading or proxying it.
	 *
	 * @return void
	 */
	public function register_remote_font() {
		$this->require_capability();
		check_admin_referer( 'sbty_register_remote_font' );

		$name    = isset( $_POST['sbty_font_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sbty_font_name'] ) ) : '';
		$url     = isset( $_POST['sbty_font_url'] ) ? $this->sanitize_remote_url( esc_url_raw( wp_unslash( $_POST['sbty_font_url'] ), array( 'https' ) ) ) : '';
		$weight  = isset( $_POST['sbty_font_weight'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['sbty_font_weight'] ) ) ) : '400';
		$style   = isset( $_POST['sbty_font_style'] ) ? sanitize_key( wp_unslash( $_POST['sbty_font_style'] ) ) : 'normal';
		$display = isset( $_POST['sbty_font_display'] ) ? sanitize_key( wp_unslash( $_POST['sbty_font_display'] ) ) : 'swap';

		if ( '' === $name || $this->text_length( $name ) > 100 ) {
			$this->redirect( 'invalid_name' );
		}

		if ( '' === $url ) {
			$this->redirect( 'invalid_remote_url' );
		}

		$format = $this->format_from_url( $url );
		if ( '' === $format ) {
			$this->redirect( 'invalid_remote_format' );
		}

		if ( ! $this->valid_weight( $weight ) ) {
			$this->redirect( 'invalid_weight' );
		}

		if ( ! in_array( $style, array( 'normal', 'italic', 'oblique' ), true ) ) {
			$style = 'normal';
		}

		if ( ! in_array( $display, array( 'auto', 'block', 'swap', 'fallback', 'optional' ), true ) ) {
			$display = 'swap';
		}

		$font = array(
			'id'         => wp_generate_uuid4(),
			'name'       => $name,
			'source'     => 'remote',
			'url'        => $url,
			'format'     => $format,
			'weight'     => $weight,
			'style'      => $style,
			'display'    => $display,
			'created_at' => time(),
		);

		$result = $this->fonts->add_remote( $font );
		if ( is_wp_error( $result ) ) {
			$this->redirect( $result->get_error_code() );
		}

		$this->redirect( 'remote_registered' );
	}

	/**
	 * Delete one plugin-managed remote or legacy record.
	 * WordPress Font Library records are read-only here and must be managed by Core.
	 *
	 * @return void
	 */
	public function delete_font() {
		$this->require_capability();
		check_admin_referer( 'sbty_delete_font' );

		$id = isset( $_POST['font_id'] ) ? sanitize_text_field( wp_unslash( $_POST['font_id'] ) ) : '';

		if ( '' === $id || ! $this->fonts->remove( $id ) ) {
			$this->redirect( 'font_not_found' );
		}

		$this->redirect( 'font_deleted' );
	}

	/**
	 * Reset mappings but retain WordPress Font Library and registered remote sources.
	 *
	 * @return void
	 */
	public function reset_settings() {
		$this->require_capability();
		check_admin_referer( 'sbty_reset_settings' );

		$defaults = Font_Repository::default_settings();
		$current  = get_option( Font_Repository::SETTINGS_OPTION, null );

		if ( $current !== $defaults && ! update_option( Font_Repository::SETTINGS_OPTION, $defaults, false ) ) {
			$this->redirect( 'settings_reset_failed', 'tools' );
		}

		$this->redirect( 'settings_reset', 'tools' );
	}

	/**
	 * Validate and normalize a browser-loaded remote font URL.
	 *
	 * Only HTTPS URLs are accepted. The plugin deliberately does not fetch this URL,
	 * which means this path has no server-side SSRF or file-write surface.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	private function sanitize_remote_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url || strlen( $url ) > 2048 ) {
			return '';
		}

		$url = preg_replace( '/#.*$/', '', $url );
		$url = esc_url_raw( $url, array( 'https' ) );
		if ( '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if (
			! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ||
			'https' !== strtolower( (string) $parts['scheme'] ) ||
			isset( $parts['user'] ) || isset( $parts['pass'] )
		) {
			return '';
		}

		return $url;
	}

	/**
	 * Derive a CSS format token from the URL path only.
	 * Query strings are allowed for versioned/signed CDN URLs.
	 *
	 * @param string $url Font URL.
	 * @return string
	 */
	private function format_from_url( $url ) {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$ext  = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		$formats = array(
			'woff2' => 'woff2',
			'woff'  => 'woff',
			'ttf'   => 'truetype',
			'otf'   => 'opentype',
		);

		return isset( $formats[ $ext ] ) ? $formats[ $ext ] : '';
	}

	/**
	 * Accept static weights and the CSS range form used by variable fonts.
	 *
	 * @param string $weight Weight declaration.
	 * @return bool
	 */
	private function valid_weight( $weight ) {
		if ( in_array( $weight, array( 'normal', 'bold' ), true ) ) {
			return true;
		}

		if ( ! preg_match( '/^([1-9]\d{0,2}|1000)(?:\s+([1-9]\d{0,2}|1000))?$/', $weight, $matches ) ) {
			return false;
		}

		return ! isset( $matches[2] ) || (int) $matches[1] <= (int) $matches[2];
	}

	/** @param string $value Text. @return int */
	private function text_length( $value ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}

	/**
	 * Enforce the administrator capability required by mutation handlers.
	 * Nonces are verified directly inside each handler before request data is read.
	 *
	 * @return void
	 */
	private function require_capability() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these fonts.', 'saeidbakhsh-typography-manager' ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Redirect to the plugin page with a short notice code.
	 *
	 * @param string $notice Notice code.
	 * @param string $tab Destination tab.
	 * @return void
	 */
	private function redirect( $notice, $tab = 'fonts' ) {
		$url = add_query_arg(
			array(
				'page'        => 'saeidbakhsh-typography-manager',
				'tab'         => sanitize_key( $tab ),
				'sbty_notice' => sanitize_key( $notice ),
			),
			admin_url( 'themes.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}
