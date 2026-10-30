<?php
/**
 * Unit Test: UrlNormalizer
 */

require_once __DIR__ . '/../../src/Services/UrlNormalizer.php';

class UrlNormalizerTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): bool
    {
        echo "=== Running UrlNormalizerTest ===\n";

        $this->testNormalizeRelativeUrls();
        $this->testResolveDotSegments();
        $this->testRemoveFragment();
        $this->testSameHostDomainCheck();
        $this->testEdgeCases();

        echo "UrlNormalizerTest: {$this->passed} passed, {$this->failed} failed\n\n";
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

    private function testNormalizeRelativeUrls(): void
    {
        $base = 'http://example.com/blog/article/';

        $res1 = UrlNormalizer::resolve('index.html', $base);
        $this->assert($res1 === 'http://example.com/blog/article/index.html', 'Resolve sibling file');

        $res2 = UrlNormalizer::resolve('/about.html', $base);
        $this->assert($res2 === 'http://example.com/about.html', 'Resolve root-relative path');

        $res3 = UrlNormalizer::resolve('https://other.com/test', $base);
        $this->assert($res3 === 'https://other.com/test', 'Preserve absolute URL');
    }

    private function testResolveDotSegments(): void
    {
        $base = 'http://localhost:8888/products/product1.html';

        $res1 = UrlNormalizer::resolve('../images/product1.png', $base);
        $this->assert($res1 === 'http://localhost:8888/images/product1.png', 'Resolve single parent directory (../)');

        $baseDeep = 'http://localhost:8888/a/b/c/page.html';
        $res2 = UrlNormalizer::resolve('../../images/test.jpg', $baseDeep);
        $this->assert($res2 === 'http://localhost:8888/a/images/test.jpg', 'Resolve multiple parent directories (../../)');

        $res3 = UrlNormalizer::resolve('./sibling.html', $baseDeep);
        $this->assert($res3 === 'http://localhost:8888/a/b/c/sibling.html', 'Resolve current directory (./)');
    }

    private function testRemoveFragment(): void
    {
        $url = 'http://example.com/page.html#section-1';
        $stripped = UrlNormalizer::removeFragment($url);
        $this->assert($stripped === 'http://example.com/page.html', 'Strip fragment from URL');

        $fragment = UrlNormalizer::getFragment($url);
        $this->assert($fragment === 'section-1', 'Extract fragment name');
    }

    private function testSameHostDomainCheck(): void
    {
        $allowed = ['localhost:8888', 'example.com'];

        $this->assert(UrlNormalizer::isAllowedDomain('http://localhost:8888/page', $allowed), 'Allowed domain check true');
        $this->assert(!UrlNormalizer::isAllowedDomain('http://other.com/page', $allowed), 'Disallowed domain check false');
    }

    private function testEdgeCases(): void
    {
        $this->assert(UrlNormalizer::resolve('mailto:test@example.com', 'http://example.com') === null, 'Ignore mailto');
        $this->assert(UrlNormalizer::resolve('tel:0123456789', 'http://example.com') === null, 'Ignore tel');
        $this->assert(UrlNormalizer::resolve('javascript:void(0)', 'http://example.com') === null, 'Ignore javascript');
        $this->assert(UrlNormalizer::isValidHttpUrl('http://localhost:8888'), 'Validate valid HTTP URL');
        $this->assert(!UrlNormalizer::isValidHttpUrl('ftp://example.com'), 'Reject non-HTTP URL');
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $test = new UrlNormalizerTest();
    exit($test->run() ? 0 : 1);
}
