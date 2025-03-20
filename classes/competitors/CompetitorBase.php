<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace ArtPriceMatcher\Competitors;

/**
 * CompetitorBase-klass för PriceMatcher-modulen
 * Basklass för alla konkurrentklasser
 */
abstract class CompetitorBase
{
    /**
     * Standardisera konkurrenspriser och spara dem i en CSV-fil
     * 
     * @return string Sökväg till standardiserad prisfil
     */
    abstract public function standardize();
    
    /**
     * Ladda ner prisuppgifter från konkurrentens webbplats/API
     * 
     * @return mixed Rådata från konkurrenten
     */
    abstract public function downloadPriceFile();
    
    /**
     * Validera CSV-fil för att se om den är kompatibel med systemet
     * 
     * @param string $filePath Sökväg till CSV-filen
     * @return bool True om filen är giltig
     */
    protected function validateFile($filePath)
    {
        // Kontrollera att filen existerar
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Öppna filen och kontrollera headers
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return false;
        }
        
        // Läs första raden som bör innehålla headers
        $headers = fgetcsv($handle);
        fclose($handle);
        
        // Kontrollera att nödvändiga kolumner finns
        $requiredColumns = ['sku', 'ean', 'competitor_price'];
        $missingColumns = array_diff($requiredColumns, $headers);
        
        if (count($missingColumns) > 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Skapa en tom CSV-fil med standardheaders
     * 
     * @param string $fileName Filnamn för CSV-filen
     * @return string Sökväg till den skapade filen
     */
    protected function createEmptyCSV($fileName)
    {
        $outputFile = _PS_MODULE_DIR_ . 'art_pricematcher/price_files/competitors_files/' . $fileName;
        
        $headers = [
            'sku',
            'ean',
            'competitor_price',
            'url'
        ];
        
        // Skapa filen med headers
        $fp = fopen($outputFile, 'w');
        fputcsv($fp, $headers);
        fclose($fp);
        
        return $outputFile;
    }
    
    /**
     * Lägg till en rad i CSV-filen
     * 
     * @param string $filePath Sökväg till CSV-filen
     * @param array $data Data att lägga till
     * @return bool True om det lyckades
     */
    protected function addRowToCSV($filePath, $data)
    {
        // Öppna filen i append-läge
        $fp = fopen($filePath, 'a');
        if (!$fp) {
            return false;
        }
        
        $result = fputcsv($fp, $data);
        fclose($fp);
        
        return ($result !== false);
    }
    
    /**
     * Utför en HTTP-förfrågan
     * 
     * @param string $url URL att anropa
     * @param string $method HTTP-metod (GET, POST, etc.)
     * @param array $headers HTTP-headers att skicka
     * @param string $data Data att skicka i förfrågan
     * @return string|false Svar eller false vid fel
     */
    protected function makeRequest($url, $method = 'GET', $headers = [], $data = null)
    {
        $curl = curl_init();
        
        // Ställ in cURL-alternativ
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        
        // Ställ in HTTP-metod
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        } else if ($method != 'GET') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        // Ställ in headers
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        
        // Utför förfrågan
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            return false;
        }
        
        return $response;
    }
    
    /**
     * Hjälpmetod för att logga information
     * 
     * @param string $message Meddelande att logga
     * @param string $level Loggnivå (info, warning, error)
     * @return void
     */
    protected function log($message, $level = 'info')
    {
        // Skapa loggsökväg
        $logFile = _PS_MODULE_DIR_ . 'art_pricematcher/logs/' . date('Y-m-d') . '_competitor.log';
        
        // Formatera loggmeddelande
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($level), $message);
        
        // Skriv till loggfil
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}