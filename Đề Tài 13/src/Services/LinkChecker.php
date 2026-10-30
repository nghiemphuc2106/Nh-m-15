<?php
/**
 * LinkChecker - Kiểm tra trạng thái của liên kết và tài nguyên
 * 
 * YÊU CẦU KỸ THUẬT 2: 
 * - Chỉ truy cập địa chỉ đã được cho phép (kể cả sau redirect)
 * - Đặt giới hạn request và thời gian chờ
 * - Timeout → "chưa xác định được phản hồi", KHÔNG tự kết luận trang không tồn tại
 */

class LinkChecker
{
    private int $timeout;
    private string $userAgent;
    private array $allowedDomains;

    // Cache kết quả kiểm tra để tránh request trùng
    private array $checkedUrls = [];

    // Cache HTML content cho anchor checking
    private array $htmlCache = [];

    public function __construct(int $timeout = 30, string $userAgent = 'LinkChecker/1.0', array $allowedDomains = [])
    {
        $this->timeout        = $timeout;
        $this->userAgent      = $userAgent;
        $this->allowedDomains = $allowedDomains;
    }

    /**
     * Kiểm tra một URL và trả kết quả chi tiết
     *
     * @param string $url    URL cần kiểm tra
     * @param string $type   Loại: anchor, image, stylesheet, script
     * @return array [
     *   'status'      => 'ok'|'error'|'timeout'|'out_of_scope'|'unknown',
     *   'http_status' => int|null,
     *   'final_url'   => string|null,  (URL sau redirect)
     *   'error_type'  => string|null,  (http_error, broken_image, missing_anchor, timeout, connection_error, redirect_out_of_scope)
     *   'message'     => string|null,
     *   'content_type'=> string|null,
     * ]
     */
    public function check(string $url, string $type = 'anchor'): array
    {
        // Tách fragment (anchor) ra khỏi URL
        $fragment    = UrlNormalizer::getFragment($url);
        $urlNoFrag   = UrlNormalizer::removeFragment($url);

        // Kiểm tra phạm vi trước khi request
        if (!empty($this->allowedDomains) && !UrlNormalizer::isAllowedDomain($urlNoFrag, $this->allowedDomains)) {
            return [
                'status'      => 'out_of_scope',
                'http_status' => null,
                'final_url'   => null,
                'error_type'  => 'redirect_out_of_scope',
                'message'     => 'URL ngoài phạm vi được phép: ' . UrlNormalizer::getDomain($urlNoFrag),
                'content_type'=> null,
            ];
        }

        // Nếu đã kiểm tra URL này rồi (không có fragment), dùng cache
        if (isset($this->checkedUrls[$urlNoFrag]) && $fragment === null) {
            return $this->checkedUrls[$urlNoFrag];
        }

        // Thực hiện HTTP request
        $result = $this->httpRequest($urlNoFrag, $type);

        // Kiểm tra redirect có ra ngoài phạm vi không
        if ($result['final_url'] !== null && $result['final_url'] !== $urlNoFrag) {
            if (!empty($this->allowedDomains) && !UrlNormalizer::isAllowedDomain($result['final_url'], $this->allowedDomains)) {
                $result = [
                    'status'      => 'out_of_scope',
                    'http_status' => $result['http_status'],
                    'final_url'   => $result['final_url'],
                    'error_type'  => 'redirect_out_of_scope',
                    'message'     => 'Redirect dẫn đến URL ngoài phạm vi: ' . $result['final_url'],
                    'content_type'=> null,
                ];
                $this->checkedUrls[$urlNoFrag] = $result;
                return $result;
            }
        }

        // Nếu có fragment (anchor), kiểm tra anchor tồn tại trên trang đích
        if ($fragment !== null && $result['status'] === 'ok') {
            $anchorResult = $this->checkAnchor($urlNoFrag, $fragment);
            if (!$anchorResult) {
                $result['status']     = 'error';
                $result['error_type'] = 'missing_anchor';
                $result['message']    = "Anchor #{$fragment} không tồn tại trên trang";
            }
        }

        // Cache kết quả (chỉ URL không fragment)
        if ($fragment === null) {
            $this->checkedUrls[$urlNoFrag] = $result;
        }

        return $result;
    }

