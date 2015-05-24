<?php
/*
      QT Pro Version 4.1
  
      pad_sequenced_dropdowns.php
  
      Contribution extension to:
        osCommerce, Open Source E-Commerce Solutions
        http://www.oscommerce.com
     
      Copyright (c) 2004, 2005 Ralph Day
      Released under the GNU General Public License
  
      Based on prior works released under the GNU General Public License:
        QT Pro prior versions
          Ralph Day, October 2004
          Tom Wojcik aka TomThumb 2004/07/03 based on work by Michael Coffman aka coffman
          FREEZEHELL - 08/11/2003 freezehell@hotmail.com Copyright (c) 2003 IBWO
          Joseph Shain, January 2003
        osCommerce MS2
          Copyright (c) 2003 osCommerce
          
      Modifications made:
          11/2004 - Created
          12/2004 - Fix _draw_dropdown_sequence_js to prevent js error when all attribute combinations
                    are out of stock
          03/2005 - Remove '&' for pass by reference from parameters to call of
                    _build_attributes_combinations.  Only needed on method definition and causes
                    error messages on some php versions/configurations
  
*******************************************************************************************
  
      QT Pro Product Attributes Display Plugin
  
      pad_sequenced_dropdowns.php - Display stocked product attributes first as one dropdown for each attribute
                                    with Javascript to force user to select attributes in sequence so only
                                    in-stock combinations are seen.
  
      Class Name: pad_sba_sequenced_dropdowns
  
      This class generates the HTML to display product attributes.  First, product attributes that
      stock is tracked for are displayed, each attribute in its own dropdown list with Javascript to
      force user to select attributes in sequence so only in-stock combinations are seen.  Then
      attributes that stock is not tracked for are displayed, each attribute in its own dropdown list.
  
      Methods overidden or added:
  
        _draw_stocked_attributes            draw attributes that stock is tracked for
        _draw_dropdown_sequence_js          draw Javascript to force the attributes to be selected in
                                            sequence
        _SetConfigurationProperties         set local properties
                                            
*/
  require_once(DIR_WS_CLASSES . 'pad_multiple_dropdowns.php');

  class pad_sba_sequenced_dropdowns extends pad_multiple_dropdowns {


/*
    Method: _draw_stocked_attributes
  
    draw dropdown lists for attributes that stock is tracked for

  
    Parameters:
  
      none
  
    Returns:
  
      string:         HTML to display dropdown lists for attributes that stock is tracked for
  
*/
    function _draw_stocked_attributes() {
      global $db;
      
      $out='';
      
      $attributes = $this->_build_attributes_array(true, true);
      if (sizeof($attributes)<=1) {
        return parent::_draw_stocked_attributes();
      }

	/*for ($o=0; $o<=sizeof($attributes); $o++) */{
$o = 0;
      // Check stock
//var_dump($attributes[0]);
      $s=sizeof($attributes[$o]['ovals']);
      for ($a=0; $a<$s; $a++) {

// mc12345678 NEED TO PERFORM ABOVE QUERY BASED OFF OF THE INFORMATION IN $attributes[0]['ovals'] to pull only the data associated with the one attribute in the first selection... Needs to be clear enough that the sequence of the data searched for identifies the appropriate attribute.  Also need to make sure that the subsequent data forced to display below actually pulls the out of stock information associated with the sub (sub-sub(sub-sub-sub)) attribute.

        $attribute_stock_query = "select sum(pwas.quantity) as quantity from " .  TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " pwas where pwas.products_id = :products_id: AND pwas.quantity >= 0 AND pwas.stock_attributes like (SELECT products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_id = :products_id: and options_values_id = :options_values_id:) OR pwas.stock_attributes like CONCAT((SELECT products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_id = :products_id: and options_values_id = :options_values_id:),',%') or pwas.stock_attributes like CONCAT('%,',(SELECT products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_id = :products_id: and options_values_id = :options_values_id:),',%') or pwas.stock_attributes like CONCAT('%,',(SELECT products_attributes_id from " . TABLE_PRODUCTS_ATTRIBUTES . " WHERE products_id = :products_id: and options_values_id = :options_values_id:))";
        $attribute_stock_query = $db->bindVars($attribute_stock_query, ':products_id:', $this->products_id, 'integer');
        $attribute_stock_query = $db->bindVars($attribute_stock_query, ':options_values_id:', $attributes[$o]['ovals'][$a]['id'], 'integer');
        

        $attribute_stock = $db->Execute($attribute_stock_query);
//echo 'Attrib stock_' . $a . ' is: ' . $attribute_stock->RecordCount();
        $out_of_stock=(($attribute_stock->fields['quantity'])==0);  // This looks at all variants indicating 0 or no variant being present.  Need to modify to look at the quantity for each variant... So look at the quantity of each and if that quantity is zero then, that line needs to be modified...
        if ($out_of_stock && ($this->show_out_of_stock == 'True')) {
          switch ($this->mark_out_of_stock) {
               case 'Left':  $attributes[$o]['ovals'][$a]['text'] = TEXT_OUT_OF_STOCK.' - '.$attributes[$o]['ovals'][$a]['text'];
                    break;
               case 'Right': $attributes[$o]['ovals'][$a]['text'] .=' - '.TEXT_OUT_OF_STOCK;
                    break;
          } //end switch
        } //end if
        elseif ($out_of_stock && ($this->show_out_of_stock != 'True')) {
          unset($attributes[$o]['ovals'][$a]);
        } //end elseif
//$attribute_stock->MoveNext();
      } // end for $a
	} // end for $o      
      if (sizeof($attributes[0]['ovals']) == 0) {
        // NEED TO DISPLAY A MESSAGE OR ADD SOMETHING TO THE LIST AS THERE
        //  IS NO PRODUCT TO DISPLAY (ALL OUT OF ORDER) SO NEED TO DO WHAT
        //  NEEDS TO BE DONE IN THAT CONDITION. 
      }
      // Draw first option dropdown with all values
      $out.='<tr><td align="right" class="main"><b>'.$attributes[0]['oname'].":</b></td><td class=\"main\">".zen_draw_pull_down_menu('id['.$attributes[0]['oid'].']',array_merge(array(array('id'=>0, 'text'=>'First select '.$attributes[0]['oname'])), $attributes[0]['ovals']),$attributes[0]['default'], "onchange=\"i".$attributes[0]['oid']."(this.form);\"")."</td></tr>\n";

      // Draw second to next to last option dropdowns - no values, with onchange
      for($o=1; $o<sizeof($attributes)-1; $o++) {
        $out.='<tr><td align="right" class="main"><b>'.$attributes[$o]['oname'].":</b></td><td class=\"main\">".zen_draw_pull_down_menu('id['.$attributes[$o]['oid'].']',array(array('id'=>0, 'text'=>'Next select '.$attributes[$o]['oname'])), '', "onchange=\"i".$attributes[$o]['oid']."(this.form);\"")."</td></tr>\n";
      } // end for $o       

      // Draw last option dropdown - no values, no onchange      
      $out.='<tr><td align="right" class="main"><b>'.$attributes[$o]['oname'].":</b></td><td class=\"main\">".zen_draw_pull_down_menu('id['.$attributes[$o]['oid'].']',array(array('id'=>0, 'text'=>'Next select '.$attributes[$o]['oname'])), '', "onchange=\"i".$attributes[$o]['oid']."(this.form);\"")."</td></tr>\n";
      
//      $out.=$this->_draw_out_of_stock_message_js($attributes);
      $out.=$this->_draw_dropdown_sequence_js($attributes);
      
      return $out;
    } // end if size attributes

/*
    Method: _draw_dropdown_sequence_js
  
    draw Javascript to display out of stock message for out of stock attribute combinations

  
    Parameters:
  
      $attributes     array   Array of attributes for the product.  Format is as returned by
                              _build_attributes_array.
  
    Returns:
  
      string:         Javascript to force user to select stocked dropdowns in sequence
  
*/
    function _draw_dropdown_sequence_js($attributes) {
      $out='';
      
      $combinations = array();
      $selected_combination = 0;
      $this->_build_attributes_combinations($attributes, true, 'None', $combinations, $selected_combination); // Used to identify all possible combinations as provided in SBA.

      $this->_build_attributes_combinations($attributes, false,'None', $combinations2, $selected_combination); // This is used to identify what is out of stock by comparison with the above.

      $out.="<tr><td>&nbsp;</td><td><span id=\"oosmsg\" class=\"errorBox\"></span>\n";
      $out.="<tr><td colspan=\"2\">&nbsp;\n";
      
      $out.="<script type=\"text/javascript\" language=\"javascript\"><!--\n";
      // build javascript array of in stock combinations of the form
      // {optval1:{optval2:{optval3:1,optval3:1}, optval2:{optval3:1}}, optval1:{optval2:{optval3:1}}};
      $out.="  var stk=".$this->_draw_js_stock_array($combinations).";\n";
      $out.="  var stk2=".$this->_draw_js_stock_array($combinations2).";\n";
      // js arrays of possible option values/text for dropdowns
      // do all but the first attribute (its dropdown never changes)
      for ($curattr=1; $curattr<sizeof($attributes); $curattr++) {
        $attr = $attributes[$curattr];
        $out.="  var txt".$attr['oid']."={";
        foreach ($attr['ovals'] as $oval) {
          $out.=$oval['id'].":'".$oval['text']."',";
        }
        $out=substr($out,0,strlen($out)-1)."};";
        $out.="\n";
      }

      // js functions to set next dropdown options when a dropdown selection is made
      // do all but last attribute (nothing needs to happen when it changes except additional validation action to improve the customer experience)
      for ($curattr=0; $curattr<sizeof($attributes); $curattr++) {
        $attr=$attributes[$curattr];
        $out.="  function i".$attr['oid']."(frm) {\n";
        if ($curattr < sizeof($attributes)-1) {
          $out.="    var displayshown = false;\n"; //Allow control of the alert to provide it one time only.
          $out.="    var span=document.getElementById(\"oosmsg\");\n";
          $out.="    while (span.childNodes[0]) {\n";
          $out.="      span.removeChild(span.childNodes[0]);\n";
		  $out.="    }\n";
          $i=key($attributes);
          for ($i=$curattr+1; $i<sizeof($attributes); $i++) {
        $out.="    frm['id[".$attributes[$i]['oid']."]'].length=1;\n";
          }
//         $out.="    stkmsg(frm);\n";
          //Loop on all selections available if all stock were included.
        $out.="    for (opt in stk";
          $outArray = '';
          for ($i=0; $i<=$curattr; $i++) {
            $outArray.="[frm['id[".$attributes[$i]['oid']."]'].value]";
          }
          $out.=$outArray;
          $out.=") {\n";
          //The following checks to verify that the option exists in the list
          //  Without looking at the sub-selection yet.  Is necessary on 
          //  a product with two or more attributes, where any attribute is
          //  already exhausted before the last selectable attribute.
        $out.="      if (typeof stk2";
        $out.=$outArray;
        $out.=" != \"undefined\") {\n"; 
        $out.="        if (typeof stk2";
          $out.=$outArray;
          $out.="[opt] != \"undefined\") {\n";
          //  Add the product to the next selectable list item as it is in stock.
        $out.="          frm['id[".$attributes[$curattr+1]['oid']."]'].options[frm['id[".$attributes[$curattr+1]['oid']."]'].length]=new Option(txt".$attributes[$curattr+1]['oid']."[opt]";
          if ($curattr==sizeof($attributes)-2) {
            if (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true') {
              $out.=" + '" . PWA_STOCK_QTY . "' + stk2";
              $out.=$outArray;
              $out.="[opt]";
            }
          }
        $out.=",opt);\n";
        $out.="        } else {\n";
          if (PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK == 'True') {
          //  Add the product to the next selectable list item and identify its out-of-stock status as controlled by the admin panel.  
        $out.="          frm['id[".$attributes[$curattr+1]['oid']."]'].options[frm['id[".$attributes[$curattr+1]['oid']."]'].length]=new Option(";
            if (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'None') {
              $out.="txt".$attributes[$curattr+1]['oid']."[opt]"; 
            } elseif (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'Left') {
              $out.="'".PWA_OUT_OF_STOCK ."' + txt".$attributes[$curattr+1]['oid']."[opt]"; 
            } elseif (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'Right') {
              $out.="txt".$attributes[$curattr+1]['oid']."[opt] + '".PWA_OUT_OF_STOCK . "'";          
            }
            $out.=",opt);\n";
          }
        $out.="        }\n";
        $out.="      } else {\n";
          if (PRODINFO_ATTRIBUTE_SHOW_OUT_OF_STOCK == 'True') {
          //  Add the product to the next selectable list item and identify its out-of-stock status as controlled by the admin panel.  
        $out.="        frm['id[".$attributes[$curattr+1]['oid']."]'].options[frm['id[".$attributes[$curattr+1]['oid']."]'].length]=new Option(";
            if (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'None') {
              $out.="txt".$attributes[$curattr+1]['oid']."[opt]"; 
            } elseif (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'Left') {
              $out.="'".PWA_OUT_OF_STOCK ."' + txt".$attributes[$curattr+1]['oid']."[opt]"; 
            } elseif (PRODINFO_ATTRIBUTE_MARK_OUT_OF_STOCK == 'Right') {
              $out.="txt".$attributes[$curattr+1]['oid']."[opt] + '".PWA_OUT_OF_STOCK . "'";
            }
            $out.=",opt);\n";
        $out.="        if (displayshown != true) {\n";
        $out.="          alert('All selections of the attributes below this one are Out of Stock. Please select a different option.');\n";
        $out.="          stkmsg(frm);\n";
        $out.="          displayshown=true;\n";
        $out.="        }\n";
          }
        $out.="      }\n";
        $out.="    }\n";
        } else {
          $out.="    if (!chkstk(frm)) {\n";
          $out.="      stkmsg(frm);\n";
      	  $out.="      alert('Your choice is out of stock.');\n";
          $out.="    }\n";
        }
        $out.="  }\n";
      }

      // js to initialize dropdowns to defaults if product id contains attributes (i.e. clicked through to product page from cart)
      $out.="  i" . $attributes[0]['oid'] . "(document.cart_quantity);\n";
      for($o=1; $o<sizeof($attributes)-1; $o++) {
        if ($attributes[$o]['default']!='') {
          $out.="  document.cart_quantity['id[".$attributes[$o]['oid']."]'].value=".$attributes[$o]['default'].";\n";
          $out.="  i" . $attributes[$o]['oid'] . "(document.cart_quantity);\n";
        }
        else break;
      }
      if (($o == sizeof($attributes)-1) && ($attributes[$o]['default']!='')) {
        $out.="  document.cart_quantity['id[".$attributes[$o]['oid']."]'].value=".$attributes[$o]['default'].";\n";
      }
      
      // js to not allow add to cart if selections not made
      $out.="  function chksel(form) {\n";
      $out.="    var ok=true;\n";
      foreach ($attributes as $attr) {
	      $out.="    if (form['id[".$attr['oid']."]'].value==0) ok=false;\n";
      }
      $out.="    if (!ok) {\n";
      $out.="      alert('".TEXT_SELECT_OPTIONS."');\n";
      $out.="      form.action = '';\n";
      $out.="      return false;\n";
      $out.="    } else {\n";
      $out.=" "; //Need to check stock somewhere in this, perhaps some help from other code?
      $out.="      return true;\n";
      $out.="    }\n";
      $out.="  }\n";
      $out.="  document.cart_quantity.onsubmit=function () {chksel(this)};\n";

      
      $out.="  function chkstk(frm) {\n";
      
        // build javascript array of in stock combinations
      $out.="    var stk3=".$this->_draw_js_stock_array($combinations2).";\n";
      $out.="    var instk=false;\n";
      
        // build javascript if statement to test level by level for existence  
//        $out.='    ';
      for ($i=0; $i<sizeof($attributes); $i++) {
        $out.="    " . str_repeat("  ",$i);
        $out.='if (stk3';
        for ($j=0; $j<=$i; $j++) {
          $out.="[frm['id[".$attributes[$j]['oid']."]'].value]";
        }
        $out.=") {\n";
      }
      $out.="    " . str_repeat("  ",sizeof($attributes)) . "instk=true;\n";
      for ($i=sizeof($attributes)-1; $i>0; $i--) {
        $out.="    " . str_repeat("  ",$i) . "}\n";
      }
      $out.="    }\n";
      $out.="    return instk;\n";
      $out.="  }\n";

      if ($this->out_of_stock_msgline == 'True') {
        // set/reset out of stock message based on selection
        $out.="  function stkmsg(frm) {\n";
        $out.="    var instk=chkstk(frm);\n";
        $out.="    var span=document.getElementById(\"oosmsg\");\n";
        $out.="    while (span.childNodes[0])\n";
        $out.="      span.removeChild(span.childNodes[0]);\n";
        $out.="    if (!instk) {\n";
        $out.="      span.appendChild(document.createTextNode(\"".TEXT_OUT_OF_STOCK_MESSAGE."\"));\n";
//        $out.="      alert('Your choice is out of stock.');\n";
        $out.="    } else {\n";
        $out.="      span.appendChild(document.createTextNode(\" \"));\n";
        $out.="    }\n";
        $out.="  }\n";
        //initialize out of stock message
//          $out.="  stkmsg(document.cart_quantity);\n";
      }
      $out.="//--></script>\n";
      $out.="\n</td></tr></td></tr>\n";
      
      return $out;
    }

/*
    Method: _draw_js_stock_array
  
    Draw a Javascript array containing the given attribute combinations.
    Generally used to draw array of in-stock combinations for Javascript out of stock
    validation and messaging.
  
    Parameters:
  
      $combinations        array   Array of combinations to build the Javascript array for.
                                   Array must be of the form returned by _build_attributes_combinations
                                   Usually this array only contains in-stock combinations.
  
    Returns:
  
      string:                 Javacript array definition.  Excludes the "var xxx=" and terminating ";".  Form is:
                              {optval1:{optval2:{optval3:1,optval3:1}, optval2:{optval3:1}}, optval1:{optval2:{optval3:1}}}
                              For example if there are 3 options and the instock value combinations are:
                                opt1   opt2   opt3
                                  1      5      4
                                  1      5      8
                                  1     10      4
                                  3      5      8
                              The string returned would be
                                {1:{5:{4:1,8:1}, 10:{4:1}}, 3:{5:{8:1}}}
  
*/
    function _draw_js_stock_array($combinations) {
      if (!((isset($combinations)) && (is_array($combinations)) && (sizeof($combinations) > 0))){
        return '{}';
      }
      $out='';
      foreach ($combinations[0]['comb'] as $oid=>$ovid) {
        $out.='{'.$ovid.':';
        $ovids[]=$ovid;
        $opts[]=$oid;
      }
      if (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true') { 
        //Search for quantity in the SBA table... 
        $numbadd=zen_get_products_stock($_GET['products_id'],$ovids);
        if ($numbadd == 0) {
          $numbadd = '0';
        }
        $out.=$numbadd;
      } else {
        $out.='1';
      }
      
      for ($combindex=1; $combindex<sizeof($combinations); $combindex++) {
        $comb=$combinations[$combindex]['comb'];
        for ($i=0; $i<sizeof($opts)-1; $i++) {
          if ($comb[$opts[$i]]!=$combinations[$combindex-1]['comb'][$opts[$i]]){
            break;
          }
        }
        $out.=str_repeat('}',sizeof($opts)-1-$i).',';
        if ($i<sizeof($opts)-1) {
          for ($j=$i; $j<sizeof($opts)-1; $j++){
            $out.=$comb[$opts[$j]].':{';
          }
        }
        $out.=$comb[$opts[sizeof($opts)-1]] . ':';
        if (STOCK_SHOW_ATTRIB_LEVEL_STOCK == 'true') {
          $idvals = array();
          foreach($comb as $ids=>$idvalsadd) {
            $idvals[] = $idvalsadd;
          }
          $numadd = zen_get_products_stock($_GET['products_id'],$idvals);
          if ($numadd == 0) {
            $numadd = '0';
          }
          $out.=$numadd;
        } else {
          $out.='1';
        }
      }
      $out.=str_repeat('}',sizeof($opts));
      
      return $out;
    }

    
    
/*
    Method: _SetConfigurationProperties
  
    Set local configuration properties
  
    Parameters:
  
      $prefix      sting     Prefix for the osCommerce DB constants
  
    Returns:
  
      nothing
  
*/
  /*  function _SetConfigurationProperties($prefix) {

      // These properties are not used directly by this class 
      // They are set to match how this class displays for the case of a single
      // attribute where the parent class _draw_stocked_attributes method is called
      $this->show_out_of_stock    = 'True';
      $this->mark_out_of_stock    = 'Right';
      $this->out_of_stock_msgline = 'True';
      $this->no_add_out_of_stock  = 'False';

    }*/

  }
  
/*    function _draw_js_stock_array($combinations) {
      if (!((isset($combinations)) && (is_array($combinations)) && (sizeof($combinations) >= 0))){
        return '{}';
      }
      $out='';
      foreach ($combinations[0]['comb'] as $oid=>$ovid) {
        $out.='{'.$ovid.':';
        $opts[]=$oid;
      }
      $out.='1';
      
      for ($combindex=1; $combindex<sizeof($combinations); $combindex++) {
        $comb=$combinations[$combindex]['comb'];
        for ($i=0; $i<sizeof($opts)-1; $i++) {
          if ($comb[$opts[$i]]!=$combinations[$combindex-1]['comb'][$opts[$i]]) break;
        }
        $out.=str_repeat('}',sizeof($opts)-1-$i).',';
        if ($i<sizeof($opts)-1) {
          for ($j=$i; $j<sizeof($opts)-1; $j++)
            $out.=$comb[$opts[$j]].':{';
        }
        $out.=$comb[$opts[sizeof($opts)-1]].':1';
      }
      $out.=str_repeat('}',sizeof($opts));
      
      return $out;
    }*/

