<?php
/**
 *  @author    Rasmus Lejonfelt
 *  @copyright 2007-2025 ART
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace ArtPriceMatcher\Helpers;

use Db;
use Tools;

/**
 * Statistikhjälparklass för Art PriceMatcher-modulen
 * Hanterar insamling och bearbetning av statistik
 */
class Statistics
{
    /**
     * Spara operationsstatistik i databasen
     * 
     * @param int $idCompetitor Konkurrent-ID
     * @param string $operationType Operationstyp (download, compare, update, clean)
     * @param array $stats Statistikdata
     * @return bool Resultat
     */
    public function saveOperationStatistics($idCompetitor, $operationType, $stats)
    {
        $db = Db::getInstance();
        
        // Validera operationstyp
        $validOperationTypes = ['download', 'compare', 'update', 'clean'];
        if (!in_array($operationType, $validOperationTypes)) {
            return false;
        }
        
        // Standardvärden för statistikfält
        $defaults = [
            'total_products' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'skipped_count' => 0,
            'execution_time' => 0,
            'initiated_by' => 'manual'
        ];
        
        // Sammanfoga standardvärden med angivna statistikdata
        $stats = array_merge($defaults, $stats);
        
        // Spara i databasen
        $result = $db->insert('art_pricematcher_statistics', [
            'id_competitor' => (int)$idCompetitor,
            'operation_type' => pSQL($operationType),
            'total_products' => (int)$stats['total_products'],
            'success_count' => (int)$stats['success_count'],
            'error_count' => (int)$stats['error_count'],
            'skipped_count' => (int)$stats['skipped_count'],
            'execution_time' => (float)$stats['execution_time'],
            'execution_date' => date('Y-m-d H:i:s'),
            'initiated_by' => pSQL($stats['initiated_by'])
        ]);
        
        return $result;
    }
    
