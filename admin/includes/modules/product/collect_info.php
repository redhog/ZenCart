<?php
/**
 * @package admin
 * @copyright Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: collect_info.php 3009 2006-02-11 15:41:10Z wilt $
 */
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}
    $parameters = array('products_name' => '',
                       'products_description' => '',
                       'products_url' => '',
                       'products_id' => '',
                       'products_quantity' => '',
                       'products_model' => '',
		       'products_parts' => array(),
		       'products_wholesalers' => array(),
                       'products_image' => '',
                       'products_price' => '',
                       'products_virtual' => DEFAULT_PRODUCT_PRODUCTS_VIRTUAL,
                       'products_weight' => '',
                       'products_date_added' => '',
                       'products_last_modified' => '',
                       'products_date_available' => '',
                       'products_status' => '',
                       'products_tax_class_id' => DEFAULT_PRODUCT_TAX_CLASS_ID,
                       'manufacturers_id' => '',
                       'products_quantity_order_min' => '',
                       'products_quantity_order_units' => '',
                       'products_priced_by_attribute' => '',
                       'product_is_free' => '',
                       'product_is_call' => '',
                       'products_quantity_mixed' => '',
                       'product_is_always_free_shipping' => DEFAULT_PRODUCT_PRODUCTS_IS_ALWAYS_FREE_SHIPPING,
                       'products_qty_box_status' => PRODUCTS_QTY_BOX_STATUS,
                       'products_quantity_order_max' => '0',
                       'products_sort_order' => '0',
                       'products_discount_type' => '0',
                       'products_discount_type_from' => '0',
                       'products_price_sorter' => '0',
                       'master_categories_id' => ''
                       );

    $pInfo = new objectInfo($parameters);

    if (isset($_GET['pID']) && empty($_POST)) {
      $product = $db->Execute("select pd.products_name, pd.products_description, pd.products_url,
                                      p.products_id, p.products_quantity, p.products_model,
                                      p.products_image, p.products_price, p.products_virtual, p.products_weight,
                                      p.products_date_added, p.products_last_modified,
                                      date_format(p.products_date_available, '%Y-%m-%d') as
                                      products_date_available, p.products_status, p.products_tax_class_id,
                                      p.manufacturers_id,
                                      p.products_quantity_order_min, p.products_quantity_order_units, p.products_priced_by_attribute,
                                      p.product_is_free, p.product_is_call, p.products_quantity_mixed,
                                      p.product_is_always_free_shipping, p.products_qty_box_status, p.products_quantity_order_max,
                                      p.products_sort_order,
                                      p.products_discount_type, p.products_discount_type_from,
                                      p.products_price_sorter, p.master_categories_id
                              from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd
                              where p.products_id = '" . (int)$_GET['pID'] . "'
                              and p.products_id = pd.products_id
                              and pd.language_id = '" . (int)$_SESSION['languages_id'] . "'");
      $parameters = $product->fields;

      $parts = $db->Execute("select products_parts.*, products.*, products_description.*
                             from " . TABLE_PRODUCTS_PARTS . " as products_parts, " . TABLE_PRODUCTS . " as products, " . TABLE_PRODUCTS_DESCRIPTION . " as products_description
                             where products_parts.product = '" . (int)$_GET['pID'] . "'
                             and products.products_id = products_parts.product_part
                             and products_description.products_id = products_parts.product_part
                             and products_description.language_id = '" . (int)$_SESSION['languages_id'] . "'
                             order by products.products_model");
      $products_parts = array();
      while (!$parts->EOF) {
        $products_parts[$parts->fields['products_id']] = $parts->fields;
        $parts->MoveNext();
      }
      $parameters['products_parts'] = $products_parts;

      $wholesalers = $db->Execute("select products_wholesalers.*, wholesalers.*, wholesalers_info.*
                             from " . TABLE_PRODUCTS_WHOLESALERS . " as products_wholesalers, " . TABLE_WHOLESALERS . " as wholesalers, " . TABLE_WHOLESALERS_INFO . " as wholesalers_info
                             where products_wholesalers.product = '" . (int)$_GET['pID'] . "'
                             and products_wholesalers.wholesaler = wholesalers.wholesalers_id
                             and products_wholesalers.wholesaler = wholesalers_info.wholesalers_id
                             and wholesalers_info.languages_id = '" . (int)$_SESSION['languages_id'] . "'
                             order by wholesalers.wholesalers_id, products_wholesalers.amount");
      $products_wholesalers = array();
      while (!$wholesalers->EOF) {
        $products_wholesalers[$wholesalers->fields['products_wholesalers_id']] = $wholesalers->fields;
        $wholesalers->MoveNext();
      }
      $parameters['products_wholesalers'] = $products_wholesalers;

      $pInfo->objectInfo($parameters);
    } elseif (zen_not_null($_POST)) {
      $parameters = $_POST;

      $products_parts = array();
      $delete_parts = array();
      foreach ($parameters as $key => $value) {
        // name is products_part__PRODUCTID__fieldname
        if (strncmp($key, "products_part__", strlen("products_part__")) == 0) {
          $keyparts = explode('__', $key);
          if ($keyparts[2] == 'delete') {
 	    $delete_parts[] = $keyparts[1];
 	  } else {
	    if ($keyparts[2] == 'visible') $value = 1;
   	    if (!isset($products_parts[$keyparts[1]]))
	      $products_parts[$keyparts[1]] = array('products_id' => $keyparts[1], 'visible' => 0);
	    $products_parts[$keyparts[1]][$keyparts[2]] = $value;
	  }
        }
      }
      foreach ($delete_parts as $part)
        unset($products_parts[$part]);

      if (isset($_POST['products_add_part'])) {
	$parts = $db->Execute("select products.*, products_description.*
			       from " . TABLE_PRODUCTS . " as products, " . TABLE_PRODUCTS_DESCRIPTION . " as products_description
			       where products.products_model = '" . $parameters['products_new_part_model'] . "'
			       and products_description.products_id = products.products_id
			       and products_description.language_id = '" . (int)$_SESSION['languages_id'] . "'");
	if ($parts->RecordCount() != 0) {
 	  $products_parts[$parts->fields['products_id']] = $parts->fields;
	  $products_parts[$parts->fields['products_id']]['amount'] = $parameters['products_new_part_amount'];
	  $products_parts[$parts->fields['products_id']]['visible'] = isset($parameters['products_new_part_visible']);
        }
      }

      $parameters['products_parts'] = $products_parts;

      $products_wholesalers = array();
      $delete_wholesalers = array();
      foreach ($parameters as $key => $value) {
        // name is products_wholesaler__PRODUCTID__fieldname
        if (strncmp($key, "products_wholesaler__", strlen("products_wholesaler__")) == 0) {
          $keyparts = explode('__', $key);
          if ($keyparts[2] == 'delete') {
 	    $delete_wholesalers[] = $keyparts[1];
 	  } else {
   	    if (!isset($products_wholesalers[(int) $keyparts[1]]))
	      $products_wholesalers[$keyparts[1]] = array('products_wholesalers_id' => $keyparts[1]);
	    $products_wholesalers[$keyparts[1]][$keyparts[2]] = $value;
	  }
        }
      }
      foreach ($delete_wholesalers as $wholesaler)
        unset($products_wholesalers[$wholesaler]);

      if (isset($_POST['products_add_wholesaler'])) {
	$wholesalers = $db->Execute("select wholesalers.*, wholesalers_info.*
			       from " . TABLE_WHOLESALERS . " as wholesalers, " . TABLE_WHOLESALERS_INFO . " as wholesalers_info
			       where wholesalers.wholesalers_id = '" . $parameters['products_new_wholesaler_wholesaler'] . "'
			       and wholesalers_info.wholesalers_id = '" . $parameters['products_new_wholesaler_wholesaler'] . "'
			       and wholesalers_info.languages_id = '" . (int)$_SESSION['languages_id'] . "'");
	if ($wholesalers->RecordCount() != 0) {
 	  $products_wholesalers_id = array_reduce(array_keys($products_wholesalers), "max", 0) + 1;
	  var_dump($products_wholesalers_id);
 	  $products_wholesalers[$products_wholesalers_id] = $wholesalers->fields;
	  $products_wholesalers[$products_wholesalers_id]['products_wholesalers_id'] = $products_wholesalers_id;
	  $products_wholesalers[$products_wholesalers_id]['amount'] = $parameters['products_new_wholesaler_amount'];
	  $products_wholesalers[$products_wholesalers_id]['price'] = $parameters['products_new_wholesaler_price'];
	  $products_wholesalers[$products_wholesalers_id]['wholesaler'] = $parameters['products_new_wholesaler_wholesaler'];
        }
      }

      $parameters['products_wholesalers'] = $products_wholesalers;

      $pInfo->objectInfo($parameters);
      $products_name = $_POST['products_name'];
      $products_description = $_POST['products_description'];
      $products_url = $_POST['products_url'];
    }

    $manufacturers_array = array(array('id' => '', 'text' => TEXT_NONE));
    $manufacturers = $db->Execute("select manufacturers_id, manufacturers_name
                                   from " . TABLE_MANUFACTURERS . " order by manufacturers_name");
    while (!$manufacturers->EOF) {
      $manufacturers_array[] = array('id' => $manufacturers->fields['manufacturers_id'],
                                     'text' => $manufacturers->fields['manufacturers_name']);
      $manufacturers->MoveNext();
    }

    $wholesalers_array = array();
    $wholesalers = $db->Execute("select wholesalers_id, wholesalers_name
                                   from " . TABLE_WHOLESALERS . " order by wholesalers_name");
    while (!$wholesalers->EOF) {
      $wholesalers_array[] = array('id' => $wholesalers->fields['wholesalers_id'],
                                     'text' => $wholesalers->fields['wholesalers_name']);
      $wholesalers->MoveNext();
    }

    $tax_class_array = array(array('id' => '0', 'text' => TEXT_NONE));
    $tax_class = $db->Execute("select tax_class_id, tax_class_title
                                     from " . TABLE_TAX_CLASS . " order by tax_class_title");
    while (!$tax_class->EOF) {
      $tax_class_array[] = array('id' => $tax_class->fields['tax_class_id'],
                                 'text' => $tax_class->fields['tax_class_title']);
      $tax_class->MoveNext();
    }

    $languages = zen_get_languages();

    if (!isset($pInfo->products_status)) $pInfo->products_status = '1';
    switch ($pInfo->products_status) {
      case '0': $in_status = false; $out_status = true; break;
      case '1':
      default: $in_status = true; $out_status = false;
        break;
    }
// set to out of stock if categories_status is off and new product or existing products_status is off
    if (zen_get_categories_status($current_category_id) == '0' and $pInfo->products_status != '1') {
      $pInfo->products_status = 0;
      $in_status = false;
      $out_status = true;
    }

// Virtual Products
    if (!isset($pInfo->products_virtual)) $pInfo->products_virtual = PRODUCTS_VIRTUAL_DEFAULT;
    switch ($pInfo->products_virtual) {
      case '0': $is_virtual = false; $not_virtual = true; break;
      case '1': $is_virtual = true; $not_virtual = false; break;
      default: $is_virtual = false; $not_virtual = true;
    }
// Always Free Shipping
    if (!isset($pInfo->product_is_always_free_shipping)) $pInfo->product_is_always_free_shipping = DEFAULT_PRODUCT_PRODUCTS_IS_ALWAYS_FREE_SHIPPING;
    switch ($pInfo->product_is_always_free_shipping) {
      case '0': $is_product_is_always_free_shipping = false; $not_product_is_always_free_shipping = true; $special_product_is_always_free_shipping = false; break;
      case '1': $is_product_is_always_free_shipping = true; $not_product_is_always_free_shipping = false; $special_product_is_always_free_shipping = false; break;
      case '2': $is_product_is_always_free_shipping = false; $not_product_is_always_free_shipping = false; $special_product_is_always_free_shipping = true; break;
      default: $is_product_is_always_free_shipping = false; $not_product_is_always_free_shipping = true; $special_product_is_always_free_shipping = false;
    }
// products_qty_box_status shows
    if (!isset($pInfo->products_qty_box_status)) $pInfo->products_qty_box_status = PRODUCTS_QTY_BOX_STATUS;
    switch ($pInfo->products_qty_box_status) {
      case '0': $is_products_qty_box_status = false; $not_products_qty_box_status = true; break;
      case '1': $is_products_qty_box_status = true; $not_products_qty_box_status = false; break;
      default: $is_products_qty_box_status = true; $not_products_qty_box_status = false;
    }
// Product is Priced by Attributes
    if (!isset($pInfo->products_priced_by_attribute)) $pInfo->products_priced_by_attribute = '0';
    switch ($pInfo->products_priced_by_attribute) {
      case '0': $is_products_priced_by_attribute = false; $not_products_priced_by_attribute = true; break;
      case '1': $is_products_priced_by_attribute = true; $not_products_priced_by_attribute = false; break;
      default: $is_products_priced_by_attribute = false; $not_products_priced_by_attribute = true;
    }
// Product is Free
    if (!isset($pInfo->product_is_free)) $pInfo->product_is_free = '0';
    switch ($pInfo->product_is_free) {
      case '0': $in_product_is_free = false; $out_product_is_free = true; break;
      case '1': $in_product_is_free = true; $out_product_is_free = false; break;
      default: $in_product_is_free = false; $out_product_is_free = true;
    }
// Product is Call for price
    if (!isset($pInfo->product_is_call)) $pInfo->product_is_call = '0';
    switch ($pInfo->product_is_call) {
      case '0': $in_product_is_call = false; $out_product_is_call = true; break;
      case '1': $in_product_is_call = true; $out_product_is_call = false; break;
      default: $in_product_is_call = false; $out_product_is_call = true;
    }
// Products can be purchased with mixed attributes retail
    if (!isset($pInfo->products_quantity_mixed)) $pInfo->products_quantity_mixed = '0';
    switch ($pInfo->products_quantity_mixed) {
      case '0': $in_products_quantity_mixed = false; $out_products_quantity_mixed = true; break;
      case '1': $in_products_quantity_mixed = true; $out_products_quantity_mixed = false; break;
      default: $in_products_quantity_mixed = true; $out_products_quantity_mixed = false;
    }

// set image overwrite
  $on_overwrite = true;
  $off_overwrite = false;
?>
<link rel="stylesheet" type="text/css" href="includes/javascript/spiffyCal/spiffyCal_v2_1.css">
<script language="JavaScript" src="includes/javascript/spiffyCal/spiffyCal_v2_1.js"></script>
<script language="javascript"><!--
  var dateAvailable = new ctlSpiffyCalendarBox("dateAvailable", "new_product", "products_date_available","btnDate1","<?php echo $pInfo->products_date_available; ?>",scBTNMODE_CUSTOMBLUE);
//--></script>
<script language="javascript"><!--
var tax_rates = new Array();
<?php
    for ($i=0, $n=sizeof($tax_class_array); $i<$n; $i++) {
      if ($tax_class_array[$i]['id'] > 0) {
        echo 'tax_rates["' . $tax_class_array[$i]['id'] . '"] = ' . zen_get_tax_rate_value($tax_class_array[$i]['id']) . ';' . "\n";
      }
    }
?>

function doRound(x, places) {
  return Math.round(x * Math.pow(10, places)) / Math.pow(10, places);
}

function getTaxRate() {
  var selected_value = document.forms["new_product"].products_tax_class_id.selectedIndex;
  var parameterVal = document.forms["new_product"].products_tax_class_id[selected_value].value;

  if ( (parameterVal > 0) && (tax_rates[parameterVal] > 0) ) {
    return tax_rates[parameterVal];
  } else {
    return 0;
  }
}

function updateGross() {
  var taxRate = getTaxRate();
  var grossValue = document.forms["new_product"].products_price.value;

  if (taxRate > 0) {
    grossValue = grossValue * ((taxRate / 100) + 1);
  }

  document.forms["new_product"].products_price_gross.value = doRound(grossValue, 4);
}

function updateNet() {
  var taxRate = getTaxRate();
  var netValue = document.forms["new_product"].products_price_gross.value;

  if (taxRate > 0) {
    netValue = netValue / ((taxRate / 100) + 1);
  }

  document.forms["new_product"].products_price.value = doRound(netValue, 4);
}
//--></script>
    <?php
//  echo $type_admin_handler;
//  We set action to go back to this page here, and override it with the submit button (POST overrides GET in admin/product.php
echo zen_draw_form('new_product', $type_admin_handler , 'cPath=' . $cPath . (isset($_GET['product_type']) ? '&product_type=' . $_GET['product_type'] : '') . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '') . '&action=new_product' . (isset($_GET['page']) ? '&page=' . $_GET['page'] : ''), 'post', 'enctype="multipart/form-data"'); ?>

    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo sprintf(TEXT_NEW_PRODUCT, zen_output_generated_category_path($current_category_id)); ?></td>
            <td class="pageHeading" align="right"><?php echo zen_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main" align="right"><?php echo zen_draw_hidden_field('products_date_added', (zen_not_null($pInfo->products_date_added) ? $pInfo->products_date_added : date('Y-m-d'))) . zen_image_submit('button_preview.gif', IMAGE_PREVIEW, "name='action' value='new_product_preview'") . '&nbsp;&nbsp;<a href="' . zen_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '') . (isset($_GET['page']) ? '&page=' . $_GET['page'] : '')) . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
      </tr>
      <tr>
        <td><table border="0" cellspacing="0" cellpadding="2">
<?php
// show when product is linked
if (zen_get_product_is_linked($_GET['pID']) == 'true' and $_GET['pID'] > 0) {
?>
          <tr>
            <td class="main"><?php echo TEXT_MASTER_CATEGORIES_ID; ?></td>
            <td class="main">
              <?php
                // echo zen_draw_pull_down_menu('products_tax_class_id', $tax_class_array, $pInfo->products_tax_class_id);
                echo zen_image(DIR_WS_IMAGES . 'icon_yellow_on.gif', IMAGE_ICON_LINKED) . '&nbsp;&nbsp;';
                echo zen_draw_pull_down_menu('master_category', zen_get_master_categories_pulldown($_GET['pID']), $pInfo->master_categories_id); ?>
            </td>
          </tr>
<?php } else { ?>
          <tr>
            <td class="main"><?php echo TEXT_MASTER_CATEGORIES_ID; ?></td>
            <td class="main"><?php echo TEXT_INFO_ID . ($_GET['pID'] > 0 ? $pInfo->master_categories_id  . ' ' . zen_get_category_name($pInfo->master_categories_id, $_SESSION['languages_id']) : $current_category_id  . ' ' . zen_get_category_name($current_category_id, $_SESSION['languages_id'])); ?>
          </tr>
<?php } ?>
          <tr>
            <td colspan="2" class="main"><?php echo TEXT_INFO_MASTER_CATEGORIES_ID; ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '100%', '2'); ?></td>
          </tr>
<?php
// hidden fields not changeable on products page
echo zen_draw_hidden_field('master_categories_id', $pInfo->master_categories_id);
echo zen_draw_hidden_field('products_discount_type', $pInfo->products_discount_type);
echo zen_draw_hidden_field('products_discount_type_from', $pInfo->products_discount_type_from);
echo zen_draw_hidden_field('products_price_sorter', $pInfo->products_price_sorter);
?>
          <tr>
            <td colspan="2" class="main" align="center"><?php echo (zen_get_categories_status($current_category_id) == '0' ? TEXT_CATEGORIES_STATUS_INFO_OFF : '') . ($out_status == true ? ' ' . TEXT_PRODUCTS_STATUS_INFO_OFF : ''); ?></td>
          <tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_STATUS; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_radio_field('products_status', '1', $in_status) . '&nbsp;' . TEXT_PRODUCT_AVAILABLE . '&nbsp;' . zen_draw_radio_field('products_status', '0', $out_status) . '&nbsp;' . TEXT_PRODUCT_NOT_AVAILABLE; ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_DATE_AVAILABLE; ?><br /><small>(YYYY-MM-DD)</small></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;'; ?><script language="javascript">dateAvailable.writeControl(); dateAvailable.dateFormat="yyyy-MM-dd";</script></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_MANUFACTURER; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_pull_down_menu('manufacturers_id', $manufacturers_array, $pInfo->manufacturers_id); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
	   <td class="main" valign="top"><?php echo TEXT_PRODUCTS_WHOLESALERS; ?></td>
	   <td class="main">
	    <table border="0" cellspacing="0" cellpadding="0">
	     <tr>
	      <td><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;'; ?></td>
	      <td>
	       <table border="0" cellspacing="0" cellpadding="2">
		<?php
	         echo "<tr class='dataTableHeadingRow'>";
                 echo "<th class='dataTableHeadingContent'>" . TABLE_HEADING_WHOLESALERS_QUANTITY . "</th>";
		 echo "<th class='dataTableHeadingContent'>" . TABLE_HEADING_WHOLESALERS_PRICE . "</th>";
		 echo "<th class='dataTableHeadingContent'>" . TABLE_HEADING_WHOLESALERS_WHOLESALER . "</th>";
		 echo "<th class='dataTableHeadingContent' width='200px'>" . TABLE_HEADING_WHOLESALERS_ACTIONS . "</th>";
		 echo "</tr>";
		 foreach ($pInfo->products_wholesalers as $wholesaler) {
                  echo zen_draw_hidden_field("products_wholesaler__{$wholesaler['products_wholesalers_id']}__wholesaler", $wholesaler["wholesaler"]);
                  echo zen_draw_hidden_field("products_wholesaler__{$wholesaler['products_wholesalers_id']}__wholesalers_url", $wholesaler["wholesalers_url"]);
                  echo zen_draw_hidden_field("products_wholesaler__{$wholesaler['products_wholesalers_id']}__wholesalers_name", $wholesaler["wholesalers_name"]);
		  echo "<tr class='dataTableRow'>";
		  echo "<td class='dataTableContent'>" . zen_draw_input_field("products_wholesaler__{$wholesaler['products_wholesalers_id']}__amount", $wholesaler['amount']) . "</td>";
		  echo "<td class='dataTableContent'>" . zen_draw_input_field("products_wholesaler__{$wholesaler['products_wholesalers_id']}__price", $wholesaler['price']) . "</td>";
		  echo "<td class='dataTableContent'><a href='{$wholesaler['wholesalers_url']}' target='_blank'>{$wholesaler['wholesalers_name']}</a></td>";
		  echo "<td class='dataTableContent'>";
		  echo zen_image_submit('button_delete.gif', IMAGE_DELETE, "name='products_wholesaler__{$wholesaler['products_wholesalers_id']}__delete' value='1'");
		  $details = zen_href_link(FILENAME_WHOLESALER_DETAILS, "wholesaler={$wholesaler['wholesaler']}");
		  echo "<a href='{$details}' target='_blank'>" . zen_image_button('button_details.gif', IMAGE_DETAILS, "name='products_wholesaler__{$wholesaler['products_wholesalers_id']}__details' value='1'") . "</a>";
		  $order = zen_href_link(FILENAME_WHOLESALER_ORDER, '');
		  echo "<a href='{$order}' target='_blank'>" . zen_image_button('button_order.gif', IMAGE_ORDER, "name='products_wholesaler__{$wholesaler['products_wholesalers_id']}__order' value='1'") . "</a>";
		  echo "</td>";
		  echo "</tr>\n";
		 }
                 echo "<tr class='dataTableRow'>";
	         echo "<td class='dataTableContent'>" . zen_draw_input_field("products_new_wholesaler_amount", '1') . "</td>";
                 echo "<td class='dataTableContent'>" . zen_draw_input_field('products_new_wholesaler_price', '0.0000') . "</td>";
                 echo "<td class='dataTableContent'>" . zen_draw_pull_down_menu('products_new_wholesaler_wholesaler', $wholesalers_array) . "</td>";
                 echo "<td class='dataTableContent'>";
                 echo zen_image_submit('button_insert.gif', IMAGE_INSERT, "name='products_add_wholesaler' value='1'");
                 echo "</td>";
                 echo "</tr>\n";
 	        ?>
	       </table>
	      </td>
	     </tr>
            </table>        
           </td>
          </tr>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main"><?php if ($i == 0) echo TEXT_PRODUCTS_NAME; ?></td>
            <td class="main"><?php echo zen_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . zen_draw_input_field('products_name[' . $languages[$i]['id'] . ']', (isset($products_name[$languages[$i]['id']]) ? stripslashes($products_name[$languages[$i]['id']]) : zen_get_products_name($pInfo->products_id, $languages[$i]['id'])), zen_set_field_length(TABLE_PRODUCTS_DESCRIPTION, 'products_name')); ?></td>
          </tr>
<?php
    }
?>

          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>

          <tr>
            <td class="main"><?php echo TEXT_PRODUCT_IS_FREE; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_radio_field('product_is_free', '1', ($in_product_is_free==1)) . '&nbsp;' . TEXT_YES . '&nbsp;&nbsp;' . zen_draw_radio_field('product_is_free', '0', ($in_product_is_free==0)) . '&nbsp;' . TEXT_NO . ' ' . ($pInfo->product_is_free == 1 ? '<span class="errorText">' . TEXT_PRODUCTS_IS_FREE_EDIT . '</span>' : ''); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCT_IS_CALL; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_radio_field('product_is_call', '1', ($in_product_is_call==1)) . '&nbsp;' . TEXT_YES . '&nbsp;&nbsp;' . zen_draw_radio_field('product_is_call', '0', ($in_product_is_call==0)) . '&nbsp;' . TEXT_NO . ' ' . ($pInfo->product_is_call == 1 ? '<span class="errorText">' . TEXT_PRODUCTS_IS_CALL_EDIT . '</span>' : ''); ?></td>
          </tr>

          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_PRICED_BY_ATTRIBUTES; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_radio_field('products_priced_by_attribute', '1', $is_products_priced_by_attribute) . '&nbsp;' . TEXT_PRODUCT_IS_PRICED_BY_ATTRIBUTE . '&nbsp;&nbsp;' . zen_draw_radio_field('products_priced_by_attribute', '0', $not_products_priced_by_attribute) . '&nbsp;' . TEXT_PRODUCT_NOT_PRICED_BY_ATTRIBUTE . ' ' . ($pInfo->products_priced_by_attribute == 1 ? '<span class="errorText">' . TEXT_PRODUCTS_PRICED_BY_ATTRIBUTES_EDIT . '</span>' : ''); ?></td>
          </tr>

          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr bgcolor="#ebebff">
            <td class="main"><?php echo TEXT_PRODUCTS_TAX_CLASS; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_pull_down_menu('products_tax_class_id', $tax_class_array, $pInfo->products_tax_class_id, 'onchange="updateGross()"'); ?></td>
          </tr>
          <tr bgcolor="#ebebff">
            <td class="main"><?php echo TEXT_PRODUCTS_PRICE_NET; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_input_field('products_price', $pInfo->products_price, 'onKeyUp="updateGross()"'); ?></td>
          </tr>
          <tr bgcolor="#ebebff">
            <td class="main"><?php echo TEXT_PRODUCTS_PRICE_GROSS; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_input_field('products_price_gross', $pInfo->products_price, 'OnKeyUp="updateNet()"'); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_VIRTUAL; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_radio_field('products_virtual', '1', $is_virtual) . '&nbsp;' . TEXT_PRODUCT_IS_VIRTUAL . '&nbsp;' . zen_draw_radio_field('products_virtual', '0', $not_virtual) . '&nbsp;' . TEXT_PRODUCT_NOT_VIRTUAL . ' ' . ($pInfo->products_virtual == 1 ? '<br /><span class="errorText">' . TEXT_VIRTUAL_EDIT . '</span>' : ''); ?></td>
          </tr>

          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_PRODUCTS_IS_ALWAYS_FREE_SHIPPING; ?></td>
            <td class="main" valign="top"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_radio_field('product_is_always_free_shipping', '1', $is_product_is_always_free_shipping) . '&nbsp;' . TEXT_PRODUCT_IS_ALWAYS_FREE_SHIPPING . '&nbsp;' . zen_draw_radio_field('product_is_always_free_shipping', '0', $not_product_is_always_free_shipping) . '&nbsp;' . TEXT_PRODUCT_NOT_ALWAYS_FREE_SHIPPING  . '<br />' . zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_radio_field('product_is_always_free_shipping', '2', $special_product_is_always_free_shipping) . '&nbsp;' . TEXT_PRODUCT_SPECIAL_ALWAYS_FREE_SHIPPING . ' ' . ($pInfo->product_is_always_free_shipping == 1 ? '<br /><span class="errorText">' . TEXT_FREE_SHIPPING_EDIT . '</span>' : ''); ?></td>
          </tr>

          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_QTY_BOX_STATUS; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_radio_field('products_qty_box_status', '1', $is_products_qty_box_status) . '&nbsp;' . TEXT_PRODUCTS_QTY_BOX_STATUS_ON . '&nbsp;' . zen_draw_radio_field('products_qty_box_status', '0', $not_products_qty_box_status) . '&nbsp;' . TEXT_PRODUCTS_QTY_BOX_STATUS_OFF . ' ' . ($pInfo->products_qty_box_status == 0 ? '<br /><span class="errorText">' . TEXT_PRODUCTS_QTY_BOX_STATUS_EDIT . '</span>' : ''); ?></td>
          </tr>

          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>

          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_QUANTITY_MIN_RETAIL; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_input_field('products_quantity_order_min', ($pInfo->products_quantity_order_min == 0 ? 1 : $pInfo->products_quantity_order_min)); ?></td>
          </tr>

          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_QUANTITY_MAX_RETAIL; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_input_field('products_quantity_order_max', $pInfo->products_quantity_order_max); ?>&nbsp;&nbsp;<?php echo TEXT_PRODUCTS_QUANTITY_MAX_RETAIL_EDIT; ?></td>
          </tr>

          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_QUANTITY_UNITS_RETAIL; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_input_field('products_quantity_order_units', ($pInfo->products_quantity_order_units == 0 ? 1 : $pInfo->products_quantity_order_units)); ?></td>
          </tr>

          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_MIXED; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_radio_field('products_quantity_mixed', '1', $in_products_quantity_mixed) . '&nbsp;' . TEXT_YES . '&nbsp;&nbsp;' . zen_draw_radio_field('products_quantity_mixed', '0', $out_products_quantity_mixed) . '&nbsp;' . TEXT_NO; ?></td>
          </tr>

          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>

<script language="javascript"><!--
updateGross();
//--></script>
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main" valign="top"><?php if ($i == 0) echo TEXT_PRODUCTS_DESCRIPTION; ?></td>
            <td colspan="2"><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td class="main" width="25" valign="top"><?php echo zen_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']); ?>&nbsp;</td>
                <td class="main" width="100%">
        <?php if (is_null($_SESSION['html_editor_preference_status'])) echo TEXT_HTML_EDITOR_NOT_DEFINED; ?>
        <?php if ($_SESSION['html_editor_preference_status']=="FCKEDITOR") {
//          if ($_SESSION['html_editor_preference_status']=="FCKEDITOR") require(DIR_WS_INCLUDES.'fckeditor.php');
          $oFCKeditor = new FCKeditor ;
          $oFCKeditor->Value = (isset($products_description[$languages[$i]['id']])) ? stripslashes($products_description[$languages[$i]['id']]) : zen_get_products_description($pInfo->products_id, $languages[$i]['id']) ;
          $oFCKeditor->CreateFCKeditor( 'products_description[' . $languages[$i]['id'] . ']', '99%', '230' ) ;  //instanceName, width, height (px or %)
          } else { // using HTMLAREA or just raw "source"

          echo zen_draw_textarea_field('products_description[' . $languages[$i]['id'] . ']', 'soft', '100%', '20', (isset($products_description[$languages[$i]['id']])) ? stripslashes($products_description[$languages[$i]['id']]) : zen_get_products_description($pInfo->products_id, $languages[$i]['id'])); //,'id="'.'products_description' . $languages[$i]['id'] . '"');
          } ?>
        </td>
              </tr>
            </table></td>
          </tr>
<?php
    }
?>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_QUANTITY; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_input_field('products_quantity', $pInfo->products_quantity); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_MODEL; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_input_field('products_model', $pInfo->products_model, zen_set_field_length(TABLE_PRODUCTS, 'products_model')); ?></td>
          </tr>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_PRODUCTS_PARTS; ?></td>
            <td class="main">
             <table border="0" cellspacing="0" cellpadding="0">
              <tr>
	       <td><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;'; ?></td>
	       <td>
		<table border="0" cellspacing="0" cellpadding="2">
		 <?php
	         echo "<tr class='dataTableHeadingRow'>";
		 echo "<th class='dataTableHeadingContent'>" . TABLE_HEADING_QUANTITY . "</th>";
		 echo "<th class='dataTableHeadingContent'>" . TABLE_HEADING_MODEL . "</th>";
		 echo "<th class='dataTableHeadingContent'>" . TABLE_HEADING_NAME . "</th>";
		 echo "<th class='dataTableHeadingContent'>" . TABLE_HEADING_VISIBLE. "</th>";
		 echo "<th class='dataTableHeadingContent'>" . TABLE_HEADING_ACTIONS ."</th>";
		 echo "</tr>";
		 foreach ($pInfo->products_parts as $part) {
                  echo zen_draw_hidden_field("products_part__{$part['products_id']}__products_model", $part["products_model"]);
                  echo zen_draw_hidden_field("products_part__{$part['products_id']}__products_name", $part["products_name"]);
		  echo "<tr class='dataTableRow'>";
		  echo "<td class='dataTableContent'>" . zen_draw_input_field("products_part__{$part['products_id']}__amount", $part['amount']) . "</td>";
		  echo "<td class='dataTableContent'>{$part['products_model']}</td><td class='dataTableContent'>{$part['products_name']}</td>";
		  echo "<td class='dataTableContent'>" . zen_draw_checkbox_field("products_part__{$part['products_id']}__visible", 'on', $part['visible']) . "</td>";
		  echo "<td class='dataTableContent'>" . zen_image_submit('button_delete.gif', IMAGE_DELETE, "name='products_part__{$part['products_id']}__delete' value='1'") . "</td>";
		  echo "</tr>";
		 }
                 echo "<tr class='dataTableRow'>";
	         echo "<td class='dataTableContent'>" . zen_draw_input_field("products_new_part_amount", '1') . "</td>";
                 echo "<td class='dataTableContent'>" . zen_draw_input_field('products_new_part_model', '') . "</td>";
                 echo "<td class='dataTableContent'></td>";
                 echo "<td class='dataTableContent'>" . zen_draw_checkbox_field("products_new_part_visible", 'on', 1) . "</td>";
                 echo "<td class='dataTableContent'>";
                 echo zen_image_submit('button_insert.gif', IMAGE_INSERT, "name='products_add_part' value='1'");
                 echo zen_image_submit('button_search.gif', IMAGE_SEARCH, "name='products_search_part' value='1'");
                 echo "</td>";
                 echo "</tr>";
 	        ?>
               </table>
	      </td>
	     </tr>
            </table>        
           </td>
          </tr>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
<?php
  $dir = @dir(DIR_FS_CATALOG_IMAGES);
  $dir_info[] = array('id' => '', 'text' => "Main Directory");
  while ($file = $dir->read()) {
    if (is_dir(DIR_FS_CATALOG_IMAGES . $file) && strtoupper($file) != 'CVS' && $file != "." && $file != "..") {
      $dir_info[] = array('id' => $file . '/', 'text' => $file);
    }
  }

  $default_directory = substr( $pInfo->products_image, 0,strpos( $pInfo->products_image, '/')+1);
?>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_IMAGE; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_file_field('products_image') . '<br />' . zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . $pInfo->products_image . zen_draw_hidden_field('products_previous_image', $pInfo->products_image); ?></td>
            <td valign = "center" class="main"><?php echo TEXT_PRODUCTS_IMAGE_DIR; ?>&nbsp;<?php echo zen_draw_pull_down_menu('img_dir', $dir_info, $default_directory); ?></td>
            <td class="main" valign="top"><?php echo TEXT_IMAGES_OVERWRITE . '<br />' . zen_draw_radio_field('overwrite', '0', $off_overwrite) . '&nbsp;' . TABLE_HEADING_NO . ' ' . zen_draw_radio_field('overwrite', '1', $on_overwrite) . '&nbsp;' . TABLE_HEADING_YES; ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
<?php
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
?>
          <tr>
            <td class="main"><?php if ($i == 0) echo TEXT_PRODUCTS_URL . '<br /><small>' . TEXT_PRODUCTS_URL_WITHOUT_HTTP . '</small>'; ?></td>
            <td class="main"><?php echo zen_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . zen_draw_input_field('products_url[' . $languages[$i]['id'] . ']', (isset($products_url[$languages[$i]['id']]) ? $products_url[$languages[$i]['id']] : zen_get_products_url($pInfo->products_id, $languages[$i]['id'])), zen_set_field_length(TABLE_PRODUCTS_DESCRIPTION, 'products_url')); ?></td>
          </tr>
<?php
    }
?>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_WEIGHT; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_input_field('products_weight', $pInfo->products_weight); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo TEXT_PRODUCTS_SORT_ORDER; ?></td>
            <td class="main"><?php echo zen_draw_separator('pixel_trans.gif', '24', '15') . '&nbsp;' . zen_draw_input_field('products_sort_order', $pInfo->products_sort_order); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main" align="right"><?php echo zen_draw_hidden_field('products_date_added', (zen_not_null($pInfo->products_date_added) ? $pInfo->products_date_added : date('Y-m-d'))) . zen_image_submit('button_preview.gif', IMAGE_PREVIEW, "name='action' value='new_product_preview'") . '&nbsp;&nbsp;<a href="' . zen_href_link(FILENAME_CATEGORIES, 'cPath=' . $cPath . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '') . (isset($_GET['page']) ? '&page=' . $_GET['page'] : '')) . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
      </tr>
    </table></form>
