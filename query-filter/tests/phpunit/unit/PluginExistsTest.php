<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PluginExistsTest extends TestCase {
	public function test_plugin_class_exists(): void {
		$this->assertTrue(class_exists(Query_Filter_Plugin::class));
	}
}
