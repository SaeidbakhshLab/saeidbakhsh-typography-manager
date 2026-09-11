<?php
namespace Saeidbakhsh\Typography;

defined('ABSPATH') || exit;

/**
 * CDN friendly URL resolver.
 */
final class CDN_Compatibility {
	public static function asset_url($path) {
		return set_url_scheme($path);
	}
}
