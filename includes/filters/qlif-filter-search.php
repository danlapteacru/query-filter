<?php

declare(strict_types=1);

final class QLIF_Filter_Search {

	/**
	 * @return array<string, string>
	 */
	public function get_query_args( string $search, string $search_source = 'wordpress', string $searchwp_engine = 'default' ): array { // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- REST JSON slug default.
		$search = trim( $search );
		if ( $search === '' ) {
			return array();
		}
		// phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- REST JSON slug.
		$source = $search_source === 'searchwp' ? 'searchwp' : 'wordpress';
		$engine = $searchwp_engine !== '' ? $searchwp_engine : 'default';

		return array(
			's'               => $search,
			'source'          => $source,
			'searchwp_engine' => $engine,
		);
	}
}