    /**
     * Hämta sammanfattande statistik för alla operationer
     * 
     * @param int $days Antal dagar att återgå
     * @return array Sammanfattande statistik
     */
    public function getStatisticsSummary($days = 30)
    {
        $db = Db::getInstance();
        
        // Beräkna datumintervall
        $endDate = date('Y-m-d H:i:s');
        $startDate = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        
        // Hämta statistik per operationstyp
        $sql = "
            SELECT 
                operation_type,
                COUNT(*) as count,
                SUM(total_products) as total_products,
                SUM(success_count) as success_count,
                SUM(error_count) as error_count,
                SUM(skipped_count) as skipped_count,
                AVG(success_count / total_products * 100) as success_rate,
                AVG(execution_time) as avg_execution_time
            FROM `" . _DB_PREFIX_ . "art_pricematcher_statistics`
            WHERE execution_date BETWEEN '" . pSQL($startDate) . "' AND '" . pSQL($endDate) . "'
            GROUP BY operation_type
        ";
        
        $results = $db->executeS($sql);
        
        // Skapa sammanfattning per operationstyp
        $summary = [
            'download' => [
                'count' => 0,
                'total_products' => 0,
                'success_count' => 0,
                'success_rate' => 0,
                'avg_execution_time' => 0
            ],
            'compare' => [
                'count' => 0,
                'total_products' => 0,
                'success_count' => 0,
                'success_rate' => 0,
                'avg_execution_time' => 0
            ],
            'update' => [
                'count' => 0,
                'total_products' => 0,
                'success_count' => 0,
                'success_rate' => 0,
                'avg_execution_time' => 0
            ],
            'clean' => [
                'count' => 0,
                'total_products' => 0,
                'success_count' => 0,
                'success_rate' => 0,
                'avg_execution_time' => 0
            ]
        ];
        
        if ($results) {
            foreach ($results as $row) {
                $operationType = $row['operation_type'];
                if (isset($summary[$operationType])) {
                    $summary[$operationType] = [
                        'count' => (int)$row['count'],
                        'total_products' => (int)$row['total_products'],
                        'success_count' => (int)$row['success_count'],
                        'success_rate' => round((float)$row['success_rate'], 2),
                        'avg_execution_time' => round((float)$row['avg_execution_time'], 2)
                    ];
                }
            }
        }
        
        // Hämta statistik per konkurrent
        $sql = "
            SELECT 
                s.id_competitor,
                c.name as competitor_name,
                COUNT(*) as operation_count,
                SUM(s.total_products) as total_products,
                SUM(s.success_count) as success_count,
                AVG(s.success_count / s.total_products * 100) as success_rate
            FROM `" . _DB_PREFIX_ . "art_pricematcher_statistics` s
            JOIN `" . _DB_PREFIX_ . "art_pricematcher_competitors` c ON s.id_competitor = c.id_competitor
            WHERE s.execution_date BETWEEN '" . pSQL($startDate) . "' AND '" . pSQL($endDate) . "'
            GROUP BY s.id_competitor
            ORDER BY operation_count DESC
        ";
        
        $competitorStats = $db->executeS($sql);
        
        // Lägga till konkurrentstatistik till sammanfattningen
        $summary['competitors'] = $competitorStats ? $competitorStats : [];
        
        // Hämta daglig statistik
        $sql = "
            SELECT 
                DATE(execution_date) as date,
                operation_type,
                SUM(total_products) as total_products,
                SUM(success_count) as success_count
            FROM `" . _DB_PREFIX_ . "art_pricematcher_statistics`
            WHERE execution_date BETWEEN '" . pSQL($startDate) . "' AND '" . pSQL($endDate) . "'
            GROUP BY DATE(execution_date), operation_type
            ORDER BY execution_date
        ";
        
        $dailyStats = $db->executeS($sql);
        
        // Skapa datumserie för varje dag i intervallet
        $dailyData = [];
        
        $currentDate = new \DateTime($startDate);
        $endDateTime = new \DateTime($endDate);
        
        while ($currentDate <= $endDateTime) {
            $dateStr = $currentDate->format('Y-m-d');
            $dailyData[$dateStr] = [
                'download' => ['total' => 0, 'success' => 0],
                'compare' => ['total' => 0, 'success' => 0],
                'update' => ['total' => 0, 'success' => 0],
                'clean' => ['total' => 0, 'success' => 0]
            ];
            $currentDate->modify('+1 day');
        }
        
        // Fyll i faktiska värden från databasen
        if ($dailyStats) {
            foreach ($dailyStats as $row) {
                $date = $row['date'];
                $operationType = $row['operation_type'];
                
                if (isset($dailyData[$date][$operationType])) {
                    $dailyData[$date][$operationType]['total'] = (int)$row['total_products'];
                    $dailyData[$date][$operationType]['success'] = (int)$row['success_count'];
                }
            }
        }
        
        // Lägga till daglig statistik till sammanfattningen
        $summary['daily'] = $dailyData;
        
        return $summary;
    }
    
