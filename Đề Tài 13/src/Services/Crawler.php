<?php
/**
 * Crawler - Duyệt website và kiểm tra liên kết
 * 
 * YÊU CẦU KỸ THUẬT 1: Tự viết phần duyệt liên kết
 * - Trang đã đi qua không gây vòng lặp
 * - Nhiều trang dẫn đến cùng 1 đích → chỉ kiểm tra đích 1 lần, giữ đầy đủ trang nguồn
 * - Chuẩn hóa URL
 * 
 * YÊU CẦU KỸ THUẬT 2: Kiểm soát phạm vi
 * - Chỉ truy cập địa chỉ đã được cho phép (kể cả sau chuyển hướng)
 * - Đặt giới hạn request và thời gian
 * - Hết hạn kết nối → "chưa xác định", không tự kết luận trang không tồn tại
 */

class Crawler
{
    private int    $maxPages;
    private int    $maxDepth;
    private int    $timeout;        // Timeout cho mỗi request (giây)
    private int    $maxTotalTime;   // Tổng thời gian tối đa (giây)
    private string $userAgent;
    private int    $delayMs;        // Delay giữa các request (ms)
    private array  $allowedDomains;

    // Trạng thái crawl
    private array $visited = [];       // URL đã duyệt → true
    private array $queue = [];         // Queue cho BFS: [url, depth]
    private int   $pagesScanned = 0;
    private float $startTime;

    // Kết quả
    private array $pages = [];         // Danh sách trang đã duyệt
    private array $links = [];         // Danh sách liên kết tìm thấy
    private array $findings = [];      // Danh sách lỗi phát hiện

    // Source tracking: target_url → [source_page_url, ...]
    private array $sourcePages = [];

    // Callback để cập nhật tiến trình
    private $onProgress = null;

    // LinkChecker instance
    private LinkChecker $checker;

    public function __construct(array $options = [])
    {
        $appConfig = require __DIR__ . '/../../config/app.php';
        $defaults  = $appConfig['crawler'];

        $this->maxPages      = $options['max_pages']       ?? $defaults['max_pages'];
        $this->maxDepth      = $options['max_depth']       ?? $defaults['max_depth'];
        $this->timeout       = $options['timeout']         ?? $defaults['timeout'];
        $this->maxTotalTime  = $options['max_total_time']  ?? $defaults['max_total_time'];
        $this->userAgent     = $options['user_agent']      ?? $defaults['user_agent'];
        $this->delayMs       = $options['delay_between_requests'] ?? $defaults['delay_between_requests'];
        $this->allowedDomains= $options['allowed_domains'] ?? [];

        $this->checker = new LinkChecker($this->timeout, $this->userAgent, $this->allowedDomains);
    }

    /**
     * Đặt callback tiến trình
     */
    public function onProgress(callable $callback): void
    {
        $this->onProgress = $callback;
    }

