<?php
/**
 * Reset Sandra password di hosting master database
 */

define('APP_ACCESS', true);
require_once __DIR__ . '/config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<h2>Reset Sandra Password</h2>";
    echo "<pre>";
    
    // Hash password
    $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
    
    // Check if Sandra exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'sandra'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Update existing Sandra
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET password = ? 
            WHERE username = 'sandra'
        ");
        $updateStmt->execute([$hashedPassword]);
        echo "✓ Sandra password UPDATED\n";
        echo "  Username: sandra\n";
        echo "  Password: admin123\n";
        echo "  ID: {$user['id']}\n";
    } else {
        // Create new Sandra
        $insertStmt = $pdo->prepare("
            INSERT INTO users (username, password, full_name, email, phone, role_id, is_active)
            VALUES (?, ?, ?, ?, ?, 3, 1)
        ");
        $insertStmt->execute([
            'sandra',
            $hashedPassword,
            'Sandra Oktavia',
            'sandra@system.local',
            '0812345678'
        ]);
        $sandraId = $pdo->lastInsertId();
        echo "✓ Sandra CREATED\n";
        echo "  ID: $sandraId\n";
        echo "  Username: sandra\n";
        echo "  Password: admin123\n";
    }
    
    // Show Sandra info
    $checkStmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.email, u.role_id, u.is_active
        FROM users 
        WHERE username = 'sandra'
    ");
    $checkStmt->execute();
    $sandra = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "SANDRA INFO DI MASTER DATABASE:\n";
    echo str_repeat("=", 60) . "\n";
    echo "ID: {$sandra['id']}\n";
    echo "Username: {$sandra['username']}\n";
    echo "Full Name: {$sandra['full_name']}\n";
    echo "Email: {$sandra['email']}\n";
    echo "Role ID: {$sandra['role_id']}\n";
    echo "Active: " . ($sandra['is_active'] ? "YES" : "NO") . "\n";
    
    echo "\nSILAKAN COBA LOGIN DENGAN:\n";
    echo "Username: sandra\n";
    echo "Password: admin123\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
