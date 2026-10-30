<?php $pageTitle = 'Chi tiết kiểm tra #' . $scan['id']; ?>
<?php ob_start(); ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?= icon('alert') ?> <span><?= htmlspecialchars($error) ?></span></div>
<?php endif; ?>

<!-- Scan Info -->
<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-value is-text"><?= htmlspecialchars($scan['site_name']) ?></div>
            <div class="stat-label">Website</div>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-value"><?= $scan['pages_scanned'] ?></div>
            <div class="stat-label">Trang đã duyệt</div>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-value"><?= $scan['links_checked'] ?></div>
            <div class="stat-label">Liên kết kiểm tra</div>
        </div>
    </div>
    <div class="stat-card <?= $scan['errors_found'] > 0 ? 'is-danger' : 'is-success' ?>">
        <div>
            <div class="stat-value"><?= $scan['errors_found'] ?></div>
            <div class="stat-label">Lỗi phát hiện</div>
        </div>
    </div>
</div>

<?php
$typeLabels = [
    'http_error'            => ['Lỗi HTTP',         'badge-danger'],
    'broken_image'          => ['Ảnh hỏng',        'badge-warning'],
    'missing_anchor'        => ['Anchor thiếu',     'badge-info'],
    'timeout'               => ['Hết thời gian (Timeout)', 'badge-gray'],
    'connection_error'      => ['Lỗi kết nối',     'badge-gray'],
    'redirect_out_of_scope' => ['Chuyển hướng ngoài', 'badge-warning'],
    'unknown'               => ['Không xác định',   'badge-gray'],
];
?>

<!-- Error Stats by Type -->
<?php if (!empty($stats['by_type'])): ?>
<div class="card mb-6">
    <div class="card-header">
        <h3><?= icon('chart') ?> Phân loại lỗi</h3>
        <div class="flex gap-2">
            <a href="<?= url('/sites/' . $scan['site_id']) ?>" class="btn btn-sm btn-outline"><?= icon('arrow-left', 'icon-sm') ?> Về website</a>
            <a href="<?= url('/compare?site_id=' . $scan['site_id']) ?>" class="btn btn-sm btn-primary"><?= icon('compare', 'icon-sm') ?> So sánh</a>
        </div>
    </div>
    <div class="card-body">
        <div class="flex gap-2 flex-wrap">
            <?php
            foreach ($stats['by_type'] as $t):
                $label = $typeLabels[$t['error_type']] ?? [$t['error_type'], 'badge-gray'];
            ?>
                <span class="badge badge-lg <?= $label[1] ?>">
                    <?= $label[0] ?>: <strong><?= $t['count'] ?></strong>
                </span>
            <?php endforeach; ?>

            <?php if ($stats['resolved'] > 0): ?>
                <span class="badge badge-lg badge-success">
                    <?= icon('check', 'icon-sm') ?> Đã sửa: <strong><?= $stats['resolved'] ?></strong>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters + Findings Table -->
