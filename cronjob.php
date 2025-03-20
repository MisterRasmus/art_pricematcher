<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

// Inkludera PrestaShop-konfigurationen
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');

// Säkerhetsvalidering: Kontrollera token
$requestToken = Tools::getValue('token');
if (empty($requestToken)) {
    die('Error: No token provided');
}

// Hämta den lagrade token från databasen
$storedToken = Db::getInstance()->getValue(
    'SELECT `value` FROM `' . _DB_PREFIX_ . 'art_pricematcher_config` WHERE `name` = "cron_token"'
);

// Validera token
if ($requestToken !== $storedToken) {
    die('Error: Invalid token');
}

// Ladda modulen
$module = Module::getInstanceByName('art_pricematcher');
if (!$module) {
    die('Error: Module not found');
}

// Skapa en logger för att spåra cron-körningen
require_once(dirname(__FILE__) . '/classes/helpers/Logger.php');
$logger = new ArtPriceMatcher\Helpers\Logger('cronjob');
$logger->log('Starting cron job execution');

// Hämta konkurrenter som är konfigurerade för cron-uppdatering
$competitors = Db::getInstance()->executeS('
    SELECT * FROM `' . _DB_PREFIX_ . 'art_pricematcher_competitors`
    WHERE `active` = 1 AND `cron_update` = 1
');

if (!$competitors || !is_array($competitors)) {
    $logger->log('No competitors configured for cron updates');
    die('No competitors configured for cron updates');
}

// Bearbeta varje konkurrent
foreach ($competitors as $competitor) {
    $logger->log('Processing competitor: ' . $competitor['name']);
    
    try {
        // 1. Ladda ner priser
        $logger->log('Downloading prices for: ' . $competitor['name']);
        require_once(dirname(__FILE__) . '/classes/competitors/CompetitorBase.php');
        $competitorClassName = 'ArtPriceMatcher\\Competitors\\' . str_replace(' ', '', $competitor['name']);
        
        if (!class_exists($competitorClassName)) {
            $logger->log('Error: Competitor class not found: ' . $competitorClassName);
            continue;
        }
        
        $competitorHandler = new $competitorClassName();
        $downloadResult = $competitorHandler->downloadPrices();
        
        if (!$downloadResult['success']) {
            $logger->log('Error downloading prices: ' . $downloadResult['message']);
            continue;
        }
        
        // 2. Jämför priser
        $logger->log('Comparing prices for: ' . $competitor['name']);
        require_once(dirname(__FILE__) . '/classes/process/ComparePrices.php');
        $compareHandler = new ArtPriceMatcher\Process\ComparePrices($competitor['id_competitor']);
        $compareResult = $compareHandler->process();
        
        if (!$compareResult['success']) {
            $logger->log('Error comparing prices: ' . $compareResult['message']);
            continue;
        }
        
        // 3. Uppdatera priser
        $logger->log('Updating prices for: ' . $competitor['name']);
        require_once(dirname(__FILE__) . '/classes/process/UpdatePrices.php');
        $updateHandler = new ArtPriceMatcher\Process\UpdatePrices($competitor['id_competitor']);
        $updateResult = $updateHandler->process();
        
        if (!$updateResult['success']) {
            $logger->log('Error updating prices: ' . $updateResult['message']);
            continue;
        }
        
        $logger->log('Successfully processed competitor: ' . $competitor['name']);
        
    } catch (Exception $e) {
        $logger->log('Exception processing competitor: ' . $e->getMessage());
    }
}

// Rensa utgångna rabatter
try {
    $logger->log('Cleaning expired discounts');
    require_once(dirname(__FILE__) . '/classes/process/UpdatePrices.php');
    $updateHandler = new ArtPriceMatcher\Process\UpdatePrices();
    $cleanResult = $updateHandler->cleanExpiredDiscounts();
    
    if (!$cleanResult['success']) {
        $logger->log('Error cleaning expired discounts: ' . $cleanResult['message']);
    } else {
        $logger->log('Successfully cleaned expired discounts');
    }
} catch (Exception $e) {
    $logger->log('Exception cleaning expired discounts: ' . $e->getMessage());
}

$logger->log('Cron job execution completed');
echo 'Cron job executed successfully';
