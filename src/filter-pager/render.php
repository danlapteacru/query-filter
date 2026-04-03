<?php

declare(strict_types=1);

$query_id = $block->context['queryId'] ?? 0;

$context = [
    'queryId' => $query_id,
];

?>
<nav
    <?php echo get_block_wrapper_attributes( [ 'aria-label' => __( 'Pagination', 'query-filter' ) ] ); ?>
    data-wp-interactive="query-filter"
    data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
>
    <span data-wp-text="state.pagerSummary"></span>
    <button
        data-wp-on--click="actions.prevPage"
        data-wp-bind--disabled="state.isFirstPage"
    >&laquo; <?php esc_html_e( 'Prev', 'query-filter' ); ?></button>
    <span data-wp-text="state.currentPage"></span>
    /
    <span data-wp-text="state.pages"></span>
    <button
        data-wp-on--click="actions.nextPage"
        data-wp-bind--disabled="state.isLastPage"
    ><?php esc_html_e( 'Next', 'query-filter' ); ?> &raquo;</button>
</nav>