    /**
     * Hämta statistik för prisavvikelser
     * 
     * @param int $idCompetitor Filtrera på konkurrent-ID (optional)
     * @return array Prisavvikelsestatistik
     */
    public function getPriceDeviations($idCompetitor = null)
    {
        $db = Db::getInstance();
        
        // Skapa SQL-fråga
        $sql = "
            SELECT 
                pm.id_product,
                pm.supplier_reference,
                p.reference,
                pm.ean13,
                pl.name as product_name,
                pm.current_price,
                pm.competitor_price,
                pm.discount_percent,
                c.name as competitor_name,
                m.name as manufacturer_name,
                cat.name as category_name
            FROM `" . _DB_PREFIX_ . "art_pricematcher` pm
            JOIN `" . _DB_PREFIX_ . "art_pricematcher_competitors` c ON pm.id_competitor = c.id_competitor
            JOIN `" . _DB_PREFIX_ . "product` p ON pm.id_product = p.id_product
            JOIN `" . _DB_PREFIX_ . "product_lang` pl ON p.id_product = pl.id_product 
                AND pl.id_lang = " . (int)Context::getContext()->language->id . "
            LEFT JOIN `" . _DB_PREFIX_ . "manufacturer` m ON p.id_manufacturer = m.id_manufacturer
            LEFT JOIN `" . _DB_PREFIX_ . "category_product` cp ON p.id_product = cp.id_product
            LEFT JOIN `" . _DB_PREFIX_ . "category` cat ON cp.id_category = cat.id_category AND cat.id_parent > 0
            LEFT JOIN `" . _DB_PREFIX_ . "category_lang` cl ON cat.id_category = cl.id_category 
                AND cl.id_lang = " . (int)Context::getContext()->language->id . "
        ";
        
        // Lägg till filter för konkurrent om det anges
        if ($idCompetitor !== null) {
            $sql .= " WHERE pm.id_competitor = " . (int)$idCompetitor;
        }
        
        // Lägg till sortering
        $sql .= " ORDER BY pm.discount_percent DESC";
        
        $result = $db->executeS($sql);
        
        // Analysera resultaten för att skapa statistik
        $deviations = [
            'total_products' => count($result),
            'avg_discount' => 0,
            'max_discount' => 0,
            'min_discount' => 0,
            'discount_ranges' => [
                '0-5%' => 0,
                '5-10%' => 0,
                '10-15%' => 0,
                '15-20%' => 0,
                '20-25%' => 0,
                '>25%' => 0
            ],
            'by_manufacturer' => [],
            'by_category' => [],
            'by_competitor' => [],
            'products' => []
        ];
        
        if ($result) {
            // Beräkna genomsnittlig rabatt
            $totalDiscount = 0;
            $maxDiscount = 0;
            $minDiscount = 100;
            
            foreach ($result as $row) {
                $discount = (float)$row['discount_percent'];
                $totalDiscount += $discount;
                
                if ($discount > $maxDiscount) {
                    $maxDiscount = $discount;
                }
                
                if ($discount < $minDiscount) {
                    $minDiscount = $discount;
                }
                
                // Räkna rabatt inom intervall
                if ($discount <= 5) {
                    $deviations['discount_ranges']['0-5%']++;
                } elseif ($discount <= 10) {
                    $deviations['discount_ranges']['5-10%']++;
                } elseif ($discount <= 15) {
                    $deviations['discount_ranges']['10-15%']++;
                } elseif ($discount <= 20) {
                    $deviations['discount_ranges']['15-20%']++;
                } elseif ($discount <= 25) {
                    $deviations['discount_ranges']['20-25%']++;
                } else {
                    $deviations['discount_ranges']['>25%']++;
                }
                
                // Räkna per tillverkare
                $manufacturer = $row['manufacturer_name'] ?: 'Unknown';
                if (!isset($deviations['by_manufacturer'][$manufacturer])) {
                    $deviations['by_manufacturer'][$manufacturer] = [
                        'count' => 0,
                        'avg_discount' => 0,
                        'total_discount' => 0
                    ];
                }
                $deviations['by_manufacturer'][$manufacturer]['count']++;
                $deviations['by_manufacturer'][$manufacturer]['total_discount'] += $discount;
                
                // Räkna per kategori
                $category = $row['category_name'] ?: 'Unknown';
                if (!isset($deviations['by_category'][$category])) {
                    $deviations['by_category'][$category] = [
                        'count' => 0,
                        'avg_discount' => 0,
                        'total_discount' => 0
                    ];
                }
                $deviations['by_category'][$category]['count']++;
                $deviations['by_category'][$category]['total_discount'] += $discount;
                
                // Räkna per konkurrent
                $competitor = $row['competitor_name'];
                if (!isset($deviations['by_competitor'][$competitor])) {
                    $deviations['by_competitor'][$competitor] = [
                        'count' => 0,
                        'avg_discount' => 0,
                        'total_discount' => 0
                    ];
                }
                $deviations['by_competitor'][$competitor]['count']++;
                $deviations['by_competitor'][$competitor]['total_discount'] += $discount;
                
                // Lägg till produkt i listan
                $deviations['products'][] = [
                    'id_product' => $row['id_product'],
                    'reference' => $row['reference'],
                    'supplier_reference' => $row['supplier_reference'],
                    'ean13' => $row['ean13'],
                    'name' => $row['product_name'],
                    'current_price' => $row['current_price'],
                    'competitor_price' => $row['competitor_price'],
                    'discount_percent' => $discount,
                    'competitor' => $row['competitor_name'],
                    'manufacturer' => $manufacturer,
                    'category' => $category
                ];
            }
            
            // Beräkna genomsnitt
            if (count($result) > 0) {
                $deviations['avg_discount'] = $totalDiscount / count($result);
                $deviations['max_discount'] = $maxDiscount;
                $deviations['min_discount'] = $minDiscount;
                
                // Beräkna genomsnitt per tillverkare
                foreach ($deviations['by_manufacturer'] as $name => $data) {
                    $deviations['by_manufacturer'][$name]['avg_discount'] = 
                        $data['total_discount'] / $data['count'];
                }
                
                // Beräkna genomsnitt per kategori
                foreach ($deviations['by_category'] as $name => $data) {
                    $deviations['by_category'][$name]['avg_discount'] = 
                        $data['total_discount'] / $data['count'];
                }
                
                // Beräkna genomsnitt per konkurrent
                foreach ($deviations['by_competitor'] as $name => $data) {
                    $deviations['by_competitor'][$name]['avg_discount'] = 
                        $data['total_discount'] / $data['count'];
                }
            }
        }
        
        return $deviations;
    }
    
