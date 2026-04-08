<?php
// tests/phpunit/integration/IndexerHooksTest.php
declare(strict_types=1);

class IndexerHooksTest extends WP_UnitTestCase {

	private QLIF_Indexer $indexer;

	public function set_up(): void {
		parent::set_up();
		QLIF_Indexer::create_table();

		$this->indexer = new QLIF_Indexer();
		$source = new QLIF_Source_Taxonomy('category');
		$this->indexer->register_filter(new QLIF_Filter_Checkboxes('category', $source));
	}

	public function test_save_post_triggers_indexing(): void {
		global $wpdb;
		$table = QLIF_Indexer::table_name();

		QLIF_Plugin::instance()->set_indexer($this->indexer);

		$term = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Hats']);
		$post_id = self::factory()->post->create(['post_status' => 'publish']);
		wp_set_object_terms($post_id, [$term->term_id], 'category');

		do_action('save_post', $post_id, get_post($post_id), true);

		$count = (int) $wpdb->get_var($wpdb->prepare(
			'SELECT COUNT(*) FROM %i WHERE post_id = %d',
			$table,
			$post_id
		));
		$this->assertGreaterThan(0, $count);
	}

	public function test_delete_post_removes_index_rows(): void {
		global $wpdb;
		$table = QLIF_Indexer::table_name();

		QLIF_Plugin::instance()->set_indexer($this->indexer);

		$post_id = self::factory()->post->create(['post_status' => 'publish']);
		$term = self::factory()->term->create_and_get(['taxonomy' => 'category', 'name' => 'Temp']);
		wp_set_object_terms($post_id, [$term->term_id], 'category');
		$this->indexer->index_post($post_id);

		do_action('delete_post', $post_id);

		$count = (int) $wpdb->get_var($wpdb->prepare(
			'SELECT COUNT(*) FROM %i WHERE post_id = %d',
			$table,
			$post_id
		));
		$this->assertSame(0, $count);
	}
}
