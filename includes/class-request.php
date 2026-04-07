<?php

declare(strict_types=1);

final class Query_Filter_Request {

	/**
	 * @param array<string, array<string, mixed>> $filters Normalized discrete configs.
	 */
	public function __construct(
		public readonly int $query_id,
		public readonly int $page_id,
		public readonly array $filters,
		public readonly string $filters_relationship,
		public readonly int $page,
		public readonly string $orderby,
		public readonly string $order,
		public readonly string $search,
	) {}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function from_array( array $data ): self {
		$filters_raw = $data['filters'] ?? [];
		$filters     = [];

		if ( is_array( $filters_raw ) ) {
			foreach ( $filters_raw as $name => $entry ) {
				if ( ! is_string( $name ) ) {
					continue;
				}
				$key = sanitize_key( $name );
				if ( $key === '' ) {
					continue;
				}

				if ( ! is_array( $entry ) ) {
					continue;
				}

				if ( array_key_exists( 'values', $entry ) ) {
					$values = [];
					$raw    = $entry['values'];
					if ( is_array( $raw ) ) {
						foreach ( $raw as $v ) {
							if ( is_string( $v ) ) {
								$values[] = sanitize_text_field( $v );
							}
						}
					}
					$logic_in = strtoupper( (string) ( $entry['logic'] ?? 'OR' ) );
					$logic    = $logic_in === 'AND' ? 'AND' : 'OR';
					if ( $values !== [] ) {
						$filters[ $key ] = [
							'kind'   => 'discrete',
							'values' => $values,
							'logic'  => $logic,
						];
					}
					continue;
				}

				if ( array_is_list( $entry ) ) {
					$values = [];
					foreach ( $entry as $v ) {
						if ( is_string( $v ) ) {
							$values[] = sanitize_text_field( $v );
						}
					}
					if ( $values !== [] ) {
						$filters[ $key ] = [
							'kind'   => 'discrete',
							'values' => $values,
							'logic'  => 'OR',
						];
					}
					continue;
				}
			}
		}

		$rel                  = strtoupper( (string) ( $data['filtersRelationship'] ?? 'AND' ) );
		$filters_relationship = $rel === 'OR' ? 'OR' : 'AND';

		$order = strtoupper( (string) ( $data['order'] ?? 'DESC' ) );
		if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
			$order = 'DESC';
		}

		return new self(
			query_id:             (int) ( $data['queryId'] ?? 0 ),
			page_id:              (int) ( $data['pageId'] ?? 0 ),
			filters:              $filters,
			filters_relationship: $filters_relationship,
			page:                 max( 1, (int) ( $data['page'] ?? 1 ) ),
			orderby:              sanitize_key( (string) ( $data['orderby'] ?? 'date' ) ),
			order:                $order,
			search:               sanitize_text_field( (string) ( $data['search'] ?? '' ) ),
		);
	}
}
