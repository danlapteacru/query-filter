<?php

declare(strict_types=1);

/**
 * Built-in block registration: one list of build/ subdirectories, extendable via filter.
 */
final class Query_Filter_Blocks {

	/**
	 * Subdirectories under `build/` that contain a `block.json` each.
	 *
	 * @var list<string>
	 */
	private const DEFAULT_BUILD_DIRECTORIES = [
		'filter-container',
		'filter-checkboxes',
		'filter-search',
		'filter-sort',
		'filter-pager',
		'filter-reset',
	];

	/**
	 * Directories to pass to register_block_type (relative to build/).
	 *
	 * @return list<string>
	 */
	public static function get_build_directories(): array {
		$dirs = self::DEFAULT_BUILD_DIRECTORIES;

		/**
		 * Add or reorder block build folders (each folder must contain block.json).
		 *
		 * @param list<string> $dirs Folder names only, e.g. 'filter-checkboxes'.
		 */
		$filtered = apply_filters( 'query_filter/blocks/build_directories', $dirs );

		if ( ! is_array( $filtered ) ) {
			return $dirs;
		}

		$out = [];
		foreach ( $filtered as $item ) {
			if ( is_string( $item ) && $item !== '' ) {
				$out[] = $item;
			}
		}

		return $out !== [] ? array_values( array_unique( $out ) ) : $dirs;
	}
}
