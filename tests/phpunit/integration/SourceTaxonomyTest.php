<?php
// tests/phpunit/integration/SourceTaxonomyTest.php
declare(strict_types=1);

class SourceTaxonomyTest extends WP_UnitTestCase {

    public function test_get_values_returns_assigned_terms(): void {
        $post_id = self::factory()->post->create();
        $term = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Shoes']);
        wp_set_object_terms($post_id, [$term->term_id], 'category');

        $source = new QLIF_Source_Taxonomy('category');
        $values = $source->get_values($post_id);

        $this->assertCount(1, $values);
        $this->assertSame($term->slug, $values[0]['value']);
        $this->assertSame('Shoes', $values[0]['label']);
        $this->assertSame($term->term_id, $values[0]['term_id']);
        $this->assertSame(0, $values[0]['parent_id']);
        $this->assertSame(0, $values[0]['depth']);
    }

    public function test_get_values_returns_empty_for_post_with_no_terms(): void {
        $post_id = self::factory()->post->create();
        wp_set_object_terms($post_id, [], 'category');

        $source = new QLIF_Source_Taxonomy('category');
        $values = $source->get_values($post_id);

        $this->assertSame([], $values);
    }

    public function test_get_values_includes_parent_and_depth(): void {
        $parent = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Footwear']);
        $child = self::factory()->term->create_and_get([
            'taxonomy' => 'category',
            'name' => 'Sneakers',
            'parent' => $parent->term_id,
        ]);
        $post_id = self::factory()->post->create();
        wp_set_object_terms($post_id, [$child->term_id], 'category');

        $source = new QLIF_Source_Taxonomy('category');
        $values = $source->get_values($post_id);

        $this->assertCount(1, $values);
        $this->assertSame($parent->term_id, $values[0]['parent_id']);
        $this->assertSame(1, $values[0]['depth']);
    }
}
