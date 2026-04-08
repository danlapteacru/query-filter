<?php

declare(strict_types=1);

/**
 * @var WP_Block $block
 */

$block_name = ( isset( $block ) && $block instanceof WP_Block ) ? $block->name : '';

$query_id = ( isset( $block ) && $block instanceof WP_Block ) ? (int) ( $block->context['queryId'] ?? 0 ) : 0;

$context = [
	'queryId' => $query_id,
];

ob_start();
?>
<nav
	<?php echo get_block_wrapper_attributes( [ 'aria-label' => __( 'Pagination', 'query-loop-index-filters' ) ] ); ?>
	data-wp-interactive="query-filter"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
>
	<span data-wp-text="state.pagerSummary"></span>
	<button
		data-wp-on--click="actions.prevPage"
		data-wp-bind--disabled="state.isFirstPage"
	>&laquo; <?php esc_html_e( 'Prev', 'query-loop-index-filters' ); ?></button>
	<span data-wp-text="state.pagerCurrentNum"></span>
	/
	<span data-wp-text="state.pagerPagesNum"></span>
	<button
		data-wp-on--click="actions.nextPage"
		data-wp-bind--disabled="state.isLastPage"
	><?php esc_html_e( 'Next', 'query-loop-index-filters' ); ?> &raquo;</button>
</nav>
<?php
echo QLIF_Render_Hooks::block_html( ob_get_clean(), $block_name, $attributes, $context );
