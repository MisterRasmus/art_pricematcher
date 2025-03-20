<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace ArtPriceMatcher\Helpers;

use Db;
use Mail;
use Context;
use Configuration;
use Validate;
use Tools;

/**
 * E-posthanteringsklass för Art PriceMatcher-modulen
 * Hanterar e-postaviseringar till administratörer
 */
class EmailHandler
{
    /** @var \Logger Logghanterare */
    private $logger;
    
    /** @var array E-postmottagare */
    private $recipients = [];
    
    /** @var string E-postaviseringsfrekvens */
    private $frequency;
    
    /** @var float Aviseringströskel för rabatter */
    private $threshold;
    
    /** @var bool Om e-postaviseringar är aktiverade */
    private $enabled;
    
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->logger = new Logger('email');
        $this->loadConfiguration();
    }
    
    /**
     * Ladda konfiguration för e-postaviseringar
     */
    private function loadConfiguration()
    {
        $db = Db::getInstance();
        
        // Hämta e-postaviseringsinställningar
        $enabled = $db->getValue("SELECT `value` FROM `" . _DB_PREFIX_ . "art_pricematcher_config` WHERE `name` = 'email_notifications'");
        $this->enabled = (bool)$enabled;
        
        if (!$this->enabled) {
            return;
        }
        
        // Hämta e-postmottagare
        $recipientsStr = $db->getValue("SELECT `value` FROM `" . _DB_PREFIX_ . "art_pricematcher_config` WHERE `name` = 'email_recipients'");
        if (!empty($recipientsStr)) {
            $this->recipients = array_map('trim', explode(',', $recipientsStr));
        }
        
        // Hämta aviseringsfrekvens
        $frequency = $db->getValue("SELECT `value` FROM `" . _DB_PREFIX_ . "art_pricematcher_config` WHERE `name` = 'email_frequency'");
        $this->frequency = $frequency ?: 'always';
        
        // Hämta aviseringströskel
        $threshold = $db->getValue("SELECT `value` FROM `" . _DB_PREFIX_ . "art_pricematcher_config` WHERE `name` = 'notification_threshold'");
        $this->threshold = (float)$threshold;
        if ($this->threshold <= 0) {
            $this->threshold = 15;
        }
    }
    
    /**
     * Skicka e-postavisering
     * 
     * @param string $subject Ämne
     * @param string $template Mall att använda
     * @param array $templateVars Mallvariabler
     * @return bool Resultat
     */
    public function sendNotification($subject, $template, $templateVars = [])
    {
        if (!$this->enabled || empty($this->recipients)) {
            $this->logger->info("E-postaviseringar är inaktiverade eller inga mottagare angivna");
            return false;
        }
        
        // Lägg till standardvariabler till alla mallar
        $templateVars['shop_name'] = Configuration::get('PS_SHOP_NAME');
        $templateVars['shop_url'] = Context::getContext()->link->getBaseLink();
        $templateVars['logo_url'] = Context::getContext()->link->getMediaLink(_PS_IMG_ . Configuration::get('PS_LOGO'));
        
        $result = true;
        
        foreach ($this->recipients as $email) {
            if (!Validate::isEmail($email)) {
                $this->logger->warning("Ogiltig e-postadress: $email");
                continue;
            }
            
            // Skicka e-post med PrestaShop Mail-klass
            $mailResult = Mail::send(
                Context::getContext()->language->id,
                $template,
                $subject,
                $templateVars,
                $email,
                null,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_ . 'art_pricematcher/mails/'
            );
            
            if (!$mailResult) {
                $this->logger->error("Misslyckades med att skicka e-post till: $email");
                $result = false;
            } else {
                $this->logger->info("E-post skickad till: $email");
            }
        }
        
        return $result;
    }
    
    /**
     * Kontrollera om en aviseringen ska skickas baserat på frekvensen
     * 
     * @param string $type Aviseringstyp (download, compare, update)
     * @return bool Om aviseringen ska skickas
     */
    public function shouldSendNotification($type)
    {
        if (!$this->enabled) {
            return false;
        }
        
        // Om frekvensen är 'always', skicka alltid
        if ($this->frequency === 'always') {
            return true;
        }
        
        // För 'daily' och 'weekly', kontrollera om det är dags att skicka en sammanfattning
        if ($this->frequency === 'daily') {
            $lastSent = Configuration::get('PRICEMATCHER_LAST_DAILY_NOTIFICATION');
            $sendAfter = strtotime('-1 day');
            
            if (!$lastSent || $lastSent < $sendAfter) {
                // Uppdatera senaste avisering
                Configuration::updateValue('PRICEMATCHER_LAST_DAILY_NOTIFICATION', time());
                return true;
            }
        } elseif ($this->frequency === 'weekly') {
            $lastSent = Configuration::get('PRICEMATCHER_LAST_WEEKLY_NOTIFICATION');
            $sendAfter = strtotime('-1 week');
            
            if (!$lastSent || $lastSent < $sendAfter) {
                // Uppdatera senaste avisering
                Configuration::updateValue('PRICEMATCHER_LAST_WEEKLY_NOTIFICATION', time());
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Skicka nedladdningsrapport
     * 
     * @param array $downloadResults Nedladdningsresultat
     * @param float $executionTime Exekveringstid
     * @return bool Resultat
     */
    public function sendDownloadReport($downloadResults, $executionTime)
    {
        if (!$this->shouldSendNotification('download')) {
            return false;
        }
        
        $subject = sprintf(
            'Prismatchning: Nedladdningsrapport (%s)', 
            date('Y-m-d H:i')
        );
        
        $templateVars = [
            'download_results' => $downloadResults,
            'execution_time' => round($executionTime, 2),
            'date' => date('Y-m-d H:i:s')
        ];
        
        return $this->sendNotification($subject, 'download_report', $templateVars);
    }
    
    /**
     * Skicka jämförelserapport
     * 
     * @param array $priceDeviations Prisjämförelseresultat
     * @param float $executionTime Exekveringstid
     * @return bool Resultat
     */
    public function sendCompareReport($priceDeviations, $executionTime)
    {
        if (!$this->shouldSendNotification('compare')) {
            return false;
        }
        
        // Filtrera bort små prisavvikelser under tröskelvärdet
        $significantDeviations = [];
        foreach ($priceDeviations as $deviation) {
            if ($deviation['discount_percent'] >= $this->threshold) {
                $significantDeviations[] = $deviation;
            }
        }
        
        if (empty($significantDeviations)) {
            $this->logger->info("Inga betydande prisavvikelser att rapportera");
            return false;
        }
        
        $subject = sprintf(
            'Prismatchning: Jämförelserapport - %d produkter (%s)', 
            count($significantDeviations),
            date('Y-m-d H:i')
        );
        
        $templateVars = [
            'price_deviations' => $significantDeviations,
            'execution_time' => round($executionTime, 2),
            'date' => date('Y-m-d H:i:s'),
            'threshold' => $this->threshold
        ];
        
        return $this->sendNotification($subject, 'compare_report', $templateVars);
    }
    
    /**
     * Skicka uppdateringsrapport
     * 
     * @param array $updateResults Uppdateringsresultat
     * @param float $executionTime Exekveringstid
     * @return bool Resultat
     */
    public function sendUpdateReport($updateResults, $executionTime)
    {
        if (!$this->shouldSendNotification('update')) {
            return false;
        }
        
        $subject = sprintf(
            'Prismatchning: Uppdateringsrapport - %d produkter (%s)', 
            isset($updateResults['products_updated']) ? $updateResults['products_updated'] : 0,
            date('Y-m-d H:i')
        );
        
        $templateVars = [
            'update_results' => $updateResults,
            'execution_time' => round($executionTime, 2),
            'date' => date('Y-m-d H:i:s')
        ];
        
        return $this->sendNotification($subject, 'update_report', $templateVars);
    }
    
    /**
     * Skicka veckosammanfattning
     * 
     * @return bool Resultat
     */
    public function sendWeeklySummary()
    {
        if ($this->frequency !== 'weekly') {
            return false;
        }
        
        // Skapa statistiksammanfattning för veckan
        $stats = new Statistics();
        $summary = $stats->getStatisticsSummary(7); // Senaste 7 dagarna
        
        // Hämta prisavvikelser över tröskeln
        $deviations = $stats->getPriceDeviations();
        $significantDeviations = [];
        foreach ($deviations['products'] as $product) {
            if ($product['discount_percent'] >= $this->threshold) {
                $significantDeviations[] = $product;
            }
        }
        
        $subject = sprintf(
            'Prismatchning: Veckosammanfattning (%s)', 
            date('Y-m-d')
        );
        
        $templateVars = [
            'summary' => $summary,
            'price_deviations' => $significantDeviations,
            'date' => date('Y-m-d'),
            'week_number' => date('W'),
            'threshold' => $this->threshold
        ];
        
        return $this->sendNotification($subject, 'weekly_summary', $templateVars);
    }
}