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
        $this->module = Module::getInstanceByName('art_pricematcher');
        
        parent::__construct();
        
        // Inaktivera standardkontrollerfunktioner eftersom vi hanterar allt manuellt
        $this->list_no_link = true;
        $this->allow_export = false;
        $this->can_import = false;
        $this->list_skip_actions = true;
    }

    /**
     * Initiera innehållet för kontrollern
     * Denna metod agerar som en router för olika tabbar
     */
    public function initContent()
    {
        parent::initContent();
        
        // Hämta aktuell tabb från URL-parametern (standard är dashboard)
        $tab = Tools::getValue('tab', 'dashboard');
        
        // Validera tab-parametern för att förhindra säkerhetsproblem
        $validTabs = ['dashboard', 'competitors', 'statistics', 'active_discounts', 'settings'];
        if (!in_array($tab, $validTabs)) {
            $tab = 'dashboard';
        }
        
        // Bestäm vilken tabbklass som ska användas
        $className = 'ArtPriceMatcher\\Tabs\\' . ucfirst($tab);
        
        // Kontrollera att klassen existerar innan vi försöker instantiera den
        if (!class_exists($className)) {
            $this->errors[] = $this->l('Could not load tab: ') . $tab;
            $className = 'ArtPriceMatcher\\Tabs\\Dashboard';
        }
        
        try {
            // Skapa en instans av tabbklassen och rendera den
            $tabHandler = new $className($this->module, $this->context);
            $tabContent = $tabHandler->render();
            
            // Skapa bredsmenyinnehåll
            $menuContent = $this->renderMenu($tab);
            
            // Kombinera menyn och tabbinnehållet till det slutliga innehållet
            $this->context->smarty->assign([
                'content' => $tabContent,
                'menu' => $menuContent,
                'current_tab' => $tab,
                'module_version' => $this->module->version,
                'ps_version' => _PS_VERSION_,
            ]);
            
            // Rendera huvudmallen med både meny och innehåll
            $this->content = $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/layout.tpl');
        } catch (Exception $e) {
            // Hantera eventuella fel
            $this->errors[] = $this->l('Error loading tab: ') . $e->getMessage();
            $this->content = $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/error.tpl');
        }
    }
    
    /**
     * Rendera sidomenyn
     * 
     * @param string $currentTab Aktuell tabb som visas
     * @return string HTML för sidomenyn
     */
    private function renderMenu($currentTab)
    {
        // Definiera alla tillgängliga tabbar med deras ikoner och namn
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
        
        // Förbered länkarna för varje tabb
        $menuItems = [];
        foreach ($tabs as $tabId => $tabInfo) {
            $menuItems[] = [
                'id' => $tabId,
                'name' => $tabInfo['name'],
                'icon' => $tabInfo['icon'],
                'url' => $this->context->link->getAdminLink('AdminPriceMatcherController', true, [], ['tab' => $tabId]),
                'active' => ($tabId == $currentTab)
            ];
        }
        
        // Tilldela menydata till Smarty
        $this->context->smarty->assign([
            'menu_items' => $menuItems
        ]);
        
        // Rendera och returnera menyn
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/menu.tpl');
    }
    
    /**
     * Hantera AJAX-förfrågningar
     * Denna metod delegerar AJAX-anrop till rätt tabbklass
     */
    public function ajaxProcessRequest()
    {
        // Kontrollera CSRF-token för säkerhet
        if (!$this->isTokenValid()) {
            die(json_encode([
                'success' => false,
                'message' => $this->l('Invalid security token')
            ]));
        }
        
        // Hämta aktuell tabb och åtgärd
        $tab = Tools::getValue('tab');
        $action = Tools::getValue('action');
        
        // Validera tab-parametern
        $validTabs = ['dashboard', 'competitors', 'statistics', 'active_discounts', 'settings'];
        if (!in_array($tab, $validTabs)) {
            die(json_encode([
                'success' => false,
                'message' => $this->l('Invalid tab')
            ]));
        }
        
        // Bestäm vilken tabbklass som ska hantera AJAX-förfrågan
        $className = 'ArtPriceMatcher\\Tabs\\' . ucfirst($tab);
        
        // Kontrollera att klassen existerar innan vi försöker instantiera den
        if (!class_exists($className)) {
            die(json_encode([
                'success' => false,
                'message' => $this->l('Could not load tab class: ') . $tab
            ]));
        }
        
        try {
            // Skapa en instans av tabbklassen och anropa AJAX-metoden
            $tabHandler = new $className($this->module, $this->context);
            
            // Kontrollera att tabbklassen har en handleAjax-metod
            if (!method_exists($tabHandler, 'handleAjax')) {
                die(json_encode([
                    'success' => false,
                    'message' => $this->l('AJAX handling not implemented for this tab')
                ]));
            }
            
            // Anropa tabbklassens AJAX-hanterare
            $result = $tabHandler->handleAjax($action);
            die(json_encode($result));
            
        } catch (Exception $e) {
            // Hantera eventuella fel
            die(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
        }
    }
    
    /**
     * Kontrollera om en POST-förfrågan har skickats
     * 
     * @return bool
     */
    public function postProcess()
    {
        // Kontrollera om ett formulär har skickats
        if (Tools::isSubmit('submitPriceMatcher')) {
            $tab = Tools::getValue('tab', 'dashboard');
            
            // Delegera formulärhantering till rätt tabbklass
            $className = 'ArtPriceMatcher\\Tabs\\' . ucfirst($tab);
            
            if (class_exists($className)) {
                try {
                    $tabHandler = new $className($this->module, $this->context);
                    
                    // Kontrollera att tabbklassen har en processForm-metod
                    if (method_exists($tabHandler, 'processForm')) {
                        $result = $tabHandler->processForm();
                        
                        if ($result === true) {
                            $this->confirmations[] = $this->l('Settings updated successfully.');
                        } else {
                            if (is_array($result)) {
                                foreach ($result as $error) {
                                    $this->errors[] = $error;
                                }
                            } else {
                                $this->errors[] = $this->l('An error occurred while saving settings.');
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                }
            }
        }
        
        return parent::postProcess();
    }
}