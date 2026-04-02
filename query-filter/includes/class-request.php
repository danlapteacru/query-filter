<?php
declare(strict_types=1);

final class Query_Filter_Request {

    public function __construct(
        public readonly int $query_id,
        public readonly int $page_id,
        /** @var array<string, string[]> */
        public readonly array $filters,
        public readonly int $page,
        public readonly string $orderby,
        public readonly string $order,
        public readonly string $search,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function from_array(array $data): self {
        $filters_raw = $data['filters'] ?? [];
        $filters = [];

        if (is_array($filters_raw)) {
            foreach ($filters_raw as $name => $values) {
                if (! is_string($name) || ! is_array($values)) {
                    continue;
                }
                $clean = [];
                foreach ($values as $v) {
                    if (is_string($v)) {
                        $clean[] = sanitize_text_field($v);
                    }
                }
                if (! empty($clean)) {
                    $filters[sanitize_key($name)] = $clean;
                }
            }
        }

        $order = strtoupper((string) ($data['order'] ?? 'DESC'));
        if (! in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        return new self(
            query_id: (int) ($data['queryId'] ?? 0),
            page_id:  (int) ($data['pageId'] ?? 0),
            filters:  $filters,
            page:     max(1, (int) ($data['page'] ?? 1)),
            orderby:  sanitize_key((string) ($data['orderby'] ?? 'date')),
            order:    $order,
            search:   sanitize_text_field((string) ($data['search'] ?? '')),
        );
    }
}
