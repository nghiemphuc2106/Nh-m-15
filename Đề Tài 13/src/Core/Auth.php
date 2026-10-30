<?php
/**
 * Auth - Quản lý xác thực và phiên đăng nhập
 */

class Auth
{
    /**
     * Đăng nhập: kiểm tra email + password
     * @return array|null User data hoặc null nếu sai
     */
    public static function attempt(string $email, string $password): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Lưu thông tin vào session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];
            return $user;
        }

        return null;
    }

    /**
     * Kiểm tra đã đăng nhập chưa
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Lấy thông tin user hiện tại
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id'       => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email'    => $_SESSION['email'],
            'role'     => $_SESSION['role'],
        ];
    }

    /**
     * Lấy ID user hiện tại
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Kiểm tra user hiện tại có phải admin
     */
    public static function isAdmin(): bool
    {
        return ($_SESSION['role'] ?? '') === 'admin';
    }

    /**
     * Yêu cầu đăng nhập, redirect nếu chưa
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
    }

    /**
     * Đăng xuất
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
}
