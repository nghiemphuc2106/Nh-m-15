<?php
/**
 * DashboardController - Trang tổng quan
 */

class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        // Lấy sites
        if (Auth::isAdmin()) {
            $sites = Site::getAll();
        } else {
            $sites = Site::getAllByUser(Auth::id());
        }

        // Thống kê chung
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM scan_runs WHERE created_by = ?");
        $stmt->execute([Auth::id()]);
        $totalScans = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM findings f 
            JOIN scan_runs sr ON f.scan_id = sr.id 
            WHERE sr.created_by = ? AND f.is_resolved = 0
        ");
        $stmt->execute([Auth::id()]);
        $activeErrors = $stmt->fetch()['total'];

        // Recent scans
        $stmt = $pdo->prepare("
            SELECT sr.*, s.name as site_name
            FROM scan_runs sr
            JOIN sites s ON sr.site_id = s.id
            WHERE sr.created_by = ?
            ORDER BY sr.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([Auth::id()]);
        $recentScans = $stmt->fetchAll();

        require __DIR__ . '/../Views/dashboard/index.php';
    }
}
