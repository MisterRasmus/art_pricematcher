<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace ArtPriceMatcher\Helpers;

/**
 * Loggningsklass för Art PriceMatcher-modulen
 * Hanterar loggning av operationer för felsökning och uppföljning
 */
class Logger
{
    /** @var string Loggkategori */
    private $category;
    
    /** @var string Loggsökväg */
    private $logPath;
    
    /**
     * Konstruktor
     * 
     * @param string $category Loggkategori (t.ex. 'download', 'compare', 'update')
     */
    public function __construct($category = 'general')
    {
        $this->category = $category;
        $this->logPath = _PS_MODULE_DIR_ . 'art_pricematcher/logs/';
        
        // Skapa logkatalog om den inte finns
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }
    }
    
    /**
     * Logga ett meddelande på lämplig nivå
     * 
     * @param string $message Meddelande att logga
     * @param string $level Loggnivå (info, warning, error, debug)
     * @param array $context Extra kontext för logginlägg
     */
    public function log($message, $level = 'info', $context = [])
    {
        $logFile = $this->logPath . $this->category . '_' . date('Y-m-d') . '.log';
        
        // Formatera meddelande med tidsstämpel och nivå
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = sprintf("[%s] [%s] %s", $timestamp, strtoupper($level), $message);
        
        // Lägg till kontext om det finns
        if (!empty($context)) {
            $formattedMessage .= ' ' . json_encode($context);
        }
        
        // Skriv till loggfil
        file_put_contents($logFile, $formattedMessage . PHP_EOL, FILE_APPEND);
    }
    
    /**
     * Logga informationsmeddelande
     * 
     * @param string $message Meddelande att logga
     * @param array $context Extra kontext
     */
    public function info($message, $context = [])
    {
        $this->log($message, 'info', $context);
    }
    
    /**
     * Logga varningsmeddelande
     * 
     * @param string $message Meddelande att logga
     * @param array $context Extra kontext
     */
    public function warning($message, $context = [])
    {
        $this->log($message, 'warning', $context);
    }
    
    /**
     * Logga felmeddelande
     * 
     * @param string $message Meddelande att logga
     * @param array $context Extra kontext
     */
    public function error($message, $context = [])
    {
        $this->log($message, 'error', $context);
    }
    
    /**
     * Logga felsökningsmeddelande
     * 
     * @param string $message Meddelande att logga
     * @param array $context Extra kontext
     */
    public function debug($message, $context = [])
    {
        $this->log($message, 'debug', $context);
    }
    
    /**
     * Hämta loggposter för en viss dag
     * 
     * @param string $date Datum i formatet YYYY-MM-DD
     * @param string $level Filter på loggnivå
     * @return array Loggposter
     */
    public function getLogEntries($date = null, $level = null)
    {
        // Används aktuellt datum om inget anges
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $logFile = $this->logPath . $this->category . '_' . $date . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $entries = [];
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Analysera loggposten
            if (preg_match('/^\[(.*?)\] \[(.*?)\] (.*)$/', $line, $matches)) {
                $entryLevel = strtolower($matches[2]);
                
                // Filtrera på nivå om det anges
                if ($level !== null && $entryLevel !== strtolower($level)) {
                    continue;
                }
                
                $entries[] = [
                    'timestamp' => $matches[1],
                    'level' => $entryLevel,
                    'message' => $matches[3]
                ];
            }
        }
        
        return $entries;
    }
    
    /**
     * Rensa gamla loggfiler
     * 
     * @param int $days Antal dagar att behålla
     * @return int Antal borttagna filer
     */
    public function cleanOldLogs($days = 30)
    {
        $count = 0;
        $cutoffDate = new \DateTime();
        $cutoffDate->modify('-' . $days . ' days');
        $cutoffTimestamp = $cutoffDate->getTimestamp();
        
        $logFiles = glob($this->logPath . '*.log');
        
        foreach ($logFiles as $file) {
            $fileTimestamp = filemtime($file);
            if ($fileTimestamp < $cutoffTimestamp) {
                if (unlink($file)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
}