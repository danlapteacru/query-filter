<?php

declare(strict_types=1);

class RendererResolveTest extends WP_UnitTestCase {

	public function test_resolve_document_query_loop_id_zero_falls_back_to_first_query(): void {
		$block = [
			'blockName'    => 'core/query',
			'attrs'        => [ 'queryId' => 5 ],
			'innerBlocks'  => [],
			'innerHTML'    => '',
			'innerContent' => [],
		];
		$post_id = self::factory()->post->create(
			[
				'post_content' => serialize_blocks( [ $block ] ),
			]
		);
		$this->assertSame( 5, QLIF_Renderer::resolve_document_query_loop_id( $post_id, 0 ) );
	}

	public function test_resolve_document_query_loop_id_exact_match(): void {
		$inner = [
			'blockName'    => 'core/query',
			'attrs'        => [ 'queryId' => 2 ],
			'innerBlocks'  => [],
			'innerHTML'    => '',
			'innerContent' => [],
		];
		$wrap = [
			'blockName'    => 'core/group',
			'attrs'        => [],
			'innerBlocks'  => [ $inner ],
			'innerHTML'    => '',
			'innerContent' => [],
		];
		$post_id = self::factory()->post->create(
			[
				'post_content' => serialize_blocks( [ $wrap ] ),
			]
		);
		$this->assertSame( 2, QLIF_Renderer::resolve_document_query_loop_id( $post_id, 2 ) );
	}

	public function test_resolve_returns_requested_when_no_block(): void {
		$post_id = self::factory()->post->create( [ 'post_content' => '<p>plain</p>' ] );
		$this->assertSame( 9, QLIF_Renderer::resolve_document_query_loop_id( $post_id, 9 ) );
	}
}
