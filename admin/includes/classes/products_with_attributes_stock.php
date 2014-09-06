<?php
/**
 * @package admin
 * @copyright Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: products_with_attributes_stock.php  $
 *
 * Updated for Stock by Attributes 1.5.4
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class products_with_attributes_stock
	{	
		function get_products_attributes($products_id, $languageId=1)
		{
			global $db;
			// Added the following to query "and pa.attributes_display_only != 1" This removed read only attributes from the stock selection.
			$query = '	select pa.products_attributes_id, pa.options_values_price, pa.price_prefix,
			 				po.products_options_name, pov.products_options_values_name
			 			from '.TABLE_PRODUCTS_ATTRIBUTES.' pa
			 			left join '.TABLE_PRODUCTS_OPTIONS.' po on (pa.options_id = po.products_options_id)
			 			left join '.TABLE_PRODUCTS_OPTIONS_VALUES.' pov on (pa.options_values_id = pov.products_options_values_id)
			 			where pa.products_id = "'.$products_id.'"  
			 				AND po.language_id = "'.$languageId.'" and po.language_id = pov.language_id
							and pa.attributes_display_only != 1';
			
			$attributes = $db->Execute($query);
			
			if($attributes->RecordCount()>0)
			{
				while(!$attributes->EOF)
				{
					$attributes_array[$attributes->fields['products_options_name']][] =
						array('id' => $attributes->fields['products_attributes_id'],
							  'text' => $attributes->fields['products_options_values_name']
							  			. ' (' . $attributes->fields['price_prefix']
										. '$'.zen_round($attributes->fields['options_values_price'],2) . ')' );
					$attributes->MoveNext();
				}
	
				return $attributes_array;
	
			}
			else
			{
				return false;
			}
		}
	
		function update_parent_products_stock($products_id)
		{
			global $db;

			$query = 'select sum(quantity) as quantity from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' where products_id = "'.(int)$products_id.'"';
			$quantity = $db->Execute($query);
			$query = 'update '.TABLE_PRODUCTS.' set  products_quantity="'.$quantity->fields['quantity'].'" where products_id="'.(int)$products_id.'"';
			$db->Execute($query);
		}
    
    function update_all_parent_products_stock() {
      global $db;
      $products_array = $this->get_products_with_attributes();
      foreach ($products_array as $products_id) {
        $query = 'select sum(quantity) as quantity from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' where products_id = "'.(int)$products_id.'"';
        $quantity = $db->Execute($query);
        $query = 'update '.TABLE_PRODUCTS.' set  products_quantity="'.$quantity->fields['quantity'].'" where products_id="'.(int)$products_id.'"';
        $db->Execute($query);
      }
    }
    
    // returns an array of product ids which contain attributes
    function get_products_with_attributes() {
      global $db;
      if(isset($_SESSION['languages_id'])){ $language_id = (int)$_SESSION['languages_id'];} else { $language_id=1;}
      $query = 'SELECT DISTINCT pa.products_id, d.products_name, p.products_quantity, p.products_model, p.products_image
                FROM '.TABLE_PRODUCTS_ATTRIBUTES.' pa
                left join '.TABLE_PRODUCTS_DESCRIPTION.' d on (pa.products_id = d.products_id)
                left join '.TABLE_PRODUCTS.' p on (pa.products_id = p.products_id)
                WHERE  d.language_id='.$language_id.' 
                ORDER BY d.products_name ';
      $products = $db->Execute($query);
      while(!$products->EOF){
        $products_array[] = $products->fields['products_id'];
        $products->MoveNext();
      }
      return $products_array;
    }
	
	
		function get_attributes_name($attribute_id, $languageId=1)
		{
			global $db;

			$query = 'select pa.products_attributes_id, po.products_options_name, pov.products_options_values_name
			 			from '.TABLE_PRODUCTS_ATTRIBUTES.' pa
			 			left join '.TABLE_PRODUCTS_OPTIONS.' po on (pa.options_id = po.products_options_id)
			 			left join '.TABLE_PRODUCTS_OPTIONS_VALUES.' pov on (pa.options_values_id = pov.products_options_values_id)
			 			where pa.products_attributes_id = "'.$attribute_id.'"
							AND po.language_id = "'.$languageId.'"
							and po.language_id = pov.language_id';
							
			$attributes = $db->Execute($query);
			if(!$attributes->EOF)
			{		
				$attributes_output = array('option' => $attributes->fields['products_options_name'],
										   'value' => $attributes->fields['products_options_values_name']);
				return $attributes_output;
			}
			else
			{
				return false;
			}
		}
        
        
/**
 * @desc displays the filtered product-rows
 * 
 * Passed Options
 * $SearchBoxOnly
 * $ReturnedPage
 * $NumberRecordsShown
 */
