<?php
/**
 * Plugin Name:       Query Filter
 * Description:       Index-powered filtering for Query Loop blocks.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            Query Filter
 * License:           GPL-2.0-or-later
 * Text Domain:       query-filter
 *
 * @package Query_Filter
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

define('QUERY_FILTER_VERSION', '0.1.0');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/class-plugin.php';

Query_Filter_Plugin::instance();
