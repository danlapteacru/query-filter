<?php
// tests/phpunit/integration/SourcePostMetaTest.php
declare(strict_types=1);

class SourcePostMetaTest extends WP_UnitTestCase {

    public function test_get_values_returns_scalar_meta(): void {
        $post_id = self::factory()->post->create();
        update_post_meta($post_id, 'color', 'red');

        $source = new QLIF_Source_Post_Meta('color');
        $values = $source->get_values($post_id);

        $this->assertCount(1, $values);
        $this->assertSame('red', $values[0]['value']);
        $this->assertSame('red', $values[0]['label']);
    }

    public function test_get_values_returns_multiple_meta_values(): void {
        $post_id = self::factory()->post->create();
        add_post_meta($post_id, 'size', 'small');
        add_post_meta($post_id, 'size', 'medium');

        $source = new QLIF_Source_Post_Meta('size');
        $values = $source->get_values($post_id);

        $this->assertCount(2, $values);
        $slugs = array_column($values, 'value');
        $this->assertContains('small', $slugs);
        $this->assertContains('medium', $slugs);
    }

    public function test_get_values_returns_empty_when_no_meta(): void {
        $post_id = self::factory()->post->create();

        $source = new QLIF_Source_Post_Meta('nonexistent');
        $values = $source->get_values($post_id);

        $this->assertSame([], $values);
    }
}
