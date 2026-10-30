<?php
/**
 * ScanComparator - So sánh kết quả giữa 2 lần kiểm tra
 * 
 * Chức năng 5: So sánh hai lần kiểm tra để chỉ ra:
 * - Lỗi mới (trước OK, nay lỗi)
 * - Lỗi đã hết (trước lỗi, nay OK)
 * - Mục chưa kiểm tra
 */

class ScanComparator
{
    /**
     * So sánh 2 scan runs
     *
     * @param int $scanId1 ID scan lần 1 (cũ)
     * @param int $scanId2 ID scan lần 2 (mới)
     * @return array [
     *   'new_errors'       => [...],  // Lỗi mới xuất hiện
     *   'resolved_errors'  => [...],  // Lỗi đã được sửa
     *   'persistent_errors'=> [...],  // Lỗi vẫn còn
     *   'not_checked'      => [...],  // Có ở lần 1, không có ở lần 2
     *   'stats' => [...]
     * ]
     */
    public static function compare(int $scanId1, int $scanId2): array
    {
        $pdo = Database::getConnection();

        // Lấy findings của scan 1
        $stmt1 = $pdo->prepare("
            SELECT f.target_url, f.error_type, f.http_status, f.message, 
                   l.source_page_id, p.url as source_url
            FROM findings f
            LEFT JOIN links l ON f.link_id = l.id
            LEFT JOIN pages p ON l.source_page_id = p.id
            WHERE f.scan_id = ?
        ");
        $stmt1->execute([$scanId1]);
        $findings1 = $stmt1->fetchAll();

        // Lấy findings của scan 2
        $stmt2 = $pdo->prepare("
            SELECT f.target_url, f.error_type, f.http_status, f.message,
                   l.source_page_id, p.url as source_url
            FROM findings f
            LEFT JOIN links l ON f.link_id = l.id
            LEFT JOIN pages p ON l.source_page_id = p.id
            WHERE f.scan_id = ?
        ");
        $stmt2->execute([$scanId2]);
        $findings2 = $stmt2->fetchAll();

        // Lấy danh sách pages đã duyệt của scan 2
        $stmt3 = $pdo->prepare("
            SELECT url, status FROM pages WHERE scan_id = ?
        ");
        $stmt3->execute([$scanId2]);
        $pages2 = $stmt3->fetchAll();
        $scannedUrls2 = array_column($pages2, 'status', 'url');

        // Tạo map lỗi theo target_url
        $errors1 = self::groupByTarget($findings1);
        $errors2 = self::groupByTarget($findings2);

        $newErrors        = [];
        $resolvedErrors   = [];
        $persistentErrors = [];
        $notChecked       = [];

        // Tìm lỗi đã hết và lỗi vẫn còn
        foreach ($errors1 as $targetUrl => $data1) {
            if (isset($errors2[$targetUrl])) {
                // Lỗi vẫn còn
                $persistentErrors[] = [
                    'target_url' => $targetUrl,
                    'old_error'  => $data1,
                    'new_error'  => $errors2[$targetUrl],
                ];
            } elseif (isset($scannedUrls2[UrlNormalizer::removeFragment($targetUrl)])) {
                // URL đã được kiểm tra lần 2 nhưng không còn lỗi → đã sửa
                $resolvedErrors[] = [
                    'target_url' => $targetUrl,
                    'old_error'  => $data1,
                ];
            } else {
                // URL không được kiểm tra ở lần 2
                $notChecked[] = [
                    'target_url' => $targetUrl,
                    'old_error'  => $data1,
                    'reason'     => 'Không nằm trong phạm vi lần kiểm tra 2',
                ];
            }
        }

        // Tìm lỗi mới (có ở lần 2, không có ở lần 1)
        foreach ($errors2 as $targetUrl => $data2) {
            if (!isset($errors1[$targetUrl])) {
                $newErrors[] = [
                    'target_url' => $targetUrl,
                    'error'      => $data2,
                ];
            }
        }

        return [
            'new_errors'        => $newErrors,
            'resolved_errors'   => $resolvedErrors,
            'persistent_errors' => $persistentErrors,
            'not_checked'       => $notChecked,
            'stats'             => [
                'total_errors_scan1' => count($errors1),
                'total_errors_scan2' => count($errors2),
                'new_errors'         => count($newErrors),
                'resolved_errors'    => count($resolvedErrors),
                'persistent_errors'  => count($persistentErrors),
                'not_checked'        => count($notChecked),
            ],
        ];
    }

    /**
     * Nhóm findings theo target_url
     */
    private static function groupByTarget(array $findings): array
    {
        $grouped = [];
        foreach ($findings as $f) {
            $target = $f['target_url'];
            if (!isset($grouped[$target])) {
                $grouped[$target] = [
                    'error_type'  => $f['error_type'],
                    'http_status' => $f['http_status'],
                    'message'     => $f['message'],
                    'sources'     => [],
                ];
            }
            if ($f['source_url'] && !in_array($f['source_url'], $grouped[$target]['sources'])) {
                $grouped[$target]['sources'][] = $f['source_url'];
            }
        }
        return $grouped;
    }
}
