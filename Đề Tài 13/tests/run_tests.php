<?php
/**
 * Test Runner - Chạy toàn bộ test suites
 * Chạy: C:\xampp\php\php.exe tests/run_tests.php
 */

require_once __DIR__ . '/Unit/UrlNormalizerTest.php';
require_once __DIR__ . '/Unit/CrawlerTest.php';
require_once __DIR__ . '/Integration/FullScanTest.php';

echo "========================================================\n";
echo "   LINK CHECKER - TEST SUITE RUNNER                     \n";
echo "========================================================\n\n";

$suites = [
    'UrlNormalizerTest' => new UrlNormalizerTest(),
    'CrawlerTest'       => new CrawlerTest(),
    'FullScanTest'      => new FullScanTest(),
];

$allPassed = true;
$summary = [];

foreach ($suites as $name => $suite) {
    $ok = $suite->run();
    $summary[$name] = $ok;
    if (!$ok) {
        $allPassed = false;
    }
}

echo "========================================================\n";
echo "   KẾT QUẢ TỔNG HỢP                                     \n";
echo "========================================================\n";

foreach ($summary as $name => $ok) {
    $status = $ok ? "[PASS]" : "[FAIL]";
    echo sprintf("  %-25s %s\n", $name, $status);
}

echo "--------------------------------------------------------\n";
if ($allPassed) {
    echo "  ALL TESTS PASSED SUCCESSFULLY!\n";
} else {
    echo "  SOME TESTS FAILED!\n";
}
echo "========================================================\n";

exit($allPassed ? 0 : 1);
