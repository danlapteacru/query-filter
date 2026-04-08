<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QueryEngineCombineTest extends TestCase {

	public function test_combine_and_intersects(): void {
		$result = QLIF_Query_Engine::combine_post_id_sets(
			[[1, 2], [2, 3]],
			'AND'
		);
		$this->assertSame([2], $result);
	}

	public function test_combine_or_unions_unique(): void {
		$result = QLIF_Query_Engine::combine_post_id_sets(
			[[1, 2], [2, 3]],
			'OR'
		);
		sort($result);
		$this->assertSame([1, 2, 3], $result);
	}

	public function test_combine_single_set_unchanged(): void {
		$result = QLIF_Query_Engine::combine_post_id_sets([[7, 8]], 'AND');
		$this->assertSame([7, 8], $result);
		$this->assertSame([7, 8], QLIF_Query_Engine::combine_post_id_sets([[7, 8]], 'OR'));
	}

	public function test_combine_empty_sets_returns_empty(): void {
		$this->assertSame(
			[],
			QLIF_Query_Engine::combine_post_id_sets([], 'AND')
		);
	}
}
