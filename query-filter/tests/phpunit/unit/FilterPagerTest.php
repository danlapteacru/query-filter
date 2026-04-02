<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class FilterPagerTest extends TestCase {
    public function test_compute_returns_correct_pagination(): void {
        $filter = new Query_Filter_Filter_Pager();
        $result = $filter->compute(total: 47, per_page: 10, current_page: 2);
        $this->assertSame(47, $result['total']);
        $this->assertSame(5, $result['pages']);
        $this->assertSame(2, $result['current_page']);
    }

    public function test_compute_clamps_page_to_max(): void {
        $filter = new Query_Filter_Filter_Pager();
        $result = $filter->compute(total: 5, per_page: 10, current_page: 3);
        $this->assertSame(1, $result['pages']);
        $this->assertSame(1, $result['current_page']);
    }
}
