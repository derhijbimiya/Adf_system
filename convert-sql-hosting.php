<?php
/**
 * SQL Converter for Niaga Hoster (cPanel)
 * Converts local SQL dumps to hosting-compatible format
 * 
 * Perubahan yang dilakukan:
 * 1. DEFINER=`root`@`localhost` → DEFINER=`adfb2574`@`localhost`
 * 2. Database names: adf_system → adfb2574_adf, etc.
 * 3. Handles VIEWs, TRIGGERs, and PROCEDUREs DEFINER
 */

// Configuration
$cpanelUser = 'adfb2574';
$outputDir = 'C:/Users/Public/';

// Database name mapping
$dbMapping = [
    'adf_system' => 'adfb2574_adf',
    'adf_narayana_hotel' => 'adfb2574_narayana_hotel',
    'adf_benscafe' => 'adfb2574_Adf_Bens',
    'adf_narayana_db' => 'adfb2574_narayana_db',
];

// Files to convert
$sqlFiles = [
    'adf_system.sql',
    'adf_narayana_hotel.sql',
    'adf_benscafe.sql',
    'adf_narayana_db.sql',
];

echo "===========================================\n";
echo "   SQL CONVERTER FOR NIAGA HOSTER\n";
echo "===========================================\n\n";

// Process each file
foreach ($sqlFiles as $file) {
    $inputPath = __DIR__ . '/' . $file;
    
    if (!file_exists($inputPath)) {
        echo "⚠️ File not found: $file - Skipping\n";
        continue;
    }
    
    echo "📄 Processing: $file\n";
    
    $content = file_get_contents($inputPath);
    $originalSize = strlen($content);
    
    // 1. Replace DEFINER - multiple patterns
    // Pattern: DEFINER=`root`@`localhost`
    $content = preg_replace(
        '/DEFINER=`[^`]+`@`[^`]+`/',
        "DEFINER=`{$cpanelUser}`@`localhost`",
        $content
    );
    
    // 2. Replace database names
    foreach ($dbMapping as $local => $hosting) {
        // Replace in USE statements
        $content = str_replace("USE `$local`", "USE `$hosting`", $content);
        
        // Replace in CREATE DATABASE
        $content = str_replace("CREATE DATABASE IF NOT EXISTS `$local`", "CREATE DATABASE IF NOT EXISTS `$hosting`", $content);
        $content = str_replace("CREATE DATABASE `$local`", "CREATE DATABASE `$hosting`", $content);
        
        // Replace database references in VIEWs and other statements
        $content = str_replace("`$local`.", "`$hosting`.", $content);
        
        // Replace in comments
        $content = str_replace("Database: $local", "Database: $hosting", $content);
        $content = str_replace("database `$local`", "database `$hosting`", $content);
    }
    
    // 3. Add header with Foreign Key disable
    $header = "-- ===========================================\n";
    $header .= "-- SQL File Converted for Niaga Hoster\n";
    $header .= "-- cPanel User: {$cpanelUser}\n";
    $header .= "-- Converted: " . date('Y-m-d H:i:s') . "\n";
    $header .= "-- ===========================================\n\n";
    $header .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $header .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
    $header .= "SET AUTOCOMMIT = 0;\n";
    $header .= "START TRANSACTION;\n\n";
    
    // Add footer
    $footer = "\n\nCOMMIT;\n";
    $footer .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    // Check if header already exists
    if (strpos($content, 'SET FOREIGN_KEY_CHECKS = 0') === false) {
        $content = $header . $content . $footer;
    }
    
    // Generate output filename
    $baseName = pathinfo($file, PATHINFO_FILENAME);
    $hostingDbName = $dbMapping[$baseName] ?? $baseName;
    $outputFile = $outputDir . "CONVERTED_{$hostingDbName}.sql";
    
    // Write converted file
    file_put_contents($outputFile, $content);
    $newSize = strlen($content);
    
    echo "   ✅ Converted to: CONVERTED_{$hostingDbName}.sql\n";
    echo "   📊 Size: " . number_format($originalSize/1024, 1) . " KB → " . number_format($newSize/1024, 1) . " KB\n\n";
}

echo "===========================================\n";
echo "Conversion complete!\n\n";
echo "Output files saved to: $outputDir\n\n";
echo "CHANGES MADE:\n";
echo "1. DEFINER changed to: {$cpanelUser}@localhost\n";
echo "2. Database names converted to hosting format\n";
echo "3. Foreign key checks disabled at start\n";
echo "4. Transaction wrap added\n\n";
echo "===========================================\n";
echo "\nNOW UPLOAD TO HOSTING:\n";
echo "1. Open phpMyAdmin di cPanel\n";
echo "2. Pilih database yang sesuai\n";
echo "3. Import file CONVERTED_*.sql\n";
echo "===========================================\n";
