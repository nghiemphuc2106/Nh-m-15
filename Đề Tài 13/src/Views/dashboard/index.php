<?php $pageTitle = 'Tổng quan'; ?>
<?php ob_start(); ?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-value"><?= count($sites) ?></div>
            <div class="stat-label">Website theo dõi</div>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-value"><?= $totalScans ?></div>
            <div class="stat-label">Lần kiểm tra</div>
        </div>
    </div>
    <div class="stat-card <?= $activeErrors > 0 ? 'is-danger' : 'is-success' ?>">
        <div>
            <div class="stat-value"><?= $activeErrors ?></div>
            <div class="stat-label">Lỗi cần xử lý</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-6">
    <div class="card-header">
        <h3><?= icon('zap') ?> Thao tác nhanh</h3>
    </div>
    <div class="card-body">
        <div class="flex gap-2 flex-wrap">
            <a href="<?= url('/sites/create') ?>" class="btn btn-primary"><?= icon('plus') ?> Thêm website mới</a>
            <a href="<?= url('/sites') ?>" class="btn btn-outline"><?= icon('globe') ?> Danh sách website</a>
            <a href="<?= url('/compare') ?>" class="btn btn-outline"><?= icon('compare') ?> So sánh kết quả</a>
        </div>
    </div>
</div>

<!-- Recent Scans -->
<div class="card">
    <div class="card-header">
        <h3><?= icon('history') ?> Lần kiểm tra gần đây</h3>
    </div>
    <?php if (empty($recentScans)): ?>
        <div class="empty-state">
            <div class="icon-wrap"><?= icon('search') ?></div>
            <h3>Chưa có lần kiểm tra nào</h3>
            <p>Thêm website của bạn và bắt đầu kiểm tra các liên kết, ảnh và thẻ neo.</p>
            <a href="<?= url('/sites/create') ?>" class="btn btn-primary"><?= icon('plus') ?> Thêm website đầu tiên</a>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Website</th>
                        <th>Trạng thái</th>
                        <th>Trang quét</th>
                        <th>Liên kết</th>
                        <th>Lỗi</th>
                        <th>Thời gian</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentScans as $scan): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($scan['site_name']) ?></strong>
                        </td>
                        <td>
                            <?php
                            $statusMap = [
                                'completed' => ['badge-success', 'Hoàn thành'],
                                'running'   => ['badge-info',    'Đang chạy'],
                                'pending'   => ['badge-gray',    'Chờ xử lý'],
                                'failed'    => ['badge-danger',  'Thất bại'],
                                'cancelled' => ['badge-warning', 'Đã hủy'],
                            ];
                            $s = $statusMap[$scan['status']] ?? ['badge-gray', $scan['status']];
                            ?>
                            <span class="badge badge-status <?= $s[0] ?>"><?= $s[1] ?></span>
                        </td>
                        <td><?= $scan['pages_scanned'] ?></td>
                        <td><?= $scan['links_checked'] ?></td>
                        <td>
                            <?php if ($scan['errors_found'] > 0): ?>
                                <span class="badge badge-danger"><?= $scan['errors_found'] ?> lỗi</span>
                            <?php else: ?>
                                <span class="badge badge-success">0 lỗi</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-muted"><?= $scan['created_at'] ?></td>
                        <td class="text-right">
                            <a href="<?= url('/scans/' . $scan['id']) ?>" class="btn btn-sm btn-outline"><?= icon('eye', 'icon-sm') ?> Xem</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
