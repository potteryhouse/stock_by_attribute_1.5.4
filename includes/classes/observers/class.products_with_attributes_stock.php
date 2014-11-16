<?php

/**
 * Description of class.products_with_attributes_stock
 *
 * @author mc12345678
 */
class products_with_attributes_stock extends base {
	function products_with_attributes_stock() {
		global $zco_notifier;
		$zco_notifier->attach($this, array('NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM', 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM')); 
	}	

	function update(&$callingClass, $notifier, $paramsArray) {
    if ($notifier == 'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM'){
      
    }
    if ($notifier == 'NOTIFY_ORDER_PROCESSING_ATTRIBUTES_BEGIN') {
      
//      $stock_attribute = zen_get_sba_stock_attribute(zen_get_prid($this->products[$i]['id']), $this->products[$i]['attributes']);
//      $stock_id = zen_get_sba_stock_attribute_id(zen_get_prid($this->products[$i]['id']), $this->products[$i]['attributes']); //true; // Need to use the $stock_attribute/attributes to obtain the attribute id.
    }

    if ($notifier == 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM') {
			/* First check to see if SBA is installed and if it is then look to see if a value is 
       *  supplied in the stock_id parameter (which should only be populated when a SBA tracked
       *  item is in the order */
      $_SESSION['paramsArray'] = $paramsArray;
			if (defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK') && zen_not_null($paramsArray['stock_info']['stock_id'])) {  
        //Need to validate that order had attributes in it.  If so, then were they tracked by SBA and then add to appropriate table.
/*          `orders_products_attributes_stock_id` INT(11) NOT NULL auto_increment, 
    `orders_products_attributes_id` INT(11) NOT NULL default '0',
    `orders_id` INT(11) NOT NULL default '0', 
    `orders_products_id` INT(11) NOT NULL default '0', 
    `stock_id` INT(11) NOT NULL default '0', 
    `stock_attribute` VARCHAR(255) NULL DEFAULT NULL, 
    `products_prid` TINYTEXT NOT NULL, */
              $sql_data_array = array('orders_products_attributes_id' =>$paramsArray['orders_products_attributes_id'],
                              'orders_id' =>$paramsArray['orders_id'], 
                              'orders_products_id' =>$paramsArray['orders_products_id'], 
                              'stock_id' => $paramsArray['stock_info']['stock_id'], 
                              'stock_attribute' => $paramsArray['stock_info']['stock_attribute'], 
                              'products_prid' =>$paramsArray['products_prid']);
      zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK, $sql_data_array); //inserts data into the TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK table.

      }
		} //endif NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM - mc12345678
	} //end update function - mc12345678
} //end class - mc12345678

?>
