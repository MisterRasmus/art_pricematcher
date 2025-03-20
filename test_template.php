<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

// This script tests different template path formats to help diagnose Smarty template loading issues

// Define paths
define('_PS_ROOT_DIR_', realpath(__DIR__ . '/../../'));
define('_PS_ADMIN_DIR_', _PS_ROOT_DIR_ . '/admin-dev');

// Include PrestaShop configuration
include_once(_PS_ROOT_DIR_ . '/config/config.inc.php');

// Check if the user has permission
if (!isset($cookie) || !$cookie->id_employee || !Employee::existsInDatabase($cookie->id_employee, $cookie->passwd)) {
    die('You must be logged in to the PrestaShop admin to run this script.');
}

// Get Smarty instance
$smarty = Context::getContext()->smarty;

// Test various template path formats
$templateFormats = [
    'Relative Path' => '../modules/art_pricematcher/views/templates/admin/layout.tpl',
    'Module Path' => 'module:art_pricematcher/views/templates/admin/layout.tpl',
    'Absolute Path' => _PS_MODULE_DIR_ . 'art_pricematcher/views/templates/admin/layout.tpl',
    'Full Path' => __DIR__ . '/views/templates/admin/layout.tpl',
    'Admin Theme Override' => 'controllers/modules/art_pricematcher/layout.tpl'
];

echo '<h1>ART PriceMatcher Template Test</h1>';
echo '<p>This script tests different template path formats to help diagnose Smarty template loading issues.</p>';

echo '<h2>Template Path Tests</h2>';
echo '<ul>';

foreach ($templateFormats as $formatName => $templatePath) {
    echo '<li><strong>' . $formatName . ':</strong> ' . $templatePath;
    
    try {
        // Test if the template file exists
        if (file_exists($templatePath)) {
            echo ' <span style="color: green;">[File exists]</span>';
        } else {
            // For module: format, we need to check differently
            if (strpos($templatePath, 'module:') === 0) {
                $modulePath = str_replace('module:', _PS_MODULE_DIR_, $templatePath);
                $modulePath = str_replace('/', DIRECTORY_SEPARATOR, $modulePath);
                
                if (file_exists($modulePath)) {
                    echo ' <span style="color: green;">[File exists at ' . $modulePath . ']</span>';
                } else {
                    echo ' <span style="color: red;">[File does not exist at ' . $modulePath . ']</span>';
                }
            } else {
                echo ' <span style="color: red;">[File does not exist]</span>';
            }
        }
        
        // Test if Smarty can fetch the template
        try {
            $smarty->fetch($templatePath);
            echo ' <span style="color: green;">[Template can be loaded]</span>';
        } catch (Exception $e) {
            echo ' <span style="color: red;">[Template cannot be loaded: ' . $e->getMessage() . ']</span>';
        }
    } catch (Exception $e) {
        echo ' <span style="color: red;">[Error: ' . $e->getMessage() . ']</span>';
    }
    
    echo '</li>';
}

echo '</ul>';

echo '<h2>Module Directory Information</h2>';
echo '<ul>';
echo '<li><strong>Module Directory:</strong> ' . __DIR__ . '</li>';
echo '<li><strong>_PS_MODULE_DIR_:</strong> ' . _PS_MODULE_DIR_ . '</li>';
echo '<li><strong>Layout Template Path:</strong> ' . __DIR__ . '/views/templates/admin/layout.tpl</li>';
echo '</ul>';

// Check if layout.tpl exists
$layoutPath = __DIR__ . '/views/templates/admin/layout.tpl';
if (file_exists($layoutPath)) {
    echo '<h2>Layout Template Content</h2>';
    echo '<pre>' . htmlspecialchars(file_get_contents($layoutPath)) . '</pre>';
} else {
    echo '<p style="color: red;">Layout template file does not exist at: ' . $layoutPath . '</p>';
}

// Check permissions
echo '<h2>File Permissions</h2>';
echo '<ul>';
$templateDir = __DIR__ . '/views/templates/admin';
if (is_dir($templateDir)) {
    echo '<li><strong>Template Directory:</strong> ' . substr(sprintf('%o', fileperms($templateDir)), -4) . '</li>';
    
    $files = scandir($templateDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $templateDir . '/' . $file;
            echo '<li><strong>' . $file . ':</strong> ' . substr(sprintf('%o', fileperms($filePath)), -4) . '</li>';
        }
    }
} else {
    echo '<li style="color: red;">Template directory does not exist: ' . $templateDir . '</li>';
}
echo '</ul>';
