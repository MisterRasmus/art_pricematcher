<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

// This script clears the PrestaShop cache to ensure changes take effect

// Define paths
define('_PS_ROOT_DIR_', realpath(__DIR__ . '/../../'));
define('_PS_ADMIN_DIR_', _PS_ROOT_DIR_ . '/admin-dev');

// Include PrestaShop configuration
include_once(_PS_ROOT_DIR_ . '/config/config.inc.php');

// Check if the user has permission
if (!isset($cookie) || !$cookie->id_employee || !Employee::existsInDatabase($cookie->id_employee, $cookie->passwd)) {
    die('You must be logged in to the PrestaShop admin to run this script.');
}

// Clear cache
try {
    // Clear Smarty cache
    Tools::clearSmartyCache();
    
    // Clear Symfony cache
    if (file_exists(_PS_ROOT_DIR_ . '/var/cache')) {
        Tools::deleteDirectory(_PS_ROOT_DIR_ . '/var/cache', false);
    }
    
    // Clear media cache
    if (file_exists(_PS_ROOT_DIR_ . '/var/cache/prod/themes')) {
        Tools::deleteDirectory(_PS_ROOT_DIR_ . '/var/cache/prod/themes', false);
    }
    
    // Clear opcache if enabled
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    echo '<div class="alert alert-success">Cache cleared successfully!</div>';
    echo '<p>You can now access the module at: <a href="' . Context::getContext()->link->getAdminLink('AdminPriceMatcher') . '">' . 
         Context::getContext()->link->getAdminLink('AdminPriceMatcher') . '</a></p>';
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error clearing cache: ' . $e->getMessage() . '</div>';
    echo '<p>Try clearing the cache manually from the PrestaShop admin panel.</p>';
}
