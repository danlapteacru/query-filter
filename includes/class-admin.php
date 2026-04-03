<?php

declare(strict_types=1);

final class Query_Filter_Admin {

	public static function register(): void {
		add_management_page(
			__('Query Filter', 'query-filter'),
			__('Query Filter', 'query-filter'),
			'manage_options',
			'query-filter',
			[self::class, 'render_page']
		);
	}

	public static function render_page(): void {
		if (! current_user_can('manage_options')) {
			return;
		}

		// Handle actions.
		if (isset($_POST['query_filter_action']) && check_admin_referer('query_filter_admin')) {
			$action = sanitize_key($_POST['query_filter_action']);
			if ($action === 'rebuild') {
				Query_Filter_Indexer::schedule_full_rebuild();
				echo '<div class="notice notice-success"><p>' . esc_html__('Full rebuild scheduled.', 'query-filter') . '</p></div>';
			} elseif ($action === 'clear') {
				global $wpdb;
				$wpdb->query("TRUNCATE TABLE " . Query_Filter_Indexer::table_name());
				delete_option('query_filter_last_indexed');
				echo '<div class="notice notice-success"><p>' . esc_html__('Index cleared.', 'query-filter') . '</p></div>';
			}
		}

		global $wpdb;
		$table = Query_Filter_Indexer::table_name();
		$indexed_posts = (int) $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$table}");
		$total_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
		$last_indexed = get_option('query_filter_last_indexed');
		$rebuild_in_progress = get_option('query_filter_rebuild_offset') !== false;

		?>
		<div class="wrap">
			<h1><?php esc_html_e('Query Filter', 'query-filter'); ?></h1>

			<h2><?php esc_html_e('Index Status', 'query-filter'); ?></h2>
			<table class="widefat striped" style="max-width: 500px;">
				<tr>
					<td><?php esc_html_e('Indexed Posts', 'query-filter'); ?></td>
					<td><strong><?php echo esc_html((string) $indexed_posts); ?></strong></td>
				</tr>
				<tr>
					<td><?php esc_html_e('Total Index Rows', 'query-filter'); ?></td>
					<td><strong><?php echo esc_html((string) $total_rows); ?></strong></td>
				</tr>
				<tr>
					<td><?php esc_html_e('Last Indexed', 'query-filter'); ?></td>
					<td><strong><?php
						echo $last_indexed
							? esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $last_indexed))
							: esc_html__('Never', 'query-filter');
					?></strong></td>
				</tr>
				<tr>
					<td><?php esc_html_e('Status', 'query-filter'); ?></td>
					<td><strong><?php
						echo $rebuild_in_progress
							? esc_html__('Rebuilding...', 'query-filter')
							: esc_html__('Up to date', 'query-filter');
					?></strong></td>
				</tr>
			</table>

			<h2><?php esc_html_e('Actions', 'query-filter'); ?></h2>
			<form method="post">
				<?php wp_nonce_field('query_filter_admin'); ?>
				<p>
					<button type="submit" name="query_filter_action" value="rebuild" class="button button-primary">
						<?php esc_html_e('Rebuild Full Index', 'query-filter'); ?>
					</button>
					<button type="submit" name="query_filter_action" value="clear" class="button"
							onclick="return confirm('<?php esc_attr_e('Clear the entire index?', 'query-filter'); ?>')">
						<?php esc_html_e('Clear Index', 'query-filter'); ?>
					</button>
				</p>
			</form>

			<h2><?php esc_html_e('WP-CLI Commands', 'query-filter'); ?></h2>
			<pre style="background: #23282d; color: #eee; padding: 15px; max-width: 500px;">
wp query-filter index rebuild
wp query-filter index post &lt;post_id&gt;
wp query-filter index status
wp query-filter index clear</pre>
		</div>
		<?php
	}
}
