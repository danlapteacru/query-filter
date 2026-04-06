<?php

declare(strict_types=1);

/**
 * WordPress hooks for block markup and REST responses.
 */
final class Query_Filter_Render_Hooks {

	public const BLOCK_FILTER_CONTAINER  = 'query-filter/filter-container';
	public const BLOCK_FILTER_CHECKBOXES = 'query-filter/filter-checkboxes';
	public const BLOCK_FILTER_SEARCH     = 'query-filter/filter-search';
	public const BLOCK_FILTER_SORT       = 'query-filter/filter-sort';
	public const BLOCK_FILTER_RESET      = 'query-filter/filter-reset';
	public const BLOCK_FILTER_PAGER      = 'query-filter/filter-pager';

	/**
	 * Filter final HTML for a block render template.
	 *
	 * @param array<string, mixed>  $attributes Block attributes.
	 * @param array<string, mixed>|null $context  Interactivity context when applicable.
	 */
	public static function block_html( string $html, string $block_name, array $attributes, ?array $context = null ): string {
		/**
		 * Front-end markup for a Query Filter block (after the template runs).
		 *
		 * @param string               $html        Buffered HTML.
		 * @param string               $block_name  e.g. query-filter/filter-checkboxes.
		 * @param array<string, mixed> $attributes  Block attributes.
		 * @param array<string, mixed>|null $context Interactivity data-wp-context payload, or null.
		 */
		$filtered = apply_filters( 'query_filter/render/block', $html, $block_name, $attributes, $context );

		if ( ! is_string( $filtered ) ) {
			return $html;
		}

		return $filtered;
	}

	/**
	 * @param array<string, mixed> $context
	 * @param array<string, mixed> $attributes
	 * @return array<string, mixed>
	 */
	public static function checkboxes_context( array $context, array $attributes ): array {
		/**
		 * Checkbox block Interactivity context before JSON encoding.
		 *
		 * @param array<string, mixed> $context    Keys: filterName, logic, options, selected.
		 * @param array<string, mixed> $attributes Block attributes.
		 */
		$filtered = apply_filters( 'query_filter/render/checkboxes/context', $context, $attributes );

		if ( ! is_array( $filtered ) ) {
			return $context;
		}

		return $filtered;
	}
}
