<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace ArtPriceMatcher\Tabs;

use Context;
use Db;
use Tools;
use Configuration;
use PrestaShopException;

/**
 * Competitors-klass för PriceMatcher-modulen
 * Hanterar visning och hantering av konkurrenter
 */
class Competitors
{
    /** @var \Module */
    private $module;
    
    /** @var \Context */
    private $context;
    
    /** @var array */
    private $errors = [];
    
    /** @var array */
    private $confirmations = [];
    
    /** @var string */
    private $competitor_class_template = '<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace ArtPriceMatcher\Competitors;

use ArtPriceMatcher\Competitors\CompetitorBase;

/**
 * %s-klass för PriceMatcher-modulen
 * Hanterar nedladdning och standardisering av prisfiler för %s
 */
class %s extends CompetitorBase
{
    /**
     * Standardisera konkurrenspriser och spara dem i en CSV-fil
     * 
     * @return string Sökväg till standardiserad prisfil
     */
    public function standardize()
    {
        // Implementera logik för att ladda ner och standardisera prislistor
        // ...
        
        // För nu, skapa en tom fil som exempel
        $outputFile = _PS_MODULE_DIR_ . \'art_pricematcher/price_files/competitors_files/%s_prices.csv\';
        
        $headers = [
            \'sku\',
            \'ean\',
            \'competitor_price\',
            \'url\'
        ];
        
        // Skapa filen med headers
        $fp = fopen($outputFile, \'w\');
        fputcsv($fp, $headers);
        fclose($fp);
        
        return $outputFile;
    }
    
    /**
     * Ladda ner prisuppgifter från konkurrentens webbplats/API
     * 
     * @return mixed Rådata från konkurrenten
     */
    public function downloadPriceFile()
    {
        // Implementera nedladdningslogik
        // ...
        
        return null;
    }
}
';
    
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
     * Huvudmetod för att rendera konkurrentinnehåll
     * 
     * @return string HTML-innehåll för konkurrenthantering
     */
    public function render()
    {
        // Process any form submissions before rendering
        $this->processSubmissions();
        
        // Prepare data for template
        $competitors = $this->getCompetitors();
        $globalSettings = $this->getGlobalSettings();
        
        $templateData = [
            'form_action' => $this->context->link->getAdminLink('AdminPriceMatcher') . '&tab=competitors',
            'competitors' => $competitors,
            'settings' => [
                'global' => $globalSettings
            ]
        ];
        
        // Check for errors
        if (count($this->errors) > 0) {
            $templateData['errors'] = $this->errors;
        }
        
        // Check for confirmations
        if (count($this->confirmations) > 0) {
            $templateData['confirmations'] = $this->confirmations;
        }
        
        // Assign data to template
        $this->context->smarty->assign($templateData);
        
        // Render the template
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/competitors.tpl');
    }
    
