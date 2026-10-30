<?php
/**
 * Router - Điều hướng URL đến Controller tương ứng
 * Hỗ trợ GET, POST với route parameters
 */

class Router
{
    private array $routes = [];

    /**
     * Đăng ký route GET
     */
    public function get(string $path, string $controller, string $action): void
    {
        $this->routes['GET'][$path] = ['controller' => $controller, 'action' => $action];
    }

    /**
     * Đăng ký route POST
     */
    public function post(string $path, string $controller, string $action): void
    {
        $this->routes['POST'][$path] = ['controller' => $controller, 'action' => $action];
    }

    /**
     * Xử lý request hiện tại
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip BASE_URL prefix khi chạy qua XAMPP subfolder
        if (defined('BASE_URL') && BASE_URL !== '' && str_starts_with($uri, BASE_URL)) {
            $uri = substr($uri, strlen(BASE_URL));
            if ($uri === '' || $uri === false) {
                $uri = '/';
            }
        }

        // Tìm route khớp (hỗ trợ parameters dạng {id})
        foreach ($this->routes[$method] ?? [] as $pattern => $route) {
            $regex = $this->patternToRegex($pattern);
            if (preg_match($regex, $uri, $matches)) {
                $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

                $controllerClass = $route['controller'];
                $action          = $route['action'];

                if (!class_exists($controllerClass)) {
                    $this->sendError(500, "Controller {$controllerClass} not found");
                    return;
                }

                $controller = new $controllerClass();

                if (!method_exists($controller, $action)) {
                    $this->sendError(500, "Action {$action} not found in {$controllerClass}");
                    return;
                }

                call_user_func_array([$controller, $action], $params);
                return;
            }
        }

        $this->sendError(404, 'Page not found');
    }

    /**
     * Chuyển pattern route thành regex
     * Ví dụ: /sites/{id}/scans → /sites/(?P<id>[^/]+)/scans
     */
    private function patternToRegex(string $pattern): string
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }

    /**
     * Trả về trang lỗi
     */
    private function sendError(int $code, string $message): void
    {
        http_response_code($code);
        if ($code === 404) {
            require __DIR__ . '/../../src/Views/errors/404.php';
        } else {
            echo "<h1>Error {$code}</h1><p>{$message}</p>";
        }
    }
}
