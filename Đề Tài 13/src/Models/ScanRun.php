<?php
/**
 * Model ScanRun - Quản lý mỗi lần chạy kiểm tra
 */

class ScanRun
{
    /**
     * Lấy tất cả scan runs của site
     */
    public static function getBySiteId(int $siteId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT sr.*, u.username as created_by_name
            FROM scan_runs sr
            JOIN users u ON sr.created_by = u.id
            WHERE sr.site_id = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$siteId]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy scan run theo ID
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT sr.*, s.name as site_name, s.seed_url, u.username as created_by_name
            FROM scan_runs sr
            JOIN sites s ON sr.site_id = s.id
            JOIN users u ON sr.created_by = u.id
            WHERE sr.id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Tạo scan run mới
     */
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO scan_runs (site_id, status, max_pages, max_depth, timeout, max_total_time, created_by)
            VALUES (?, 'pending', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['site_id'],
            $data['max_pages'],
            $data['max_depth'],
            $data['timeout'],
            $data['max_total_time'],
            $data['created_by'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * Cập nhật trạng thái scan
     */
    public static function updateStatus(int $id, string $status): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE scan_runs SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    /**
     * Đánh dấu bắt đầu
     */
    public static function markStarted(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE scan_runs SET status = 'running', started_at = NOW() WHERE id = ?
        ");
        $stmt->execute([$id]);
    }

    /**
     * Đánh dấu hoàn thành với thống kê
     */
    public static function markCompleted(int $id, int $pagesScanned, int $linksChecked, int $errorsFound): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE scan_runs 
            SET status = 'completed', 
                finished_at = NOW(),
                pages_scanned = ?,
                links_checked = ?,
                errors_found = ?
            WHERE id = ?
        ");
        $stmt->execute([$pagesScanned, $linksChecked, $errorsFound, $id]);
    }

    /**
     * Đánh dấu thất bại
     */
    public static function markFailed(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE scan_runs SET status = 'failed', finished_at = NOW() WHERE id = ?
        ");
        $stmt->execute([$id]);
    }

    /**
     * Lưu kết quả crawl vào database
     */
    public static function saveResults(int $scanId, array $crawlResult): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Lưu pages
            $pageStmt = $pdo->prepare("
                INSERT INTO pages (scan_id, url, http_status, content_type, depth, status, error_message, scanned_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $pageIdMap = []; // url → page_id

            foreach ($crawlResult['pages'] as $page) {
                $pageStmt->execute([
                    $scanId,
                    $page['url'],
                    $page['http_status'],
                    $page['content_type'],
                    $page['depth'],
                    $page['status'],
                    $page['error_message'],
                ]);
                $pageIdMap[$page['url']] = (int) $pdo->lastInsertId();
            }

            // 2. Lưu links
            $linkStmt = $pdo->prepare("
                INSERT INTO links (scan_id, source_page_id, target_url, link_type, link_text, html_element)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $linkIdMap = []; // source_url|target_url → link_id

            foreach ($crawlResult['links'] as $link) {
                $sourcePageId = $pageIdMap[$link['source_url']] ?? null;
                if ($sourcePageId === null) continue;

                $linkStmt->execute([
                    $scanId,
                    $sourcePageId,
                    $link['target_url'],
                    $link['link_type'],
                    mb_substr($link['link_text'] ?? '', 0, 500),
                    $link['html_element'],
                ]);

                $key = $link['source_url'] . '|' . $link['target_url'];
                $linkIdMap[$key] = (int) $pdo->lastInsertId();
            }

            // 3. Lưu findings
            $findingStmt = $pdo->prepare("
                INSERT INTO findings (scan_id, link_id, target_url, error_type, http_status, final_url, message, checked_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            foreach ($crawlResult['findings'] as $finding) {
                $key = $finding['source_url'] . '|' . $finding['target_url'];
                $linkId = $linkIdMap[$key] ?? null;

                $findingStmt->execute([
                    $scanId,
                    $linkId,
                    $finding['target_url'],
                    $finding['error_type'],
                    $finding['http_status'],
                    $finding['final_url'],
                    $finding['message'],
                ]);
            }

            $pdo->commit();

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Xóa scan run
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM scan_runs WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
