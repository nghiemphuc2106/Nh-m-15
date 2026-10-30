<?php
/**
 * UrlNormalizer - Chuẩn hóa và xử lý URL
 * 
 * YÊU CẦU KỸ THUẬT 1: Tự viết phần chuẩn hóa địa chỉ
 * - Chuyển đường dẫn tương đối thành tuyệt đối
 * - Xử lý ../, ./, trailing /
 * - Chuẩn hóa query string, fragment
 */

class UrlNormalizer
{
    /**
     * Chuyển URL tương đối thành URL tuyệt đối dựa trên URL trang chứa
     *
     * @param string $relativeUrl URL tương đối (href, src)
     * @param string $baseUrl     URL trang chứa liên kết
     * @return string|null URL tuyệt đối hoặc null nếu không hợp lệ
     */
    public static function resolve(string $relativeUrl, string $baseUrl): ?string
    {
        $relativeUrl = trim($relativeUrl);
        $baseUrl     = trim($baseUrl);

        // Bỏ qua các scheme không phải HTTP
        if (preg_match('#^(javascript|mailto|tel|data|ftp):#i', $relativeUrl)) {
            return null;
        }

        // Nếu đã là URL tuyệt đối
        if (preg_match('#^https?://#i', $relativeUrl)) {
            return self::normalize($relativeUrl);
        }

        // Parse base URL
        $base = parse_url($baseUrl);
        if (!$base || !isset($base['scheme']) || !isset($base['host'])) {
            return null;
        }

        $scheme = $base['scheme'];
        $host   = $base['host'];
        $port   = isset($base['port']) ? ':' . $base['port'] : '';

        // Protocol-relative URL (//example.com/path)
        if (str_starts_with($relativeUrl, '//')) {
            return self::normalize($scheme . ':' . $relativeUrl);
        }

        // Absolute path (/path/to/page)
        if (str_starts_with($relativeUrl, '/')) {
            return self::normalize($scheme . '://' . $host . $port . $relativeUrl);
        }

        // Fragment only (#section)
        if (str_starts_with($relativeUrl, '#')) {
            $basePath = $base['path'] ?? '/';
            return self::normalize($scheme . '://' . $host . $port . $basePath . $relativeUrl);
        }

        // Query only (?key=val)
        if (str_starts_with($relativeUrl, '?')) {
            $basePath = $base['path'] ?? '/';
            return self::normalize($scheme . '://' . $host . $port . $basePath . $relativeUrl);
        }

        // Relative path (page.html, ../page.html, ./page.html)
        $basePath = $base['path'] ?? '/';
        // Lấy thư mục chứa trang hiện tại
        $baseDir = self::getDirectory($basePath);

        $fullPath = $baseDir . $relativeUrl;
        $resolvedPath = self::resolveDotSegments($fullPath);

        return self::normalize($scheme . '://' . $host . $port . $resolvedPath);
    }

    /**
     * Chuẩn hóa URL: lowercase scheme/host, resolve dots, remove default port
     */
    public static function normalize(string $url): string
    {
        $parts = parse_url($url);
        if (!$parts || !isset($parts['scheme']) || !isset($parts['host'])) {
            return $url;
        }

        // Lowercase scheme và host
        $scheme = strtolower($parts['scheme']);
        $host   = strtolower($parts['host']);

        // Bỏ port mặc định (80 cho http, 443 cho https)
        $port = '';
        if (isset($parts['port'])) {
            if (($scheme === 'http' && $parts['port'] !== 80) ||
                ($scheme === 'https' && $parts['port'] !== 443)) {
                $port = ':' . $parts['port'];
            }
        }

        // Resolve dot segments trong path
        $path = $parts['path'] ?? '/';
        $path = self::resolveDotSegments($path);
        if ($path === '') {
            $path = '/';
        }

        // Query string (giữ nguyên)
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        // Fragment
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $scheme . '://' . $host . $port . $path . $query . $fragment;
    }

    /**
     * Lấy URL không có fragment (để so sánh trang)
     */
    public static function removeFragment(string $url): string
    {
        $pos = strpos($url, '#');
        if ($pos !== false) {
            return substr($url, 0, $pos);
        }
        return $url;
    }

    /**
     * Lấy fragment từ URL
     */
    public static function getFragment(string $url): ?string
    {
        $parts = parse_url($url);
        return $parts['fragment'] ?? null;
    }

    /**
     * Lấy domain (host:port) từ URL
     */
    public static function getDomain(string $url): ?string
    {
        $parts = parse_url($url);
        if (!$parts || !isset($parts['host'])) {
            return null;
        }

        $domain = strtolower($parts['host']);
        if (isset($parts['port'])) {
            $domain .= ':' . $parts['port'];
        }
        return $domain;
    }

    /**
     * Kiểm tra URL thuộc danh sách domain được phép
     *
     * @param string $url             URL cần kiểm tra
     * @param array  $allowedDomains  Danh sách domain cho phép
     * @return bool
     */
    public static function isAllowedDomain(string $url, array $allowedDomains): bool
    {
        $domain = self::getDomain($url);
        if ($domain === null) {
            return false;
        }

        foreach ($allowedDomains as $allowed) {
            $allowed = trim($allowed);
            if (str_starts_with($allowed, 'http://') || str_starts_with($allowed, 'https://')) {
                $allowed = self::getDomain($allowed) ?: $allowed;
            }
            if (strtolower($allowed) === $domain) {
                return true;
            }
        }
        return false;
    }

    /**
     * Resolve dot segments (../ và ./) trong path
     * RFC 3986 Section 5.2.4
     */
    public static function resolveDotSegments(string $path): string
    {
        $segments = explode('/', $path);
        $resolved = [];

        foreach ($segments as $segment) {
            if ($segment === '.') {
                // Bỏ qua "."
                continue;
            } elseif ($segment === '..') {
                // Lùi lại 1 cấp
                array_pop($resolved);
            } else {
                $resolved[] = $segment;
            }
        }

        $result = implode('/', $resolved);

        // Đảm bảo bắt đầu bằng /
        if (!str_starts_with($result, '/')) {
            $result = '/' . $result;
        }

        return $result;
    }

    /**
     * Lấy thư mục chứa file từ path
     * /dir/page.html → /dir/
     * /dir/ → /dir/
     */
    private static function getDirectory(string $path): string
    {
        // Nếu kết thúc bằng / thì đã là thư mục
        if (str_ends_with($path, '/')) {
            return $path;
        }

        $lastSlash = strrpos($path, '/');
        if ($lastSlash !== false) {
            return substr($path, 0, $lastSlash + 1);
        }

        return '/';
    }

    /**
     * Kiểm tra URL có phải là hợp lệ (HTTP/HTTPS)
     */
    public static function isValidHttpUrl(string $url): bool
    {
        return (bool) preg_match('#^https?://[a-zA-Z0-9\-\.]+(\:[0-9]+)?(/.*)?$#', $url);
    }
}
