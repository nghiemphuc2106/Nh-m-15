<?php
/**
 * Model Site - Quản lý website cần kiểm tra
 */

class Site
{
    /**
     * Lấy tất cả sites của user
     */
    public static function getAllByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, u.username as created_by_name,
                   (SELECT COUNT(*) FROM scan_runs WHERE site_id = s.id) as scan_count
            FROM sites s
            JOIN users u ON s.created_by = u.id
            WHERE s.created_by = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy tất cả sites (admin)
     */
    public static function getAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT s.*, u.username as created_by_name,
                   (SELECT COUNT(*) FROM scan_runs WHERE site_id = s.id) as scan_count
            FROM sites s
            JOIN users u ON s.created_by = u.id
            ORDER BY s.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Lấy site theo ID
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Tạo site mới
     */
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO sites (name, seed_url, allowed_domains, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['seed_url'],
            json_encode($data['allowed_domains']),
            $data['created_by'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * Cập nhật site
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE sites SET name = ?, seed_url = ?, allowed_domains = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['seed_url'],
            json_encode($data['allowed_domains']),
            $id,
        ]);
    }

    /**
     * Xóa site
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Kiểm tra user có quyền truy cập site
     */
    public static function userCanAccess(int $siteId, int $userId, string $role): bool
    {
        if ($role === 'admin') {
            return true;
        }
        $site = self::findById($siteId);
        return $site && $site['created_by'] == $userId;
    }
}