    /**
     * Hämta alla konkurrenter
     * 
     * @return array Lista över konkurrenter
     */
    private function getCompetitors()
    {
        $competitors = [];
        
        $result = Db::getInstance()->executeS('
            SELECT * FROM `' . _DB_PREFIX_ . 'art_pricematcher_competitors`
            ORDER BY `name` ASC
        ');
        
        if ($result && is_array($result)) {
            $competitors = $result;
        }
        
        return $competitors;
    }
    
    /**
     * Hämta globala inställningar för prismatching
     * 
     * @return array Globala inställningar
     */
    private function getGlobalSettings()
    {
        $settings = [];
        
        $result = Db::getInstance()->executeS('
            SELECT * FROM `' . _DB_PREFIX_ . 'art_pricematcher_config`
            WHERE `name` LIKE "global_%" OR `name` IN ("min_margin_percent", "max_discount_percent", "price_underbid", "min_price_threshold", "discount_validity_days")
        ');
        
        if ($result && is_array($result)) {
            foreach ($result as $row) {
                $name = str_replace('global_', '', $row['name']);
                $settings[$name] = $row['value'];
            }
        }
        
        return $settings;
    }
    
    /**
     * Bearbeta formulärisändningar
     */
    private function processSubmissions()
    {
        // Hantera tillägg av ny konkurrent
        if (Tools::isSubmit('submitAddCompetitor')) {
            $this->processAddCompetitor();
        }
        
        // Hantera uppdatering av konkurrentinställningar
        if (Tools::isSubmit('submitCompetitorSettings')) {
            $this->processCompetitorSettings();
        }
    }
    
    /**
     * Bearbeta tillägg av ny konkurrent
     */
    private function processAddCompetitor()
    {
        $name = Tools::getValue('competitor_name');
        $url = Tools::getValue('competitor_url');
        $cronDownload = (int)Tools::getValue('competitor_cron_download', 0);
        $cronCompare = (int)Tools::getValue('competitor_cron_compare', 0);
        $cronUpdate = (int)Tools::getValue('competitor_cron_update', 0);
        
        // Validera konkurrentnamn
        if (empty($name)) {
            $this->errors[] = $this->module->l('Competitor name is required', 'Competitors');
            return;
        }
        
        // Kontrollera att namnet är alfanumeriskt och giltigt för en PHP-klass
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            $this->errors[] = $this->module->l('Competitor name must contain only letters, numbers, and underscores', 'Competitors');
            return;
        }
        
        // Kontrollera att konkurrenten inte redan finns
        $existingId = Db::getInstance()->getValue('
            SELECT id_competitor FROM `' . _DB_PREFIX_ . 'art_pricematcher_competitors`
            WHERE `name` = "' . pSQL($name) . '"
        ');
        
        if ($existingId) {
            $this->errors[] = $this->module->l('A competitor with this name already exists', 'Competitors');
            return;
        }
        
        // Lägg till konkurrenten i databasen
        $result = Db::getInstance()->insert('art_pricematcher_competitors', [
            'name' => pSQL($name),
            'url' => pSQL($url),
            'active' => 1,
            'cron_download' => $cronDownload,
            'cron_compare' => $cronCompare,
            'cron_update' => $cronUpdate,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ]);
        
        if (!$result) {
            $this->errors[] = $this->module->l('Failed to add competitor to database', 'Competitors');
            return;
        }
        
        // Skapa konkurrentklass-fil
        $this->createCompetitorClass($name);
        
        $this->confirmations[] = $this->module->l('Competitor added successfully', 'Competitors');
    }
    
    /**
     * Skapa konkurrentklassfil
     * 
     * @param string $name Konkurrentnamn
     * @return bool True om filen skapades framgångsrikt
     */
    private function createCompetitorClass($name)
    {
        // Skapa klassfilsökväg
        $classPath = _PS_MODULE_DIR_ . 'art_pricematcher/classes/competitors/' . $name . '.php';
        
        // Generera klassinnehåll
        $classContent = sprintf($this->competitor_class_template, $name, $name, $name, strtolower($name));
        
        // Skapa filen
        $result = file_put_contents($classPath, $classContent);
        
        if (!$result) {
            $this->errors[] = $this->module->l('Failed to create competitor class file', 'Competitors');
            return false;
        }
        
        return true;
    }
    
    /**
     * Bearbeta konkurrentinställningar
     */
    private function processCompetitorSettings()
    {
        $id_competitor = (int)Tools::getValue('id_competitor');
        
        if (!$id_competitor) {
            $this->errors[] = $this->module->l('Invalid competitor ID', 'Competitors');
            return;
        }
        
        // Hämta konkurrentdata från formuläret
        $competitorData = Tools::getValue('competitor');
        
        if (!isset($competitorData[$id_competitor])) {
            $this->errors[] = $this->module->l('No data provided for competitor', 'Competitors');
            return;
        }
        
        $data = $competitorData[$id_competitor];
        
        // Förbereda uppdateringsfält
        $updateFields = [
            'url' => pSQL($data['url']),
            'active' => isset($data['active']) ? 1 : 0,
            'cron_download' => isset($data['cron_download']) ? 1 : 0,
            'cron_compare' => isset($data['cron_compare']) ? 1 : 0,
            'cron_update' => isset($data['cron_update']) ? 1 : 0,
            'override_discount_settings' => isset($data['override_discount_settings']) ? 1 : 0,
            'date_upd' => date('Y-m-d H:i:s')
        ];
        
        // Lägg till rabattinställningar om de är aktiverade
        if (isset($data['override_discount_settings']) && $data['override_discount_settings']) {
            $updateFields['discount_strategy'] = pSQL($data['discount_strategy']);
            $updateFields['min_margin_percent'] = (float)$data['min_margin_percent'];
            $updateFields['max_discount_percent'] = (float)$data['max_discount_percent'];
            $updateFields['price_underbid'] = (float)$data['price_underbid'];
            $updateFields['min_price_threshold'] = (float)$data['min_price_threshold'];
            $updateFields['discount_validity_days'] = (int)$data['discount_validity_days'];
            $updateFields['clean_expired_discounts'] = isset($data['clean_expired_discounts']) ? 1 : 0;
        }
        
        // Uppdatera konkurrenten i databasen
        $result = Db::getInstance()->update(
            'art_pricematcher_competitors',
            $updateFields,
            'id_competitor = ' . $id_competitor
        );
        
        if (!$result) {
            $this->errors[] = $this->module->l('Failed to update competitor settings', 'Competitors');
            return;
        }
        
        $this->confirmations[] = $this->module->l('Competitor settings updated successfully', 'Competitors');
    }
    
    /**
     * Hantera AJAX-anrop för konkurrenter
     * 
     * @param string $action Åtgärden som ska utföras
     * @return array Resultat som ska returneras som JSON
     */
    public function handleAjax($action)
    {
        $result = [
            'success' => false,
            'message' => $this->module->l('Invalid action', 'Competitors')
        ];
        
        switch ($action) {
            case 'toggleCompetitor':
                $id_competitor = (int)Tools::getValue('id_competitor');
                $status = (int)Tools::getValue('status');
                
                if ($id_competitor > 0) {
                    $toggleResult = $this->toggleCompetitor($id_competitor, $status);
                    
                    if ($toggleResult === true) {
                        $result = [
                            'success' => true,
                            'message' => $this->module->l('Competitor status updated successfully', 'Competitors')
                        ];
                    } else {
                        $result = [
                            'success' => false,
                            'message' => $toggleResult
                        ];
                    }
                }
                break;
                
            case 'deleteCompetitor':
                $id_competitor = (int)Tools::getValue('id_competitor');
                
                if ($id_competitor > 0) {
                    $deleteResult = $this->deleteCompetitor($id_competitor);
                    
                    if ($deleteResult === true) {
                        $result = [
                            'success' => true,
                            'message' => $this->module->l('Competitor deleted successfully', 'Competitors')
                        ];
                    } else {
                        $result = [
                            'success' => false,
                            'message' => $deleteResult
                        ];
                    }
                }
                break;
        }
        
        return $result;
    }
    
    /**
     * Växla konkurrentstatus (aktivera/inaktivera)
     * 
     * @param int $id_competitor Konkurrent-ID
     * @param int $status Ny status (0 eller 1)
     * @return bool|string true om lyckad, felmeddelande annars
     */
    private function toggleCompetitor($id_competitor, $status)
    {
        try {
            $result = Db::getInstance()->update(
                'art_pricematcher_competitors',
                [
                    'active' => (int)$status,
                    'date_upd' => date('Y-m-d H:i:s')
                ],
                'id_competitor = ' . (int)$id_competitor
            );
            
            if (!$result) {
                return $this->module->l('Failed to update competitor status', 'Competitors');
            }
            
            return true;
            
        } catch (PrestaShopException $e) {
            return $e->getMessage();
        }
    }
    
    /**
     * Ta bort konkurrent
     * 
     * @param int $id_competitor Konkurrent-ID
     * @return bool|string true om lyckad, felmeddelande annars
     */
    private function deleteCompetitor($id_competitor)
    {
        try {
            // Hämta konkurrentinformation först för att få namn
            $competitor = Db::getInstance()->getRow('
                SELECT * FROM `' . _DB_PREFIX_ . 'art_pricematcher_competitors` 
                WHERE `id_competitor` = ' . (int)$id_competitor
            );
            
            if (!$competitor) {
                return $this->module->l('Competitor not found', 'Competitors');
            }
            
            // Ta bort aktiva rabatter för denna konkurrent
            Db::getInstance()->execute('
                DELETE FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` 
                WHERE `id_competitor` = ' . (int)$id_competitor
            );
            
            // Ta bort konkurrenten från databasen
            Db::getInstance()->delete(
                'art_pricematcher_competitors',
                'id_competitor = ' . (int)$id_competitor
            );
            
            // Försök att ta bort klassfilen om den finns
            $classPath = _PS_MODULE_DIR_ . 'art_pricematcher/classes/competitors/' . $competitor['name'] . '.php';
            if (file_exists($classPath)) {
                @unlink($classPath);
            }
            
            return true;
            
        } catch (PrestaShopException $e) {
            return $e->getMessage();
        }
    }
    
    /**
     * Bearbeta formulärisändningar för konkurrentfliken
     * 
     * @return bool|array true om lyckad, array med fel annars
     */
    public function processForm()
    {
        $errors = [];
        
        // Hantera tillägg av ny konkurrent
        if (Tools::isSubmit('submitAddCompetitor')) {
            $this->processAddCompetitor();
            if (count($this->errors) > 0) {
                $errors = $this->errors;
            }
        }
        
        // Hantera uppdatering av konkurrentinställningar
        if (Tools::isSubmit('submitCompetitorSettings')) {
            $this->processCompetitorSettings();
            if (count($this->errors) > 0) {
                $errors = $this->errors;
            }
        }
        
        if (count($errors) > 0) {
            return $errors;
        }
        
        return true;
    }
}