    /**
     * Bắt đầu duyệt website từ seed URL
     *
     * @param string $seedUrl URL bắt đầu
     * @return array Kết quả crawl:
     *   [
     *     'pages'    => [...],
     *     'links'    => [...],
     *     'findings' => [...],
     *     'stats'    => ['pages_scanned' => int, 'links_checked' => int, 'errors_found' => int, 'duration' => float],
     *   ]
     */
    public function crawl(string $seedUrl): array
    {
        $this->startTime = microtime(true);

        // Chuẩn hóa seed URL
        $seedUrl = UrlNormalizer::normalize($seedUrl);

        // Thêm seed URL vào queue
        $this->enqueue($seedUrl, 0);

        // BFS - Duyệt theo chiều rộng
        while (!empty($this->queue)) {
            // Kiểm tra giới hạn
            if ($this->pagesScanned >= $this->maxPages) {
                $this->reportProgress("Đạt giới hạn số trang ({$this->maxPages})");
                // Ghi các URL chưa kiểm tra
                $this->markRemainingAsSkipped();
                break;
            }

            if ($this->isTimeExceeded()) {
                $this->reportProgress("Hết thời gian cho phép ({$this->maxTotalTime}s)");
                $this->markRemainingAsSkipped();
                break;
            }

            // Lấy URL tiếp theo từ queue
            [$currentUrl, $depth] = array_shift($this->queue);

            // Kiểm tra đã duyệt chưa (tránh vòng lặp)
            $normalizedUrl = UrlNormalizer::removeFragment($currentUrl);
            if (isset($this->visited[$normalizedUrl])) {
                continue;
            }

            // Kiểm tra phạm vi domain
            if (!UrlNormalizer::isAllowedDomain($normalizedUrl, $this->allowedDomains)) {
                $this->pages[] = [
                    'url'         => $normalizedUrl,
                    'http_status' => null,
                    'content_type'=> null,
                    'depth'       => $depth,
                    'status'      => 'out_of_scope',
                    'error_message' => 'Ngoài phạm vi domain được phép',
                ];
                $this->visited[$normalizedUrl] = true;
                continue;
            }

            // Kiểm tra độ sâu
            if ($depth > $this->maxDepth) {
                $this->pages[] = [
                    'url'         => $normalizedUrl,
                    'http_status' => null,
                    'content_type'=> null,
                    'depth'       => $depth,
                    'status'      => 'skipped',
                    'error_message' => "Vượt quá độ sâu tối đa ({$this->maxDepth})",
                ];
                $this->visited[$normalizedUrl] = true;
                continue;
            }

            // Đánh dấu đã duyệt
            $this->visited[$normalizedUrl] = true;
            $this->pagesScanned++;

            $this->reportProgress("Đang duyệt [{$this->pagesScanned}/{$this->maxPages}]: {$normalizedUrl}");

            // Tải trang
            $pageResult = $this->fetchPage($normalizedUrl);

            $this->pages[] = [
                'url'         => $normalizedUrl,
                'http_status' => $pageResult['http_status'],
                'content_type'=> $pageResult['content_type'],
                'depth'       => $depth,
                'status'      => $pageResult['status'],
                'error_message' => $pageResult['error_message'],
            ];

            // Nếu trang HTML hợp lệ, parse và lấy links
            if ($pageResult['status'] === 'scanned' && $pageResult['html'] !== null) {
                $extractedLinks = HtmlParser::extractLinks($pageResult['html'], $normalizedUrl);

                foreach ($extractedLinks as $linkData) {
                    $targetUrl = $linkData['url'];
                    $targetNoFrag = UrlNormalizer::removeFragment($targetUrl);

                    // Ghi link vào kết quả
                    $this->links[] = [
                        'source_url'  => $normalizedUrl,
                        'target_url'  => $targetUrl,
                        'link_type'   => $linkData['type'],
                        'link_text'   => $linkData['text'],
                        'html_element'=> $linkData['element'],
                        'raw_href'    => $linkData['raw_href'],
                    ];

                    // Tracking source pages cho mỗi target
                    if (!isset($this->sourcePages[$targetNoFrag])) {
                        $this->sourcePages[$targetNoFrag] = [];
                    }
                    if (!in_array($normalizedUrl, $this->sourcePages[$targetNoFrag])) {
                        $this->sourcePages[$targetNoFrag][] = $normalizedUrl;
                    }

                    // Nếu là anchor link (thẻ <a>), thêm trang đích vào queue để duyệt
                    if ($linkData['type'] === 'anchor') {
                        if (!isset($this->visited[$targetNoFrag])) {
                            $this->enqueue($targetUrl, $depth + 1);
                        }
                    }

                    // Kiểm tra link
                    $checkResult = $this->checker->check($targetUrl, $linkData['type']);

                    if ($checkResult['status'] !== 'ok') {
                        $this->findings[] = [
                            'target_url'   => $targetUrl,
                            'source_url'   => $normalizedUrl,
                            'error_type'   => $checkResult['error_type'],
                            'http_status'  => $checkResult['http_status'],
                            'final_url'    => $checkResult['final_url'],
                            'message'      => $checkResult['message'],
                            'link_type'    => $linkData['type'],
                        ];
                    }
                }
            }

            // Delay giữa các request
            if ($this->delayMs > 0) {
                usleep($this->delayMs * 1000);
            }
        }

        $duration = round(microtime(true) - $this->startTime, 2);

        return [
            'pages'       => $this->pages,
            'links'       => $this->links,
            'findings'    => $this->findings,
            'source_pages'=> $this->sourcePages,
            'stats'       => [
                'pages_scanned' => $this->pagesScanned,
                'links_checked' => count($this->links),
                'errors_found'  => count($this->findings),
                'duration'      => $duration,
            ],
        ];
    }

