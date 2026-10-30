<?php
/**
 * Integration Test: FullScanTest
 * Kiểm tra tích hợp quét website thử nghiệm http://localhost:8888
 */

require_once __DIR__ . '/../../src/Services/UrlNormalizer.php';
require_once __DIR__ . '/../../src/Services/HtmlParser.php';
require_once __DIR__ . '/../../src/Services/LinkChecker.php';
require_once __DIR__ . '/../../src/Services/Crawler.php';

class FullScanTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): bool
    {
        echo "=== Running FullScanTest (Integration) ===\n";

        // Kiểm tra xem test-website có đang chạy không
        $testUrl = 'http://localhost:8888';
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $reachable = @file_get_contents($testUrl, false, $ctx);
        if ($reachable === false) {
            $testUrl = 'http://127.0.0.1:8888';
            $reachable = @file_get_contents($testUrl, false, $ctx);
        }

        if ($reachable === false) {
            echo "  [SKIP] http://localhost:8888 khong chay. Vui long bat server test-website truoc.\n\n";
            return true;
        }

        $crawler = new Crawler([
            'max_pages'       => 15,
            'max_depth'       => 3,
            'timeout'         => 5,
            'max_total_time'  => 30,
            'allowed_domains' => ['localhost:8888', '127.0.0.1:8888'],
        ]);

        $results = $crawler->crawl($testUrl);

        $this->assert(isset($results['pages']) && count($results['pages']) > 0, 'Crawled at least 1 page successfully');
        $this->assert(isset($results['links']) && count($results['links']) > 0, 'Extracted links successfully');
        $this->assert(isset($results['findings']), 'Generated findings list');

        // Kiểm tra xem có phát hiện lỗi nào trên test-website không
        $findings = $results['findings'] ?? [];
        $hasBrokenLink = false;
        $hasBrokenImage = false;

        foreach ($findings as $f) {
            if (($f['error_type'] ?? '') === 'broken_link' || ($f['http_status'] ?? 0) === 404) {
                $hasBrokenLink = true;
            }
            if (($f['error_type'] ?? '') === 'broken_image' || ($f['link_type'] ?? '') === 'image') {
                $hasBrokenImage = true;
            }
        }

        $this->assert($hasBrokenLink, 'Detected at least 1 broken link (404) on test-website');
        $this->assert($hasBrokenImage, 'Detected at least 1 broken image on test-website');

        echo "FullScanTest: {$this->passed} passed, {$this->failed} failed\n\n";
        return $this->failed === 0;
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$message}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$message}\n";
        }
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $test = new FullScanTest();
    exit($test->run() ? 0 : 1);
}
