<?php
// Simple webhook untuk trigger git pull
// Setup: 1) Upload deploy.sh ke /home/adfb2574/
//        2) chmod +x deploy.sh via terminal
//        3) Add webhook di GitHub: https://adfsystem.online/webhook-deploy.php

$secret = 'YOUR_SECRET_KEY_HERE'; // Ganti dengan key rahasia
$received_secret = $_GET['key'] ?? '';

if ($received_secret !== $secret) {
    http_response_code(403);
    die('Forbidden');
}

// Execute deploy script
$output = shell_exec('cd /home/adfb2574/public_html/adf_system && git pull origin main 2>&1');

echo "<h2>✅ Deploy Success</h2>";
echo "<pre>$output</pre>";
?>
