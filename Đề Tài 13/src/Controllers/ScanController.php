<?php
/**
 * ScanController - Quản lý scan runs
 * Chức năng 2,3,4: Duyệt, kiểm tra, báo cáo
 */

class ScanController
{
    /**
     * Tạo scan run mới và bắt đầu kiểm tra
     */
    public function start(string $siteId): void
    {
        Auth::requireLogin();
        $siteId = (int) $siteId;

        $site = Site::findById($siteId);
        if (!$site || !Site::userCanAccess($siteId, Auth::id(), Auth::user()['role'])) {
            http_response_code(403);
            echo 'Không có quyền truy cập.';
            return;
        }

        // Lấy cấu hình từ form
        $maxPages     = max(1, min(500, (int) ($_POST['max_pages'] ?? 100)));
        $maxDepth     = max(1, min(20, (int) ($_POST['max_depth'] ?? 5)));
        $timeout      = max(5, min(120, (int) ($_POST['timeout'] ?? 30)));
        $maxTotalTime = max(30, min(1800, (int) ($_POST['max_total_time'] ?? 300)));

        // Tạo scan run
        $scanId = ScanRun::create([
            'site_id'        => $siteId,
            'max_pages'      => $maxPages,
            'max_depth'      => $maxDepth,
            'timeout'        => $timeout,
            'max_total_time' => $maxTotalTime,
            'created_by'     => Auth::id(),
        ]);

        // Bắt đầu crawl
        ScanRun::markStarted($scanId);

        $allowedDomains = json_decode($site['allowed_domains'], true) ?: [];

        try {
            $crawler = new Crawler([
                'max_pages'      => $maxPages,
                'max_depth'      => $maxDepth,
                'timeout'        => $timeout,
                'max_total_time' => $maxTotalTime,
                'allowed_domains'=> $allowedDomains,
            ]);

            $result = $crawler->crawl($site['seed_url']);

            // Lưu kết quả vào database
            ScanRun::saveResults($scanId, $result);

            // Cập nhật thống kê
            ScanRun::markCompleted(
                $scanId,
                $result['stats']['pages_scanned'],
                $result['stats']['links_checked'],
                $result['stats']['errors_found']
            );

        } catch (Exception $e) {
            ScanRun::markFailed($scanId);
            $_SESSION['scan_error'] = 'Lỗi khi kiểm tra: ' . $e->getMessage();
        }

        header('Location: ' . url("/scans/{$scanId}"));
        exit;
    }

    /**
     * Chi tiết kết quả scan
     */
    public function show(string $id): void
    {
        Auth::requireLogin();
        $id = (int) $id;

        $scan = ScanRun::findById($id);
        if (!$scan) {
            http_response_code(404);
            echo 'Không tìm thấy lần kiểm tra này.';
            return;
        }

        // Kiểm tra quyền
        $site = Site::findById($scan['site_id']);
        if (!Site::userCanAccess($scan['site_id'], Auth::id(), Auth::user()['role'])) {
            http_response_code(403);
            echo 'Không có quyền truy cập.';
            return;
        }

        // Lấy filters
        $filters = [
            'error_type' => $_GET['error_type'] ?? '',
            'source_url' => $_GET['source_url'] ?? '',
            'link_type'  => $_GET['link_type'] ?? '',
        ];

        $findings = Finding::getByScanId($id, array_filter($filters));
        $stats    = Finding::getStats($id);
        $error    = $_SESSION['scan_error'] ?? null;
        unset($_SESSION['scan_error']);

        require __DIR__ . '/../Views/scans/show.php';
    }

    /**
     * Xem tất cả trang nguồn của một target URL
     */
    public function sources(string $scanId): void
    {
        Auth::requireLogin();
        $scanId    = (int) $scanId;
        $targetUrl = $_GET['target'] ?? '';

        if (empty($targetUrl)) {
            http_response_code(400);
            echo 'Thiếu tham số target URL.';
            return;
        }

        $scan = ScanRun::findById($scanId);
        if (!$scan || !Site::userCanAccess($scan['site_id'], Auth::id(), Auth::user()['role'])) {
            http_response_code(403);
            echo 'Không có quyền.';
            return;
        }

        $sources = Finding::getSourcePages($scanId, $targetUrl);

        header('Content-Type: application/json');
        echo json_encode([
            'target_url' => $targetUrl,
            'sources'    => $sources,
        ]);
    }

    /**
     * Re-check một finding
     */
    public function recheck(string $findingId): void
    {
        Auth::requireLogin();
        $findingId = (int) $findingId;

        $result = Finding::recheck($findingId);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * So sánh 2 scan runs
     */
    public function compare(): void
    {
        Auth::requireLogin();

        $scanId1 = (int) ($_GET['scan1'] ?? 0);
        $scanId2 = (int) ($_GET['scan2'] ?? 0);

        if ($scanId1 === 0 || $scanId2 === 0) {
            // Hiển thị form chọn
            $scan1 = null;
            $scan2 = null;
            $comparison = null;

            // Nếu có site_id, lấy danh sách scan runs
            $siteId = (int) ($_GET['site_id'] ?? 0);
            $scanRuns = [];
            $site = null;

            if ($siteId > 0) {
                $site = Site::findById($siteId);
                if ($site && Site::userCanAccess($siteId, Auth::id(), Auth::user()['role'])) {
                    $scanRuns = ScanRun::getBySiteId($siteId);
                }
            }

            require __DIR__ . '/../Views/scans/compare.php';
            return;
        }

        // Kiểm tra quyền
        $scan1 = ScanRun::findById($scanId1);
        $scan2 = ScanRun::findById($scanId2);

        if (!$scan1 || !$scan2) {
            http_response_code(404);
            echo 'Không tìm thấy scan.';
            return;
        }

        if (!Site::userCanAccess($scan1['site_id'], Auth::id(), Auth::user()['role'])) {
            http_response_code(403);
            echo 'Không có quyền.';
            return;
        }

        $comparison = ScanComparator::compare($scanId1, $scanId2);
        $site = Site::findById($scan1['site_id']);
        $scanRuns = ScanRun::getBySiteId($scan1['site_id']);

        require __DIR__ . '/../Views/scans/compare.php';
    }

    /**
     * Xóa scan
     */
    public function delete(string $id): void
    {
        Auth::requireLogin();
        $id = (int) $id;

        $scan = ScanRun::findById($id);
        if (!$scan || !Site::userCanAccess($scan['site_id'], Auth::id(), Auth::user()['role'])) {
            http_response_code(403);
            echo 'Không có quyền.';
            return;
        }

        $siteId = $scan['site_id'];
        ScanRun::delete($id);
        header('Location: ' . url("/sites/{$siteId}"));
        exit;
    }
}
