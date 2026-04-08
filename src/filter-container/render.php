<?php

declare(strict_types=1);

/**
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content.
 * @var WP_Block $block      Block instance.
 */

$block_name = ( isset( $block ) && $block instanceof WP_Block ) ? $block->name : '';

$query_id_requested = (int) ( $attributes['queryId'] ?? 0 );
$filters_relationship = strtoupper( (string) ( $attributes['filtersRelationship'] ?? 'AND' ) );
if ( 'OR' !== $filters_relationship ) {
	$filters_relationship = 'AND';
}
$page_id = get_the_ID() ?: 0;
if ( $page_id <= 0 ) {
	$queried = get_queried_object_id();
	if ( $queried > 0 && get_post( $queried ) instanceof WP_Post ) {
		$page_id = $queried;
	}
}
$per_page           = (int) get_option( 'posts_per_page', 10 );

$effective_query_id = QLIF_Renderer::resolve_document_query_loop_id( $page_id, $query_id_requested );

$search_config = QLIF_Renderer::parse_search_config_from_container(
	isset( $block ) && $block instanceof WP_Block ? $block : null
);

$initial_total = 0;
$initial_pages = 0;
$indexer       = QLIF_Plugin::instance()->get_indexer();
if ( $indexer instanceof QLIF_Indexer ) {
	$engine          = new QLIF_Query_Engine();
	$initial_total   = count( $engine->get_post_ids( [] ) );
	$pager_calc      = new QLIF_Filter_Pager();
	$initial_pager   = $pager_calc->compute( $initial_total, $per_page, 1 );
	$initial_pages   = $initial_pager['pages'];
}

wp_interactivity_state(
	'query-filter',
	[
		'restUrl'                    => rest_url( 'query-filter/v1/results' ),
		'restNonce'                  => wp_create_nonce( 'wp_rest' ),
		'queryId'                    => $effective_query_id,
		'pageId'                     => $page_id,
		'perPage'                    => $per_page,
		'total'                      => $initial_total,
		'pages'                      => $initial_pages,
		'filtersRelationship'        => $filters_relationship,
		'initialFiltersRelationship' => $filters_relationship,
		'searchSource'               => $search_config['searchSource'],
		'searchwpEngine'             => $search_config['searchwpEngine'],
	]
);

$context = [
	'queryId' => $effective_query_id,
];

ob_start();
printf(
	'<div %s data-wp-interactive="query-filter" data-wp-class--query-filter-loading="state.loading" data-wp-context=\'%s\'>%s</div>',
	get_block_wrapper_attributes(),
	wp_json_encode( $context ),
	$content
);
echo QLIF_Render_Hooks::block_html( ob_get_clean(), $block_name, $attributes, $context );
