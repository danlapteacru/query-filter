<?php
// tests/phpunit/integration/SmokeTest.php
declare(strict_types=1);

class SmokeTest extends WP_UnitTestCase {

    public function test_plugin_loaded_in_wp_context(): void {
        $this->assertTrue(class_exists(QLIF_Plugin::class));
        $this->assertTrue(defined('QLIF_VERSION'));
    }

    public function test_index_table_exists_after_activation(): void {
        global $wpdb;
        QLIF_Indexer::create_table();
        $table = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', QLIF_Indexer::table_name())
        );
        $this->assertSame(QLIF_Indexer::table_name(), $table);
    }
}
