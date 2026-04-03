<?php
// includes/sources/class-source-woocommerce.php
declare(strict_types=1);

final class Query_Filter_Source_WooCommerce extends Query_Filter_Source {

    public function __construct(
        private readonly string $attribute_name,
    ) {}

    public static function is_available(): bool {
        return class_exists('WooCommerce');
    }

    public function get_values(int $post_id): array {
        if (! self::is_available()) {
            return [];
        }

        $product = wc_get_product($post_id);
        if (! $product) {
            return [];
        }

        $taxonomy = wc_attribute_taxonomy_name($this->attribute_name);
        $terms = wp_get_object_terms($post_id, $taxonomy);
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        $values = [];
        foreach ($terms as $term) {
            $values[] = [
                'value'     => $term->slug,
                'label'     => $term->name,
                'term_id'   => $term->term_id,
                'parent_id' => $term->parent,
                'depth'     => 0,
            ];
        }

        return $values;
    }
}
