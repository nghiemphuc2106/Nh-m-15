<?php
/**
 * Model Finding - Kết quả lỗi phát hiện
 */

class Finding
{
    /**
     * Lấy findings của scan, có lọc
     */
    public static function getByScanId(int $scanId, array $filters = []): array
    {
        $pdo = Database::getConnection();
        $where = ["f.scan_id = ?"];
        $params = [$scanId];

        // Lọc theo loại lỗi
        if (!empty($filters['error_type'])) {
            $where[] = "f.error_type = ?";
            $params[] = $filters['error_type'];
        }

        // Lọc theo trang nguồn
        if (!empty($filters['source_url'])) {
            $where[] = "p.url LIKE ?";
            $params[] = '%' . $filters['source_url'] . '%';
        }

        // Lọc theo loại tài nguyên
        if (!empty($filters['link_type'])) {
            $where[] = "l.link_type = ?";
            $params[] = $filters['link_type'];
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT f.*, l.link_type, l.link_text, l.html_element,
                   p.url as source_url, p.depth as source_depth
            FROM findings f
            LEFT JOIN links l ON f.link_id = l.id
            LEFT JOIN pages p ON l.source_page_id = p.id
            WHERE {$whereClause}
            ORDER BY f.error_type, f.target_url
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lấy tất cả trang nguồn của một target URL trong một scan
     */
    public static function getSourcePages(int $scanId, string $targetUrl): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.url, p.depth, l.link_type, l.link_text, l.html_element
            FROM links l
            JOIN pages p ON l.source_page_id = p.id
            WHERE l.scan_id = ? AND l.target_url = ?
            ORDER BY p.url
        ");
        $stmt->execute([$scanId, $targetUrl]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy finding theo ID
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT f.*, l.link_type, l.link_text, l.html_element,
                   p.url as source_url
            FROM findings f
            LEFT JOIN links l ON f.link_id = l.id
            LEFT JOIN pages p ON l.source_page_id = p.id
            WHERE f.id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Đếm findings theo loại lỗi
     */
    public static function countByType(int $scanId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT error_type, COUNT(*) as count
            FROM findings
            WHERE scan_id = ?
            GROUP BY error_type
            ORDER BY count DESC
        ");
        $stmt->execute([$scanId]);
        return $stmt->fetchAll();
    }

    /**
     * Kiểm tra lại (re-check) một finding
     */
    public static function recheck(int $findingId): array
    {
        $finding = self::findById($findingId);
        if (!$finding) {
            return ['error' => 'Finding không tồn tại'];
        }

        // Tạo checker mới
        $checker = new LinkChecker(30, 'LinkChecker/1.0');
        $result = $checker->check($finding['target_url'], $finding['link_type'] ?? 'anchor');

        // Cập nhật trạng thái
        $pdo = Database::getConnection();
        if ($result['status'] === 'ok') {
            $stmt = $pdo->prepare("
                UPDATE findings SET is_resolved = 1, checked_at = NOW(),
                       message = CONCAT(IFNULL(message, ''), ' | Re-check: OK')
                WHERE id = ?
            ");
            $stmt->execute([$findingId]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE findings SET checked_at = NOW(),
                       message = CONCAT(IFNULL(message, ''), ' | Re-check: ', ?)
                WHERE id = ?
            ");
            $stmt->execute([$result['message'], $findingId]);
        }

        return $result;
    }

    /**
     * Lấy statistics tổng quan cho scan
     */
    public static function getStats(int $scanId): array
    {
        $pdo = Database::getConnection();

        // Tổng findings
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM findings WHERE scan_id = ?");
        $stmt->execute([$scanId]);
        $total = $stmt->fetch()['total'];

        // Đã resolved
        $stmt = $pdo->prepare("SELECT COUNT(*) as resolved FROM findings WHERE scan_id = ? AND is_resolved = 1");
        $stmt->execute([$scanId]);
        $resolved = $stmt->fetch()['resolved'];

        // Theo loại
        $byType = self::countByType($scanId);

        return [
            'total'    => $total,
            'resolved' => $resolved,
            'active'   => $total - $resolved,
            'by_type'  => $byType,
        ];
    }
}
