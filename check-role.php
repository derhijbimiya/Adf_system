<?php
define('APP_ACCESS', true);

// Use local credentials directly for CLI
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=adf_system;charset=utf8mb4",
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Get admin user info
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.role_id, r.role_code, r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.username = 'admin'
    ");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Admin User Roles Check</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
