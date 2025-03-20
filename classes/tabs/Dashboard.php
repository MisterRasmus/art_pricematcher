<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace ArtPriceMatcher\Tabs;

use Configuration;
use Context;
use Db;
use Module;
use Tools;

/**
 * Dashboard-klass för PriceMatcher-modulen
 * Hanterar visning av dashboard och olika åtgärder som kan utföras från den
 */
class Dashboard
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
     * Huvudmetod för att rendera dashboardinnehåll
     * 
     * @return string HTML-innehåll för dashboarden
     */
    public function render()
    {
        // Process any actions first
        $this->processActions();
        
        // Prepare template data
        $templateData = [
            'form_action' => $this->context->link->getAdminLink('AdminPriceMatcher') . '&tab=dashboard',
            'competitors' => $this->getCompetitors(),
            'statistics' => $this->getStatistics(),
            'cron_token' => $this->getCronToken(),
            'cron_url' => $this->getShopUrl() . 'modules/art_pricematcher/cronjob.php?token=' . $this->getCronToken(),
            'module_dir' => _PS_MODULE_DIR_ . 'art_pricematcher/',
        ];
        
        // Add results arrays if they exist
        $resultTypes = ['download_results', 'compare_results', 'update_results', 'clean_expired_discounts_results'];
        foreach ($resultTypes as $type) {
            if ($result = $this->context->cookie->{$type}) {
                $templateData[$type] = json_decode($result, true);
                // Clear the cookie after reading
                $this->context->cookie->{$type} = null;
                $this->context->cookie->write();
            }
        }
        
        // Assign template variables
        $this->context->smarty->assign($templateData);
        
        // Render the template
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/dashboard.tpl');
    }
    
    /**
     * Hämta konkurrenter från databasen
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
     * Hämta statistik för dashboarden
     * 
     * @return array Statistikdata
     */
    private function getStatistics()
    {
        $statistics = [];
        
        // Hämta totalt antal produkter
        $statistics['total_products'] = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product`'
        );
        
        // Hämta antal jämförda produkter
        $statistics['total_compared'] = Db::getInstance()->getValue(
            'SELECT COUNT(DISTINCT id_product) FROM `' . _DB_PREFIX_ . 'art_pricematcher`'
        );
        
        // Hämta antal uppdaterade produkter
        $statistics['total_updated'] = Db::getInstance()->getValue(
            'SELECT COUNT(DISTINCT id_product) FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
            WHERE new_price IS NOT NULL AND new_price > 0'
        );
        
        // Hämta antal aktiva rabatter
        $statistics['active_discounts'] = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts`'
        );
        
        return $statistics;
    }
    
    /**
     * Hämta cron-token från databasen
     * 
     * @return string Cron-token eller en tom sträng om den inte finns
     */
    private function getCronToken()
    {
        $token = Db::getInstance()->getValue(
            'SELECT `value` FROM `' . _DB_PREFIX_ . 'art_pricematcher_config` 
            WHERE `name` = "cron_token"'
        );
        
        return $token ?: '';
    }
    
    /**
     * Hämta shop-URL för att bygga fullständig cron-URL
     * 
     * @return string Shop-URL
     */
    private function getShopUrl()
    {
        $ssl = Configuration::get('PS_SSL_ENABLED');
        $shop_url = Tools::getShopDomainSsl($ssl) . __PS_BASE_URI__;
        
        return $shop_url;
    }
    
    /**
     * Bearbeta åtgärder från formuläret
     */
    private function processActions()
    {
        // Kontrollera om ett formulär har skickats
        if (Tools::isSubmit('submitPriceMatcher')) {
            $action = Tools::getValue('action');
            $competitor = Tools::getValue('competitor');
            
            // Validera konkurrent om den inte är tom (för certain åtgärder)
            if ($action != 'clean_expired' && empty($competitor)) {
                $this->errors[] = $this->module->l('Please select a competitor', 'Dashboard');
                return;
            }
            
            // Utför vald åtgärd baserat på form-värdet
            switch ($action) {
                case 'download':
                    $this->downloadPrices($competitor);
                    break;
                    
                case 'compare':
                    $this->comparePrices($competitor);
                    break;
                    
                case 'update':
                    $this->updatePrices($competitor);
                    break;
                    
                case 'clean_expired':
                    $this->cleanExpiredDiscounts();
                    break;
            }
        }
    }
    
    /**
     * Ladda ner priser för en konkurrent
     * 
     * @param string $competitor Konkurrentens namn
     */
    private function downloadPrices($competitor)
    {
        // Hämta konkurrentklass och anropa standardize-metoden
        $results = [];
        
        try {
            // Skapa sökvägen till konkurrentklassen
            $className = 'ArtPriceMatcher\\Competitors\\' . $competitor;
            
            // Kontrollera att klassen existerar
            if (!class_exists($className)) {
                throw new \Exception('Competitor class not found: ' . $className);
            }
            
            // Skapa en instans av konkurrentklassen
            $competitorObj = new $className();
            
            // Mät tiden det tar att ladda ner
            $startTime = microtime(true);
            
            // Anropa standardize-metoden för att ladda ner och standardisera prislistan
            $filePath = $competitorObj->standardize();
            
            // Beräkna exekveringstiden
            $executionTime = microtime(true) - $startTime;
            
            // Räkna antal produkter i filen
            $productsFound = 0;
            if (file_exists($filePath)) {
                $handle = fopen($filePath, 'r');
                if ($handle) {
                    while (($line = fgets($handle)) !== false) {
                        $productsFound++;
                    }
                    // Ta bort header-raden från räkningen
                    $productsFound--;
                    fclose($handle);
                }
            }
            
            // Förbered resultat
            $results = [
                'success' => true,
                'competitor' => $competitor,
                'file' => $filePath,
                'products_found' => $productsFound,
                'execution_time' => $executionTime
            ];
            
        } catch (\Exception $e) {
            // Hantera fel
            $results = [
                'success' => false,
                'competitor' => $competitor,
                'error' => $e->getMessage()
            ];
        }
        
        // Spara resultatet i en cookie för att visa det efter omdirigeringen
        $this->context->cookie->download_results = json_encode($results);
        $this->context->cookie->write();
        
        // Omdirigera för att förhindra återsändning av formuläret vid uppdatering
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminPriceMatcher') . '&tab=dashboard');
    }
    
    /**
     * Jämför priser för en konkurrent
     * 
     * @param string $competitor Konkurrentens namn
     */
    private function comparePrices($competitor)
    {
        $results = [];
        
        try {
            // Importera jämförelseklass
            require_once _PS_MODULE_DIR_ . 'art_pricematcher/classes/process/ComparePrices.php';
            
            // Skapa en instans av jämförelseklassen
            $compare = new \ArtPriceMatcher\Process\ComparePrices();
            
            // Mät tiden det tar att jämföra
            $startTime = microtime(true);
            
            // Anropa jämförelsemetoden
            $compareResults = $compare->compareCompetitorPrices($competitor);
            
            // Beräkna exekveringstiden
            $executionTime = microtime(true) - $startTime;
            
            // Förbered resultat
            $results = [
                'success' => true,
                'competitor' => $competitor,
                'products_found' => $compareResults['products_found'],
                'products_matched' => $compareResults['products_matched'],
                'products_lower' => $compareResults['products_lower'],
                'execution_time' => $executionTime
            ];
            
        } catch (\Exception $e) {
            // Hantera fel
            $results = [
                'success' => false,
                'competitor' => $competitor,
                'error' => $e->getMessage()
            ];
        }
        
        // Spara resultatet i en cookie för att visa det efter omdirigeringen
        $this->context->cookie->compare_results = json_encode($results);
        $this->context->cookie->write();
        
        // Omdirigera för att förhindra återsändning av formuläret vid uppdatering
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminPriceMatcher') . '&tab=dashboard');
    }
    
    /**
     * Uppdatera priser för en konkurrent
     * 
     * @param string $competitor Konkurrentens namn
     */
    private function updatePrices($competitor)
    {
        $results = [];
        
        try {
            // Importera uppdateringsklass
            require_once _PS_MODULE_DIR_ . 'art_pricematcher/classes/process/UpdatePrices.php';
            
            // Skapa en instans av uppdateringsklassen
            $update = new \ArtPriceMatcher\Process\UpdatePrices();
            
            // Mät tiden det tar att uppdatera
            $startTime = microtime(true);
            
            // Anropa uppdateringsmetoden
            $updateResults = $update->updatePrices($competitor);
            
            // Beräkna exekveringstiden
            $executionTime = microtime(true) - $startTime;
            
            // Förbered resultat
            $results = [
                'success' => true,
                'competitor' => $competitor,
                'total_checked' => $updateResults['total_checked'],
                'updated_count' => $updateResults['updated_count'],
                'skipped_count' => $updateResults['skipped_count'],
                'execution_time' => $executionTime
            ];
            
        } catch (\Exception $e) {
            // Hantera fel
            $results = [
                'success' => false,
                'competitor' => $competitor,
                'error' => $e->getMessage()
            ];
        }
        
        // Spara resultatet i en cookie för att visa det efter omdirigeringen
        $this->context->cookie->update_results = json_encode($results);
        $this->context->cookie->write();
        
        // Omdirigera för att förhindra återsändning av formuläret vid uppdatering
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminPriceMatcher') . '&tab=dashboard');
    }
    
    /**
     * Rensa utgångna rabatter
     */
    private function cleanExpiredDiscounts()
    {
        $results = [];
        
        try {
            // Importera uppdateringsklass
            require_once _PS_MODULE_DIR_ . 'art_pricematcher/classes/process/UpdatePrices.php';
            
            // Skapa en instans av uppdateringsklassen
            $update = new \ArtPriceMatcher\Process\UpdatePrices();
            
            // Mät tiden det tar att rensa
            $startTime = microtime(true);
            
            // Anropa metoden för att rensa utgångna rabatter
            $removedCount = $update->cleanExpiredDiscounts();
            
            // Beräkna exekveringstiden
            $executionTime = microtime(true) - $startTime;
            
            // Förbered resultat
            $results = [
                'success' => true,
                'removed_count' => $removedCount,
                'execution_time' => $executionTime
            ];
            
        } catch (\Exception $e) {
            // Hantera fel
            $results = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        // Spara resultatet i en cookie för att visa det efter omdirigeringen
        $this->context->cookie->clean_expired_discounts_results = json_encode($results);
        $this->context->cookie->write();
        
        // Omdirigera för att förhindra återsändning av formuläret vid uppdatering
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminPriceMatcher') . '&tab=dashboard');
    }
    
    /**
     * Hantera AJAX-anrop för dashboarden
     * 
     * @param string $action Åtgärden som ska utföras
     * @return array Resultat som ska returneras som JSON
     */
    public function handleAjax($action)
    {
        $result = [
            'success' => false,
            'message' => 'Invalid action'
        ];
        
        switch ($action) {
            case 'getStatistics':
                $result = [
                    'success' => true,
                    'statistics' => $this->getStatistics()
                ];
                break;
                
            case 'getActiveDiscounts':
                $result = [
                    'success' => true,
                    'active_discounts' => $this->getActiveDiscounts()
                ];
                break;
                
            // Fler AJAX-åtgärder kan läggas till här
        }
        
        return $result;
    }
    
    /**
     * Hämta aktiva rabatter
     * 
     * @param int $limit Begränsa antal resultat
     * @return array Lista över aktiva rabatter
     */
    private function getActiveDiscounts($limit = 5)
    {
        $discounts = [];
        
        $result = Db::getInstance()->executeS('
            SELECT ad.*, p.reference, pl.name
            FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` ad
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (ad.id_product = p.id_product)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (ad.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . ')
            ORDER BY ad.date_expiration ASC
            LIMIT ' . (int)$limit
        );
        
        if ($result && is_array($result)) {
            $discounts = $result;
        }
        
        return $discounts;
    }
    
    /**
     * Bearbeta formulärisändringar för dashboarden
     * 
     * @return bool|array true om lyckad, array med fel annars
     */
    public function processForm()
    {
        // Detta hanteras redan i processActions-metoden för Dashboard
        // så vi behöver inte göra något här
        return true;
    }
}    