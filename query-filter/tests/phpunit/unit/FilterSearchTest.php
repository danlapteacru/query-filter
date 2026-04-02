<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class FilterSearchTest extends TestCase {
    public function test_get_query_args_returns_search_param(): void {
        $filter = new Query_Filter_Filter_Search();
        $args = $filter->get_query_args('running shoes');
        $this->assertSame('running shoes', $args['s']);
    }

    public function test_get_query_args_returns_empty_for_blank_search(): void {
        $filter = new Query_Filter_Filter_Search();
        $args = $filter->get_query_args('');
        $this->assertEmpty($args);
    }
}
