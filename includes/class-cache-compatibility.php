<?php
/**
 * Optional integrations; no cache plugin is required and no settings are overwritten.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class Cache_Compatibility {
	/** @var bool */
	private static $purging = false;

	/** @return void */
	public static function register_hooks() {
		add_filter( 'rocket_delay_js_exclusions', array( __CLASS__, 'runtime_exclusions' ) );
		add_filter( 'litespeed_optm_js_defer_exc', array( __CLASS__, 'runtime_exclusions' ) );
		add_filter( 'litespeed_optm_gm_js_exc', array( __CLASS__, 'runtime_exclusions' ) );
		add_filter( 'rocket_rucss_external_exclusions', array( __CLASS__, 'css_exclusions' ) );
		add_filter( 'rocket_rucss_inline_atts_exclusions', array( __CLASS__, 'inline_exclusions' ) );
	}

	/** @param array $list Existing exclusions. @return array */
	public static function runtime_exclusions( $list ) {
		$list   = is_array( $list ) ? $list : array();
		$list[] = 'saeidbakhsh-typography-manager/assets/frontend.js';
		$list[] = 'sbtyFontRuntime';
		return array_values( array_unique( $list ) );
	}

	/** @param array $list Existing exclusions. @return array */
	public static function css_exclusions( $list ) {
		$list   = is_array( $list ) ? $list : array();
		$list[] = '/saeidbakhsh-typography-manager/typography-';
		return array_values( array_unique( $list ) );
	}

	/** @param array $list Existing exclusions. @return array */
	public static function inline_exclusions( $list ) {
		$list   = is_array( $list ) ? $list : array();
		$list[] = 'id="sbty-typography-inline-css"';
		$list[] = "id='sbty-typography-inline-css'";
		return array_values( array_unique( $list ) );
	}

	/** Only called after a successfully stored, changed typography snapshot. @return void */
	public static function purge() {
		if ( self::$purging ) {
			return;
		}
		self::$purging = true;
		try {
			// Minified/Used CSS must be invalidated as well as cached HTML.
			self::invoke( array( 'autoptimizeCache', 'clearall' ) );
			self::invoke( 'rocket_clean_minify' );
			self::invoke( array( __CLASS__, 'clear_rocket_used_css' ) );
			self::invoke( 'rocket_clean_domain' );
			self::invoke( 'w3tc_flush_all' );
			self::invoke( 'wp_cache_clear_cache', array( get_current_blog_id() ) );
			self::invoke( 'wpfc_clear_all_cache', array( true ) );
			// Current public action, not the removed/incorrectly namespaced LiteSpeed API class.
			self::invoke( 'do_action', array( 'litespeed_purge_all', 'Saeidbakhsh Typography Manager changed' ) );
			// Hosting/CDN integrations can subscribe without credentials stored in this plugin.
			self::invoke( 'do_action', array( 'sbty_typography_cache_invalidated', get_current_blog_id() ) );
		} finally {
			self::$purging = false;
		}
	}

	/** Guard the optional service used in WP Rocket's official cleanup example. @return void */
	public static function clear_rocket_used_css() {
		if ( ! defined( 'WP_ROCKET_VERSION' ) ) {
			return;
		}
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Rocket owns this public hook; its name must remain unchanged for the integration to work.
		$container = apply_filters( 'rocket_container', null );
		if ( is_object( $container ) && is_callable( array( $container, 'get' ) ) ) {
			$subscriber = $container->get( 'rucss_admin_subscriber' );
			if ( is_object( $subscriber ) && is_callable( array( $subscriber, 'truncate_used_css' ) ) ) {
				$subscriber->truncate_used_css();
			}
		}
	}

	/** @param callable|string|array $callback Optional public API. @param array $args Arguments. @return void */
	private static function invoke( $callback, array $args = array() ) {
		if ( ! is_callable( $callback ) ) {
			return;
		}
		try {
			call_user_func_array( $callback, $args );
		} catch ( \Throwable $error ) {
			// A changed integration must not break saving or prevent other purges.
		}
	}
}
