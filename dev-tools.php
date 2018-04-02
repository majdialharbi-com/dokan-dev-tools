<?php
/**
 * Plugin Name: Dokan - Dev Tools
 * Description: Development tools for Dokan
 * Plugin URI: https://wedevs.com/dokan/
 * Author: Tareq Hasan
 * Author URI: https://tareq.co
 * Version: 1.0
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) or exit;

add_action( 'plugins_loaded', function() {

    if ( ! defined( 'DOKAN_DIR' ) ) {
        return;
    }

    require_once __DIR__ . '/includes/class-admin-bar.php';
    require_once __DIR__ . '/includes/class-cleaner.php';
    require_once __DIR__ . '/includes/class-seeder.php';

    if ( is_admin() ) {
        require_once __DIR__ . '/includes/class-order-info.php';
    }

    new WeDevs\Dokan\DevTools\Admin_Bar();
    new WeDevs\Dokan\DevTools\Cleaner();
    new WeDevs\Dokan\DevTools\Seeder();

    if ( is_admin() ) {
        new WeDevs\Dokan\DevTools\Order_Info();
    }

    if ( class_exists( 'WP_CLI' ) ) {
        require_once __DIR__ . '/includes/cli/class-importer.php';
    }
} );