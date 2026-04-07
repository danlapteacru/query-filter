<?php

declare(strict_types=1);

final class Query_Filter_Admin {

	public static function register(): void {
		add_management_page(
			__( 'Query Filter', 'query-filter' ),
			__( 'Query Filter', 'query-filter' ),
			'manage_options',
			'query-filter',
			array( self::class, 'render_page' )
		);
		add_action( 'load-tools_page_query-filter', array( self::class, 'process_rebuild_batches_without_cron' ) );
	}

	/**
	 * WP-Cron often does not run on local sites (no traffic, DISABLE_WP_CRON, etc.).
	 * While a rebuild is pending, run several batches during this admin screen load
	 * so the index can finish without a system cron.
	 */
	public static function process_rebuild_batches_without_cron(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! Query_Filter_Indexer::rebuild_is_in_progress() ) {
			return;
		}

		/**
		 * Whether to advance the rebuild when loading Tools → Query Filter.
		 *
		 * @param bool $run Default true.
		 */
		if ( ! apply_filters( 'query_filter/admin/run_rebuild_batches_on_tools_page', true ) ) {
			return;
		}

		$indexer = Query_Filter_Plugin::instance()->get_indexer();
		if ( ! $indexer instanceof Query_Filter_Indexer ) {
			return;
		}

		/**
		 * Max seconds to spend on rebuild batches per admin request.
		 *
		 * @param float $seconds Default 20.
		 */
		$budget = (float) apply_filters( 'query_filter/admin/rebuild_time_budget_seconds', 20.0 );
		if ( $budget < 1.0 ) {
			$budget = 1.0;
		}

		/**
		 * Safety cap on batches per request (each batch is {@see Query_Filter_Indexer::BATCH_SIZE} posts).
		 *
		 * @param int $max Default 500.
		 */
		$max_batches = (int) apply_filters( 'query_filter/admin/rebuild_max_batches_per_request', 500 );
		if ( $max_batches < 1 ) {
			$max_batches = 1;
		}

		$start   = microtime( true );
		$batch_n = 0;

		while ( ( microtime( true ) - $start ) < $budget && $batch_n < $max_batches ) {
			if ( ! Query_Filter_Indexer::rebuild_is_in_progress() ) {
				break;
			}
			$more = $indexer->run_single_rebuild_batch();
			++$batch_n;
			if ( ! $more ) {
				break;
			}
		}

		// Option may have been deleted by run_single_rebuild_batch(); PHPStan cannot see that.
		if ( ! Query_Filter_Indexer::rebuild_is_in_progress() ) {
			wp_clear_scheduled_hook( Query_Filter_Indexer::CRON_HOOK );
		}
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle actions.
		if ( isset( $_POST['query_filter_action'] ) && check_admin_referer( 'query_filter_admin' ) ) {
			$action = sanitize_key( $_POST['query_filter_action'] );
			if ( $action === 'rebuild' ) {
				Query_Filter_Indexer::schedule_full_rebuild();
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Full rebuild scheduled.', 'query-filter' ) . '</p></div>';
			} elseif ( $action === 'clear' ) {
				global $wpdb;
				$wpdb->query( 'TRUNCATE TABLE ' . Query_Filter_Indexer::table_name() );
				delete_option( 'query_filter_last_indexed' );
				delete_option( 'query_filter_rebuild_offset' );
				wp_clear_scheduled_hook( Query_Filter_Indexer::CRON_HOOK );
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Index cleared.', 'query-filter' ) . '</p></div>';
			}
		}

		global $wpdb;
		$table               = Query_Filter_Indexer::table_name();
		$indexed_posts       = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$table}" );
		$total_rows          = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$last_indexed        = get_option( 'query_filter_last_indexed' );
		$rebuild_in_progress = Query_Filter_Indexer::rebuild_is_in_progress();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Query Filter', 'query-filter' ); ?></h1>

			<h2><?php esc_html_e( 'Index Status', 'query-filter' ); ?></h2>
			<table class="widefat striped" style="max-width: 500px;">
				<tr>
					<td><?php esc_html_e( 'Indexed Posts', 'query-filter' ); ?></td>
					<td><strong><?php echo esc_html( (string) $indexed_posts ); ?></strong></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Total Index Rows', 'query-filter' ); ?></td>
					<td><strong><?php echo esc_html( (string) $total_rows ); ?></strong></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Last Indexed', 'query-filter' ); ?></td>
					<td><strong>
					<?php
						echo $last_indexed
							? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $last_indexed ) )
							: esc_html__( 'Never', 'query-filter' );
					?>
					</strong></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Status', 'query-filter' ); ?></td>
					<td><strong>
					<?php
						echo $rebuild_in_progress
							? esc_html__( 'Rebuilding...', 'query-filter' )
							: esc_html__( 'Up to date', 'query-filter' );
					?>
					</strong></td>
				</tr>
			</table>
			<?php if ( $rebuild_in_progress ) : ?>
				<p class="description" style="max-width: 640px;">
					<?php
					esc_html_e(
						'Rebuilds normally continue via WP-Cron (triggered by site visits). On local environments without cron, batches also run when you open or refresh this screen — reload until the status shows Up to date, or use WP-CLI: wp query-filter index rebuild.',
						'query-filter'
					);
					?>
				</p>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Actions', 'query-filter' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'query_filter_admin' ); ?>
				<p>
					<button type="submit" name="query_filter_action" value="rebuild" class="button button-primary">
						<?php esc_html_e( 'Rebuild Full Index', 'query-filter' ); ?>
					</button>
					<button type="submit" name="query_filter_action" value="clear" class="button"
							onclick="return confirm('<?php esc_attr_e( 'Clear the entire index?', 'query-filter' ); ?>')">
						<?php esc_html_e( 'Clear Index', 'query-filter' ); ?>
					</button>
				</p>
			</form>

			<h2><?php esc_html_e( 'WP-CLI Commands', 'query-filter' ); ?></h2>
			<pre style="background: #23282d; color: #eee; padding: 15px; max-width: 500px;">
wp query-filter index rebuild
wp query-filter index post &lt;post_id&gt;
wp query-filter index status
wp query-filter index clear</pre>
		</div>
		<?php
	}
}
