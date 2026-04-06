<?php
// includes/sources/class-source-postmeta.php
declare(strict_types=1);

final class Query_Filter_Source_Post_Meta extends Query_Filter_Source {

	public function __construct(
		private readonly string $meta_key,
	) {}

	public function get_values( int $post_id ): array {
		$raw = get_post_meta( $post_id, $this->meta_key );
		if ( empty( $raw ) ) {
			return array();
		}

		$values = array();
		foreach ( $raw as $val ) {
			$str = (string) $val;
			if ( $str === '' ) {
				continue;
			}
			$values[] = array(
				'value'     => $str,
				'label'     => $str,
				'term_id'   => 0,
				'parent_id' => 0,
				'depth'     => 0,
			);
		}

		return $values;
	}
}
