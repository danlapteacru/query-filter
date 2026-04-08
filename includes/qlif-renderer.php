<?php

declare(strict_types=1);

final class QLIF_Renderer {

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
		array $search_args = array(),
		array $sort_args = array(),
	): array {
		$per_page     = (int) get_option( 'posts_per_page', 10 );
		$prepared     = $this->prepare_result_post_ids( $post_ids, $search_args, $sort_args );
		$total        = count( $prepared );
		$pager        = new QLIF_Filter_Pager();
		$pager_result = $pager->compute( $total, $per_page, $page );
		$pages        = $pager_result['pages'];
		$current_page = $pager_result['current_page'];

		// Prefer full core/query render (matches theme post template). Uses theme templates
		// when the query lives in home.html / page.html etc., not in post_content.
		$html = $this->render_via_query_block( $query_id, $page_id, $prepared, $current_page, $per_page );
		if ( $html !== null ) {
			return array(
				'results_html' => $html,
				'total'        => $total,
				'pages'        => $pages,
			);
		}

		// Fallback: simple WP_Query rendering.
		$html = $this->render_simple( $prepared, $current_page, $per_page );

		return array(
			'results_html' => $html,
			'total'        => $total,
			'pages'        => $pages,
		);
	}

	/**
	 * Apply text search and sort across the full candidate set, then paginate.
	 * Previously we sliced IDs first, so order/search only applied within one page.
	 *
	 * @param int[]                 $post_ids
	 * @param array<string, string> $search_args
	 * @param array<string, string> $sort_args
	 * @return int[]
	 */
	private function prepare_result_post_ids( array $post_ids, array $search_args, array $sort_args ): array {
		if ( $post_ids === array() ) {
			return array();
		}
		$orderby     = $sort_args['orderby'] ?? 'date';
		$order       = $sort_args['order'] ?? 'DESC';
		$args        = array(
			'post__in'            => $post_ids,
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => $orderby,
			'order'               => $order,
		);
		$search_term = trim( (string) ( $search_args['s'] ?? '' ) );
		if ( $search_term !== '' ) {
			// phpcs:disable WordPress.WP.CapitalPDangit.MisspelledInText -- slug from QLIF_Filter_Search.
			$source = (string) ( $search_args['source'] ?? 'wordpress' );
			// phpcs:enable WordPress.WP.CapitalPDangit.MisspelledInText
			if ( $source === 'searchwp' && class_exists( 'SWP_Query', false ) ) {
				return $this->prepare_result_post_ids_searchwp( $post_ids, $search_term, $search_args, $sort_args );
			}
			$args['s'] = $search_term;
		}
		$query = new \WP_Query( $args );
		/** @var int[]|string[] $posts */
		$posts = $query->posts;
		$ids   = array_map( 'intval', $posts );

		return array_values( array_unique( $ids ) );
	}

	/**
	 * SearchWP: run {@see \SWP_Query} over facet candidate IDs (like FacetWP + SearchWP).
	 *
	 * @param int[]                   $post_ids
	 * @param array<string, string>   $search_args
	 * @param array<string, string>   $sort_args
	 * @return int[]
	 */
	private function prepare_result_post_ids_searchwp( array $post_ids, string $search_term, array $search_args, array $sort_args ): array {
		$engine = (string) ( $search_args['searchwp_engine'] ?? 'default' );
		if ( $engine === '' ) {
			$engine = 'default';
		}
		$orderby_in  = $sort_args['orderby'] ?? 'date';
		$order       = $sort_args['order'] ?? 'DESC';
		$swp_orderby = 'relevance';
		if ( in_array( $orderby_in, [ 'date', 'post_date' ], true ) ) {
			$swp_orderby = 'date';
		} elseif ( $orderby_in === 'rand' ) {
			$swp_orderby = 'rand';
		}

		$swp_args = array(
			's'           => $search_term,
			'engine'      => $engine,
			'post__in'    => $post_ids,
			'fields'      => 'ids',
			'nopaging'    => true,
			'post_status' => array( 'publish' ),
			'orderby'     => $swp_orderby,
			'order'       => $order,
		);

		$swp = new \SWP_Query( $swp_args );
		/** @var int[]|string[]|mixed $posts */
		$posts = $swp->posts;
		if ( ! is_array( $posts ) ) {
			return array();
		}

		return array_values( array_unique( array_map( 'intval', $posts ) ) );
	}

	/**
	 * Read the first nested Filter: Search block attributes (source / SearchWP engine).
	 *
	 * @return array{searchSource: string, searchwpEngine: string}
	 */
	public static function parse_search_config_from_container( ?\WP_Block $block ): array {
		// phpcs:disable WordPress.WP.CapitalPDangit.MisspelledInText -- block attribute default slug.
		$defaults = array(
			'searchSource'   => 'wordpress',
			'searchwpEngine' => 'default',
		);
		// phpcs:enable WordPress.WP.CapitalPDangit.MisspelledInText
		if ( ! $block instanceof \WP_Block ) {
			return $defaults;
		}
		$found = self::find_first_inner_block_named( $block, 'query-filter/filter-search' );
		if ( $found === null ) {
			return $defaults;
		}
		$attrs = $found->attributes;
		// phpcs:disable WordPress.WP.CapitalPDangit.MisspelledInText -- block attribute slug.
		$src = isset( $attrs['searchSource'] ) && $attrs['searchSource'] === 'searchwp' ? 'searchwp' : 'wordpress';
		// phpcs:enable WordPress.WP.CapitalPDangit.MisspelledInText
		$eng = isset( $attrs['searchwpEngine'] ) && is_string( $attrs['searchwpEngine'] ) && $attrs['searchwpEngine'] !== ''
			? sanitize_text_field( $attrs['searchwpEngine'] )
			: 'default';
		if ( $eng === '' ) {
			$eng = 'default';
		}
		if ( ! preg_match( '/^[a-z0-9_-]+$/i', $eng ) ) {
			$eng = 'default';
		}

		return array(
			'searchSource'   => $src,
			'searchwpEngine' => $eng,
		);
	}

	private static function find_first_inner_block_named( \WP_Block $block, string $name ): ?\WP_Block {
		foreach ( $block->inner_blocks as $inner ) {
			if ( $inner->name === $name ) {
				return $inner;
			}
			$nested = self::find_first_inner_block_named( $inner, $name );
			if ( $nested !== null ) {
				return $nested;
			}
		}

		return null;
	}

	/**
	 * @param int[] $ordered_post_ids
	 */
	private function render_via_query_block(
		int $query_id,
		int $page_id,
		array $ordered_post_ids,
		int $page,
		int $per_page,
	): ?string {
		$blocks      = self::get_page_block_tree( $page_id, $query_id );
		$query_block = $this->locate_query_block( $blocks, $query_id );
		if ( ! $query_block ) {
			return null;
		}

		$paged_ids = array_slice( $ordered_post_ids, ( $page - 1 ) * $per_page, $per_page );

		// Inherited Query Loops skip build_query_vars_from_query_block(), so
		// query_loop_block_query_vars never runs and post__in is never applied (empty list in REST).
		$to_render = self::query_block_force_non_inherit( $query_block );

		$filter_fn = function ( array $query_vars ) use ( $paged_ids ): array {
			foreach ( array(
				'category__in',
				'category__not_in',
				'category__and',
				'cat',
				'category_name',
				'tag__in',
				'tag__not_in',
				'tag__and',
				'tag_slug__in',
				'tag_slug__and',
				'tax_query',
				'author',
				'author__in',
				'author__not_in',
				'author_name',
				'meta_query',
				'post_name__in',
				'name',
				'pagename',
				'attachment_id',
			) as $key ) {
				unset( $query_vars[ $key ] );
			}
			$query_vars['post__in'] = $paged_ids ?: array( 0 );
			$query_vars['orderby']  = 'post__in';
			// Slice is already paginated in PHP; avoid a second LIMIT/OFFSET on this ID set.
			$query_vars['posts_per_page']      = -1;
			$query_vars['paged']               = 1;
			$query_vars['post_status']         = 'publish';
			$query_vars['ignore_sticky_posts'] = true;
			unset( $query_vars['offset'], $query_vars['s'], $query_vars['search'] );

			return $query_vars;
		};

		add_filter( 'query_loop_block_query_vars', $filter_fn, 999 );
		$html = render_block( $to_render );
		remove_filter( 'query_loop_block_query_vars', $filter_fn, 999 );

		return $html;
	}

	/**
	 * @param array<string, mixed> $query_block
	 * @return array<string, mixed>
	 */
	private static function query_block_force_non_inherit( array $query_block ): array {
		if ( ( $query_block['blockName'] ?? '' ) !== 'core/query' ) {
			return $query_block;
		}
		$copy             = $query_block;
		$attrs            = is_array( $copy['attrs'] ?? null ) ? $copy['attrs'] : array();
		$query            = is_array( $attrs['query'] ?? null ) ? $attrs['query'] : array();
		$query['inherit'] = false;
		$attrs['query']   = $query;
		$copy['attrs']    = $attrs;

		return $copy;
	}

	/**
	 * Match the Query Loop block used for filtering and DOM targeting.
	 *
	 * When $query_id is 0 (unset in Filter Container), use the first core/query in the document.
	 *
	 * @param array<int, array<string, mixed>> $blocks
	 * @return array<string, mixed>|null
	 */
	public function locate_query_block( array $blocks, int $query_id ): ?array {
		$exact = $this->find_query_block_by_exact_id( $blocks, $query_id );
		if ( $exact !== null ) {
			return $exact;
		}
		if ( $query_id === 0 ) {
			return $this->find_first_core_query_block( $blocks );
		}
		return null;
	}

	/**
	 * Resolve the Query Loop's queryId for data-query-filter-query and REST (e.g. when Filter Container is 0).
	 */
	public static function resolve_document_query_loop_id( int $page_id, int $requested_query_id ): int {
		if ( $page_id <= 0 ) {
			return $requested_query_id;
		}
		$post = get_post( $page_id );
		if ( ! $post instanceof \WP_Post ) {
			return $requested_query_id;
		}
		$renderer = new self();
		$block    = $renderer->locate_query_block( self::get_page_block_tree( $page_id, $requested_query_id ), $requested_query_id );
		if ( $block === null ) {
			return $requested_query_id;
		}
		return (int) ( $block['attrs']['queryId'] ?? 0 );
	}

	/**
	 * Blocks to search for core/query: page body first, then block theme templates
	 * (query often lives in page.html / home.html, not in the page post).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_page_block_tree( int $page_id, int $query_id = 0 ): array {
		$renderer = new self();
		if ( $page_id > 0 ) {
			$post = get_post( $page_id );
			if ( $post instanceof \WP_Post ) {
				$from_page = parse_blocks( $post->post_content );
				if ( $renderer->locate_query_block( $from_page, $query_id ) !== null ) {
					return $from_page;
				}
				if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
					foreach ( self::block_template_candidates_for_post( $post ) as $template_id ) {
						$tpl = get_block_template( $template_id );
						if ( ! $tpl || $tpl->content === '' ) {
							continue;
						}
						$tree = parse_blocks( $tpl->content );
						if ( $renderer->locate_query_block( $tree, $query_id ) !== null ) {
							return $tree;
						}
					}
				}
				$scanned = self::find_block_tree_from_theme_scan( $renderer, $query_id );
				if ( $scanned !== null ) {
					return $scanned;
				}

				return $from_page;
			}
		}

		$scanned = self::find_block_tree_from_theme_scan( $renderer, $query_id );
		if ( $scanned !== null ) {
			return $scanned;
		}

		return self::get_fallback_theme_block_tree( $query_id );
	}

	/**
	 * Last resort: any theme template that contains the requested Query Loop.
	 *
	 * @return array<int, array<string, mixed>>|null
	 */
	private static function find_block_tree_from_theme_scan( self $renderer, int $query_id ): ?array {
		if ( ! function_exists( 'get_block_templates' ) || ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return null;
		}
		$templates = get_block_templates( array( 'theme' => get_stylesheet() ) );
		if ( ! is_array( $templates ) ) {
			return null;
		}
		foreach ( $templates as $tpl ) {
			if ( ! is_object( $tpl ) || $tpl->content === '' ) {
				continue;
			}
			$tree = parse_blocks( $tpl->content );
			if ( $renderer->locate_query_block( $tree, $query_id ) !== null ) {
				return $tree;
			}
		}

		return null;
	}

	/**
	 * @return string[] Theme-relative template ids, e.g. theme//page-slug.
	 */
	private static function block_template_candidates_for_post( \WP_Post $post ): array {
		$stylesheet = get_stylesheet();
		$prefix     = $stylesheet . '//';
		$out        = array();
		if ( $post->post_type === 'page' ) {
			$assigned = get_page_template_slug( $post );
			if ( is_string( $assigned ) && $assigned !== '' && $assigned !== 'default' ) {
				$slug = str_replace( '\\', '/', $assigned );
				$slug = preg_replace( '/\.html$/i', '', $slug );
				$slug = $slug !== null ? ltrim( $slug, '/' ) : '';
				if ( $slug !== '' && $slug !== 'default' ) {
					$out[] = $prefix . $slug;
				}
			}
			if ( $post->post_name !== '' ) {
				$out[] = $prefix . 'page-' . $post->post_name;
			}
			$out[] = $prefix . 'page-' . $post->ID;
			$out[] = $prefix . 'page';
		} else {
			$pt = $post->post_type;
			if ( $post->post_name !== '' ) {
				$out[] = $prefix . 'single-' . $pt . '-' . $post->post_name;
			}
			$out[] = $prefix . 'single-' . $pt;
			$out[] = $prefix . 'single';
		}
		$page_on_front  = (int) get_option( 'page_on_front' );
		$page_for_posts = (int) get_option( 'page_for_posts' );
		if ( $page_on_front === (int) $post->ID ) {
			$out[] = $prefix . 'front-page';
		}
		if ( $page_for_posts === (int) $post->ID ) {
			$out[] = $prefix . 'home';
		}
		$out[] = $prefix . 'singular';
		$out[] = $prefix . 'index';

		return array_values( array_unique( $out ) );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_fallback_theme_block_tree( int $query_id ): array {
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return array();
		}
		$renderer   = new self();
		$stylesheet = get_stylesheet() . '//';
		foreach ( array( 'home', 'front-page', 'index' ) as $slug ) {
			$tpl = get_block_template( $stylesheet . $slug );
			if ( ! $tpl || $tpl->content === '' ) {
				continue;
			}
			$tree = parse_blocks( $tpl->content );
			if ( $renderer->locate_query_block( $tree, $query_id ) !== null ) {
				return $tree;
			}
		}

		return array();
	}

	/**
	 * @param array<int, array<string, mixed>> $blocks
	 * @return array<string, mixed>|null
	 */
	private function find_query_block_by_exact_id( array $blocks, int $query_id ): ?array {
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] === 'core/query' && (int) ( $block['attrs']['queryId'] ?? 0 ) === $query_id ) {
				return $block;
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = $this->find_query_block_by_exact_id( $block['innerBlocks'], $query_id );
				if ( $found !== null ) {
					return $found;
				}
			}
		}
		return null;
	}

	/**
	 * @param array<int, array<string, mixed>> $blocks
	 * @return array<string, mixed>|null
	 */
	private function find_first_core_query_block( array $blocks ): ?array {
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] === 'core/query' ) {
				return $block;
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = $this->find_first_core_query_block( $block['innerBlocks'] );
				if ( $found !== null ) {
					return $found;
				}
			}
		}
		return null;
	}

	/**
	 * @param int[] $ordered_post_ids
	 */
	private function render_simple(
		array $ordered_post_ids,
		int $page,
		int $per_page,
	): string {
		$paged_ids = array_slice( $ordered_post_ids, ( $page - 1 ) * $per_page, $per_page );

		$args = array(
			'post__in'            => $paged_ids ?: array( 0 ),
			'orderby'             => 'post__in',
			'posts_per_page'      => $per_page,
			'ignore_sticky_posts' => true,
			'post_status'         => 'publish',
		);

		$query = new \WP_Query( $args );

		ob_start();
		echo '<ul class="wp-block-post-template is-layout-flow wp-block-post-template-is-layout-flow">';
		while ( $query->have_posts() ) {
			$query->the_post();
			printf(
				'<li class="wp-block-post"><h3 class="wp-block-post-title">%s</h3></li>',
				esc_html( get_the_title() )
			);
		}
		echo '</ul>';
		wp_reset_postdata();

		return ob_get_clean() ?: '';
	}
}
