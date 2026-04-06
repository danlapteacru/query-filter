<?php

declare(strict_types=1);

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

final class Query_Filter_CLI {

	/**
	 * Rebuild the full index.
	 *
	 * ## EXAMPLES
	 *     wp query-filter index rebuild
	 *
	 * @param array<int, string>   $args
	 * @param array<string, mixed> $assoc_args
	 * @subcommand rebuild
	 */
	public function rebuild( array $args, array $assoc_args ): void {
		$indexer = Query_Filter_Plugin::instance()->get_indexer();
		if ( ! $indexer ) {
			\WP_CLI::error( 'Indexer not configured.' );
			return;
		}

		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . Query_Filter_Indexer::table_name() );

		$post_ids = get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1,
			)
		);

		$progress = \WP_CLI\Utils\make_progress_bar( 'Indexing', count( $post_ids ) );
		foreach ( $post_ids as $post_id ) {
			$indexer->index_post( $post_id );
			$progress->tick();
		}
		$progress->finish();

		update_option( 'query_filter_last_indexed', time() );
		\WP_CLI::success( count( $post_ids ) . ' posts indexed.' );
	}

	/**
	 * Index a single post.
	 *
	 * ## OPTIONS
	 * <post_id>
	 * : The post ID to index.
	 *
	 * ## EXAMPLES
	 *     wp query-filter index post 42
	 *
	 * @param array<int, string>   $args
	 * @param array<string, mixed> $assoc_args
	 * @subcommand post
	 */
	public function post( array $args, array $assoc_args ): void {
		$post_id = (int) $args[0];
		$post    = get_post( $post_id );
		if ( ! $post ) {
			\WP_CLI::error( "Post {$post_id} not found." );
			return;
		}

		$indexer = Query_Filter_Plugin::instance()->get_indexer();
		if ( ! $indexer ) {
			\WP_CLI::error( 'Indexer not configured.' );
			return;
		}

		$indexer->index_post( $post_id );
		\WP_CLI::success( "Post {$post_id} indexed." );
	}

	/**
	 * Show index status.
	 *
	 * ## EXAMPLES
	 *     wp query-filter index status
	 *
	 * @param array<int, string>   $args
	 * @param array<string, mixed> $assoc_args
	 * @subcommand status
	 */
	public function status( array $args, array $assoc_args ): void {
		global $wpdb;
		$table = Query_Filter_Indexer::table_name();

		$indexed_posts = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$table}" );
		$total_rows    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$last_indexed  = get_option( 'query_filter_last_indexed' );

		\WP_CLI::log( "Indexed posts: {$indexed_posts}" );
		\WP_CLI::log( "Total rows:    {$total_rows}" );
		\WP_CLI::log( 'Last indexed:  ' . ( $last_indexed ? wp_date( 'Y-m-d H:i:s', (int) $last_indexed ) : 'Never' ) );
	}

	/**
	 * Clear the entire index.
	 *
	 * ## EXAMPLES
	 *     wp query-filter index clear
	 *
	 * @param array<int, string>   $args
	 * @param array<string, mixed> $assoc_args
	 * @subcommand clear
	 */
	public function clear( array $args, array $assoc_args ): void {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . Query_Filter_Indexer::table_name() );
		delete_option( 'query_filter_last_indexed' );
		\WP_CLI::success( 'Index cleared.' );
	}
}
