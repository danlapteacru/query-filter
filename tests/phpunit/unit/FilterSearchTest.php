<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class FilterSearchTest extends TestCase {
    public function test_get_query_args_returns_search_param(): void {
        $filter = new QLIF_Filter_Search();
        $args = $filter->get_query_args( 'running shoes' );
        $this->assertSame( 'running shoes', $args['s'] );
        $this->assertSame( 'wordpress', $args['source'] );
        $this->assertSame( 'default', $args['searchwp_engine'] );
    }

    public function test_get_query_args_searchwp_source(): void {
        $filter = new QLIF_Filter_Search();
        $args   = $filter->get_query_args( 'foo', 'searchwp', 'custom' );
        $this->assertSame( 'foo', $args['s'] );
        $this->assertSame( 'searchwp', $args['source'] );
        $this->assertSame( 'custom', $args['searchwp_engine'] );
    }

    public function test_get_query_args_returns_empty_for_blank_search(): void {
        $filter = new QLIF_Filter_Search();
        $args = $filter->get_query_args('');
        $this->assertEmpty($args);
    }
}
