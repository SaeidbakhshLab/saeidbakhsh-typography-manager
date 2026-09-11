<?php
/**
 * Plugin Name:       Saeidbakhsh Typography Manager
 * Description:       Manage typography for HTML, WordPress, and WooCommerce elements with local, remote, and system fonts.
 * Version:           2.3.16
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Ahmadreza Saeidbakhsh
 * Author URI:        https://profiles.wordpress.org/saeidbakhsh
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       saeidbakhsh-typography-manager
 *
 * @package SaeidbakhshTypographyManager
 */

defined( 'ABSPATH' ) || exit;

define( 'SBTY_VERSION', '2.3.16' );
define( 'SBTY_FILE', __FILE__ );
define( 'SBTY_PATH', plugin_dir_path( __FILE__ ) );
define( 'SBTY_URL', plugin_dir_url( __FILE__ ) );

require_once SBTY_PATH . 'includes/class-wordpress-font-library.php';
require_once SBTY_PATH . 'includes/class-font-repository.php';
require_once SBTY_PATH . 'includes/class-element-registry.php';
require_once SBTY_PATH . 'includes/class-css-generator.php';
require_once SBTY_PATH . 'includes/class-css-version.php';
require_once SBTY_PATH . 'includes/class-cdn-compatibility.php';
require_once SBTY_PATH . 'includes/class-cache-compatibility.php';
require_once SBTY_PATH . 'includes/class-css-cache.php';
require_once SBTY_PATH . 'includes/class-plugin.php';

register_activation_hook( SBTY_FILE, array( 'Saeidbakhsh\\Typography\\Plugin', 'activate' ) );

add_action( 'plugins_loaded', array( 'Saeidbakhsh\\Typography\\Plugin', 'boot' ) );
