<?php
// includes/sources/class-source.php
declare(strict_types=1);

abstract class QLIF_Source {

	/**
	 * Return indexable values for a post.
	 *
	 * Each element: ['value' => string, 'label' => string, 'term_id' => int, 'parent_id' => int, 'depth' => int]
	 *
	 * @return array<int, array{value: string, label: string, term_id: int, parent_id: int, depth: int}>
	 */
	abstract public function get_values( int $post_id ): array;
}
