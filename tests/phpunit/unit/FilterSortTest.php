<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class FilterSortTest extends TestCase {
    public function test_get_query_args_returns_orderby_and_order(): void {
        $filter = new Query_Filter_Filter_Sort();
        $args = $filter->get_query_args('price', 'ASC');
        $this->assertSame('price', $args['orderby']);
        $this->assertSame('ASC', $args['order']);
    }

    public function test_get_query_args_defaults_to_date_desc(): void {
        $filter = new Query_Filter_Filter_Sort();
        $args = $filter->get_query_args('', '');
        $this->assertSame('date', $args['orderby']);
        $this->assertSame('DESC', $args['order']);
    }
}
