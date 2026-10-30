<?php
/**
 * Chuyển hướng tự động vào thư mục public/
 * Giúp ứng dụng chạy thuận tiện trên XAMPP Apache khi đặt project trong htdocs
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (!str_ends_with($uri, '/')) {
    header('Location: ' . $uri . '/public/');
} else {
    header('Location: ' . $uri . 'public/');
}
exit;
