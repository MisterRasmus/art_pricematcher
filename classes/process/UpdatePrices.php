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
use SpecificPrice;
use Validate;
use Tax;
use DateTime;
use Exception;
use ArtPriceMatcher\Helpers\Logger;
use ArtPriceMatcher\Helpers\Statistics;
use ArtPriceMatcher\Helpers\EmailHandler;

/**
 * Applicerar prisjusteringar genom att skapa tidsbegränsade rabatter (specific_price)
 * baserat på data från art_pricematcher-tabellen.
 */
class UpdatePrices
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
        $this->logger = new Logger('update');
        $this->statistics = new Statistics();

        $this->logger->info("Initialiserar UpdatePrices");
    }

    /**
     * Uppdatera priser baserat på konkurrentdata
     *
     * @param int|string|array $competitor Konkurrent ID, namn eller array med konkurrentdata
     * @return array Resultat av uppdateringen
     */
    public function updatePrices($competitor)
    {
        $db = Db::getInstance();
        $startTime = microtime(true);

        // Standardresultat
        $results = [
            'total_checked' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
            'cleaned_discounts' => 0,
            'execution_time' => 0
        ];

        try {
            // Rensa utgångna rabatter om inställningen är aktiverad
            $cleanExpiredDiscounts = (bool)$db->getValue("SELECT `value` FROM `" . _DB_PREFIX_ . "art_pricematcher_config` WHERE `name` = 'clean_expired_discounts'");

            if ($cleanExpiredDiscounts) {
                $cleanedCount = $this->cleanExpiredDiscounts();
                $results['cleaned_discounts'] = $cleanedCount;
                $this->logger->info("Rensade $cleanedCount utgångna rabatter");
            }

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
                $this->logger->info("Konkurrent {$competitor['name']} (ID: {$competitor['id_competitor']}) är inaktiv, hoppar över prisuppdatering");
                return $results;
            }

            $this->logger->info("Startar prisuppdatering för konkurrent {$competitor['name']} (ID: {$competitor['id_competitor']})");

            // Hämta inställningar
            $settings = $this->getSettings($competitor['id_competitor']);
            $this->logger->info("Använder inställningar: " . json_encode($settings));

            // Hämta alla produkter som behöver uppdateras för denna konkurrent
            $productsToUpdate = $this->getProductsToUpdate($competitor['id_competitor']);
            $results['total_checked'] = count($productsToUpdate);

            if (empty($productsToUpdate)) {
                $this->logger->info("Inga produkter hittades för konkurrent {$competitor['name']} (ID: {$competitor['id_competitor']})");
                return $results;
            }

            // Bearbeta varje produkt
            foreach ($productsToUpdate as $productData) {
                try {
                    // Skapa produktobjekt
                    $product = new Product($productData['id_product']);
                    if (!Validate::isLoadedObject($product)) {
                        throw new Exception("Produkt med ID {$productData['id_product']} kunde inte laddas");
                    }

                    // Hämta det ordinarie priset (utan rabatt)
                    $regularPrice = $this->getProductRegularPrice($product);
                    $competitorPrice = (float)$productData['competitor_price'];
                    $targetPrice = (float)$productData['new_price'];
                    $discountPercent = (float)$productData['discount_percent'];
                    $newMargin = (float)$productData['new_margin'];

                    // Validera att priset är rimligt
                    if (!$this->validatePriceUpdate($productData, $settings)) {
                        $results['skipped_count']++;
                        // Ta bort från prismatchningstabell
                        $this->removeFromPriceMatchTable($productData['id_product'], $competitor['id_competitor']);
                        continue;
                    }

                    // Beräkna giltighetsdatum för rabatten
                    $fromDate = date('Y-m-d H:i:s');
                    $toDate = date('Y-m-d H:i:s', strtotime('+' . $settings['discount_days_valid'] . ' days'));

                    // Kontrollera om en specifik rabatt redan existerar för denna produkt
                    $existingSpecificPrice = $this->checkIfSpecificPriceExists($productData['id_product']);
                    $specificPriceId = null;

                    if ($existingSpecificPrice) {
                        // Uppdatera befintlig rabatt
                        $this->logger->info("Uppdaterar befintlig rabatt för produkt {$productData['id_product']}");
                        $result = $this->updateSpecificPrice(
                            $existingSpecificPrice['id_specific_price'],
                            $targetPrice,
                            $fromDate,
                            $toDate
                        );

                        if ($result) {
                            $specificPriceId = $existingSpecificPrice['id_specific_price'];
                        }
                    } else {
                        // Skapa nya rabatter för varje kundgrupp
                        $result = true;
                        foreach ($settings['discount_customer_groups'] as $idGroup) {
                            $newSpecificPriceId = $this->createSpecificPrice(
                                $productData['id_product'],
                                $idGroup,
                                $targetPrice,
                                $fromDate,
                                $toDate
                            );

                            if (!$newSpecificPriceId) {
                                $result = false;
                                break;
                            }

                            // Spara ID för den första rabatten
                            if ($specificPriceId === null) {
                                $specificPriceId = $newSpecificPriceId;
                            }
                        }
                    }

                    if (!$result) {
                        throw new Exception(
                            "Misslyckades med att skapa/uppdatera rabatt för produkt {$productData['id_product']}"
                        );
                    }

                    // Spåra den aktiva rabatten i vår databas
                    if ($specificPriceId) {
                        $this->trackActiveDiscount(
                            $productData['id_product'],
                            $specificPriceId,
                            $competitor['id_competitor'],
                            $regularPrice,
                            $targetPrice,
                            $competitorPrice,
                            $discountPercent,
                            $newMargin,
                            $toDate
                        );
                    }

                    // Uppdatera statistik
                    $results['updated_count']++;

                    // Ta bort från prismatchningstabell
                    $this->removeFromPriceMatchTable($productData['id_product'], $competitor['id_competitor']);

                    $this->logger->info("Skapade/uppdaterade rabatt för produkt {$productData['id_product']} ({$product->name}): Rabatt {$discountPercent}%, Pris från {$regularPrice} till {$targetPrice}");
                } catch (Exception $e) {
                    $this->logger->error("Misslyckades med att uppdatera produkt {$productData['id_product']}: " . $e->getMessage());
                    $results['failed_count']++;
                }
            }

            // Beräkna exekveringstid
            $executionTime = microtime(true) - $startTime;
            $results['execution_time'] = $executionTime;

            $this->logger->info("Slutförde prisuppdatering för konkurrent {$competitor['name']}. Uppdaterade {$results['updated_count']} av {$results['total_checked']} produkter på " . round($executionTime, 2) . " sekunder. Hoppade över: {$results['skipped_count']}, Misslyckades: {$results['failed_count']}");

            // Spara statistik
            $this->statistics->saveOperationStatistics(
                $competitor['id_competitor'],
                'update',
                [
                    'total_products' => $results['total_checked'],
                    'success_count' => $results['updated_count'],
                    'error_count' => $results['failed_count'],
                    'skipped_count' => $results['skipped_count'],
                    'execution_time' => $executionTime,
                    'initiated_by' => Tools::getValue('cron') ? 'cron' : 'manual'
                ]
            );

            return $results;
        } catch (Exception $e) {
            $this->logger->error("Fel i updatePrices: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Rensa utgångna rabatter
     *
     * @return int Antal borttagna rabatter
     */
    public function cleanExpiredDiscounts()
    {
        $this->logger->info("Påbörjar rensning av utgångna rabatter");
        $db = Db::getInstance();
        $now = date('Y-m-d H:i:s');
        $totalRemoved = 0;

        // Hitta alla utgångna rabatter i vår spårningstabell
        $sql = "SELECT ad.id_active_discount, ad.id_specific_price
                FROM `" . _DB_PREFIX_ . "art_pricematcher_active_discounts` ad
                WHERE ad.date_expiration < '" . pSQL($now) . "'";

        $expiredDiscounts = $db->executeS($sql);
        $expiredCount = count($expiredDiscounts);

        $this->logger->info("Hittade {$expiredCount} utgångna rabatter i vår spårningstabell");

        // Ta bort från specific_price-tabellen
        if (!empty($expiredDiscounts)) {
            $specificPriceIds = array_column($expiredDiscounts, 'id_specific_price');
            $specificPriceIds = array_filter($specificPriceIds);

            if (!empty($specificPriceIds)) {
                $specificPriceIdsStr = implode(',', array_map('intval', $specificPriceIds));

                $sql = "DELETE FROM `" . _DB_PREFIX_ . "specific_price`
                       WHERE `id_specific_price` IN (" . $specificPriceIdsStr . ")";
                $db->execute($sql);

                // Ta bort från vår spårningstabell
                $activeDiscountIds = array_column($expiredDiscounts, 'id_active_discount');
                $activeDiscountIdsStr = implode(',', array_map('intval', $activeDiscountIds));

                $sql = "DELETE FROM `" . _DB_PREFIX_ . "art_pricematcher_active_discounts`
                       WHERE `id_active_discount` IN (" . $activeDiscountIdsStr . ")";
                $db->execute($sql);

                $this->logger->info("Tog bort {$expiredCount} spårade utgångna rabatter");
                $totalRemoved += $expiredCount;
            }
        }

        // Rensa även andra utgångna rabatter som inte är i vår spårningstabell
        $sql = "DELETE FROM `" . _DB_PREFIX_ . "specific_price`
               WHERE `to` < '" . pSQL($now) . "'
               AND `to` != '0000-00-00 00:00:00'";

        $result = $db->execute($sql);
        $additionalRemoved = $db->Affected_Rows();

        $this->logger->info("Tog bort {$additionalRemoved} ytterligare ospårade utgångna rabatter");
        $totalRemoved += $additionalRemoved;

        // Uppdatera timestamp för senaste körning
        $db->update(
            'art_pricematcher_config',
            ['value' => date('Y-m-d H:i:s')],
            "name = 'last_clean_run'"
        );

        $this->logger->info("Slutförde rensning av utgångna rabatter. Totalt borttagna: $totalRemoved");
        return $totalRemoved;
    }

    /**
     * Hämta inställningar för prisuppdatering
     *
     * @param int $competitorId Konkurrent-ID
     * @return array Inställningar
     */
    private function getSettings($competitorId)
    {
        $db = Db::getInstance();
        $settings = [];

        // Hämta globala inställningar
        $globalSettingsQuery = "SELECT * FROM `" . _DB_PREFIX_ . "art_pricematcher_config`";
        $globalSettings = $db->executeS($globalSettingsQuery);

        // Skapa associativ array av globala inställningar
        foreach ($globalSettings as $setting) {
            $settings[$setting['name']] = $setting['value'];
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
            if (isset($competitor['discount_validity_days'])) {
                $settings['discount_days_valid'] = $competitor['discount_validity_days'];
            }
        }

        // Förbered kundgrupper-array
        if (isset($settings['discount_customer_groups'])) {
            $settings['discount_customer_groups'] = json_decode($settings['discount_customer_groups'], true);
            if (!$settings['discount_customer_groups'] || !is_array($settings['discount_customer_groups'])) {
                $settings['discount_customer_groups'] = [1]; // Standardvärde
            }
        } else {
            $settings['discount_customer_groups'] = [1]; // Standardvärde
        }

        // Sätt standardvärden om de saknas
        if (!isset($settings['discount_days_valid']) || (int)$settings['discount_days_valid'] <= 0) {
            $settings['discount_days_valid'] = 2;
        }
        if (!isset($settings['min_margin_percent']) || (float)$settings['min_margin_percent'] <= 0) {
            $settings['min_margin_percent'] = 30;
        }
        if (!isset($settings['max_discount_percent']) || (float)$settings['max_discount_percent'] <= 0) {
            $settings['max_discount_percent'] = 24;
        }
        if (!isset($settings['min_discount_percent']) || (float)$settings['min_discount_percent'] <= 0) {
            $settings['min_discount_percent'] = 5;
        }
        if (!isset($settings['discount_strategy']) || !in_array($settings['discount_strategy'], ['margin', 'discount', 'both'])) {
            $settings['discount_strategy'] = 'margin';
        }

        return $settings;
    }

    /**
     * Hämta produkter som behöver uppdateras
     *
     * @param int $competitorId Konkurrent-ID
     * @return array Produkter som behöver uppdateras
     */
    private function getProductsToUpdate($competitorId)
    {
        $db = Db::getInstance();

        $sql = "
            SELECT pm.id_product, p.reference, pm.supplier_reference,
                  pm.competitor_price, pm.current_price, p.wholesale_price,
                  pm.new_price, pm.discount_percent, pm.new_margin
            FROM `" . _DB_PREFIX_ . "art_pricematcher` pm
            JOIN `" . _DB_PREFIX_ . "product` p ON pm.id_product = p.id_product
            WHERE pm.id_competitor = " . (int)$competitorId . "
            AND pm.competitor_price > 0
            AND pm.new_price > 0
            AND p.active = 1
            LIMIT 1000";

        $result = $db->executeS($sql);

        return $result ? $result : [];
    }

    /**
     * Validera att en prisuppdatering är giltig
     *
     * @param array $productData Produktdata
     * @param array $settings Inställningar
     * @return bool Om uppdateringen är giltig
     */
    private function validatePriceUpdate($productData, $settings)
    {
        $discountPercent = (float)$productData['discount_percent'];
        $newMargin = (float)$productData['new_margin'];
        $minDiscountPercent = (float)$settings['min_discount_percent'];
        $maxDiscountPercent = (float)$settings['max_discount_percent'];
        $minMarginPercent = (float)$settings['min_margin_percent'];

        // Kontrollera att rabatten är tillräckligt stor
        if ($discountPercent < $minDiscountPercent) {
            $this->logger->info("Hoppar över produkt {$productData['id_product']}: För liten rabatt ({$discountPercent}% < {$minDiscountPercent}%)");
            return false;
        }

        // Kontrollera baserat på rabattstrategi
        switch ($settings['discount_strategy']) {
            case 'margin':
                // Kontrollera att marginalen är tillräcklig
                if ($newMargin < $minMarginPercent) {
                    $this->logger->info("Hoppar över produkt {$productData['id_product']}: För låg marginal ({$newMargin}% < {$minMarginPercent}%)");
                    return false;
                }
                break;

            case 'discount':
                // Kontrollera att rabatten inte är för stor
                if ($discountPercent > $maxDiscountPercent) {
                    $this->logger->info("Hoppar över produkt {$productData['id_product']}: För stor rabatt ({$discountPercent}% > {$maxDiscountPercent}%)");
                    return false;
                }
                break;

            case 'both':
                // Kontrollera både marginal och rabatt
                if ($newMargin < $minMarginPercent) {
                    $this->logger->info("Hoppar över produkt {$productData['id_product']}: För låg marginal ({$newMargin}% < {$minMarginPercent}%)");
                    return false;
                }
                if ($discountPercent > $maxDiscountPercent) {
                    $this->logger->info("Hoppar över produkt {$productData['id_product']}: För stor rabatt ({$discountPercent}% > {$maxDiscountPercent}%)");
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Hämta det ordinarie priset för en produkt (utan rabatt)
     *
     * @param Product $product Produktobjekt
     * @return float Ordinarie pris
     */
    private function getProductRegularPrice($product)
    {
        // Förenkla genom att alltid använda produktens pris
        // In PrestaShop, the regular price is stored in the product's price field
        return (float)$product->price;
    }

    /**
     * Kontrollera om en specifik rabatt redan finns för en produkt
     *
     * @param int $idProduct Produkt-ID
     * @return array|false Rabattdata eller false om ingen hittas
     */
    private function checkIfSpecificPriceExists($idProduct)
    {
        $db = Db::getInstance();
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "specific_price`
               WHERE `id_product` = " . (int)$idProduct . "
               AND `from_quantity` = 1
               AND `id_specific_price_rule` = 0
               AND `id_shop` = " . (int)$this->context->shop->id . "
               ORDER BY `id_specific_price` DESC";

        return $db->getRow($sql);
    }

    /**
     * Skapa en specifik rabatt för en produkt
     *
     * @param int $idProduct Produkt-ID
     * @param int $idGroup Kundgrupp-ID
     * @param float $price Rabattpris
     * @param string $fromDate Från-datum (Y-m-d H:i:s)
     * @param string $toDate Till-datum (Y-m-d H:i:s)
     * @return int|false Rabatt-ID eller false vid fel
     */
    private function createSpecificPrice($idProduct, $idGroup, $price, $fromDate, $toDate)
    {
        $specificPrice = new SpecificPrice();
        $specificPrice->id_product = (int)$idProduct;
        $specificPrice->id_product_attribute = 0;
        $specificPrice->id_shop = (int)$this->context->shop->id;
        $specificPrice->id_shop_group = 0;
        $specificPrice->id_currency = 0; // Alla valutor
        $specificPrice->id_country = 0; // Alla länder
        $specificPrice->id_group = (int)$idGroup;
        $specificPrice->id_customer = 0; // Alla kunder
        $specificPrice->price = $price;
        $specificPrice->from_quantity = 1;
        $specificPrice->reduction = 0;
        $specificPrice->reduction_type = 'amount';
        $specificPrice->from = $fromDate;
        $specificPrice->to = $toDate;

        if ($specificPrice->add()) {
            return $specificPrice->id;
        }

        return false;
    }

    /**
     * Uppdatera en befintlig rabatt
     *
     * @param int $idSpecificPrice Rabatt-ID
     * @param float $price Nytt pris
     * @param string $fromDate Från-datum (Y-m-d H:i:s)
     * @param string $toDate Till-datum (Y-m-d H:i:s)
     * @return bool Resultat
     */
    private function updateSpecificPrice($idSpecificPrice, $price, $fromDate, $toDate)
    {
        $specificPrice = new SpecificPrice($idSpecificPrice);
        if (!Validate::isLoadedObject($specificPrice)) {
            return false;
        }

        $specificPrice->price = $price;
        $specificPrice->from = $fromDate;
        $specificPrice->to = $toDate;

        return $specificPrice->update();
    }

    /**
     * Spåra en aktiv rabatt i databasen
     *
     * @param int $idProduct Produkt-ID
     * @param int $idSpecificPrice Rabatt-ID
     * @param int $idCompetitor Konkurrent-ID
     * @param float $regularPrice Ordinarie pris
     * @param float $discountPrice Rabattpris
     * @param float $competitorPrice Konkurrentpris
     * @param float $discountPercent Rabattprocent
     * @param float $marginPercent Marginalprocent
     * @param string $expirationDate Utgångsdatum (Y-m-d H:i:s)
     * @return bool Resultat
     */
    private function trackActiveDiscount(
        $idProduct,
        $idSpecificPrice,
        $idCompetitor,
        $regularPrice,
        $discountPrice,
        $competitorPrice,
        $discountPercent,
        $marginPercent,
        $expirationDate
    ) {
        $db = Db::getInstance();

        // Kontrollera om det redan finns en post för denna produkt och konkurrent
        $sql = "SELECT `id_active_discount` FROM `" . _DB_PREFIX_ . "art_pricematcher_active_discounts`
               WHERE `id_product` = " . (int)$idProduct . "
               AND `id_competitor` = " . (int)$idCompetitor;

        $existingId = $db->getValue($sql);

        if ($existingId) {
            // Uppdatera befintlig post
            $data = [
                'id_specific_price' => (int)$idSpecificPrice,
                'regular_price' => (float)$regularPrice,
                'discount_price' => (float)$discountPrice,
                'competitor_price' => (float)$competitorPrice,
                'discount_percent' => (float)$discountPercent,
                'margin_percent' => (float)$marginPercent,
                'date_expiration' => pSQL($expirationDate)
            ];

            return $db->update(
                'art_pricematcher_active_discounts',
                $data,
                'id_active_discount = ' . (int)$existingId
            );
        } else {
            // Skapa ny post
            $data = [
                'id_product' => (int)$idProduct,
                'id_specific_price' => (int)$idSpecificPrice,
                'id_competitor' => (int)$idCompetitor,
                'regular_price' => (float)$regularPrice,
                'discount_price' => (float)$discountPrice,
                'competitor_price' => (float)$competitorPrice,
                'discount_percent' => (float)$discountPercent,
                'margin_percent' => (float)$marginPercent,
                'date_add' => date('Y-m-d H:i:s'),
                'date_expiration' => pSQL($expirationDate)
            ];

            return $db->insert('art_pricematcher_active_discounts', $data);
        }
    }

    /**
     * Ta bort en produkt från prismatchningstabellen
     *
     * @param int $productId Produkt-ID
     * @param int $competitorId Konkurrent-ID
     * @return bool Resultat
     */
    private function removeFromPriceMatchTable($productId, $competitorId)
    {
        $sql = "DELETE FROM `" . _DB_PREFIX_ . "art_pricematcher`
               WHERE `id_product` = " . (int)$productId . "
               AND `id_competitor` = " . (int)$competitorId;

        return Db::getInstance()->execute($sql);
    }

    /**
     * Uppdatera priser för alla aktiva konkurrenter
     *
     * @return array Resultat
     */
    public function updateAllPrices()
    {
        $this->logger->info("Startar prisuppdatering för alla konkurrenter");
        $startTime = microtime(true);

        $results = [
            'total_competitors' => 0,
            'updated_competitors' => 0,
            'skipped_competitors' => 0,
            'total_products' => 0,
            'updated_products' => 0,
            'skipped_products' => 0,
            'cleaned_discounts' => 0,
            'execution_time' => 0,
            'competitor_results' => []
        ];

        // Rensa utgångna rabatter
        $cleanedCount = $this->cleanExpiredDiscounts();
        $results['cleaned_discounts'] = $cleanedCount;

        // Hämta alla aktiva konkurrenter med cron_update aktiverat
        $db = Db::getInstance();
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "art_pricematcher_competitors`
               WHERE `active` = 1 AND `cron_update` = 1";
        $competitors = $db->executeS($sql);

        if (empty($competitors)) {
            $this->logger->info("Inga aktiva konkurrenter hittades med cron_update aktiverat");
            return $results;
        }

        $results['total_competitors'] = count($competitors);

        // Uppdatera priser för varje konkurrent
        foreach ($competitors as $competitor) {
            $competitorId = (int)$competitor['id_competitor'];
            $competitorName = $competitor['name'];

            $this->logger->info("Bearbetar konkurrent: $competitorName (ID: $competitorId)");

            try {
                // Uppdatera priser för denna konkurrent
                $competitorResults = $this->updatePrices($competitorId);

                // Lägg till i övergripande resultat
                $results['updated_competitors']++;
                $results['total_products'] += $competitorResults['total_checked'];
                $results['updated_products'] += $competitorResults['updated_count'];
                $results['skipped_products'] += $competitorResults['skipped_count'];

                // Spara individuella konkurrentresultat
                $results['competitor_results'][$competitorId] = [
                    'name' => $competitorName,
                    'total_products' => $competitorResults['total_checked'],
                    'updated_products' => $competitorResults['updated_count'],
                    'skipped_products' => $competitorResults['skipped_count'],
                    'failed_products' => $competitorResults['failed_count']
                ];

                $this->logger->info("Prisuppdatering för konkurrent: $competitorName slutförd");
            } catch (Exception $e) {
                $this->logger->error("Fel vid uppdatering av priser för konkurrent $competitorName: " . $e->getMessage());
                $results['skipped_competitors']++;

                // Spara fel i resultat
                $results['competitor_results'][$competitorId] = [
                    'name' => $competitorName,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Beräkna exekveringstid
        $executionTime = microtime(true) - $startTime;
        $results['execution_time'] = $executionTime;

        $this->logger->info("Slutförde prisuppdatering för alla konkurrenter. Uppdaterade " .
            $results['updated_products'] . " produkter över " .
            $results['updated_competitors'] . " konkurrenter, rensade " .
            $results['cleaned_discounts'] . " utgångna rabatter på " .
            round($executionTime, 2) . " sekunder");

        return $results;
    }

    /**
     * Alias för updatePrices för att bibehålla bakåtkompatibilitet
     *
     * @param mixed $competitor Konkurrent
     * @return array Resultat
     */
    public function updateCompetitorPrices($competitor)
    {
        return $this->updatePrices($competitor);
    }
}
