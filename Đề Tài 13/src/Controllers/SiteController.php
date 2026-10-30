<?php
/**
 * SiteController - Quản lý website (CRUD)
 * Chức năng 1: Khai báo phạm vi kiểm tra
 */

class SiteController
{
    /**
     * Danh sách sites
     */
    public function index(): void
    {
        Auth::requireLogin();

        if (Auth::isAdmin()) {
            $sites = Site::getAll();
        } else {
            $sites = Site::getAllByUser(Auth::id());
        }

        require __DIR__ . '/../Views/sites/index.php';
    }

    /**
     * Form tạo site mới
     */
    public function create(): void
    {
        Auth::requireLogin();
        $error = $_SESSION['site_error'] ?? null;
        unset($_SESSION['site_error']);
        require __DIR__ . '/../Views/sites/create.php';
    }

    /**
     * Lưu site mới
     */
    public function store(): void
    {
        Auth::requireLogin();

        $name           = trim($_POST['name'] ?? '');
        $seedUrl        = trim($_POST['seed_url'] ?? '');
        $allowedDomains = trim($_POST['allowed_domains'] ?? '');

        if (empty($name) || empty($seedUrl)) {
            $_SESSION['site_error'] = 'Vui lòng nhập đầy đủ tên và URL bắt đầu.';
            header('Location: ' . url('/sites/create'));
            exit;
        }

        // Validate URL
        if (!UrlNormalizer::isValidHttpUrl($seedUrl)) {
            $_SESSION['site_error'] = 'URL không hợp lệ. Cần bắt đầu bằng http:// hoặc https://';
            header('Location: ' . url('/sites/create'));
            exit;
        }

        // Parse allowed domains (mỗi dòng một domain)
        $domains = array_filter(
            array_map('trim', explode("\n", $allowedDomains))
        );

        // Tự động thêm domain của seed URL
        $seedDomain = UrlNormalizer::getDomain($seedUrl);
        if ($seedDomain && !in_array($seedDomain, $domains)) {
            array_unshift($domains, $seedDomain);
        }

        $id = Site::create([
            'name'            => $name,
            'seed_url'        => UrlNormalizer::normalize($seedUrl),
            'allowed_domains' => $domains,
            'created_by'      => Auth::id(),
        ]);

        header('Location: ' . url("/sites/{$id}"));
        exit;
    }

    /**
     * Chi tiết site + danh sách scan runs
     */
    public function show(string $id): void
    {
        Auth::requireLogin();
        $id = (int) $id;

        $site = Site::findById($id);
        if (!$site || !Site::userCanAccess($id, Auth::id(), Auth::user()['role'])) {
            http_response_code(403);
            echo 'Không có quyền truy cập.';
            return;
        }

        $site['allowed_domains'] = json_decode($site['allowed_domains'], true) ?: [];
        $scanRuns = ScanRun::getBySiteId($id);

        require __DIR__ . '/../Views/sites/show.php';
    }

    /**
     * Xóa site
     */
    public function delete(string $id): void
    {
        Auth::requireLogin();
        $id = (int) $id;

        if (!Site::userCanAccess($id, Auth::id(), Auth::user()['role'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Không có quyền']);
            return;
        }

        Site::delete($id);
        header('Location: ' . url('/sites'));
        exit;
    }
}
