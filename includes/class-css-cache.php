<?php
/**
 * Content-addressed CSS and a persistent fallback for unwritable uploads.
 *
 * @package SaeidbakhshTypographyManager
 */

namespace Saeidbakhsh\Typography;

defined( 'ABSPATH' ) || exit;

final class CSS_Cache {
	const OPTION = 'sbty_css_cache';
	const PAYLOAD_OPTION = 'sbty_css_payload';
	const DIRECTORY = 'saeidbakhsh-typography-manager';

	/** @var array<int,array<string,mixed>> Small per-site metadata, memoized per request. */
	private $records = array();

	/** @var CSS_Generator */
	private $generator;

	/** @var array<int,bool> Sites changed during this request. */
	private $pending = array();

	/** @var bool Prevent recursive cache-plugin callbacks. */
	private $rebuilding = false;

	/** @param CSS_Generator $generator CSS generator. */
	public function __construct( CSS_Generator $generator ) {
		$this->generator = $generator;
	}

	/** @return void */
	public function register_hooks() {
		add_action( 'sbty_flush_css_cache', array( $this, 'flush_pending' ) );
		foreach ( array( Font_Repository::SETTINGS_OPTION, Font_Repository::FONTS_OPTION ) as $option ) {
			add_action( 'update_option_' . $option, array( $this, 'schedule_rebuild' ), 20, 0 );
			add_action( 'add_option_' . $option, array( $this, 'schedule_rebuild' ), 20, 0 );
			add_action( 'delete_option_' . $option, array( $this, 'schedule_rebuild' ), 20, 0 );
		}
		add_action( 'save_post_wp_font_face', array( $this, 'schedule_rebuild' ), 20, 0 );
		add_action( 'save_post_wp_font_family', array( $this, 'schedule_rebuild' ), 20, 0 );
		add_action( 'deleted_post', array( $this, 'font_deleted' ), 20, 2 );
		add_action( 'after_switch_theme', array( $this, 'schedule_rebuild' ), 20, 0 );
		// Upgrade/cold-start checks run after Core post types and theme filters exist.
		add_action( 'wp_loaded', array( $this, 'ensure_current' ), 99 );
		add_action( 'admin_init', array( $this, 'ensure_exists' ), 99 );
		// Finish related changes before generating, and before response headers flush.
		add_filter( 'wp_redirect', array( $this, 'before_response' ), 1 );
		add_filter( 'rest_request_after_callbacks', array( $this, 'before_response' ), 99 );
		add_action( 'shutdown', array( $this, 'flush_pending' ), 0 );
	}

	/** @return void */
	public function schedule_rebuild() {
		$this->pending[ get_current_blog_id() ] = true;
	}

	/** @param int $post_id Deleted post. @param \WP_Post $post Deleted object. @return void */
	public function font_deleted( $post_id, $post ) {
		if ( $post instanceof \WP_Post && in_array( $post->post_type, array( 'wp_font_face', 'wp_font_family' ), true ) ) {
			$this->schedule_rebuild();
		}
	}

	/** @param mixed $response Unmodified redirect/REST response. @return mixed */
	public function before_response( $response ) {
		$this->flush_pending();
		return $response;
	}

	/** @return void */
	public function flush_pending() {
		if ( $this->rebuilding ) {
			return;
		}
		foreach ( array_keys( $this->pending ) as $blog_id ) {
			$switched = (int) $blog_id !== get_current_blog_id();
			if ( $switched ) {
				switch_to_blog( (int) $blog_id );
			}
			try {
				$this->rebuild();
			} finally {
				if ( $switched ) {
					restore_current_blog();
				}
			}
		}
	}

	/** @return array<string,mixed> */
	private function record() {
		$blog_id = get_current_blog_id();
		if ( ! array_key_exists( $blog_id, $this->records ) ) {
			$record = get_option( self::OPTION, array() );
			$this->records[ $blog_id ] = is_array( $record ) ? $record : array();
		}
		return $this->records[ $blog_id ];
	}

	/** @return void */
	public function ensure_current() {
		$record = $this->record();
		if ( ! isset( $record['plugin_version'], $record['asset_directory'], $record['has_css'], $record['has_runtime'], $record['payload_hash'] ) || SBTY_VERSION !== $record['plugin_version'] || self::DIRECTORY !== $record['asset_directory'] ) {
			Plugin::migrate_registry_assignments();
			$this->rebuild();
		}
	}

	/** Repair a removed file on admin requests, not on every visitor request. @return void */
	public function ensure_exists() {
		$this->ensure_current();
		$record = $this->record();
		if ( ! empty( $record['has_css'] ) && '' === $this->url() ) {
			$this->rebuild();
		}
	}

	/** @return array<string,string> */
	private function paths() {
		// Do not create date-based upload directories on frontend requests.
		$upload = wp_upload_dir( null, false );
		if ( empty( $upload['basedir'] ) || empty( $upload['baseurl'] ) || ! empty( $upload['error'] ) ) {
			return array();
		}
		return array(
			'dir' => trailingslashit( $upload['basedir'] ) . self::DIRECTORY . '/',
			'url' => trailingslashit( $upload['baseurl'] ) . self::DIRECTORY . '/',
		);
	}

