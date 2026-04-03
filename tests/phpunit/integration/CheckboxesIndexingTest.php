<?php

declare(strict_types=1);

class CheckboxesIndexingTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Query_Filter_Indexer::create_table();
	}

	public function test_index_post_writes_rows_for_assigned_terms(): void {
		global $wpdb;

		$post_id = self::factory()->post->create();
		$shoes   = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
		$boots   = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Boots']);
		wp_set_object_terms($post_id, [$shoes->term_id, $boots->term_id], 'category');

		$source  = new Query_Filter_Source_Taxonomy('category');
		$filter  = new Query_Filter_Filter_Checkboxes('category', $source);
		$indexer = new Query_Filter_Indexer();
		$indexer->register_filter($filter);
		$indexer->index_post($post_id);

		$rows = $wpdb->get_results($wpdb->prepare(
			'SELECT filter_name, filter_value, display_value FROM %i WHERE post_id = %d ORDER BY filter_value',
			Query_Filter_Indexer::table_name(),
			$post_id
		));

		$this->assertCount(2, $rows);
		$this->assertSame('category', $rows[0]->filter_name);
		$this->assertSame($boots->slug, $rows[0]->filter_value);
		$this->assertSame('Boots', $rows[0]->display_value);
		$this->assertSame($shoes->slug, $rows[1]->filter_value);
	}

	public function test_delete_for_post_removes_all_rows(): void {
		global $wpdb;

		$post_id = self::factory()->post->create();
		$term    = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Test']);
		wp_set_object_terms($post_id, [$term->term_id], 'category');

		$source  = new Query_Filter_Source_Taxonomy('category');
		$filter  = new Query_Filter_Filter_Checkboxes('category', $source);
		$indexer = new Query_Filter_Indexer();
		$indexer->register_filter($filter);
		$indexer->index_post($post_id);

		$count_before = (int) $wpdb->get_var($wpdb->prepare(
			'SELECT COUNT(*) FROM %i WHERE post_id = %d',
			Query_Filter_Indexer::table_name(),
			$post_id
		));
		$this->assertGreaterThan(0, $count_before);

		$indexer->delete_for_post($post_id);

		$count_after = (int) $wpdb->get_var($wpdb->prepare(
			'SELECT COUNT(*) FROM %i WHERE post_id = %d',
			Query_Filter_Indexer::table_name(),
			$post_id
		));
		$this->assertSame(0, $count_after);
	}

	public function test_reindex_replaces_old_rows(): void {
		global $wpdb;

		$post_id = self::factory()->post->create();
		$shoes   = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
		$boots   = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Boots']);

		$source  = new Query_Filter_Source_Taxonomy('category');
		$filter  = new Query_Filter_Filter_Checkboxes('category', $source);
		$indexer = new Query_Filter_Indexer();
		$indexer->register_filter($filter);

		wp_set_object_terms($post_id, [$shoes->term_id, $boots->term_id], 'category');
		$indexer->index_post($post_id);

		wp_set_object_terms($post_id, [$shoes->term_id], 'category');
		$indexer->index_post($post_id);

		$count = (int) $wpdb->get_var($wpdb->prepare(
			'SELECT COUNT(*) FROM %i WHERE post_id = %d',
			Query_Filter_Indexer::table_name(),
			$post_id
		));
		$this->assertSame(1, $count);
	}
}
