<?php

declare(strict_types=1);

final class Query_Filter_Plugin {

	private static ?self $instance         = null;
	private ?Query_Filter_Indexer $indexer = null;

	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function set_indexer( Query_Filter_Indexer $indexer ): void {
		$this->indexer = $indexer;
	}

	public function get_indexer(): ?Query_Filter_Indexer {
		return $this->indexer;
	}

	private function __construct() {
		if ( ! function_exists( 'register_activation_hook' ) ) {
			return;
		}

		register_activation_hook(
			QUERY_FILTER_PLUGIN_FILE,
			static function (): void {
				Query_Filter_Indexer::create_table();
				Query_Filter_Indexer::schedule_full_rebuild();
			}
		);

		add_action( 'init', array( $this, 'configure_indexer' ), 5 );
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'save_post', array( $this, 'on_save_post' ), 10, 2 );
		add_action( 'delete_post', array( $this, 'on_delete_post' ) );
		add_action( 'edited_term', array( $this, 'on_edited_term' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'on_delete_term' ), 10, 4 );
		add_action( 'query_filter_cron_rebuild', array( $this, 'on_cron_rebuild' ) );
		add_action( 'admin_menu', array( Query_Filter_Admin::class, 'register' ) );
		add_action( 'rest_api_init', array( Query_Filter_Rest_Controller::class, 'register' ) );
		add_filter( 'render_block_core/query', array( $this, 'tag_query_block' ), 10, 2 );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'query-filter index', Query_Filter_CLI::class );
		}
	}

	public function configure_indexer(): void {
		$this->indexer = new Query_Filter_Indexer();

		// Register filters based on saved filter configs.
		// For MVP: scan for registered taxonomies and register a checkbox filter for each public one.
		$taxonomies = get_taxonomies( array( 'public' => true ), 'names' );
		foreach ( $taxonomies as $taxonomy ) {
			$source = new Query_Filter_Source_Taxonomy( $taxonomy );
			$this->indexer->register_filter( new Query_Filter_Filter_Checkboxes( $taxonomy, $source ) );
		}
	}

	public function register_blocks(): void {
		$build_dir = dirname( QUERY_FILTER_PLUGIN_FILE ) . '/build';
		$blocks    = array(
			'filter-container',
			'filter-checkboxes',
			'filter-search',
			'filter-sort',
			'filter-pager',
			'filter-reset',
		);
		foreach ( $blocks as $block ) {
			$block_dir = $build_dir . '/' . $block;
			if ( file_exists( $block_dir . '/block.json' ) ) {
				register_block_type( $block_dir );
			}
		}
	}

	public function on_save_post( int $post_id, \WP_Post $post ): void {
		if ( $this->indexer === null ) {
			return;
		}
		if ( $post->post_status !== 'publish' ) {
			$this->indexer->delete_for_post( $post_id );
			return;
		}
		$this->indexer->index_post( $post_id );
	}

	public function on_delete_post( int $post_id ): void {
		$this->indexer?->delete_for_post( $post_id );
	}

	public function on_edited_term( int $term_id, int $tt_id, string $taxonomy ): void {
		$this->indexer?->reindex_posts_for_term( $term_id, $taxonomy );
	}

	public function on_delete_term( int $term_id, int $tt_id, string $taxonomy, \WP_Term $deleted_term ): void {
		$this->indexer?->reindex_posts_for_term( $term_id, $taxonomy );
	}

	public function on_cron_rebuild(): void {
		$this->indexer?->run_cron_batch();
	}

	/**
	 * @param array<string, mixed> $block
	 */
	public function tag_query_block( string $content, array $block ): string {
		$query_id  = $block['attrs']['queryId'] ?? 0;
		$processor = new \WP_HTML_Tag_Processor( $content );
		if ( $processor->next_tag() ) {
			// Target for client DOM updates only. Do not set data-wp-interactive here:
			// replacing innerHTML would desync Preact hydration for nested blocks (e.g. pager).
			$processor->set_attribute( 'data-query-filter-query', (string) $query_id );
		}
		return (string) $processor;
	}
}
