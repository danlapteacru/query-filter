<?php

declare(strict_types=1);

/**
 * WordPress hooks for block markup and REST responses.
 * Block names are taken from {@see WP_Block::$name} in templates — not duplicated here.
 */
final class Query_Filter_Render_Hooks {

	/**
	 * Filter final HTML for a block render template.
	 *
	 * @param array<string, mixed>      $attributes Block attributes.
	 * @param array<string, mixed>|null $context    Interactivity context when applicable.
	 */
	public static function block_html( string $html, string $block_name, array $attributes, ?array $context = null ): string {
		/**
		 * Front-end markup for a Query Filter block (after the template runs).
		 *
		 * @param string               $html        Buffered HTML.
		 * @param string               $block_name  Registered block name from block.json (e.g. query-filter/filter-checkboxes).
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
	 * Interactivity context before JSON encoding (any block may use this from its render template).
	 *
	 * @param array<string, mixed> $context
	 * @param array<string, mixed> $attributes
	 * @return array<string, mixed>
	 */
	public static function filter_interactivity_context( array $context, array $attributes, string $block_name ): array {
		/**
		 * Interactivity context array before wp_json_encode for data-wp-context.
		 *
		 * @param array<string, mixed> $context
		 * @param array<string, mixed> $attributes
		 * @param string               $block_name Registered block name.
		 */
		$filtered = apply_filters( 'query_filter/render/interactivity_context', $context, $attributes, $block_name );

		if ( ! is_array( $filtered ) ) {
			return $context;
		}

		return $filtered;
	}

	/**
	 * Checkbox block: legacy hook then generic interactivity filter.
	 *
	 * @param array<string, mixed> $context
	 * @param array<string, mixed> $attributes
	 * @return array<string, mixed>
	 */
	public static function filter_checkboxes_interactivity_context( array $context, array $attributes, string $block_name ): array {
		/**
		 * @deprecated Use {@see 'query_filter/render/interactivity_context'} and check `$block_name`.
		 */
		$legacy = apply_filters( 'query_filter/render/checkboxes/context', $context, $attributes );
		if ( is_array( $legacy ) ) {
			$context = $legacy;
		}

		return self::filter_interactivity_context( $context, $attributes, $block_name );
	}
}
