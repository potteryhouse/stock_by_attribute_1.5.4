<?php
/*
 * Updated for Stock by Attributes 1.5.4
 */
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}
define('FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK','products_with_attributes_stock');
define('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK', DB_PREFIX . 'products_with_attributes_stock');
define('FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK_SETUP','stock_by_attr_install');
//TODO: New table schema thought
//define('TABLE_PRODUCTS_ATTRIBUTES_STOCK', DB_PREFIX . 'products_attributes_stock');
//define('TABLE_PRODUCTS_ATTRIBUTES_STOCK_RELATIONSHIP', DB_PREFIX . 'products_attributes_stock_relationship');