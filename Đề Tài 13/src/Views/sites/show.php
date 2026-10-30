<?php $pageTitle = htmlspecialchars($site['name']); ?>
<?php ob_start(); ?>

<!-- Site Info -->
<div class="card mb-6">
    <div class="card-header">
        <h3><?= icon('globe') ?> Thông tin website</h3>
        <div class="flex gap-2">
            <form method="POST" action="<?= url('/sites/' . $site['id'] . '/delete') ?>" style="display:inline">
                <button type="submit" class="btn btn-sm btn-danger btn-delete"><?= icon('trash') ?> Xóa website</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="grid-3">
            <div>
                <div class="meta-label">URL BẮT ĐẦU</div>
                <span class="url-text"><?= htmlspecialchars($site['seed_url']) ?></span>
            </div>
            <div>
                <div class="meta-label">DOMAIN CHO PHÉP</div>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($site['allowed_domains'] as $domain): ?>
                        <span class="badge badge-primary"><?= htmlspecialchars($domain) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <div class="meta-label">NGƯỜI TẠO</div>
                <div class="text-sm">
                    <strong><?= htmlspecialchars($site['created_by'] ?? '') ?></strong>
                    <span class="text-muted"> — <?= $site['created_at'] ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Start New Scan Form -->
<div class="card mb-6">
    <div class="card-header">
        <h3><?= icon('play') ?> Cấu hình và bắt đầu kiểm tra</h3>
    </div>
    <div class="card-body">
        <form id="scanForm" method="POST" action="<?= url('/sites/' . $site['id'] . '/scan') ?>">
            <div class="scan-config">
                <div class="form-group mb-0">
                    <label for="max_pages">Số trang tối đa</label>
                    <input type="number" id="max_pages" name="max_pages" value="30" min="1" max="500">
                </div>
                <div class="form-group mb-0">
                    <label for="max_depth">Độ sâu tối đa (hops)</label>
                    <input type="number" id="max_depth" name="max_depth" value="4" min="1" max="20">
                </div>
                <div class="form-group mb-0">
                    <label for="timeout">Timeout mỗi request (s)</label>
                    <input type="number" id="timeout" name="timeout" value="10" min="3" max="120">
                </div>
                <div class="form-group mb-0">
                    <label for="max_total_time">Tổng thời gian tối đa (s)</label>
                    <input type="number" id="max_total_time" name="max_total_time" value="60" min="15" max="1800">
                </div>
            </div>
            <div class="scan-actions">
                <button type="submit" id="btnStartScan" class="btn btn-primary btn-lg"><?= icon('play') ?> Bắt đầu kiểm tra</button>
                
                <!-- Khối Animation Progress Bar & Phần trăm (%) -->
                <div id="scanProgressBox" class="scan-progress-box" style="display:none;">
                    <div class="scan-progress-header">
                        <div class="scan-progress-status-wrap">
                            <span class="scan-progress-spinner"><?= icon('refresh', 'icon-spin') ?></span>
                            <span id="scanProgressStatus" class="scan-progress-status">Đang khởi tạo phiên kiểm tra...</span>
                        </div>
                        <span id="scanProgressPercent" class="scan-progress-percent">0%</span>
                    </div>
                    <div class="scan-progress-track">
                        <div id="scanProgressBar" class="scan-progress-bar" style="width: 0%;"></div>
                    </div>
                    <div class="scan-progress-footer">
                        <span>Hệ thống đang duyệt thu thập trang, bóc tách liên kết và kiểm tra mạng. Vui lòng giữ nguyên trang, kết quả sẽ tự động hiển thị sau khi hoàn tất.</span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Scan History -->
<div class="card">
    <div class="card-header">
        <h3><?= icon('history') ?> Lịch sử kiểm tra</h3>
        <span class="badge badge-gray"><?= count($scanRuns) ?> lần quét</span>
    </div>

    <?php if (empty($scanRuns)): ?>
        <div class="empty-state">
            <div class="icon-wrap"><?= icon('search') ?></div>
            <h3>Chưa có lần kiểm tra nào</h3>
            <p>Nhấn nút "Bắt đầu kiểm tra" ở trên để thực hiện đợt quét đầu tiên cho website này.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:48px">#</th>
                        <th>Trạng thái</th>
                        <th>Cấu hình quét</th>
                        <th>Kết quả thực tế</th>
                        <th>Thời gian bắt đầu</th>
                        <th class="text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scanRuns as $i => $scan): ?>
                    <tr>
                        <td><strong>#<?= $scan['id'] ?></strong></td>
                        <td>
                            <?php
                            $statusMap = [
                                'completed' => ['badge-success', 'Hoàn thành'],
                                'running'   => ['badge-info',    'Đang chạy'],
                                'pending'   => ['badge-gray',    'Chờ'],
                                'failed'    => ['badge-danger',  'Thất bại'],
                                'cancelled' => ['badge-warning', 'Đã hủy'],
                            ];
                            $s = $statusMap[$scan['status']] ?? ['badge-gray', $scan['status']];
                            ?>
                            <span class="badge badge-status <?= $s[0] ?>"><?= $s[1] ?></span>
                        </td>
                        <td class="text-sm">
                            <?= $scan['max_pages'] ?> trang &bull; độ sâu <?= $scan['max_depth'] ?> &bull; <?= $scan['timeout'] ?>s timeout
                        </td>
                        <td>
                            <span class="text-sm">
                                <strong><?= $scan['pages_scanned'] ?></strong> trang, <strong><?= $scan['links_checked'] ?></strong> link
                            </span>
                            <?php if ($scan['errors_found'] > 0): ?>
                                <span class="badge badge-danger" style="margin-left:6px"><?= $scan['errors_found'] ?> lỗi</span>
                            <?php else: ?>
                                <span class="badge badge-success" style="margin-left:6px">0 lỗi</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-muted">
                            <?= $scan['started_at'] ?? 'N/A' ?>
                        </td>
                        <td class="text-right">
                            <div class="flex gap-2" style="justify-content:flex-end">
                                <a href="<?= url('/scans/' . $scan['id']) ?>" class="btn btn-sm btn-outline"><?= icon('eye', 'icon-sm') ?> Xem</a>
                                <form method="POST" action="<?= url('/scans/' . $scan['id'] . '/delete') ?>" style="display:inline">
                                    <button type="submit" class="btn btn-sm btn-danger btn-delete" title="Xóa đợt quét"><?= icon('trash', 'icon-sm') ?></button>
                                </form>
                            </div>
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
