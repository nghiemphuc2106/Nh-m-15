<?php $pageTitle = 'Thêm Website mới'; ?>
<?php ob_start(); ?>

<div class="grid-2">
    <!-- Form -->
    <div class="card">
        <div class="card-header">
            <h3><?= icon('edit') ?> Khai báo phạm vi kiểm tra</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?= icon('alert') ?> <span><?= htmlspecialchars($error) ?></span></div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/sites') ?>">
                <div class="form-group">
                    <label for="name">Tên website</label>
                    <input type="text" id="name" name="name" placeholder="VD: Website Công ty ABC" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    <div class="form-hint">Đặt tên phân biệt để theo dõi trong danh sách và báo cáo so sánh.</div>
                </div>

                <div class="form-group">
                    <label for="seed_url">URL bắt đầu (Seed URL)</label>
                    <input type="url" id="seed_url" name="seed_url" placeholder="http://localhost:8888" required value="<?= htmlspecialchars($_POST['seed_url'] ?? '') ?>">
                    <div class="form-hint">Điểm xuất phát mà crawler bắt đầu duyệt (yêu cầu giao thức http:// hoặc https://).</div>
                </div>

                <div class="form-group">
                    <label for="allowed_domains">Domain được phép crawl (mỗi dòng một domain)</label>
                    <textarea id="allowed_domains" name="allowed_domains" rows="3" placeholder="localhost:8888&#10;example.com"><?= htmlspecialchars($_POST['allowed_domains'] ?? '') ?></textarea>
                    <div class="form-hint">Domain của Seed URL được tự động thêm vào phạm vi. Khai báo thêm nếu trang có liên kết chéo qua subdomain/domain khác.</div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><?= icon('check') ?> Tạo website</button>
                    <a href="<?= url('/sites') ?>" class="btn btn-outline btn-lg"><?= icon('arrow-left') ?> Quay lại</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Scope Preview & Guide -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3><?= icon('eye') ?> Xem trước phạm vi</h3>
            </div>
            <div class="card-body">
                <div id="scopePreview" class="scope-preview">
                    <p class="text-muted text-sm">Nhập URL bắt đầu để xem trước phân tích phạm vi kiểm tra.</p>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><?= icon('lightbulb') ?> Hướng dẫn cấu hình</h3>
            </div>
            <div class="card-body">
                <ul class="guide-list">
                    <li>
                        <?= icon('info') ?>
                        <div><strong>Seed URL:</strong> Điểm xuất phát của thuật toán duyệt BFS (Breadth-First Search).</div>
                    </li>
                    <li>
                        <?= icon('info') ?>
                        <div><strong>Allowed domains:</strong> Giới hạn phạm vi tải HTML. Các liên kết nằm ngoài domain này sẽ chỉ kiểm tra mã trạng thái, không cào tiếp để tránh lặp vô hạn ra Internet.</div>
                    </li>
                    <li>
                        <?= icon('info') ?>
                        <div><strong>Cấu hình quét:</strong> Sau khi tạo, bạn có thể chỉnh số trang tối đa, độ sâu và timeout cho từng lần chạy.</div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
