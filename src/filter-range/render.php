<?php

declare(strict_types=1);

/**
 * @var WP_Block $block
 */

$block_name   = ( isset( $block ) && $block instanceof WP_Block ) ? $block->name : '';
$filter_name  = $attributes['filterName'] ?? '';
$label        = $attributes['label'] ?? '';
$show_label   = $attributes['showLabel'] ?? true;
$step         = isset( $attributes['step'] ) ? (float) $attributes['step'] : 1.0;
$attr_min     = $attributes['inputMin'] ?? '';
$attr_max     = $attributes['inputMax'] ?? '';

if ( $step <= 0 ) {
	$step = 1.0;
}

if ( empty( $filter_name ) ) {
	echo Query_Filter_Render_Hooks::block_html( '', $block_name, $attributes, [] );
	return;
}

$slider_min = is_numeric( $attr_min ) ? (string) $attr_min : '';
$slider_max = is_numeric( $attr_max ) ? (string) $attr_max : '';

$context = [
	'filterName' => $filter_name,
	'step'       => $step,
	'sliderMin'  => $slider_min,
	'sliderMax'  => $slider_max,
];

$context = Query_Filter_Render_Hooks::filter_interactivity_context( $context, $attributes, $block_name );

ob_start();
?>
<div
	<?php echo get_block_wrapper_attributes(); ?>
	data-wp-interactive="query-filter"
	data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>"
	class="wp-block-query-filter-filter-range"
>
	<?php if ( $show_label && $label ) : ?>
		<span class="wp-block-query-filter__label"><?php echo esc_html( $label ); ?></span>
	<?php endif; ?>
	<div class="wp-block-query-filter-range__inputs">
		<label class="wp-block-query-filter-range__field">
			<span><?php esc_html_e( 'Min', 'query-filter' ); ?></span>
			<input
				type="number"
				step="<?php echo esc_attr( (string) $step ); ?>"
				data-query-filter-range="min"
				data-wp-on--input="actions.setRangePart"
			/>
		</label>
		<label class="wp-block-query-filter-range__field">
			<span><?php esc_html_e( 'Max', 'query-filter' ); ?></span>
			<input
				type="number"
				step="<?php echo esc_attr( (string) $step ); ?>"
				data-query-filter-range="max"
				data-wp-on--input="actions.setRangePart"
			/>
		</label>
	</div>
	<?php if ( $slider_min !== '' && $slider_max !== '' ) : ?>
		<div class="wp-block-query-filter-range__sliders">
			<label>
				<span><?php esc_html_e( 'Min slider', 'query-filter' ); ?></span>
				<input
					type="range"
					min="<?php echo esc_attr( $slider_min ); ?>"
					max="<?php echo esc_attr( $slider_max ); ?>"
					step="<?php echo esc_attr( (string) $step ); ?>"
					data-query-filter-range="min"
					data-wp-on--input="actions.setRangePart"
				/>
			</label>
			<label>
				<span><?php esc_html_e( 'Max slider', 'query-filter' ); ?></span>
				<input
					type="range"
					min="<?php echo esc_attr( $slider_min ); ?>"
					max="<?php echo esc_attr( $slider_max ); ?>"
					step="<?php echo esc_attr( (string) $step ); ?>"
					data-query-filter-range="max"
					data-wp-on--input="actions.setRangePart"
				/>
			</label>
		</div>
	<?php endif; ?>
</div>
<?php
echo Query_Filter_Render_Hooks::block_html( ob_get_clean(), $block_name, $attributes, $context );
