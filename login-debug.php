<?php
/**
 * Login Debug Script
 * Shows actual error messages
 */

define('APP_ACCESS', true);
require_once 'config/config.php';

echo "<h1>🔍 Login Error Debug</h1>";
echo "<hr>";

// Test 1: Main database connection
echo "<h3>1️⃣ Main Database (DB_NAME: " . DB_NAME . ")</h3>";
try {
    $testConn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    echo "<div style='background:#4CAF50; color:white; padding:15px; border-radius:5px;'>";
    echo "✅ Connected to: " . DB_NAME . "";
    echo "</div>";
    unset($testConn);
} catch (PDOException $e) {
    echo "<div style='background:#f44336; color:white; padding:15px; border-radius:5px;'>";
    echo "❌ Error: " . $e->getMessage() . "";
    echo "</div>";
}

echo "<br><br>";

// Test 2: Master database (adf_system) - what login.php tries
echo "<h3>2️⃣ Master Database (adf_system) - for login.php</h3>";
try {
    $masterPdo = new PDO("mysql:host=" . DB_HOST . ";dbname=adf_system", DB_USER, DB_PASS);
    echo "<div style='background:#4CAF50; color:white; padding:15px; border-radius:5px;'>";
    echo "✅ Connected to: adf_system<br>";
    
    // Check if users table exists
    $tables = $masterPdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (!empty($tables)) {
        echo "✅ users table exists<br>";
    } else {
        echo "❌ users table NOT found<br>";
    }
    echo "</div>";
} catch (PDOException $e) {
    echo "<div style='background:#f44336; color:white; padding:15px; border-radius:5px;'>";
    echo "❌ Error connecting to adf_system: " . $e->getMessage() . "";
    echo "</div>";
}

echo "<br><br>";

// Test 3: Check Auth class
echo "<h3>3️⃣ Auth Class</h3>";
try {
    require_once 'includes/auth.php';
    echo "<div style='background:#4CAF50; color:white; padding:15px; border-radius:5px;'>";
    echo "✅ Auth class loaded successfully<br>";
    $auth = new Auth();
    echo "Auth object created<br>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background:#f44336; color:white; padding:15px; border-radius:5px;'>";
    echo "❌ Auth Error: " . $e->getMessage() . "";
    echo "</div>";
}

echo "<br><br>";

// Test 4: Check functions
echo "<h3>4️⃣ Functions & Helpers</h3>";
try {
    require_once 'includes/functions.php';
    echo "<div style='background:#4CAF50; color:white; padding:15px; border-radius:5px;'>";
    echo "✅ Functions loaded<br>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background:#f44336; color:white; padding:15px; border-radius:5px;'>";
    echo "❌ Functions Error: " . $e->getMessage() . "";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='login.php'>← Back to Login</a></p>";
?>
