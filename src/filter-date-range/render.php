<?php

declare(strict_types=1);

/**
 * @var WP_Block $block
 */

$block_name  = ( isset( $block ) && $block instanceof WP_Block ) ? $block->name : '';
$filter_name = $attributes['filterName'] ?? '';
$label       = $attributes['label'] ?? '';
$show_label  = $attributes['showLabel'] ?? true;

if ( empty( $filter_name ) ) {
	echo Query_Filter_Render_Hooks::block_html( '', $block_name, $attributes, [] );
	return;
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
