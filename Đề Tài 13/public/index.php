<?php
/**
 * Entry Point - Điểm vào chính của ứng dụng
 * 
 * Chạy: C:\xampp\php\php.exe -S localhost:8080 -t public
 */

// Bật hiển thị lỗi khi debug
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Bắt đầu session
session_start();

// Đường dẫn gốc của project
define('BASE_PATH', realpath(__DIR__ . '/..'));

// Tự động phát hiện subfolder khi chạy qua XAMPP (VD: /link-checker)
// Khi dùng PHP built-in server, giá trị này sẽ rỗng ('')
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
define('BASE_URL', $scriptDir === '/' ? '' : rtrim($scriptDir, '/'));

/**
 * Helper tạo URL có prefix đúng cho cả XAMPP lẫn PHP built-in server
 * Ví dụ: url('/login') => '/link-checker/login' (XAMPP) hoặc '/login' (built-in)
 */
function url(string $path = '/'): string
{
    return BASE_URL . $path;
}

// Autoload tất cả các class
$autoloadDirs = [
    BASE_PATH . '/src/Core',
    BASE_PATH . '/src/Models',
    BASE_PATH . '/src/Services',
    BASE_PATH . '/src/Controllers',
];

foreach ($autoloadDirs as $dir) {
    if (is_dir($dir)) {
        foreach (glob($dir . '/*.php') as $file) {
            require_once $file;
        }
    }
}

// Tạo Router và đăng ký routes
$router = new Router();

// Auth routes
$router->get('/login',    'AuthController', 'loginForm');
$router->post('/login',   'AuthController', 'login');
$router->get('/register', 'AuthController', 'registerForm');
$router->post('/register','AuthController', 'register');
$router->get('/logout',   'AuthController', 'logout');

// Dashboard
$router->get('/',          'DashboardController', 'index');
$router->get('/dashboard', 'DashboardController', 'index');

// Sites
$router->get('/sites',          'SiteController', 'index');
$router->get('/sites/create',   'SiteController', 'create');
$router->post('/sites',         'SiteController', 'store');
$router->get('/sites/{id}',     'SiteController', 'show');
$router->post('/sites/{id}/delete', 'SiteController', 'delete');

// Scans
$router->post('/sites/{id}/scan',   'ScanController', 'start');
$router->get('/scans/{id}',         'ScanController', 'show');
$router->get('/scans/{id}/sources', 'ScanController', 'sources');
$router->post('/scans/{id}/delete', 'ScanController', 'delete');
$router->post('/findings/{id}/recheck', 'ScanController', 'recheck');

// Compare
$router->get('/compare', 'ScanController', 'compare');

// Xử lý request
$router->dispatch();
