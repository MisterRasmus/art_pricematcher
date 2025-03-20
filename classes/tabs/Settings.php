<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace ArtPriceMatcher\Tabs;

use Category;
use Configuration;
use Context;
use Db;
use Group;
use Manufacturer;
use Tools;
use Shop;

/**
 * Settings-klass för PriceMatcher-modulen
 * Hanterar globala inställningar för modulen
 */
class Settings
{
    /** @var \Module */
    private $module;
    
    /** @var \Context */
    private $context;
    
    /** @var array */
    private $errors = [];
    
    /** @var array */
    private $confirmations = [];
    
    /**
     * Konstruktor
     * 
     * @param \Module $module Modulinstans
     * @param \Context $context Kontextinstans
     */
    public function __construct($module, $context)
    {
        $this->module = $module;
        $this->context = $context;
    }
    
    /**
     * Huvudmetod för att rendera inställningar
     * 
     * @return string HTML-innehåll för inställningar
     */
    public function render()
    {
        // Hämta alla inställningar
        $settings = $this->getAllSettings();
        
        // Hämta kundgrupper, kategorier och tillverkare
        $customerGroups = $this->getCustomerGroups();
        $categories = $this->getCategories();
        $manufacturers = $this->getManufacturers();
        
        // Generera cron-URL:er
        $cronUrls = $this->generateCronUrls();
        
        // Tilldela till Smarty
        $this->context->smarty->assign([
            'form_action' => $this->context->link->getAdminLink('AdminPriceMatcherController', true, [], ['tab' => 'settings']),
            'settings' => $settings,
            'customer_groups' => $customerGroups,
            'categories' => $categories,
            'manufacturers' => $manufacturers,
            'cron_urls' => $cronUrls
        ]);
        
        // Ladda JavaScript-översättningar
        $this->loadJSTranslations();
        
        // Rendera mallen
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/settings.tpl');
    }
    
