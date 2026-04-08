<?php
// tests/phpunit/unit/SourceAcfGuardTest.php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SourceAcfGuardTest extends TestCase {

    public function test_is_available_returns_false_when_acf_not_loaded(): void {
        $this->assertFalse(QLIF_Source_ACF::is_available());
    }
}
