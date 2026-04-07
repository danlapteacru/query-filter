<?php

declare(strict_types=1);

class FilterRangeQueryTest extends WP_UnitTestCase {

	private Query_Filter_Indexer $indexer;

	public function set_up(): void {
		parent::set_up();
		Query_Filter_Indexer::create_table();
		$this->indexer = new Query_Filter_Indexer();
		$meta_source = new Query_Filter_Source_Post_Meta( 'qf_test_price' );
		$this->indexer->register_filter( new Query_Filter_Filter_Range( 'qf_test_price', $meta_source ) );
	}

	public function test_numeric_range_matches_meta(): void {
		$p1 = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$p2 = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		update_post_meta( $p1, 'qf_test_price', '25' );
		update_post_meta( $p2, 'qf_test_price', '80' );
		$this->indexer->index_post( $p1 );
		$this->indexer->index_post( $p2 );

		$engine = new Query_Filter_Query_Engine();
		$ids    = $engine->get_post_ids(
			[
				'qf_test_price' => [
					'kind' => 'range',
					'min'  => '10',
					'max'  => '30',
				],
			],
			'AND'
		);
		sort( $ids );
		$this->assertSame( [ $p1 ], $ids );
	}

	public function test_date_range_matches_indexed_iso_dates(): void {
		$indexer     = new Query_Filter_Indexer();
		$date_source = new Query_Filter_Source_Post_Date();
		$indexer->register_filter( new Query_Filter_Filter_Date_Range( 'qf_pub', $date_source ) );

		$p1 = self::factory()->post->create(
			[
				'post_status' => 'publish',
				'post_date'   => '2024-06-15 12:00:00',
			]
		);
		$p2 = self::factory()->post->create(
			[
				'post_status' => 'publish',
				'post_date'   => '2025-01-10 12:00:00',
			]
		);
		$indexer->index_post( $p1 );
		$indexer->index_post( $p2 );

		$engine = new Query_Filter_Query_Engine();
		$ids    = $engine->get_post_ids(
			[
				'qf_pub' => [
					'kind'   => 'date_range',
					'after'  => '2024-01-01',
					'before' => '2024-12-31',
				],
			],
			'AND'
		);
		sort( $ids );
		$this->assertSame( [ $p1 ], $ids );
	}
}
