<?php

declare(strict_types=1);

$label       = $attributes['label'] ?? 'Search';
$show_label  = $attributes['showLabel'] ?? true;
$placeholder = $attributes['placeholder'] ?? 'Search...';

?>
<div
    <?php echo get_block_wrapper_attributes(); ?>
    data-wp-interactive="query-filter"
>
    <?php if ( $show_label && $label ) : ?>
        <label class="wp-block-query-filter__label"><?php echo esc_html( $label ); ?></label>
    <?php endif; ?>
    <input
        type="search"
        placeholder="<?php echo esc_attr( $placeholder ); ?>"
        data-wp-on--input="actions.onSearchInput"
        data-wp-bind--value="state.search"
    />
</div>
