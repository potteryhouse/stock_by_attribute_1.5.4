<?php

/**
 * attributes module
 *
 * Prepares attributes content for rendering in the template system
 * Prepares HTML for input fields with required uniqueness so template can display them as needed and keep collected data in proper fields
 *
 * @package modules
 * @copyright Copyright 2003-2012 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id: Author: Ian Wilson  Tue Aug 14 14:56:11 2012 +0100 Modified in v1.5.1 $
 * 
 * Stock by Attributes 1.5.4
 */
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}

$show_onetime_charges_description = 'false';
$show_attributes_qty_prices_description = 'false';

//limit to 1 for performance when processing larger tables
//gets the number of associated attributes
$sql = "select count(*) as total
          from " . TABLE_PRODUCTS_OPTIONS . " po 
			left join " . TABLE_PRODUCTS_ATTRIBUTES . " pa ON (po.products_options_id = pa.options_id)
          where    pa.products_id='" . (int) $_GET['products_id'] . "'
            and      po.language_id = '" . (int) $_SESSION['languages_id'] . "'" .
        " limit 1";

$pr_attr = $db->Execute($sql);

//only continue if there are attributes
if ($pr_attr->fields['total'] > 0) {
  //LPAD - Return the string argument, left-padded with the specified string 
  //example: LPAD(po.products_options_sort_order,11,"0") the field is 11 digits, and is left padded with 0
  if (PRODUCTS_OPTIONS_SORT_ORDER == '0') {
    $options_order_by = ' order by LPAD(po.products_options_sort_order,11,"0")';
  } else {
    $options_order_by = ' order by po.products_options_name';
  }

  //get the option/attribute list
  $sql = "select distinct po.products_options_id, po.products_options_name, po.products_options_sort_order,
                              po.products_options_type, po.products_options_length, po.products_options_comment,
                              po.products_options_size,
                              po.products_options_images_per_row,
                              po.products_options_images_style,
                              po.products_options_rows
              from        " . TABLE_PRODUCTS_OPTIONS . " po
              left join " . TABLE_PRODUCTS_ATTRIBUTES . " pa ON (pa.options_id = po.products_options_id)
              where           pa.products_id='" . (int) $_GET['products_id'] . "'             
              and             po.language_id = '" . (int) $_SESSION['languages_id'] . "' " .
          $options_order_by;

  $products_options_names = $db->Execute($sql);
$products_options_names_count = $products_options_names->RecordCount();
  // iii 030813 added: initialize $number_of_uploads
  $number_of_uploads = 0;

  if (PRODUCTS_OPTIONS_SORT_BY_PRICE == '1') {
    $order_by = ' order by LPAD(pa.products_options_sort_order,11,"0"), pov.products_options_values_name';
  } else {
    $order_by = ' order by LPAD(pa.products_options_sort_order,11,"0"), pa.options_values_price';
  }

  $discount_type = zen_get_products_sale_discount_type((int) $_GET['products_id']);
  $discount_amount = zen_get_discount_calc((int) $_GET['products_id']);

              $zv_display_select_option = 0;
              //loop for each option/attribute listed
              while (!$products_options_names->EOF) {
                $products_options_array = array();
                $options_menu_images = array();

    /*
      pov.products_options_values_id, pov.products_options_values_name,
      pa.options_values_price, pa.price_prefix,
      pa.products_options_sort_order, pa.product_attribute_is_free, pa.products_attributes_weight, pa.products_attributes_weight_prefix,
      pa.attributes_default, pa.attributes_discounted, pa.attributes_image
      p.products_quantity
     */

    /* Original query
      $sql = "select	pov.products_options_values_id,
      pov.products_options_values_name,
      pa.*, p.products_quantity

      from      " . TABLE_PRODUCTS_ATTRIBUTES . " pa
      left join " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov on (pa.options_values_id = pov.products_options_values_id)
      left join " . TABLE_PRODUCTS . " p on (pa.products_id = p.products_id)

      where pa.products_id = '" . (int)$_GET['products_id'] . "'
      and   pa.options_id  = '" . (int)$products_options_names->fields['products_options_id'] . "'
      and   pov.language_id = '" . (int)$_SESSION['languages_id'] . "' " .
      $order_by;
     */
    /*
      $sql = "select	pov.products_options_values_id,
      pov.products_options_values_name,
      pa.*, p.products_quantity, pas.stock_id as pasid, pas.quantity as pasqty, pas.customid

      from      " . TABLE_PRODUCTS_ATTRIBUTES . " pa
      left join " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov on (pa.options_values_id = pov.products_options_values_id)
      left join " . TABLE_PRODUCTS . " p on (pa.products_id = p.products_id)

      left join " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " pas on
      (p.products_id = pas.products_id and pa.products_attributes_id = pas.stock_attributes )

      where pa.products_id = '" . (int)$_GET['products_id'] . "'
      and   pa.options_id  = '" . (int)$products_options_names->fields['products_options_id'] . "'
      and   pov.language_id = '" . (int)$_SESSION['languages_id'] . "' " .
      $order_by;
     */
    $sql = "select distinct	pov.products_options_values_id,
			                    pov.products_options_values_name,
			                    pa.*, p.products_quantity, 
                				" . ($products_options_names_count <= 1 ? " pas.stock_id as pasid, pas.quantity as pasqty, pas.sort,  pas.customid, pas.title, pas.product_attribute_combo, pas.stock_attributes, " : "") . " pas.products_id 

		              from      " . TABLE_PRODUCTS_ATTRIBUTES . " pa
		              left join " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov on (pa.options_values_id = pov.products_options_values_id)
		              left join " . TABLE_PRODUCTS . " p on (pa.products_id = p.products_id)
                
		              left join " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " pas on 
		              (p.products_id = pas.products_id and FIND_IN_SET(pa.products_attributes_id, pas.stock_attributes) > 0 )
		              where pa.products_id = '" . (int) $_GET['products_id'] . "'
		              and   pa.options_id  = '" . (int) $products_options_names->fields['products_options_id'] . "'
		              and   pov.language_id = '" . (int) $_SESSION['languages_id'] . "' " .
                $order_by;

    $products_options = $db->Execute($sql);

    $products_options_value_id = '';
    $products_options_details = '';
    $products_options_details_noname = '';
    $tmp_radio = '';
    $tmp_checkbox = '';
    $tmp_html = '';
    $selected_attribute = false;

    $tmp_attributes_image = '';
    $tmp_attributes_image_row = 0;
    $show_attributes_qty_prices_icon = 'false';

    //process each attribute in the list
    while (!$products_options->EOF) {
      // reset
      $products_options_display_price = '';
      $new_attributes_price = '';
      $price_onetime = '';

      // START "Stock by Attributes"  
      //used to find if an attribute is read-only
      $sqlRO = "select pa.attributes_display_only
              			from " . TABLE_PRODUCTS_OPTIONS . " po
              			left join " . TABLE_PRODUCTS_ATTRIBUTES . " pa on (pa.options_id = po.products_options_id)
              			where pa.products_id='" . (int) $_GET['products_id'] . "'
               			and pa.products_attributes_id = '" . (int) $products_options->fields["products_attributes_id"] . "' ";
      $products_options_READONLY = $db->Execute($sqlRO);

      //echo 'ID: ' . $products_options->fields["products_attributes_id"] . ' Stock ID: ' . $products_options->fields['pasid'] . ' QTY: ' . $products_options->fields['pasqty'] . ' Custom ID: ' . $products_options->fields['customid'] . '<br />';//debug line
      //add out of stock text based on qty
      if ($products_options->fields['pasqty'] < 1 && STOCK_CHECK == 'true' && $products_options->fields['pasid'] > 0) {
        //test, only applicable to products with-out the read-only attribute set
        if ($products_options_READONLY->fields['attributes_display_only'] < 1) {
          $products_options->fields['products_options_values_name'] = $products_options->fields['products_options_values_name'] . PWA_OUT_OF_STOCK;
        }
      }

      //Add qty to atributes based on STOCK_SHOW_ATTRIB_LEVEL_STOCK setting
      //Only add to Radio, Checkbox, and selection lists 
      //PRODUCTS_OPTIONS_TYPE_RADIO PRODUCTS_OPTIONS_TYPE_CHECKBOX  
      //Exclude the following:
      //PRODUCTS_OPTIONS_TYPE_TEXT PRODUCTS_OPTIONS_TYPE_FILE PRODUCTS_OPTIONS_TYPE_READONLY
      //PRODUCTS_OPTIONS_TYPE_SELECT_SBA
      $PWA_STOCK_QTY = null; //initialize variable	
      if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_TEXT) {
        if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_FILE) {
          if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_READONLY) {
            if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_SELECT_SBA) {

              if (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true' && $products_options->fields['pasqty'] > 0) {
                //test, only applicable to products with-out the read-only attribute set
                if ($products_options_READONLY->fields['attributes_display_only'] < 1) {
                  $PWA_STOCK_QTY = PWA_STOCK_QTY . $products_options->fields['pasqty'] . ' ';
                  //show custom ID if flag set to true
                  if (STOCK_SBA_DISPLAY_CUSTOMID == 'true' AND ! empty($products_options->fields['customid'])) {
                    $PWA_STOCK_QTY .= ' (' . $products_options->fields['customid'] . ') ';
                  }
                }
              } elseif (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true' && $products_options->fields['pasqty'] < 1 && $products_options->fields['pasid'] < 1) {
                //test, only applicable to products with-out the read-only attribute set
                if ($products_options_READONLY->fields['attributes_display_only'] < 1) {
                  //use the qty from the product, unless it is 0, then set to out of stock.
                  if ($products_options_names_count <= 1) {
                    if ($products_options->fields['products_quantity'] > 0) {
                      $PWA_STOCK_QTY = PWA_STOCK_QTY . $products_options->fields['products_quantity'] . ' ';
                    } else {
                      $products_options->fields['products_options_values_name'] = $products_options->fields['products_options_values_name'] . PWA_OUT_OF_STOCK;
                    }
                  }

                  //show custom ID if flag set to true
                  if (STOCK_SBA_DISPLAY_CUSTOMID == 'true' AND ! empty($products_options->fields['customid'])) {
                    $PWA_STOCK_QTY .= ' (' . $products_options->fields['customid'] . ') ';
                  }
                }
              } elseif (STOCK_SBA_DISPLAY_CUSTOMID == 'true' AND ! empty($products_options->fields['customid'])) {
                //show custom ID if flag set to true
                //test, only applicable to products with-out the read-only attribute set
                if ($products_options_READONLY->fields['attributes_display_only'] < 1) {
                  $PWA_STOCK_QTY .= ' (' . $products_options->fields['customid'] . ') ';
                }
              }
            }
          }
        }
      }

      //create image array for use in select list to rotate visable image on select.
      if (!empty($products_options->fields['attributes_image'])) {
        $options_menu_images[] = array('id' => $products_options->fields['products_options_values_id'],
          'src' => 'images/' . $products_options->fields['attributes_image']);
      } else {
        $options_menu_images[] = array('id' => $products_options->fields['products_options_values_id']);
      }
      // END "Stock by Attributes"

      $products_options_array[] = array('id' => $products_options->fields['products_options_values_id'],
        'text' => $products_options->fields['products_options_values_name']);

      if (((CUSTOMERS_APPROVAL == '2' and $_SESSION['customer_id'] == '') or ( STORE_STATUS == '1')) or ( (CUSTOMERS_APPROVAL_AUTHORIZATION == '1' or CUSTOMERS_APPROVAL_AUTHORIZATION == '2') and $_SESSION['customers_authorization'] == '') or ( CUSTOMERS_APPROVAL == '2' and $_SESSION['customers_authorization'] == '2') or ( CUSTOMERS_APPROVAL_AUTHORIZATION == '2' and $_SESSION['customers_authorization'] != 0)) {

        $new_attributes_price = '';
        $new_options_values_price = 0;
        $products_options_display_price = '';
        $price_onetime = '';
      } else {
        // collect price information if it exists
        if ($products_options->fields['attributes_discounted'] == 1) {
          // apply product discount to attributes if discount is on
          $new_attributes_price = zen_get_attributes_price_final($products_options->fields["products_attributes_id"], 1, '', 'false');
          $new_attributes_price = zen_get_discount_calc((int) $_GET['products_id'], true, $new_attributes_price);
        } else {
          // discount is off do not apply
          $new_attributes_price = $products_options->fields['options_values_price'];
        }

        // reverse negative values for display
        if ($new_attributes_price < 0) {
          $new_attributes_price = -$new_attributes_price;
        }

        if ($products_options->fields['attributes_price_onetime'] != 0 or $products_options->fields['attributes_price_factor_onetime'] != 0) {
          $show_onetime_charges_description = 'true';
          $new_onetime_charges = zen_get_attributes_price_final_onetime($products_options->fields["products_attributes_id"], 1, '');
          $price_onetime = TEXT_ONETIME_CHARGE_SYMBOL . $currencies->display_price($new_onetime_charges, zen_get_tax_rate($product_info->fields['products_tax_class_id']));
        } else {
          $price_onetime = '';
        }

        if ($products_options->fields['attributes_qty_prices'] != '' or $products_options->fields['attributes_qty_prices_onetime'] != '') {
          $show_attributes_qty_prices_description = 'true';
          $show_attributes_qty_prices_icon = 'true';
        }

        if ($products_options->fields['options_values_price'] != '0' and ( $products_options->fields['product_attribute_is_free'] != '1' and $product_info->fields['product_is_free'] != '1')) {
          // show sale maker discount if a percentage
          // START "Stock by Attributes"
          if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_RADIO || $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_CHECKBOX) {
            //use this if a PRODUCTS_OPTIONS_TYPE_RADIO or PRODUCTS_OPTIONS_TYPE_CHECKBOX
            //class="productSpecialPrice" can be used in a CSS file to control the text properties, not compatable with selection lists
            $products_options_display_price = ATTRIBUTES_PRICE_DELIMITER_PREFIX . '<span class="productSpecialPrice">' . $products_options->fields['price_prefix'] . $currencies->display_price($new_attributes_price, zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . '</span>' . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
          } else {
            //need to remove the <span> tag for selection lists and text boxes
            $products_options_display_price = ATTRIBUTES_PRICE_DELIMITER_PREFIX . $products_options->fields['price_prefix'] . $currencies->display_price($new_attributes_price, zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
          }
          // END "Stock by Attributes"
        } else {
          // if product_is_free and product_attribute_is_free
          if ($products_options->fields['product_attribute_is_free'] == '1' and $product_info->fields['product_is_free'] == '1') {
            $products_options_display_price = TEXT_ATTRIBUTES_PRICE_WAS . $products_options->fields['price_prefix'] .
                    $currencies->display_price($new_attributes_price, zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . TEXT_ATTRIBUTE_IS_FREE;
          } else {
            // normal price
            if ($new_attributes_price == 0) {
              $products_options_display_price = '';
            } else {
              $products_options_display_price = ATTRIBUTES_PRICE_DELIMITER_PREFIX . $products_options->fields['price_prefix'] .
                      $currencies->display_price($new_attributes_price, zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
            }
          }
        }

        $products_options_display_price .= $price_onetime;
      } // approve
      // START "Stock by Attributes" added original price for display, and some formatting
      $originalpricedisplaytext = null;
      if (STOCK_SHOW_ORIGINAL_PRICE_STRUCK == 'true' && zen_get_attributes_price_final($products_options->fields["products_attributes_id"], 1, '', 'false') != $new_attributes_price) {
        //Original price struck through
        if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_RADIO || $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_CHECKBOX) {
          //use this if a PRODUCTS_OPTIONS_TYPE_RADIO or PRODUCTS_OPTIONS_TYPE_CHECKBOX
          //class="normalprice" can be used in a CSS file to control the text properties, not compatable with selection lists
          $originalpricedisplaytext = ATTRIBUTES_PRICE_DELIMITER_PREFIX . '<span class="normalprice">' . $products_options->fields['price_prefix'] . $currencies->display_price(zen_get_attributes_price_final($products_options->fields["products_attributes_id"], 1, '', 'false'), zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . '</span>' . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
        } else {
          //need to remove the <span> tag for selection lists and text boxes
          $originalpricedisplaytext = ATTRIBUTES_PRICE_DELIMITER_PREFIX . $products_options->fields['price_prefix'] . $currencies->display_price(zen_get_attributes_price_final($products_options->fields["products_attributes_id"], 1, '', 'false'), zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . ATTRIBUTES_PRICE_DELIMITER_SUFFIX;
        }
      }
      // END "Stock by Attributes"
      // START "Stock by Attributes"
      $products_options_display_price .= $originalpricedisplaytext . $PWA_STOCK_QTY;
      // END "Stock by Attributes"

      $products_options_array[sizeof($products_options_array) - 1]['text'] .= $products_options_display_price;

      // collect weight information if it exists
      if (($flag_show_weight_attrib_for_this_prod_type == '1' and $products_options->fields['products_attributes_weight'] != '0')) {
        $products_options_display_weight = ATTRIBUTES_WEIGHT_DELIMITER_PREFIX . $products_options->fields['products_attributes_weight_prefix'] . round($products_options->fields['products_attributes_weight'], 2) . TEXT_PRODUCT_WEIGHT_UNIT . ATTRIBUTES_WEIGHT_DELIMITER_SUFFIX;
        $products_options_array[sizeof($products_options_array) - 1]['text'] .= $products_options_display_weight;
      } else {
        // reset
        $products_options_display_weight = '';
      }

      // prepare product options details
      $prod_id = $_GET['products_id'];
      //die($prod_id);
      if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE or $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT or $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_CHECKBOX or $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_RADIO or $products_options->RecordCount() == 1 or $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_READONLY) {
        $products_options_value_id = $products_options->fields['products_options_values_id'];
        if ($products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_TEXT and $products_options_names->fields['products_options_type'] != PRODUCTS_OPTIONS_TYPE_FILE) {
          $products_options_details = $products_options->fields['products_options_values_name'];
        } else {
          // don't show option value name on TEXT or filename
          $products_options_details = '';
        }
        if ($products_options_names->fields['products_options_images_style'] >= 3) {
          $products_options_details .= $products_options_display_price . ($products_options->fields['products_attributes_weight'] != 0 ? '<br />' . $products_options_display_weight : '');
          $products_options_details_noname = $products_options_display_price . ($products_options->fields['products_attributes_weight'] != 0 ? '<br />' . $products_options_display_weight : '');
        } else {
          $products_options_details .= $products_options_display_price . ($products_options->fields['products_attributes_weight'] != 0 ? '  ' . $products_options_display_weight : '');
          $products_options_details_noname = $products_options_display_price . ($products_options->fields['products_attributes_weight'] != 0 ? '  ' . $products_options_display_weight : '');
        }
      }

      // radio buttons
      if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_RADIO) {
        if ($_SESSION['cart']->in_cart($prod_id)) {
          if ($_SESSION['cart']->contents[$prod_id]['attributes'][$products_options_names->fields['products_options_id']] == $products_options->fields['products_options_values_id']) {
            $selected_attribute = $_SESSION['cart']->contents[$prod_id]['attributes'][$products_options_names->fields['products_options_id']];
          } else {
            $selected_attribute = false;
          }
        } else {
          //              $selected_attribute = ($products_options->fields['attributes_default']=='1' ? true : false);
          // if an error, set to customer setting
          if ($_POST['id'] != '') {
            $selected_attribute = false;
            reset($_POST['id']);
            foreach ($_POST['id'] as $key => $value) {
              if (($key == $products_options_names->fields['products_options_id'] and $value == $products_options->fields['products_options_values_id'])) {
                // zen_get_products_name($_POST['products_id']) .
                $selected_attribute = true;
                break;
              }
            }
          } else {
            // select default but do NOT auto select single radio buttons
            // $selected_attribute = ($products_options->fields['attributes_default']=='1' ? true : false);
            // select default radio button or auto select single radio buttons
            $selected_attribute = ($products_options->fields['attributes_default'] == '1' ? true : ($products_options->RecordCount() == 1 ? true : false));
          }
        }

        // START "Stock by Attributes"
        //move default selected attribute if attribute is out of stock and check out is not allowed
        if ($moveSelectedAttribute == true && (STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['pasqty'] > 0)) {
          $selected_attribute = true;
          $moveSelectedAttribute = false;
        }
        $disablebackorder = null;
        //disable radio and disable default selected
        if ((STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['pasqty'] == 0 && !empty($products_options->fields['pasid']) ) 
		|| ( STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['products_quantity'] <= 0 && empty($products_options->fields['pasid']) )
        ) {//|| $products_options_READONLY->fields['attributes_display_only'] == 1
          if ($selected_attribute == true) {
            $selected_attribute = false;
            $moveSelectedAttribute = true;
          }
          $disablebackorder = 'disabled="disabled"';
        }
        // END "Stock by Attributes"
        // START "Stock by Attributes"
        switch ($products_options_names->fields['products_options_images_style']) {

          case '0':
            $tmp_radio .= zen_draw_radio_field('id[' . $products_options_names->fields['products_options_id'] . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<label class="attribsRadioButton zero" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options_details . '</label><br />' . "\n";
            break;
          case '1':
            $tmp_radio .= zen_draw_radio_field('id[' . $products_options_names->fields['products_options_id'] . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<label class="attribsRadioButton one" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . ($products_options->fields['attributes_image'] != '' ? zen_image(DIR_WS_IMAGES . $products_options->fields['attributes_image'], '', '', '') . '  ' : '') . $products_options_details . '</label><br />' . "\n";
            break;
          case '2':
            $tmp_radio .= zen_draw_radio_field('id[' . $products_options_names->fields['products_options_id'] . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<label class="attribsRadioButton two" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options_details . ($products_options->fields['attributes_image'] != '' ? '<br />' . zen_image(DIR_WS_IMAGES . $products_options->fields['attributes_image'], '', '', '') : '') . '</label><br />' . "\n";
            break;
          case '3':
            $tmp_attributes_image_row++;
            //                  if ($tmp_attributes_image_row > PRODUCTS_IMAGES_ATTRIBUTES_PER_ROW) {
            if ($tmp_attributes_image_row > $products_options_names->fields['products_options_images_per_row']) {
              $tmp_attributes_image .= '<br class="clearBoth" />' . "\n";
              $tmp_attributes_image_row = 1;
            }

            if ($products_options->fields['attributes_image'] != '') {
              $tmp_attributes_image .= '<div class="attribImg">' . zen_draw_radio_field('id[' . $products_options_names->fields['products_options_id'] . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<label class="attribsRadioButton three" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . zen_image(DIR_WS_IMAGES . $products_options->fields['attributes_image']) . (PRODUCT_IMAGES_ATTRIBUTES_NAMES == '1' ? '<br />' . $products_options->fields['products_options_values_name'] : '') . $products_options_details_noname . '</label></div>' . "\n";
            } else {
              $tmp_attributes_image .= '<div class="attribImg">' . zen_draw_radio_field('id[' . $products_options_names->fields['products_options_id'] . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<br />' . '<label class="attribsRadioButton threeA" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options->fields['products_options_values_name'] . $products_options_details_noname . '</label></div>' . "\n";
            }
            break;

          case '4':
            $tmp_attributes_image_row++;

            //                  if ($tmp_attributes_image_row > PRODUCTS_IMAGES_ATTRIBUTES_PER_ROW) {
            if ($tmp_attributes_image_row > $products_options_names->fields['products_options_images_per_row']) {
              $tmp_attributes_image .= '<br class="clearBoth" />' . "\n";
              $tmp_attributes_image_row = 1;
            }

            if ($products_options->fields['attributes_image'] != '') {
              $tmp_attributes_image .= '<div class="attribImg">' . '<label class="attribsRadioButton four" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . zen_image(DIR_WS_IMAGES . $products_options->fields['attributes_image']) . (PRODUCT_IMAGES_ATTRIBUTES_NAMES == '1' ? '<br />' . $products_options->fields['products_options_values_name'] : '') . ($products_options_details_noname != '' ? '<br />' . $products_options_details_noname : '') . '</label><br />' . zen_draw_radio_field('id[' . $products_options_names->fields['products_options_id'] . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '</div>' . "\n";
            } else {
              $tmp_attributes_image .= '<div class="attribImg">' . '<label class="attribsRadioButton fourA" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options->fields['products_options_values_name'] . ($products_options_details_noname != '' ? '<br />' . $products_options_details_noname : '') . '</label><br />' . zen_draw_radio_field('id[' . $products_options_names->fields['products_options_id'] . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '</div>' . "\n";
            }
            break;

          case '5':
            $tmp_attributes_image_row++;

            //                  if ($tmp_attributes_image_row > PRODUCTS_IMAGES_ATTRIBUTES_PER_ROW) {
            if ($tmp_attributes_image_row > $products_options_names->fields['products_options_images_per_row']) {
              $tmp_attributes_image .= '<br class="clearBoth" />' . "\n";
              $tmp_attributes_image_row = 1;
            }

            if ($products_options->fields['attributes_image'] != '') {
              $tmp_attributes_image .= '<div class="attribImg">' . zen_draw_radio_field('id[' . $products_options_names->fields['products_options_id'] . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<br />' . '<label class="attribsRadioButton five" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . zen_image(DIR_WS_IMAGES . $products_options->fields['attributes_image']) . (PRODUCT_IMAGES_ATTRIBUTES_NAMES == '1' ? '<br />' . $products_options->fields['products_options_values_name'] : '') . ($products_options_details_noname != '' ? '<br />' . $products_options_details_noname : '') . '</label></div>';
            } else {
              $tmp_attributes_image .= '<div class="attribImg">' . zen_draw_radio_field('id[' . $products_options_names->fields['products_options_id'] . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<br />' . '<label class="attribsRadioButton fiveA" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options->fields['products_options_values_name'] . ($products_options_details_noname != '' ? '<br />' . $products_options_details_noname : '') . '</label></div>';
            }
            break;
        }
      }

      // checkboxes
      if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_CHECKBOX) {
        $string = $products_options_names->fields['products_options_id'] . '_chk' . $products_options->fields['products_options_values_id'];
        if ($_SESSION['cart']->in_cart($prod_id)) {
          if ($_SESSION['cart']->contents[$prod_id]['attributes'][$string] == $products_options->fields['products_options_values_id']) {
            $selected_attribute = true;
          } else {
            $selected_attribute = false;
          }
        } else {
          //              $selected_attribute = ($products_options->fields['attributes_default']=='1' ? true : false);
          // if an error, set to customer setting
          if ($_POST['id'] != '') {
            $selected_attribute = false;
            reset($_POST['id']);
            foreach ($_POST['id'] as $key => $value) {
              if (is_array($value)) {
                foreach ($value as $kkey => $vvalue) {
                  if (($key == $products_options_names->fields['products_options_id'] and $vvalue == $products_options->fields['products_options_values_id'])) {
                    $selected_attribute = true;
                    break;
                  }
                }
              } else {
                if (($key == $products_options_names->fields['products_options_id'] and $value == $products_options->fields['products_options_values_id'])) {
                  // zen_get_products_name($_POST['products_id']) .
                  $selected_attribute = true;
                  break;
                }
              }
            }
          } else {
            $selected_attribute = ($products_options->fields['attributes_default'] == '1' ? true : false);
          }
        }

        // START "Stock by Attributes"
        //move default selected attribute if attribute is out of stock and check out is not allowed
        if ($moveSelectedAttribute == true && (STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['pasqty'] > 0)) {
          $selected_attribute = true;
          $moveSelectedAttribute = false;
        }
        $disablebackorder = null;
        //disable radio and disable default selected
        if ((STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['pasqty'] == 0 && !empty($products_options->fields['pasid']) ) 
		|| ( STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['products_quantity'] <= 0 && empty($products_options->fields['pasid']) )
        ) {//|| $products_options_READONLY->fields['attributes_display_only'] == 1
          if ($selected_attribute == true) {
            $selected_attribute = false;
            $moveSelectedAttribute = true;
          }
          $disablebackorder = 'disabled="disabled"';
        }
        // END "Stock by Attributes"

        /*
          $tmp_checkbox .= zen_draw_checkbox_field('id[' . $products_options_names->fields['products_options_id'] . ']['.$products_options_value_id.']', $products_options_value_id, $selected_attribute, 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<label class="" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options_details .'</label><br />';
         */
        switch ($products_options_names->fields['products_options_images_style']) {
          case '0':
            $tmp_checkbox .= zen_draw_checkbox_field('id[' . $products_options_names->fields['products_options_id'] . '][' . $products_options_value_id . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<label class="attribsCheckbox" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options_details . '</label><br />' . "\n";
            break;
          case '1':
            $tmp_checkbox .= zen_draw_checkbox_field('id[' . $products_options_names->fields['products_options_id'] . '][' . $products_options_value_id . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<label class="attribsCheckbox" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . ($products_options->fields['attributes_image'] != '' ? zen_image(DIR_WS_IMAGES . $products_options->fields['attributes_image'], '', '', '') . '  ' : '') . $products_options_details . '</label><br />' . "\n";
            break;
          case '2':
            $tmp_checkbox .= zen_draw_checkbox_field('id[' . $products_options_names->fields['products_options_id'] . '][' . $products_options_value_id . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<label class="attribsCheckbox" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options_details . ($products_options->fields['attributes_image'] != '' ? '<br />' . zen_image(DIR_WS_IMAGES . $products_options->fields['attributes_image'], '', '', '') : '') . '</label><br />' . "\n";
            break;

          case '3':
            $tmp_attributes_image_row++;

            //                  if ($tmp_attributes_image_row > PRODUCTS_IMAGES_ATTRIBUTES_PER_ROW) {
            if ($tmp_attributes_image_row > $products_options_names->fields['products_options_images_per_row']) {
              $tmp_attributes_image .= '<br class="clearBoth" />' . "\n";
              $tmp_attributes_image_row = 1;
            }

            if ($products_options->fields['attributes_image'] != '') {
              $tmp_attributes_image .= '<div class="attribImg">' . zen_draw_checkbox_field('id[' . $products_options_names->fields['products_options_id'] . '][' . $products_options_value_id . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<label class="attribsCheckbox" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . zen_image(DIR_WS_IMAGES . $products_options->fields['attributes_image']) . (PRODUCT_IMAGES_ATTRIBUTES_NAMES == '1' ? '<br />' . $products_options->fields['products_options_values_name'] : '') . $products_options_details_noname . '</label></div>' . "\n";
            } else {
              $tmp_attributes_image .= '<div class="attribImg">' . zen_draw_checkbox_field('id[' . $products_options_names->fields['products_options_id'] . '][' . $products_options_value_id . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<br />' . '<label class="attribsCheckbox" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options->fields['products_options_values_name'] . $products_options_details_noname . '</label></div>' . "\n";
            }
            break;

          case '4':
            $tmp_attributes_image_row++;

            //                  if ($tmp_attributes_image_row > PRODUCTS_IMAGES_ATTRIBUTES_PER_ROW) {
            if ($tmp_attributes_image_row > $products_options_names->fields['products_options_images_per_row']) {
              $tmp_attributes_image .= '<br class="clearBoth" />' . "\n";
              $tmp_attributes_image_row = 1;
            }

            if ($products_options->fields['attributes_image'] != '') {
              $tmp_attributes_image .= '<div class="attribImg">' . '<label class="attribsCheckbox" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . zen_image(DIR_WS_IMAGES . $products_options->fields['attributes_image']) . (PRODUCT_IMAGES_ATTRIBUTES_NAMES == '1' ? '<br />' . $products_options->fields['products_options_values_name'] : '') . ($products_options_details_noname != '' ? '<br />' . $products_options_details_noname : '') . '</label><br />' . zen_draw_checkbox_field('id[' . $products_options_names->fields['products_options_id'] . '][' . $products_options_value_id . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '</div>' . "\n";
            } else {
              $tmp_attributes_image .= '<div class="attribImg">' . '<label class="attribsCheckbox" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options->fields['products_options_values_name'] . ($products_options_details_noname != '' ? '<br />' . $products_options_details_noname : '') . '</label><br />' . zen_draw_checkbox_field('id[' . $products_options_names->fields['products_options_id'] . '][' . $products_options_value_id . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '</div>' . "\n";
            }
            break;

          case '5':
            $tmp_attributes_image_row++;

            //                  if ($tmp_attributes_image_row > PRODUCTS_IMAGES_ATTRIBUTES_PER_ROW) {
            if ($tmp_attributes_image_row > $products_options_names->fields['products_options_images_per_row']) {
              $tmp_attributes_image .= '<br class="clearBoth" />' . "\n";
              $tmp_attributes_image_row = 1;
            }

            if ($products_options->fields['attributes_image'] != '') {
              $tmp_attributes_image .= '<div class="attribImg">' . zen_draw_checkbox_field('id[' . $products_options_names->fields['products_options_id'] . '][' . $products_options_value_id . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<br />' . zen_image(DIR_WS_IMAGES . $products_options->fields['attributes_image']) . '<label class="attribsCheckbox" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . (PRODUCT_IMAGES_ATTRIBUTES_NAMES == '1' ? '<br />' . $products_options->fields['products_options_values_name'] : '') . ($products_options_details_noname != '' ? '<br />' . $products_options_details_noname : '') . '</label></div>' . "\n";
            } else {
              $tmp_attributes_image .= '<div class="attribImg">' . zen_draw_checkbox_field('id[' . $products_options_names->fields['products_options_id'] . '][' . $products_options_value_id . ']', $products_options_value_id, $selected_attribute, $disablebackorder . 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<br />' . '<label class="attribsCheckbox" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options->fields['products_options_values_name'] . ($products_options_details_noname != '' ? '<br />' . $products_options_details_noname : '') . '</label></div>' . "\n";
            }
            break;
        }
      }
      // END "Stock by Attributes"
      // text
      if (($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT)) {
        //CLR 030714 Add logic for text option
        //            $products_attribs_query = zen_db_query("select distinct pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id='" . (int)$_GET['products_id'] . "' and pa.options_id = '" . $products_options_name['products_options_id'] . "'");
        //            $products_attribs_array = zen_db_fetch_array($products_attribs_query);
        if ($_POST['id']) {
          reset($_POST['id']);
          foreach ($_POST['id'] as $key => $value) {
            //echo preg_replace('/txt_/', '', $key) . '#';
            //print_r($_POST['id']);
            //echo $products_options_names->fields['products_options_id'].'|';
            //echo $value.'|';
            //echo $products_options->fields['products_options_values_id'].'#';
            if ((preg_replace('/txt_/', '', $key) == $products_options_names->fields['products_options_id'])) {
              // use text area or input box based on setting of products_options_rows in the products_options table
              if ($products_options_names->fields['products_options_rows'] > 1) {
                $tmp_html = '  <input disabled="disabled" type="text" name="remaining' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . '" size="3" maxlength="3" value="' . $products_options_names->fields['products_options_length'] . '" /> ' . TEXT_MAXIMUM_CHARACTERS_ALLOWED . '<br />';
                $tmp_html .= '<textarea class="attribsTextarea" name="id[' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ']" rows="' . $products_options_names->fields['products_options_rows'] . '" cols="' . $products_options_names->fields['products_options_size'] . '" onKeyDown="characterCount(this.form[\'' . 'id[' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ']\'],this.form.remaining' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ',' . $products_options_names->fields['products_options_length'] . ');" onKeyUp="characterCount(this.form[\'' . 'id[' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ']\'],this.form.remaining' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ',' . $products_options_names->fields['products_options_length'] . ');" id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '" >' . stripslashes($value) . '</textarea>' . "\n";
              } else {
                $tmp_html = '<input type="text" name="id[' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ']" size="' . $products_options_names->fields['products_options_size'] . '" maxlength="' . $products_options_names->fields['products_options_length'] . '" value="' . stripslashes($value) . '" id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '" />  ';
              }
              $tmp_html .= $products_options_details;
              break;
            }
          }
        } else {
          $tmp_value = $_SESSION['cart']->contents[$_GET['products_id']]['attributes_values'][$products_options_names->fields['products_options_id']];
          // use text area or input box based on setting of products_options_rows in the products_options table
          if ($products_options_names->fields['products_options_rows'] > 1) {
            $tmp_html = '  <input disabled="disabled" type="text" name="remaining' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . '" size="3" maxlength="3" value="' . $products_options_names->fields['products_options_length'] . '" /> ' . TEXT_MAXIMUM_CHARACTERS_ALLOWED . '<br />';
            $tmp_html .= '<textarea class="attribsTextarea" name="id[' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ']" rows="' . $products_options_names->fields['products_options_rows'] . '" cols="' . $products_options_names->fields['products_options_size'] . '" onkeydown="characterCount(this.form[\'' . 'id[' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ']\'],this.form.remaining' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ',' . $products_options_names->fields['products_options_length'] . ');" onkeyup="characterCount(this.form[\'' . 'id[' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ']\'],this.form.remaining' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ',' . $products_options_names->fields['products_options_length'] . ');" id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '" >' . stripslashes($tmp_value) . '</textarea>' . "\n";
            //                $tmp_html .= '  <input type="reset">';
          } else {
            $tmp_html = '<input type="text" name="id[' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ']" size="' . $products_options_names->fields['products_options_size'] . '" maxlength="' . $products_options_names->fields['products_options_length'] . '" value="' . htmlspecialchars($tmp_value, ENT_COMPAT, CHARSET, TRUE) . '" id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '" />  ';
          }
          $tmp_html .= $products_options_details;
          $tmp_word_cnt_string = '';
          // calculate word charges
          $tmp_word_cnt = 0;
          $tmp_word_cnt_string = $_SESSION['cart']->contents[$_GET['products_id']]['attributes_values'][$products_options_names->fields['products_options_id']];
          $tmp_word_cnt = zen_get_word_count($tmp_word_cnt_string, $products_options->fields['attributes_price_words_free']);
          $tmp_word_price = zen_get_word_count_price($tmp_word_cnt_string, $products_options->fields['attributes_price_words_free'], $products_options->fields['attributes_price_words']);

          if ($products_options->fields['attributes_price_words'] != 0) {
            $tmp_html .= TEXT_PER_WORD . $currencies->display_price($products_options->fields['attributes_price_words'], zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . ($products_options->fields['attributes_price_words_free'] != 0 ? TEXT_WORDS_FREE . $products_options->fields['attributes_price_words_free'] : '');
          }
          if ($tmp_word_cnt != 0 and $tmp_word_price != 0) {
            $tmp_word_price = $currencies->display_price($tmp_word_price, zen_get_tax_rate($product_info->fields['products_tax_class_id']));
            $tmp_html = $tmp_html . '<br />' . TEXT_CHARGES_WORD . ' ' . $tmp_word_cnt . ' = ' . $tmp_word_price;
          }
          // calculate letter charges
          $tmp_letters_cnt = 0;
          $tmp_letters_cnt_string = $_SESSION['cart']->contents[$_GET['products_id']]['attributes_values'][$products_options_names->fields['products_options_id']];
          $tmp_letters_cnt = zen_get_letters_count($tmp_letters_cnt_string, $products_options->fields['attributes_price_letters_free']);
          $tmp_letters_price = zen_get_letters_count_price($tmp_letters_cnt_string, $products_options->fields['attributes_price_letters_free'], $products_options->fields['attributes_price_letters']);

          if ($products_options->fields['attributes_price_letters'] != 0) {
            $tmp_html .= TEXT_PER_LETTER . $currencies->display_price($products_options->fields['attributes_price_letters'], zen_get_tax_rate($product_info->fields['products_tax_class_id'])) . ($products_options->fields['attributes_price_letters_free'] != 0 ? TEXT_LETTERS_FREE . $products_options->fields['attributes_price_letters_free'] : '');
          }
          if ($tmp_letters_cnt != 0 and $tmp_letters_price != 0) {
            $tmp_letters_price = $currencies->display_price($tmp_letters_price, zen_get_tax_rate($product_info->fields['products_tax_class_id']));
            $tmp_html = $tmp_html . '<br />' . TEXT_CHARGES_LETTERS . ' ' . $tmp_letters_cnt . ' = ' . $tmp_letters_price;
          }
          $tmp_html .= "\n";
        }
      }

      // file uploads
      // iii 030813 added: support for file fields
      if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE) {
        $number_of_uploads++;
        if (zen_run_normal() == true and zen_check_show_prices() == true) {
          // $cart->contents[$_GET['products_id']]['attributes_values'][$products_options_name['products_options_id']]
          $tmp_html = '<input type="file" name="id[' . TEXT_PREFIX . $products_options_names->fields['products_options_id'] . ']"  id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '" /><br />' . $_SESSION['cart']->contents[$prod_id]['attributes_values'][$products_options_names->fields['products_options_id']] . "\n" .
                  zen_draw_hidden_field(UPLOAD_PREFIX . $number_of_uploads, $products_options_names->fields['products_options_id']) . "\n" .
                  zen_draw_hidden_field(TEXT_PREFIX . UPLOAD_PREFIX . $number_of_uploads, $_SESSION['cart']->contents[$prod_id]['attributes_values'][$products_options_names->fields['products_options_id']]);
        } else {
          $tmp_html = '';
        }
        $tmp_html .= $products_options_details;
      }


      // collect attribute image if it exists and to be drawn in table below
      if ($products_options_names->fields['products_options_images_style'] == '0' or ( $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE or $products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT or $products_options_names->fields['products_options_type'] == '0')) {
        if ($products_options->fields['attributes_image'] != '') {
          $tmp_attributes_image_row++;

          //              if ($tmp_attributes_image_row > PRODUCTS_IMAGES_ATTRIBUTES_PER_ROW) {
          if ($tmp_attributes_image_row > $products_options_names->fields['products_options_images_per_row']) {
            $tmp_attributes_image .= '<br class="clearBoth" />' . "\n";
            $tmp_attributes_image_row = 1;
          }

          $tmp_attributes_image .= '<div class="attribImg">' . zen_image(DIR_WS_IMAGES . $products_options->fields['attributes_image']) . (PRODUCT_IMAGES_ATTRIBUTES_NAMES == '1' ? '<br />' . $products_options->fields['products_options_values_name'] : '') . '</div>' . "\n";
        }
      }

      // Read Only - just for display purposes
      if ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_READONLY) {
        //            $tmp_html .= '<input type="hidden" name ="id[' . $products_options_names->fields['products_options_id'] . ']"' . '" value="' . stripslashes($products_options->fields['products_options_values_name']) . ' SELECTED' . '" />  ' . $products_options->fields['products_options_values_name'];
        $tmp_html .= $products_options_details . '<br />';
      } else {
        $zv_display_select_option ++;
      }

      // default
      // find default attribute if set to for default dropdown
      if ($products_options->fields['attributes_default'] == '1') {
        $selected_attribute = $products_options->fields['products_options_values_id'];
      }

      //Next Item
      $products_options->MoveNext();
    }

    //echo 'TEST I AM ' . $products_options_names->fields['products_options_name'] . ' Type - ' . $products_options_names->fields['products_options_type'] . '<br />';
    // Option Name Type Display
    switch (true) {

      // text
      case ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_TEXT):
        if ($show_attributes_qty_prices_icon == 'true') {
          $options_name[] = '<label class="attribsInput" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . ATTRIBUTES_QTY_PRICE_SYMBOL . $products_options_names->fields['products_options_name'] . '</label>';
        } else {
          $options_name[] = '<label class="attribsInput" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options_names->fields['products_options_name'] . '</label>';
        }
        $options_menu[] = $tmp_html . "\n";
        $options_comment[] = $products_options_names->fields['products_options_comment'];
        $options_comment_position[] = ($products_options_names->fields['products_options_comment_position'] == '1' ? '1' : '0');
        break;

      // checkbox
      case ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_CHECKBOX):
        if ($show_attributes_qty_prices_icon == 'true') {
          $options_name[] = ATTRIBUTES_QTY_PRICE_SYMBOL . $products_options_names->fields['products_options_name'];
        } else {
          $options_name[] = $products_options_names->fields['products_options_name'];
        }
        $options_menu[] = $tmp_checkbox . "\n";
        $options_comment[] = $products_options_names->fields['products_options_comment'];
        $options_comment_position[] = ($products_options_names->fields['products_options_comment_position'] == '1' ? '1' : '0');
        break;

      // radio buttons
      case ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_RADIO):
        if ($show_attributes_qty_prices_icon == 'true') {
          $options_name[] = ATTRIBUTES_QTY_PRICE_SYMBOL . $products_options_names->fields['products_options_name'];
        } else {
          $options_name[] = $products_options_names->fields['products_options_name'];
        }
        $options_menu[] = $tmp_radio . "\n";
        $options_comment[] = $products_options_names->fields['products_options_comment'];
        $options_comment_position[] = ($products_options_names->fields['products_options_comment_position'] == '1' ? '1' : '0');
        break;

      // file upload
      case ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_FILE):
        if ($show_attributes_qty_prices_icon == 'true') {
          $options_name[] = '<label class="attribsUploads" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . ATTRIBUTES_QTY_PRICE_SYMBOL . $products_options_names->fields['products_options_name'] . '</label>';
        } else {
          $options_name[] = '<label class="attribsUploads" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options_names->fields['products_options_name'] . '</label>';
        }
        $options_menu[] = $tmp_html . "\n";
        $options_comment[] = $products_options_names->fields['products_options_comment'];
        $options_comment_position[] = ($products_options_names->fields['products_options_comment_position'] == '1' ? '1' : '0');
        break;

      // READONLY
      case ($products_options_names->fields['products_options_type'] == PRODUCTS_OPTIONS_TYPE_READONLY):
        $options_name[] = $products_options_names->fields['products_options_name'];
        $options_menu[] = $tmp_html . "\n";
        $options_comment[] = $products_options_names->fields['products_options_comment'];
        $options_comment_position[] = ($products_options_names->fields['products_options_comment_position'] == '1' ? '1' : '0');
        break;

      // dropdown menu auto switch to selected radio button display
      case ($products_options->RecordCount() == 1):
        if ($show_attributes_qty_prices_icon == 'true') {
          $options_name[] = '<label class="switchedLabel ONE" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . ATTRIBUTES_QTY_PRICE_SYMBOL . $products_options_names->fields['products_options_name'] . '</label>';
        } else {
          $options_name[] = $products_options_names->fields['products_options_name'];
        }
        $options_menu[] = zen_draw_radio_field('id[' . $products_options_names->fields['products_options_id'] . ']', $products_options_value_id, 'selected', 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '"') . '<label class="attribsRadioButton" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '-' . $products_options_value_id . '">' . $products_options_details . '</label>' . "\n";
        $options_comment[] = $products_options_names->fields['products_options_comment'];
        $options_comment_position[] = ($products_options_names->fields['products_options_comment_position'] == '1' ? '1' : '0');
        break;

      default:
        // normal dropdown "SELECT LIST" menu display
        if (isset($_SESSION['cart']->contents[$prod_id]['attributes'][$products_options_names->fields['products_options_id']])) {
          $selected_attribute = $_SESSION['cart']->contents[$prod_id]['attributes'][$products_options_names->fields['products_options_id']];
        } else {
          // use customer-selected values
          if ($_POST['id'] != '') {
            reset($_POST['id']);
            foreach ($_POST['id'] as $key => $value) {
              if ($key == $products_options_names->fields['products_options_id']) {
                $selected_attribute = $value;
                break;
              }
            }
          } else {
            // use default selected set above
          }
        }

        if ($show_attributes_qty_prices_icon == 'true') {
          $options_name[] = ATTRIBUTES_QTY_PRICE_SYMBOL . $products_options_names->fields['products_options_name'];
        } else {
          $options_name[] = '<label class="attribsSelect" for="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '">' . $products_options_names->fields['products_options_name'] . '</label>';
        }

        // START "Stock by Attributes"
        $disablebackorder = null;
        //disable default selected if out of stock
        if ((STOCK_ALLOW_CHECKOUT == 'false') 
		|| (STOCK_ALLOW_CHECKOUT == 'false' && $products_options->fields['products_quantity'] <= 0)) {
          $disablebackorder = 'disabled="disabled"';
        }
        //var_dump($products_options_array);//Debug Line
        //added new image rotate ability ($options_menu_images);
        $options_menu[] = zen_draw_pull_down_menu_SBAmod('id[' . $products_options_names->fields['products_options_id'] . ']', $products_options_array, $selected_attribute, 'id="' . 'attrib-' . $products_options_names->fields['products_options_id'] . '"' . ' class="sbaselectlist"', false, $disablebackorder, $options_menu_images) . "\n";
        // END "Stock by Attributes"

        $options_comment[] = $products_options_names->fields['products_options_comment'];
        $options_comment_position[] = ($products_options_names->fields['products_options_comment_position'] == '1' ? '1' : '0');
        break;
    }

    // attributes images table
    // START "Stock by Attributes"
    if (SBA_SHOW_IMAGE_ON_PRODUCT_INFO == 'true') {
      $options_attributes_image[] = trim($tmp_attributes_image) . "\n";
    }
    // END "Stock by Attributes"
    //Next Item
    $products_options_names->MoveNext();
  }
  // manage filename uploads
  $_GET['number_of_uploads'] = $number_of_uploads;
  //      zen_draw_hidden_field('number_of_uploads', $_GET['number_of_uploads']);
  zen_draw_hidden_field('number_of_uploads', $number_of_uploads);
}