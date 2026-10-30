<?php
/**
 * Seed Script - Tạo dữ liệu mẫu
 * Chạy: C:\xampp\php\php.exe database/seed.php
 */

require_once __DIR__ . '/../src/Core/Database.php';

try {
    $pdo = Database::getConnection();

    echo "=== Link Checker - Seeding Data ===\n\n";

    // -------------------------------------------------------
    // 1. Tạo users mẫu
    // -------------------------------------------------------
    $password = password_hash('password123', PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`)
        VALUES (?, ?, ?, ?)
    ");

    $users = [
        ['admin', 'admin@example.com', $password, 'admin'],
        ['user1', 'user@example.com',  $password, 'user'],
        ['tester', 'tester@example.com', $password, 'user'],
    ];

    foreach ($users as $user) {
        $stmt->execute($user);
    }
    echo "[OK] Tao " . count($users) . " users\n";

    // -------------------------------------------------------
    // 2. Tạo site mẫu (website thử nghiệm)
    // -------------------------------------------------------
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO `sites` (`name`, `seed_url`, `allowed_domains`, `created_by`)
        VALUES (?, ?, ?, ?)
    ");

    $sites = [
        [
            'Website Thu Nghiem',
            'http://localhost:8888',
            json_encode(['localhost:8888']),
            1
        ],
    ];

    foreach ($sites as $site) {
        $stmt->execute($site);
    }
    echo "[OK] Tao " . count($sites) . " sites\n";

    echo "\n=== Seeding hoan tat! ===\n";
    echo "\nTai khoan:\n";
    echo "  Admin: admin@example.com / password123\n";
    echo "  User:  user@example.com  / password123\n";

} catch (PDOException $e) {
    echo "[LOI] " . $e->getMessage() . "\n";
    exit(1);
}
