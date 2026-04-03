<?php

declare(strict_types=1);

class EndToEndTest extends WP_UnitTestCase {

    public function set_up(): void {
        parent::set_up();
        Query_Filter_Indexer::create_table();
        Query_Filter_Plugin::instance()->configure_indexer();
    }

    public function test_full_flow_index_and_query(): void {
        $shoes = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        $hats  = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Hats']);

        $p1 = self::factory()->post->create(['post_title' => 'Shoe Post', 'post_status' => 'publish']);
        $p2 = self::factory()->post->create(['post_title' => 'Hat Post', 'post_status' => 'publish']);
        wp_set_object_terms($p1, [$shoes->term_id], 'category');
        wp_set_object_terms($p2, [$hats->term_id], 'category');

        $indexer = Query_Filter_Plugin::instance()->get_indexer();
        $indexer->index_post($p1);
        $indexer->index_post($p2);

        do_action('rest_api_init');

        $request = new WP_REST_Request('POST', '/query-filter/v1/results');
        $request->set_body(wp_json_encode([
            'queryId' => 1,
            'pageId'  => 0,
            'filters' => ['category' => [$shoes->slug]],
            'page'    => 1,
        ]));
        $request->set_header('Content-Type', 'application/json');

        $response = rest_do_request($request);
        $data = $response->get_data();

        $this->assertSame(200, $response->get_status());
        $this->assertSame(1, $data['total']);
        $this->assertStringContainsString('Shoe Post', $data['results_html']);
        $this->assertStringNotContainsString('Hat Post', $data['results_html']);

        // Verify filter counts.
        $this->assertArrayHasKey('category', $data['filters']);
    }
}
