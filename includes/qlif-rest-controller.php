<?php

declare(strict_types=1);

final class QLIF_Rest_Controller {

	public const NAMESPACE = 'query-filter/v1';
	public const ROUTE     = '/results';

	public static function register(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function handle( \WP_REST_Request $wp_request ): \WP_REST_Response {
		$body = $wp_request->get_json_params();
		if ( ! is_array( $body ) ) {
			return new \WP_REST_Response( array( 'error' => 'Invalid JSON' ), 400 );
		}

		$request = QLIF_Request::from_array( $body );
		$page_id = $request->page_id;
		if ( $page_id <= 0 ) {
			$referer = $wp_request->get_header( 'referer' );
			if ( is_string( $referer ) && $referer !== '' ) {
				$from_ref = url_to_postid( esc_url_raw( $referer ) );
				if ( $from_ref > 0 ) {
					$page_id = $from_ref;
				}
			}
		}
		$plugin  = QLIF_Plugin::instance();
		$indexer = $plugin->get_indexer();

		if ( ! $indexer ) {
			return new \WP_REST_Response( array( 'error' => 'Indexer not configured' ), 500 );
		}

		// Keep only configs whose filter type matches (avoid mismatched payloads).
		$active_filters = [];
		foreach ( $request->filters as $filter_name => $config ) {
			if ( ! is_array( $config ) ) {
				continue;
			}
			$filter = $indexer->get_filter( $filter_name );
			if ( $filter === null ) {
				continue;
			}
			$kind = strtolower( (string) ( $config['kind'] ?? '' ) );
			if ( $kind === 'discrete' && $filter instanceof QLIF_Filter_Checkboxes ) {
				$values = isset( $config['values'] ) && is_array( $config['values'] ) ? $config['values'] : [];
				$logic  = strtoupper( (string) ( $config['logic'] ?? 'OR' ) );
				if ( $logic !== 'AND' ) {
					$logic = 'OR';
				}
				if ( $values !== [] ) {
					$active_filters[ $filter_name ] = [
						'kind'   => 'discrete',
						'values' => $values,
						'logic'  => $logic,
					];
				}
			}
		}

		// Resolve post IDs.
		$engine       = new QLIF_Query_Engine();
		$all_post_ids = $engine->get_post_ids( $active_filters, $request->filters_relationship );

		// Apply search filter.
		$search_filter = new QLIF_Filter_Search();
		$search_args   = $search_filter->get_query_args( $request->search, $request->search_source, $request->searchwp_engine );

		// Apply sort.
		$sort_filter = new QLIF_Filter_Sort();
		$sort_args   = $sort_filter->get_query_args( $request->orderby, $request->order );

		// Render results.
		$renderer      = new QLIF_Renderer();
		$render_result = $renderer->render(
			post_ids:    $all_post_ids,
			page:        $request->page,
			query_id:    $request->query_id,
			page_id:     $page_id,
			search_args: $search_args,
			sort_args:   $sort_args,
		);

		// Load filter values (counts scoped to matching posts).
		$filter_states = [];
		foreach ( $indexer->get_filters() as $name => $filter ) {
			if ( $filter instanceof QLIF_Filter_Checkboxes ) {
				$filter_states[ $name ] = $filter->load_values(
					[
						'post_ids' => $all_post_ids,
					]
				);
			}
		}

		$data = array(
			'results_html' => $render_result['results_html'],
			'filters'      => $filter_states,
			'total'        => $render_result['total'],
			'pages'        => $render_result['pages'],
		);

		/**
		 * REST JSON payload for POST query-filter/v1/results.
		 *
		 * @param array{results_html: string, filters: array<string, mixed>, total: int, pages: int} $data
		 */
		$filtered = apply_filters( 'query_filter/rest/response', $data );
		if ( is_array( $filtered ) ) {
			$data = $filtered;
		}

		return new \WP_REST_Response( $data );
	}
}
