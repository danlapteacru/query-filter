<?php

declare(strict_types=1);

$label   = $attributes['label'] ?? 'Sort by';
$options = $attributes['options'] ?? [];

?>
<div
    <?php echo get_block_wrapper_attributes(); ?>
    data-wp-interactive="query-filter"
>
    <label class="wp-block-query-filter__label"><?php echo esc_html( $label ); ?></label>
    <select data-wp-on--change="actions.onSortChange">
        <?php foreach ( $options as $opt ) : ?>
            <option value="<?php echo esc_attr( $opt['orderby'] . ':' . $opt['order'] ); ?>">
                <?php echo esc_html( $opt['label'] ); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
