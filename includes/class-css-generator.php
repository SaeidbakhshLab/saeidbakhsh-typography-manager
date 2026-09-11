<?php
/**
 * Deterministic front-end CSS generation.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class CSS_Generator {
	const DESKTOP_MIN_WIDTH = 1025;
	const TABLET_MIN_WIDTH  = 768;
	const TABLET_MAX_WIDTH  = 1024;
	const MOBILE_MAX_WIDTH  = 767;

	/** @var Font_Repository */
	private $fonts;

	/** @var Element_Registry */
	private $elements;

	/**
	 * @param Font_Repository $fonts Font repository.
	 * @param Element_Registry $elements Element registry.
	 */
	public function __construct( Font_Repository $fonts, Element_Registry $elements ) {
		$this->fonts    = $fonts;
		$this->elements = $elements;
	}

	/** @return void */
	public function refresh_fonts() {
		$this->fonts->refresh();
	}

	/**
	 * Supported local system stacks.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function system_fonts() {
		$fonts = array(
			'system' => array(
				'label' => __( 'System UI', 'saeidbakhsh-typography-manager' ),
				'css'   => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
			),
			'tahoma' => array(
				'label' => 'Tahoma',
				'css'   => 'Tahoma, Arial, sans-serif',
			),
			'arial' => array(
				'label' => 'Arial',
				'css'   => 'Arial, Helvetica, sans-serif',
			),
			'georgia' => array(
				'label' => 'Georgia',
				'css'   => 'Georgia, "Times New Roman", serif',
			),
			'times' => array(
				'label' => 'Times New Roman',
				'css'   => '"Times New Roman", Times, serif',
			),
			'monospace' => array(
				'label' => __( 'Monospace', 'saeidbakhsh-typography-manager' ),
				'css'   => 'ui-monospace, SFMono-Regular, Consolas, "Liberation Mono", monospace',
			),
		);

		/**
		 * Filter built-in font stacks.
		 *
		 * @param array<string,array<string,string>> $fonts System fonts.
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Public filter retains the former Saeidbakhsh Element Font Manager prefix for backward compatibility.
		return apply_filters( 'sefm_system_fonts', $fonts );
	}

	/**
	 * Build all front-end CSS from validated options.
	 *
	 * @return string
	 */
	public function generate() {
		$settings        = $this->fonts->settings();
		$definitions     = $this->elements->all();
		$used            = $this->used_font_ids( $settings );
		$font_map        = empty( $used ) ? array() : $this->font_map( $used );
		$site_assignment = isset( $settings['assignments']['site'] ) && is_array( $settings['assignments']['site'] ) ? $settings['assignments']['site'] : array();
		$site_targets    = Element_Registry::active_site_targets( $site_assignment );
		$site_selector   = isset( $definitions['site']['selector'] ) ? $this->selected_selector( $definitions['site']['selector'], $site_assignment, 'site' ) : '';
		$site_map        = '' !== $site_selector ? $this->declaration_map( $site_assignment, $font_map ) : array();
		$viewports       = array( 'desktop', 'tablet', 'mobile' );
		$site_layer_maps = array();
		foreach ( $viewports as $viewport ) {
			$site_layer_maps[ $viewport ] = '' !== $site_selector ? $this->layer_declaration_map( $site_assignment, $font_map, $viewport ) : array();
		}

		$full_site     = $this->site_has_full_coverage( $site_targets );
		$site_reserved = $full_site ? $site_map : array();
		$site_layer_reserved = array();
		foreach ( $viewports as $viewport ) {
			$site_layer_reserved[ $viewport ] = $full_site
				? array_replace( $site_map, $site_layer_maps[ $viewport ] )
				: array();
		}

		$lines       = array();
		$layer_lines = array( 'desktop' => array(), 'tablet' => array(), 'mobile' => array() );
		$declared    = array();

		foreach ( $used as $id ) {
			if ( ! isset( $font_map[ $id ] ) ) {
				continue;
			}

			$selected = $font_map[ $id ];
			foreach ( $font_map as $face_id => $font ) {
				if ( isset( $declared[ $face_id ] ) || empty( $font['name'] ) || empty( $selected['name'] ) || $font['name'] !== $selected['name'] ) {
					continue;
				}
				if ( isset( $font['source'], $selected['source'] ) && $font['source'] !== $selected['source'] ) {
					continue;
				}

				$face = $this->font_face( $font );
				if ( '' !== $face ) {
					$lines[] = $face;
					$declared[ $face_id ] = true;
				}
			}
		}

		$priority  = isset( $settings['priority_mode'] ) ? $settings['priority_mode'] : 'strong';
		$important = in_array( $priority, array( 'strong', 'maximum' ), true );

		foreach ( $definitions as $key => $definition ) {
			if ( 'site' === $key ) {
				continue;
			}
			if ( empty( $settings['assignments'][ $key ] ) || empty( $definition['selector'] ) ) {
				continue;
			}

			$assignment = $settings['assignments'][ $key ];
			$selector   = $this->selected_selector( $definition['selector'], $assignment, $key );
			if ( '' === $selector ) {
				continue;
			}

			$rule = $this->rule( $selector, $assignment, $font_map, $important, $site_reserved );
			if ( '' !== $rule ) {
				$lines[] = $rule;
			}

			foreach ( $viewports as $viewport ) {
				$map        = array_diff_key( $this->layer_declaration_map( $assignment, $font_map, $viewport ), $site_layer_reserved[ $viewport ] );
				$layer_rule = $this->rule_from_map( $selector, $map, $important );
				if ( '' !== $layer_rule ) {
					$layer_lines[ $viewport ][] = $layer_rule;
				}
			}
		}

		if ( ! empty( $settings['custom_rules'] ) && is_array( $settings['custom_rules'] ) ) {
			foreach ( $settings['custom_rules'] as $custom_rule ) {
				if ( empty( $custom_rule['selector'] ) ) {
					continue;
				}

				$rule = $this->rule( $custom_rule['selector'], $custom_rule, $font_map, $important, $site_reserved );
				if ( '' !== $rule ) {
					$lines[] = $rule;
				}

				foreach ( $viewports as $viewport ) {
					$map        = array_diff_key( $this->layer_declaration_map( $custom_rule, $font_map, $viewport ), $site_layer_reserved[ $viewport ] );
					$layer_rule = $this->rule_from_map( $custom_rule['selector'], $map, $important );
					if ( '' !== $layer_rule ) {
						$layer_lines[ $viewport ][] = $layer_rule;
					}
				}
			}
		}

		$has_site_layer = false;
		foreach ( $site_layer_maps as $map ) {
			if ( ! empty( $map ) ) {
				$has_site_layer = true;
				break;
			}
		}

		if ( '' !== $site_selector && ( ! empty( $site_map ) || $has_site_layer ) ) {
			$site_rule = $this->rule( $site_selector, $site_assignment, $font_map, $important );
			if ( '' !== $site_rule ) {
				$lines[] = $site_rule;
			}

			foreach ( $viewports as $viewport ) {
				$site_layer_rule = $this->rule_from_map( $site_selector, $site_layer_maps[ $viewport ], $important );
				if ( '' !== $site_layer_rule ) {
					$layer_lines[ $viewport ][] = $site_layer_rule;
				}
			}

			$pseudo_selectors = $this->site_pseudo_selectors( $site_targets );
			foreach ( $pseudo_selectors as $pseudo_selector ) {
				$pseudo_rule = $this->rule( $pseudo_selector, $site_assignment, $font_map, $important );
				if ( '' !== $pseudo_rule ) {
					$lines[] = $pseudo_rule;
				}
				foreach ( $viewports as $viewport ) {
					$pseudo_layer = $this->rule_from_map( $pseudo_selector, $site_layer_maps[ $viewport ], $important );
					if ( '' !== $pseudo_layer ) {
						$layer_lines[ $viewport ][] = $pseudo_layer;
					}
				}
			}
		}

		foreach ( $viewports as $viewport ) {
			if ( ! empty( $layer_lines[ $viewport ] ) ) {
				$lines[] = '@media ' . $this->viewport_media( $viewport ) . '{' . implode( '', $layer_lines[ $viewport ] ) . '}';
			}
		}

		$css = implode( "\n", $lines );

		/**
		 * Filter generated CSS after all built-in safety checks.
		 *
		 * @param string $css Generated CSS.
		 * @param array<string,mixed> $settings Current settings.
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Public filter retains the former Saeidbakhsh Element Font Manager prefix for backward compatibility.
		return (string) apply_filters( 'sefm_generated_css', $css, $settings );
	}

	/**
	 * Return safe declaration maps for maximum-priority DOM enforcement.
	 *
	 * The runtime is intentionally absent in standard and strong modes. Pseudo
	 * elements remain covered by generated CSS because they cannot receive an
	 * inline style declaration.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function runtime_rules() {
		$settings = $this->fonts->settings();
		if ( empty( $settings['priority_mode'] ) || 'maximum' !== $settings['priority_mode'] ) {
			return array();
		}

		$definitions     = $this->elements->all();
		$used            = $this->used_font_ids( $settings );
		$font_map        = empty( $used ) ? array() : $this->font_map( $used );
		$site_assignment = isset( $settings['assignments']['site'] ) && is_array( $settings['assignments']['site'] ) ? $settings['assignments']['site'] : array();
		$site_targets    = Element_Registry::active_site_targets( $site_assignment );
		$site_selector   = isset( $definitions['site']['selector'] ) ? $this->selected_selector( $definitions['site']['selector'], $site_assignment, 'site' ) : '';
		$site_map        = '' !== $site_selector ? $this->declaration_map( $site_assignment, $font_map ) : array();
		$viewports       = array( 'desktop', 'tablet', 'mobile' );
		$site_layer_maps = array();
		foreach ( $viewports as $viewport ) {
			$site_layer_maps[ $viewport ] = '' !== $site_selector ? $this->layer_declaration_map( $site_assignment, $font_map, $viewport ) : array();
		}

		$full_site     = $this->site_has_full_coverage( $site_targets );
		$site_reserved = $full_site ? $site_map : array();
		$site_layer_reserved = array();
		foreach ( $viewports as $viewport ) {
			$site_layer_reserved[ $viewport ] = $full_site
				? array_replace( $site_map, $site_layer_maps[ $viewport ] )
				: array();
		}

		$base_rules       = array();
		$layer_rules      = array( 'desktop' => array(), 'tablet' => array(), 'mobile' => array() );
		$site_base        = null;
		$site_layer_rules = array( 'desktop' => null, 'tablet' => null, 'mobile' => null );

		foreach ( $definitions as $key => $definition ) {
			if ( empty( $settings['assignments'][ $key ] ) || empty( $definition['selector'] ) ) {
				continue;
			}

			$assignment = $settings['assignments'][ $key ];
			$selector   = $this->selected_selector( $definition['selector'], $assignment, $key );
			if ( '' === $selector || false !== strpos( $selector, '::' ) || ! self::is_safe_selector( $selector ) ) {
				continue;
			}

			$declarations = $this->declaration_map( $assignment, $font_map );
			if ( 'site' === $key ) {
				if ( ! empty( $declarations ) ) {
					$site_base = array( 'selector' => $selector, 'declarations' => $declarations );
				}
				foreach ( $viewports as $viewport ) {
					$map = $site_layer_maps[ $viewport ];
					if ( ! empty( $map ) ) {
						$site_layer_rules[ $viewport ] = array( 'selector' => $selector, 'declarations' => $map, 'media' => $this->viewport_media( $viewport ) );
					}
				}
				continue;
			}

			$declarations = array_diff_key( $declarations, $site_reserved );
			if ( ! empty( $declarations ) ) {
				$base_rules[] = array( 'selector' => $selector, 'declarations' => $declarations );
			}

			foreach ( $viewports as $viewport ) {
				$map = array_diff_key( $this->layer_declaration_map( $assignment, $font_map, $viewport ), $site_layer_reserved[ $viewport ] );
				if ( ! empty( $map ) ) {
					$layer_rules[ $viewport ][] = array( 'selector' => $selector, 'declarations' => $map, 'media' => $this->viewport_media( $viewport ) );
				}
			}
		}

		if ( ! empty( $settings['custom_rules'] ) && is_array( $settings['custom_rules'] ) ) {
			foreach ( $settings['custom_rules'] as $rule ) {
				if ( empty( $rule['selector'] ) || false !== strpos( $rule['selector'], '::' ) || ! self::is_safe_selector( $rule['selector'] ) ) {
					continue;
				}

				$declarations = array_diff_key( $this->declaration_map( $rule, $font_map ), $site_reserved );
				if ( ! empty( $declarations ) ) {
					$base_rules[] = array( 'selector' => $rule['selector'], 'declarations' => $declarations );
				}

				foreach ( $viewports as $viewport ) {
					$map = array_diff_key( $this->layer_declaration_map( $rule, $font_map, $viewport ), $site_layer_reserved[ $viewport ] );
					if ( ! empty( $map ) ) {
						$layer_rules[ $viewport ][] = array( 'selector' => $rule['selector'], 'declarations' => $map, 'media' => $this->viewport_media( $viewport ) );
					}
				}
			}
		}

		if ( null !== $site_base ) {
			$base_rules[] = $site_base;
		}
		foreach ( $viewports as $viewport ) {
			if ( null !== $site_layer_rules[ $viewport ] ) {
				$layer_rules[ $viewport ][] = $site_layer_rules[ $viewport ];
			}
		}

		return array_merge( $base_rules, $layer_rules['desktop'], $layer_rules['tablet'], $layer_rules['mobile'] );
	}

	/**
	 * Resolve a registry selector list against its saved active targets.
	 *
	 * Settings saved before selector toggles existed default to every target.
	 * Empty saved arrays intentionally disable the complete built-in rule.
	 *
	 * @param string $selector Registered selector list.
	 * @param array<string,mixed> $assignment Saved assignment.
	 * @param string $key Element definition ID.
	 * @return string
	 */
	private function selected_selector( $selector, array $assignment, $key ) {
		if ( 'site' === $key ) {
			return $this->site_element_selector( Element_Registry::active_site_targets( $assignment ) );
		}

		$allowed = Element_Registry::selector_parts( $selector, $key );
		if ( ! array_key_exists( 'selectors', $assignment ) ) {
			return implode( ', ', $allowed );
		}

		if ( ! is_array( $assignment['selectors'] ) ) {
			return '';
		}

		$selected = array_values( array_filter( $allowed, static function ( $value ) use ( $assignment ) {
			return in_array( $value, $assignment['selectors'], true );
		} ) );

		return implode( ', ', $selected );
	}

	/**
	 * Build the concrete DOM selector for the logical Entire site targets.
	 *
	 * With all three switches enabled this intentionally returns the exact
	 * pre-2.2.6 selector so existing sites keep identical behavior. Partial
	 * coverage avoids the root declaration so excluded icon/SVG descendants do
	 * not receive the selected values through inheritance.
	 *
	 * @param array<int,string> $targets Active logical targets.
	 * @return string
	 */
	private function site_element_selector( array $targets ) {
		$targets = array_values( array_intersect( Element_Registry::site_targets(), $targets ) );
		if ( empty( $targets ) ) {
			return '';
		}

		$root      = ':root:not(#sefm-site):not(#sefm-site-priority)';
		$all       = in_array( 'all', $targets, true );
		$icons     = in_array( 'icons', $targets, true );
		$svg       = in_array( 'svg', $targets, true );
		$icon_list = implode( ',', $this->site_icon_selectors() );

		if ( $all && $icons && $svg ) {
			return $root . ', ' . $root . ' *';
		}

		if ( $all && $icons ) {
			return $root . ' *:not(:is(svg,svg *))';
		}

		if ( $all && $svg ) {
			return $root . ' :is(*:not(:is(' . $icon_list . ')),svg,svg *)';
		}

		if ( $all ) {
			return $root . ' *:not(:is(' . $icon_list . ',svg,svg *))';
		}

		if ( $icons && $svg ) {
			return $root . ' :is(' . $icon_list . ',svg,svg *)';
		}

		if ( $icons ) {
			return $root . ' :is(' . $icon_list . '):not(:is(svg,svg *))';
		}

		return $svg ? $root . ' :is(svg,svg *)' : '';
	}

	/**
	 * Return pseudo-element selectors controlled by the Entire site switches.
	 *
	 * Before/after pseudo-elements are grouped with Icon because icon fonts
	 * commonly render their glyphs there. Placeholders remain ordinary page text
	 * and therefore follow the * switch.
	 *
	 * @param array<int,string> $targets Active logical targets.
	 * @return array<int,string>
	 */
	private function site_pseudo_selectors( array $targets ) {
		$targets = array_values( array_intersect( Element_Registry::site_targets(), $targets ) );
		$root    = ':root:not(#sefm-site):not(#sefm-site-priority)';
		$all     = in_array( 'all', $targets, true );
		$icons   = in_array( 'icons', $targets, true );
		$svg     = in_array( 'svg', $targets, true );
		$result  = array();

		if ( $icons ) {
			$descendant = $svg ? $root . ' *' : $root . ' *:not(:is(svg,svg *))';
			$result[]   = $root . '::before';
			$result[]   = $descendant . '::before';
			$result[]   = $root . '::after';
			$result[]   = $descendant . '::after';
		}

		if ( $all ) {
			$result[] = $root . ' input::placeholder';
			$result[] = $root . ' textarea::placeholder';
		}

		return $result;
	}

	/**
	 * Common DOM patterns used by icon-font libraries.
	 *
	 * SVG-based icon systems are handled independently by the SVG switch. The
	 * broad "icon" class fragment covers WordPress Dashicons, Elementor icons,
	 * Material Icons and conventional icon-* families; Font Awesome and Material
	 * Symbols need their additional conventional class patterns.
	 *
	 * @return array<int,string>
	 */
	private function site_icon_selectors() {
		return array(
			'i[class]',
			'i[class] *',
			'[class*="icon"]',
			'[class*="icon"] *',
			'.fa',
			'.fas',
			'.far',
			'.fab',
			'[class^="fa-"]',
			'[class*=" fa-"]',
			'[class*="material-symbol"]',
			'[data-icon]',
			'[data-icon] *',
		);
	}

	/**
	 * Whether Entire site still covers every legacy target.
	 *
	 * Property reservation can safely suppress lower-level rules only with full
	 * coverage. With a partial scope the high-specificity site rule remains last,
	 * but excluded targets are free to keep their own typography rules.
	 *
	 * @param array<int,string> $targets Active logical targets.
	 * @return bool
	 */
	private function site_has_full_coverage( array $targets ) {
		return 3 === count( array_intersect( Element_Registry::site_targets(), array_unique( $targets ) ) );
	}

	/**
	 * Index valid font records.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function font_map( array $used_ids ) {
		$map           = array();
		$need_managed  = false;
		$need_wordpress = false;

		foreach ( $used_ids as $id ) {
			if ( 0 === strpos( (string) $id, 'wordpress-' ) ) {
				$need_wordpress = true;
			} else {
				$need_managed = true;
			}
		}

		$fonts = array();
		if ( $need_managed ) {
			$fonts = array_merge( $fonts, $this->fonts->managed() );
		}
		if ( $need_wordpress ) {
			$fonts = array_merge( $fonts, $this->fonts->wordpress() );
		}

		foreach ( $fonts as $font ) {
			if ( isset( $font['id'] ) ) {
				$map[ (string) $font['id'] ] = $font;
			}
		}

		return $map;
	}

	/**
	 * Get custom-font IDs actually referenced by rules.
	 *
	 * @param array<string,mixed> $settings Current settings.
	 * @return array<int,string>
	 */
	private function used_font_ids( array $settings ) {
		$ids         = array();
		$definitions = $this->elements->all();
		$assignments = is_array( $settings['assignments'] ) ? $settings['assignments'] : array();
		$font_keys   = array( 'font', 'font_desktop', 'font_mobile', 'font_tablet' );

		foreach ( $assignments as $key => $rule ) {
			if ( ! is_array( $rule ) || ! isset( $definitions[ $key ]['selector'] ) || '' === $this->selected_selector( $definitions[ $key ]['selector'], $rule, $key ) ) {
				continue;
			}
			foreach ( $font_keys as $font_key ) {
				if ( isset( $rule[ $font_key ] ) && 0 === strpos( (string) $rule[ $font_key ], 'font:' ) ) {
					$ids[] = substr( (string) $rule[ $font_key ], 5 );
				}
			}
		}

		$custom_rules = is_array( $settings['custom_rules'] ) ? $settings['custom_rules'] : array();
		foreach ( $custom_rules as $rule ) {
			if ( ! is_array( $rule ) || empty( $rule['selector'] ) ) {
				continue;
			}
			foreach ( $font_keys as $font_key ) {
				if ( isset( $rule[ $font_key ] ) && 0 === strpos( (string) $rule[ $font_key ], 'font:' ) ) {
					$ids[] = substr( (string) $rule[ $font_key ], 5 );
				}
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Build a validated @font-face block.
	 *
	 * @param array<string,mixed> $font Font record.
	 * @return string
	 */
	private function font_face( array $font ) {
		if ( empty( $font['name'] ) || empty( $font['url'] ) || empty( $font['format'] ) ) {
			return '';
		}

		$formats = array( 'woff2', 'woff', 'truetype', 'opentype' );
		$sources = array();
		if ( ! empty( $font['sources'] ) && is_array( $font['sources'] ) ) {
			foreach ( $font['sources'] as $source ) {
				if ( ! is_array( $source ) || empty( $source['url'] ) || empty( $source['format'] ) || ! in_array( $source['format'], $formats, true ) ) {
					continue;
				}
				$url = $this->css_url( $source['url'] );
				if ( '' !== $url ) {
					$sources[] = "url('" . $url . "') format('" . $source['format'] . "')";
				}
			}
		} else {
			$format = in_array( $font['format'], $formats, true ) ? $font['format'] : '';
			$url    = $this->css_url( $font['url'] );
			if ( '' !== $format && '' !== $url ) {
				$sources[] = "url('" . $url . "') format('" . $format . "')";
			}
		}

		if ( empty( $sources ) ) {
			return '';
		}

		$styles  = array( 'normal', 'italic', 'oblique' );
		$display = array( 'auto', 'block', 'swap', 'fallback', 'optional' );
		$weight  = isset( $font['weight'] ) ? trim( (string) $font['weight'] ) : '400';
		if ( ! $this->valid_font_face_weight( $weight ) ) {
			$weight = '400';
		}
		$style = isset( $font['style'] ) && in_array( $font['style'], $styles, true ) ? $font['style'] : 'normal';
		$swap  = isset( $font['display'] ) && in_array( $font['display'], $display, true ) ? $font['display'] : 'swap';

		return '@font-face{font-family:' . $this->css_string( $font['name'] ) . ';src:' . implode( ',', $sources ) . ';font-weight:' . $weight . ';font-style:' . $style . ';font-display:' . $swap . ';}';
	}


	/** @param string $weight Font-face weight or range. @return bool */
	private function valid_font_face_weight( $weight ) {
		if ( in_array( $weight, array( 'normal', 'bold' ), true ) ) {
			return true;
		}
		if ( ! preg_match( '/^([1-9]\d{0,2}|1000)(?:\s+([1-9]\d{0,2}|1000))?$/', $weight, $matches ) ) {
			return false;
		}
		return ! isset( $matches[2] ) || (int) $matches[1] <= (int) $matches[2];
	}

	/**
	 * Build one selector rule from whitelisted values.
	 *
	 * @param string $selector Selector.
	 * @param array<string,mixed> $assignment Typography values.
	 * @param array<string,array<string,mixed>> $font_map Fonts by ID.
	 * @param bool $important Append important flag.
	 * @param array<string,string> $reserved Declarations controlled by Entire site.
	 * @return string
	 */
	private function rule( $selector, array $assignment, array $font_map, $important, array $reserved = array() ) {
		$map = array_diff_key( $this->declaration_map( $assignment, $font_map ), $reserved );

		return $this->rule_from_map( $selector, $map, $important );
	}

	/**
	 * Build one rule from an already validated declaration map.
	 *
	 * @param string $selector Selector.
	 * @param array<string,string> $map Safe property/value map.
	 * @param bool $important Append important flag.
	 * @return string
	 */
	private function rule_from_map( $selector, array $map, $important ) {
		if ( ! self::is_safe_selector( $selector ) || empty( $map ) ) {
			return '';
		}

		// Load the selector transformer only while generating CSS, not on
		// normal visits served from the compiled typography snapshot.
		require_once __DIR__ . '/class-selector-scope.php';
		$selector = Selector_Scope::without_admin_bar( $selector );

		$declarations = array();
		$flag         = $important ? ' !important' : '';
		foreach ( $map as $property => $value ) {
			$declarations[] = $property . ':' . $value . $flag;
		}

		return $selector . '{' . implode( ';', $declarations ) . ';}';
	}

	/**
	 * Return explicit declarations for one device-specific typography layer.
	 *
	 * @param array<string,mixed> $assignment Typography values.
	 * @param array<string,array<string,mixed>> $font_map Fonts by ID.
	 * @param string $viewport desktop|tablet|mobile.
	 * @return array<string,string>
	 */
	private function layer_declaration_map( array $assignment, array $font_map, $viewport ) {
		if ( ! in_array( $viewport, array( 'desktop', 'tablet', 'mobile' ), true ) ) {
			return array();
		}

		$layer = array();
		foreach ( array( 'font', 'weight', 'style', 'transform', 'direction', 'font_size', 'letter_spacing', 'line_height', 'color' ) as $base ) {
			$key = $base . '_' . $viewport;
			$layer[ $base ] = isset( $assignment[ $key ] ) ? $assignment[ $key ] : 'inherit';
		}

		return $this->declaration_map( $layer, $font_map );
	}

	/**
	 * Return the non-overlapping media query for one responsive layer.
	 *
	 * @param string $viewport desktop|tablet|mobile.
	 * @return string
	 */
	private function viewport_media( $viewport ) {
		if ( 'desktop' === $viewport ) {
			return '(min-width: ' . self::DESKTOP_MIN_WIDTH . 'px)';
		}
		if ( 'tablet' === $viewport ) {
			return '(min-width: ' . self::TABLET_MIN_WIDTH . 'px) and (max-width: ' . self::TABLET_MAX_WIDTH . 'px)';
		}

		return '(max-width: ' . self::MOBILE_MAX_WIDTH . 'px)';
	}

	/**
	 * Convert one assignment to a property/value map containing only safe data.
	 *
	 * @param array<string,mixed> $assignment Typography values.
	 * @param array<string,array<string,mixed>> $font_map Fonts by ID.
	 * @return array<string,string>
	 */
	private function declaration_map( array $assignment, array $font_map ) {
		$declarations = array();
		$family       = isset( $assignment['font'] ) ? $this->font_family( $assignment['font'], $font_map ) : '';

		if ( '' !== $family ) {
			$declarations['font-family'] = $family;
		}

		if ( isset( $assignment['color'] ) ) {
			$color = sanitize_hex_color( (string) $assignment['color'] );
			if ( $color ) {
				$declarations['color'] = $color;
			}
		}

		$allowed = array(
			'weight'    => array( 'property' => 'font-weight', 'values' => array( 'normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900' ) ),
			'style'     => array( 'property' => 'font-style', 'values' => array( 'normal', 'italic', 'oblique' ) ),
			'transform' => array( 'property' => 'text-transform', 'values' => array( 'none', 'uppercase', 'lowercase', 'capitalize' ) ),
			'direction' => array( 'property' => 'direction', 'values' => array( 'rtl', 'ltr' ) ),
		);

		foreach ( $allowed as $key => $config ) {
			if ( isset( $assignment[ $key ] ) && in_array( (string) $assignment[ $key ], $config['values'], true ) ) {
				$declarations[ $config['property'] ] = (string) $assignment[ $key ];
			}
		}

		$measurements = array(
			'font_size'      => array( 'property' => 'font-size', 'units' => array( 'px', 'rem', 'em', '%' ), 'negative' => false ),
			'letter_spacing' => array( 'property' => 'letter-spacing', 'units' => array( 'px', 'rem', 'em' ), 'negative' => true ),
			'line_height'    => array( 'property' => 'line-height', 'units' => array( '', 'px', 'rem', 'em', '%' ), 'negative' => false ),
		);

		foreach ( $measurements as $key => $config ) {
			if ( isset( $assignment[ $key ] ) && $this->is_safe_measurement( (string) $assignment[ $key ], $config['units'], $config['negative'] ) ) {
				$declarations[ $config['property'] ] = (string) $assignment[ $key ];
			}
		}

		return $declarations;
	}

	/**
	 * Validate a stored advanced typography measurement before CSS output.
	 *
	 * @param string $value Stored CSS value.
	 * @param array<int,string> $allowed_units Allowed units; empty string permits unitless values.
	 * @param bool $allow_negative Whether negative values are valid.
	 * @return bool
	 */
	private function is_safe_measurement( $value, array $allowed_units, $allow_negative ) {
		if ( 'inherit' === $value || ! preg_match( '/^(-?(?:\d+(?:\.\d+)?|\.\d+))(px|rem|em|%)?$/D', $value, $matches ) ) {
			return false;
		}

		$number = (float) $matches[1];
		$unit   = isset( $matches[2] ) ? $matches[2] : '';

		return ( $allow_negative || $number >= 0 ) && abs( $number ) <= 100000 && in_array( $unit, $allowed_units, true );
	}

	/**
	 * Resolve a saved font selection to CSS.
	 *
	 * @param string $selection Saved selection.
	 * @param array<string,array<string,mixed>> $font_map Fonts by ID.
	 * @return string
	 */
	private function font_family( $selection, array $font_map ) {
		if ( 0 === strpos( $selection, 'system:' ) ) {
			$key     = substr( $selection, 7 );
			$systems = self::system_fonts();

			return isset( $systems[ $key ]['css'] ) ? $systems[ $key ]['css'] : '';
		}

		if ( 0 === strpos( $selection, 'font:' ) ) {
			$id = substr( $selection, 5 );
			if ( isset( $font_map[ $id ]['name'] ) ) {
				return $this->css_string( $font_map[ $id ]['name'] );
			}
		}

		return '';
	}

	/**
	 * Escape a string as a quoted CSS string using JSON escaping rules.
	 *
	 * @param string $value Text.
	 * @return string
	 */
	private function css_string( $value ) {
		$encoded = wp_json_encode( sanitize_text_field( (string) $value ) );

		return is_string( $encoded ) ? $encoded : '""';
	}

	/**
	 * Escape an HTTP(S) URL for a single-quoted CSS url().
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function css_url( $url ) {
		$url = esc_url_raw( $url, array( 'http', 'https' ) );
		if ( '' === $url ) {
			return '';
		}

		return str_replace(
			array( "'", '"', '(', ')', "\n", "\r", '\\' ),
			array( '%27', '%22', '%28', '%29', '', '', '%5C' ),
			$url
		);
	}

	/**
	 * Reject selector text capable of breaking out of a CSS rule.
	 *
	 * @param string $selector Selector.
	 * @return bool
	 */
	public static function is_safe_selector( $selector ) {
		if ( ! is_string( $selector ) || '' === trim( $selector ) || strlen( $selector ) > 250 ) {
			return false;
		}

		if ( preg_match( '/[{};@<]|\/\*|\*\/|[\x00-\x08\x0B\x0C\x0E-\x1F]/', $selector ) ) {
			return false;
		}

		$parentheses = 0;
		$brackets    = 0;
		$quote       = '';
		$escaped     = false;
		$segment     = '';
		$length      = strlen( $selector );

		for ( $index = 0; $index < $length; $index++ ) {
			$character = $selector[ $index ];
			if ( $escaped ) {
				$segment .= $character;
				$escaped = false;
				continue;
			}

			if ( '\\' === $character ) {
				$segment .= $character;
				$escaped = true;
				continue;
			}

			if ( '' !== $quote ) {
				$segment .= $character;
				if ( $character === $quote ) {
					$quote = '';
				}
				continue;
			}

			if ( '"' === $character || "'" === $character ) {
				$quote   = $character;
				$segment .= $character;
				continue;
			}

			if ( '(' === $character ) {
				$parentheses++;
			} elseif ( ')' === $character ) {
				if ( 0 === $parentheses ) {
					return false;
				}
				$parentheses--;
			} elseif ( '[' === $character ) {
				$brackets++;
			} elseif ( ']' === $character ) {
				if ( 0 === $brackets ) {
					return false;
				}
				$brackets--;
			}

			if ( ',' === $character && 0 === $parentheses && 0 === $brackets ) {
				if ( '' === trim( $segment ) ) {
					return false;
				}
				$segment = '';
				continue;
			}

			$segment .= $character;
		}

		return ! $escaped && '' === $quote && 0 === $parentheses && 0 === $brackets && '' !== trim( $segment );
	}
}
