<?php
/**
 * Shared administration form controls, loaded only when a screen needs them.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class Admin_Fields {
	/** @var Font_Repository */
	private $fonts;

	/** @var array<string,array>|null Font choices for this page render only. */
	private $font_choices = null;

	/** @param Font_Repository $fonts Font repository. */
	public function __construct( Font_Repository $fonts ) {
		$this->fonts = $fonts;
	}

	/**
	 * Render primary typography controls plus optional advanced readability controls.
	 *
	 * @param string $name Field prefix.
	 * @param array<string,mixed> $value Saved value.
	 * @param bool $include_remove Whether to render the custom-selector remove control inside each responsive layer.
	 * @return void
	 */
	public function render_assignment_fields( $name, array $value, $include_remove = false ) {
		$layers       = $this->assignment_layers();
		$active_count = $this->assignment_active_count( $value );
		?>
		<div class="sefm-assignment-fields" data-sefm-active-layer="general">
			<div class="sefm-layer-toolbar">
				<div class="sefm-layer-switcher" role="tablist" aria-label="<?php esc_attr_e( 'Responsive typography layers', 'saeidbakhsh-typography-manager' ); ?>">
					<?php foreach ( $layers as $layer => $config ) : ?>
						<?php $layer_count = $this->assignment_layer_active_count( $value, $layer ); ?>
						<button type="button" class="sefm-layer-tab<?php echo 'general' === $layer ? ' is-active' : ''; ?>" data-sefm-layer="<?php echo esc_attr( $layer ); ?>" role="tab" aria-selected="<?php echo 'general' === $layer ? 'true' : 'false'; ?>" tabindex="<?php echo 'general' === $layer ? '0' : '-1'; ?>" title="<?php echo esc_attr( $config['hint'] ); ?>">
							<span><?php echo esc_html( $config['label'] ); ?></span>
							<span class="sefm-layer-count"<?php echo 0 === $layer_count ? ' hidden' : ''; ?>><?php echo esc_html( (string) $layer_count ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="sefm-assignment-state">
					<?php /* translators: %d: Number of active typography settings. */ ?>
					<span class="sefm-active-indicator"<?php echo 0 === $active_count ? ' hidden' : ''; ?>><?php echo esc_html( sprintf( _n( '%d active', '%d active', $active_count, 'saeidbakhsh-typography-manager' ), $active_count ) ); ?></span>
					<button type="button" class="sefm-reset-assignment"<?php disabled( 0 === $active_count ); ?>><?php esc_html_e( 'Reset', 'saeidbakhsh-typography-manager' ); ?></button>
				</div>
			</div>

			<div class="sefm-layer-panels">
				<?php foreach ( $layers as $layer => $config ) : ?>
					<div class="sefm-layer-panel" data-sefm-layer-panel="<?php echo esc_attr( $layer ); ?>" role="tabpanel"<?php echo 'general' === $layer ? '' : ' hidden'; ?>>
						<?php $this->render_assignment_layer( $name, $value, $layer, $include_remove ); ?>
					</div>
				<?php endforeach; ?>
			</div>

		</div>
		<?php
	}

	/**
	 * Render one of the four independent typography layers.
	 *
	 * @param string $name Field prefix.
	 * @param array<string,mixed> $value Saved assignment.
	 * @param string $layer general|desktop|mobile|tablet.
	 * @param bool $include_remove Whether this layer should include the custom-selector remove control.
	 * @return void
	 */
	private function render_assignment_layer( $name, array $value, $layer, $include_remove = false ) {
		$font_key           = $this->assignment_layer_key( 'font', $layer );
		$font_size_key      = $this->assignment_layer_key( 'font_size', $layer );
		$weight_key         = $this->assignment_layer_key( 'weight', $layer );
		$style_key          = $this->assignment_layer_key( 'style', $layer );
		$color_key          = $this->assignment_layer_key( 'color', $layer );
		$line_height_key    = $this->assignment_layer_key( 'line_height', $layer );
		$letter_spacing_key = $this->assignment_layer_key( 'letter_spacing', $layer );
		$transform_key      = $this->assignment_layer_key( 'transform', $layer );
		$direction_key      = $this->assignment_layer_key( 'direction', $layer );

		$font           = isset( $value[ $font_key ] ) ? (string) $value[ $font_key ] : 'inherit';
		$weight         = isset( $value[ $weight_key ] ) ? (string) $value[ $weight_key ] : 'inherit';
		$style          = isset( $value[ $style_key ] ) ? (string) $value[ $style_key ] : 'inherit';
		$transform      = isset( $value[ $transform_key ] ) ? (string) $value[ $transform_key ] : 'inherit';
		$direction      = isset( $value[ $direction_key ] ) ? (string) $value[ $direction_key ] : 'inherit';
		$font_size      = $this->measurement_parts( isset( $value[ $font_size_key ] ) ? $value[ $font_size_key ] : 'inherit', 'px', array( 'px', 'rem', 'em', '%' ) );
		$letter_spacing = $this->measurement_parts( isset( $value[ $letter_spacing_key ] ) ? $value[ $letter_spacing_key ] : 'inherit', 'em', array( 'em', 'px', 'rem' ) );
		$line_height    = $this->measurement_parts( isset( $value[ $line_height_key ] ) ? $value[ $line_height_key ] : 'inherit', '', array( '', 'em', 'rem', 'px', '%' ) );
		$saved_color    = isset( $value[ $color_key ] ) && is_string( $value[ $color_key ] ) ? sanitize_hex_color( $value[ $color_key ] ) : '';
		$color_enabled  = (bool) $saved_color;
		$color_value    = $color_enabled ? $saved_color : '#111111';
		if ( 4 === strlen( $color_value ) ) {
			$color_value = '#' . $color_value[1] . $color_value[1] . $color_value[2] . $color_value[2] . $color_value[3] . $color_value[3];
		}

		$advanced_open = '' !== $letter_spacing['value'] || '' !== $line_height['value'] || 'inherit' !== $transform || 'inherit' !== $direction;
		?>
		<div class="sefm-controls">
			<div class="sefm-control-field sefm-font-field"><span><?php esc_html_e( 'Font', 'saeidbakhsh-typography-manager' ); ?></span><?php $this->render_font_select( $name . '[' . $font_key . ']', $font ); ?></div>
			<?php $this->render_measurement_control( $name, $font_size_key, __( 'Font size', 'saeidbakhsh-typography-manager' ), $font_size, array( 'px' => 'px', 'rem' => 'rem', 'em' => 'em', '%' => '%' ), false ); ?>
			<label><span><?php esc_html_e( 'Weight', 'saeidbakhsh-typography-manager' ); ?></span><?php $this->render_select( $name . '[' . $weight_key . ']', $weight, $this->weight_options( true ) ); ?></label>
			<label><span><?php esc_html_e( 'Style', 'saeidbakhsh-typography-manager' ); ?></span><?php $this->render_select( $name . '[' . $style_key . ']', $style, $this->style_options( true ) ); ?></label>
			<div class="sefm-control-field sefm-color-field">
				<span><?php esc_html_e( 'Color', 'saeidbakhsh-typography-manager' ); ?></span>
				<div class="sefm-color-control">
					<input type="color" name="<?php echo esc_attr( $name . '[' . $color_key . ']' ); ?>" value="<?php echo esc_attr( $color_value ); ?>" aria-label="<?php esc_attr_e( 'Font color', 'saeidbakhsh-typography-manager' ); ?>">
					<label class="sefm-color-toggle">
						<input type="checkbox" name="<?php echo esc_attr( $name . '[' . $color_key . '_enabled]' ); ?>" value="1" <?php checked( $color_enabled ); ?> aria-label="<?php esc_attr_e( 'Enable color', 'saeidbakhsh-typography-manager' ); ?>">
						<span class="sefm-toggle-track" aria-hidden="true"><span></span></span>
						<span class="sefm-toggle-status" aria-hidden="true"><span class="sefm-toggle-off"><?php esc_html_e( 'Off', 'saeidbakhsh-typography-manager' ); ?></span><span class="sefm-toggle-on"><?php esc_html_e( 'On', 'saeidbakhsh-typography-manager' ); ?></span></span>
					</label>
				</div>
			</div>
			<?php if ( $include_remove ) : ?>
				<button type="button" class="sefm-remove-rule" aria-label="<?php esc_attr_e( 'Remove selector', 'saeidbakhsh-typography-manager' ); ?>">&times;</button>
			<?php endif; ?>
		</div>

		<details class="sefm-advanced"<?php echo $advanced_open ? ' open' : ''; ?>>
			<summary><?php esc_html_e( 'Advanced typography', 'saeidbakhsh-typography-manager' ); ?></summary>
			<div class="sefm-advanced-body">
				<div class="sefm-advanced-grid">
					<?php $this->render_measurement_control( $name, $line_height_key, __( 'Line height', 'saeidbakhsh-typography-manager' ), $line_height, array( '' => __( 'Unitless', 'saeidbakhsh-typography-manager' ), 'em' => 'em', 'rem' => 'rem', 'px' => 'px', '%' => '%' ), false ); ?>
					<?php $this->render_measurement_control( $name, $letter_spacing_key, __( 'Letter spacing', 'saeidbakhsh-typography-manager' ), $letter_spacing, array( 'em' => 'em', 'px' => 'px', 'rem' => 'rem' ), true ); ?>
					<label class="sefm-control-field"><span><?php esc_html_e( 'Case', 'saeidbakhsh-typography-manager' ); ?></span><?php $this->render_select( $name . '[' . $transform_key . ']', $transform, $this->transform_options() ); ?></label>
					<label class="sefm-control-field"><span><?php esc_html_e( 'Direction', 'saeidbakhsh-typography-manager' ); ?></span><?php $this->render_select( $name . '[' . $direction_key . ']', $direction, $this->direction_options() ); ?></label>
				</div>
			</div>
		</details>
		<?php
	}

	/** @return array<string,array<string,string>> */
	private function assignment_layers() {
		return array(
			'general' => array( 'label' => __( 'General', 'saeidbakhsh-typography-manager' ), 'hint' => __( 'Base typography for every screen size.', 'saeidbakhsh-typography-manager' ) ),
			'desktop' => array( 'label' => __( 'Desktop', 'saeidbakhsh-typography-manager' ), 'hint' => __( 'Overrides General at 1025px and wider.', 'saeidbakhsh-typography-manager' ) ),
			'mobile'  => array( 'label' => __( 'Mobile', 'saeidbakhsh-typography-manager' ), 'hint' => __( 'Overrides General at 767px and narrower.', 'saeidbakhsh-typography-manager' ) ),
			'tablet'  => array( 'label' => __( 'Tablet', 'saeidbakhsh-typography-manager' ), 'hint' => __( 'Overrides General from 768px through 1024px.', 'saeidbakhsh-typography-manager' ) ),
		);
	}

	/**
	 * Resolve the storage key for one layer property.
	 *
	 * @param string $base Base property key.
	 * @param string $layer Layer key.
	 * @return string
	 */
	private function assignment_layer_key( $base, $layer ) {
		return 'general' === $layer ? $base : $base . '_' . $layer;
	}

	/**
	 * Count non-inherited values in one responsive layer.
	 *
	 * @param array<string,mixed> $value Assignment values.
	 * @param string $layer Layer key.
	 * @return int
	 */
	private function assignment_layer_active_count( array $value, $layer ) {
		$count = 0;
		foreach ( array( 'font', 'font_size', 'weight', 'style', 'color', 'line_height', 'letter_spacing', 'transform', 'direction' ) as $base ) {
			$key = $this->assignment_layer_key( $base, $layer );
			if ( isset( $value[ $key ] ) && is_scalar( $value[ $key ] ) && '' !== (string) $value[ $key ] && 'inherit' !== (string) $value[ $key ] ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Count non-inherited typography values across all four layers.
	 *
	 * @param array<string,mixed> $value Assignment values.
	 * @return int
	 */
	private function assignment_active_count( array $value ) {
		$count = 0;
		foreach ( array_keys( $this->assignment_layers() ) as $layer ) {
			$count += $this->assignment_layer_active_count( $value, $layer );
		}

		return $count;
	}

	/**
	 * Render one compact number/unit typography control.
	 *
	 * @param string $name Assignment field prefix.
	 * @param string $key Canonical measurement key.
	 * @param string $label Visible field label.
	 * @param array{value:string,unit:string} $parts Parsed saved value.
	 * @param array<string,string> $units Unit value => label map.
	 * @param bool $allow_negative Whether the numeric field may be negative.
	 * @return void
	 */
	private function render_measurement_control( $name, $key, $label, array $parts, array $units, $allow_negative ) {
		?>
		<label class="sefm-measurement-field">
			<span><?php echo esc_html( $label ); ?></span>
			<span class="sefm-measurement-control">
				<input type="number" name="<?php echo esc_attr( $name . '[' . $key . '_value]' ); ?>" value="<?php echo esc_attr( $parts['value'] ); ?>" step="0.01"<?php echo $allow_negative ? '' : ' min="0"'; ?> placeholder="<?php esc_attr_e( 'Inherit', 'saeidbakhsh-typography-manager' ); ?>" inputmode="decimal">
				<?php $this->render_select( $name . '[' . $key . '_unit]', $parts['unit'], $units ); ?>
			</span>
		</label>
		<?php
	}

	/**
	 * Split a saved canonical measurement into UI number/unit parts.
	 *
	 * @param mixed $stored Saved assignment value.
	 * @param string $default_unit Unit shown while the value remains empty/inherited.
	 * @param array<int,string> $allowed_units Units valid for this property; an empty string permits unitless values.
	 * @return array{value:string,unit:string}
	 */
	private function measurement_parts( $stored, $default_unit, array $allowed_units ) {
		$stored = is_scalar( $stored ) ? trim( (string) $stored ) : 'inherit';
		if ( 'inherit' === $stored || '' === $stored ) {
			return array( 'value' => '', 'unit' => $default_unit );
		}

		if ( preg_match( '/^(-?(?:\d+(?:\.\d+)?|\.\d+))(px|rem|em|%)?$/D', $stored, $matches ) ) {
			$unit = isset( $matches[2] ) ? $matches[2] : '';
			if ( in_array( $unit, $allowed_units, true ) ) {
				return array( 'value' => $matches[1], 'unit' => $unit );
			}
		}

		return array( 'value' => '', 'unit' => $default_unit );
	}

	/**
	 * Render accessible selector chips for one built-in rule.
	 *
	 * @param string $key Element definition ID.
	 * @param array<string,string> $element Element definition.
	 * @param array<string,mixed> $value Saved value.
	 * @return void
	 */
	public function render_selector_toggles( $key, array $element, array $value ) {
		$name = 'sbty_settings[assignments][' . $key . ']';

		if ( 'site' === $key ) {
			$active = Element_Registry::active_site_targets( $value );
			$targets = array(
				'all'   => array( '*', __( 'Regular page elements', 'saeidbakhsh-typography-manager' ) ),
				'icons' => array( 'Icon', __( 'Icon elements and before/after pseudo-elements', 'saeidbakhsh-typography-manager' ) ),
				'svg'   => array( 'SVG', __( 'SVG elements and their descendants', 'saeidbakhsh-typography-manager' ) ),
			);
			?>
			<div class="sefm-selector-toggles sefm-site-targets" role="group" aria-label="<?php esc_attr_e( 'Entire site targets', 'saeidbakhsh-typography-manager' ); ?>">
				<input type="hidden" name="<?php echo esc_attr( $name . '[site_targets_present]' ); ?>" value="1">
				<?php foreach ( $targets as $target => $config ) : ?>
					<label class="sefm-selector-toggle">
						<input type="checkbox" name="<?php echo esc_attr( $name . '[site_targets][]' ); ?>" value="<?php echo esc_attr( $target ); ?>" <?php checked( in_array( $target, $active, true ) ); ?>>
						<code title="<?php echo esc_attr( $config[1] ); ?>"><?php echo esc_html( $config[0] ); ?></code>
					</label>
				<?php endforeach; ?>
			</div>
			<?php
			return;
		}

		$parts  = Element_Registry::selector_parts( $element['selector'], $key );
		$active = isset( $value['selectors'] ) && is_array( $value['selectors'] ) ? $value['selectors'] : $parts;
		?>
		<div class="sefm-selector-toggles" role="group" aria-label="<?php esc_attr_e( 'Active selectors', 'saeidbakhsh-typography-manager' ); ?>">
			<input type="hidden" name="<?php echo esc_attr( $name . '[selectors_present]' ); ?>" value="1">
			<?php foreach ( $parts as $selector ) : ?>
				<label class="sefm-selector-toggle" title="<?php echo esc_attr( $selector ); ?>">
					<input type="checkbox" name="<?php echo esc_attr( $name . '[selectors][]' ); ?>" value="<?php echo esc_attr( $selector ); ?>" <?php checked( in_array( $selector, $active, true ) ); ?>>
					<code><?php echo esc_html( $selector ); ?></code>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/** @return void */
	public function render_remote_font_meta_fields() {
		?>
		<div class="sefm-meta-grid">
			<label class="sefm-field sefm-span-2"><span><?php esc_html_e( 'Font family name', 'saeidbakhsh-typography-manager' ); ?></span><input type="text" name="sbty_font_name" maxlength="100" placeholder="Example Sans" required></label>
			<label class="sefm-field"><span><?php esc_html_e( 'Weight or range', 'saeidbakhsh-typography-manager' ); ?></span><input type="text" name="sbty_font_weight" value="400" pattern="(?:normal|bold|(?:[1-9]\d{0,2}|1000)(?:\s+(?:[1-9]\d{0,2}|1000))?)" maxlength="9" required dir="ltr"><small><?php esc_html_e( 'Examples: 400, 700, or 100 900 for a variable font.', 'saeidbakhsh-typography-manager' ); ?></small></label>
			<label class="sefm-field"><span><?php esc_html_e( 'Style', 'saeidbakhsh-typography-manager' ); ?></span><?php $this->render_select( 'sbty_font_style', 'normal', $this->style_options( false ) ); ?></label>
			<label class="sefm-field sefm-span-2"><span><?php esc_html_e( 'Loading strategy', 'saeidbakhsh-typography-manager' ); ?></span><?php $this->render_select( 'sbty_font_display', 'swap', array( 'swap' => 'Swap', 'optional' => 'Optional', 'fallback' => 'Fallback', 'block' => 'Block', 'auto' => 'Auto' ) ); ?></label>
		</div>
		<?php
	}

	/**
	 * Render one custom selector row.
	 *
	 * @param int|string $index Array index or template token.
	 * @param array<string,mixed> $rule Saved rule.
	 * @return void
	 */
	public function render_custom_rule( $index, array $rule ) {
		$selector = isset( $rule['selector'] ) ? $rule['selector'] : '';
		$prefix   = 'sbty_settings[custom_rules][' . $index . ']';
		?>
		<article class="sefm-custom-rule">
			<label class="sefm-selector-field"><span><?php esc_html_e( 'CSS selector', 'saeidbakhsh-typography-manager' ); ?></span><input type="text" name="<?php echo esc_attr( $prefix . '[selector]' ); ?>" value="<?php echo esc_attr( $selector ); ?>" maxlength="250" placeholder=".product-card .price" dir="ltr"></label>
			<?php $this->render_assignment_fields( $prefix, $rule, true ); ?>
		</article>
		<?php
	}

	/**
	 * Render the family select with grouped plugin-managed, WordPress, and system fonts.
	 *
	 * @param string $name Field name.
	 * @param string $selected Selected value.
	 * @return void
	 */
	private function render_font_select( $name, $selected ) {
		if ( null === $this->font_choices ) {
			$this->font_choices = array(
				'managed'   => $this->fonts->managed(),
				'wordpress' => $this->fonts->wordpress(),
				'system'    => CSS_Generator::system_fonts(),
			);
		}
		$managed   = $this->font_choices['managed'];
		$wordpress = $this->font_choices['wordpress'];
		?>
		<select class="sefm-font-select" name="<?php echo esc_attr( $name ); ?>">
			<option value="inherit" <?php selected( $selected, 'inherit' ); ?>><?php esc_html_e( 'Inherit / unchanged', 'saeidbakhsh-typography-manager' ); ?></option>
			<?php if ( ! empty( $managed ) ) : ?>
				<optgroup label="<?php esc_attr_e( 'Remote & legacy fonts', 'saeidbakhsh-typography-manager' ); ?>">
					<?php foreach ( $managed as $font ) : ?>
						<option value="<?php echo esc_attr( 'font:' . $font['id'] ); ?>" <?php selected( $selected, 'font:' . $font['id'] ); ?>><?php echo esc_html( $font['name'] . ' — ' . $font['weight'] . ' ' . $font['style'] ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endif; ?>
			<?php if ( ! empty( $wordpress ) ) : ?>
				<optgroup label="<?php esc_attr_e( 'WordPress Font Library', 'saeidbakhsh-typography-manager' ); ?>">
					<?php foreach ( $wordpress as $font ) : ?>
						<option value="<?php echo esc_attr( 'font:' . $font['id'] ); ?>" <?php selected( $selected, 'font:' . $font['id'] ); ?>><?php echo esc_html( $font['name'] . ' — ' . $font['weight'] . ' ' . $font['style'] ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endif; ?>
			<optgroup label="<?php esc_attr_e( 'System fonts', 'saeidbakhsh-typography-manager' ); ?>">
				<?php foreach ( $this->font_choices['system'] as $key => $font ) : ?>
					<option value="<?php echo esc_attr( 'system:' . $key ); ?>" <?php selected( $selected, 'system:' . $key ); ?>><?php echo esc_html( $font['label'] ); ?></option>
				<?php endforeach; ?>
			</optgroup>
		</select>
		<?php
	}

	/**
	 * Render a safe enumerated select.
	 *
	 * @param string $name Field name.
	 * @param string $selected Current value.
	 * @param array<string,string> $options Options.
	 * @return void
	 */
	private function render_select( $name, $selected, array $options ) {
		?>
		<select name="<?php echo esc_attr( $name ); ?>">
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) $selected, (string) $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/** @return array<string,string> */
	private function weight_options( $inherit ) {
		$options = $inherit ? array( 'inherit' => __( 'Inherit', 'saeidbakhsh-typography-manager' ) ) : array();
		$options['normal'] = __( 'Normal', 'saeidbakhsh-typography-manager' );
		$options['bold']   = __( 'Bold', 'saeidbakhsh-typography-manager' );
		foreach ( range( 100, 900, 100 ) as $weight ) {
			$options[ (string) $weight ] = (string) $weight;
		}

		return $options;
	}

	/** @return array<string,string> */
	private function style_options( $inherit ) {
		$options = $inherit ? array( 'inherit' => __( 'Inherit', 'saeidbakhsh-typography-manager' ) ) : array();

		return array_merge(
			$options,
			array(
				'normal'  => __( 'Normal', 'saeidbakhsh-typography-manager' ),
				'italic'  => __( 'Italic', 'saeidbakhsh-typography-manager' ),
				'oblique' => __( 'Oblique', 'saeidbakhsh-typography-manager' ),
			)
		);
	}

	/** @return array<string,string> */
	private function transform_options() {
		return array(
			'inherit'    => __( 'Inherit', 'saeidbakhsh-typography-manager' ),
			'none'       => __( 'None', 'saeidbakhsh-typography-manager' ),
			'uppercase'  => __( 'Uppercase', 'saeidbakhsh-typography-manager' ),
			'lowercase'  => __( 'Lowercase', 'saeidbakhsh-typography-manager' ),
			'capitalize' => __( 'Capitalize', 'saeidbakhsh-typography-manager' ),
		);
	}

	/** @return array<string,string> */
	private function direction_options() {
		return array(
			'inherit' => __( 'Inherit', 'saeidbakhsh-typography-manager' ),
			'rtl'     => __( 'Right to left', 'saeidbakhsh-typography-manager' ),
			'ltr'     => __( 'Left to right', 'saeidbakhsh-typography-manager' ),
		);
	}
}
