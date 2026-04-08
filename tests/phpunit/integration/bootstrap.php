<?php
// tests/phpunit/integration/bootstrap.php
declare(strict_types=1);

$_tests_dir = getenv('WP_TESTS_DIR');
if (! $_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (! file_exists($_tests_dir . '/includes/functions.php')) {
    throw new \RuntimeException("WordPress test suite not found at {$_tests_dir}");
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', static function (): void {
    require dirname(__DIR__, 2) . '/query-loop-index-filters.php';
});

require $_tests_dir . '/includes/bootstrap.php';
