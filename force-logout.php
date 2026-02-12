<?php
// Force clear ALL session cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Start fresh session
session_start();
session_unset();
session_destroy();

// Clear all cookies
foreach ($_COOKIE as $key => $value) {
    setcookie($key, '', time() - 3600, '/');
}

// Redirect to login - detect environment
$isLocal = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
            strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
$basePath = $isLocal ? '/adf_system' : '';
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . $basePath . '/login.php');
exit;
?>
