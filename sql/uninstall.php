<?php

$sql = array();

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_pricematcher`;';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_pricematcher_competitors`;';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_pricematcher_config`;';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'art_pricematcher_active_discounts`;';