<div class="card">
    <div class="card-header">
        <h3><?= icon('list') ?> Danh sách phát hiện (<?= count($findings) ?>)</h3>
        <span class="text-sm text-muted">Bấm "Nguồn" để xem tất cả trang chứa liên kết lỗi</span>
    </div>

    <!-- Filters Bar -->
    <form method="GET" action="<?= url('/scans/' . $scan['id']) ?>">
        <div class="filters-bar">
            <div class="filter-group">
                <label>Loại lỗi</label>
                <select name="error_type">
                    <option value="">Tất cả loại lỗi</option>
                    <?php
                    $allTypes = ['http_error','broken_image','missing_anchor','timeout','connection_error','redirect_out_of_scope'];
                    foreach ($allTypes as $t):
                        $label = $typeLabels[$t] ?? [$t, ''];
                    ?>
                        <option value="<?= $t ?>" <?= ($filters['error_type'] ?? '') === $t ? 'selected' : '' ?>>
                            <?= $label[0] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Loại tài nguyên</label>
                <select name="link_type">
                    <option value="">Tất cả tài nguyên</option>
                    <option value="anchor" <?= ($filters['link_type'] ?? '') === 'anchor' ? 'selected' : '' ?>>Liên kết trang (a)</option>
                    <option value="image" <?= ($filters['link_type'] ?? '') === 'image' ? 'selected' : '' ?>>Hình ảnh (img)</option>
                    <option value="stylesheet" <?= ($filters['link_type'] ?? '') === 'stylesheet' ? 'selected' : '' ?>>Tập tin CSS (link)</option>
                    <option value="script" <?= ($filters['link_type'] ?? '') === 'script' ? 'selected' : '' ?>>Mã Script (script)</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Trang nguồn</label>
                <input type="text" name="source_url" value="<?= htmlspecialchars($filters['source_url'] ?? '') ?>" placeholder="Lọc theo URL nguồn" style="min-width:220px">
            </div>
            <button type="submit" class="btn btn-sm btn-primary"><?= icon('search', 'icon-sm') ?> Lọc kết quả</button>
            <?php if (array_filter($filters)): ?>
                <a href="<?= url('/scans/' . $scan['id']) ?>" class="btn btn-sm btn-outline"><?= icon('x', 'icon-sm') ?> Xóa bộ lọc</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($findings)): ?>
        <div class="empty-state">
            <div class="icon-wrap"><?= icon('check') ?></div>
            <h3>Không có lỗi nào<?= array_filter($filters) ? ' khớp với bộ lọc' : '' ?></h3>
            <p><?= array_filter($filters) ? 'Thử chọn lại tiêu chí tìm kiếm hoặc xóa bộ lọc để xem toàn bộ.' : 'Website này hoàn toàn sạch lỗi trong phạm vi kiểm tra!' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:140px">Phân loại</th>
                        <th>URL đích</th>
                        <th>Chi tiết lỗi</th>
                        <th style="width:90px">Mã HTTP</th>
                        <th>Trang nguồn đầu tiên</th>
                        <th class="text-right" style="width:170px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($findings as $f): ?>
                    <tr class="finding-row <?= $f['is_resolved'] ? 'resolved' : '' ?>">
                        <td>
                            <?php
                            $label = $typeLabels[$f['error_type']] ?? [$f['error_type'], 'badge-gray'];
                            ?>
                            <span class="badge <?= $label[1] ?>"><?= $label[0] ?></span>
                        </td>
                        <td>
                            <span class="url-text"><?= htmlspecialchars(mb_strimwidth($f['target_url'], 0, 56, '...')) ?></span>
                            <?php if (!empty($f['final_url'])): ?>
                                <span class="url-sub">&rarr; <?= htmlspecialchars(mb_strimwidth($f['final_url'], 0, 48, '...')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm">
                            <div class="message"><?= htmlspecialchars($f['message'] ?? '') ?></div>
                        </td>
                        <td>
                            <?php if ($f['http_status']): ?>
                                <span class="badge <?= $f['http_status'] >= 400 ? 'badge-danger' : 'badge-gray' ?>"><?= $f['http_status'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($f['source_url'])): ?>
                                <span class="url-text text-xs"><?= htmlspecialchars(mb_strimwidth($f['source_url'], 0, 36, '...')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="flex gap-2" style="justify-content:flex-end">
                                <button type="button"
                                        class="btn btn-sm btn-outline btn-sources"
                                        data-scan-id="<?= $scan['id'] ?>"
                                        data-target-url="<?= htmlspecialchars($f['target_url']) ?>">
                                    <?= icon('file', 'icon-sm') ?> Nguồn
                                </button>
                                <?php if (!$f['is_resolved']): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-outline btn-recheck"
                                            data-finding-id="<?= $f['id'] ?>">
                                        <?= icon('refresh', 'icon-sm') ?> Re-check
                                    </button>
                                <?php else: ?>
                                    <span class="badge badge-success"><?= icon('check', 'icon-sm') ?> Đã sửa</span>
                                <?php endif; ?>
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
