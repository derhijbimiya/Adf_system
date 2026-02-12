<?php
/**
 * Debug Login Background - Check if background is configured properly
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Login Background Debug</h2>";

$db = Database::getInstance();

// Check settings
$loginBgSetting = $db->fetchOne("SELECT * FROM settings WHERE setting_key = 'login_background'");
echo "<h3>1. Database Setting:</h3>";
echo "<pre>";
print_r($loginBgSetting);
echo "</pre>";

$customBg = $loginBgSetting['setting_value'] ?? null;
echo "<p>Setting Value: <strong>" . ($customBg ?: 'NOT SET') . "</strong></p>";

// Check file
$bgPath = BASE_PATH . '/uploads/backgrounds/' . $customBg;
echo "<h3>2. File Check:</h3>";
echo "<p>Expected Path: <code>" . htmlspecialchars($bgPath) . "</code></p>";
echo "<p>File Exists: <strong>" . (file_exists($bgPath) ? 'YES ✓' : 'NO ✗') . "</strong></p>";

// Check uploads folder
$uploadDir = BASE_PATH . '/uploads/backgrounds/';
echo "<h3>3. Upload Directory:</h3>";
echo "<p>Path: <code>" . htmlspecialchars($uploadDir) . "</code></p>";
echo "<p>Directory Exists: <strong>" . (is_dir($uploadDir) ? 'YES ✓' : 'NO ✗') . "</strong></p>";
echo "<p>Writable: <strong>" . (is_writable($uploadDir) ? 'YES ✓' : 'NO ✗') . "</strong></p>";

// List files in directory
if (is_dir($uploadDir)) {
    echo "<p>Files in directory:</p><ul>";
    foreach (glob($uploadDir . '*') as $file) {
        echo "<li>" . basename($file) . " (" . filesize($file) . " bytes)</li>";
    }
    echo "</ul>";
}

// Generate URL
$bgUrl = $customBg && file_exists($bgPath) 
    ? BASE_URL . '/uploads/backgrounds/' . $customBg 
    : null;
    
echo "<h3>4. Generated URL:</h3>";
echo "<p><strong>" . ($bgUrl ?: 'NO URL (file missing or no setting)') . "</strong></p>";

if ($bgUrl) {
    echo "<h3>5. Background Preview:</h3>";
    echo "<div style='width:400px;height:250px;background-image:url(" . $bgUrl . ");background-size:cover;border:1px solid #ccc;'></div>";
}

echo "<hr>";
echo "<p><a href='login.php'>← Back to Login</a></p>";
