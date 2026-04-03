<?php
declare(strict_types=1);

final class Query_Filter_Filter_Pager {
    /**
     * @return array{total: int, pages: int, current_page: int}
     */
    public function compute(int $total, int $per_page, int $current_page): array {
        $per_page = max(1, $per_page);
        $pages = (int) ceil($total / $per_page);
        $current_page = max(1, min($current_page, max(1, $pages)));
        return [
            'total'        => $total,
            'pages'        => $pages,
            'current_page' => $current_page,
        ];
    }
}
