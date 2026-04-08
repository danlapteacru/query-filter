<?php

declare(strict_types=1);

/**
 * @var WP_Block $block
 */

$block_name = ( isset( $block ) && $block instanceof WP_Block ) ? $block->name : '';

$label = $attributes['label'] ?? __( 'Reset Filters', 'query-loop-index-filters' );

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
echo QLIF_Render_Hooks::block_html( ob_get_clean(), $block_name, $attributes, null );
