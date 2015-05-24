<?php

/**
 * Description of class.products_with_attributes_stock: This class is used to support order information related to Stock By Attributes.  This way reduces the modifications of the includes/classes/order.php file to nearly nothing.
 *
 * @property array() $_productI This is the specific product that is being worked on in the order file.
 * @property integer $_i This is the identifier of which product is being worked on in the order file
 * @property array $_stock_info This contains information related to the SBA table associated with the product being worked on in the order file.
 * @property double $_attribute_stock_left This is the a referenced value that relates to the SBA tracked quantity that remain.
 * @property array $_stock_values The results of querying on the database for the stock remaining and other associated information.
 * @author mc12345678
 *
 * Stock by Attributes 1.5.4
 */
class products_with_attributes_stock extends base {

  //
  private $_productI;
  
  private $_i;

  private $_stock_info = array();
  
  private $_attribute_stock_left;

  private $_stock_values;
  
  /*
   * This is the observer for the includes/classes/order.php file to support Stock By Attributes when the order is being processed at the end of the purchase.
   */
  function products_with_attributes_stock() {
		global $zco_notifier;
		$zco_notifier->attach($this, array('NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM', 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM','NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT','NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN','NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END')); 
	}	

  /*
   * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT is encountered as a notifier.
   */
  //NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_INIT //Line 716
	function updateNotifyOrderProcessingStockDecrementInit(&$callingClass, $notifier, $paramsArray, & $productI, & $i) {
    $this->_i = $i;
    $this->_productI = $productI;

    $this->_stock_info = zen_get_sba_stock_attribute_info(zen_get_prid($this->_productI['id']), $this->_productI['attributes']);

    // START "Stock by Attributes"
    $attributeList = null;
    $customid = null;
    if(isset($this->_productI['attributes']) and sizeof($this->_productI['attributes']) >0){
      foreach($this->_productI['attributes'] as $attributes){
        $attributeList[] = $attributes['value_id'];
      }
      $customid = zen_get_customid($this->_productI['id'],$attributeList);
      $productI['customid'] = $customid;
      $this->_productI['customid'] = $customid;
//      $productI['model'] = (zen_not_null($customid) ? $customid : $productI['model']);
      $this->_productI['model'] = $productI['model'];
    }
    // END "Stock by Attributes"
  }

  /*
   * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN is encountered as a notifier.
   */
  // Line 739
  function updateNotifyOrderProcessingStockDecrementBegin(&$callingClass, $notifier, $paramsArray, &$stock_values, &$attribute_stock_left){
  	global $db;

    $this->_stock_values = $stock_values;

    if ($stock_values->RecordCount() > 0) {
			// kuroi: Begin Stock by Attributes additions
			// added to update quantities of products with attributes
			$attribute_search = array();
			$attribute_stock_left = STOCK_REORDER_LEVEL + 1;  // kuroi: prevent false low stock triggers 

      // mc12345678 If the has attibutes then perform the following work.
			if(isset($this->_productI['attributes']) and sizeof($this->_productI['attributes']) >0){
        // mc12345678 Identify a list of attributes associated with the product
				$stock_attributes_search = zen_get_sba_stock_attribute(zen_get_prid($this->_productI['id']), $this->_productI['attributes']);
        
				$get_quantity_query = 'select quantity from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id="' . zen_get_prid($this->_productI['id']) . '" and stock_attributes="' . $stock_attributes_search . '"';

        // mc12345678 Identify the stock available from SBA.
  			$attribute_stock_available = $db->Execute($get_quantity_query);	
        // mc12345678 Identify the stock remaining for the overall stock by removing the number of the current product from the number available for the attributes_id. 
				$attribute_stock_left = $attribute_stock_available->fields['quantity'] - $this->_productI['qty'];
	
        // mc12345678 Update the SBA table to reflect the stock remaining based on the above.
				$attribute_update_query = 'update ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' set quantity='.$attribute_stock_left.' where products_id="' . zen_get_prid($this->_productI['id']) . '" and stock_attributes="' . $stock_attributes_search . '"';
				$db->Execute($attribute_update_query);	
        $this->_attribute_stock_left = $attribute_stock_left;
      }
    }
  }

  /*
   * Function that is activated when NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END is encountered as a notifier.
   */
  // Line 776
  function updateNotifyOrderProcessingStockDecrementEnd(&$callingClass, $notifier, $paramsArray) {
    //Need to modify the email that is going out regarding low-stock.
    //paramsArray is $i at time of development.
    if ($callingClass->email_low_stock == '' && $callingClass->doStockDecrement && $this->_stock_values->RecordCount() > 0 && $this->_attribute_stock_left <= STOCK_REORDER_LEVEL) {
      // kuroi: trigger and details for attribute low stock email
      $callingClass->email_low_stock .=  'ID# ' . zen_get_prid($this->_productI['id']) . ', model# ' . $this->_productI['model'] . ', customid ' . $this->_productI['customid'] . ', name ' . $this->_productI['name'] . ', ';
			foreach($this->_productI['attributes'] as $attributes){
				$callingClass->email_low_stock .= $attributes['option'] . ': ' . $attributes['value'] . ', ';
			}
			$callingClass->email_low_stock .= 'Stock: ' . $this->_attribute_stock_left . "\n\n";
		// kuroi: End Stock by Attribute additions
    }
  }

  /*
   * Function that is activated when NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM is encountered as a notifier.
   */
//Line 883
  function updateNotifyOrderDuringCreateAddedAttributeLineItem(&$callingClass, $notifier, $paramsArray) {
    /* First check to see if SBA is installed and if it is then look to see if a value is 
     *  supplied in the stock_id parameter (which should only be populated when a SBA tracked
     *  item is in the order */
//      $_SESSION['paramsArray'] = $paramsArray;
    if (defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK') && zen_not_null($this->_stock_info['stock_id'])) {  
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
                            'stock_id' => $this->_stock_info['stock_id'], 
                            'stock_attribute' => $this->_stock_info['stock_attribute'], 
                            'customid' => $this->_productI['customid'],
                            'products_prid' =>$paramsArray['products_prid']);
    zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK, $sql_data_array); //inserts data into the TABLE_ORDERS_PRODUCTS_ATTRIBUTES_STOCK table.

    }
  } //endif NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM - mc12345678
  
  
  /*
   * Generic function that is activated when any notifier identified in the observer is called but is not found in one of the above previous specific update functions is encountered as a notifier.
   */
  function update(&$callingClass, $notifier, $paramsArray) {
	global $db;
    
    if ($notifier == 'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM'){
      
    }
    if ($notifier == 'NOTIFY_ORDER_PROCESSING_ATTRIBUTES_BEGIN') {
      
//      $stock_attribute = zen_get_sba_stock_attribute(zen_get_prid($this->products[$i]['id']), $this->products[$i]['attributes']);
//      $stock_id = zen_get_sba_stock_attribute_id(zen_get_prid($this->products[$i]['id']), $this->products[$i]['attributes']); //true; // Need to use the $stock_attribute/attributes to obtain the attribute id.
    }

	} //end update function - mc12345678
} //end class - mc12345678

?>