	/**
	 * Publish a new snapshot. Empty output is a valid revision and also purges HTML.
	 * Old files are retained because still-cached pages may reference them.
	 *
	 * @return bool Whether the compiled snapshot was stored.
	 */
	public function rebuild() {
		if ( $this->rebuilding ) {
			return false;
		}
		$this->rebuilding = true;
		try {
			$previous = $this->record();
			$this->generator->refresh_fonts();
			$css     = trim( (string) $this->generator->generate() );
			$runtime = $this->generator->runtime_rules();
			$hash    = hash( 'sha256', $css );
			$file    = '' === $css ? '' : 'typography-' . $hash . '.css';
			$saved   = '' !== $file && $this->write_file( $file, $css );
			$payload = array(
				'css'     => $css,
				'runtime' => $runtime,
				'hash'    => hash( 'sha256', $css . "\0" . wp_json_encode( $runtime ) ),
			);
			// Keep large CSS/runtime data out of the normal static-file request path.
			$payload_saved = update_option( self::PAYLOAD_OPTION, $payload, false ) || $payload === get_option( self::PAYLOAD_OPTION, array() );
			if ( ! $payload_saved ) {
				return false;
			}
			$record  = array(
				'plugin_version'  => SBTY_VERSION,
				'asset_directory' => self::DIRECTORY,
				'hash'            => $hash,
				'file'            => $saved ? $file : '',
				'has_css'         => '' !== $css,
				'has_runtime'     => ! empty( $runtime ),
				'payload_hash'    => $payload['hash'],
			);
			// Non-autoloaded option: use standard WordPress object-cache invalidation.
			$stored = update_option( self::OPTION, $record, false ) || $record === get_option( self::OPTION, array() );
			if ( ! $stored ) {
				return false;
			}
			$this->records[ get_current_blog_id() ] = $record;
			unset( $this->pending[ get_current_blog_id() ] );
			CSS_Version::bump( $css );
			if ( $previous !== $record ) {
				Cache_Compatibility::purge();
			}
			return true;
		} finally {
			$this->rebuilding = false;
		}
	}

	/** @param string $file Generated basename. @param string $css Validated CSS. @return bool */
	private function write_file( $file, $css ) {
		$paths = $this->paths();
		if ( empty( $paths ) || ! wp_mkdir_p( $paths['dir'] ) ) {
			return false;
		}
		$target = $paths['dir'] . $file;
		if ( is_readable( $target ) && hash_file( 'sha256', $target ) === hash( 'sha256', $css ) ) {
			return true;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		// Do not request FTP credentials or replace the global filesystem connection.
		if ( 'direct' !== get_filesystem_method( array(), $paths['dir'], true ) ) {
			return false;
		}
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$filesystem = new \WP_Filesystem_Direct( null );
		$temp       = $target . '.' . wp_generate_uuid4() . '.tmp';
		$mode       = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
		if ( ! $filesystem->put_contents( $temp, $css, $mode ) ) {
			$filesystem->delete( $temp );
			return false;
		}
		$saved = $filesystem->move( $temp, $target, true );
		if ( ! $saved ) {
			$filesystem->delete( $temp );
		}
		clearstatcache( true, $target );
		return $saved && is_readable( $target ) && hash_file( 'sha256', $target ) === hash( 'sha256', $css );
	}

	/** @return string */
	public function url() {
		$record = $this->record();
		if ( empty( $record['file'] ) || ! preg_match( '/^typography-[a-f0-9]{64}\.css$/D', $record['file'] ) ) {
			return '';
		}
		$paths = $this->paths();
		if ( empty( $paths ) || ! is_readable( $paths['dir'] . $record['file'] ) ) {
			return '';
		}
		return $paths['url'] . $record['file'];
	}

	/** Read the compiled fallback without disk writes on normal visits. @return array<string,mixed> */
	public function output() {
		if ( ! empty( $this->pending[ get_current_blog_id() ] ) ) {
			$this->rebuild();
		}
		$record = $this->record();
		if ( ! isset( $record['has_css'], $record['has_runtime'], $record['payload_hash'] ) ) {
			// Last-resort output if the options table itself could not store a snapshot.
			return array( 'url' => '', 'css' => $this->generator->generate(), 'runtime' => $this->generator->runtime_rules() );
		}
		$url = $this->url();
		// Standard/Strong mode with a healthy file reads no CSS body or runtime payload.
		if ( empty( $record['has_runtime'] ) && ( '' !== $url || empty( $record['has_css'] ) ) ) {
			return array( 'url' => $url, 'css' => '', 'runtime' => array() );
		}
		$payload = get_option( self::PAYLOAD_OPTION, array() );
		if ( ! is_array( $payload ) || ! isset( $payload['css'], $payload['runtime'], $payload['hash'] ) || $payload['hash'] !== $record['payload_hash'] ) {
			// Never pair metadata with a different/partially saved payload revision.
			$this->generator->refresh_fonts();
			return array( 'url' => '', 'css' => $this->generator->generate(), 'runtime' => $this->generator->runtime_rules() );
		}
		return array( 'url' => $url, 'css' => '' === $url ? $payload['css'] : '', 'runtime' => $payload['runtime'] );
	}
}
