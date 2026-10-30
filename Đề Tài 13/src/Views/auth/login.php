<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Đăng nhập vào Link Checker">
    <title>Đăng nhập | Link Checker</title>
    <link rel="stylesheet" href="<?= url('/css/style.css') ?>">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="logo">
            <span class="brand-mark"><?= icon('link') ?></span>
            <h1>Đăng nhập Link Checker</h1>
            <p>Kiểm tra liên kết hỏng, ảnh lỗi và anchor thiếu trên website của bạn.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= icon('alert') ?> <span><?= htmlspecialchars($error) ?></span></div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/login') ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="ban@vidu.com" autocomplete="email" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Đăng nhập</button>
        </form>

        <p class="auth-footer">
            Chưa có tài khoản? <a href="<?= url('/register') ?>">Đăng ký</a>
        </p>
    </div>
</div>
</body>
</html>
