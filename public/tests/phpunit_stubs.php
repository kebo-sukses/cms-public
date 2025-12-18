<?php
// Lightweight PHPUnit stubs to satisfy static analyzers when vendor/autoload.php isn't present.

namespace PHPUnit\Framework {
    if (!class_exists('PHPUnit\\Framework\\TestCase')) {
        abstract class TestCase {
            public function assertTrue($cond) {}
            public function assertFalse($cond) {}
            public function assertEquals($a, $b) {}
            public function assertNotNull($v) {}
            public function assertNotEmpty($v) {}
            public function assertFileExists($f) {}
            public function assertIsArray($v) {}
            public function expectException($class) {}
            public function assertMatchesRegularExpression($pattern, $string) {}
            public function assertNotEquals($a, $b) {}
            public static function markTestSkipped($message = '') {}
        }
    }
}
