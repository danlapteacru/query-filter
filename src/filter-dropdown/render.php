<?php

declare(strict_types=1);

/**
 * @var WP_Block $block
 */

$block_name = ( isset( $block ) && $block instanceof WP_Block ) ? $block->name : '';

$filter_name = $attributes['filterName'] ?? '';
$label       = $attributes['label'] ?? '';
$show_label  = $attributes['showLabel'] ?? true;
$show_counts = $attributes['showCounts'] ?? true;
$placeholder = $attributes['placeholder'] ?? '';

if ( empty( $filter_name ) ) {
	echo Query_Filter_Render_Hooks::block_html( '', $block_name, $attributes, [] );
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
	'options'    => $options,
	'selected'   => [],
];

$context = Query_Filter_Render_Hooks::filter_checkboxes_interactivity_context( $context, $attributes, $block_name );

ob_start();
?>
<div
	<?php echo get_block_wrapper_attributes(); ?>
	data-wp-interactive="query-filter"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
>
	<label class="wp-block-query-filter-dropdown__label">
		<?php if ( $show_label && $label ) : ?>
			<span class="wp-block-query-filter__label"><?php echo esc_html( $label ); ?></span>
		<?php endif; ?>
		<select data-wp-on--change="actions.changeDropdown" class="wp-block-query-filter-dropdown__select">
			<option value=""><?php echo esc_html( $placeholder !== '' ? $placeholder : __( 'Any', 'query-filter' ) ); ?></option>
			<?php foreach ( $options as $option ) : ?>
				<option value="<?php echo esc_attr( $option['value'] ); ?>">
					<?php echo esc_html( $option['label'] ); ?>
					<?php if ( $show_counts ) : ?>
						(<?php echo (int) $option['count']; ?>)
					<?php endif; ?>
				</option>
			<?php endforeach; ?>
		</select>
	</label>
</div>
<?php
echo Query_Filter_Render_Hooks::block_html( ob_get_clean(), $block_name, $attributes, $context );
