<?php
// Test Database Connection for Hosting
echo "<h2>Database Connection Test</h2>";
echo "<strong>Current Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "<br>";

// Production credentials
$host = 'localhost';
$dbname = 'adfb2574_adf_system';
$username = 'adfb2574_adfsystem';
$password = '@Nnoc2025';

echo "<br><strong>Testing Connection to:</strong><br>";
echo "Host: $host<br>";
echo "Database: $dbname<br>";
echo "Username: $username<br>";
echo "Password: " . str_repeat('*', strlen($password)) . "<br><br>";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "<div style='color:green; font-weight:bold;'>✅ CONNECTION SUCCESS!</div><br>";
    
    // Test query
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<strong>Tables in database (" . count($tables) . "):</strong><br>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<div style='color:red; font-weight:bold;'>❌ CONNECTION FAILED!</div><br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";
}

echo "<br><hr><strong>Next Steps:</strong><br>";
echo "1. If connection failed: Check database name, username, password in cPanel<br>";
echo "2. If connection success: Update config.php with auto-detect code<br>";
?>
