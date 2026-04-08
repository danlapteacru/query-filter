<?php

declare(strict_types=1);

/**
 * @var WP_Block $block
 */

$block_name = ( isset( $block ) && $block instanceof WP_Block ) ? $block->name : '';

$label   = $attributes['label'] ?? 'Sort by';
$options = $attributes['options'] ?? [];

$default_orderby = 'date';
$default_order   = 'DESC';
$default_sort    = $default_orderby . ':' . $default_order;
if ( is_array( $options ) && $options !== [] ) {
	$first = $options[0];
	if ( is_array( $first ) && isset( $first['orderby'], $first['order'] ) ) {
		$default_orderby = (string) $first['orderby'];
		$default_order   = strtoupper( (string) $first['order'] );
		if ( ! in_array( $default_order, [ 'ASC', 'DESC' ], true ) ) {
			$default_order = 'DESC';
		}
		$default_sort = $default_orderby . ':' . $default_order;
	}
}

wp_interactivity_state(
	'query-filter',
	[
		'sortControlValue'         => $default_sort,
		'initialSortControlValue'  => $default_sort,
		'orderby'                  => $default_orderby,
		'order'                    => $default_order,
	]
);

ob_start();
?>
<div
	<?php echo get_block_wrapper_attributes(); ?>
	data-wp-interactive="query-filter"
	data-default-sort="<?php echo esc_attr( $default_sort ); ?>"
>
	<label class="wp-block-query-filter__label"><?php echo esc_html( $label ); ?></label>
	<select
		data-wp-bind--value="state.sortControlValue"
		data-wp-on--change="actions.onSortChange"
	>
		<?php foreach ( $options as $opt ) : ?>
			<option value="<?php echo esc_attr( $opt['orderby'] . ':' . $opt['order'] ); ?>">
				<?php echo esc_html( $opt['label'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>
<?php
echo QLIF_Render_Hooks::block_html( ob_get_clean(), $block_name, $attributes, null );
