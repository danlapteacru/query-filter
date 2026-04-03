<?php

declare(strict_types=1);

$label = $attributes['label'] ?? __( 'Reset Filters', 'query-filter' );

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
