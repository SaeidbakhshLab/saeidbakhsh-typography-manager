<?php
/**
 * Persistent remote-font records and typography settings access.
 *
 * WordPress Font Library is the authoritative source for local installed fonts.
 * The sbty_fonts option is retained only for direct remote URLs and legacy data
 * created by older plugin versions.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class Font_Repository {
	const FONTS_OPTION    = 'sbty_fonts';
	const SETTINGS_OPTION = 'sbty_settings';

	/** @var WordPress_Font_Library|null */
	private $wordpress_fonts;

	/**
	 * @param WordPress_Font_Library|null $wordpress_fonts Core Font Library bridge.
	 */
	public function __construct( ?WordPress_Font_Library $wordpress_fonts = null ) {
		$this->wordpress_fonts = $wordpress_fonts;
	}

	/**
	 * Default configuration for new/reset installations.
	 *
	 * Strong is the conservative default: it handles most theme conflicts without
	 * a page-wide MutationObserver. Existing saved priority choices are preserved.
	 *
	 * @return array<string,mixed>
	 */
	public static function default_settings() {
		return array(
			'assignments'   => array(),
			'custom_rules'  => array(),
			'priority_mode' => 'strong',
		);
	}

	/**
	 * Return normalized plugin-managed remote and legacy records.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function managed() {
		$stored = get_option( self::FONTS_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}

		$fonts = array();
		foreach ( $stored as $record ) {
			$font = $this->normalize_managed( $record );
			if ( null !== $font ) {
				$fonts[] = $font;
			}
		}

		return array_values( $fonts );
	}

	/** @return array<int,array<string,mixed>> */
	public function wordpress() {
		return null !== $this->wordpress_fonts ? $this->wordpress_fonts->all() : array();
	}

	/** @return void */
	public function refresh() {
		if ( null !== $this->wordpress_fonts ) {
			$this->wordpress_fonts->reset();
		}
	}

	/** @return array<int,array<string,mixed>> */
	public function all() {
		return array_merge( $this->managed(), $this->wordpress() );
	}

	/**
	 * Find one font by immutable ID.
	 *
	 * @param string $id Font ID.
	 * @return array<string,mixed>|null
	 */
	public function find( $id ) {
		$id    = (string) $id;
		$fonts = 0 === strpos( $id, 'wordpress-' ) ? $this->wordpress() : $this->managed();
		foreach ( $fonts as $font ) {
			if ( isset( $font['id'] ) && hash_equals( (string) $font['id'], $id ) ) {
				return $font;
			}
		}

		return null;
	}

	/**
	 * Persist one validated direct remote URL record.
	 *
	 * @param array<string,mixed> $font Validated font record.
	 * @return true|\WP_Error
	 */
	public function add_remote( array $font ) {
		$font = $this->normalize_remote( $font );
		if ( null === $font ) {
			return new \WP_Error( 'invalid_remote_record' );
		}

		$fonts = $this->managed();
		foreach ( $fonts as $existing ) {
			if (
				in_array( $existing['source'], array( 'remote', 'legacy-remote' ), true ) &&
				$existing['url'] === $font['url'] &&
				0 === strcasecmp( $existing['name'], $font['name'] ) &&
				$existing['weight'] === $font['weight'] &&
				$existing['style'] === $font['style']
			) {
				return new \WP_Error( 'remote_duplicate' );
			}
		}

		$fonts[] = $font;
		if ( ! update_option( self::FONTS_OPTION, array_values( $fonts ), false ) ) {
			return new \WP_Error( 'font_store_failed' );
		}

		return true;
	}

	/**
	 * Remove a plugin-managed record. Core Font Library IDs are never stored here.
	 *
	 * @param string $id Font ID.
	 * @return bool
	 */
	public function remove( $id ) {
		$id             = (string) $id;
		$fonts          = $this->managed();
		$removed        = false;
		$kept           = array();

		foreach ( $fonts as $font ) {
			if ( isset( $font['id'] ) && hash_equals( (string) $font['id'], $id ) ) {
				$removed = true;
				continue;
			}
			$kept[] = $font;
		}

		if ( ! $removed ) {
			return false;
		}

		$original_settings = $this->settings();
		$clean_settings    = $this->without_assignments_for_font( $original_settings, $id );
		$settings_changed  = $clean_settings !== $original_settings;

		// Update dependent mappings first. If the record write then fails, restore the
		// previous mappings so the two plugin-owned options remain logically aligned.
		if ( $settings_changed && ! update_option( self::SETTINGS_OPTION, $clean_settings, false ) ) {
			return false;
		}

		if ( ! update_option( self::FONTS_OPTION, array_values( $kept ), false ) ) {
			if ( $settings_changed ) {
				update_option( self::SETTINGS_OPTION, $original_settings, false );
			}
			return false;
		}

		return true;
	}

	/**
	 * Get settings merged with future-safe defaults.
	 *
	 * @return array<string,mixed>
	 */
	public function settings() {
		$settings = get_option( self::SETTINGS_OPTION, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings = wp_parse_args( $settings, self::default_settings() );
		if ( ! is_array( $settings['assignments'] ) ) {
			$settings['assignments'] = array();
		}
		if ( ! is_array( $settings['custom_rules'] ) ) {
			$settings['custom_rules'] = array();
		}
		if ( ! in_array( $settings['priority_mode'], array( 'standard', 'strong', 'maximum' ), true ) ) {
			$settings['priority_mode'] = 'strong';
		}

		return $settings;
	}

	/**
	 * Normalize records written by current and previous versions.
	 *
	 * @param mixed $record Stored value.
	 * @return array<string,mixed>|null
	 */
	private function normalize_managed( $record ) {
		if ( ! is_array( $record ) ) {
			return null;
		}

		$source = isset( $record['source'] ) ? sanitize_key( $record['source'] ) : '';
		if ( 'remote' === $source && empty( $record['attachment_id'] ) ) {
			$remote = $this->normalize_remote( $record );
			return null !== $remote ? $remote : $this->normalize_legacy_remote( $record );
		}
		if ( 'legacy-remote' === $source && empty( $record['attachment_id'] ) ) {
			return $this->normalize_legacy_remote( $record );
		}

		// Preserve valid local records created by older releases so upgrades do not
		// break live assignments. Normalized legacy-local records are accepted too,
		// because add/remove operations persist the normalized repository shape.
		if ( ! in_array( $source, array( 'upload', 'remote', 'legacy-local' ), true ) || empty( $record['attachment_id'] ) ) {
			return null;
		}

		$id     = isset( $record['id'] ) ? sanitize_text_field( (string) $record['id'] ) : '';
		$name   = isset( $record['name'] ) ? sanitize_text_field( (string) $record['name'] ) : '';
		$url    = isset( $record['url'] ) ? esc_url_raw( (string) $record['url'], array( 'http', 'https' ) ) : '';
		$format = isset( $record['format'] ) ? $this->normalize_format( $record['format'] ) : '';
		$weight = isset( $record['weight'] ) ? trim( sanitize_text_field( (string) $record['weight'] ) ) : '400';
		$style  = isset( $record['style'] ) ? sanitize_key( $record['style'] ) : 'normal';
		$display = isset( $record['display'] ) ? sanitize_key( $record['display'] ) : 'swap';

		if ( '' === $id || '' === $name || '' === $url || ! in_array( $format, array( 'woff2', 'woff', 'truetype', 'opentype' ), true ) ) {
			return null;
		}

		if ( ! $this->valid_weight( $weight ) ) {
			$weight = '400';
		}
		if ( ! in_array( $style, array( 'normal', 'italic', 'oblique' ), true ) ) {
			$style = 'normal';
		}
		if ( ! in_array( $display, array( 'auto', 'block', 'swap', 'fallback', 'optional' ), true ) ) {
			$display = 'swap';
		}

		return array(
			'id'            => $id,
			'name'          => $name,
			'url'           => $url,
			'format'        => $format,
			'weight'        => $weight,
			'style'         => $style,
			'display'       => $display,
			'source'        => 'legacy-local',
			'attachment_id' => absint( $record['attachment_id'] ),
			'readonly'      => false,
			'legacy'        => true,
		);
	}

	/**
	 * Normalize a remote record as a defense-in-depth storage boundary.
	 *
	 * @param array<string,mixed> $record Record.
	 * @return array<string,mixed>|null
	 */
	private function normalize_remote( array $record ) {
		$id     = isset( $record['id'] ) ? sanitize_text_field( (string) $record['id'] ) : '';
		$name   = isset( $record['name'] ) ? sanitize_text_field( (string) $record['name'] ) : '';
		$url    = isset( $record['url'] ) ? $this->normalize_url( $record['url'], array( 'https' ) ) : '';
		$format = isset( $record['format'] ) ? $this->normalize_format( $record['format'] ) : '';
		$weight = isset( $record['weight'] ) ? trim( sanitize_text_field( (string) $record['weight'] ) ) : '400';
		$style  = isset( $record['style'] ) ? sanitize_key( $record['style'] ) : 'normal';
		$display = isset( $record['display'] ) ? sanitize_key( $record['display'] ) : 'swap';
		$expected_format = $this->format_from_url( $url );

		if (
			'' === $id || '' === $name || '' === $url ||
			'' === $expected_format || $format !== $expected_format ||
			! $this->valid_weight( $weight )
		) {
			return null;
		}

		if ( ! in_array( $style, array( 'normal', 'italic', 'oblique' ), true ) ) {
			$style = 'normal';
		}
		if ( ! in_array( $display, array( 'auto', 'block', 'swap', 'fallback', 'optional' ), true ) ) {
			$display = 'swap';
		}

		return array(
			'id'            => $id,
			'name'          => $name,
			'url'           => $url,
			'format'        => $format,
			'weight'        => $weight,
			'style'         => $style,
			'display'       => $display,
			'source'        => 'remote',
			'attachment_id' => 0,
			'readonly'      => false,
			'created_at'    => isset( $record['created_at'] ) ? absint( $record['created_at'] ) : 0,
		);
	}


	/**
	 * Preserve older HTTPS external records during upgrades. Legacy HTTP records are
	 * intentionally ignored so the plugin never emits an insecure remote font request.
	 *
	 * @param array<string,mixed> $record Legacy record.
	 * @return array<string,mixed>|null
	 */
	private function normalize_legacy_remote( array $record ) {
		$id     = isset( $record['id'] ) ? sanitize_text_field( (string) $record['id'] ) : '';
		$name   = isset( $record['name'] ) ? sanitize_text_field( (string) $record['name'] ) : '';
		$url    = isset( $record['url'] ) ? $this->normalize_url( $record['url'], array( 'https' ) ) : '';
		$format = isset( $record['format'] ) ? $this->normalize_format( $record['format'] ) : '';
		$weight = isset( $record['weight'] ) ? trim( sanitize_text_field( (string) $record['weight'] ) ) : '400';
		$style  = isset( $record['style'] ) ? sanitize_key( $record['style'] ) : 'normal';
		$display = isset( $record['display'] ) ? sanitize_key( $record['display'] ) : 'swap';
		$expected_format = $this->format_from_url( $url );

		if (
			'' === $id || '' === $name || '' === $url ||
			'' === $expected_format || $format !== $expected_format
		) {
			return null;
		}

		if ( ! $this->valid_weight( $weight ) ) {
			$weight = '400';
		}
		if ( ! in_array( $style, array( 'normal', 'italic', 'oblique' ), true ) ) {
			$style = 'normal';
		}
		if ( ! in_array( $display, array( 'auto', 'block', 'swap', 'fallback', 'optional' ), true ) ) {
			$display = 'swap';
		}

		return array(
			'id'            => $id,
			'name'          => $name,
			'url'           => $url,
			'format'        => $format,
			'weight'        => $weight,
			'style'         => $style,
			'display'       => $display,
			'source'        => 'legacy-remote',
			'attachment_id' => 0,
			'readonly'      => false,
			'legacy'        => true,
		);
	}

	/**
	 * Normalize a browser-loaded URL without performing DNS or HTTP validation.
	 *
	 * These URLs are never requested by PHP, so HTTP-API SSRF validation would add
	 * unnecessary DNS work on reads. Component validation plus esc_url_raw() is the
	 * appropriate boundary for a URL that is emitted only into escaped front-end CSS.
	 *
	 * @param mixed $url Raw URL.
	 * @param array<int,string> $schemes Allowed schemes.
	 * @return string
	 */
	private function normalize_url( $url, array $schemes ) {
		$url = trim( (string) $url );
		if ( '' === $url || strlen( $url ) > 2048 ) {
			return '';
		}

		$url = preg_replace( '/#.*$/', '', $url );
		$url = esc_url_raw( $url, $schemes );
		if ( '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if (
			! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ||
			isset( $parts['user'] ) || isset( $parts['pass'] ) ||
			! in_array( strtolower( (string) $parts['scheme'] ), $schemes, true )
		) {
			return '';
		}

		return $url;
	}

	/** @param mixed $format Stored format. @return string */
	private function normalize_format( $format ) {
		$format = sanitize_key( (string) $format );
		$aliases = array(
			'woff2'    => 'woff2',
			'woff'     => 'woff',
			'ttf'      => 'truetype',
			'truetype' => 'truetype',
			'otf'      => 'opentype',
			'opentype' => 'opentype',
		);
		return isset( $aliases[ $format ] ) ? $aliases[ $format ] : '';
	}

	/** @param string $url Font URL. @return string */
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

	/** @param string $weight Weight. @return bool */
	private function valid_weight( $weight ) {
		if ( in_array( $weight, array( 'normal', 'bold' ), true ) ) {
			return true;
		}
		if ( ! preg_match( '/^([1-9]\d{0,2}|1000)(?:\s+([1-9]\d{0,2}|1000))?$/', $weight, $matches ) ) {
			return false;
		}
		return ! isset( $matches[2] ) || (int) $matches[1] <= (int) $matches[2];
	}

	/**
	 * Return settings with references to one managed font cleared.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param string $id Font ID.
	 * @return array<string,mixed>
	 */
	private function without_assignments_for_font( array $settings, $id ) {
		$needle    = 'font:' . $id;
		$font_keys = array( 'font', 'font_desktop', 'font_mobile', 'font_tablet' );

		foreach ( $settings['assignments'] as $key => $assignment ) {
			foreach ( $font_keys as $font_key ) {
				if ( isset( $assignment[ $font_key ] ) && $needle === $assignment[ $font_key ] ) {
					$settings['assignments'][ $key ][ $font_key ] = 'inherit';
				}
			}
		}

		foreach ( $settings['custom_rules'] as $key => $rule ) {
			foreach ( $font_keys as $font_key ) {
				if ( isset( $rule[ $font_key ] ) && $needle === $rule[ $font_key ] ) {
					$settings['custom_rules'][ $key ][ $font_key ] = 'inherit';
				}
			}
		}

		return $settings;
	}
}
