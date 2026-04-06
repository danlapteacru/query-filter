<?php
declare(strict_types=1);

final class Query_Filter_Filter_Sort {
	/**
	 * @return array{orderby: string, order: string}
	 */
	public function get_query_args( string $orderby, string $order ): array {
		$orderby = $orderby ?: 'date';
		$order   = strtoupper( $order ?: 'DESC' );
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}
		return array(
			'orderby' => $orderby,
			'order'   => $order,
		);
	}
}
