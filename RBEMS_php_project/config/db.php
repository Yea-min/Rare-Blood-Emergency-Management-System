<?php
// ---------------------------------------------------------------------
// Database connection (PDO) - RBEMS
// Update these four constants to match your MySQL environment.
// ---------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'rbems');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    // Keep the single fixed administrator available for existing databases.
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        admin_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $adminStmt = $pdo->prepare(
        'INSERT INTO admins (username, password_hash) VALUES (?, ?) '
        . 'ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)'
    );
    $adminStmt->execute(['admin1', hash('sha256', '12345678')]);

    // Keep existing installations compatible with username-based login.
    foreach (['patient_users', 'donor_users'] as $userTable) {
        $columnStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = \'username\''
        );
        $columnStmt->execute([$userTable]);
        if (!$columnStmt->fetchColumn()) {
            $pdo->exec("ALTER TABLE `$userTable` ADD username VARCHAR(50) NULL AFTER name");
            $pdo->exec("ALTER TABLE `$userTable` ADD UNIQUE KEY `uq_{$userTable}_username` (username)");
        }
    }
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

session_start();
