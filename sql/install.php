<?php

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_pricematcher` (
    `id_product` INT(10) UNSIGNED NOT NULL,
    `id_manufacturer` INT(10) UNSIGNED NULL,
    `supplier_reference` VARCHAR(255) NULL,
    `ean13` VARCHAR(13) NULL,
    `wholesale_price` DECIMAL(20,6) NOT NULL DEFAULT \'0.000000\',
    `current_price` DECIMAL(20,6) NOT NULL DEFAULT \'0.000000\',
    `current_margin` DECIMAL(5,2) NOT NULL DEFAULT \'0.00\',
    `competitor_price` DECIMAL(20,6) NOT NULL DEFAULT \'0.000000\',
    `new_price` DECIMAL(20,6) NOT NULL DEFAULT \'0.000000\',
    `new_margin` DECIMAL(5,2) NOT NULL DEFAULT \'0.00\',
    `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT \'0.00\',
    `last_update` DATETIME NOT NULL,
    `pricefile` VARCHAR(255) NULL,
    `id_competitor` INT(10) UNSIGNED NOT NULL,
    `url` VARCHAR(255) NULL,
    PRIMARY KEY (`id_product`, `id_competitor`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_pricematcher_competitors` (
    `id_competitor` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    `cron_update` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `override_discount_settings` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `discount_strategy` VARCHAR(50) NULL,
    `min_margin_percent` DECIMAL(5,2) NULL,
    `max_discount_percent` DECIMAL(5,2) NULL,
    `price_underbid` DECIMAL(20,6) NULL,
    `min_price_threshold` DECIMAL(20,6) NULL,
    `discount_validity_days` INT(10) UNSIGNED NULL,
    PRIMARY KEY (`id_competitor`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_pricematcher_config` (
    `name` VARCHAR(255) NOT NULL,
    `value` TEXT NULL,
    PRIMARY KEY (`name`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts` (
    `id_active_discount` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INT(10) UNSIGNED NOT NULL,
    `id_specific_price` INT(10) UNSIGNED NOT NULL,
    `id_competitor` INT(10) UNSIGNED NOT NULL,
    `regular_price` DECIMAL(20,6) NOT NULL,
    `discount_price` DECIMAL(20,6) NOT NULL,
    `competitor_price` DECIMAL(20,6) NOT NULL,
    `discount_percent` DECIMAL(5,2) NOT NULL,
    `margin_percent` DECIMAL(5,2) NOT NULL,
    `date_add` DATETIME NOT NULL,
    `date_expiration` DATETIME NOT NULL,
    PRIMARY KEY (`id_active_discount`),
    INDEX (`id_product`),
    INDEX (`id_specific_price`),
    INDEX (`id_competitor`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Lägg till standardkonfigurationer
$sql[] = 'INSERT INTO `' . _DB_PREFIX_ . 'art_pricematcher_config` (`name`, `value`) VALUES
    ("cron_token", "' . Tools::passwdGen(32) . '"),
    ("notification_threshold", "15"),
    ("email_frequency", "daily"),
    ("max_discount_behavior", "partial"),
    ("discount_days_valid", "2"),
    ("min_price_threshold", "100"),
    ("price_underbid", "5"),
    ("max_discount_percent", "24"),
    ("min_margin_percent", "30"),
    ("discount_strategy", "margin"),
    ("min_discount_percent", "5");';
