<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace ArtPriceMatcher\Process;

use Context;
use Db;
use Tools;
use Configuration;
use Product;
use Category;
use Validate;
use Tax;
use DateTime;
use ArtPriceMatcher\Helpers\Logger;
use ArtPriceMatcher\Helpers\Statistics;
use ArtPriceMatcher\Helpers\EmailHandler;

/**
 * Jämför konkurrentpriser med butikens egna och identifierar produkter som kräver prisjusteringar
 */
class ComparePrices
{
    /** @var \Context PrestaShop-kontext */
    private $context;
    
    /** @var \Logger Logghanterare */
    private $logger;
    
    /** @var \Statistics Statistikhjälpare */
    private $statistics;
    
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->context = Context::getContext();
        $this->logger = new Logger('compare');
        $this->statistics = new Statistics();
        
        $this->logger->info("Initialiserar ComparePrices");
    }
    
    /**
     * Jämför priser för en specifik konkurrent
     * 
     * @param int|string|array $competitor Konkurrent ID, namn eller array med konkurrentdata
     * @param string $pricefile Sökväg till prisfil (optional)
     * @return array Resultat av jämförelsen
     */
    public function compareCompetitorPrices($competitor, $pricefile = null)
    {
        $db = Db::getInstance();
        $results = [
            'products_found' => 0,
            'products_matched' => 0,
            'products_lower' => 0,
            'total_products' => 0,
            'execution_time' => 0
        ];
        
        $startTime = microtime(true);
        
        // Om konkurrent är ett ID eller namn, hämta fullständig konkurrentdata
        if (!is_array($competitor)) {
            // Kontrollera om $competitor är ett ID eller ett namn
            if (is_numeric($competitor)) {
                $competitorId = (int)$competitor;
                $competitor = $db->getRow("SELECT * FROM `" . _DB_PREFIX_ . "art_pricematcher_competitors` 
                                          WHERE `id_competitor` = " . $competitorId);
            } else {
                // Antag att det är ett namn
                $competitorName = pSQL($competitor);
                $competitor = $db->getRow("SELECT * FROM `" . _DB_PREFIX_ . "art_pricematcher_competitors` 
                                          WHERE `name` = '" . $competitorName . "'");
            }
            
            if (!$competitor) {
                $this->logger->error("Konkurrent med ID/namn [$competitor] hittades inte");
                return $results;
            }
        }
        
        // Kontrollera om konkurrenten är aktiv
        if (isset($competitor['active']) && !$competitor['active']) {
            $this->logger->info("Konkurrent {$competitor['name']} (ID: {$competitor['id_competitor']}) är inaktiv, hoppar över prisjämförelse");
            return $results;
        }
        
        $this->logger->info("Startar prisjämförelse för konkurrent {$competitor['name']} (ID: {$competitor['id_competitor']})");
        
        // Om ingen prisfil anges, hitta den senaste CSV-filen för denna konkurrent
        if ($pricefile === null) {
            $pricefile = $this->findLatestCsvFile($competitor['name']);
            if (!$pricefile) {
                $this->logger->error("Ingen CSV-fil hittades för konkurrent {$competitor['name']}");
                return $results;
            }
        }
        
        // Hämta inställningar från databasen
        $settings = $this->getSettings($competitor['id_competitor']);
        
        // Uppdatera timestamp för senaste uppdatering för denna konkurrent
        $db->execute("UPDATE `" . _DB_PREFIX_ . "art_pricematcher_competitors` 
                     SET `date_upd` = NOW() 
                     WHERE `id_competitor` = " . (int)$competitor['id_competitor']);
        
        // Bearbeta CSV-filen
        $results = $this->processCsvFile($pricefile, $competitor, $settings);
        
        // Spara statistik i databasen
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->statistics->saveOperationStatistics(
            $competitor['id_competitor'],
            'compare',
            [
                'total_products' => $results['total_products'],
                'success_count' => $results['products_matched'],
                'error_count' => $results['total_products'] - $results['products_matched'] - $results['products_skipped'],
                'skipped_count' => $results['products_skipped'],
                'execution_time' => $executionTime,
                'initiated_by' => Tools::getValue('cron') ? 'cron' : 'manual'
            ]
        );
        
        $results['execution_time'] = $executionTime;
        
        return $results;
    }
    
    /**
     * Hitta den senaste CSV-filen för en konkurrent
     * 
     * @param string $competitorName Namn på konkurrenten
     * @return string|false Sökväg till den senaste CSV-filen eller false om ingen hittas
     */
    private function findLatestCsvFile($competitorName)
    {
        $directory = _PS_MODULE_DIR_ . 'art_pricematcher/price_files/competitors_files/';
        if (!is_dir($directory)) {
            $this->logger->error("Konkurrentkatalog hittades inte: $directory");
            return false;
        }
        
        $latestFile = false;
        $latestTime = 0;
        
        $files = glob($directory . strtolower($competitorName) . '_*.csv');
        if (!$files) {
            $this->logger->error("Inga CSV-filer hittades för konkurrent: $competitorName");
            return false;
        }
        
        foreach ($files as $file) {
            $fileTime = filemtime($file);
            if ($fileTime > $latestTime) {
                $latestTime = $fileTime;
                $latestFile = $file;
            }
        }
        
        return $latestFile;
    }
    
    /**
     * Hämta inställningar för prisjämförelse
     * 
     * @param int $competitorId Konkurrent-ID
     * @return array Inställningar
     */
    private function getSettings($competitorId)
    {
        $db = Db::getInstance();
        $settings = [];
        
        // Hämta globala inställningar
        $globalConfigQuery = "SELECT * FROM `" . _DB_PREFIX_ . "art_pricematcher_config`";
        $globalConfigs = $db->executeS($globalConfigQuery);
        
        // Skapa associativ array av globala inställningar
        foreach ($globalConfigs as $config) {
            $settings[$config['name']] = $config['value'];
        }
        
        // Hämta konkurrentspecifika inställningar
        $competitorQuery = "SELECT * FROM `" . _DB_PREFIX_ . "art_pricematcher_competitors` 
                           WHERE `id_competitor` = " . (int)$competitorId;
        $competitor = $db->getRow($competitorQuery);
        
        // Kontrollera om konkurrenten har specifika inställningar
        if (isset($competitor['override_discount_settings']) && $competitor['override_discount_settings']) {
            // Använd konkurrentspecifika inställningar
            if (isset($competitor['discount_strategy'])) {
                $settings['discount_strategy'] = $competitor['discount_strategy'];
            }
            if (isset($competitor['min_margin_percent'])) {
                $settings['min_margin_percent'] = $competitor['min_margin_percent'];
            }
            if (isset($competitor['max_discount_percent'])) {
                $settings['max_discount_percent'] = $competitor['max_discount_percent'];
            }
            if (isset($competitor['price_underbid'])) {
                $settings['price_underbid'] = $competitor['price_underbid'];
            }
            if (isset($competitor['min_price_threshold'])) {
                $settings['min_price_threshold'] = $competitor['min_price_threshold'];
            }
        }
        
        // Sätt standardvärden om inställningar saknas
        if (!isset($settings['max_discount_percent']) || (float)$settings['max_discount_percent'] <= 0) {
            $settings['max_discount_percent'] = 24;
        }
        if (!isset($settings['min_margin_percent']) || (float)$settings['min_margin_percent'] <= 0) {
            $settings['min_margin_percent'] = 30;
        }
        if (!isset($settings['min_price_threshold']) || (float)$settings['min_price_threshold'] <= 0) {
            $settings['min_price_threshold'] = 100;
        }
        if (!isset($settings['price_underbid']) || (float)$settings['price_underbid'] < 0) {
            $settings['price_underbid'] = 5;
        }
        if (!isset($settings['max_discount_behavior']) || !in_array($settings['max_discount_behavior'], ['skip', 'partial'])) {
            $settings['max_discount_behavior'] = 'partial';
        }
        
        // Hantera uteslutna kategorier
        $settings['excluded_categories'] = [];
        if (isset($settings['excluded_categories']) && !empty($settings['excluded_categories'])) {
            $excludedCategories = json_decode($settings['excluded_categories'], true);
            if (is_array($excludedCategories)) {
                $settings['excluded_categories'] = $excludedCategories;
            }
        }
        
        // Hantera uteslutna tillverkare
        $settings['excluded_manufacturers'] = [];
        if (isset($settings['excluded_manufacturers']) && !empty($settings['excluded_manufacturers'])) {
            $excludedManufacturers = json_decode($settings['excluded_manufacturers'], true);
            if (is_array($excludedManufacturers)) {
                $settings['excluded_manufacturers'] = $excludedManufacturers;
            }
        }
        
        // Hantera uteslutna referenser
        $settings['excluded_references'] = [];
        if (isset($settings['excluded_references']) && !empty($settings['excluded_references'])) {
            $excludedReferences = preg_split('/\r\n|\r|\n/', $settings['excluded_references']);
            $settings['excluded_references'] = array_map('trim', $excludedReferences);
        }
        
        return $settings;
    }
    
    /**
     * Bearbeta en CSV-fil och uppdatera databasen
     * 
     * @param string $csvFile Sökväg till CSV-filen
     * @param array $competitor Konkurrentinformation
     * @param array $settings Inställningar
     * @return array Bearbetningsresultat
     */
    private function processCsvFile($csvFile, $competitor, $settings)
    {
        $this->logger->info("Bearbetar CSV-fil: $csvFile");
        
        $results = [
            'total_products' => 0,
            'products_found' => 0,
            'products_not_found' => 0,
            'products_matched' => 0,
            'products_lower' => 0,
            'products_skipped' => 0
        ];
        
        if (!file_exists($csvFile)) {
            $this->logger->error("CSV-fil hittades inte: $csvFile");
            return $results;
        }
        
        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            $this->logger->error("Kunde inte öppna CSV-fil: $csvFile");
            return $results;
        }
        
        // Läs första raden för att identifiera kolumner
        $headers = fgetcsv($handle, 0, ',');
        
        // Skapa en mappning av kolumnnamn till index
        $columnMap = [];
        foreach ($headers as $index => $header) {
            $columnMap[strtolower(trim($header))] = $index;
        }
        
        // Kontrollera att nödvändiga kolumner finns
        $requiredColumns = ['sku', 'competitor_price'];
        foreach ($requiredColumns as $column) {
            if (!isset($columnMap[$column])) {
                $this->logger->error("Nödvändig kolumn saknas i CSV-filen: $column");
                fclose($handle);
                return $results;
            }
        }
        
        $db = Db::getInstance();
        
        // Bearbeta varje rad
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $results['total_products']++;
            
            // Extrahera data från CSV-rad
            $reference = isset($columnMap['sku']) ? trim($row[$columnMap['sku']]) : '';
            $ean = isset($columnMap['ean']) ? trim($row[$columnMap['ean']]) : '';
            $priceFromFile = isset($columnMap['competitor_price']) ? (float)str_replace(',', '.', $row[$columnMap['competitor_price']]) : 0;
            $url = isset($columnMap['url']) ? trim($row[$columnMap['url']]) : '';
            
            if (empty($reference) && empty($ean)) {
                $this->logger->info("Hoppar över rad med tom referens och EAN");
                $results['products_not_found']++;
                continue;
            }
            
            if ($priceFromFile <= 0) {
                $this->logger->info("Hoppar över rad med ogiltigt pris: $priceFromFile");
                $results['products_not_found']++;
                continue;
            }
            
            // Kontrollera om denna referens är utesluten
            if ($this->isReferenceExcluded($reference, $settings['excluded_references'])) {
                $this->logger->info("Hoppar över utesluten referens: $reference");
                $results['products_skipped']++;
                continue;
            }
            
            // Försök hitta produkten med olika matchningsmetoder
            $productId = $this->findProductByMultipleMethods($reference, $ean);
            
            if (!$productId) {
                $this->logger->info("Produkt hittades inte för referens: $reference, EAN: $ean");
                $results['products_not_found']++;
                continue;
            }
            
            $results['products_found']++;
            
            // Hämta produktinformation
            $product = new Product($productId);
            if (!Validate::isLoadedObject($product)) {
                $this->logger->error("Kunde inte ladda produkt med ID: $productId");
                $results['products_not_found']++;
                continue;
            }
            
            // Kontrollera om produkten är aktiv
            if (!$product->active) {
                $this->logger->info("Hoppar över inaktiv produkt med ID: $productId");
                $results['products_skipped']++;
                continue;
            }
            
            // Kontrollera om tillverkaren är utesluten
            if (in_array($product->id_manufacturer, $settings['excluded_manufacturers'])) {
                $this->logger->info("Hoppar över produkt ID: $productId - tillverkare är utesluten");
                $results['products_skipped']++;
                continue;
            }
            
            // Kontrollera om kategorin är utesluten
            $productCategories = Product::getProductCategories($productId);
            $excludedCategory = false;
            foreach ($settings['excluded_categories'] as $categoryId) {
                if (in_array($categoryId, $productCategories)) {
                    $excludedCategory = true;
                    break;
                }
            }
            
            if ($excludedCategory) {
                $this->logger->info("Hoppar över produkt ID: $productId - kategori är utesluten");
                $results['products_skipped']++;
                continue;
            }
            
            // Hämta produktpriser
            $currentPrice = (float)$product->price;
            $wholesalePrice = (float)$product->wholesale_price;
            
            // Kontrollera om produktpriset är under miniminivån
            if ($currentPrice < $settings['min_price_threshold']) {
                $this->logger->info("Produkt ID $productId hoppad över: pris ($currentPrice) under miniminivå ({$settings['min_price_threshold']})");
                $results['products_skipped']++;
                continue;
            }
            
            // Använd momsberäkning om det behövs
            $taxRate = Tax::getProductTaxRate($productId);
            $priceFromFileTaxExcl = $priceFromFile / (1 + ($taxRate / 100));
            
            // Beräkna nytt pris (konkurrentpris minus underbud)
            $newPrice = $priceFromFileTaxExcl > $settings['price_underbid'] ? 
                        $priceFromFileTaxExcl - $settings['price_underbid'] : 
                        $priceFromFileTaxExcl;
            
            // Om aktuellt pris är lägre än pris från fil, använd aktuellt pris
            if ($currentPrice <= $priceFromFileTaxExcl) {
                $this->logger->info("Hoppar över produkt ID: $productId - Vårt pris ($currentPrice) är redan lägre eller lika med konkurrentpris ($priceFromFileTaxExcl)");
                $results['products_skipped']++;
                
                // Om produkten finns i databasen, ta bort den eftersom vi inte behöver prismatchning
                $sql = "DELETE FROM `" . _DB_PREFIX_ . "art_pricematcher` 
                       WHERE `id_product` = " . (int)$productId . " 
                       AND `id_competitor` = " . (int)$competitor['id_competitor'];
                $db->execute($sql);
                continue;
            }
            
            // Beräkna marginal
            $currentMargin = $wholesalePrice > 0 ? (($currentPrice - $wholesalePrice) / $currentPrice) * 100 : 0;
            $newMargin = $wholesalePrice > 0 ? (($newPrice - $wholesalePrice) / $newPrice) * 100 : 0;
            
            // Beräkna rabattprocentandel
            $discountPercent = $currentPrice > 0 ? (($currentPrice - $newPrice) / $currentPrice) * 100 : 0;
            
            // Kontrollera marginalrestriktion
            if ($newMargin < $settings['min_margin_percent']) {
                $this->logger->info("Hoppar över produkt ID: $productId - Ny marginal ($newMargin%) är under minimum ({$settings['min_margin_percent']}%)");
                $results['products_skipped']++;
                continue;
            }
            
            // Kontrollera maximal rabattbegränsning
            if ($discountPercent > $settings['max_discount_percent']) {
                if ($settings['max_discount_behavior'] === 'skip') {
                    // Hoppa över produkten helt
                    $this->logger->info("Hoppar över produkt ID: $productId - Rabatt ($discountPercent%) överstiger maximum ({$settings['max_discount_percent']}%) och beteendet är satt till 'skip'");
                    $results['products_skipped']++;
                    continue;
                } else {
                    // Tillämpa partiell rabatt upp till maximum
                    $this->logger->info("Begränsar rabatt för produkt ID: $productId - Rabatt reducerad från $discountPercent% till {$settings['max_discount_percent']}%");
                    $newPrice = $currentPrice * (1 - ($settings['max_discount_percent'] / 100));
                    $discountPercent = $settings['max_discount_percent'];
                    
                    // Uppdatera marginalen baserat på det nya priset
                    $newMargin = $wholesalePrice > 0 ? (($newPrice - $wholesalePrice) / $newPrice) * 100 : 0;
                }
            }
            
            // Produkten är matchad och kan prisjusteras
            $results['products_matched']++;
            $results['products_lower']++;
            
            // Kontrollera om produkten redan finns i databasen
            $productExists = $db->getValue("SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "art_pricematcher` 
                                          WHERE `id_product` = " . (int)$productId . " 
                                          AND `id_competitor` = " . (int)$competitor['id_competitor']);
            
            if ($productExists) {
                // Uppdatera befintlig produkt
                $sql = "UPDATE `" . _DB_PREFIX_ . "art_pricematcher` 
                       SET 
                       `wholesale_price` = " . (float)$wholesalePrice . ",
                       `current_price` = " . (float)$currentPrice . ",
                       `current_margin` = " . (float)$currentMargin . ",
                       `competitor_price` = " . (float)$priceFromFileTaxExcl . ",
                       `new_price` = " . (float)$newPrice . ",
                       `new_margin` = " . (float)$newMargin . ",
                       `discount_percent` = " . (float)$discountPercent . ",
                       `last_update` = NOW(),
                       `pricefile` = '" . pSQL(basename($csvFile)) . "',
                       `url` = '" . pSQL($url) . "'
                       WHERE `id_product` = " . (int)$productId . " 
                       AND `id_competitor` = " . (int)$competitor['id_competitor'];
            } else {
                // Infoga ny produkt
                $sql = "INSERT INTO `" . _DB_PREFIX_ . "art_pricematcher` 
                       (`id_product`, `id_manufacturer`, `supplier_reference`, `ean13`, 
                       `wholesale_price`, `current_price`, `current_margin`, 
                       `competitor_price`, `new_price`, `new_margin`, 
                       `discount_percent`, `last_update`, `pricefile`, 
                       `id_competitor`, `url`) 
                       VALUES 
                       (" . (int)$productId . ", " . (int)$product->id_manufacturer . ", 
                       '" . pSQL($product->reference) . "', '" . pSQL($product->ean13) . "', 
                       " . (float)$wholesalePrice . ", " . (float)$currentPrice . ", " . (float)$currentMargin . ", 
                       " . (float)$priceFromFileTaxExcl . ", " . (float)$newPrice . ", " . (float)$newMargin . ", 
                       " . (float)$discountPercent . ", NOW(), '" . pSQL(basename($csvFile)) . "', 
                       " . (int)$competitor['id_competitor'] . ", '" . pSQL($url) . "')";
            }
            
            if (!$db->execute($sql)) {
                $this->logger->error("Misslyckades med att uppdatera produkt ID: $productId");
                $results['products_skipped']++;
            }
        }
        
        fclose($handle);
        $this->logger->info("CSV-bearbetning avslutad: " . json_encode($results));
        return $results;
    }
    
    /**
     * Kontrollera om en referens är utesluten
     * 
     * @param string $reference Produktreferens
     * @param array $excludedReferences Lista över uteslutna referenser
     * @return bool true om referensen är utesluten
     */
    private function isReferenceExcluded($reference, $excludedReferences)
    {
        if (empty($reference) || empty($excludedReferences)) {
            return false;
        }
        
        foreach ($excludedReferences as $pattern) {
            // Hantera jokerreferenser
            if (strpos($pattern, '*') !== false) {
                $regexPattern = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/i';
                if (preg_match($regexPattern, $reference)) {
                    return true;
                }
            } elseif (strcasecmp($pattern, $reference) === 0) {
                // Exakt matchning (skiftlägesokänslig)
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Hitta en produkt med flera matchningsmetoder
     * 
     * @param string $reference Produktreferens
     * @param string $ean Produkt-EAN
     * @return int|false Produkt-ID eller false om den inte hittas
     */
    private function findProductByMultipleMethods($reference, $ean)
    {
        $db = Db::getInstance();
        $productId = false;
        
        // Metod 1: Försök matcha med EAN (högsta prioritet)
        if (!empty($ean)) {
            $this->logger->info("Försöker matcha med EAN: $ean");
            $productId = $db->getValue("
                SELECT `id_product` 
                FROM `" . _DB_PREFIX_ . "product` 
                WHERE `ean13` = '" . pSQL($ean) . "'
                AND `active` = 1
            ");
            
            if ($productId) {
                $this->logger->info("Produkt matchad med EAN: $ean, Produkt-ID: $productId");
                return (int)$productId;
            }
        }
        
        // Metod 2: Försök matcha med referens OCH tillverkare
        if (!empty($reference)) {
            $this->logger->info("Försöker matcha med referens OCH tillverkare: $reference");
            $productId = $db->getValue("
                SELECT p.`id_product` 
                FROM `" . _DB_PREFIX_ . "product` p
                JOIN `" . _DB_PREFIX_ . "manufacturer` m ON p.`id_manufacturer` = m.`id_manufacturer`
                WHERE p.`reference` = '" . pSQL($reference) . "'
                AND p.`active` = 1
            ");
            
            if ($productId) {
                $this->logger->info("Produkt matchad med referens OCH tillverkare: $reference, Produkt-ID: $productId");
                return (int)$productId;
            }
        }
        
        // Metod 3: Försök matcha med supplier_reference
        if (!empty($reference)) {
            $this->logger->info("Försöker matcha med supplier_reference: $reference");
            $productId = $db->getValue("
                SELECT ps.`id_product` 
                FROM `" . _DB_PREFIX_ . "product_supplier` ps
                JOIN `" . _DB_PREFIX_ . "product` p ON ps.`id_product` = p.`id_product`
                WHERE ps.`product_supplier_reference` = '" . pSQL($reference) . "'
                AND p.`active` = 1
            ");
            
            if ($productId) {
                $this->logger->info("Produkt matchad med supplier_reference: $reference, Produkt-ID: $productId");
                return (int)$productId;
            }
        }
        
        // Metod 4: Försök matcha med bara referens (sista utväg)
        if (!empty($reference)) {
            $this->logger->info("Försöker matcha med enbart referens: $reference");
            $productId = $db->getValue("
                SELECT `id_product` 
                FROM `" . _DB_PREFIX_ . "product` 
                WHERE `reference` = '" . pSQL($reference) . "'
                AND `active` = 1
            ");
            
            if ($productId) {
                $this->logger->info("Produkt matchad med enbart referens: $reference, Produkt-ID: $productId");
                return (int)$productId;
            }
        }
        
        $this->logger->info("Ingen matchning hittades för referens: $reference, EAN: $ean");
        return false;
    }
    
    /**
     * Hämta produkter med prisskillnader för en konkurrent
     * 
     * @param int $competitorId Konkurrent-ID
     * @return array Produkter med prisskillnader
     */
    public function getProductsWithPriceDifferences($competitorId)
    {
        $this->logger->info("Hämtar produkter med prisskillnader för konkurrent ID: $competitorId");
        
        // Hämta lägsta rabattprocentandel från databasen
        $db = Db::getInstance();
        $minDiscountPercent = (float)$db->getValue("SELECT `value` FROM `" . _DB_PREFIX_ . "art_pricematcher_config` WHERE `name` = 'min_discount_percent'");
        
        // Använd standardvärde om det inte hittas eller är ogiltigt
        if ($minDiscountPercent <= 0) {
            $minDiscountPercent = 5;
        }
        
        $this->logger->info("Använder lägsta rabattprocentandel: $minDiscountPercent");
        
        $sql = "SELECT pm.*, pl.name as product_name
               FROM `" . _DB_PREFIX_ . "art_pricematcher` pm
               JOIN `" . _DB_PREFIX_ . "product_lang` pl ON pm.`id_product` = pl.`id_product` 
                   AND pl.`id_lang` = " . (int)$this->context->language->id . "
               WHERE pm.`id_competitor` = " . (int)$competitorId . "
               AND pm.`competitor_price` > 0
               AND pm.`current_price` > 0
               AND ((pm.`current_price` - pm.`competitor_price`) / pm.`current_price` * 100) >= " . (float)$minDiscountPercent . "
               ORDER BY pm.discount_percent DESC";
        
        $results = $db->executeS($sql);
        
        if (!$results) {
            return [];
        }
        
        $formattedResults = [];
        $currencySign = $this->context->currency->sign;
        
        foreach ($results as $row) {
            $priceDifference = $row['current_price'] - $row['competitor_price'];
            $percentageDifference = round(($priceDifference / $row['current_price']) * 100, 2);
            
            $formattedResults[] = [
                'id_product' => $row['id_product'],
                'reference' => $row['supplier_reference'],
                'product_name' => $row['product_name'],
                'current_price' => $currencySign . number_format($row['current_price'], 2),
                'competitor_price' => $currencySign . number_format($row['competitor_price'], 2),
                'price_difference' => $currencySign . number_format($priceDifference, 2),
                'percentage_difference' => $percentageDifference . '%',
                'wholesale_price' => $currencySign . number_format($row['wholesale_price'], 2),
                'current_margin' => round($row['current_margin'], 2) . '%',
                'new_margin' => round($row['new_margin'], 2) . '%',
                'url' => $row['url'],
                'price_mismatch_message' => "Aktuellt pris: " . $currencySign . number_format($row['current_price'], 2) . 
                                          ", Konkurrentpris: " . $currencySign . number_format($row['competitor_price'], 2) . 
                                          ", Skillnad: " . $percentageDifference . "%"
            ];
        }
        
        return $formattedResults;
    }
    
    /**
     * Matchningsfunktion för att hitta produkter baserat på CSV-data
     * 
     * @param array $products CSV-data med produkter för matchning
     * @param array $settings Inställningar för matchning
     * @return array Matchade produkter
     */
    public function matchProductsByData($products, $settings = [])
    {
        $this->logger->info("Startar produktmatchning med " . count($products) . " produkter");
        
        if (empty($settings)) {
            $settings = $this->getSettings(0); // Hämta globala inställningar om inga angetts
        }
        
        $results = [
            'total_products' => count($products),
            'products_found' => 0,
            'products_not_found' => 0,
            'products_matched' => 0,
            'products_lower' => 0,
            'products_skipped' => 0
        ];
        
        $matchedProducts = [];
        
        foreach ($products as $product) {
            // Extrahera data
            $reference = isset($product['sku']) ? trim($product['sku']) : '';
            $ean = isset($product['ean']) ? trim($product['ean']) : '';
            $priceFromFile = isset($product['competitor_price']) ? (float)$product['competitor_price'] : 0;
            $url = isset($product['url']) ? trim($product['url']) : '';
            
            if (empty($reference) && empty($ean)) {
                $this->logger->info("Hoppar över produkt med tom referens och EAN");
                $results['products_not_found']++;
                continue;
            }
            
            if ($priceFromFile <= 0) {
                $this->logger->info("Hoppar över produkt med ogiltigt pris: $priceFromFile");
                $results['products_not_found']++;
                continue;
            }
            
            // Kontrollera om denna referens är utesluten
            if ($this->isReferenceExcluded($reference, $settings['excluded_references'])) {
                $this->logger->info("Hoppar över utesluten referens: $reference");
                $results['products_skipped']++;
                continue;
            }
            
            // Försök hitta produkten
            $productId = $this->findProductByMultipleMethods($reference, $ean);
            
            if (!$productId) {
                $this->logger->info("Produkt hittades inte för referens: $reference, EAN: $ean");
                $results['products_not_found']++;
                continue;
            }
            
            $results['products_found']++;
            
            // Hämta produktinformation
            $shopProduct = new Product($productId);
            if (!Validate::isLoadedObject($shopProduct)) {
                $this->logger->error("Kunde inte ladda produkt med ID: $productId");
                $results['products_not_found']++;
                continue;
            }
            
            // Hämta produktpriser
            $currentPrice = (float)$shopProduct->price;
            $wholesalePrice = (float)$shopProduct->wholesale_price;
            
            // Kontrollera om produkten är aktiv
            if (!$shopProduct->active) {
                $this->logger->info("Hoppar över inaktiv produkt med ID: $productId");
                $results['products_skipped']++;
                continue;
            }
            
            // Kontrollera om tillverkaren är utesluten
            if (in_array($shopProduct->id_manufacturer, $settings['excluded_manufacturers'])) {
                $this->logger->info("Hoppar över produkt ID: $productId - tillverkare är utesluten");
                $results['products_skipped']++;
                continue;
            }
            
            // Kontrollera om kategorin är utesluten
            $productCategories = Product::getProductCategories($productId);
            $excludedCategory = false;
            foreach ($settings['excluded_categories'] as $categoryId) {
                if (in_array($categoryId, $productCategories)) {
                    $excludedCategory = true;
                    break;
                }
            }
            
            if ($excludedCategory) {
                $this->logger->info("Hoppar över produkt ID: $productId - kategori är utesluten");
                $results['products_skipped']++;
                continue;
            }
            
            // Kontrollera om produktpriset är under miniminivån
            if ($currentPrice < $settings['min_price_threshold']) {
                $this->logger->info("Produkt ID $productId hoppad över: pris ($currentPrice) under miniminivå ({$settings['min_price_threshold']})");
                $results['products_skipped']++;
                continue;
            }
            
            // Använd momsberäkning om det behövs
            $taxRate = Tax::getProductTaxRate($productId);
            $priceFromFileTaxExcl = $priceFromFile / (1 + ($taxRate / 100));
            
            // Om aktuellt pris är lägre än pris från fil, använd aktuellt pris (ingen prisjustering behövs)
            if ($currentPrice <= $priceFromFileTaxExcl) {
                $this->logger->info("Hoppar över produkt ID: $productId - Vårt pris ($currentPrice) är redan lägre eller lika med konkurrentpris ($priceFromFileTaxExcl)");
                $results['products_skipped']++;
                continue;
            }
            
            // Beräkna nytt pris (konkurrentpris minus underbud)
            $newPrice = $priceFromFileTaxExcl > $settings['price_underbid'] ? 
                        $priceFromFileTaxExcl - $settings['price_underbid'] : 
                        $priceFromFileTaxExcl;
            
            // Beräkna marginaler
            $currentMargin = $wholesalePrice > 0 ? (($currentPrice - $wholesalePrice) / $currentPrice) * 100 : 0;
            $newMargin = $wholesalePrice > 0 ? (($newPrice - $wholesalePrice) / $newPrice) * 100 : 0;
            
            // Beräkna rabattprocentandel
            $discountPercent = $currentPrice > 0 ? (($currentPrice - $newPrice) / $currentPrice) * 100 : 0;
            
            // Kontrollera marginalrestriktion
            if ($newMargin < $settings['min_margin_percent']) {
                $this->logger->info("Hoppar över produkt ID: $productId - Ny marginal ($newMargin%) är under minimum ({$settings['min_margin_percent']}%)");
                $results['products_skipped']++;
                continue;
            }
            
            // Kontrollera maximal rabattbegränsning
            if ($discountPercent > $settings['max_discount_percent']) {
                if ($settings['max_discount_behavior'] === 'skip') {
                    // Hoppa över produkten helt
                    $this->logger->info("Hoppar över produkt ID: $productId - Rabatt ($discountPercent%) överstiger maximum ({$settings['max_discount_percent']}%) och beteendet är satt till 'skip'");
                    $results['products_skipped']++;
                    continue;
                } else {
                    // Tillämpa partiell rabatt upp till maximum
                    $this->logger->info("Begränsar rabatt för produkt ID: $productId - Rabatt reducerad från $discountPercent% till {$settings['max_discount_percent']}%");
                    $newPrice = $currentPrice * (1 - ($settings['max_discount_percent'] / 100));
                    $discountPercent = $settings['max_discount_percent'];
                    
                    // Uppdatera marginalen baserat på det nya priset
                    $newMargin = $wholesalePrice > 0 ? (($newPrice - $wholesalePrice) / $newPrice) * 100 : 0;
                }
            }
            
            // Produkten är matchad och kan prisjusteras
            $results['products_matched']++;
            $results['products_lower']++;
            
            // Lägg till matchad produkt i resultaten
            $matchedProducts[] = [
                'id_product' => $productId,
                'id_manufacturer' => $shopProduct->id_manufacturer,
                'supplier_reference' => $shopProduct->reference,
                'ean13' => $shopProduct->ean13,
                'wholesale_price' => $wholesalePrice,
                'current_price' => $currentPrice,
                'current_margin' => $currentMargin,
                'competitor_price' => $priceFromFileTaxExcl,
                'new_price' => $newPrice,
                'new_margin' => $newMargin,
                'discount_percent' => $discountPercent,
                'url' => $url
            ];
        }
        
        return [
            'stats' => $results,
            'products' => $matchedProducts
        ];
    }
}