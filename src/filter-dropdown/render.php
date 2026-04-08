<?php

declare(strict_types=1);

/**
 * @var WP_Block $block
 */

$block_name = ( isset( $block ) && $block instanceof WP_Block ) ? $block->name : '';

$label       = $attributes['label'] ?? '';
$show_label  = $attributes['showLabel'] ?? true;
$show_counts = $attributes['showCounts'] ?? true;
$placeholder = $attributes['placeholder'] ?? '';

$indexer = QLIF_Plugin::instance()->get_indexer();
[ $filter_name, $filter ] = QLIF_Render_Hooks::resolve_discrete_checkbox_filter( $indexer, $attributes );

if ( $filter_name === '' ) {
	echo QLIF_Render_Hooks::block_html( '', $block_name, $attributes, [] );
	return;
}

$options = [];
if ( $filter instanceof QLIF_Filter_Checkboxes ) {
	$options = $filter->load_values( [] );
} elseif ( current_user_can( 'manage_options' ) ) {
	echo '<p class="wp-block-query-filter__setup-notice" style="font-size:13px;color:#646970;margin:0 0 8px;">';
	esc_html_e(
		'No index filter matches this block. For taxonomy sources the indexed name is the taxonomy slug — match Filter name to Source key.',
		'query-loop-index-filters'
	);
	echo '</p>';
}

$context = [
	'filterName' => $filter_name,
	'options'    => $options,
	'selected'   => [],
];

$context = QLIF_Render_Hooks::filter_checkboxes_interactivity_context( $context, $attributes, $block_name );

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
			<option value=""><?php echo esc_html( $placeholder !== '' ? $placeholder : __( 'Any', 'query-loop-index-filters' ) ); ?></option>
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
echo QLIF_Render_Hooks::block_html( ob_get_clean(), $block_name, $attributes, $context );
