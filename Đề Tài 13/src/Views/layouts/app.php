<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Link Checker - Công cụ kiểm tra liên kết và tài nguyên bị lỗi trên website">
    <title><?= $pageTitle ?? 'Link Checker' ?> | Link Checker</title>
    <link rel="stylesheet" href="<?= url('/css/style.css') ?>">
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <span class="brand-mark"><?= icon('link') ?></span>
                <div>
                    <h2>Link Checker</h2>
                    <span>Kiểm tra liên kết website</span>
                </div>
            </div>

            <nav class="sidebar-nav" aria-label="Điều hướng chính">
                <div class="nav-label">Menu chính</div>
                <?php $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); if (defined('BASE_URL') && BASE_URL !== '' && str_starts_with($currentUri, BASE_URL)) { $currentUri = substr($currentUri, strlen(BASE_URL)); if ($currentUri === '' || $currentUri === false) $currentUri = '/'; } ?>
                <a href="<?= url('/dashboard') ?>" class="<?= ($currentUri === '/dashboard' || $currentUri === '/') ? 'active' : '' ?>">
                    <span class="nav-icon"><?= icon('dashboard') ?></span> Tổng quan
                </a>
                <a href="<?= url('/sites') ?>" class="<?= (str_starts_with($currentUri, '/sites') || str_starts_with($currentUri, '/scans')) ? 'active' : '' ?>">
                    <span class="nav-icon"><?= icon('globe') ?></span> Quản lý Website
                </a>
                <a href="<?= url('/compare') ?>" class="<?= str_starts_with($currentUri, '/compare') ? 'active' : '' ?>">
                    <span class="nav-icon"><?= icon('compare') ?></span> So sánh kết quả
                </a>

                <div class="nav-label">Tài khoản</div>
                <?php if (Auth::isAdmin()): ?>
                <a href="<?= url('/dashboard') ?>">
                    <span class="nav-icon"><?= icon('shield') ?></span> Quản trị
                </a>
                <?php endif; ?>
                <a href="<?= url('/logout') ?>">
                    <span class="nav-icon"><?= icon('logout') ?></span> Đăng xuất
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr(Auth::user()['username'] ?? 'U', 0, 1)) ?></div>
                    <div>
                        <div class="user-name"><?= htmlspecialchars(Auth::user()['username'] ?? '') ?></div>
                        <div class="user-role"><?= Auth::isAdmin() ? 'Admin' : 'User' ?></div>
                    </div>
                </div>
            </div>
        </aside>
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="flex items-center gap-2" style="min-width:0">
                    <button id="menuToggle" class="btn btn-outline btn-icon menu-toggle" type="button" aria-label="Mở menu" aria-controls="appSidebar" aria-expanded="false"><?= icon('menu') ?></button>
                    <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
                </div>
                <div class="text-sm text-muted">
                    <?= date('d/m/Y H:i') ?>
                </div>
            </header>

            <div class="main-body">
                <?php
                // Nội dung trang sẽ được include trước layout
                // (Content đã được buffer)
                if (isset($content)) {
                    echo $content;
                }
                ?>
            </div>
        </main>
    </div>

    <!-- Source Pages Modal -->
    <div class="modal-overlay" id="sourcesModal">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="sourcesModalTitle">
            <div class="modal-header">
                <h3 id="sourcesModalTitle"><?= icon('file') ?> Trang nguồn chứa liên kết</h3>
                <button class="modal-close" type="button" aria-label="Đóng" onclick="document.getElementById('sourcesModal').classList.remove('active')"><?= icon('x') ?></button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>

    <script>window.BASE_URL = <?= json_encode(defined('BASE_URL') ? BASE_URL : '') ?>;</script>
    <script src="<?= url('/js/app.js') ?>"></script>
</body>
</html>
