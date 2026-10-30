<?php $pageTitle = 'Quản lý Website'; ?>
<?php ob_start(); ?>

<div class="page-toolbar">
    <p class="text-muted">Danh sách các website đã đăng ký và cấu hình phạm vi kiểm tra</p>
    <a href="<?= url('/sites/create') ?>" class="btn btn-primary"><?= icon('plus') ?> Thêm website</a>
</div>

<?php if (empty($sites)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="icon-wrap"><?= icon('globe') ?></div>
            <h3>Chưa có website nào</h3>
            <p>Khai báo website đầu tiên và phạm vi domain để bắt đầu quét liên kết.</p>
            <a href="<?= url('/sites/create') ?>" class="btn btn-primary"><?= icon('plus') ?> Thêm website ngay</a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:48px">#</th>
                        <th>Tên website</th>
                        <th>URL bắt đầu</th>
                        <th>Số lần quét</th>
                        <th>Người tạo</th>
                        <th>Thời gian tạo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sites as $i => $site): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($site['name']) ?></strong></td>
                        <td><span class="url-text"><?= htmlspecialchars($site['seed_url']) ?></span></td>
                        <td>
                            <span class="badge badge-primary"><?= $site['scan_count'] ?> lần</span>
                        </td>
                        <td class="text-sm"><?= htmlspecialchars($site['created_by_name']) ?></td>
                        <td class="text-sm text-muted"><?= $site['created_at'] ?></td>
                        <td class="text-right">
                            <a href="<?= url('/sites/' . $site['id']) ?>" class="btn btn-sm btn-outline"><?= icon('eye', 'icon-sm') ?> Chi tiết</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
