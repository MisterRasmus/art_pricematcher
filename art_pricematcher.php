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

// Autoload för modulklasser
class art_pricematcher extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'art_pricematcher';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Rasmus Lejonfelt';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ART PriceMatcher');
        $this->description = $this->l('Collect and manage pricematching data from competitors');

        $this->ps_versions_compliancy = array('min' => '8.0', 'max' => '8.99');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->registerAutoloader();
    }

    private function registerAutoloader()
    {
        spl_autoload_register(function ($className) {
            // Only handle classes in our namespace
            if (strpos($className, 'ArtPriceMatcher\\') === 0) {
                // Convert namespace to path
                $classPath = str_replace('ArtPriceMatcher\\', '', $className);
                $classPath = str_replace('\\', '/', $classPath);
                $filePath = dirname(__FILE__) . '/classes/' . $classPath . '.php';
                
                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }
            return false;
        });
    }

    public function install()
    {
        // Inkludera SQL-installationsfil
        include(dirname(__FILE__) . '/sql/install.php');

        // Utför SQL-frågor
        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        // Generera en säker slumpmässig token för cron-jobb
        $cronToken = Tools::passwdGen(32);

        // Uppdatera token i databasen
        $db = Db::getInstance();
        $db->update(
            'art_pricematcher_config',
            ['value' => $cronToken],
            "name = 'cron_token'"
        );

        // Skapa nödvändiga mappar
        $this->createModuleDirectories();

        // Registrera modulens tabbar i admin-menyn
        $this->installTabs();

        return parent::install()
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('displayBackOfficeHeader');
    }

    /**
     * Avinstallera modulen
     * @return bool
     */
    public function uninstall()
    {
        // Inkludera SQL-avinstallationsfil
        include(dirname(__FILE__) . '/sql/uninstall.php');

        // Utför SQL-frågor
        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        // Ta bort modulens tabbar
        $this->uninstallTabs();

        return parent::uninstall();
    }

    /**
     * Registrera modulens tabbar i admin-menyn
     */
    private function installTabs()
    {
        // Installera huvudtabben
        $mainTab = $this->installTab('AdminPriceMatcher', 'ART PriceMatcher', 'SELL', 'money');

        if ($mainTab) {
            // Vi behöver inte fler tabbar eftersom vår huvudkontroller nu hanterar alla vyer
            // genom att använda "tab"-parametern i URL:en
        }

        return $mainTab;
    }

    /**
     * Avinstallera modulens tabbar
     */
    private function uninstallTabs()
    {
        $tabs = [
            'AdminPriceMatcher'
        ];

        foreach ($tabs as $class_name) {
            $id_tab = (int)Tab::getIdFromClassName($class_name);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }

        return true;
    }

    /**
     * Installera admin-tabben
     * @return bool
     */
    protected function installTab($class_name, $name, $parent_class_name, $icon)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $class_name;
        $tab->name = array();

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $name;
        }

        $tab->id_parent = (int)Tab::getIdFromClassName($parent_class_name);
        $tab->module = $this->name;
        $tab->icon = $icon;

        return $tab->add();
    }

    /**
     * Skapa nödvändiga mappar för modulen
     */
    private function createModuleDirectories()
    {
        $directories = [
            'classes/competitors',
            'logs',
            'price_files/competitors_files',
            'price_files/uploads'
        ];

        foreach ($directories as $dir) {
            $path = _PS_MODULE_DIR_ . $this->name . '/' . $dir;
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
        }
    }

    /**
     * Hook för att ladda admin CSS och JS
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        $controller = Context::getContext()->controller;

        if ($controller->controller_name == 'AdminPriceMatcher') {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        }
    }

    /**
     * Hook för att ladda CSS och JS i admin-headern
     */
    public function hookDisplayBackOfficeHeader()
    {
        $controller = Context::getContext()->controller;

        if ($controller->controller_name == 'AdminPriceMatcher') {
            // Lägg till Bootstrap-stöd om det behövs
            $this->context->controller->addJquery();

            // Lägg till eventuella ytterligare CSS/JS för specifika flikar
            $tab = Tools::getValue('tab', 'dashboard');

            switch ($tab) {
                case 'statistics':
                    // Lägg till JS för statistikdiagram
                    $this->context->controller->addJS($this->_path . 'views/js/chart.min.js');
                    $this->context->controller->addJS($this->_path . 'views/js/statistics.js');
                    break;
                case 'active_discounts':
                    // Lägg till JS för aktiva rabatter
                    $this->context->controller->addJS($this->_path . 'views/js/active_discounts.js');
                    break;
                    // Lägg till fler case för andra flikar vid behov
            }
        }
    }

    /**
     * Omdirigera till AdminPriceMatcher när modulen öppnas från modulmenyn
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminPriceMatcher', true));
    }
}
