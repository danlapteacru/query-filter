<?php

declare(strict_types=1);

$filter_name = $attributes['filterName'] ?? '';
$source_type = $attributes['sourceType'] ?? 'taxonomy';
$source_key  = $attributes['sourceKey'] ?? 'category';
$label       = $attributes['label'] ?? '';
$show_label  = $attributes['showLabel'] ?? true;
$show_counts = $attributes['showCounts'] ?? true;
$logic       = $attributes['logic'] ?? 'OR';

if ( empty( $filter_name ) ) {
    return;
}

$indexer = Query_Filter_Plugin::instance()->get_indexer();
$filter  = $indexer ? $indexer->get_filter( $filter_name ) : null;
$options = [];

if ( $filter instanceof Query_Filter_Filter_Checkboxes ) {
    $options = $filter->load_values( [] );
}

$context = [
    'filterName' => $filter_name,
    'logic'      => $logic,
    'options'    => $options,
    'selected'   => [],
];

?>
<div
    <?php echo get_block_wrapper_attributes(); ?>
    data-wp-interactive="query-filter"
    data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
>
    <fieldset>
        <?php if ( $show_label && $label ) : ?>
            <legend class="wp-block-query-filter__label"><?php echo esc_html( $label ); ?></legend>
        <?php endif; ?>

        <?php foreach ( $options as $option ) : ?>
            <label class="wp-block-query-filter-checkboxes__option">
                <input
                    type="checkbox"
                    value="<?php echo esc_attr( $option['value'] ); ?>"
                    data-wp-on--change="actions.toggleCheckbox"
                />
                <span><?php echo esc_html( $option['label'] ); ?></span>
                <?php if ( $show_counts ) : ?>
                    <span class="wp-block-query-filter-checkboxes__count">(<?php echo (int) $option['count']; ?>)</span>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
    </fieldset>
</div>
