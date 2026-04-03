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

if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string {
        return strip_tags(trim($str));
    }
}
if (! function_exists('sanitize_key')) {
    function sanitize_key(string $key): string {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
    }
}