    /**
     * Thực hiện HTTP request với CURL
     */
    private function httpRequest(string $url, string $type): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,      // Follow redirects
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeout),
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER         => false,
            CURLOPT_ENCODING       => '',         // Accept all encodings
        ]);

        // Với image, chỉ cần HEAD request (tiết kiệm băng thông)
        if ($type === 'image') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        $httpStatus  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl    = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';

        curl_close($ch);

        // Xử lý lỗi kết nối
        if ($errno !== 0) {
            // Timeout
            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                return [
                    'status'      => 'timeout',
                    'http_status' => null,
                    'final_url'   => null,
                    'error_type'  => 'timeout',
                    'message'     => "Hết thời gian chờ ({$this->timeout}s) - chưa xác định được phản hồi",
                    'content_type'=> null,
                ];
            }

            // Lỗi kết nối khác (DNS fail, connection refused, etc.)
            return [
                'status'      => 'error',
                'http_status' => null,
                'final_url'   => null,
                'error_type'  => 'connection_error',
                'message'     => "Lỗi kết nối: {$error} (mã {$errno}) - chưa xác định được phản hồi",
                'content_type'=> null,
            ];
        }

        // Cache HTML content cho anchor checking
        if ($type !== 'image' && $body && HtmlParser::isHtml($contentType)) {
            $this->htmlCache[$url] = $body;
        }

        // Kiểm tra HTTP status
        if ($httpStatus >= 400) {
            $errorType = 'http_error';
            if ($type === 'image') {
                $errorType = 'broken_image';
            }

            return [
                'status'      => 'error',
                'http_status' => $httpStatus,
                'final_url'   => $finalUrl !== $url ? $finalUrl : null,
                'error_type'  => $errorType,
                'message'     => "HTTP {$httpStatus}" . ($httpStatus === 404 ? ' - Trang không tồn tại' : ''),
                'content_type'=> $contentType,
            ];
        }

        // Kiểm tra image content type
        if ($type === 'image' && !HtmlParser::isImage($contentType) && $httpStatus === 200) {
            return [
                'status'      => 'error',
                'http_status' => $httpStatus,
                'final_url'   => $finalUrl !== $url ? $finalUrl : null,
                'error_type'  => 'broken_image',
                'message'     => "Không phải ảnh: Content-Type = {$contentType}",
                'content_type'=> $contentType,
            ];
        }

        // OK
        return [
            'status'      => 'ok',
            'http_status' => $httpStatus,
            'final_url'   => $finalUrl !== $url ? $finalUrl : null,
            'error_type'  => null,
            'message'     => null,
            'content_type'=> $contentType,
        ];
    }

    /**
     * Kiểm tra anchor (#fragment) có tồn tại trên trang đích
     */
    private function checkAnchor(string $pageUrl, string $fragment): bool
    {
        // Nếu đã có HTML trong cache
        if (isset($this->htmlCache[$pageUrl])) {
            $ids = HtmlParser::extractIds($this->htmlCache[$pageUrl]);
            return in_array($fragment, $ids, true);
        }

        // Nếu chưa có, tải trang
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $pageUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $body = curl_exec($ch);
        curl_close($ch);

        if (!$body) {
            return false;
        }

        $this->htmlCache[$pageUrl] = $body;
        $ids = HtmlParser::extractIds($body);
        return in_array($fragment, $ids, true);
    }

    /**
     * Lấy HTML đã cache (dùng bởi Crawler)
     */
    public function getCachedHtml(string $url): ?string
    {
        return $this->htmlCache[$url] ?? null;
    }

    /**
     * Đặt HTML vào cache
     */
    public function setCachedHtml(string $url, string $html): void
    {
        $this->htmlCache[$url] = $html;
    }

    /**
     * Xóa cache
     */
    public function clearCache(): void
    {
        $this->checkedUrls = [];
        $this->htmlCache   = [];
    }
}
