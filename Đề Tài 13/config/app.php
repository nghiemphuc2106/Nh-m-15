<?php
/**
 * Cấu hình chung của ứng dụng
 */

return [
    'app_name'    => 'Link Checker',
    'app_url'     => 'http://localhost:8080',
    'debug'       => true,

    // Session
    'session_lifetime' => 3600, // 1 giờ

    // Crawler defaults
    'crawler' => [
        'max_pages'       => 30,
        'max_depth'       => 4,
        'timeout'         => 10,     // giây cho mỗi request
        'max_total_time'  => 60,     // giây tổng thời gian chạy
        'user_agent'      => 'LinkChecker/1.0 (Educational Project)',
        'delay_between_requests' => 0, // Không cần sleep khi quét local/máy chủ thử nghiệm
    ],
];
