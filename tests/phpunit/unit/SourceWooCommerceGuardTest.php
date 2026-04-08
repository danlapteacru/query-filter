<?php
// tests/phpunit/unit/SourceWooCommerceGuardTest.php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SourceWooCommerceGuardTest extends TestCase {

    public function test_is_available_returns_false_when_woo_not_loaded(): void {
        $this->assertFalse(QLIF_Source_WooCommerce::is_available());
    }
}