    /**
     * Tải một trang HTML
     */
    private function fetchPage(string $url): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeout),
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => '',
        ]);

        $body  = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        $httpStatus  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl    = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';

        curl_close($ch);

        // Kiểm tra redirect ra ngoài phạm vi
        if ($finalUrl && $finalUrl !== $url) {
            if (!UrlNormalizer::isAllowedDomain($finalUrl, $this->allowedDomains)) {
                return [
                    'http_status'  => $httpStatus,
                    'content_type' => $contentType,
                    'status'       => 'out_of_scope',
                    'error_message'=> "Redirect đến ngoài phạm vi: {$finalUrl}",
                    'html'         => null,
                ];
            }
        }

        // Lỗi kết nối
        if ($errno !== 0) {
            $status = 'error';
            $message = "Lỗi kết nối: {$error}";

            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                $status = 'timeout';
                $message = "Hết thời gian chờ - chưa xác định được phản hồi";
            }

            return [
                'http_status'  => null,
                'content_type' => null,
                'status'       => $status,
                'error_message'=> $message,
                'html'         => null,
            ];
        }

        // HTTP error
        if ($httpStatus >= 400) {
            return [
                'http_status'  => $httpStatus,
                'content_type' => $contentType,
                'status'       => 'error',
                'error_message'=> "HTTP {$httpStatus}",
                'html'         => null,
            ];
        }

        // Không phải HTML → không parse links
        if (!HtmlParser::isHtml($contentType)) {
            return [
                'http_status'  => $httpStatus,
                'content_type' => $contentType,
                'status'       => 'scanned',
                'error_message'=> null,
                'html'         => null,
            ];
        }

        // Cache HTML cho LinkChecker (dùng cho anchor checking)
        $this->checker->setCachedHtml($url, $body);

        return [
            'http_status'  => $httpStatus,
            'content_type' => $contentType,
            'status'       => 'scanned',
            'error_message'=> null,
            'html'         => $body,
        ];
    }

    /**
     * Thêm URL vào queue (tránh trùng)
     */
    private function enqueue(string $url, int $depth): void
    {
        $normalized = UrlNormalizer::removeFragment($url);
        if (!isset($this->visited[$normalized])) {
            // Kiểm tra URL chưa có trong queue
            foreach ($this->queue as [$queueUrl, $queueDepth]) {
                if (UrlNormalizer::removeFragment($queueUrl) === $normalized) {
                    return; // Đã có trong queue
                }
            }
            $this->queue[] = [$url, $depth];
        }
    }

    /**
     * Kiểm tra đã hết thời gian chưa
     */
    private function isTimeExceeded(): bool
    {
        return (microtime(true) - $this->startTime) >= $this->maxTotalTime;
    }

    /**
     * Đánh dấu các URL còn trong queue là "chưa kiểm tra"
     */
    private function markRemainingAsSkipped(): void
    {
        foreach ($this->queue as [$url, $depth]) {
            $normalized = UrlNormalizer::removeFragment($url);
            if (!isset($this->visited[$normalized])) {
                $this->pages[] = [
                    'url'         => $normalized,
                    'http_status' => null,
                    'content_type'=> null,
                    'depth'       => $depth,
                    'status'      => 'skipped',
                    'error_message' => 'Chưa kiểm tra - đạt giới hạn',
                ];
                $this->visited[$normalized] = true;
            }
        }
    }

    /**
     * Báo cáo tiến trình
     */
    private function reportProgress(string $message): void
    {
        if ($this->onProgress !== null) {
            call_user_func($this->onProgress, $message, $this->pagesScanned, count($this->findings));
        }
    }
}
