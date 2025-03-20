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
use Category;
use DateTime;
use DateInterval;
use PrestaShopException;

/**
 * Statistics-klass för PriceMatcher-modulen
 * Hanterar statistik och analyser
 */
class Statistics
{
    /** @var \Module */
    private $module;
    
    /** @var \Context */
    private $context;
    
    /** @var array */
    private $errors = [];
    
    /** @var int Antal operationer per sida */
    private $items_per_page = 15;
    
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
     * Huvudmetod för att rendera statistik
     * 
     * @return string HTML-innehåll för statistik
     */
    public function render()
    {
        // Hämta allmän statistik
        $statistics = $this->getGeneralStatistics();
        
        // Hämta konkurrenter
        $competitors = $this->getCompetitors();
        
        // Hämta konkurrentstatistik
        $competitorStats = $this->getCompetitorStatistics();
        
        // Hämta senaste operationer
        $currentPage = (int)Tools::getValue('page', 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        
        $operations = $this->getRecentOperations($currentPage, $this->items_per_page);
        
        // Skapa paginering
        $totalOperations = $this->getOperationsCount();
        $totalPages = ceil($totalOperations / $this->items_per_page);
        
        $paginationLink = $this->context->link->getAdminLink('AdminPriceMatcherController', true, [], [
            'tab' => 'statistics'
        ]);
        
        $pagination = [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total' => $totalOperations,
            'start' => ($currentPage - 1) * $this->items_per_page + 1,
            'end' => min($currentPage * $this->items_per_page, $totalOperations),
            'pagination_link' => $paginationLink
        ];
        
        // Ladda JavaScript-översättningar
        $this->loadJSTranslations();
        
        // Tilldela till Smarty
        $this->context->smarty->assign([
            'statistics' => $statistics,
            'competitors' => $competitors,
            'competitor_stats' => $competitorStats,
            'operations' => $operations,
            'pagination' => $pagination
        ]);
        
        // Rendera mallen
        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/statistics.tpl');
    }
    
    /**
     * Hämta allmän statistik
     * 
     * @return array Statistik
     */
    private function getGeneralStatistics()
    {
        $statistics = [];
        
        // Totalt antal produkter
        $statistics['total_products'] = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product` WHERE active = 1'
        );
        
        // Antal jämförda produkter
        $statistics['total_compared'] = Db::getInstance()->getValue(
            'SELECT COUNT(DISTINCT id_product) FROM `' . _DB_PREFIX_ . 'art_pricematcher`'
        );
        
        // Antal uppdaterade produkter
        $statistics['total_updated'] = Db::getInstance()->getValue(
            'SELECT COUNT(DISTINCT id_product) FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
            WHERE new_price IS NOT NULL AND new_price > 0'
        );
        
        // Antal aktiva rabatter
        $statistics['active_discounts'] = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts`'
        );
        
        return $statistics;
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
     * Hämta konkurrentstatistik
     * 
     * @return array Konkurrentstatistik
     */
    private function getCompetitorStatistics()
    {
        $stats = [];
        
        // Hämta konkurrenter
        $competitors = $this->getCompetitors();
        
        if (empty($competitors)) {
            return $stats;
        }
        
        foreach ($competitors as $competitor) {
            // Hämta statistik för denna konkurrent
            $id_competitor = (int)$competitor['id_competitor'];
            
            // Totalt antal produkter i senaste jämförelsen
            $totalProducts = Db::getInstance()->getValue('
                SELECT COUNT(*) 
                FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
                WHERE `id_competitor` = ' . $id_competitor
            );
            
            // Antal matchade produkter
            $productsMatched = Db::getInstance()->getValue('
                SELECT COUNT(*) 
                FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
                WHERE `id_competitor` = ' . $id_competitor . ' 
                AND `our_price` IS NOT NULL
            ');
            
            // Beräkna matchningsfrekvens
            $matchRate = 0;
            if ($totalProducts > 0) {
                $matchRate = round(($productsMatched / $totalProducts) * 100, 1);
            }
            
            // Antal produkter med lägre konkurrentpris
            $lowerPrices = Db::getInstance()->getValue('
                SELECT COUNT(*) 
                FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
                WHERE `id_competitor` = ' . $id_competitor . ' 
                AND `our_price` > `competitor_price` 
                AND `our_price` IS NOT NULL 
                AND `competitor_price` IS NOT NULL
            ');
            
            // Antal produkter med högre konkurrentpris
            $higherPrices = Db::getInstance()->getValue('
                SELECT COUNT(*) 
                FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
                WHERE `id_competitor` = ' . $id_competitor . ' 
                AND `our_price` < `competitor_price` 
                AND `our_price` IS NOT NULL 
                AND `competitor_price` IS NOT NULL
            ');
            
            // Beräkna genomsnittlig prisavvikelse
            $avgPriceDiff = Db::getInstance()->getValue('
                SELECT AVG((our_price - competitor_price) / our_price * 100) 
                FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
                WHERE `id_competitor` = ' . $id_competitor . ' 
                AND `our_price` IS NOT NULL 
                AND `competitor_price` IS NOT NULL 
                AND `our_price` > 0
            ');
            
            // Lägg till i statistikarrayen
            $stats[] = [
                'id_competitor' => $id_competitor,
                'name' => $competitor['name'],
                'total_products' => $totalProducts,
                'products_matched' => $productsMatched,
                'match_rate' => $matchRate,
                'lower_prices' => $lowerPrices,
                'higher_prices' => $higherPrices,
                'avg_price_diff' => $avgPriceDiff
            ];
        }
        
        return $stats;
    }
    
    /**
     * Hämta senaste operationer med paginering
     * 
     * @param int $page Aktuell sida
     * @param int $limit Antal per sida
     * @return array Operationer
     */
    private function getRecentOperations($page = 1, $limit = 15)
    {
        $operations = [];
        
        // Beräkna offset för paginering
        $offset = ($page - 1) * $limit;
        
        // Hämta operationer
        $result = Db::getInstance()->executeS('
            SELECT s.*, c.name as competitor_name
            FROM `' . _DB_PREFIX_ . 'art_pricematcher_statistics` s
            LEFT JOIN `' . _DB_PREFIX_ . 'art_pricematcher_competitors` c 
                ON s.id_competitor = c.id_competitor
            ORDER BY s.execution_date DESC
            LIMIT ' . (int)$offset . ', ' . (int)$limit
        );
        
        if ($result && is_array($result)) {
            $operations = $result;
        }
        
        return $operations;
    }
    
    /**
     * Hämta totalt antal operationer
     * 
     * @return int Antal operationer
     */
    private function getOperationsCount()
    {
        return (int)Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'art_pricematcher_statistics`
        ');
    }
    
    /**
     * Hantera AJAX-anrop för statistikfliken
     * 
     * @param string $action Åtgärden som ska utföras
     * @return array Resultat som ska returneras som JSON
     */
    public function handleAjax($action)
    {
        $result = [
            'success' => false,
            'message' => $this->module->l('Invalid action', 'Statistics')
        ];
        
        switch ($action) {
            case 'getPriceUpdateTrend':
                $days = (int)Tools::getValue('days', 30);
                $trend = $this->getPriceUpdateTrend($days);
                
                $result = [
                    'success' => true,
                    'data' => $trend
                ];
                break;
                
            case 'getDiscountDistribution':
                $distribution = $this->getDiscountDistribution();
                
                $result = [
                    'success' => true,
                    'data' => $distribution
                ];
                break;
                
            case 'getCompetitorComparison':
                $comparison = $this->getCompetitorComparison();
                
                $result = [
                    'success' => true,
                    'data' => $comparison
                ];
                break;
                
            case 'getCategoryDiscounts':
                $categoryDiscounts = $this->getCategoryDiscounts();
                
                $result = [
                    'success' => true,
                    'data' => $categoryDiscounts
                ];
                break;
                
            case 'getFilteredOperations':
                $operationType = Tools::getValue('operation_type', '');
                $competitorId = (int)Tools::getValue('competitor_id', 0);
                $dateRange = Tools::getValue('date_range', '');
                $page = (int)Tools::getValue('page', 1);
                
                $operations = $this->getFilteredOperations($operationType, $competitorId, $dateRange, $page, $this->items_per_page);
                $totalOperations = $this->getFilteredOperationsCount($operationType, $competitorId, $dateRange);
                $totalPages = ceil($totalOperations / $this->items_per_page);
                
                $paginationLink = $this->context->link->getAdminLink('AdminPriceMatcherController', true, [], [
                    'tab' => 'statistics'
                ]);
                
                $pagination = [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total' => $totalOperations,
                    'start' => ($page - 1) * $this->items_per_page + 1,
                    'end' => min($page * $this->items_per_page, $totalOperations),
                    'pagination_link' => $paginationLink
                ];
                
                $result = [
                    'success' => true,
                    'data' => [
                        'operations' => $operations,
                        'pagination' => $pagination
                    ]
                ];
                break;
                
            case 'getCompetitorDetails':
                $competitorId = (int)Tools::getValue('competitor_id', 0);
                
                if ($competitorId > 0) {
                    $details = $this->getCompetitorDetails($competitorId);
                    
                    $result = [
                        'success' => true,
                        'data' => $details
                    ];
                } else {
                    $result = [
                        'success' => false,
                        'message' => $this->module->l('Invalid competitor ID', 'Statistics')
                    ];
                }
                break;
                
            case 'getOperationDetails':
                $operationId = (int)Tools::getValue('operation_id', 0);
                
                if ($operationId > 0) {
                    $details = $this->getOperationDetails($operationId);
                    
                    $result = [
                        'success' => true,
                        'data' => $details
                    ];
                } else {
                    $result = [
                        'success' => false,
                        'message' => $this->module->l('Invalid operation ID', 'Statistics')
                    ];
                }
                break;
        }
        
        return $result;
    }
    
    /**
     * Hämta prisupdateringstrender för ett antal dagar
     * 
     * @param int $days Antal dagar att visa
     * @return array Trenddata
     */
    private function getPriceUpdateTrend($days = 30)
    {
        $trend = [
            'dates' => [],
            'updates' => [],
            'active_discounts' => []
        ];
        
        // Skapa datum för alla dagar
        $endDate = new DateTime();
        $startDate = new DateTime();
        $startDate->sub(new DateInterval('P' . $days . 'D'));
        
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $trend['dates'][] = $dateStr;
            
            // Hämta antalet uppdateringar för denna dag
            $updates = Db::getInstance()->getValue('
                SELECT SUM(total_products) 
                FROM `' . _DB_PREFIX_ . 'art_pricematcher_statistics` 
                WHERE DATE(execution_date) = "' . pSQL($dateStr) . '" 
                AND operation_type = "update"
            ');
            
            $trend['updates'][] = (int)$updates;
            
            // Hämta antalet aktiva rabatter för denna dag
            $activeDiscounts = Db::getInstance()->getValue('
                SELECT COUNT(*) 
                FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` 
                WHERE date_add <= "' . pSQL($dateStr . ' 23:59:59') . '" 
                AND date_expiration >= "' . pSQL($dateStr . ' 00:00:00') . '"
            ');
            
            $trend['active_discounts'][] = (int)$activeDiscounts;
            
            $currentDate->add(new DateInterval('P1D'));
        }
        
        return $trend;
    }
    
    /**
     * Hämta rabattdistributionsdata
     * 
     * @return array Distributionsdata
     */
    private function getDiscountDistribution()
    {
        $distribution = [
            'ranges' => ['0-5%', '5-10%', '10-15%', '15-20%', '20-25%', '>25%'],
            'counts' => []
        ];
        
        // Hämta antal rabatter i varje intervall
        $ranges = [
            [0, 5],
            [5, 10],
            [10, 15],
            [15, 20],
            [20, 25],
            [25, 100]
        ];
        
        foreach ($ranges as $range) {
            $count = Db::getInstance()->getValue('
                SELECT COUNT(*) 
                FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` 
                WHERE ((regular_price - discount_price) / regular_price * 100) >= ' . (float)$range[0] . ' 
                AND ((regular_price - discount_price) / regular_price * 100) < ' . (float)$range[1] . '
            ');
            
            $distribution['counts'][] = (int)$count;
        }
        
        return $distribution;
    }
    
    /**
     * Hämta konkurrentjämförelsedata
     * 
     * @return array Jämförelsedata
     */
    private function getCompetitorComparison()
    {
        $comparison = [
            'competitors' => [],
            'match_rates' => [],
            'price_diffs' => []
        ];
        
        // Hämta konkurrentstatistik
        $competitorStats = $this->getCompetitorStatistics();
        
        foreach ($competitorStats as $stat) {
            $comparison['competitors'][] = $stat['name'];
            $comparison['match_rates'][] = $stat['match_rate'];
            $comparison['price_diffs'][] = $stat['avg_price_diff'];
        }
        
        return $comparison;
    }
    
    /**
     * Hämta kategorirabatter
     * 
     * @return array Kategorirabatter
     */
    private function getCategoryDiscounts()
    {
        $categoryDiscounts = [
            'categories' => [],
            'discount_counts' => [],
            'avg_discounts' => []
        ];
        
        // Hämta toppkategorier med rabatter
        $query = '
            SELECT c.id_category, cl.name, 
                COUNT(DISTINCT ad.id_product) as product_count,
                AVG((ad.regular_price - ad.discount_price) / ad.regular_price * 100) as avg_discount
            FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` ad
            LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON ad.id_product = cp.id_product
            LEFT JOIN `' . _DB_PREFIX_ . 'category` c ON cp.id_category = c.id_category
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.id_category = cl.id_category 
                AND cl.id_lang = ' . (int)$this->context->language->id . '
            WHERE c.active = 1
            GROUP BY c.id_category
            ORDER BY product_count DESC
            LIMIT 10
        ';
        
        $result = Db::getInstance()->executeS($query);
        
        if ($result && is_array($result)) {
            foreach ($result as $row) {
                $categoryDiscounts['categories'][] = $row['name'];
                $categoryDiscounts['discount_counts'][] = (int)$row['product_count'];
                $categoryDiscounts['avg_discounts'][] = round((float)$row['avg_discount'], 1);
            }
        }
        
        return $categoryDiscounts;
    }
    
    /**
     * Hämta filtrerade operationer
     * 
     * @param string $operationType Operationstyp
     * @param int $competitorId Konkurrent-ID
     * @param string $dateRange Datumintervall
     * @param int $page Aktuell sida
     * @param int $limit Antal per sida
     * @return array Operationer
     */
    private function getFilteredOperations($operationType = '', $competitorId = 0, $dateRange = '', $page = 1, $limit = 15)
    {
        $operations = [];
        
        // Skapa WHERE-villkor
        $whereConditions = [];
        $whereSql = '';
        
        if (!empty($operationType)) {
            $whereConditions[] = 's.operation_type = "' . pSQL($operationType) . '"';
        }
        
        if ($competitorId > 0) {
            $whereConditions[] = 's.id_competitor = ' . (int)$competitorId;
        }
        
        if (!empty($dateRange)) {
            $dates = explode(' - ', $dateRange);
            if (count($dates) == 2) {
                $startDate = $dates[0];
                $endDate = $dates[1];
                $whereConditions[] = 'DATE(s.execution_date) BETWEEN "' . pSQL($startDate) . '" AND "' . pSQL($endDate) . '"';
            }
        }
        
        if (!empty($whereConditions)) {
            $whereSql = ' WHERE ' . implode(' AND ', $whereConditions);
        }
        
        // Beräkna offset för paginering
        $offset = ($page - 1) * $limit;
        
        // Hämta operationer
        $result = Db::getInstance()->executeS('
            SELECT s.*, c.name as competitor_name
            FROM `' . _DB_PREFIX_ . 'art_pricematcher_statistics` s
            LEFT JOIN `' . _DB_PREFIX_ . 'art_pricematcher_competitors` c 
                ON s.id_competitor = c.id_competitor
            ' . $whereSql . '
            ORDER BY s.execution_date DESC
            LIMIT ' . (int)$offset . ', ' . (int)$limit
        );
        
        if ($result && is_array($result)) {
            $operations = $result;
        }
        
        return $operations;
    }
    
    /**
     * Hämta antal filtrerade operationer
     * 
     * @param string $operationType Operationstyp
     * @param int $competitorId Konkurrent-ID
     * @param string $dateRange Datumintervall
     * @return int Antal operationer
     */
    private function getFilteredOperationsCount($operationType = '', $competitorId = 0, $dateRange = '')
    {
        // Skapa WHERE-villkor
        $whereConditions = [];
        $whereSql = '';
        
        if (!empty($operationType)) {
            $whereConditions[] = 'operation_type = "' . pSQL($operationType) . '"';
        }
        
        if ($competitorId > 0) {
            $whereConditions[] = 'id_competitor = ' . (int)$competitorId;
        }
        
        if (!empty($dateRange)) {
            $dates = explode(' - ', $dateRange);
            if (count($dates) == 2) {
                $startDate = $dates[0];
                $endDate = $dates[1];
                $whereConditions[] = 'DATE(execution_date) BETWEEN "' . pSQL($startDate) . '" AND "' . pSQL($endDate) . '"';
            }
        }
        
        if (!empty($whereConditions)) {
            $whereSql = ' WHERE ' . implode(' AND ', $whereConditions);
        }
        
        return (int)Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'art_pricematcher_statistics`
            ' . $whereSql
        );
    }
    
    /**
     * Hämta detaljerad konkurrentinformation
     * 
     * @param int $competitorId Konkurrent-ID
     * @return array Konkurrentdetaljer
     */
    private function getCompetitorDetails($competitorId)
    {
        $details = [];
        
        // Hämta grundläggande konkurrentinformation
        $competitor = Db::getInstance()->getRow('
            SELECT * FROM `' . _DB_PREFIX_ . 'art_pricematcher_competitors`
            WHERE id_competitor = ' . (int)$competitorId
        );
        
        if (!$competitor) {
            return $details;
        }
        
        // Grundläggande information
        $details['name'] = $competitor['name'];
        
        // Hämta totalt antal produkter
        $details['total_products'] = Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
            WHERE id_competitor = ' . (int)$competitorId
        );
        
        // Hämta totalt antal matchade produkter
        $productsMatched = Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
            WHERE id_competitor = ' . (int)$competitorId . ' 
            AND our_price IS NOT NULL
        ');
        
        // Beräkna matchningsfrekvens
        $details['match_rate'] = 0;
        if ($details['total_products'] > 0) {
            $details['match_rate'] = round(($productsMatched / $details['total_products']) * 100, 1);
        }
        
        // Hämta senaste uppdateringen
        $details['last_update'] = Db::getInstance()->getValue('
            SELECT execution_date 
            FROM `' . _DB_PREFIX_ . 'art_pricematcher_statistics` 
            WHERE id_competitor = ' . (int)$competitorId . ' 
            AND operation_type = "update"
            ORDER BY execution_date DESC
            LIMIT 1
        ');
        
        // Hämta antal produkter med lägre/högre/samma priser
        $details['lower_prices'] = Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
            WHERE id_competitor = ' . (int)$competitorId . ' 
            AND our_price > competitor_price 
            AND our_price IS NOT NULL 
            AND competitor_price IS NOT NULL
        ');
        
        $details['higher_prices'] = Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
            WHERE id_competitor = ' . (int)$competitorId . ' 
            AND our_price < competitor_price 
            AND our_price IS NOT NULL 
            AND competitor_price IS NOT NULL
        ');
        
        $details['same_prices'] = Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `' . _DB_PREFIX_ . 'art_pricematcher` 
            WHERE id_competitor = ' . (int)$competitorId . ' 
            AND our_price = competitor_price 
            AND our_price IS NOT NULL 
            AND competitor_price IS NOT NULL
        ');
        
        // Hämta trenddata för de senaste 30 dagarna
        $details['trend'] = [
            'dates' => [],
            'matches' => [],
            'price_diffs' => []
        ];
        
        // Skapa datum för alla dagar
        $endDate = new DateTime();
        $startDate = new DateTime();
        $startDate->sub(new DateInterval('P30D'));
        
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $details['trend']['dates'][] = $dateStr;
            
            // Hämta matchande produkter för denna dag
            $matches = Db::getInstance()->getValue('
                SELECT COUNT(DISTINCT id_product) 
                FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` 
                WHERE id_competitor = ' . (int)$competitorId . ' 
                AND date_add <= "' . pSQL($dateStr . ' 23:59:59') . '" 
                AND date_expiration >= "' . pSQL($dateStr . ' 00:00:00') . '"
            ');
            
            $details['trend']['matches'][] = (int)$matches;
            
            // Hämta genomsnittlig prisavvikelse för denna dag
            $priceDiff = Db::getInstance()->getValue('
                SELECT AVG((regular_price - discount_price) / regular_price * 100) 
                FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` 
                WHERE id_competitor = ' . (int)$competitorId . ' 
                AND date_add <= "' . pSQL($dateStr . ' 23:59:59') . '" 
                AND date_expiration >= "' . pSQL($dateStr . ' 00:00:00') . '"
            ');
            
            $details['trend']['price_diffs'][] = round((float)$priceDiff, 1);
            
            $currentDate->add(new DateInterval('P1D'));
        }
        
        // Hämta kategoridata
        $details['categories'] = [
            'names' => [],
            'counts' => []
        ];
        
        $categoryQuery = '
            SELECT c.id_category, cl.name, COUNT(DISTINCT ad.id_product) as product_count
            FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` ad
            LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON ad.id_product = cp.id_product
            LEFT JOIN `' . _DB_PREFIX_ . 'category` c ON cp.id_category = c.id_category
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.id_category = cl.id_category 
                AND cl.id_lang = ' . (int)$this->context->language->id . '
            WHERE ad.id_competitor = ' . (int)$competitorId . '
                AND c.active = 1
            GROUP BY c.id_category
            ORDER BY product_count DESC
            LIMIT 10
        ';
        
        $categoryResult = Db::getInstance()->executeS($categoryQuery);
        
        if ($categoryResult && is_array($categoryResult)) {
            foreach ($categoryResult as $category) {
                $details['categories']['names'][] = $category['name'];
                $details['categories']['counts'][] = (int)$category['product_count'];
            }
        }
        
        // Hämta topp 10 produkter
        $details['top_products'] = [];
        
        $topProductsQuery = '
            SELECT 
                p.id_product,
                COALESCE(pl.name, "Unknown Product") as name,
                cl.name as category,
                ad.regular_price as our_price,
                ad.competitor_price,
                ((ad.regular_price - ad.competitor_price) / ad.regular_price * 100) as difference,
                ad.date_add as last_updated
            FROM `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` ad
            LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON ad.id_product = p.id_product
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON ad.id_product = pl.id_product 
                AND pl.id_lang = ' . (int)$this->context->language->id . '
            LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON p.id_product = cp.id_product
            LEFT JOIN `' . _DB_PREFIX_ . 'category` c ON cp.id_category = c.id_category AND c.id_category != ' . (int)Category::getRootCategory()->id . '
            LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.id_category = cl.id_category 
                AND cl.id_lang = ' . (int)$this->context->language->id . '
            WHERE ad.id_competitor = ' . (int)$competitorId . '
            ORDER BY difference DESC
            LIMIT 10
        ';
        
        $topProductsResult = Db::getInstance()->executeS($topProductsQuery);
        
        if ($topProductsResult && is_array($topProductsResult)) {
            $currency = new \Currency($this->context->currency->id);
            $currencySign = $currency->sign;
            
            foreach ($topProductsResult as $product) {
                $details['top_products'][] = [
                    'name' => $product['name'],
                    'category' => $product['category'],
                    'our_price' => $currencySign . number_format((float)$product['our_price'], 2),
                    'competitor_price' => $currencySign . number_format((float)$product['competitor_price'], 2),
                    'difference' => number_format((float)$product['difference'], 2) . '%',
                    'last_updated' => date('Y-m-d H:i', strtotime($product['last_updated']))
                ];
            }
        }
        
        return $details;
    }
    
    /**
     * Hämta detaljerad operationsinformation
     * 
     * @param int $operationId Operations-ID
     * @return array Operationsdetaljer
     */
    private function getOperationDetails($operationId)
    {
        $details = [];
        
        // Hämta grundläggande operationsinformation
        $operation = Db::getInstance()->getRow('
            SELECT s.*, c.name as competitor_name
            FROM `' . _DB_PREFIX_ . 'art_pricematcher_statistics` s
            LEFT JOIN `' . _DB_PREFIX_ . 'art_pricematcher_competitors` c 
                ON s.id_competitor = c.id_competitor
            WHERE s.id_statistic = ' . (int)$operationId
        );
        
        if (!$operation) {
            return $details;
        }
        
        // Grundläggande information
        $details['operation_type'] = $operation['operation_type'];
        $details['competitor_name'] = $operation['competitor_name'];
        $details['execution_date'] = $operation['execution_date'];
        $details['execution_time'] = $operation['execution_time'];
        $details['initiated_by'] = $operation['initiated_by'];
        $details['products_processed'] = $operation['total_products'];
        $details['success_rate'] = round(($operation['success_count'] / $operation['total_products']) * 100, 1);
        
        // Resultatdata
        $details['results'] = [
            'success' => (int)$operation['success_count'],
            'failure' => (int)$operation['error_count'],
            'skipped' => (int)$operation['skipped_count']
        ];
        
        // Försök att hämta logg om det finns
        $logFile = _PS_MODULE_DIR_ . 'art_pricematcher/logs/' . date('Y-m-d', strtotime($operation['execution_date'])) . '_' . $operation['operation_type'] . '.log';
        
        if (file_exists($logFile)) {
            // Hitta loggposter relaterade till denna operation
            $log = file_get_contents($logFile);
            
            // Försök filtrera loggen baserat på timestamp
            $timestamp = date('Y-m-d H:i', strtotime($operation['execution_date']));
            
            // Dela upp loggen i rader
            $logLines = explode("\n", $log);
            $filteredLines = [];
            
            foreach ($logLines as $line) {
                if (strpos($line, $timestamp) !== false 
                    && strpos($line, $operation['competitor_name']) !== false
                    && strpos($line, strtoupper($operation['operation_type'])) !== false) {
                    $filteredLines[] = $line;
                }
            }
            
            if (!empty($filteredLines)) {
                $details['log'] = implode("\n", $filteredLines);
            } else {
                $details['log'] = $this->module->l('No detailed log available for this operation', 'Statistics');
            }
        } else {
            $details['log'] = $this->module->l('No log file found for this operation', 'Statistics');
        }
        
        return $details;
    }
    
    /**
     * Ladda JavaScript-översättningar
     */
    private function loadJSTranslations()
    {
        Media::addJsDef([
            'priceMatcherStatisticsAjaxUrl' => $this->context->link->getAdminLink('AdminPriceMatcherController', true, [], [
                'ajax' => 1,
                'tab' => 'statistics'
            ])
        ]);
    }
    
    /**
     * Bearbeta formulärisändningar för statistikfliken
     * 
     * @return bool|array true om lyckad, array med fel annars
     */
    public function processForm()
    {
        // Statistics-fliken har inget eget formulär för närvarande
        return true;
    }
}