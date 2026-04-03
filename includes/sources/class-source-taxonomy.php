<?php
// includes/sources/class-source-taxonomy.php
declare(strict_types=1);

final class Query_Filter_Source_Taxonomy extends Query_Filter_Source {

    public function __construct(
        private readonly string $taxonomy,
    ) {}

    public function get_values(int $post_id): array {
        $terms = wp_get_object_terms($post_id, $this->taxonomy);
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        $values = [];
        foreach ($terms as $term) {
            $depth = 0;
            $parent_id = $term->parent;
            $ancestor = $parent_id;
            while ($ancestor > 0) {
                $depth++;
                $parent_term = get_term($ancestor, $this->taxonomy);
                $ancestor = ($parent_term && ! is_wp_error($parent_term)) ? $parent_term->parent : 0;
            }

            $values[] = [
                'value'     => $term->slug,
                'label'     => $term->name,
                'term_id'   => $term->term_id,
                'parent_id' => $parent_id,
                'depth'     => $depth,
            ];
        }

        return $values;
    }
}
