<?php

declare(strict_types=1);

/**
 * One numeric value per post (first numeric meta/source value), range match in SQL.
 */
final class Query_Filter_Filter_Range extends Query_Filter_Filter {

	public function index_post( int $post_id ): array {
		foreach ( $this->source->get_values( $post_id ) as $val ) {
			$n = self::parse_numeric_string( (string) $val['value'] );
			if ( $n !== null ) {
				return [
					[
						'post_id'       => $post_id,
						'filter_name'   => $this->filter_name,
						'filter_value'  => self::normalize_number( $n ),
						'display_value' => self::normalize_number( $n ),
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
	 * Min/max of indexed values among candidate posts (for UI hints).
	 *
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
			"SELECT MIN(CAST(filter_value AS DECIMAL(20,6))) AS mn, MAX(CAST(filter_value AS DECIMAL(20,6))) AS mx
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

		$min   = $row['mn'];
		$max   = $row['mx'];
		$min_s = ( $min !== null && $min !== '' ) ? (string) $min : '';
		$max_s = ( $max !== null && $max !== '' ) ? (string) $max : '';

		return [
			'min' => $min_s,
			'max' => $max_s,
		];
	}

	/**
	 * @return int[]|null Null when both bounds empty (caller should skip constraint).
	 */
	public static function get_matching_post_ids( string $filter_name, ?string $min_raw, ?string $max_raw ): ?array {
		$min = self::parse_numeric_string( (string) ( $min_raw ?? '' ) );
		$max = self::parse_numeric_string( (string) ( $max_raw ?? '' ) );

		if ( $min === null && $max === null ) {
			return null;
		}

		global $wpdb;
		$table = Query_Filter_Indexer::table_name();
		$where = [ 'filter_name = %s' ];
		$bind  = [ $filter_name ];

		if ( $min !== null ) {
			$where[] = 'CAST(filter_value AS DECIMAL(20,6)) >= %f';
			$bind[]  = $min;
		}
		if ( $max !== null ) {
			$where[] = 'CAST(filter_value AS DECIMAL(20,6)) <= %f';
			$bind[]  = $max;
		}

		$sql = $wpdb->prepare(
			"SELECT DISTINCT post_id FROM {$table} WHERE " . implode( ' AND ', $where ),
			$bind
		);

		$col = $wpdb->get_col( $sql );

		return array_map( 'intval', $col );
	}

	private static function parse_numeric_string( string $s ): ?float {
		$t = trim( $s );
		if ( $t === '' ) {
			return null;
		}
		if ( ! is_numeric( $t ) ) {
			return null;
		}

		return (float) $t;
	}

	private static function normalize_number( float $n ): string {
		return rtrim( rtrim( sprintf( '%.6F', $n ), '0' ), '.' );
	}
}
