<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Đăng ký tài khoản Link Checker">
    <title>Đăng ký | Link Checker</title>
    <link rel="stylesheet" href="<?= url('/css/style.css') ?>">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="logo">
            <span class="brand-mark"><?= icon('link') ?></span>
            <h1>Tạo tài khoản</h1>
            <p>Dùng tài khoản để lưu website và lịch sử kiểm tra.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= icon('alert') ?> <span><?= htmlspecialchars($error) ?></span></div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/register') ?>">
            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" placeholder="vd: nguyenvana" autocomplete="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="ban@vidu.com" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" placeholder="Ít nhất 6 ký tự" autocomplete="new-password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="password_confirm">Xác nhận mật khẩu</label>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="Nhập lại mật khẩu" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Tạo tài khoản</button>
        </form>

        <p class="auth-footer">
            Đã có tài khoản? <a href="<?= url('/login') ?>">Đăng nhập</a>
        </p>
    </div>
</div>
</body>
</html>
