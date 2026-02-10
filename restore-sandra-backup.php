<?php
/**
 * RESTORE Sandra - Kembalikan ke kondisi yang TADI BERHASIL
 * Check apa yang berubah dan restore
 */

echo "<h1>🔄 RESTORE Sandra ke Kondisi Working</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .success { color: white; background: #4CAF50; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .error { color: white; background: #f44336; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .warning { color: white; background: #ff9800; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #2196F3; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; background: white; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background: #2196F3; color: white; }
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 4px; font-family: 'Courier New'; }
</style>";

// Connect to MASTER
$masterPdo = new PDO("mysql:host=localhost;dbname=adf_system", "root", "");
$masterPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<div class='info'><h3>📊 Current Status Check</h3></div>";

// Check Sandra in MASTER
$stmt = $masterPdo->prepare("SELECT * FROM users WHERE username = 'sandra'");
$stmt->execute();
$sandra = $stmt->fetch(PDO::FETCH_ASSOC);

if ($sandra) {
    echo "<div class='success'>✅ Sandra EXISTS in MASTER database</div>";
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$sandra['id']}</td></tr>";
    echo "<tr><td>Username</td><td>{$sandra['username']}</td></tr>";
    echo "<tr><td>Full Name</td><td>{$sandra['full_name']}</td></tr>";
    echo "<tr><td>Email</td><td>{$sandra['email']}</td></tr>";
    echo "<tr><td>Role ID</td><td>{$sandra['role_id']}</td></tr>";
    echo "<tr><td>Is Active</td><td>" . ($sandra['is_active'] ? 'YES ✅' : 'NO ❌') . "</td></tr>";
    echo "<tr><td>Password (hash)</td><td>" . substr($sandra['password'], 0, 40) . "...</td></tr>";
    echo "</table>";
    
    // Test passwords yang mungkin
    echo "<div class='info'><h3>🔐 Password Tests</h3></div>";
    $testPasswords = ['admin123', 'sandra123', 'password', 'admin'];
    
    foreach ($testPasswords as $testPwd) {
        if (password_verify($testPwd, $sandra['password'])) {
            echo "<div class='success'>✅ Password is: <strong>$testPwd</strong> (password_verify)</div>";
            $workingPassword = $testPwd;
            break;
        } else if (md5($testPwd) === $sandra['password']) {
            echo "<div class='success'>✅ Password is: <strong>$testPwd</strong> (MD5)</div>";
            $workingPassword = $testPwd;
            break;
        }
    }
    
    if (!isset($workingPassword)) {
        echo "<div class='warning'>⚠️ Password tidak match dengan test passwords standard</div>";
        echo "<p>Trying to check if there's a backup or previous user...</p>";
        
        // Check all users to see pattern
        $allUsers = $masterPdo->query("SELECT id, username, full_name, role_id FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='info'><h4>All Users in Database:</h4>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role ID</th></tr>";
        foreach ($allUsers as $u) {
            $highlight = ($u['username'] === 'sandra') ? 'style="background: #ffffcc;"' : '';
            echo "<tr $highlight><td>{$u['id']}</td><td><strong>{$u['username']}</strong></td><td>{$u['full_name']}</td><td>{$u['role_id']}</td></tr>";
        }
        echo "</table></div>";
    }
    
    // Check permissions
    echo "<div class='info'><h3>🔑 Permission Check</h3></div>";
    $permStmt = $masterPdo->prepare("
        SELECT b.business_name, b.business_code, COUNT(p.menu_id) as menu_count
        FROM user_menu_permissions p
        JOIN businesses b ON p.business_id = b.id
        WHERE p.user_id = ?
        GROUP BY b.id
    ");
    $permStmt->execute([$sandra['id']]);
    $perms = $permStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($perms)) {
        echo "<table>";
        echo "<tr><th>Business</th><th>Code</th><th>Menus</th></tr>";
        foreach ($perms as $p) {
            echo "<tr><td>{$p['business_name']}</td><td>{$p['business_code']}</td><td>{$p['menu_count']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ NO permissions found for Sandra!</div>";
    }
    
} else {
    echo "<div class='error'>❌ Sandra NOT FOUND in MASTER database</div>";
}

echo "<hr>";
echo "<div class='info'><h3>🔧 SOLUTION OPTIONS:</h3>";

echo "<form method='POST' style='background: white; padding: 20px; border-radius: 8px;'>";
echo "<h4>Option 1: Reset Password to 'admin123'</h4>";
echo "<button type='submit' name='action' value='reset_password' style='padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;'>Reset Password to admin123</button>";

echo "<hr style='margin: 20px 0;'>";

echo "<h4>Option 2: Check & Test Login with username LAIN yang BERHASIL tadi</h4>";
echo "<p>Coba ingat: username apa yang BERHASIL login tadi? (developer? admin? owner?)</p>";
echo "<input type='text' name='test_username' placeholder='Masukkan username yang berhasil tadi' style='width: 300px; padding: 8px;'>";
echo "<button type='submit' name='action' value='check_other' style='padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;'>Check User Ini</button>";

echo "</form>";
echo "</div>";

// Handle submitted actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'reset_password') {
        echo "<hr>";
        echo "<div class='info'><h3>🔄 Resetting Password...</h3></div>";
        
        $newPassword = 'admin123';
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $updateStmt = $masterPdo->prepare("UPDATE users SET password = ?, is_active = 1 WHERE username = 'sandra'");
        $updateStmt->execute([$hashedPassword]);
        
        echo "<div class='success'>✅ Password reset to: <strong>admin123</strong></div>";
        echo "<p>Test login: <a href='login.php' style='color: blue; font-weight: bold;'>login.php</a></p>";
        echo "<p>Username: <code>sandra</code></p>";
        echo "<p>Password: <code>admin123</code></p>";
        
    } else if ($action === 'check_other' && !empty($_POST['test_username'])) {
        $testUser = $_POST['test_username'];
        echo "<hr>";
        echo "<div class='info'><h3>🔍 Checking user: $testUser</h3></div>";
        
        $checkStmt = $masterPdo->prepare("SELECT * FROM users WHERE username = ?");
        $checkStmt->execute([$testUser]);
        $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<div class='success'>✅ User '$testUser' FOUND</div>";
            echo "<table>";
            echo "<tr><th>Property</th><th>Value</th></tr>";
            foreach ($user as $key => $val) {
                if ($key === 'password') {
                    echo "<tr><td>$key</td><td>" . substr($val, 0, 40) . "...</td></tr>";
                } else {
                    echo "<tr><td>$key</td><td>$val</td></tr>";
                }
            }
            echo "</table>";
            
            // Test common passwords
            $commonPwds = ['admin', 'admin123', 'password', $testUser, $testUser . '123'];
            foreach ($commonPwds as $pwd) {
                if (password_verify($pwd, $user['password']) || md5($pwd) === $user['password']) {
                    echo "<div class='success'>✅ Password for '$testUser' is: <strong>$pwd</strong></div>";
                    break;
                }
            }
        } else {
            echo "<div class='error'>❌ User '$testUser' NOT FOUND</div>";
        }
    }
}

echo "<hr>";
echo "<div class='info'>";
echo "<h3>📋 Quick Access:</h3>";
echo "<ul>";
echo "<li><a href='login.php' style='color: blue;'>Try Login Page</a></li>";
echo "<li><a href='check-all-databases.php' style='color: blue;'>Check All Databases</a></li>";
echo "<li><a href='password-reset.php' style='color: blue;'>Emergency Password Reset Tool</a></li>";
echo "</ul>";
echo "</div>";
?>
