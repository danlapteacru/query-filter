<?php

declare(strict_types=1);

/**
 * Single ISO date (Y-m-d) from the post's local published date.
 */
final class Query_Filter_Source_Post_Date extends Query_Filter_Source {

	public function get_values( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return [];
		}

		$ymd = get_the_date( 'Y-m-d', $post );
		if ( ! is_string( $ymd ) || $ymd === '' ) {
			return [];
		}

		return [
			[
				'value'     => $ymd,
				'label'     => $ymd,
				'term_id'   => 0,
				'parent_id' => 0,
				'depth'     => 0,
			],
		];
	}
}
