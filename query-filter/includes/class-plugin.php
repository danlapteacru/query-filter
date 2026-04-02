<?php

declare(strict_types=1);

final class Query_Filter_Plugin {

	private static ?self $instance = null;
	private ?Query_Filter_Indexer $indexer = null;

	public static function instance(): self {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function set_indexer(Query_Filter_Indexer $indexer): void {
		$this->indexer = $indexer;
	}

	public function get_indexer(): ?Query_Filter_Indexer {
		return $this->indexer;
	}

	private function __construct() {
		if (! function_exists('register_activation_hook')) {
			return;
		}

		register_activation_hook(
			QUERY_FILTER_PLUGIN_FILE,
			static function (): void {
				Query_Filter_Indexer::create_table();
				Query_Filter_Indexer::schedule_full_rebuild();
			}
		);

		add_action('save_post', [$this, 'on_save_post'], 10, 2);
		add_action('delete_post', [$this, 'on_delete_post']);
		add_action('edited_term', [$this, 'on_edited_term'], 10, 3);
		add_action('delete_term', [$this, 'on_delete_term'], 10, 4);
		add_action('query_filter_cron_rebuild', [$this, 'on_cron_rebuild']);
	}

	public function on_save_post(int $post_id, \WP_Post $post): void {
		if ($this->indexer === null) {
			return;
		}
		if ($post->post_status !== 'publish') {
			$this->indexer->delete_for_post($post_id);
			return;
		}
		$this->indexer->index_post($post_id);
	}

	public function on_delete_post(int $post_id): void {
		$this->indexer?->delete_for_post($post_id);
	}

	public function on_edited_term(int $term_id, int $tt_id, string $taxonomy): void {
		$this->indexer?->reindex_posts_for_term($term_id, $taxonomy);
	}

	public function on_delete_term(int $term_id, int $tt_id, string $taxonomy, \WP_Term $deleted_term): void {
		$this->indexer?->reindex_posts_for_term($term_id, $taxonomy);
	}

	public function on_cron_rebuild(): void {
		$this->indexer?->run_cron_batch();
	}
}
