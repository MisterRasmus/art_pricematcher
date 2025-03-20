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
use Currency;
use DateTime;
use DateInterval;
use PrestaShopException;

/**
 * Active_Discounts-klass för PriceMatcher-modulen
 * Hanterar visning och hantering av aktiva rabatter
 */
class Active_Discounts
{
    /** @var \Module */
    private $module;
    
    /** @var \Context */
    private $context;
    
    /** @var int Antal rabatter per sida */
    private $items_per_page = 25;
    
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
     * Huvudmetod för att rendera innehåll för aktiva rabatter
     * 
     * @return string HTML-innehåll för aktiva rabatter
     */
    public function render()
    {
        // Hämta aktuell sida från URL
        $page = (int)Tools::getValue('page', 1);
        if ($page < 1) {
            $page = 1;
        }
        
        // Hämta alla aktiva konkurrenter
        $competitors = $this->getCompetitors();
        
        // Filtreringsparametrar
        $competitor = Tools::getValue('competitor', '');
        $search = Tools::getValue('search', '');
        
        // Hämta aktiva rabatter med paginering
        $activeDiscounts = $this->getActiveDiscounts($page, $this->items_per_page, $competitor, $search);
        
        // Beräkna paginering
        $totalDiscounts = $this->getActiveDiscountsCount($competitor, $search);
        $totalPages = ceil($totalDiscounts / $this->items_per_page);
        
        // Skapa pagination-länk
        $paginationLink = $this->context->link->getAdminLink('AdminPriceMatcher') . '&tab=active_discounts';
        
        // Lägg till konkurrent-filter i pagination-länk om det finns
        if (!empty($competitor)) {
            $paginationLink .= '&competitor=' . urlencode($competitor);
        }
        
        // Lägg till sökterm i pagination-länk om det finns
        if (!empty($search)) {
            $paginationLink .= '&search=' . urlencode($search);
        }
        
        // Förbered pagination-data
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => $totalDiscounts,
            'start' => ($page - 1) * $this->items_per_page + 1,
            'end' => min($page * $this->items_per_page, $totalDiscounts),
            'pagination_link' => $paginationLink
        ];
        
        // Tilldela variabler till Smarty
        $this->context->smarty->assign([
            'active_discounts' => $activeDiscounts,
            'competitors' => $competitors,
            'pagination' => $pagination
        ]);
        
