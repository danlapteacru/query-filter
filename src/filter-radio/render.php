<?php

declare(strict_types=1);

/**
 * @var WP_Block $block
 */

$block_name = ( isset( $block ) && $block instanceof WP_Block ) ? $block->name : '';

$label       = $attributes['label'] ?? '';
$show_label  = $attributes['showLabel'] ?? true;
$show_counts = $attributes['showCounts'] ?? true;

$indexer = Query_Filter_Plugin::instance()->get_indexer();
[ $filter_name, $filter ] = Query_Filter_Render_Hooks::resolve_discrete_checkbox_filter( $indexer, $attributes );

if ( $filter_name === '' ) {
	echo Query_Filter_Render_Hooks::block_html( '', $block_name, $attributes, [] );
	return;
}

$options = [];
if ( $filter instanceof Query_Filter_Filter_Checkboxes ) {
	$options = $filter->load_values( [] );
} elseif ( current_user_can( 'manage_options' ) ) {
	echo '<p class="wp-block-query-filter__setup-notice" style="font-size:13px;color:#646970;margin:0 0 8px;">';
	esc_html_e(
		'No index filter matches this block. For taxonomy sources the indexed name is the taxonomy slug (e.g. category) — match Filter name to Source key.',
		'query-filter'
	);
	echo '</p>';
}

$group_id = 'qf-radio-' . sanitize_html_class( $filter_name );

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
	<fieldset>
		<?php if ( $show_label && $label ) : ?>
			<legend class="wp-block-query-filter__label"><?php echo esc_html( $label ); ?></legend>
		<?php endif; ?>

		<?php foreach ( $options as $option ) : ?>
			<label class="wp-block-query-filter-radio__option">
				<input
					type="radio"
					name="<?php echo esc_attr( $group_id ); ?>"
					value="<?php echo esc_attr( $option['value'] ); ?>"
					data-wp-on--change="actions.selectRadio"
				/>
				<span><?php echo esc_html( $option['label'] ); ?></span>
				<?php if ( $show_counts ) : ?>
					<span class="wp-block-query-filter-radio__count">(<?php echo (int) $option['count']; ?>)</span>
				<?php endif; ?>
			</label>
		<?php endforeach; ?>
	</fieldset>
</div>
<?php
echo Query_Filter_Render_Hooks::block_html( ob_get_clean(), $block_name, $attributes, $context );
