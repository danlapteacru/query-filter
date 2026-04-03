<?php

declare(strict_types=1);

/**
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content.
 * @var WP_Block $block      Block instance.
 */

$query_id = $attributes['queryId'] ?? 0;
$page_id  = get_the_ID() ?: 0;
$per_page = (int) get_option( 'posts_per_page', 10 );

wp_interactivity_state( 'query-filter', [
    'restUrl'   => rest_url( 'query-filter/v1/results' ),
    'restNonce' => wp_create_nonce( 'wp_rest' ),
    'queryId'   => $query_id,
    'pageId'    => $page_id,
    'perPage'   => $per_page,
] );

$context = [
    'queryId' => $query_id,
];

printf(
    '<div %s data-wp-interactive="query-filter" data-wp-context=\'%s\'>%s</div>',
    get_block_wrapper_attributes(),
    wp_json_encode( $context ),
    $content
);
