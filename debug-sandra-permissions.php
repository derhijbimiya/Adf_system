<?php
/**
 * Check Sandra's Permissions - Debug
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=adf_system;charset=utf8mb4',
        'root',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get Sandra's info
    $stmt = $pdo->prepare('SELECT id, username, full_name FROM users WHERE username = ?');
    $stmt->execute(['sandra']);
    $sandra = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sandra) {
        throw new Exception('Sandra not found');
    }

    $result = [
        'user' => $sandra,
        'businesses' => [],
        'total_permission_rows' => 0,
        'menus_available' => [],
        'issues' => []
    ];

    // Get Sandra's businesses
    $stmt = $pdo->prepare('
        SELECT b.id, b.business_name FROM user_business_assignment uba
        JOIN businesses b ON uba.business_id = b.id
        WHERE uba.user_id = ?
    ');
    $stmt->execute([$sandra['id']]);
    $result['businesses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all menus available in system
    $stmt = $pdo->query('SELECT DISTINCT menu_code FROM user_menu_permissions ORDER BY menu_code');
    $result['menus_available'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get Sandra's permissions detail
    $stmt = $pdo->prepare('
        SELECT 
            business_id,
            menu_code,
            can_view,
            can_create,
            can_edit,
            can_delete
        FROM user_menu_permissions 
        WHERE user_id = ?
        ORDER BY business_id, menu_code
    ');
    $stmt->execute([$sandra['id']]);
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result['total_permission_rows'] = count($permissions);
    $result['permissions_detail'] = $permissions;

    // Group by business
    $byBusiness = [];
    foreach ($permissions as $perm) {
        $bizId = $perm['business_id'];
        if (!isset($byBusiness[$bizId])) {
            $byBusiness[$bizId] = [];
        }
        $byBusiness[$bizId][] = $perm['menu_code'];
    }
    $result['permissions_by_business'] = $byBusiness;

    // Check for issues
    if (empty($permissions)) {
        $result['issues'][] = '❌ NO PERMISSIONS FOUND!';
    }

    if (count($permissions) < count($result['menus_available']) * count($result['businesses'])) {
        $expected = count($result['menus_available']) * count($result['businesses']);
        $result['issues'][] = "⚠️ Incomplete permissions: $result[total_permission_rows] rows, expected $expected";
    }

    if (array_values($result['menus_available']) != array_keys(array_flip($result['menus_available']))) {
        // Check if some menus missing
        $menus_in_db = [];
        foreach ($permissions as $p) {
            $menus_in_db[] = $p['menu_code'];
        }
        $menus_in_db = array_unique($menus_in_db);
        $missing = array_diff($result['menus_available'], $menus_in_db);
        if ($missing) {
            $result['issues'][] = "❌ Missing menus: " . implode(', ', $missing);
        }
    }

    // Show conclusion
    if (empty($result['issues'])) {
        $result['status'] = '✅ All permissions look good!';
    } else {
        $result['status'] = '❌ Permission issues found!';
    }

} catch (Exception $e) {
    http_response_code(500);
    $result = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
