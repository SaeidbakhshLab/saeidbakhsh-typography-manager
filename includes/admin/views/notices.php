<?php
/**
 * Notices administration view.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

/** @var Admin $this */
$sbty_setting_errors = get_settings_errors( Font_Repository::SETTINGS_OPTION );
foreach ( $sbty_setting_errors as $sbty_error ) {
	$sbty_type = isset( $sbty_error['type'] ) && in_array( $sbty_error['type'], array( 'success', 'warning', 'info' ), true ) ? $sbty_error['type'] : 'error';
	if ( ! empty( $sbty_error['message'] ) ) {
		$this->render_notice( $sbty_type, $sbty_error['message'] );
	}
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only Settings API result flag; no state is changed.
if ( isset( $_GET['settings-updated'] ) && 'true' === sanitize_key( wp_unslash( $_GET['settings-updated'] ) ) ) {
	$this->render_notice( 'success', __( 'Typography settings saved.', 'saeidbakhsh-typography-manager' ) );
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice code produced by this plugin's redirects.
$sbty_code = isset( $_GET['sbty_notice'] ) ? sanitize_key( wp_unslash( $_GET['sbty_notice'] ) ) : '';
if ( '' === $sbty_code ) {
	return;
}

$sbty_messages = array(
	'remote_registered'     => array( 'success', __( 'Remote font URL registered. No file was downloaded by the plugin.', 'saeidbakhsh-typography-manager' ) ),
	'font_deleted'          => array( 'success', __( 'Font record removed from the plugin. Core-managed font files were not changed.', 'saeidbakhsh-typography-manager' ) ),
	'settings_reset'        => array( 'success', __( 'Typography settings were reset.', 'saeidbakhsh-typography-manager' ) ),
	'settings_reset_failed' => array( 'error', __( 'WordPress could not reset the typography settings.', 'saeidbakhsh-typography-manager' ) ),
	'invalid_name'          => array( 'error', __( 'Enter a valid font family name.', 'saeidbakhsh-typography-manager' ) ),
	'invalid_remote_url'    => array( 'error', __( 'Enter a complete direct HTTPS font URL without embedded credentials.', 'saeidbakhsh-typography-manager' ) ),
	'invalid_remote_format' => array( 'error', __( 'The URL path must end with .woff2, .woff, .ttf, or .otf. Query strings are allowed.', 'saeidbakhsh-typography-manager' ) ),
	'invalid_weight'        => array( 'error', __( 'Enter a valid font weight such as 400, 700, or a variable range such as 100 900.', 'saeidbakhsh-typography-manager' ) ),
	'invalid_remote_record' => array( 'error', __( 'The remote font record failed validation and was not saved.', 'saeidbakhsh-typography-manager' ) ),
	'remote_duplicate'      => array( 'warning', __( 'That remote font face is already registered.', 'saeidbakhsh-typography-manager' ) ),
	'font_store_failed'     => array( 'error', __( 'WordPress could not save the remote font record. The font file itself was not modified.', 'saeidbakhsh-typography-manager' ) ),
	'font_not_found'        => array( 'error', __( 'That plugin-managed font record no longer exists or could not be removed.', 'saeidbakhsh-typography-manager' ) ),
);

if ( isset( $sbty_messages[ $sbty_code ] ) ) {
	$this->render_notice( $sbty_messages[ $sbty_code ][0], $sbty_messages[ $sbty_code ][1] );
}
