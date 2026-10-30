<?php
/**
 * AuthController - Xử lý đăng nhập, đăng xuất, đăng ký
 */

class AuthController
{
    /**
     * Hiển thị form đăng nhập
     */
    public function loginForm(): void
    {
        if (Auth::check()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        require __DIR__ . '/../Views/auth/login.php';
    }

    /**
     * Xử lý đăng nhập
     */
    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Vui lòng nhập đầy đủ email và mật khẩu.';
            header('Location: ' . url('/login'));
            exit;
        }

        $user = Auth::attempt($email, $password);

        if ($user) {
            header('Location: ' . url('/dashboard'));
            exit;
        }

        $_SESSION['login_error'] = 'Email hoặc mật khẩu không đúng.';
        header('Location: ' . url('/login'));
        exit;
    }

    /**
     * Hiển thị form đăng ký
     */
    public function registerForm(): void
    {
        if (Auth::check()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        $error = $_SESSION['register_error'] ?? null;
        unset($_SESSION['register_error']);
        require __DIR__ . '/../Views/auth/register.php';
    }

    /**
     * Xử lý đăng ký
     */
    public function register(): void
    {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['register_error'] = 'Vui lòng nhập đầy đủ thông tin.';
            header('Location: ' . url('/register'));
            exit;
        }

        if ($password !== $confirm) {
            $_SESSION['register_error'] = 'Mật khẩu xác nhận không khớp.';
            header('Location: ' . url('/register'));
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['register_error'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
            header('Location: ' . url('/register'));
            exit;
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role)
                VALUES (?, ?, ?, 'user')
            ");
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT)]);

            // Auto login
            Auth::attempt($email, $password);
            header('Location: ' . url('/dashboard'));
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['register_error'] = 'Username hoặc email đã tồn tại.';
            } else {
                $_SESSION['register_error'] = 'Lỗi hệ thống: ' . $e->getMessage();
            }
            header('Location: ' . url('/register'));
            exit;
        }
    }

    /**
     * Đăng xuất
     */
    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . url('/login'));
        exit;
    }
}
