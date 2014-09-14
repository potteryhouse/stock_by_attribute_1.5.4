<?php
/**
 * @package includes/functions/extra_functions
 * products_with_attributes.php
 *
 * @copyright Copyright 2003-2014 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  $
 * 
 * Updated for Stock by Attributes 1.5.3.1
 */

//test for multiple entry of same product in shopping cart
function cartProductCount($products_id){
	
	global $db;
	$products_id = zen_get_prid($products_id);
	
	$productCount = $db->Execute('select products_id
  									from ' . TABLE_CUSTOMERS_BASKET . '
  									where products_id like "' . (int)$products_id . ':%"');
	
	return $productCount->RecordCount();
}

/*	Update for Stock by Attributes
 *  Output a form pull down menu
 *  Pulls values from a passed array, with the indicated option pre-selected
 *  
 * This is a copy of the default function from "html_output.php", this version has been extended to support additional parameters.
 * These updates could be rolled back into the core, but to avoid unexpected issues at this time it is separate.
 * HTML-generating functions used with products_with_attributes
 *
 * Use Jquery to change image 'SBA_ProductImage' on selection change
 */
  function zen_draw_pull_down_menu_SBAmod($name, $values, $default = '', $parameters = '', $required = false, $disable = null, $options_menu_images = null) {
		
  	global $template_dir;
  	require('./includes/configure.php');
  	$tmp_attribID = trim($name, 'id[]');//used to get the select ID reference to be used in jquery
  	$field = '<script src="'.DIR_WS_TEMPLATES . $template_dir . '/jscript/jquery-1.10.2.min.js"></script>
			  <script type="text/javascript">
	  			$(function(){
					$("#attrib-'.$tmp_attribID.'").on("click", function(){
						$("#SBA_ProductImage").attr("src", $(this).find(":selected").attr("data-src"));
					});
				});
			</script>';
  					
  	$field .= '<select name="' . zen_output_string($name) . '" onclick=""';

    if (zen_not_null($parameters)) $field .= ' ' . $parameters;

    $field .= '>' . "\n";

    if (empty($default) && isset($GLOBALS[$name]) && is_string($GLOBALS[$name]) ) $default = stripslashes($GLOBALS[$name]);

    for ($i=0, $n=sizeof($values); $i<$n; $i++) {
      $field .= '  <option value="' . zen_output_string($values[$i]['id']) . '"';
      if ($default == $values[$i]['id']) {
        $field .= ' selected="selected"';
      }
      
      //"Stock by Attributes"
      if( $disable && strpos($values[$i]['text'], trim(PWA_OUT_OF_STOCK)) ){
      	$field .= $disable;
      }
      //add image link if available
      if( !empty($options_menu_images[$i]['src']) ){
      	$field .= ' data-src="' . $options_menu_images[$i]['src'] . '"';
      }
      else{
      	$field .= ' data-src="images/no_picture.gif"';
      }
      
      //close tag and add displaed text
      $field .= '>' . zen_output_string($values[$i]['text'], array('"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;')) . '</option>' . "\n";
    }
    
    $field .= '</select>' . "\n";

    if ($required == true) $field .= TEXT_FIELD_REQUIRED;

    return $field;
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
  										where products_id = '. (int)$products_id . ';';
  		$customid = $db->Execute($no_attribute_stock_query);
  		return $customid->fields['products_model'];
  	} 
  	else {
  		
  		if(is_array($attributes) and sizeof($attributes) > 0){
  			// check if attribute stock values have been set for the product
  			// if there are will we continue, otherwise we'll use product level data
			$attribute_stock = $db->Execute("select stock_id 
							  					from " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " 
							  					where products_id = " . (int)$products_id . ";");
  	
  			if ($attribute_stock->RecordCount() > 0) {
  				// search for details for the particular attributes combination
  					$first_search = 'where options_values_id in ("'.implode('","',$attributes).'")';
  				
  				// obtain the attribute ids
  				$query = 'select products_attributes_id 
  						from '.TABLE_PRODUCTS_ATTRIBUTES.' 
  								'.$first_search.' 
  								and products_id='.$products_id.' 
  								order by products_attributes_id;';
  				$attributes_new = $db->Execute($query);
  				
  				while(!$attributes_new->EOF){
  					$stock_attributes[] = $attributes_new->fields['products_attributes_id'];
  					$attributes_new->MoveNext();
  				}

  				if(sizeof($stock_attributes) > 1){
  					$stock_attributes = implode(',',$stock_attributes);
  					$stock_attributes = str_ireplace(',', '","', $stock_attributes);					
  				} else {
  					$stock_attributes = $stock_attributes[0];
  				}
  			}
  			
  			//Get product model
  			$customid_model_query = 'select products_model 
						  					from '.TABLE_PRODUCTS.' 
						  					where products_id = '. (int)$products_id . ';';

  			//Get custom id as products_model
  			$customid_query = 'select customid as products_model
		  							from '.TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK.' 
		  							where products_id = '.(int)$products_id.' 
		  							and stock_attributes in ("'.$stock_attributes.'");';  
  		}
  		
  		$customid = $db->Execute($customid_query);
  		if($customid->fields['products_model']){
  		
	  		//Test to see if a custom ID exists
	  		//if there are custom IDs with the attribute, then return them.
	  			$multiplecid = null;
	  			while(!$customid->EOF){
	  				$multiplecid .= $customid->fields['products_model'] . ', ';
	  				$customid->MoveNext();
	  			}
	  			$multiplecid = rtrim($multiplecid, ', ');
	  			
	  			//return result for display
	  			return $multiplecid;
	  	
  		}
  		else{
  			$customid = null;
  			//This is used as a fall-back when custom ID is set to be displayed but no attribute is available.
  			//Get product model
  			$customid_model_query = 'select products_model
						  					from '.TABLE_PRODUCTS.'
						  					where products_id = '. (int)$products_id . ';';
  			$customid = $db->Execute($customid_model_query);
  			//return result for display
  			return $customid->fields['products_model'];
  		}
  		return;//nothing to return, should never reach this return
  	}
  }//end of function