function displayFilteredRows($SearchBoxOnly = null, $NumberRecordsShown = null, $ReturnedProductID = null){
        global $db;
      
        if(isset($_SESSION['languages_id'])){ $language_id = $_SESSION['languages_id'];} else { $language_id=1;}
        if( isset($_GET['search']) ){
            $s = zen_db_input($_GET['search']);
         	//$w = "(p.products_id = '$s' OR d.products_name LIKE '%$s%' OR p.products_model LIKE '%$s%') AND  " ;//original version of search
            //$w = "( p.products_id = '$s' OR d.products_name LIKE '%$s%' OR p.products_model LIKE '$s%' ) AND  " ;//changed search to products_model 'startes with'.
         	//$w = "( p.products_id = '$s' OR d.products_name LIKE '%$s%' ) AND  " ;//removed products_model from search
            $w = " AND ( p.products_id = '$s' OR d.products_name LIKE '%$s%' OR p.products_model LIKE '$s%' ) " ;//changed search to products_model 'startes with'.
		} 
		else {
		    $w = ''; 
			$s = '';
		}

      	//Show last edited record or Limit number of records displayed on page
      	$SearchRange = null;
      	if( $ReturnedProductID != null && !isset($_GET['search']) ){
      		$ReturnedProductID = zen_db_input($ReturnedProductID);
      		//$w = "( p.products_id = '$ReturnedProductID' ) AND  " ;//sets returned record to display
      		$w = " AND ( p.products_id = '$ReturnedProductID' ) " ;//sets returned record to display
	      	$SearchRange = "limit 1";//show only selected record
	  	}
	  	elseif( $NumberRecordsShown > 0 && $SearchBoxOnly == 'false' ){
	  		$NumberRecordsShown = zen_db_input($NumberRecordsShown);
			$SearchRange = " limit $NumberRecordsShown";//sets start record and total number of records to display
		}
		elseif( $SearchBoxOnly == 'true' && !isset($_GET['search']) ){
		   	$SearchRange = "limit 0";//hides all records
		}

    $html = zen_draw_form('stock_update', FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK . '_ajax', 'save=1&pid='.$ReturnedProductID, 'post', 'NONSSL');
    $html .= zen_image_submit('button_save.gif', IMAGE_SAVE) . ' Hint: To quickly edit click in the "Quantity in Stock" field.';
    $html .= '
    <table id="mainProductTable"> 
    <tr>
      <th class="thProdId">'.PWA_PRODUCT_ID.'</th>
      <th class="thProdName">'.PWA_PRODUCT_NAME.'</th>';
    
    if (STOCK_SHOW_IMAGE == 'true') {$html .= '<th class="thProdImage">'.PWA_PRODUCT_IMAGE.'</th>';}   

    $html .= '<th class="thProdModel">'.PWA_PRODUCT_MODEL.'</th>            
		      <th class="thProdQty">'.PWA_QUANTITY_FOR_ALL_VARIANTS.'</th>
		      <th class="thProdAdd">'.PWA_ADD_QUANTITY.'</th> 
		      <th class="thProdSync">'.PWA_SYNC_QUANTITY.'</th>
		      </tr>';
       
        $retArr = array();
        $query =    'select distinct pa.products_id, d.products_name, p.products_quantity, 
						p.products_model, p.products_image, p.products_type, p.master_categories_id
						
						FROM '.TABLE_PRODUCTS_ATTRIBUTES.' pa
						left join '.TABLE_PRODUCTS_DESCRIPTION.' d on (pa.products_id = d.products_id)
						left join '.TABLE_PRODUCTS.' p on (pa.products_id = p.products_id)
						
						WHERE d.language_id='.$language_id.'
						' . $w . '
						order by d.products_name
						'.$SearchRange.'';
        
        $products = $db->Execute($query);
        
        while(!$products->EOF){ 
			    $html .= '<tr>'."\n";
			    $html .= '<td colspan="7">'."\n";
			    $html .= '<div class="productGroup">'."\n";
			    $html .= '<table width="100%">'."\n";
		        $html .= '<tr class="productRow">'."\n";
		        $html .= '<td class="tdProdId" class="pwas">'.$products->fields['products_id'].'</td>';
		        $html .= '<td class="tdProdName">'.$products->fields['products_name'].'</td>';
		        
		        if (STOCK_SHOW_IMAGE == 'true') {$html .= '<td class="tdProdImage">'.zen_info_image($products->fields['products_image'], $products->fields['products_name'], "60", "60").'</td>';}
		        
		        //product.php? page=1 & product_type=1 & cPath=13 & pID=1042 & action=new_product
		        //$html .= '<td class="tdProdModel">'.$products->fields['products_model'] .' </td>';
		        $html .= '<td class="tdProdModel">'.$products->fields['products_model'] . '<br /><a href="'.zen_href_link(FILENAME_PRODUCT, "page=1&amp;product_type=".$products->fields['products_type']."&amp;cPath=".$products->fields['master_categories_id']."&amp;pID=".$products->fields['products_id']."&amp;action=new_product", 'NONSSL').'">Link</a> </td>';
		        $html .= '<td class="tdProdQty">'.$products->fields['products_quantity'].'</td>';
		        $html .= '<td class="tdProdAdd"><a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=add&amp;products_id=".$products->fields['products_id'], 'NONSSL').'">' . PWA_ADD_QUANTITY . '</a></td>';
		        $html .= '<td class="tdProdSync"><a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=resync&amp;products_id=".$products->fields['products_id'], 'NONSSL').'">' . PWA_SYNC_QUANTITY . '</a></td>';
		        $html .= '</tr>'."\n";
		        $html .= '</table>'."\n";
		        
          // SUB            
          $query = 'select * from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' where products_id="'.$products->fields['products_id'].'"
                    order by sort ASC;';

          $attribute_products = $db->Execute($query);
          if($attribute_products->RecordCount() > 0){

              $html .= '<table class="stockAttributesTable">';
              $html .= '<tr>';
              $html .= '<th class="stockAttributesHeadingStockId">'.PWA_STOCK_ID.'</th>
              			<th class="stockAttributesHeadingComboId" title="This number is the Product ID and related Attributes (Unique Combo).">'.PWA_PAC.'</th>
              			<th class="stockAttributesHeadingVariant">'.PWA_VARIANT.'</th>
              			<th class="stockAttributesHeadingQuantity">'.PWA_QUANTITY_IN_STOCK.'</th>
              			<th class="stockAttributesHeadingSort">'.PWA_SORT_ORDER.'</th>
              			<th class="stockAttributesHeadingCustomid" title="The Custom ID MUST be Unique, no duplicates allowed!">'.PWA_CUSTOM_ID.'</th>
              			<th class="stockAttributesHeadingSKUTitleId">'.PWA_SKU_TITLE.'</th>
              			<th class="stockAttributesHeadingEdit">'.PWA_EDIT.'</th>
              			<th class="stockAttributesHeadingDelete">'.PWA_DELETE.'</th>
              			<th class="stockAttributesHeadingSpace"></th>
              			<th class="stockAttributesHeadingParent" title="Parent Stock ID">'.PWA_PARENT.'</th>
              			<th class="stockAttributesHeadingChild" title="Child Stock ID">'.PWA_CHILD.'</th>
              			<th class="stockAttributesHeadingSibling" title="Sibling Stock ID">'.PWA_SIBLING.'</th>';
              $html .= '</tr>';

              while(!$attribute_products->EOF){
              	
                  $html .= '<tr id="sid-'. $attribute_products->fields['stock_id'] .'">';
                  $html .= '<td class="stockAttributesCellStockId">'."\n";
                  $html .= $attribute_products->fields['stock_id'];
                  $html .= '</td>'."\n";
                  $html .= '<td>' . $attribute_products->fields['product_attribute_combo'] . '</td>'."\n";
                  $html .= '<td class="stockAttributesCellVariant">'."\n";
                 
                  $attributes_of_stock = explode(',',$attribute_products->fields['stock_attributes']);
                  $attributes_output = array();
                  foreach($attributes_of_stock as $attri_id)
                  {
                      $stock_attribute = $this->get_attributes_name($attri_id, $_SESSION['languages_id']);
                      if ($stock_attribute['option'] == '' && $stock_attribute['value'] == '') {
                        // delete stock attribute
                        $db->Execute("DELETE FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " WHERE stock_id = " . $attribute_products->fields['stock_id'] . " LIMIT 1;");
                      } else { 
                        $attributes_output[] = '<strong>'.$stock_attribute['option'].':</strong> '.$stock_attribute['value'].'<br/>';
                      }
                  }
                  sort($attributes_output);
                  $html .= implode("\n",$attributes_output);

                  $html .= '</td>'."\n";
                  $html .= '<td class="stockAttributesCellQuantity" id="stockid2-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['quantity'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellSort" id="stockid3-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['sort'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellCustomid" id="stockid4-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['customid'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellTitle" id="stockid1-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['title'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellEdit">'."\n";
                  $html .= '<a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=edit&amp;products_id=".$products->fields['products_id'].'&amp;attributes='.$attribute_products->fields['stock_attributes'].'&amp;q='.$attribute_products->fields['quantity'], 'NONSSL').'">'.PWA_EDIT_QUANTITY.'</a>'; //s_mack:prefill_quantity
                  $html .= '</td>'."\n";
                  $html .= '<td class="stockAttributesCellDelete">'."\n";
                  $html .= '<a href="'.zen_href_link(FILENAME_PRODUCTS_WITH_ATTRIBUTES_STOCK, "action=delete&amp;products_id=".$products->fields['products_id'].'&amp;attributes='.$attribute_products->fields['stock_attributes'], 'NONSSL').'">'.PWA_DELETE_VARIANT.'</a>';
                  
                  $html .= '<td class="stockAttributesCellSpace"></td>'."\n";
                  $html .= '<td class="stockAttributesCellParent" id="stockid5-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['parentid'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellChild" id="stockid6-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['siblingid'].'</td>'."\n";
                  $html .= '<td class="stockAttributesCellSibling" id="stockid7-'. $attribute_products->fields['stock_id'] .'">'.$attribute_products->fields['childid'].'</td>'."\n";
                  
                  $html .= '</div>';
                  
                  $html .= '</td>'."\n";
                  $html .= '</tr>'."\n";
                 

                  $attribute_products->MoveNext();
              }
              $html .= '</table>';
          }
          $products->MoveNext();   
      }
      $html .= '</table>';
      $html .= zen_image_submit('button_save.gif', IMAGE_SAVE);
      $html .= '</form>'."\n";

      return $html;
    }

//Used with jquery to edit qty on stock page and to save
function saveAttrib(){

	global $db;
	$stock = new products_with_attributes_stock;
    $i = 0;
    foreach ($_POST as $key => $value) {
    	$id1 = intval(str_replace('stockid1-', '', $key));//title
       	$id2 = intval(str_replace('stockid2-', '', $key));//quantity
    	$id3 = intval(str_replace('stockid3-', '', $key));//sort
    	$id4 = intval(str_replace('stockid4-', '', $key));//customid
    	$id5 = intval(str_replace('stockid5-', '', $key));//parentid
    	$id6 = intval(str_replace('stockid6-', '', $key));//siblingid
    	$id7 = intval(str_replace('stockid7-', '', $key));//childid

        if($id1 > 0){
        	$value = $stock->nullDataEntry($value);
        	if(empty($value) || is_null($value)){$value = 'null';}
       		$sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET title = $value WHERE stock_id = " .$id1. " LIMIT 1";
       		$db->execute($sql);
        	$i++;
        }
        if($id2 > 0){
        	$value = doubleval($value);
        	if(empty($value) || is_null($value)){$value = 0;}
			$sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET quantity = $value WHERE stock_id = " .$id2. " LIMIT 1";
            $db->execute($sql);
            $i++;
        }      
        if($id3 > 0){
        	$value = doubleval($value);
        	if(empty($value) || is_null($value)){$value = 0;}
        	$sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET sort = $value WHERE stock_id = " .$id3. " LIMIT 1";
        	$db->execute($sql);
        	$i++;
        }
        if($id4 > 0){
        	$value = addslashes($value);
        	$value = $stock->nullDataEntry($value);
        	if(empty($value) || is_null($value)){$value = 'null';}
        	$sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET customid = $value WHERE stock_id = " .$id4. " LIMIT 1";
        	$db->execute($sql);
        	$i++;
        }

        if($id5 > 0){
        	$value = addslashes($value);
        	$value = $stock->nullDataEntry($value);
        	if(empty($value) || is_null($value)){$value = 'null';}
        	$sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET parentid = $value WHERE stock_id = " .$id5. " LIMIT 1";
        	$db->execute($sql);
        	$i++;
        }
        if($id6 > 0){
        	$value = addslashes($value);
        	$value = $stock->nullDataEntry($value);
        	if(empty($value) || is_null($value)){$value = 'null';}
        	$sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET siblingid = $value WHERE stock_id = " .$id6. " LIMIT 1";
        	$db->execute($sql);
        	$i++;
        }
        if($id7 > 0){
        	$value = addslashes($value);
        	$value = $stock->nullDataEntry($value);
        	if(empty($value) || is_null($value)){$value = 'null';}
        	$sql = "UPDATE ".TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK." SET childid = $value WHERE stock_id = " .$id7. " LIMIT 1";
        	$db->execute($sql);
        	$i++;
        }
        
        
    }
    $html = print_r($_POST, true);
    $html = "$i DS SAVED";
    return $html;  
}

//Update attribute qty
function updateAttribQty($stock_id = null, $quantity = null){
	global $db;

	if(empty($quantity) || is_null($quantity)){$quantity = 0;}
	if( is_numeric($stock_id) && is_numeric($quantity) ){
		$query = 'update `'.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.'` set quantity='.$quantity.' where stock_id='.$stock_id.' limit 1';
		$result = $db->execute($query);
	}

	return $result;
}

//New attribute qty insert
//The on duplicate updates an existing record instead of adding a new one
function insertNewAttribQty($products_id = null, $productAttributeCombo = null, $strAttributes = null, $quantity = 0, $customid = null, $skuTitle = null){
	global $db;
	$stock = new products_with_attributes_stock;
	$productAttributeCombo = $stock->nullDataEntry($productAttributeCombo);//sets proper quoting for input
	$skuTitle = addslashes($skuTitle);
	$skuTitle = $stock->nullDataEntry($skuTitle);//sets proper quoting for input
	$customid = addslashes($customid);
	$customid = $stock->nullDataEntry($customid);//sets proper quoting for input
	$strAttributes = $stock->nullDataEntry($strAttributes);//sets proper quoting for input
	
	//Set quantity to 0 if not valid input
	if( !is_numeric($quantity) ){
		$quantity = 0;
	}
	
	if( is_numeric($products_id) && isset($strAttributes) ){
 		$query = "insert into ". TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK ." (`products_id`, `product_attribute_combo`, `stock_attributes`, `quantity`, `customid`, `title`) 
 					values ($products_id, $productAttributeCombo, $strAttributes, $quantity, $customid, $skuTitle)
 							ON DUPLICATE KEY UPDATE 
 							`quantity` = $quantity,
					 		`customid` =  $customid,
 							`title` = $skuTitle";
 		$result = $db->execute($query);
	}
	
	return $result;
}

//IN-WORK New attribute qty insert NEEDS MORE THOUGHT
//NEED one qty for multiple attributes, this does not accomplish this.
//The on duplicate updates an existing record instead of adding a new one
function insertTablePASR($products_id = null, $strAttributes = null, $quantity = null, $customid = null){
	
	global $db;
	$stock = new products_with_attributes_stock;
	$customid = addslashes($customid);
	$customid = $stock->nullDataEntry($customid);//sets proper quoting for input
	$strAttributes = $stock->nullDataEntry($strAttributes);//sets proper quoting for input

	//INSERT INTO `znc_products_attributes_stock_relationship` (`products_id`, `products_attributes_id`, `products_attributes_stock_id`) VALUES (226, 1121, 37);
	
	//Table PASR (Inset and get $pasrid for next query)
	if( is_numeric($products_id) && isset($strAttributes) ){
		
		//Get the last records ID
		$query = "select pas.products_attributes_stock_id
					from  ". TABLE_PRODUCTS_ATTRIBUTES_STOCK ."  pas
					order by pas.products_attributes_stock_id desc
					limit 1;";
		$result = $db->execute($query);
		$pasid = $result->fields['products_attributes_stock_id'];
		$pasid = ($pasid +1);//increment to next value
		$pasid = $stock->nullDataEntry($pasid);//sets proper quoting for input
		
		$query = "insert into ". TABLE_PRODUCTS_ATTRIBUTES_STOCK_RELATIONSHIP ." (`products_id`,`products_attributes_id`, `products_attributes_stock_id`)
					values ($products_id, $strAttributes, $pasid)
					ON DUPLICATE KEY UPDATE
					`products_id` = $products_id,
					`products_attributes_id` =  $strAttributes;";
		$result = $db->execute($query);
	
		if( $result == 'true' ){
			//Get the last records ID
			$query = "select pasr.products_attributes_stock_relationship_id
						from ". TABLE_PRODUCTS_ATTRIBUTES_STOCK_RELATIONSHIP ." pasr
						order by pasr.products_attributes_stock_relationship_id desc
						limit 1;";
			$result = $db->execute($query);
			$pasrid = $result->fields['products_attributes_stock_relationship_id'];
		}
	}
	
	//Table PAS
	if( is_numeric($quantity) && is_numeric($pasrid) ){
		
		$query = "insert into ". TABLE_PRODUCTS_ATTRIBUTES_STOCK ." (`quantity`,`customid`)
					values ($quantity, $customid)
					ON DUPLICATE KEY UPDATE
					`quantity` = $quantity,
					`customid` =  $customid;";
		$result = $db->execute($query);
		
// 		//Get the last records ID
// 		$query = "select pas.products_attributes_stock_id
// 					from  ". TABLE_PRODUCTS_ATTRIBUTES_STOCK ."  pas
// 					order by pas.products_attributes_stock_id desc
// 					limit 1;";
// 		$result = $db->execute($query);
// 		$pasid = $result->fields['products_attributes_stock_id'];
		
// 		$pasid = $stock->nullDataEntry($pasid);//sets proper quoting for input
		
		//UPDATE `znc_products_attributes_stock_relationship` SET `products_attributes_stock_id`=26 WHERE  `products_attributes_stock_relationship_id`=27 LIMIT 1;
// 		$query = "UPDATE ". TABLE_PRODUCTS_ATTRIBUTES_STOCK_RELATIONSHIP ." SET `products_attributes_stock_id` = $pasid
// 						where `products_attributes_stock_relationship_id` = $pasrid
// 						LIMIT 1;";
// 		$result = $db->execute($query);
		
	}
	else {
		//PANIC we had an error!!!
		exit;
	}

 	return $result;
}

//IN-WORK New attribute qty insert NEEDS MORE THOUGHT
//products_attributes_stock INWORK New attribute qty insert NEEDS MORE THOUGHT
//NEED one qty for multiple attributes, this does not accomplish this.
//The on duplicate updates an existing record instead of adding a new one
function insertTablePAS($products_id = null, $quantity = null, $customid = null){

	global $db;
	$stock = new products_with_attributes_stock;
	$customid = addslashes($customid);
	$customid = $stock->nullDataEntry($customid);//sets proper quoting for input

// 	INSERT INTO `znc_products_attributes_stock` (`products_id`, `quantity`, `customid`) VALUES (226, 636, 'test37');
	
	//Table PASR (Inset and get $pasrid for next query)
	if( is_numeric($products_id) ){

		$query = "insert into ". TABLE_PRODUCTS_ATTRIBUTES_STOCK ." (`products_id`,`quantity`,`customid`)
					values ($products_id, $quantity, $customid)
					ON DUPLICATE KEY UPDATE
					`products_id` = $products_id,
					`quantity` = $quantity,
					`customid` =  $customid;";
		$result = $db->execute($query);

	}
	else {
		//PANIC we had an error!!!
		exit('PANIC we had an error!!!');
	}

	return $result;
}
//ABOVE IN-WORK New attribute qty insert NEEDS MORE THOUGHT

//Update Custom ID of Attribute using the StockID as a key
function updateCustomIDAttrib($stockid = null, $customid = null){
	global $db;
	$stock = new products_with_attributes_stock;
	$customid = addslashes($customid);
	$customid = $stock->nullDataEntry($customid);//sets proper quoting for input

	if( $customid && is_numeric($stockid) ){
		$query = 'update ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' set customid = ' . $customid . ' where stock_id = ' . $stockid . ' limit 1';
		$result = $db->execute($query);
	}

	return $result;
}

//Update  sku Title of Attribute using the StockID as a key
function updateTitleAttrib($stockid = null, $skuTitle = null){
	global $db;
	$stock = new products_with_attributes_stock;
	$skuTitle = addslashes($skuTitle);
	$skuTitle = $stock->nullDataEntry($skuTitle);//sets proper quoting for input

	if( $skuTitle && is_numeric($stockid) ){
		$query = 'update ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' set title = ' . $skuTitle . ' where stock_id = ' . $stockid . ' limit 1';
		$result = $db->execute($query);
	}

	return $result;
}

//************************* Select list Function *************************//
//need to update to allow passing the data for $Item "$Item = 'ID:&nbsp;&nbsp;' . $result->fields["products_id"];"
//used to get a rows id number based on the table and column
//$Table = the table in the database to use.
//$Field = the field name from the table to use that has primary key or other uniqueness.
//The $Field is used as the default 'name' for the post event.
//$current = the current value in the database for this item.
//$providedQuery = This is a provided query that overrides the default $Table and $Field,
//note the $Field input (field name) is required to get returned data if name is not set or if there is not a $providedQuery.
function selectItemID($Table, $Field, $current = null, $providedQuery = null, $name= null, $id = null, $class = null, $style = null, $onChange = null){

	global $db;
	
	if(!$name){
		//use the $Field as the select NAME if no $name is provided
		$name = zen_db_input($Field);
	}
	if(!$id){
		//use the $Field as the select ID if no $id is provided
		$id = zen_db_input($Field);
	}

	if($providedQuery){
		$query = $providedQuery;//provided from calling object
	}
	else{
		$Table = zen_db_input($Table);
		$Field = zen_db_input($Field);
 		$query = "SELECT * FROM $Table ORDER BY $Field ASC";
	}

	if($onChange){
		$onChange = "onchange=\"selectItem()\"";
	}
		
	$class = zen_db_input($class);
	
	$Output = "<SELECT class='".$class."' id='".$id."' name='".$name."' $onChange >";//create selection list
    $Output .= "<option value='' $style></option>";//adds blank entry as first item in list

	/* Fields that may be of use in returned set
	["products_id"]
	["products_name"]
	["products_quantity"]
	["products_model"]
	["products_image"]
	 */
    $i = 1;
	$result = $db->Execute($query);
   	while(!$result->EOF){

   		//set each row background color
   		if($i == 1){
   			$style = 'style="background-color:silver;"';
   			$i = 0;
   		}
   		else{
   			$style = null;//'style="background-color:blue;"';
   			$i = 1;
   		}
   		
        $rowID = $result->fields["products_id"];
        $Item = 'ID:&nbsp;&nbsp;' . $result->fields["products_id"];
        $Item .= '&nbsp;&nbsp;Model:&nbsp;&nbsp;' . $result->fields["products_model"];
        $Item .= '&nbsp;&nbsp;Name:&nbsp;&nbsp;' . $result->fields["products_name"];
            
		if ( ($Item == $current AND $current != NULL) || ($rowID == $current AND $current != NULL) ){
	    	$Output .= "<option selected='selected' $style value='".$rowID."'>$Item</option>";
	    }
	    else{
	    	$Output .= "<option $style value='".$rowID."'>$Item</option>";
	    }

		$result->MoveNext();
	}

	$Output .= "</select>";

	return $Output;
}

//NULL entry for database
function nullDataEntry($fieldtoNULL){

	//Need to test for absolute 0 (===), else compare will convert $fieldtoNULL to a number (null) and evauluate as a null 
	//This is due to PHP string to number compare "feature"
	if(!empty($fieldtoNULL) || $fieldtoNULL === 0){
		if(is_numeric($fieldtoNULL) || $fieldtoNULL === 0){
			$output = $fieldtoNULL;//returns number without quotes
		}
		else{
			$output = "'".$fieldtoNULL."'";//encases the string in quotes
		}
	}
	else{
		$output = 'null';
	}

	return $output;
}

  /* ********************************************************************* */
  /*  Ported from rhuseby: (my_stock_id MOD) and modified for SBA customid */
  /*  Added function to support attribute specific part numbers            */
  /* ********************************************************************* */
  function zen_get_customid($products_id, $attributes = null) {
  	global $db;
  	$customid_model_query = null;
  	$customid_query = null;
  	$products_id = zen_get_prid($products_id);
  
  	// check if there are attributes for this product
 	$stock_has_attributes = $db->Execute('select products_attributes_id 
  											from '.TABLE_PRODUCTS_ATTRIBUTES.' 
  											where products_id = ' . (int)$products_id . '');

  	if ( $stock_has_attributes->RecordCount() < 1 ) {
  		
  			//if no attributes return products_model
			$no_attribute_stock_query = 'select products_model 
  										from '.TABLE_PRODUCTS.' 
  										where products_id = '. (int)$products_id . '';
  		$customid = $db->Execute($no_attribute_stock_query);
  		return $customid->fields['products_model'];
  	} 
  	else {
  		
  		if(is_array($attributes) and sizeof($attributes) > 0){
  			// check if attribute stock values have been set for the product
  			// if there are will we continue, otherwise we'll use product level data
			$attribute_stock = $db->Execute("select stock_id 
							  					from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " 
							  					where products_id = " . (int)$products_id . "");
  	
  			if ($attribute_stock->RecordCount() > 0) {
  				// search for details for the particular attributes combination
  				if(sizeof($attributes) > 1){
  					$first_search = 'where options_values_id in ("'.implode('","',$attributes).'")';
  				} else {
  					//even though there is only one attribute, it is still in an array and must be retrieved
  					foreach($attributes as $attribute){
  						$first_search = ' where options_values_id="'.$attribute.' "';
  					}
  				}
  				
  				// obtain the attribute ids
  				$query = 'select products_attributes_id 
  						from '.TABLE_PRODUCTS_ATTRIBUTES.' 
  								'.$first_search.' 
  								and products_id='.$products_id.' 
  								order by products_attributes_id';
  				$attributes_new = $db->Execute($query);
  				
  				while(!$attributes_new->EOF){
  					$stock_attributes[] = $attributes_new->fields['products_attributes_id'];
  					$attributes_new->MoveNext();
  				}
  				if(sizeof($stock_attributes) > 1){
  					$stock_attributes = implode(',',$stock_attributes);
  				} else {
  					$stock_attributes = $stock_attributes[0];
  				}
  			}
  			
  			//Get product model
  			$customid_model_query = 'select products_model 
						  					from '.TABLE_PRODUCTS.' 
						  					where products_id = '. (int)$products_id . '';

  			//Get custom id as products_model
  			$customid_query = 'select customid as products_model
		  							from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' 
		  							where products_id = '.(int)$products_id.' 
		  							and stock_attributes = "'.$stock_attributes.'"';  
  		}
  		
  		if($customid_query){
  		
	  		//Test to see if a custom ID exists
	  		$has_customid = $db->Execute($customid_query);
	  		//if there is a custom ID with the attribute, then return it.
	  		if ($has_customid->RecordCount() > 0) {
	  			$customid = $db->Execute($customid_query);
	  		} 
	  		else {//if no custom ID then return the products_model from the product
	  	  		$customid = $db->Execute($customid_model_query);
	  		}
	  	
  		//return result for display
  		return $customid->fields['products_model'];
  		}
  		else{
  			//This is used as a fallback when custom ID is set to be displayed but no attribute is available.
  			//Get product model
  			$customid_model_query = 'select products_model
						  					from '.TABLE_PRODUCTS.'
						  					where products_id = '. (int)$products_id . '';
  			$customid = $db->Execute($customid_model_query);
  			//return result for display
  			return $customid->fields['products_model'];
  		}
  		return;//nothing to return, should never reach this return
  	}
  }//end of function
  
}//end of class