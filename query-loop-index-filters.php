<?php
/**
 * Plugin Name:       Query Loop Index Filters
 * Description:       Index-backed filters for the core Query Loop.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            Query Loop Index Filters
 * License:           GPL-2.0-or-later
 * Text Domain:       query-loop-index-filters
 *
 * @package QLIF
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QLIF_VERSION', '0.1.0' );
define( 'QLIF_PLUGIN_FILE', __FILE__ );

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/qlif-plugin.php';

QLIF_Plugin::instance();
