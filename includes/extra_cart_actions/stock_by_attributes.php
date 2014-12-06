<?php

//What about: 'multiple_products_add_product' (Needs to be addressed), 'update_product' (Needs to be addressed), or 'cart' (does a notify action, so may need to address?)actions?
if (isset($_GET['action']) && ($_GET['action'] == 'add_product')) {
  $_SESSION['cart_posted'] = $_POST;
}
if (isset($_GET['action']) && ($_GET['action'] == 'add_product')) {
//Loop for each product in the cart
  $attributes = (isset($_POST['id']) && zen_not_null($_POST['id']) ? $_POST['id'] : null );
  $product_id = zen_get_uprid($_POST['products_id'], $attributes);
  //$product_id = $product['id'];
  $query = 'select stock_id from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = :products_id:';
  $query = $db->bindVars($query, ':products_id:', $_POST['products_id'], 'integer');
  $stock_id = $db->Execute($query);
//  $_SESSION['stock_idquery'] = $stock_id->RecordCount();
//Check if item is an SBA tracked item, if so, then perform analysis of whether to add or not.
  if ($stock_id->RecordCount() > 0) {
//Looks like $_SESSION['cart']->in_cart_mixed($prodId) could be used here to pull the attribute related product information to verify same product is being added to cart... This also may help in the shopping_cart routine added for SBA as all SBA products will have this modifier.
    $cart_quantity = 0;
    $new_quantity = $_POST['cart_quantity']; //Number of items being added (Known to be SBA tracked already)
//  $_SESSION['stock_idquery2'] = $new_quantity . " " . ($_SESSION['cart']->in_cart($product_id) ? "true" : "false");
    if ($_SESSION['cart']->in_cart($product_id)) {
      $cart_quantity = $_SESSION['cart']->get_quantity($product_id);
//Evaluate product to see if the same attributes are on both of them... If so, then need to maintain record of this for verification.
      $_SESSION['stock_idquery3'] = $cart_quantity;
    }
//obtain the quantity remaining of that product with attributes.
    $sbaAvailable = zen_get_products_stock($product_id, $attributes  /*$attributes = null*/ /*(as an array)*/);
//If have more being purchased than available, take action.
    if ($cart_quantity + $new_quantity > $sbaAvailable) {
//If not adding to the cart then do the following:
//Give message then prevent the item from being added to the cart.
      $messageStack->add_session('header', 'Out of stock. Item not added to cart.');
      $_GET['action'] = '';
    }
  }
/*
  $products = $_SESSION['cart']->get_products();
  for ($i = 0, $n = sizeof($products); $i < $n; $i++) {
    $product_id = $products[$i]['id'];
//Check if SBA tracked product.
    $query = 'select stock_id from ' . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . ' where products_id = :products_id:';
    $db->bindVars($query, ':products_id:', $product_id, 'integer');
    $stock_id = $db->Execute($query);
//Thought is this: If the product is SBA tracked, then need to evaluate the specific stock_id, and increment the count of that stock_id
// as an array ($stocknum[$stock_id]++;)
// and do this for all products, then when done with all products in the cart
// use a $key=>value loop of the products and see if the product being added is out of range... Hmmm.. This also means that need to
// instead of all of this looking only through the cart, look through the cart for the item being added to reduce some of the work...
// Point is that one product is being added to the cart, which already does or does not have product in it,
// The POST variables are still available to review for this, so there is a lot that can be done here... This is just before taking an
// action to modify the cart...
    if ($stock_id->RecordCount() > 0) {
// If it is a product tracked by SBA
//obtain the quantity remaining of that product with attributes.
*/  //    $sbaAvailable = zen_get_products_stock($product_id, $attributes = null /*(as an array)*/);
//COMMENT ABOUT CARTPRODUCTCOUNT FUNCTION... It does not seem to differentiate between customers, but instead
//Simply identify that the product is in a shopping cart.. So, how would this work/was is the expected result
// if there were two customers with the same product in the cart?
// cartProductCount($products_id)
//Need a way to look at all products with the same attributes to then compare with the SBA table...
/*      if ( 0 > $sbaAvailable) {
//if the quantity being added exceeds the quantity available
// display error message
// Option to reduce quantity added to the maximum available?
// bof: adjust new quantity to be same as current in stock // TAKEN FROM INCLUDES/CLASSES/SHOPPING_CART.PHP
        $chk_current_qty = zen_get_products_stock($_POST['products_id'], $_POST['attributes']);
        $this->flag_duplicate_msgs_set = FALSE;
        if (STOCK_ALLOW_CHECKOUT == 'false' && ($cart_qty + $new_qty > $chk_current_qty)) {
          $new_qty = $chk_current_qty;
          $messageStack->add_session('shopping_cart', ($this->display_debug_messages ? 'C: FUNCTION ' . __FUNCTION__ . ': ' : '') . WARNING_PRODUCT_QUANTITY_ADJUSTED . zen_get_products_name($_POST['products_id']), 'caution');
          $this->flag_duplicate_msgs_set = TRUE;
        }

// eof: adjust new quantity to be same as current in stock
        $messageStack->add_session('shopping_cart', ERROR_MAXIMUM_QTY . zen_get_products_name($_POST['products_id'][$i]), 'caution');
// includes/classes/responsive_sheffield_blue/shopping_cart.php
// Line #1876 : $messageStack->add_session('shopping_cart', ERROR_MAXIMUM_QTY . zen_get_products_name($prodId), 'caution');
        $messageStack->add_session('header', 'Out of stock');
// Otherwise don't add and reset the action to not add the product.
        $messageStack->add_session('header', 'Out of stock');
// $messageStack->add('', 'Note to customer', 'caution'); //<- Used if displaying on the current page... (ie. not after a redirect).
// OR if to be displayed after a redirect:
// $messageStack->add_session('shopping_cart', 'Out of stock');
// The following should be performed only if no products are being added to the cart.
        $_GET['action'] = '';
      }
    } else {
// If it is not a product tracked by SBA do nothing special.
    }
  }*/
}