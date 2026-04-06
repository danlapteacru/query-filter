<?php

declare(strict_types=1);

/**
 * Checkbox-style filter: indexes one row per term assigned to a post.
 */
final class Query_Filter_Filter_Checkboxes extends Query_Filter_Filter {

	public function index_post( int $post_id ): array {
		$source_values = $this->source->get_values( $post_id );
		$rows          = array();

		foreach ( $source_values as $val ) {
			$rows[] = array(
				'post_id'       => $post_id,
				'filter_name'   => $this->filter_name,
				'filter_value'  => $val['value'],
				'display_value' => $val['label'],
				'term_id'       => $val['term_id'],
				'parent_id'     => $val['parent_id'],
				'depth'         => $val['depth'],
			);
		}

		return $rows;
	}

	public function load_values( array $params ): array {
		global $wpdb;
		$table = Query_Filter_Indexer::table_name();

		$where = 'WHERE filter_name = %s';
		$bind  = array( $this->filter_name );

		if ( ! empty( $params['post_ids'] ) ) {
			$id_placeholders = implode( ',', array_fill( 0, count( $params['post_ids'] ), '%d' ) );
			$where          .= " AND post_id IN ({$id_placeholders})";
			$bind            = array_merge( $bind, $params['post_ids'] );
		}

		$sql = $wpdb->prepare(
			"SELECT filter_value AS value, display_value AS label, COUNT(DISTINCT post_id) AS count
			 FROM {$table}
			 {$where}
			 GROUP BY filter_value, display_value
			 ORDER BY count DESC, display_value ASC",
			$bind
		);

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( ! $rows ) {
			return array();
		}

		return array_map(
			static fn( array $row ): array => array(
				'value' => $row['value'],
				'label' => $row['label'],
				'count' => (int) $row['count'],
			),
			$rows
		);
	}
}
