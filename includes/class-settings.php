<?php
/**
 * Settings API registration and validation.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class Settings {
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
	public function register_hooks() {
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'wp_ajax_sbty_save_all', array( $this, 'save_all' ) );
	}

	/** @return void */
	public function save_all() {
		require_once __DIR__ . '/class-admin-save.php';
		$handler = new Admin_Save( $this );
		$handler->save();
	}

	/** @return void */
	public function register() {
		register_setting(
			'sbty_settings_group',
			Font_Repository::SETTINGS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => Font_Repository::default_settings(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Validate partial tab submissions while retaining untouched groups.
	 *
	 * @param mixed $input Submitted option.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ) {
		$current = $this->fonts->settings();

		if ( ! is_array( $input ) ) {
			return $current;
		}

		if ( isset( $input['assignments'] ) && is_array( $input['assignments'] ) ) {
			$known = $this->elements->all();
			foreach ( $input['assignments'] as $key => $assignment ) {
				$key = sanitize_key( $key );
				if ( isset( $known[ $key ] ) && is_array( $assignment ) ) {
					$clean = $this->sanitize_assignment( $assignment );
					if ( 'site' === $key ) {
						$clean['site_targets'] = $this->sanitize_site_targets( $assignment );

						// A legacy version can represent only full coverage or fully disabled.
						// Partial scopes downgrade safely to disabled instead of re-enabling excluded targets.
						$clean['selectors'] = 3 === count( $clean['site_targets'] )
							? Element_Registry::selector_parts( $known[ $key ]['selector'], $key )
							: array();
					} else {
						$clean['selectors'] = $this->sanitize_selectors( $assignment, $known[ $key ]['selector'], $key );
					}
					$current['assignments'][ $key ] = $clean;
				}
			}
		}

		if ( array_key_exists( 'priority_mode', $input ) ) {
			$mode = sanitize_key( $input['priority_mode'] );
			$current['priority_mode'] = in_array( $mode, array( 'standard', 'strong', 'maximum' ), true ) ? $mode : 'strong';
		}

		if ( isset( $input['custom_rules_present'] ) ) {
			$current['custom_rules'] = array();
			$rules                   = isset( $input['custom_rules'] ) && is_array( $input['custom_rules'] ) ? $input['custom_rules'] : array();

			foreach ( array_slice( $rules, 0, 20 ) as $rule ) {
				if ( ! is_array( $rule ) ) {
					continue;
				}

				$selector = isset( $rule['selector'] ) ? trim( sanitize_text_field( wp_unslash( $rule['selector'] ) ) ) : '';
				if ( '' === $selector ) {
					continue;
				}

				if ( ! CSS_Generator::is_safe_selector( $selector ) ) {
					add_settings_error(
						Font_Repository::SETTINGS_OPTION,
						'sefm_invalid_selector',
						__( 'One custom selector was rejected because it contained unsafe or invalid characters.', 'saeidbakhsh-typography-manager' ),
						'error'
					);
					continue;
				}

				$clean             = $this->sanitize_assignment( $rule );
				$clean['selector'] = $selector;
				$current['custom_rules'][] = $clean;
			}
		}

		return $current;
	}

	/**
	 * Validate one typography rule strictly against enumerated choices.
	 *
	 * @param array<string,mixed> $assignment Raw assignment.
	 * @return array<string,string>
	 */
	private function sanitize_assignment( array $assignment ) {
		$clean = array();
		foreach ( array( 'general', 'desktop', 'mobile', 'tablet' ) as $layer ) {
			$clean = array_merge( $clean, $this->sanitize_assignment_layer( $assignment, $layer ) );
		}

		return $clean;
	}

	/**
	 * Validate one responsive typography layer.
	 *
	 * General keeps the historical unsuffixed keys. Device-specific layers use
	 * stable suffixes so existing settings remain valid without a migration.
	 *
	 * @param array<string,mixed> $assignment Raw assignment.
	 * @param string $layer general|desktop|mobile|tablet.
	 * @return array<string,string>
	 */
	private function sanitize_assignment_layer( array $assignment, $layer ) {
		$suffix = 'general' === $layer ? '' : '_' . $layer;
		$key    = static function ( $base ) use ( $suffix ) {
			return $base . $suffix;
		};

		$font_key      = $key( 'font' );
		$weight_key    = $key( 'weight' );
		$style_key     = $key( 'style' );
		$transform_key = $key( 'transform' );
		$direction_key = $key( 'direction' );
		$color_key     = $key( 'color' );

		$font      = isset( $assignment[ $font_key ] ) ? sanitize_text_field( wp_unslash( $assignment[ $font_key ] ) ) : 'inherit';
		$font      = $this->valid_font_selection( $font ) ? $font : 'inherit';
		$weight    = isset( $assignment[ $weight_key ] ) ? (string) $assignment[ $weight_key ] : 'inherit';
		$style     = isset( $assignment[ $style_key ] ) ? (string) $assignment[ $style_key ] : 'inherit';
		$transform = isset( $assignment[ $transform_key ] ) ? (string) $assignment[ $transform_key ] : 'inherit';
		$direction = isset( $assignment[ $direction_key ] ) ? sanitize_key( wp_unslash( (string) $assignment[ $direction_key ] ) ) : 'inherit';

		$font_size      = $this->sanitize_measurement( $assignment, $key( 'font_size' ) . '_value', $key( 'font_size' ) . '_unit', array( 'px', 'rem', 'em', '%' ), false );
		$letter_spacing = $this->sanitize_measurement( $assignment, $key( 'letter_spacing' ) . '_value', $key( 'letter_spacing' ) . '_unit', array( 'px', 'rem', 'em' ), true );
		$line_height    = $this->sanitize_measurement( $assignment, $key( 'line_height' ) . '_value', $key( 'line_height' ) . '_unit', array( '', 'px', 'rem', 'em', '%' ), false );
		$color          = 'inherit';
		$enabled_key    = $color_key . '_enabled';
		if ( ! empty( $assignment[ $enabled_key ] ) && isset( $assignment[ $color_key ] ) && is_string( $assignment[ $color_key ] ) ) {
			$validated_color = sanitize_hex_color( wp_unslash( $assignment[ $color_key ] ) );
			$color           = $validated_color ? $validated_color : 'inherit';
		}

		$weights    = array( 'inherit', 'normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900' );
		$styles     = array( 'inherit', 'normal', 'italic', 'oblique' );
		$transforms = array( 'inherit', 'none', 'uppercase', 'lowercase', 'capitalize' );
		$directions = array( 'inherit', 'rtl', 'ltr' );

		return array(
			$font_key              => $font,
			$weight_key            => in_array( $weight, $weights, true ) ? $weight : 'inherit',
			$style_key             => in_array( $style, $styles, true ) ? $style : 'inherit',
			$transform_key         => in_array( $transform, $transforms, true ) ? $transform : 'inherit',
			$direction_key         => in_array( $direction, $directions, true ) ? $direction : 'inherit',
			$key( 'font_size' )      => $font_size,
			$key( 'letter_spacing' ) => $letter_spacing,
			$key( 'line_height' )    => $line_height,
			$color_key             => $color,
		);
	}

	/**
	 * Convert a compact number/unit control to a safe CSS measurement.
	 *
	 * An empty value deliberately means inherit, so advanced controls stay inert
	 * until the administrator explicitly enters a value.
	 *
	 * @param array<string,mixed> $assignment Submitted assignment.
	 * @param string $value_key Numeric field key.
	 * @param string $unit_key Unit field key.
	 * @param array<int,string> $allowed_units Allowed CSS units; an empty string means unitless.
	 * @param bool $allow_negative Whether negative values are valid.
	 * @return string
	 */
	private function sanitize_measurement( array $assignment, $value_key, $unit_key, array $allowed_units, $allow_negative ) {
		$raw = isset( $assignment[ $value_key ] ) && is_scalar( $assignment[ $value_key ] )
			? trim( wp_unslash( (string) $assignment[ $value_key ] ) )
			: '';

		if ( '' === $raw ) {
			return 'inherit';
		}

		if ( ! preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)$/D', $raw ) ) {
			return 'inherit';
		}

		$number = (float) $raw;
		if ( ( ! $allow_negative && $number < 0 ) || abs( $number ) > 100000 ) {
			return 'inherit';
		}

		$unit = isset( $assignment[ $unit_key ] ) && is_scalar( $assignment[ $unit_key ] )
			? trim( wp_unslash( (string) $assignment[ $unit_key ] ) )
			: '';
		if ( ! in_array( $unit, $allowed_units, true ) ) {
			return 'inherit';
		}

		$normalized = 0.0 === $number ? '0' : rtrim( rtrim( sprintf( '%.4F', $number ), '0' ), '.' );

		return $normalized . $unit;
	}

	/**
	 * Sanitize the independent Entire site scope switches.
	 *
	 * Missing marker data can come from older plugin versions or integrations;
	 * in that case the legacy selector state is translated conservatively by the
	 * registry compatibility helper.
	 *
	 * @param array<string,mixed> $assignment Submitted assignment.
	 * @return array<int,string>
	 */
	private function sanitize_site_targets( array $assignment ) {
		if ( ! array_key_exists( 'site_targets_present', $assignment ) ) {
			return Element_Registry::active_site_targets( $assignment );
		}

		$allowed   = Element_Registry::site_targets();
		$submitted = isset( $assignment['site_targets'] ) && is_array( $assignment['site_targets'] ) ? $assignment['site_targets'] : array();
		$submitted = array_values(
			array_filter(
				array_map(
					static function ( $value ) {
						return is_scalar( $value ) ? sanitize_key( wp_unslash( (string) $value ) ) : '';
					},
					$submitted
				),
				'strlen'
			)
		);

		return array_values( array_intersect( $allowed, $submitted ) );
	}

	/**
	 * Keep only exact selectors registered for this built-in element rule.
	 *
	 * Missing marker data means the value came from an older version or another
	 * integration, so every target stays enabled for backward compatibility.
	 *
	 * @param array<string,mixed> $assignment Submitted assignment.
	 * @param string $selector Registered selector list.
	 * @param string $key Element definition ID.
	 * @return array<int,string>
	 */
	private function sanitize_selectors( array $assignment, $selector, $key ) {
		$allowed = Element_Registry::selector_parts( $selector, $key );
		if ( ! array_key_exists( 'selectors_present', $assignment ) ) {
			return $allowed;
		}

		$submitted = isset( $assignment['selectors'] ) && is_array( $assignment['selectors'] ) ? $assignment['selectors'] : array();
		$submitted = array_map(
			static function ( $value ) {
				return is_string( $value ) ? trim( wp_unslash( $value ) ) : '';
			},
			$submitted
		);

		return array_values( array_filter( $allowed, static function ( $value ) use ( $submitted ) {
			return in_array( $value, $submitted, true );
		} ) );
	}

	/**
	 * Confirm a family selection exists now.
	 *
	 * @param string $selection Font selection.
	 * @return bool
	 */
	private function valid_font_selection( $selection ) {
		if ( 'inherit' === $selection ) {
			return true;
		}

		if ( 0 === strpos( $selection, 'system:' ) ) {
			$key = substr( $selection, 7 );

			return isset( CSS_Generator::system_fonts()[ $key ] );
		}

		if ( 0 === strpos( $selection, 'font:' ) ) {
			return null !== $this->fonts->find( substr( $selection, 5 ) );
		}

		return false;
	}
}
