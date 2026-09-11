<?php
/**
 * Save an administration draft in one validated option update.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class Admin_Save {
	/** @var Settings */
	private $settings;

	/** @param Settings $settings Existing Settings API validator. */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/** @return void */
	public function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to save these settings.', 'saeidbakhsh-typography-manager' ) ), 403 );
		}
		if ( ! check_ajax_referer( 'sbty_save_all', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Your session has expired. Keep this page open and sign in again before retrying.', 'saeidbakhsh-typography-manager' ) ), 403 );
		}

		// One JSON field avoids PHP max_input_vars truncating large multi-tab forms.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Decode JSON unchanged; valid_input() checks its shape and Settings::sanitize() sanitizes decoded values before storage below.
		$raw = isset( $_POST['payload'] ) && is_string( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
		if ( '' === $raw || strlen( $raw ) > 2 * MB_IN_BYTES ) {
			wp_send_json_error( array( 'message' => __( 'The settings request is empty or too large.', 'saeidbakhsh-typography-manager' ) ), 400 );
		}
		$input = json_decode( $raw, true, 16 );
		if ( JSON_ERROR_NONE !== json_last_error() || ! $this->valid_input( $input ) ) {
			wp_send_json_error( array( 'message' => __( 'The settings request is invalid.', 'saeidbakhsh-typography-manager' ) ), 400 );
		}

		$option      = Font_Repository::SETTINGS_OPTION;
		$error_count = count( get_settings_errors( $option ) );
		$clean       = $this->settings->sanitize( wp_slash( $input ) );
		$errors      = array_slice( get_settings_errors( $option ), $error_count );
		foreach ( $errors as $error ) {
			if ( 'error' === $error['type'] ) {
				wp_send_json_error( array( 'message' => wp_strip_all_tags( $error['message'] ) ), 400 );
			}
		}

		// The validator accepts UI fields, not canonical measurements. It has
		// already run above; do not run it again on the normalized result.
		$callback = array( $this->settings, 'sanitize' );
		$removed  = remove_filter( 'sanitize_option_' . $option, $callback );
		try {
			$saved = update_option( $option, $clean, false );
		} finally {
			if ( $removed ) {
				add_filter( 'sanitize_option_' . $option, $callback );
			}
		}
		if ( ! $saved && $clean !== get_option( $option ) ) {
			wp_send_json_error( array( 'message' => __( 'WordPress could not save the settings. Your edits have been retained.', 'saeidbakhsh-typography-manager' ) ), 500 );
		}

		// Publish the one pending CSS revision before acknowledging the save.
		do_action( 'sbty_flush_css_cache' );
		$payload = get_option( CSS_Cache::PAYLOAD_OPTION, array() );
		wp_send_json_success(
			array(
				'nonce' => wp_create_nonce( 'sbty_save_all' ),
				'css'   => isset( $payload['css'] ) ? (string) $payload['css'] : '',
			)
		);
	}

	/**
	 * Accept only the flat controls and checkbox lists produced by our forms.
	 *
	 * @param mixed $input Decoded draft.
	 * @return bool
	 */
	private function valid_input( $input ) {
		if ( ! is_array( $input ) || empty( $input ) ) {
			return false;
		}
		foreach ( $input as $key => $value ) {
			if ( in_array( $key, array( 'priority_mode', 'custom_rules_present' ), true ) ) {
				if ( ! is_string( $value ) ) {
					return false;
				}
				continue;
			}
			if ( ! in_array( $key, array( 'assignments', 'custom_rules' ), true ) || ! is_array( $value ) ) {
				return false;
			}
			foreach ( $value as $rule ) {
				if ( ! is_array( $rule ) ) {
					return false;
				}
				foreach ( $rule as $field => $control ) {
					if ( in_array( $field, array( 'selectors', 'site_targets' ), true ) ) {
						if ( ! is_array( $control ) || count( array_filter( $control, 'is_string' ) ) !== count( $control ) ) {
							return false;
						}
					} elseif ( ! is_string( $control ) ) {
						return false;
					}
				}
			}
		}
		return true;
	}
}
