<?php
/**
 * Unit Test: Crawler
 */

require_once __DIR__ . '/../../src/Services/UrlNormalizer.php';
require_once __DIR__ . '/../../src/Services/HtmlParser.php';
require_once __DIR__ . '/../../src/Services/LinkChecker.php';
require_once __DIR__ . '/../../src/Services/Crawler.php';

class CrawlerTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): bool
    {
        echo "=== Running CrawlerTest ===\n";

        $this->testOptionsConfiguration();
        $this->testHtmlExtraction();
        $this->testScopeFiltering();

        echo "CrawlerTest: {$this->passed} passed, {$this->failed} failed\n\n";
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

    private function testOptionsConfiguration(): void
    {
        $crawler = new Crawler([
            'max_pages'      => 5,
            'max_depth'      => 2,
            'timeout'        => 10,
            'allowed_domains'=> ['localhost:8888'],
        ]);

        $this->assert($crawler !== null, 'Initialize Crawler with custom options');
    }

    private function testHtmlExtraction(): void
    {
        $html = '<!DOCTYPE html><html><body>
            <a href="/about.html">About</a>
            <a href="https://google.com">Google</a>
            <img src="/images/logo.png" alt="Logo">
            <a href="#top">Back to top</a>
            <h1 id="top">Title</h1>
        </body></html>';

        $links = HtmlParser::extractLinks($html, 'http://localhost:8888/');
        $anchors = HtmlParser::extractIds($html);

        $this->assert(count($links) >= 3, 'Extract links and images from HTML');
        
        $hasImage = false;
        foreach ($links as $l) {
            if ($l['type'] === 'image' && str_contains($l['url'], 'logo.png')) {
                $hasImage = true;
                break;
            }
        }
        $this->assert($hasImage, 'Correctly identify image type');
        $this->assert(in_array('top', $anchors), 'Extract target anchor ID (top)');
    }

    private function testScopeFiltering(): void
    {
        $allowed = ['localhost:8888'];
        $internalUrl = 'http://localhost:8888/contact.html';
        $externalUrl = 'https://external-domain.com/path';

        $this->assert(UrlNormalizer::isAllowedDomain($internalUrl, $allowed), 'Internal URL matches scope');
        $this->assert(!UrlNormalizer::isAllowedDomain($externalUrl, $allowed), 'External URL outside scope');
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $test = new CrawlerTest();
    exit($test->run() ? 0 : 1);
}
