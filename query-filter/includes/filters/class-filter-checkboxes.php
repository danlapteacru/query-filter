<?php

declare(strict_types=1);

/**
 * Checkbox-style filter: indexes one row per term assigned to a post.
 */
final class Query_Filter_Filter_Checkboxes extends Query_Filter_Filter {

	public function index_post(int $post_id): array {
		$source_values = $this->source->get_values($post_id);
		$rows = [];

		foreach ($source_values as $val) {
			$rows[] = [
				'post_id'       => $post_id,
				'filter_name'   => $this->filter_name,
				'filter_value'  => $val['value'],
				'display_value' => $val['label'],
				'term_id'       => $val['term_id'],
				'parent_id'     => $val['parent_id'],
				'depth'         => $val['depth'],
			];
		}

		return $rows;
	}

	public function load_values(array $params): array {
		// Implemented in Task 6.
		return [];
	}
}
