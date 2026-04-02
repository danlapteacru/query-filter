<?php

declare(strict_types=1);

/**
 * Database index table lifecycle (create on activation; row ops in later tasks).
 */
final class Query_Filter_Indexer {

	/**
	 * Table name without prefix.
	 */
	public const TABLE_SUFFIX = 'query_filter_index';

	public const CRON_HOOK = 'query_filter_cron_rebuild';
	public const BATCH_SIZE = 10;

	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_SUFFIX;
	}

	/**
	 * SQL for dbDelta. Exposed for tests with arbitrary table name and charset clause.
	 */
	public static function get_create_table_sql( string $prefixed_table_name, string $charset_collate ): string {
		return "CREATE TABLE {$prefixed_table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			filter_name varchar(50) NOT NULL,
			filter_value varchar(200) NOT NULL,
			display_value varchar(200) NOT NULL,
			term_id bigint(20) unsigned NOT NULL DEFAULT 0,
			parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
			depth tinyint NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY post_id_idx (post_id),
			KEY filter_name_idx (filter_name),
			KEY filter_name_value (filter_name, filter_value)
		) {$charset_collate};";
	}

	public static function create_table(): void {
		global $wpdb;

		if (! defined('ABSPATH')) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql = self::get_create_table_sql( $table, $charset_collate );
		dbDelta( $sql );
	}

	// -------------------------------------------------------------------------
	// Instance methods: filter registry and row operations
	// -------------------------------------------------------------------------

	/** @var Query_Filter_Filter[] */
	private array $filters = [];

	public function register_filter(Query_Filter_Filter $filter): void {
		$this->filters[ $filter->get_name() ] = $filter;
	}

	public function get_filter(string $name): ?Query_Filter_Filter {
		return $this->filters[ $name ] ?? null;
	}

	/** @return Query_Filter_Filter[] */
	public function get_filters(): array {
		return $this->filters;
	}

	public function index_post(int $post_id): void {
		global $wpdb;
		$table = self::table_name();

		foreach ($this->filters as $filter) {
			$wpdb->delete($table, [
				'post_id'     => $post_id,
				'filter_name' => $filter->get_name(),
			]);

			$rows = $filter->index_post($post_id);
			foreach ($rows as $row) {
				$wpdb->insert($table, $row);
			}
		}
	}

	public function delete_for_post(int $post_id): void {
		global $wpdb;
		$wpdb->delete(self::table_name(), ['post_id' => $post_id]);
	}

	public static function schedule_full_rebuild(): void {
		update_option('query_filter_rebuild_offset', 0);
		if (! wp_next_scheduled(self::CRON_HOOK)) {
			wp_schedule_single_event(time(), self::CRON_HOOK);
		}
	}

	public function run_cron_batch(): void {
		$offset = (int) get_option('query_filter_rebuild_offset', 0);

		$post_ids = get_posts([
			'post_type'      => 'any',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => self::BATCH_SIZE,
			'offset'         => $offset,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		]);

		foreach ($post_ids as $post_id) {
			$this->index_post($post_id);
		}

		if (count($post_ids) >= self::BATCH_SIZE) {
			update_option('query_filter_rebuild_offset', $offset + self::BATCH_SIZE);
			wp_schedule_single_event(time() + 5, self::CRON_HOOK);
		} else {
			delete_option('query_filter_rebuild_offset');
			update_option('query_filter_last_indexed', time());
		}
	}

	public function reindex_posts_for_term(int $term_id, string $taxonomy): void {
		$post_ids = get_objects_in_term($term_id, $taxonomy);
		if (is_wp_error($post_ids) || empty($post_ids)) {
			return;
		}
		foreach ($post_ids as $post_id) {
			$this->index_post((int) $post_id);
		}
	}
}
