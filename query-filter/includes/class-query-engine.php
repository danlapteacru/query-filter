<?php

declare(strict_types=1);

final class Query_Filter_Query_Engine {

	/**
	 * Resolve matching post IDs from the index based on active filters.
	 *
	 * @param array<string, array{values: string[], logic: string}> $active_filters
	 * @return int[]
	 */
	public function get_post_ids(array $active_filters): array {
		global $wpdb;
		$table = Query_Filter_Indexer::table_name();

		if (empty($active_filters)) {
			$ids = $wpdb->get_col("SELECT DISTINCT post_id FROM {$table}");
			return array_map('intval', $ids);
		}

		$sets = [];

		foreach ($active_filters as $filter_name => $config) {
			$values = $config['values'];
			$logic  = strtoupper($config['logic'] ?? 'OR');

			if (empty($values)) {
				continue;
			}

			$placeholders = implode(',', array_fill(0, count($values), '%s'));
			$params = array_merge([$filter_name], $values);

			if ($logic === 'AND') {
				$sql = $wpdb->prepare(
					"SELECT post_id FROM {$table}
					 WHERE filter_name = %s AND filter_value IN ({$placeholders})
					 GROUP BY post_id
					 HAVING COUNT(DISTINCT filter_value) = %d",
					array_merge($params, [count($values)])
				);
			} else {
				$sql = $wpdb->prepare(
					"SELECT DISTINCT post_id FROM {$table}
					 WHERE filter_name = %s AND filter_value IN ({$placeholders})",
					$params
				);
			}

			$ids = $wpdb->get_col($sql);
			$sets[] = array_map('intval', $ids);
		}

		if (empty($sets)) {
			$ids = $wpdb->get_col("SELECT DISTINCT post_id FROM {$table}");
			return array_map('intval', $ids);
		}

		// Intersect across filters (AND between different filters).
		$result = $sets[0];
		for ($i = 1; $i < count($sets); $i++) {
			$result = array_values(array_intersect($result, $sets[$i]));
		}

		return $result;
	}
}
