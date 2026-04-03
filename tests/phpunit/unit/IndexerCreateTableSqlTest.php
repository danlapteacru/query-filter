<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class IndexerCreateTableSqlTest extends TestCase {

	public function test_create_table_sql_contains_expected_columns_and_keys(): void {
		$sql = Query_Filter_Indexer::get_create_table_sql(
			'wp_query_filter_index',
			'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);

		$this->assertStringContainsString('post_id', $sql);
		$this->assertStringContainsString('filter_name', $sql);
		$this->assertStringContainsString('filter_value', $sql);
		$this->assertStringContainsString('display_value', $sql);
		$this->assertStringContainsString('term_id', $sql);
		$this->assertStringContainsString('parent_id', $sql);
		$this->assertStringContainsString('depth', $sql);
		$this->assertStringContainsString('PRIMARY KEY', $sql);
		$this->assertStringContainsString('post_id_idx', $sql);
		$this->assertStringContainsString('filter_name_idx', $sql);
		$this->assertStringContainsString('filter_name_value', $sql);
	}
}
