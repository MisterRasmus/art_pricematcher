<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminPriceMatcherController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        $this->meta_title = 'ART PriceMatcher';
        
        parent::__construct();
        
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules'));
        }
    }
    
    public function initContent()
    {
        parent::initContent();
        
        // Get current tab from URL parameter (default is dashboard)
        $tab = Tools::getValue('tab', 'dashboard');
        
        // Validate tab parameter
        $validTabs = ['dashboard', 'competitors', 'statistics', 'active_discounts', 'settings'];
        if (!in_array($tab, $validTabs)) {
            $tab = 'dashboard';
        }
        
        // Include tab class file directly
        $tabClassName = ucfirst($tab);
        $tabFile = _PS_MODULE_DIR_ . 'art_pricematcher/classes/tabs/' . $tabClassName . '.php';
        
        if (!file_exists($tabFile)) {
            $this->errors[] = $this->l('Could not load tab file: ') . $tabFile;
            $tabFile = _PS_MODULE_DIR_ . 'art_pricematcher/classes/tabs/Dashboard.php';
            $tabClassName = 'Dashboard';
        }
        
        try {
            // Include file directly
            require_once($tabFile);
            
            // Create fully qualified class name
            $fullClassName = 'ArtPriceMatcher\\Tabs\\' . $tabClassName;
            
            // Create tab instance and render content
            $tabHandler = new $fullClassName($this->module, $this->context);
            $tabContent = $tabHandler->render();
            
            // Define all available tabs with their icons and names
            $tabs = [
                'dashboard' => [
                    'name' => $this->l('Dashboard'),
                    'icon' => 'dashboard'
                ],
                'competitors' => [
                    'name' => $this->l('Competitors'),
                    'icon' => 'business'
                ],
                'statistics' => [
                    'name' => $this->l('Statistics'),
                    'icon' => 'assessment'
                ],
                'active_discounts' => [
                    'name' => $this->l('Active Discounts'),
                    'icon' => 'local_offer'
                ],
                'settings' => [
                    'name' => $this->l('Settings'),
                    'icon' => 'settings'
                ]
            ];
            
            // Get admin token
            $adminToken = Tools::getAdminTokenLite('AdminPriceMatcher');
            
            // Prepare menu items with manually constructed URLs
            $menuItems = [];
            foreach ($tabs as $tabId => $tabInfo) {
                // Create URL manually
                $url = _PS_BASE_URL_ . __PS_BASE_URI__ . 'admin-ljustema/index.php?controller=AdminPriceMatcher&tab=' . $tabId . '&token=' . $adminToken;
                
                $menuItems[] = [
                    'id' => $tabId,
                    'name' => $tabInfo['name'],
                    'icon' => $tabInfo['icon'],
                    'url' => $url,
                    'active' => ($tabId == $tab)
                ];
            }
            
            // Assign menu items and content to Smarty
            $this->context->smarty->assign([
                'menu_items' => $menuItems,
                'content' => $tabContent,
                'current_tab' => $tab,
                'module_version' => $this->module->version,
                'ps_version' => _PS_VERSION_,
                'errors' => $this->errors,
                'debug_menu' => true,  // Add debug flag
                'debug_menu_items' => print_r($menuItems, true),  // Add debug info
            ]);
            
            // Set template to use
            $this->content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'art_pricematcher/views/templates/admin/layout.tpl');
        } catch (Exception $e) {
            // Handle any errors
            $this->errors[] = $this->l('Error loading tab: ') . $e->getMessage();
            $this->context->smarty->assign([
                'errors' => $this->errors
            ]);
            $this->content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'art_pricematcher/views/templates/admin/error.tpl');
        }
    }
    
    public function renderView()
    {
        return $this->content;
    }
    
    public function ajaxProcessRequest()
    {
        // Check CSRF token
        if (!$this->isTokenValid()) {
            die(json_encode([
                'success' => false,
                'message' => $this->l('Invalid token')
            ]));
        }
        
        // Get current tab and action
        $tab = Tools::getValue('tab', 'dashboard');
        $action = Tools::getValue('action', '');
        
        // Validate tab parameter
        $validTabs = ['dashboard', 'competitors', 'statistics', 'active_discounts', 'settings'];
        if (!in_array($tab, $validTabs)) {
            die(json_encode([
                'success' => false,
                'message' => $this->l('Invalid tab')
            ]));
        }
        
        // Include tab class file directly
        $tabClassName = ucfirst($tab);
        $tabFile = _PS_MODULE_DIR_ . 'art_pricematcher/classes/tabs/' . $tabClassName . '.php';
        
        if (!file_exists($tabFile)) {
            die(json_encode([
                'success' => false,
                'message' => $this->l('Could not load tab file: ') . $tabFile
            ]));
        }
        
        try {
            // Include file directly
            require_once($tabFile);
            
            // Create fully qualified class name
            $fullClassName = 'ArtPriceMatcher\\Tabs\\' . $tabClassName;
            
            // Create tab instance and handle AJAX request
            $tabHandler = new $fullClassName($this->module, $this->context);
            $result = $tabHandler->handleAjax($action);
            
            die(json_encode($result));
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'message' => $this->l('Error: ') . $e->getMessage()
            ]));
        }
    }
}