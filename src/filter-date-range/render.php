<?php

declare(strict_types=1);

/**
 * @var WP_Block $block
 */

$block_name  = ( isset( $block ) && $block instanceof WP_Block ) ? $block->name : '';
$filter_name = sanitize_key( (string) ( $attributes['filterName'] ?? '' ) );
$label       = $attributes['label'] ?? '';
$show_label  = $attributes['showLabel'] ?? true;

if ( $filter_name === '' ) {
	echo Query_Filter_Render_Hooks::block_html( '', $block_name, $attributes, [] );
	return;
}

$indexer      = Query_Filter_Plugin::instance()->get_indexer();
$date_filter  = $indexer ? $indexer->get_filter( $filter_name ) : null;
$is_registered = $date_filter instanceof Query_Filter_Filter_Date_Range;

if ( ! $is_registered && current_user_can( 'manage_options' ) ) {
	echo '<p class="wp-block-query-filter__setup-notice" style="font-size:13px;color:#646970;margin:0 0 8px;">';
	printf(
		/* translators: %s: filter name from block attributes */
		esc_html__(
			'Date range filter "%s" is not registered on the indexer. Register Query_Filter_Filter_Date_Range in PHP (action query_filter/indexer/register_filters) — see README.',
			'query-filter'
		),
		esc_html( $filter_name )
	);
	echo '</p>';
}

$context = [
	'filterName' => $filter_name,
];

$context = Query_Filter_Render_Hooks::filter_interactivity_context( $context, $attributes, $block_name );

ob_start();
?>
<div
	<?php echo get_block_wrapper_attributes(); ?>
	data-wp-interactive="query-filter"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
	class="wp-block-query-filter-filter-date-range"
>
	<?php if ( $show_label && $label ) : ?>
		<span class="wp-block-query-filter__label"><?php echo esc_html( $label ); ?></span>
	<?php endif; ?>
	<div class="wp-block-query-filter-date-range__inputs">
		<label class="wp-block-query-filter-date-range__field">
			<span><?php esc_html_e( 'From', 'query-filter' ); ?></span>
			<input
				type="date"
				data-query-filter-date="after"
				data-wp-on--input="actions.setDatePart"
			/>
		</label>
		<label class="wp-block-query-filter-date-range__field">
			<span><?php esc_html_e( 'To', 'query-filter' ); ?></span>
			<input
				type="date"
				data-query-filter-date="before"
				data-wp-on--input="actions.setDatePart"
			/>
		</label>
	</div>
</div>
<?php
echo Query_Filter_Render_Hooks::block_html( ob_get_clean(), $block_name, $attributes, $context );
