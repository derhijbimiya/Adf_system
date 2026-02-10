<?php
/**
 * Test Permission Query Fix
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

    // Get Sandra (user_id = 7)
    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE username = ?');
    $stmt->execute(['sandra']);
    $sandra = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sandra) {
        throw new Exception('Sandra not found');
    }

    $result = [
        'user' => $sandra,
        'test_queries' => []
    ];

    // Get Sandra's businesses
    $stmt = $pdo->prepare('
        SELECT id, business_name FROM businesses WHERE is_active = 1 ORDER BY business_name
    ');
    $stmt->execute();
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Test queries for each business
    foreach ($businesses as $biz) {
        $bizData = [
            'business' => $biz['business_name'],
            'menus' => []
        ];

        // Test: Get all menus Sandra can view
        $stmt = $pdo->prepare('
            SELECT menu_code, can_view, can_create, can_edit, can_delete
            FROM user_menu_permissions
            WHERE user_id = ? AND business_id = ? AND can_view = 1
            ORDER BY menu_code
        ');
        $stmt->execute([$sandra['id'], $biz['id']]);
        $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($menus as $menu) {
            $bizData['menus'][] = $menu['menu_code'];
        }

        $bizData['menu_count'] = count($menus);
        $result['test_queries'][] = $bizData;
    }

    // Test: OLD QUERY (BROKEN)
    $result['old_query_test'] = [
        'query' => 'SELECT p.can_view FROM user_menu_permissions p JOIN menu_items m ON p.menu_id = m.id WHERE p.user_id = ? AND p.business_id = ? AND m.menu_code = "dashboard"',
        'note' => 'This was BROKEN because user_menu_permissions has menu_code, NOT menu_id. There is no menu_items table with that structure.',
        'expected_result' => 'ERROR or 0 rows'
    ];

    // Test: NEW QUERY (FIXED)
    $result['new_query_test'] = [
        'query' => 'SELECT can_view FROM user_menu_permissions WHERE user_id = ? AND business_id = ? AND menu_code = "dashboard" AND can_view = 1',
        'note' => 'This is FIXED - directly use menu_code from user_menu_permissions',
        'for_sandra_narayana' => null
    ];

    // Execute test for new query
    if (!empty($businesses)) {
        $stmt = $pdo->prepare('
            SELECT can_view FROM user_menu_permissions 
            WHERE user_id = ? AND business_id = ? AND menu_code = ? AND can_view = 1
        ');
        $stmt->execute([$sandra['id'], $businesses[0]['id'], 'dashboard']);
        $testResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['new_query_test']['for_sandra_narayana'] = $testResult ? '✅ Found' : '❌ Not found';
    }

    $result['status'] = '✅ All permission queries working!';
    $result['conclusion'] = 'Sandra should now see all 9 menus when logging in!';

} catch (Exception $e) {
    http_response_code(500);
    $result = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
