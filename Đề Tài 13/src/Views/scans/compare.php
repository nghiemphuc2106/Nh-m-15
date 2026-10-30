<?php $pageTitle = 'So sánh 2 lần kiểm tra'; ?>
<?php ob_start(); ?>

<!-- Select form -->
<div class="card mb-6">
    <div class="card-header">
        <h3><?= icon('compare') ?> Chọn 2 lần kiểm tra để so sánh</h3>
    </div>
    <div class="card-body">
        <?php if (empty($scanRuns)): ?>
            <div class="alert alert-info" role="status">
                <?= icon('info') ?>
                <span>Vui lòng chọn một website có ít nhất 2 lần kiểm tra để tiến hành so sánh đối chiếu kết quả.</span>
            </div>

            <?php
            // Lấy danh sách sites cho user
            if (Auth::isAdmin()) {
                $allSites = Site::getAll();
            } else {
                $allSites = Site::getAllByUser(Auth::id());
            }
            ?>
            <div class="flex gap-2 flex-wrap">
                <?php foreach ($allSites as $s): ?>
                    <a href="<?= url('/compare?site_id=' . $s['id']) ?>" class="btn btn-outline">
                        <?= icon('globe', 'icon-sm') ?> <?= htmlspecialchars($s['name']) ?> (<?= $s['scan_count'] ?> lần quét)
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <form method="GET" action="<?= url('/compare') ?>">
                <input type="hidden" name="site_id" value="<?= $site['id'] ?? '' ?>">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Lần kiểm tra 1 (Mốc đối chiếu cũ)</label>
                        <select name="scan1" required>
                            <option value="">-- Chọn lần kiểm tra cũ --</option>
                            <?php foreach ($scanRuns as $sr): ?>
                                <option value="<?= $sr['id'] ?>" <?= ($scanId1 ?? 0) == $sr['id'] ? 'selected' : '' ?>>
                                    #<?= $sr['id'] ?> — <?= $sr['status'] ?> — <?= $sr['pages_scanned'] ?> trang, <?= $sr['errors_found'] ?> lỗi (<?= $sr['started_at'] ?? $sr['created_at'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Lần kiểm tra 2 (Lần kiểm tra mới hơn)</label>
                        <select name="scan2" required>
                            <option value="">-- Chọn lần kiểm tra mới --</option>
                            <?php foreach ($scanRuns as $sr): ?>
                                <option value="<?= $sr['id'] ?>" <?= ($scanId2 ?? 0) == $sr['id'] ? 'selected' : '' ?>>
                                    #<?= $sr['id'] ?> — <?= $sr['status'] ?> — <?= $sr['pages_scanned'] ?> trang, <?= $sr['errors_found'] ?> lỗi (<?= $sr['started_at'] ?? $sr['created_at'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><?= icon('compare') ?> Tiến hành so sánh</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($comparison)): ?>

<!-- Comparison Stats -->
<div class="stats-grid">
    <div class="stat-card is-danger">
        <div>
            <div class="stat-value"><?= $comparison['stats']['new_errors'] ?></div>
            <div class="stat-label">Lỗi mới xuất hiện</div>
        </div>
    </div>
    <div class="stat-card is-success">
        <div>
            <div class="stat-value"><?= $comparison['stats']['resolved_errors'] ?></div>
            <div class="stat-label">Lỗi đã được sửa</div>
        </div>
    </div>
    <div class="stat-card is-warning">
        <div>
            <div class="stat-value"><?= $comparison['stats']['persistent_errors'] ?></div>
            <div class="stat-label">Lỗi còn tồn tại</div>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-value"><?= $comparison['stats']['not_checked'] ?></div>
            <div class="stat-label">Chưa kiểm tra lại</div>
        </div>
    </div>
</div>

<?php
$typeLabels = [
    'http_error'            => ['Lỗi HTTP',         'badge-danger'],
    'broken_image'          => ['Ảnh hỏng',        'badge-warning'],
    'missing_anchor'        => ['Anchor thiếu',     'badge-info'],
    'timeout'               => ['Hết thời gian',    'badge-gray'],
    'connection_error'      => ['Lỗi kết nối',     'badge-gray'],
    'redirect_out_of_scope' => ['Chuyển hướng ngoài', 'badge-warning'],
    'unknown'               => ['Không xác định',   'badge-gray'],
];
?>

