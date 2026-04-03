<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap: minimal ABSPATH so the plugin header guard passes without full WordPress.
 */
if (! defined('ABSPATH')) {
	define('ABSPATH', sys_get_temp_dir() . '/query-filter-abspath-stub/');
}

$plugin_root = dirname(__DIR__, 2);

require $plugin_root . '/vendor/autoload.php';
require $plugin_root . '/query-filter.php';
