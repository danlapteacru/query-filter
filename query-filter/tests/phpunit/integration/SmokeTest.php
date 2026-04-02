<?php
// tests/phpunit/integration/SmokeTest.php
declare(strict_types=1);

class SmokeTest extends WP_UnitTestCase {

    public function test_plugin_loaded_in_wp_context(): void {
        $this->assertTrue(class_exists(Query_Filter_Plugin::class));
        $this->assertTrue(defined('QUERY_FILTER_VERSION'));
    }

    public function test_index_table_exists_after_activation(): void {
        global $wpdb;
        Query_Filter_Indexer::create_table();
        $table = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', Query_Filter_Indexer::table_name())
        );
        $this->assertSame(Query_Filter_Indexer::table_name(), $table);
    }
}
