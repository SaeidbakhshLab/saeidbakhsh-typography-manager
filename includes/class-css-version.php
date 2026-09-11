<?php
/** @package SaeidbakhshTypography */
namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

/** Content versions do not collide just because two saves occurred in one second. */
final class CSS_Version {
	const OPTION = 'sbty_css_version';

	/** @return string */
	public static function get() {
		return (string) get_option( self::OPTION, SBTY_VERSION );
	}

	/** @param string|null $css Compiled CSS, or null for an explicit revision. @return string */
	public static function bump( $css = null ) {
		$version = null === $css ? wp_generate_uuid4() : hash( 'sha256', (string) $css );
		update_option( self::OPTION, $version, false );
		return $version;
	}
}
