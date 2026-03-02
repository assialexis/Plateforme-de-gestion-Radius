<?php
$config = require 'config/config.php';
$db = $config['database'];
try {
    $pdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql1 = "
    CREATE TABLE IF NOT EXISTS `system_notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `type` varchar(20) NOT NULL DEFAULT 'info',
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_by` int(11) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $sql2 = "
    CREATE TABLE IF NOT EXISTS `system_notification_reads` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `notification_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `read_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_read` (`notification_id`, `user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql1);
    $pdo->exec($sql2);
    echo "Tables created successfully.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
