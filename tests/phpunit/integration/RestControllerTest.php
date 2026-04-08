<?php

declare(strict_types=1);

class RestControllerTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		QLIF_Indexer::create_table();

		$indexer = new QLIF_Indexer();
		$source = new QLIF_Source_Taxonomy('category');
		$indexer->register_filter(new QLIF_Filter_Checkboxes('category', $source));
		QLIF_Plugin::instance()->set_indexer($indexer);

		do_action('rest_api_init');
	}

	public function test_results_endpoint_returns_valid_response(): void {
		$term = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
		$p1 = self::factory()->post->create(['post_title' => 'Post One', 'post_status' => 'publish']);
		wp_set_object_terms($p1, [$term->term_id], 'category');
		QLIF_Plugin::instance()->get_indexer()->index_post($p1);

		$request = new WP_REST_Request('POST', '/query-filter/v1/results');
		$request->set_body(wp_json_encode([
			'queryId' => 1,
			'pageId'  => 0,
			'filters' => ['category' => [$term->slug]],
			'page'    => 1,
		]));
		$request->set_header('Content-Type', 'application/json');

		$response = rest_do_request($request);
		$data = $response->get_data();

		$this->assertSame(200, $response->get_status());
		$this->assertArrayHasKey('results_html', $data);
		$this->assertArrayHasKey('filters', $data);
		$this->assertArrayHasKey('total', $data);
		$this->assertArrayHasKey('pages', $data);
		$this->assertSame(1, $data['total']);
	}

	public function test_results_endpoint_returns_filter_counts(): void {
		$shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
		$hats  = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Hats']);

		$indexer = QLIF_Plugin::instance()->get_indexer();

		$p1 = self::factory()->post->create(['post_status' => 'publish']);
		$p2 = self::factory()->post->create(['post_status' => 'publish']);
		wp_set_object_terms($p1, [$shoes->term_id], 'category');
		wp_set_object_terms($p2, [$hats->term_id], 'category');
		$indexer->index_post($p1);
		$indexer->index_post($p2);

		$request = new WP_REST_Request('POST', '/query-filter/v1/results');
		$request->set_body(wp_json_encode([
			'queryId' => 1,
			'pageId'  => 0,
			'filters' => [],
			'page'    => 1,
		]));
		$request->set_header('Content-Type', 'application/json');

		$response = rest_do_request($request);
		$data = $response->get_data();

		$this->assertArrayHasKey('category', $data['filters']);
		$this->assertCount(2, $data['filters']['category']);
	}

	public function test_results_endpoint_rejects_invalid_method(): void {
		$request = new WP_REST_Request('GET', '/query-filter/v1/results');
		$response = rest_do_request($request);
		$this->assertSame(404, $response->get_status());
	}
}
