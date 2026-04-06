<?php
declare(strict_types=1);

final class Query_Filter_Filter_Search {
	/**
	 * @return array<string, string>
	 */
	public function get_query_args( string $search ): array {
		$search = trim( $search );
		if ( $search === '' ) {
			return array();
		}
		return array( 's' => $search );
	}
}
