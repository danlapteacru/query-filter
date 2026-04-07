<?php

declare(strict_types=1);

/**
 * One ISO date (Y-m-d) per post, compared as strings (chronological for ISO).
 */
final class Query_Filter_Filter_Date_Range extends Query_Filter_Filter {

	public function index_post( int $post_id ): array {
		foreach ( $this->source->get_values( $post_id ) as $val ) {
			$d = self::sanitize_ymd( (string) $val['value'] );
			if ( $d !== null ) {
				return [
					[
						'post_id'       => $post_id,
						'filter_name'   => $this->filter_name,
						'filter_value'  => $d,
						'display_value' => $d,
						'term_id'       => 0,
						'parent_id'     => 0,
						'depth'         => 0,
					],
				];
			}
		}

		return [];
	}

	/**
	 * @param array{post_ids?: int[], logic?: string} $params
	 * @return array<int, array{value: string, label: string, count: int}>
	 */
	public function load_values( array $params ): array {
		return [];
	}

	/**
	 * @param int[] $post_ids
	 * @return array{min: string, max: string}
	 */
	public function load_bounds( array $post_ids ): array {
		global $wpdb;
		$table = Query_Filter_Indexer::table_name();
		if ( $post_ids === [] ) {
			return [
				'min' => '',
				'max' => '',
			];
		}

		$post_ids = array_values( array_unique( array_map( 'intval', $post_ids ) ) );
		$holders  = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$sql      = $wpdb->prepare(
			"SELECT MIN(filter_value) AS mn, MAX(filter_value) AS mx
			 FROM {$table}
			 WHERE filter_name = %s AND post_id IN ({$holders})",
			array_merge( [ $this->filter_name ], $post_ids )
		);

		$row = $wpdb->get_row( $sql, ARRAY_A );
		if ( ! is_array( $row ) ) {
			return [
				'min' => '',
				'max' => '',
			];
		}

		$min = isset( $row['mn'] ) && is_string( $row['mn'] ) ? $row['mn'] : '';
		$max = isset( $row['mx'] ) && is_string( $row['mx'] ) ? $row['mx'] : '';

		return [
			'min' => $min,
			'max' => $max,
		];
	}

	/**
	 * @return int[]|null Null when both bounds empty.
	 */
	public static function get_matching_post_ids( string $filter_name, ?string $after_raw, ?string $before_raw ): ?array {
		$after  = self::sanitize_ymd( (string) ( $after_raw ?? '' ) );
		$before = self::sanitize_ymd( (string) ( $before_raw ?? '' ) );

		if ( $after === null && $before === null ) {
			return null;
		}

		global $wpdb;
		$table = Query_Filter_Indexer::table_name();
		$where = [ 'filter_name = %s' ];
		$bind  = [ $filter_name ];

		if ( $after !== null ) {
			$where[] = 'filter_value >= %s';
			$bind[]  = $after;
		}
		if ( $before !== null ) {
			$where[] = 'filter_value <= %s';
			$bind[]  = $before;
		}

		$sql = $wpdb->prepare(
			"SELECT DISTINCT post_id FROM {$table} WHERE " . implode( ' AND ', $where ),
			$bind
		);

		$col = $wpdb->get_col( $sql );

		return array_map( 'intval', $col );
	}

	private static function sanitize_ymd( string $s ): ?string {
		$t = trim( $s );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $t ) !== 1 ) {
			return null;
		}

		return $t;
	}
}
