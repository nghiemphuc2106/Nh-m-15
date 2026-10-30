<?php
/**
 * HtmlParser - Đọc HTML và trích xuất liên kết, ảnh, anchors
 * 
 * Sử dụng DOMDocument của PHP (extension DOM có sẵn)
 * Không dùng thư viện bên ngoài
 */

class HtmlParser
{
    /**
     * Parse HTML và trích xuất tất cả liên kết
     *
     * @param string $html     Nội dung HTML
     * @param string $pageUrl  URL của trang chứa HTML này
     * @return array Mảng các liên kết tìm được
     *   [
     *     ['url' => '...', 'type' => 'anchor|image|stylesheet', 'text' => '...', 'element' => 'a', 'raw_href' => '...'],
     *     ...
     *   ]
     */
    public static function extractLinks(string $html, string $pageUrl): array
    {
        $links = [];

        // Suppress DOMDocument warnings cho HTML không hoàn chỉnh
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();

        // 1. Lấy tất cả thẻ <a> (liên kết)
        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $node) {
            $href = $node->getAttribute('href');
            if ($href === '' || $href === null) {
                continue;
            }

            $resolvedUrl = UrlNormalizer::resolve($href, $pageUrl);
            if ($resolvedUrl === null) {
                continue; // Bỏ qua javascript:, mailto:, etc.
            }

            $links[] = [
                'url'       => $resolvedUrl,
                'type'      => 'anchor',
                'text'      => trim($node->textContent),
                'element'   => 'a',
                'raw_href'  => $href,
            ];
        }

        // 2. Lấy tất cả thẻ <img> (ảnh)
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $node) {
            $src = $node->getAttribute('src');
            if ($src === '' || $src === null) {
                continue;
            }

            $resolvedUrl = UrlNormalizer::resolve($src, $pageUrl);
            if ($resolvedUrl === null) {
                continue;
            }

            $links[] = [
                'url'       => $resolvedUrl,
                'type'      => 'image',
                'text'      => $node->getAttribute('alt') ?: '',
                'element'   => 'img',
                'raw_href'  => $src,
            ];
        }

        // 3. Lấy tất cả thẻ <link> (stylesheet, etc.)
        $linkElements = $dom->getElementsByTagName('link');
        foreach ($linkElements as $node) {
            $href = $node->getAttribute('href');
            $rel  = $node->getAttribute('rel');

            if ($href === '' || $href === null) {
                continue;
            }

            $resolvedUrl = UrlNormalizer::resolve($href, $pageUrl);
            if ($resolvedUrl === null) {
                continue;
            }

            $links[] = [
                'url'       => $resolvedUrl,
                'type'      => $rel === 'stylesheet' ? 'stylesheet' : 'other',
                'text'      => $rel,
                'element'   => 'link',
                'raw_href'  => $href,
            ];
        }

        // 4. Lấy tất cả thẻ <script> có src
        $scripts = $dom->getElementsByTagName('script');
        foreach ($scripts as $node) {
            $src = $node->getAttribute('src');
            if ($src === '' || $src === null) {
                continue;
            }

            $resolvedUrl = UrlNormalizer::resolve($src, $pageUrl);
            if ($resolvedUrl === null) {
                continue;
            }

            $links[] = [
                'url'       => $resolvedUrl,
                'type'      => 'script',
                'text'      => '',
                'element'   => 'script',
                'raw_href'  => $src,
            ];
        }

        return $links;
    }

    /**
     * Lấy tất cả ID có trong HTML (dùng để kiểm tra anchor links)
     *
     * @param string $html Nội dung HTML
     * @return array Mảng các ID tìm được
     */
    public static function extractIds(string $html): array
    {
        $ids = [];

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*[@id]');

        foreach ($elements as $element) {
            $id = $element->getAttribute('id');
            if ($id !== '') {
                $ids[] = $id;
            }
        }

        // Cũng lấy <a name="..."> (cách cũ để đặt anchor)
        $namedAnchors = $xpath->query('//a[@name]');
        foreach ($namedAnchors as $element) {
            $name = $element->getAttribute('name');
            if ($name !== '') {
                $ids[] = $name;
            }
        }

        return array_unique($ids);
    }

    /**
     * Kiểm tra nội dung có phải HTML không
     */
    public static function isHtml(string $contentType): bool
    {
        return str_contains(strtolower($contentType), 'text/html');
    }

    /**
     * Kiểm tra nội dung có phải hình ảnh
     */
    public static function isImage(string $contentType): bool
    {
        return str_starts_with(strtolower($contentType), 'image/');
    }
}