        // Rendera mallen
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/active_discounts.tpl');
    }
    
    /**
     * Hämta alla aktiva konkurrenter
     * 
     * @return array Lista över aktiva konkurrenter
     */
    private function getCompetitors()
    {
        $competitors = [];
        
        $result = Db::getInstance()->executeS('
            SELECT * FROM `' . _DB_PREFIX_ . 'art_pricematcher_competitors`
            WHERE active = 1
            ORDER BY name ASC
        ');
        
        if ($result && is_array($result)) {
            $competitors = $result;
        }
        
        return $competitors;
    }
    
    /**
     * Hämta aktiva rabatter med paginering och filtrering
     * 
     * @param int $page Aktuell sida
     * @param int $limit Antal rabatter per sida
     * @param string $competitor Filtrering på konkurrent
     * @param string $search Sökterm för produkt eller referens
     * @return array Lista över aktiva rabatter
     */
    public function getActiveDiscounts($page = 1, $limit = 25, $competitor = '', $search = '')
    {
        // Beräkna offset för paginering
        $offset = ($page - 1) * $limit;
        
        // Skapa SQL-frågan med JOIN för att hämta produktinformation
        $sql = '
            SELECT 
                ad.*, 
                p.reference, 
                COALESCE(pl.name, "Unknown Product") as product_name,
                c.name as competitor_name
            FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` ad
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (ad.id_product = p.id_product)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (ad.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'art_pricematcher_competitors` c ON (ad.id_competitor = c.id_competitor)
            WHERE 1 ';
        
        // Lägg till filter för konkurrent om det finns
        if (!empty($competitor)) {
            $sql .= ' AND c.name = "' . pSQL($competitor) . '"';
        }
        
        // Lägg till sökterm om det finns
        if (!empty($search)) {
            $sql .= ' AND (pl.name LIKE "%' . pSQL($search) . '%" OR p.reference LIKE "%' . pSQL($search) . '%")';
        }
        
        // Lägga till sortering
        $sql .= ' ORDER BY ad.date_expiration ASC';
        
        // Lägga till paginering
        $sql .= ' LIMIT ' . (int)$offset . ', ' . (int)$limit;
        
        $result = Db::getInstance()->executeS($sql);
        $discounts = [];
        
        if ($result && is_array($result)) {
            // Hämta valutainformation för att formatera priser
            $currency = new Currency($this->context->currency->id);
            $currencySign = $currency->sign;
            
            // Aktuellt datum för att beräkna dagar som är kvar
            $currentDate = new DateTime();
            
            foreach ($result as $row) {
                // Formatera priser
                $originalPrice = (float)$row['regular_price'];
                $competitorPrice = (float)$row['competitor_price'];
                $newPrice = (float)$row['discount_price'];
                
                // Beräkna rabatt i procent
                $discountPercent = 0;
                if ($originalPrice > 0) {
                    $discountPercent = round(($originalPrice - $newPrice) / $originalPrice * 100, 2);
                }
                
                // Formatera datum
                $dateAdd = new DateTime($row['date_add']);
                $dateExpiration = new DateTime($row['date_expiration']);
                
                // Beräkna dagar kvar till utgång
                $daysLeft = 0;
                if ($dateExpiration > $currentDate) {
                    $interval = $currentDate->diff($dateExpiration);
                    $daysLeft = $interval->days;
                }
                
                // Förbered radens data
                $discounts[] = [
                    'id_active_discount' => $row['id_active_discount'],
                    'id_product' => $row['id_product'],
                    'id_competitor' => $row['id_competitor'],
                    'product_name' => $row['product_name'],
                    'reference' => $row['reference'],
                    'competitor_name' => $row['competitor_name'],
                    'original_price' => $originalPrice,
                    'original_price_formatted' => $currencySign . number_format($originalPrice, 2),
                    'competitor_price' => $competitorPrice,
                    'competitor_price_formatted' => $currencySign . number_format($competitorPrice, 2),
                    'new_price' => $newPrice,
                    'new_price_formatted' => $currencySign . number_format($newPrice, 2),
                    'discount_percent' => $discountPercent,
                    'discount_formatted' => $discountPercent . '%',
                    'date_add' => $row['date_add'],
                    'date_add_timestamp' => $dateAdd->getTimestamp(),
                    'date_add_formatted' => $dateAdd->format('Y-m-d H:i'),
                    'date_expiration' => $row['date_expiration'],
                    'date_expiration_timestamp' => $dateExpiration->getTimestamp(),
                    'date_expiration_formatted' => $dateExpiration->format('Y-m-d'),
                    'days_left' => $daysLeft
                ];
            }
        }
        
        return $discounts;
    }
    
    /**
     * Hämta antal aktiva rabatter med filtrering
     * 
     * @param string $competitor Filtrering på konkurrent
     * @param string $search Sökterm för produkt eller referens
     * @return int Antal aktiva rabatter
     */
    private function getActiveDiscountsCount($competitor = '', $search = '')
    {
        $sql = '
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` ad
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (ad.id_product = p.id_product)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (ad.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'art_pricematcher_competitors` c ON (ad.id_competitor = c.id_competitor)
            WHERE 1 ';
        
        // Lägg till filter för konkurrent om det finns
        if (!empty($competitor)) {
            $sql .= ' AND c.name = "' . pSQL($competitor) . '"';
        }
        
        // Lägg till sökterm om det finns
        if (!empty($search)) {
            $sql .= ' AND (pl.name LIKE "%' . pSQL($search) . '%" OR p.reference LIKE "%' . pSQL($search) . '%")';
        }
        
        return (int)Db::getInstance()->getValue($sql);
    }
    
    /**
     * Ta bort en aktiv rabatt
     * 
     * @param int $id_active_discount ID för den aktiva rabatten
     * @return bool|string true om lyckad, felmeddelande annars
     */
    private function removeDiscount($id_active_discount)
    {
        try {
            // Hämta rabattinformation
            $discount = Db::getInstance()->getRow('
                SELECT * FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` 
                WHERE `id_active_discount` = ' . (int)$id_active_discount
            );
            
            if (!$discount) {
                return $this->module->l('Discount not found', 'Active_Discounts');
            }
            
            // Hämta specifik rabatt-ID
            $id_specific_price = (int)$discount['id_specific_price'];
            
            // Ta bort specific price från Prestashop
            if ($id_specific_price > 0) {
                $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'specific_price` WHERE id_specific_price = ' . $id_specific_price;
                Db::getInstance()->execute($sql);
            }
            
            // Ta bort från active_discounts-tabellen
            $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` WHERE id_active_discount = ' . (int)$id_active_discount;
            Db::getInstance()->execute($sql);
            
            // Logga statistik om det behövs
            // Exempel: $this->logDiscountRemoved($discount);
            
            return true;
            
        } catch (PrestaShopException $e) {
            return $e->getMessage();
        }
    }
    
    /**
     * Förlänga en aktiv rabatt med ett antal dagar
     * 
     * @param int $id_active_discount ID för den aktiva rabatten
     * @param int $days Antal dagar att förlänga
     * @return bool|string true om lyckad, felmeddelande annars
     */
    private function extendDiscount($id_active_discount, $days = 7)
    {
        try {
            // Hämta rabattinformation
            $discount = Db::getInstance()->getRow('
                SELECT * FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` 
                WHERE `id_active_discount` = ' . (int)$id_active_discount
            );
            
            if (!$discount) {
                return $this->module->l('Discount not found', 'Active_Discounts');
            }
            
            // Hämta specific price-ID
            $id_specific_price = (int)$discount['id_specific_price'];
            
            // Beräkna nytt utgångsdatum
            $expirationDate = new DateTime($discount['date_expiration']);
            $expirationDate->add(new DateInterval('P' . (int)$days . 'D'));
            $newExpirationDate = $expirationDate->format('Y-m-d 23:59:59');
            
            // Uppdatera i active_discounts-tabellen
            $sql = '
                UPDATE `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` 
                SET `date_expiration` = "' . pSQL($newExpirationDate) . '"
                WHERE `id_active_discount` = ' . (int)$id_active_discount;
            Db::getInstance()->execute($sql);
            
            // Uppdatera i specific_price-tabellen om det behövs
            if ($id_specific_price > 0) {
                $sql = '
                    UPDATE `' . _DB_PREFIX_ . 'specific_price` 
                    SET `to` = "' . pSQL($newExpirationDate) . '"
                    WHERE `id_specific_price` = ' . $id_specific_price;
                Db::getInstance()->execute($sql);
            }
            
            return true;
            
        } catch (PrestaShopException $e) {
            return $e->getMessage();
        }
    }
    
    /**
     * Hantera AJAX-anrop för aktiva rabatter
     * 
     * @param string $action Åtgärden som ska utföras
     * @return array Resultat som ska returneras som JSON
     */
    public function handleAjax($action)
    {
        $result = [
            'success' => false,
            'message' => $this->module->l('Invalid action', 'Active_Discounts')
        ];
        
        switch ($action) {
            case 'removeDiscount':
                $id_active_discount = (int)Tools::getValue('id_discount');
                
                if ($id_active_discount > 0) {
                    $removeResult = $this->removeDiscount($id_active_discount);
                    
                    if ($removeResult === true) {
                        $result = [
                            'success' => true,
                            'message' => $this->module->l('Discount successfully removed', 'Active_Discounts')
                        ];
                    } else {
                        $result = [
                            'success' => false,
                            'message' => $removeResult
                        ];
                    }
                }
                break;
                
            case 'extendDiscount':
                $id_active_discount = (int)Tools::getValue('id_discount');
                $days = (int)Tools::getValue('days', 7);
                
                if ($id_active_discount > 0) {
                    $extendResult = $this->extendDiscount($id_active_discount, $days);
                    
                    if ($extendResult === true) {
                        $result = [
                            'success' => true,
                            'message' => sprintf($this->module->l('Discount extended by %d days', 'Active_Discounts'), $days)
                        ];
                    } else {
                        $result = [
                            'success' => false,
                            'message' => $extendResult
                        ];
                    }
                }
                break;
                
            case 'getFilteredDiscounts':
                $page = (int)Tools::getValue('page', 1);
                $competitor = Tools::getValue('competitor', '');
                $search = Tools::getValue('search', '');
                
                $discounts = $this->getActiveDiscounts($page, $this->items_per_page, $competitor, $search);
                $totalDiscounts = $this->getActiveDiscountsCount($competitor, $search);
                $totalPages = ceil($totalDiscounts / $this->items_per_page);
                
                $result = [
                    'success' => true,
                    'discounts' => $discounts,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total' => $totalDiscounts,
                        'start' => ($page - 1) * $this->items_per_page + 1,
                        'end' => min($page * $this->items_per_page, $totalDiscounts)
                    ]
                ];
                break;
        }
        
        return $result;
    }
    
    /**
     * Bearbeta formulärisändningar för aktiva rabatter
     * 
     * @return bool|array true om lyckad, array med fel annars
     */
    public function processForm()
    {
        // Active_Discounts har inget eget formulär, så denna metod behöver inte implementeras
        return true;
    }
}