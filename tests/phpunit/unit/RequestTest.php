<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase {

	public function test_from_array_parses_valid_payload(): void {
		$input = [
			'queryId' => 1,
			'pageId'  => 42,
			'filters' => [ 'category' => [ 'shoes', 'boots' ] ],
			'page'    => 2,
			'orderby' => 'price',
			'order'   => 'ASC',
			'search'  => 'running',
		];

		$request = Query_Filter_Request::from_array( $input );

		$this->assertSame( 1, $request->query_id );
		$this->assertSame( 42, $request->page_id );
		$this->assertSame(
			[
				'category' => [
					'kind'   => 'discrete',
					'values' => [ 'shoes', 'boots' ],
					'logic'  => 'OR',
				],
			],
			$request->filters
		);
		$this->assertSame( 'AND', $request->filters_relationship );
		$this->assertSame( 2, $request->page );
		$this->assertSame( 'price', $request->orderby );
		$this->assertSame( 'ASC', $request->order );
		$this->assertSame( 'running', $request->search );
	}

	public function test_from_array_uses_defaults_for_missing_fields(): void {
		$request = Query_Filter_Request::from_array( [ 'queryId' => 1, 'pageId' => 10 ] );

		$this->assertSame( [], $request->filters );
		$this->assertSame( 'AND', $request->filters_relationship );
		$this->assertSame( 1, $request->page );
		$this->assertSame( 'date', $request->orderby );
		$this->assertSame( 'DESC', $request->order );
		$this->assertSame( '', $request->search );
	}

	public function test_from_array_sanitizes_filter_values(): void {
		$input = [
			'queryId' => 1,
			'pageId'  => 10,
			'filters' => [ 'cat' => [ '<script>alert(1)</script>', 'shoes' ] ],
		];

		$request = Query_Filter_Request::from_array( $input );

		$this->assertSame(
			[
				'cat' => [
					'kind'   => 'discrete',
					'values' => [ 'alert(1)', 'shoes' ],
					'logic'  => 'OR',
				],
			],
			$request->filters
		);
	}

	public function test_from_array_rejects_non_array_filters(): void {
		$input  = [ 'queryId' => 1, 'pageId' => 10, 'filters' => 'bad' ];
		$request = Query_Filter_Request::from_array( $input );
		$this->assertSame( [], $request->filters );
	}

	public function test_from_array_parses_structured_filters_and_relationship(): void {
		$request = Query_Filter_Request::from_array(
			[
				'queryId'             => 1,
				'pageId'              => 2,
				'filtersRelationship' => 'OR',
				'filters'             => [
					'category' => [
						'values' => [ 'a', 'b' ],
						'logic'  => 'AND',
					],
					'color'    => [
						'values' => [ 'red' ],
						'logic'  => 'INVALID',
					],
				],
			]
		);

		$this->assertSame( 'OR', $request->filters_relationship );
		$this->assertSame(
			[
				'category' => [ 'kind' => 'discrete', 'values' => [ 'a', 'b' ], 'logic' => 'AND' ],
				'color'    => [ 'kind' => 'discrete', 'values' => [ 'red' ], 'logic' => 'OR' ],
			],
			$request->filters
		);
	}

	public function test_from_array_normalizes_lowercase_filters_relationship(): void {
		$request = Query_Filter_Request::from_array(
			[
				'queryId'             => 1,
				'pageId'              => 1,
				'filtersRelationship' => 'or',
				'filters'             => [],
			]
		);
		$this->assertSame( 'OR', $request->filters_relationship );
	}

	public function test_from_array_skips_associative_filter_without_values_key(): void {
		$request = Query_Filter_Request::from_array(
			[
				'queryId' => 1,
				'pageId'  => 1,
				'filters' => [
					'category' => [ 'unexpected' => 'x' ],
				],
			]
		);
		$this->assertSame( [], $request->filters );
	}

	public function test_from_array_parses_numeric_range(): void {
		$request = Query_Filter_Request::from_array(
			[
				'queryId' => 1,
				'pageId'  => 1,
				'filters' => [
					'price' => [ 'min' => '10', 'max' => '99' ],
				],
			]
		);
		$this->assertSame(
			[
				'price' => [ 'kind' => 'range', 'min' => '10', 'max' => '99' ],
			],
			$request->filters
		);
	}

	public function test_from_array_parses_open_numeric_range(): void {
		$request = Query_Filter_Request::from_array(
			[
				'queryId' => 1,
				'pageId'  => 1,
				'filters' => [
					'price' => [ 'max' => '50' ],
				],
			]
		);
		$this->assertSame(
			[
				'price' => [ 'kind' => 'range', 'min' => '', 'max' => '50' ],
			],
			$request->filters
		);
	}

	public function test_from_array_parses_date_range(): void {
		$request = Query_Filter_Request::from_array(
			[
				'queryId' => 1,
				'pageId'  => 1,
				'filters' => [
					'published' => [ 'after' => '2024-01-01', 'before' => '2024-12-31' ],
				],
			]
		);
		$this->assertSame(
			[
				'published' => [
					'kind'   => 'date_range',
					'after'  => '2024-01-01',
					'before' => '2024-12-31',
				],
			],
			$request->filters
		);
	}
}
