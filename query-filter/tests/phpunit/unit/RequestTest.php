<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase {

    public function test_from_array_parses_valid_payload(): void {
        $input = [
            'queryId' => 1,
            'pageId'  => 42,
            'filters' => ['category' => ['shoes', 'boots']],
            'page'    => 2,
            'orderby' => 'price',
            'order'   => 'ASC',
            'search'  => 'running',
        ];

        $request = Query_Filter_Request::from_array($input);

        $this->assertSame(1, $request->query_id);
        $this->assertSame(42, $request->page_id);
        $this->assertSame(['category' => ['shoes', 'boots']], $request->filters);
        $this->assertSame(2, $request->page);
        $this->assertSame('price', $request->orderby);
        $this->assertSame('ASC', $request->order);
        $this->assertSame('running', $request->search);
    }

    public function test_from_array_uses_defaults_for_missing_fields(): void {
        $request = Query_Filter_Request::from_array(['queryId' => 1, 'pageId' => 10]);

        $this->assertSame([], $request->filters);
        $this->assertSame(1, $request->page);
        $this->assertSame('date', $request->orderby);
        $this->assertSame('DESC', $request->order);
        $this->assertSame('', $request->search);
    }

    public function test_from_array_sanitizes_filter_values(): void {
        $input = [
            'queryId' => 1,
            'pageId'  => 10,
            'filters' => ['cat' => ['<script>alert(1)</script>', 'shoes']],
        ];

        $request = Query_Filter_Request::from_array($input);

        $this->assertSame(['cat' => ['alert(1)', 'shoes']], $request->filters);
    }

    public function test_from_array_rejects_non_array_filters(): void {
        $input = ['queryId' => 1, 'pageId' => 10, 'filters' => 'bad'];
        $request = Query_Filter_Request::from_array($input);
        $this->assertSame([], $request->filters);
    }
}
