<?php

declare(strict_types=1);

$label = $attributes['label'] ?? __( 'Reset Filters', 'query-filter' );

ob_start();
?>
<div
	<?php echo get_block_wrapper_attributes(); ?>
	data-wp-interactive="query-filter"
	data-wp-class--is-hidden="state.hasNoActiveFilters"
>
	<button
		class="wp-block-query-filter-reset__button"
		data-wp-on--click="actions.resetAll"
	>
		<?php echo esc_html( $label ); ?>
	</button>
</div>
<?php
echo Query_Filter_Render_Hooks::block_html(
	ob_get_clean(),
	Query_Filter_Render_Hooks::BLOCK_FILTER_RESET,
	$attributes,
	null
);
