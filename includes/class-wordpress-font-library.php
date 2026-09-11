<?php
/**
 * Read-only bridge to the WordPress Font Library.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class WordPress_Font_Library {
	/** @var array<int,array<string,mixed>>|null */
	private $fonts = null;

	/** Reset in-memory data after a site switch or font mutation. @return void */
	public function register_hooks() {
		add_action( 'switch_blog', array( $this, 'reset' ), 10, 0 );
		add_action( 'save_post_wp_font_face', array( $this, 'reset' ), 1, 0 );
		add_action( 'save_post_wp_font_family', array( $this, 'reset' ), 1, 0 );
		add_action( 'deleted_post', array( $this, 'reset' ), 1, 0 );
	}

	/** @return void */
	public function reset() {
		$this->fonts = null;
	}

	/**
	 * Whether Core's font-face storage is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return post_type_exists( 'wp_font_face' );
	}

	/**
	 * Return the best official WordPress UI for managing local fonts.
	 *
	 * WordPress 7.0+ ships wp-admin/font-library.php. On WordPress 6.5-6.9
	 * block themes, font management lives inside the Site Editor Styles UI.
	 * Classic themes on those older versions have no equivalent Core screen.
	 *
	 * @return string Empty when Core has no accessible management screen.
	 */
	public function management_url() {
		if ( file_exists( ABSPATH . 'wp-admin/font-library.php' ) ) {
			return admin_url( 'font-library.php' );
		}

		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return admin_url( 'site-editor.php?path=/styles' );
		}

		return '';
	}

	/**
	 * Return published font faces registered by WordPress Core.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function all() {
		if ( null !== $this->fonts ) {
			return $this->fonts;
		}

		if ( ! $this->is_available() ) {
			$this->fonts = array();
			return $this->fonts;
		}

		$posts = get_posts(
			array(
				'post_type'              => 'wp_font_face',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => false,
			)
		);

		$fonts = array();
		foreach ( $posts as $post ) {
			$font = $this->normalize( $post );
			if ( null !== $font ) {
				$fonts[] = $font;
			}
		}

		$this->fonts = $fonts;
		return $this->fonts;
	}

	/**
	 * Normalize one Core font-face post into the plugin record shape.
	 *
	 * @param \WP_Post $post Font face post.
	 * @return array<string,mixed>|null
	 */
	private function normalize( $post ) {
		$settings = json_decode( $post->post_content, true );
		if ( ! is_array( $settings ) ) {
			return null;
		}

		$family  = isset( $settings['fontFamily'] ) ? $settings['fontFamily'] : $post->post_title;
		$name    = $this->family_name( $family );
		$sources = isset( $settings['src'] ) ? $this->sources( $settings['src'] ) : array();

		if ( '' === $name || empty( $sources ) ) {
			return null;
		}

		$url    = $sources[0]['url'];
		$format = $sources[0]['format'];

		$weight = isset( $settings['fontWeight'] ) ? trim( sanitize_text_field( $settings['fontWeight'] ) ) : '400';
		if ( ! $this->valid_weight( $weight ) ) {
			$weight = '400';
		}

		$style = isset( $settings['fontStyle'] ) ? sanitize_key( $settings['fontStyle'] ) : 'normal';
		if ( ! in_array( $style, array( 'normal', 'italic', 'oblique' ), true ) ) {
			$style = 'normal';
		}

		$display = isset( $settings['fontDisplay'] ) ? sanitize_key( $settings['fontDisplay'] ) : 'fallback';
		if ( ! in_array( $display, array( 'auto', 'block', 'swap', 'fallback', 'optional' ), true ) ) {
			$display = 'fallback';
		}

		return array(
			'id'            => 'wordpress-' . absint( $post->ID ),
			'name'          => $name,
			'url'           => $url,
			'format'        => $format,
			'sources'       => $sources,
			'weight'        => $weight,
			'style'         => $style,
			'display'       => $display,
			'source'        => 'wordpress',
			'attachment_id' => 0,
			'readonly'      => true,
		);
	}


	/**
	 * Match Core's font-family normalization for theme.json/Font Library data.
	 * A Font Library family may be stored quoted or as the first item in a CSS
	 * fallback list; assignments must use the canonical family name only.
	 *
	 * @param mixed $family Raw fontFamily value.
	 * @return string
	 */
	private function family_name( $family ) {
		if ( ! is_scalar( $family ) ) {
			return '';
		}

		$family = sanitize_text_field( (string) $family );
		if ( false !== strpos( $family, ',' ) ) {
			$family = explode( ',', $family, 2 )[0];
		}

		return trim( trim( $family ), "\"'" );
	}

	/**
	 * Find the first usable HTTP(S) source URL from the Core schema.
	 *
	 * @param mixed $source Font source.
	 * @return string
	 */
	private function sources( $source ) {
		$candidates = is_array( $source ) ? $source : array( $source );
		$sources    = array();

		foreach ( $candidates as $item ) {
			$url = '';
			if ( is_string( $item ) ) {
				$url = $item;
			} elseif ( is_array( $item ) && isset( $item['url'] ) && is_string( $item['url'] ) ) {
				$url = $item['url'];
			}

			$url = esc_url_raw( $url, array( 'http', 'https' ) );
			if ( '' === $url ) {
				continue;
			}

			$path   = (string) wp_parse_url( $url, PHP_URL_PATH );
			$format = $this->format( strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) );
			if ( '' === $format ) {
				continue;
			}

			$sources[] = array(
				'url'    => $url,
				'format' => $format,
			);
		}

		return $sources;
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

	/** @param string $extension Extension. @return string */
	private function format( $extension ) {
		$formats = array(
			'woff2' => 'woff2',
			'woff'  => 'woff',
			'ttf'   => 'truetype',
			'otf'   => 'opentype',
		);

		return isset( $formats[ $extension ] ) ? $formats[ $extension ] : '';
	}
}
