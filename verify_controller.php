<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

// This script verifies that all AdminPriceMatcherController references have been updated to AdminPriceMatcher
// It scans all PHP files in the module directory and reports any remaining occurrences

// Define the module directory
$moduleDir = __DIR__;

// Create a log file
$logFile = $moduleDir . '/logs/verify_controller_' . date('Y-m-d_H-i-s') . '.log';

// Ensure logs directory exists
if (!is_dir($moduleDir . '/logs')) {
    mkdir($moduleDir . '/logs', 0755, true);
}

// Initialize log
$log = "=== ART PriceMatcher Controller Name Verification ===\n";
$log .= "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Function to scan a directory recursively
function scanDirectory($dir, &$log) {
    $issues = 0;
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            // Skip logs directory
            if (basename($path) === 'logs') {
                continue;
            }
            // Recursively scan subdirectories
            $issues += scanDirectory($path, $log);
        } else {
            // Check PHP and TPL files
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if ($ext === 'php' || $ext === 'tpl') {
                $content = file_get_contents($path);
                
                // Check for AdminPriceMatcherController
                if (strpos($content, 'AdminPriceMatcherController') !== false) {
                    $log .= "ISSUE: Found 'AdminPriceMatcherController' in file: " . str_replace($GLOBALS['moduleDir'] . '/', '', $path) . "\n";
                    $issues++;
                }
                
                // For TPL files, check for old link format
                if ($ext === 'tpl' && strpos($content, "getAdminLink('AdminPriceMatcher', true, [], [") !== false) {
                    $log .= "ISSUE: Found old link format in TPL file: " . str_replace($GLOBALS['moduleDir'] . '/', '', $path) . "\n";
                    $issues++;
                }
            }
        }
    }
    
    return $issues;
}

// Scan the module directory
$issues = scanDirectory($moduleDir, $log);

// Add summary to log
$log .= "\n=== Summary ===\n";
if ($issues === 0) {
    $log .= "✓ No issues found! All controller references have been updated correctly.\n";
} else {
    $log .= "✗ Found {$issues} issue(s) that need to be fixed.\n";
}

// Write log to file
file_put_contents($logFile, $log);

// Display results
echo "<pre>";
echo $log;
echo "\nLog saved to: " . str_replace($moduleDir . '/', '', $logFile);
echo "</pre>";
