<?php
// includes/sources/class-source-acf.php
declare(strict_types=1);

final class Query_Filter_Source_ACF extends Query_Filter_Source {

    public function __construct(
        private readonly string $field_name,
    ) {}

    public static function is_available(): bool {
        return function_exists('get_field');
    }

    public function get_values(int $post_id): array {
        if (! self::is_available()) {
            return [];
        }

        $raw = get_field($this->field_name, $post_id, false);
        if (empty($raw)) {
            return [];
        }

        $items = is_array($raw) ? $raw : [$raw];
        $values = [];
        foreach ($items as $item) {
            $str = (string) $item;
            if ($str === '') {
                continue;
            }
            $values[] = [
                'value'     => $str,
                'label'     => $str,
                'term_id'   => 0,
                'parent_id' => 0,
                'depth'     => 0,
            ];
        }

        return $values;
    }
}
