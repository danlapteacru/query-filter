<?php

declare(strict_types=1);

class CheckboxesLoadValuesTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Query_Filter_Indexer::create_table();
	}

	public function test_load_values_returns_counts_for_matching_posts(): void {
		$shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
		$hats  = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Hats']);

		$indexer = new Query_Filter_Indexer();
		$source = new Query_Filter_Source_Taxonomy('category');
		$filter = new Query_Filter_Filter_Checkboxes('category', $source);
		$indexer->register_filter($filter);

		$p1 = self::factory()->post->create();
		$p2 = self::factory()->post->create();
		$p3 = self::factory()->post->create();
		wp_set_object_terms($p1, [$shoes->term_id], 'category');
		wp_set_object_terms($p2, [$shoes->term_id], 'category');
		wp_set_object_terms($p3, [$hats->term_id], 'category');
		$indexer->index_post($p1);
		$indexer->index_post($p2);
		$indexer->index_post($p3);

		$values = $filter->load_values(['post_ids' => [$p1, $p2, $p3]]);

		$this->assertCount(2, $values);
		$by_value = array_column($values, null, 'value');
		$this->assertSame(2, $by_value[$shoes->slug]['count']);
		$this->assertSame(1, $by_value[$hats->slug]['count']);
	}

	public function test_load_values_counts_scoped_to_given_post_ids(): void {
		$shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);

		$indexer = new Query_Filter_Indexer();
		$source = new Query_Filter_Source_Taxonomy('category');
		$filter = new Query_Filter_Filter_Checkboxes('category', $source);
		$indexer->register_filter($filter);

		$p1 = self::factory()->post->create();
		$p2 = self::factory()->post->create();
		wp_set_object_terms($p1, [$shoes->term_id], 'category');
		wp_set_object_terms($p2, [$shoes->term_id], 'category');
		$indexer->index_post($p1);
		$indexer->index_post($p2);

		$values = $filter->load_values(['post_ids' => [$p1]]);

		$by_value = array_column($values, null, 'value');
		$this->assertSame(1, $by_value[$shoes->slug]['count']);
	}
}
