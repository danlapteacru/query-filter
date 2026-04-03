<?php

declare(strict_types=1);

final class Query_Filter_Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if (! function_exists('register_activation_hook')) {
			return;
		}

		register_activation_hook(
			QUERY_FILTER_PLUGIN_FILE,
			static function (): void {
				Query_Filter_Indexer::create_table();
			}
		);
	}
}
