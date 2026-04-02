<?php

declare(strict_types=1);

class QueryEngineTest extends WP_UnitTestCase {

	private Query_Filter_Indexer $indexer;

	public function set_up(): void {
		parent::set_up();
		Query_Filter_Indexer::create_table();

		$this->indexer = new Query_Filter_Indexer();
		$cat_source = new Query_Filter_Source_Taxonomy('category');
		$this->indexer->register_filter(new Query_Filter_Filter_Checkboxes('category', $cat_source));
	}

	public function test_or_logic_returns_posts_matching_any_value(): void {
		$shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
		$boots = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Boots']);
		$hats  = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Hats']);

		$p1 = self::factory()->post->create();
		wp_set_object_terms($p1, [$shoes->term_id], 'category');
		$this->indexer->index_post($p1);

		$p2 = self::factory()->post->create();
		wp_set_object_terms($p2, [$boots->term_id], 'category');
		$this->indexer->index_post($p2);

		$p3 = self::factory()->post->create();
		wp_set_object_terms($p3, [$hats->term_id], 'category');
		$this->indexer->index_post($p3);

		$engine = new Query_Filter_Query_Engine();
		$result = $engine->get_post_ids(
			['category' => ['values' => [$shoes->slug, $boots->slug], 'logic' => 'OR']],
		);

		sort($result);
		$expected = [$p1, $p2];
		sort($expected);
		$this->assertSame($expected, $result);
	}

	public function test_and_logic_returns_posts_matching_all_values(): void {
		$shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
		$red   = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Red']);

		$p1 = self::factory()->post->create();
		wp_set_object_terms($p1, [$shoes->term_id, $red->term_id], 'category');
		$this->indexer->index_post($p1);

		$p2 = self::factory()->post->create();
		wp_set_object_terms($p2, [$shoes->term_id], 'category');
		$this->indexer->index_post($p2);

		$engine = new Query_Filter_Query_Engine();
		$result = $engine->get_post_ids(
			['category' => ['values' => [$shoes->slug, $red->slug], 'logic' => 'AND']],
		);

		$this->assertSame([$p1], $result);
	}

	public function test_multiple_filters_intersect(): void {
		register_taxonomy('color', 'post');

		$shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
		$red   = self::factory()->term->create_and_get(['taxonomy' => 'color', 'name' => 'Red']);
		$blue  = self::factory()->term->create_and_get(['taxonomy' => 'color', 'name' => 'Blue']);

		$color_source = new Query_Filter_Source_Taxonomy('color');
		$this->indexer->register_filter(new Query_Filter_Filter_Checkboxes('color', $color_source));

		$p1 = self::factory()->post->create();
		wp_set_object_terms($p1, [$shoes->term_id], 'category');
		wp_set_object_terms($p1, [$red->term_id], 'color');
		$this->indexer->index_post($p1);

		$p2 = self::factory()->post->create();
		wp_set_object_terms($p2, [$shoes->term_id], 'category');
		wp_set_object_terms($p2, [$blue->term_id], 'color');
		$this->indexer->index_post($p2);

		$engine = new Query_Filter_Query_Engine();
		$result = $engine->get_post_ids([
			'category' => ['values' => [$shoes->slug], 'logic' => 'OR'],
			'color'    => ['values' => [$red->slug], 'logic' => 'OR'],
		]);

		$this->assertSame([$p1], $result);
	}

	public function test_empty_filters_returns_all_indexed_post_ids(): void {
		$term = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Any']);
		$p1 = self::factory()->post->create();
		$p2 = self::factory()->post->create();
		wp_set_object_terms($p1, [$term->term_id], 'category');
		wp_set_object_terms($p2, [$term->term_id], 'category');
		$this->indexer->index_post($p1);
		$this->indexer->index_post($p2);

		$engine = new Query_Filter_Query_Engine();
		$result = $engine->get_post_ids([]);

		sort($result);
		$expected = [$p1, $p2];
		sort($expected);
		$this->assertSame($expected, $result);
	}
}
