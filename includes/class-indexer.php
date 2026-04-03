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
}
