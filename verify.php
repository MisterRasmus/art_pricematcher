<?php
/**
 * Verification script for ART PriceMatcher module
 */

// Enable debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html');

echo '<h1>ART PriceMatcher Verification</h1>';

// Check if module directory exists
$moduleDir = dirname(__FILE__);
if (!is_dir($moduleDir)) {
    die('<p style="color: red;">Error: Module directory not found</p>');
}

echo '<p>Module directory: ' . $moduleDir . '</p>';

// Check key files
$files = [
    'art_pricematcher.php',
    'controllers/admin/AdminPriceMatcherController.php',
    'classes/tabs/Dashboard.php',
    'views/templates/admin/layout.tpl',
    'views/templates/admin/dashboard.tpl',
    'views/templates/admin/error.tpl'
];

echo '<h2>File Verification</h2>';
echo '<ul>';
foreach ($files as $file) {
    $filePath = $moduleDir . '/' . $file;
    $exists = file_exists($filePath);
    $readable = is_readable($filePath);
    $size = $exists ? filesize($filePath) : 0;
    
    echo '<li>';
    echo $file . ': ';
    if ($exists) {
        echo '<span style="color: green;">Exists</span> (' . $size . ' bytes)';
        if (!$readable) {
            echo ' <span style="color: orange;">Warning: Not readable</span>';
        }
    } else {
        echo '<span style="color: red;">Missing</span>';
    }
    echo '</li>';
}
echo '</ul>';

// Check template paths in controller
$controllerFile = $moduleDir . '/controllers/admin/AdminPriceMatcherController.php';
if (file_exists($controllerFile)) {
    $controllerContent = file_get_contents($controllerFile);
    
    echo '<h2>Controller Template Paths</h2>';
    echo '<ul>';
    
    // Check template paths
    if (strpos($controllerContent, '$this->setTemplate(\'module:art_pricematcher/') !== false) {
        echo '<li><span style="color: red;">Incorrect template path format found: module:art_pricematcher/</span></li>';
    } else if (strpos($controllerContent, '$this->setTemplate(\'../modules/art_pricematcher/') !== false) {
        echo '<li><span style="color: green;">Correct template path format found: ../modules/art_pricematcher/</span></li>';
    } else {
        echo '<li><span style="color: orange;">No standard template path format found</span></li>';
    }
    
    echo '</ul>';
}

// Check for admin links in templates
$dashboardFile = $moduleDir . '/classes/tabs/Dashboard.php';
if (file_exists($dashboardFile)) {
    $dashboardContent = file_get_contents($dashboardFile);
    
    echo '<h2>Admin Links in Dashboard.php</h2>';
    echo '<ul>';
    
    // Check admin links
    if (strpos($dashboardContent, '$this->context->link->getAdminLink(\'AdminPriceMatcher\'') !== false) {
        echo '<li><span style="color: green;">Correct admin link format found: AdminPriceMatcher</span></li>';
    } else if (strpos($dashboardContent, '$this->context->link->getAdminLink(\'AdminPriceMatcherController\'') !== false) {
        echo '<li><span style="color: red;">Incorrect admin link format found: AdminPriceMatcherController</span></li>';
    } else {
        echo '<li><span style="color: orange;">No standard admin link format found</span></li>';
    }
    
    echo '</ul>';
}

echo '<p>Verification completed. Please use this information to fix any remaining issues.</p>';
echo '<p>After fixing issues, clear your PrestaShop cache and try accessing the module again.</p>';
echo '<p>Access URL: <code>https://staging.ljustema.nu/admin-ljustema/index.php?controller=AdminPriceMatcher&token=YOUR_TOKEN</code></p>';