    /**
     * Hämta trenddata för ett angivet antal dagar
     * 
     * @param int $days Antal dagar att inkludera
     * @return array Trenddata
     */
    public function getTrendData($days = 30)
    {
        $db = Db::getInstance();
        
        // Beräkna datumintervall
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-' . (int)$days . ' days'));
        
        // Hämta dagliga statistikdata för aktiva rabatter
        $sql = "
            SELECT 
                DATE(date_add) as date,
                COUNT(*) as active_discounts,
                AVG(discount_percent) as avg_discount
            FROM `" . _DB_PREFIX_ . "art_pricematcher_active_discounts`
            WHERE date_add BETWEEN '" . pSQL($startDate) . "' AND '" . pSQL($endDate) . " 23:59:59'
            GROUP BY DATE(date_add)
            ORDER BY date
        ";
        
        $activeDiscountsData = $db->executeS($sql);
        
        // Hämta dagliga statistikdata för operationer
        $sql = "
            SELECT 
                DATE(execution_date) as date,
                operation_type,
                COUNT(*) as operation_count,
                SUM(success_count) as success_count
            FROM `" . _DB_PREFIX_ . "art_pricematcher_statistics`
            WHERE execution_date BETWEEN '" . pSQL($startDate) . "' AND '" . pSQL($endDate) . " 23:59:59'
            GROUP BY DATE(execution_date), operation_type
            ORDER BY date
        ";
        
        $operationsData = $db->executeS($sql);
        
        // Skapa array med alla datum i intervallet
        $trends = [];
        $current = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $trends[$dateStr] = [
                'date' => $dateStr,
                'active_discounts' => 0,
                'avg_discount' => 0,
                'downloads' => 0,
                'compares' => 0,
                'updates' => 0
            ];
            $current->modify('+1 day');
        }
        
        // Fyll i aktiva rabatter
        if ($activeDiscountsData) {
            foreach ($activeDiscountsData as $row) {
                $date = $row['date'];
                if (isset($trends[$date])) {
                    $trends[$date]['active_discounts'] = (int)$row['active_discounts'];
                    $trends[$date]['avg_discount'] = round((float)$row['avg_discount'], 2);
                }
            }
        }
        
        // Fyll i operationsdata
        if ($operationsData) {
            foreach ($operationsData as $row) {
                $date = $row['date'];
                $type = $row['operation_type'];
                
                if (isset($trends[$date])) {
                    if ($type === 'download') {
                        $trends[$date]['downloads'] += (int)$row['operation_count'];
                    } elseif ($type === 'compare') {
                        $trends[$date]['compares'] += (int)$row['operation_count'];
                    } elseif ($type === 'update') {
                        $trends[$date]['updates'] += (int)$row['success_count'];
                    }
                }
            }
        }
        
        // Konvertera till indexerad array för enklare användning i grafer
        $result = [
            'dates' => [],
            'active_discounts' => [],
            'avg_discount' => [],
            'downloads' => [],
            'compares' => [],
            'updates' => []
        ];
        
        foreach ($trends as $date => $data) {
            $result['dates'][] = $date;
            $result['active_discounts'][] = $data['active_discounts'];
            $result['avg_discount'][] = $data['avg_discount'];
            $result['downloads'][] = $data['downloads'];
            $result['compares'][] = $data['compares'];
            $result['updates'][] = $data['updates'];
        }
        
        return $result;
    }
}