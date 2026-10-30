<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Không tìm thấy | Link Checker</title>
    <link rel="stylesheet" href="<?= url('/css/style.css') ?>">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="text-align:center">
        <div class="error-code">404</div>
        <h1 style="font-size:var(--fs-lg);font-weight:600;margin-bottom:var(--s-2)">Không tìm thấy trang</h1>
        <p class="text-muted" style="margin-bottom:var(--s-6)">Đường dẫn này không tồn tại hoặc đã bị xoá. Kiểm tra lại URL, hoặc quay về trang tổng quan.</p>
        <a href="<?= url('/dashboard') ?>" class="btn btn-primary btn-lg"><?= icon('arrow-left') ?> Về trang tổng quan</a>
    </div>
</div>
</body>
</html>
