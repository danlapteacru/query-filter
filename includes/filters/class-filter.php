<?php

declare(strict_types=1);

/**
 * Abstract base class for all query filters.
 */
abstract class Query_Filter_Filter {

	public function __construct(
		protected readonly string $filter_name,
		protected readonly Query_Filter_Source $source,
	) {}

	public function get_name(): string {
		return $this->filter_name;
	}

	/**
	 * Return index rows for a post.
	 *
	 * @return array<int, array{post_id: int, filter_name: string, filter_value: string, display_value: string, term_id: int, parent_id: int, depth: int}>
	 */
	abstract public function index_post(int $post_id): array;

	/**
	 * Return available filter values with counts.
	 *
	 * @param array{post_ids?: int[], logic?: string} $params
	 * @return array<int, array{value: string, label: string, count: int}>
	 */
	abstract public function load_values(array $params): array;
}
