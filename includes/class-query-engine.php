<?php

declare(strict_types=1);

final class Query_Filter_Query_Engine {

	/**
	 * Combine per-filter post ID sets (AND = intersect, OR = union).
	 *
	 * @param array<int, int[]> $sets Non-empty list of post ID lists.
	 * @return int[]
	 */
	public static function combine_post_id_sets( array $sets, string $between_filters_logic ): array {
		if ( $sets === array() ) {
			return array();
		}

		$rel = strtoupper( $between_filters_logic );
		if ( $rel === 'OR' ) {
			$merged = array();
			foreach ( $sets as $set ) {
				$merged = array_merge( $merged, $set );
			}

			return array_values( array_unique( array_map( 'intval', $merged ) ) );
		}

		$result = $sets[0];
		for ( $i = 1, $n = count( $sets ); $i < $n; $i++ ) {
			$result = array_values( array_intersect( $result, $sets[ $i ] ) );
		}

		return $result;
	}

	/**
	 * Resolve matching post IDs from the index based on active filters.
	 *
	 * @param array<string, array<string, mixed>> $active_filters Normalized configs (see Query_Filter_Request).
	 * @return int[]
	 */
	public function get_post_ids( array $active_filters, string $between_filters_logic = 'AND' ): array {
		global $wpdb;
		$table = Query_Filter_Indexer::table_name();

		if ( empty( $active_filters ) ) {
			$ids = $wpdb->get_col( "SELECT DISTINCT post_id FROM {$table}" );
			return array_map( 'intval', $ids );
		}

		$sets = [];

		foreach ( $active_filters as $filter_name => $config ) {
			if ( ! is_array( $config ) ) {
				continue;
			}

			$kind = strtolower( (string) ( $config['kind'] ?? 'discrete' ) );

			if ( $kind === 'discrete' ) {
				$values = isset( $config['values'] ) && is_array( $config['values'] ) ? $config['values'] : [];
				$logic  = strtoupper( (string) ( $config['logic'] ?? 'OR' ) );

				if ( $values === [] ) {
					continue;
				}

				$placeholders = implode( ',', array_fill( 0, count( $values ), '%s' ) );
				$params       = array_merge( [ $filter_name ], $values );

				if ( $logic === 'AND' ) {
					$sql = $wpdb->prepare(
						"SELECT post_id FROM {$table}
						 WHERE filter_name = %s AND filter_value IN ({$placeholders})
						 GROUP BY post_id
						 HAVING COUNT(DISTINCT filter_value) = %d",
						array_merge( $params, [ count( $values ) ] )
					);
				} else {
					$sql = $wpdb->prepare(
						"SELECT DISTINCT post_id FROM {$table}
						 WHERE filter_name = %s AND filter_value IN ({$placeholders})",
						$params
					);
				}

				$ids    = $wpdb->get_col( $sql );
				$sets[] = array_map( 'intval', $ids );
				continue;
			}

			if ( $kind === 'range' ) {
				$min = isset( $config['min'] ) ? (string) $config['min'] : '';
				$max = isset( $config['max'] ) ? (string) $config['max'] : '';
				$ids = Query_Filter_Filter_Range::get_matching_post_ids( $filter_name, $min, $max );
				if ( $ids === null ) {
					continue;
				}
				$sets[] = $ids;
				continue;
			}

			if ( $kind === 'date_range' ) {
				$after  = isset( $config['after'] ) ? (string) $config['after'] : '';
				$before = isset( $config['before'] ) ? (string) $config['before'] : '';
				$ids    = Query_Filter_Filter_Date_Range::get_matching_post_ids( $filter_name, $after, $before );
				if ( $ids === null ) {
					continue;
				}
				$sets[] = $ids;
			}
		}

		if ( $sets === [] ) {
			$ids = $wpdb->get_col( "SELECT DISTINCT post_id FROM {$table}" );
			return array_map( 'intval', $ids );
		}

		return self::combine_post_id_sets( $sets, $between_filters_logic );
	}
}
