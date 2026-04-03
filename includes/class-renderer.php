<?php

declare(strict_types=1);

final class Query_Filter_Renderer {

	/**
	 * @param int[]                  $post_ids
	 * @param int                    $page
	 * @param int                    $query_id
	 * @param int                    $page_id
	 * @param array<string, string>  $search_args
	 * @param array<string, string>  $sort_args
	 * @return array{results_html: string, total: int, pages: int}
	 */
	public function render(
		array $post_ids,
		int $page,
		int $query_id,
		int $page_id,
		array $search_args = [],
		array $sort_args = [],
	): array {
		$per_page = (int) get_option('posts_per_page', 10);
		$total = count($post_ids);
		$pager = new Query_Filter_Filter_Pager();
		$pager_result = $pager->compute($total, $per_page, $page);
		$pages = $pager_result['pages'];
		$current_page = $pager_result['current_page'];

		// Try block-based rendering if pageId is provided.
		if ($page_id > 0) {
			$html = $this->render_via_query_block($query_id, $page_id, $post_ids, $current_page, $per_page, $search_args, $sort_args);
			if ($html !== null) {
				return [
					'results_html' => $html,
					'total'        => $total,
					'pages'        => $pages,
				];
			}
		}

		// Fallback: simple WP_Query rendering.
		$html = $this->render_simple($post_ids, $current_page, $per_page, $search_args, $sort_args);

		return [
			'results_html' => $html,
			'total'        => $total,
			'pages'        => $pages,
		];
	}

	private function render_via_query_block(
		int $query_id,
		int $page_id,
		array $post_ids,
		int $page,
		int $per_page,
		array $search_args,
		array $sort_args,
	): ?string {
		$page_post = get_post($page_id);
		if (! $page_post) {
			return null;
		}

		$blocks = parse_blocks($page_post->post_content);
		$query_block = $this->find_query_block($blocks, $query_id);
		if (! $query_block) {
			return null;
		}

		$paged_ids = array_slice($post_ids, ($page - 1) * $per_page, $per_page);

		$filter_fn = function (array $query_vars) use ($paged_ids, $page, $search_args, $sort_args): array {
			$query_vars['post__in'] = $paged_ids ?: [0];
			$query_vars['orderby'] = ! empty($sort_args['orderby']) ? $sort_args['orderby'] : ($paged_ids ? 'post__in' : 'date');
			$query_vars['order'] = $sort_args['order'] ?? 'DESC';
			$query_vars['paged'] = $page;
			if (! empty($search_args['s'])) {
				$query_vars['s'] = $search_args['s'];
			}
			return $query_vars;
		};

		add_filter('query_loop_block_query_vars', $filter_fn, 999);
		$html = render_block($query_block);
		remove_filter('query_loop_block_query_vars', $filter_fn, 999);

		return $html;
	}

	/**
	 * @param array<array<string, mixed>> $blocks
	 */
	private function find_query_block(array $blocks, int $query_id): ?array {
		foreach ($blocks as $block) {
			if ($block['blockName'] === 'core/query' && ($block['attrs']['queryId'] ?? 0) === $query_id) {
				return $block;
			}
			if (! empty($block['innerBlocks'])) {
				$found = $this->find_query_block($block['innerBlocks'], $query_id);
				if ($found !== null) {
					return $found;
				}
			}
		}
		return null;
	}

	private function render_simple(
		array $post_ids,
		int $page,
		int $per_page,
		array $search_args,
		array $sort_args,
	): string {
		$paged_ids = array_slice($post_ids, ($page - 1) * $per_page, $per_page);

		$args = array_merge([
			'post__in'            => $paged_ids ?: [0],
			'orderby'             => $paged_ids ? 'post__in' : 'date',
			'posts_per_page'      => $per_page,
			'ignore_sticky_posts' => true,
			'post_status'         => 'publish',
		], $search_args, $sort_args);

		$query = new \WP_Query($args);

		ob_start();
		while ($query->have_posts()) {
			$query->the_post();
			printf(
				'<li class="wp-block-post"><h3 class="wp-block-post-title">%s</h3></li>',
				esc_html(get_the_title())
			);
		}
		wp_reset_postdata();

		return ob_get_clean() ?: '';
	}
}
