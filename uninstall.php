<?php
/**
 * Remove plugin-owned options. WordPress Font Library files are intentionally retained.
 *
 * @package SaeidbakhshTypographyManager
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/** @return void */
function sbty_delete_site_options() {
	delete_option( 'sbty_fonts' );
	delete_option( 'sbty_settings' );
	delete_option( 'sbty_css_cache' );
	delete_option( 'sbty_css_payload' );
	delete_option( 'sbty_css_version' );
}

/** @return void */
function sbty_delete_multisite_options() {
	$offset = 0;
	$batch  = 100;

	do {
		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => $batch,
				'offset' => $offset,
			)
		);

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			sbty_delete_site_options();
			restore_current_blog();
		}

		$offset += $batch;
	} while ( count( $site_ids ) === $batch );
}

sbty_delete_site_options();

if ( is_multisite() ) {
	sbty_delete_multisite_options();
}