<!-- New Errors -->
<?php if (!empty($comparison['new_errors'])): ?>
<div class="comparison-section">
    <h3 class="new-errors">Lỗi mới xuất hiện <span class="count">(<?= count($comparison['new_errors']) ?>)</span></h3>
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>URL đích</th><th>Phân loại</th><th>Thông báo chi tiết</th><th>Trang nguồn chứa link</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($comparison['new_errors'] as $item): ?>
                    <tr>
                        <td><span class="url-text"><?= htmlspecialchars(mb_strimwidth($item['target_url'], 0, 56, '...')) ?></span></td>
                        <td>
                            <?php $label = $typeLabels[$item['error']['error_type']] ?? [$item['error']['error_type'], 'badge-gray']; ?>
                            <span class="badge <?= $label[1] ?>"><?= $label[0] ?></span>
                        </td>
                        <td class="text-sm"><div class="message"><?= htmlspecialchars($item['error']['message'] ?? '') ?></div></td>
                        <td class="text-sm">
                            <div class="url-list">
                                <?php foreach ($item['error']['sources'] as $src): ?>
                                    <span class="url-text text-xs"><?= htmlspecialchars(mb_strimwidth($src, 0, 36, '...')) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Resolved Errors -->
<?php if (!empty($comparison['resolved_errors'])): ?>
<div class="comparison-section">
    <h3 class="resolved">Lỗi đã được khắc phục <span class="count">(<?= count($comparison['resolved_errors']) ?>)</span></h3>
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>URL đích</th><th>Loại lỗi cũ</th><th>Thông báo cũ</th><th>Trang nguồn trước đó</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($comparison['resolved_errors'] as $item): ?>
                    <tr>
                        <td><span class="url-text"><?= htmlspecialchars(mb_strimwidth($item['target_url'], 0, 56, '...')) ?></span></td>
                        <td>
                            <?php $label = $typeLabels[$item['old_error']['error_type']] ?? [$item['old_error']['error_type'], 'badge-gray']; ?>
                            <span class="badge <?= $label[1] ?>"><?= $label[0] ?></span>
                        </td>
                        <td class="text-sm"><div class="message"><?= htmlspecialchars($item['old_error']['message'] ?? '') ?></div></td>
                        <td class="text-sm">
                            <div class="url-list">
                                <?php foreach ($item['old_error']['sources'] as $src): ?>
                                    <span class="url-text text-xs"><?= htmlspecialchars(mb_strimwidth($src, 0, 36, '...')) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Persistent Errors -->
<?php if (!empty($comparison['persistent_errors'])): ?>
<div class="comparison-section">
    <h3 class="persistent">Lỗi vẫn còn tồn tại <span class="count">(<?= count($comparison['persistent_errors']) ?>)</span></h3>
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>URL đích</th><th>Lỗi lần 1</th><th>Lỗi lần 2</th><th>Trang nguồn</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($comparison['persistent_errors'] as $item): ?>
                    <tr>
                        <td><span class="url-text"><?= htmlspecialchars(mb_strimwidth($item['target_url'], 0, 56, '...')) ?></span></td>
                        <td class="text-sm">
                            <?php $label = $typeLabels[$item['old_error']['error_type']] ?? [$item['old_error']['error_type'], 'badge-gray']; ?>
                            <span class="badge <?= $label[1] ?>"><?= $label[0] ?></span>
                        </td>
                        <td class="text-sm">
                            <?php $label = $typeLabels[$item['new_error']['error_type']] ?? [$item['new_error']['error_type'], 'badge-gray']; ?>
                            <span class="badge <?= $label[1] ?>"><?= $label[0] ?></span>
                        </td>
                        <td class="text-sm">
                            <div class="url-list">
                                <?php foreach ($item['old_error']['sources'] as $src): ?>
                                    <span class="url-text text-xs"><?= htmlspecialchars(mb_strimwidth($src, 0, 36, '...')) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Not Checked -->
<?php if (!empty($comparison['not_checked'])): ?>
<div class="comparison-section">
    <h3 class="not-checked">Chưa kiểm tra lại <span class="count">(<?= count($comparison['not_checked']) ?>)</span></h3>
    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>URL đích</th><th>Lỗi đợt trước</th><th>Lý do chưa kiểm tra</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($comparison['not_checked'] as $item): ?>
                    <tr>
                        <td><span class="url-text"><?= htmlspecialchars(mb_strimwidth($item['target_url'], 0, 56, '...')) ?></span></td>
                        <td class="text-sm">
                            <?php $label = $typeLabels[$item['old_error']['error_type']] ?? [$item['old_error']['error_type'], 'badge-gray']; ?>
                            <span class="badge <?= $label[1] ?>"><?= $label[0] ?></span>
                            <span class="text-muted" style="margin-left:6px"><?= htmlspecialchars($item['old_error']['message'] ?? '') ?></span>
                        </td>
                        <td class="text-sm text-muted"><?= htmlspecialchars($item['reason']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($comparison['new_errors']) && empty($comparison['resolved_errors']) && empty($comparison['persistent_errors']) && empty($comparison['not_checked'])): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon-wrap"><?= icon('check') ?></div>
        <h3>Không có thay đổi nào</h3>
        <p>Hai lần kiểm tra này ghi nhận kết quả và trạng thái các liên kết hoàn toàn giống nhau.</p>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/app.php'; ?>