    /**
     * Hämta alla inställningar från databasen
     * 
     * @return array Inställningar
     */
    private function getAllSettings()
    {
        $settings = [];
        
        // Hämta alla rader från konfigurationstabellen
        $rows = Db::getInstance()->executeS('
            SELECT * FROM `' . _DB_PREFIX_ . 'art_pricematcher_config`
        ');
        
        if ($rows && is_array($rows)) {
            foreach ($rows as $row) {
                // Hantera multivärden (arrays)
                if (in_array($row['name'], ['discount_customer_groups', 'excluded_categories', 'excluded_manufacturers'])) {
                    $settings[$row['name']] = json_decode($row['value'], true) ?: [];
                } else {
                    $settings[$row['name']] = $row['value'];
                }
            }
        }
        
        return $settings;
    }
    
    /**
     * Hämta alla kundgrupper
     * 
     * @return array Kundgrupper
     */
    private function getCustomerGroups()
    {
        $groups = [];
        $result = Group::getGroups($this->context->language->id);
        
        if ($result && is_array($result)) {
            foreach ($result as $group) {
                $groups[$group['id_group']] = $group['name'];
            }
        }
        
        return $groups;
    }
    
    /**
     * Hämta alla kategorier
     * 
     * @return array Kategorier
     */
    private function getCategories()
    {
        $categories = [];
        $result = Category::getCategories($this->context->language->id, true, false);
        
        if ($result && is_array($result)) {
            foreach ($result as $category) {
                // Skapa en indenterad kategorinamn för att visa hierarkin
                $depth = str_repeat('—', (int)$category['level_depth'] - 1);
                $prefix = $depth ? $depth . ' ' : '';
                $categories[$category['id_category']] = $prefix . $category['name'];
            }
        }
        
        return $categories;
    }
    
    /**
     * Hämta alla tillverkare
     * 
     * @return array Tillverkare
     */
    private function getManufacturers()
    {
        $manufacturers = [];
        $result = Manufacturer::getManufacturers(false, $this->context->language->id, true);
        
        if ($result && is_array($result)) {
            foreach ($result as $manufacturer) {
                $manufacturers[$manufacturer['id_manufacturer']] = $manufacturer['name'];
            }
        }
        
        return $manufacturers;
    }
    
    /**
     * Generera cron-URL:er
     * 
     * @return array Cron-URL:er
     */
    private function generateCronUrls()
    {
        $token = $this->getSetting('cron_token');
        $shopUrl = $this->getShopUrl();
        $cronPath = $shopUrl . 'modules/art_pricematcher/cronjob.php?token=' . $token;
        
        return [
            'download' => $cronPath . '&action=download',
            'compare' => $cronPath . '&action=compare',
            'update' => $cronPath . '&action=update',
            'clean' => $cronPath . '&action=clean_expired'
        ];
    }
    
    /**
     * Hämta en specifik inställning
     * 
     * @param string $name Inställningsnamn
     * @param mixed $default Standardvärde om inställningen inte finns
     * @return mixed Inställningsvärde
     */
    private function getSetting($name, $default = '')
    {
        $value = Db::getInstance()->getValue('
            SELECT `value` FROM `' . _DB_PREFIX_ . 'art_pricematcher_config`
            WHERE `name` = "' . pSQL($name) . '"
        ');
        
        return $value !== false ? $value : $default;
    }
    
    /**
     * Hämta shop-URL för att bygga cron-URL:er
     * 
     * @return string Shop-URL
     */
    private function getShopUrl()
    {
        $ssl = Configuration::get('PS_SSL_ENABLED');
        return Tools::getShopDomainSsl($ssl) . __PS_BASE_URI__;
    }
    
    /**
     * Ladda JavaScript-översättningar
     */
    private function loadJSTranslations()
    {
        Media::addJsDef([
            'pricematcherTranslations' => [
                'selectCategories' => $this->module->l('Select categories to exclude', 'Settings'),
                'selectManufacturers' => $this->module->l('Select manufacturers to exclude', 'Settings'),
                'selectGroups' => $this->module->l('Select customer groups', 'Settings'),
                'tokenGenerated' => $this->module->l('New security token has been generated', 'Settings'),
                'copied' => $this->module->l('Copied to clipboard', 'Settings')
            ]
        ]);
    }
    
    /**
     * Bearbeta formulärisändningar för inställningar
     * 
     * @return bool|array true om lyckad, array med fel annars
     */
    public function processForm()
    {
        if (!Tools::isSubmit('submitSettings')) {
            return true;
        }
        
        $errors = [];
        
        // Hämta formulärvärden
        $settings = [
            // Allmänna inställningar
            'active' => (int)Tools::getValue('active', 0),
            
            // Cron-inställningar
            'cron_token' => pSQL(Tools::getValue('cron_token')),
            
            // Rabattinställningar
            'discount_strategy' => pSQL(Tools::getValue('discount_strategy', 'both')),
            'min_margin_percent' => (float)Tools::getValue('min_margin_percent', 30),
            'max_discount_percent' => (float)Tools::getValue('max_discount_percent', 24),
            'min_discount_percent' => (float)Tools::getValue('min_discount_percent', 5),
            'price_underbid' => (float)Tools::getValue('price_underbid', 5),
            'min_price_threshold' => (float)Tools::getValue('min_price_threshold', 100),
            'discount_days_valid' => (int)Tools::getValue('discount_days_valid', 2),
            'max_discount_behavior' => pSQL(Tools::getValue('max_discount_behavior', 'partial')),
            'clean_expired_discounts' => (int)Tools::getValue('clean_expired_discounts', 0),
            
            // Multivärden (arrays)
            'discount_customer_groups' => Tools::getValue('discount_customer_groups', []),
            'excluded_categories' => Tools::getValue('excluded_categories', []),
            'excluded_manufacturers' => Tools::getValue('excluded_manufacturers', []),
            
            // Undantag
            'min_stock' => (int)Tools::getValue('min_stock', 1),
            'excluded_references' => pSQL(Tools::getValue('excluded_references', '')),
            
            // E-postnotifieringar
            'email_notifications' => (int)Tools::getValue('email_notifications', 0),
            'email_recipients' => pSQL(Tools::getValue('email_recipients', '')),
            'email_frequency' => pSQL(Tools::getValue('email_frequency', 'daily')),
            'notification_threshold' => (float)Tools::getValue('notification_threshold', 15)
        ];
        
        // Validera inställningar
        if (empty($settings['cron_token'])) {
            $errors[] = $this->module->l('Cron token is required for security', 'Settings');
        }
        
        if ($settings['min_margin_percent'] < 0 || $settings['min_margin_percent'] > 100) {
            $errors[] = $this->module->l('Minimum margin must be between 0 and 100', 'Settings');
        }
        
        if ($settings['max_discount_percent'] < 0 || $settings['max_discount_percent'] > 100) {
            $errors[] = $this->module->l('Maximum discount must be between 0 and 100', 'Settings');
        }
        
        if ($settings['min_discount_percent'] < 0 || $settings['min_discount_percent'] > 100) {
            $errors[] = $this->module->l('Minimum discount must be between 0 and 100', 'Settings');
        }
        
        if ($settings['price_underbid'] < 0) {
            $errors[] = $this->module->l('Price underbid cannot be negative', 'Settings');
        }
        
        if ($settings['min_price_threshold'] < 0) {
            $errors[] = $this->module->l('Minimum price threshold cannot be negative', 'Settings');
        }
        
        if ($settings['discount_days_valid'] < 1) {
            $errors[] = $this->module->l('Discount validity must be at least 1 day', 'Settings');
        }
        
        if ($settings['email_notifications'] && empty($settings['email_recipients'])) {
            $errors[] = $this->module->l('Email recipients are required if notifications are enabled', 'Settings');
        }
        
        // Om inga fel, spara inställningarna
        if (empty($errors)) {
            foreach ($settings as $key => $value) {
                // Hantera multivärden (arrays)
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                
                // Kontrollera om inställningen redan finns
                $exists = Db::getInstance()->getValue('
                    SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'art_pricematcher_config`
                    WHERE `name` = "' . pSQL($key) . '"
                ');
                
                if ($exists) {
                    // Uppdatera befintlig inställning
                    Db::getInstance()->update(
                        'art_pricematcher_config',
                        ['value' => pSQL($value, true)],
                        '`name` = "' . pSQL($key) . '"'
                    );
                } else {
                    // Lägg till ny inställning
                    Db::getInstance()->insert(
                        'art_pricematcher_config',
                        [
                            'name' => pSQL($key),
                            'value' => pSQL($value, true)
                        ]
                    );
                }
            }
            
            return true;
        }
        
        return $errors;
    }
    
    /**
     * Hantera AJAX-anrop för inställningar
     * 
     * @param string $action Åtgärden som ska utföras
     * @return array Resultat som ska returneras som JSON
     */
    public function handleAjax($action)
    {
        $result = [
            'success' => false,
            'message' => $this->module->l('Invalid action', 'Settings')
        ];
        
        switch ($action) {
            case 'saveTab':
                $tab = Tools::getValue('tab');
                $this->context->cookie->pricematcher_settings_tab = $tab;
                $this->context->cookie->write();
                
                $result = [
                    'success' => true,
                    'message' => $this->module->l('Tab preference saved', 'Settings')
                ];
                break;
                
            case 'generateToken':
                $token = Tools::passwdGen(32);
                
                // Uppdatera token i databasen
                Db::getInstance()->update(
                    'art_pricematcher_config',
                    ['value' => pSQL($token)],
                    '`name` = "cron_token"'
                );
                
                $result = [
                    'success' => true,
                    'token' => $token,
                    'urls' => $this->generateCronUrls(),
                    'message' => $this->module->l('New token generated', 'Settings')
                ];
                break;
        }
        
        return $result;
    }
}