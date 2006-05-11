<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006 Egil Möller <redhog@redhog.org>                   |
// |                                                                      |
// | http://redhog.org                                                    |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+

require('includes/application_top.php');
require('includes/functions/compatibility.php');

$action = (isset($_GET['action']) ? $_GET['action'] : '');

$null_descriptorspec = array(0 => array("file", "/dev/zero", "r"), 1 => array("file", "/dev/null", "a"), 2 => array("file", "/dev/null", "a"));

if (!is_writeable(DIR_FS_IMPORT)) {
 $messageStack->add("Unable to write to " . DIR_FS_IMPORT, 'error');
}

$import_result = array();

switch($action) {
 case ('import_products'):

  if (!($products = new upload('import_products_products'))) {
   $messageStack->add("Unable to create upload object", 'error');
   break;
  }
  $products->set_destination(DIR_FS_IMPORT);
  if (!$products->parse()) {
   $messageStack->add("Unable to parse upload", 'error');
   break;
  }
  if (!is_uploaded_file($products->file['tmp_name'])) {
   $messageStack->add("Possible filename cracking attack on '" . $products->file['tmp_name'] ."'. Go away", 'error');
   break;
  }

  $process = proc_open('rm -rf ' . DIR_FS_IMPORT . '*', $null_descriptorspec, $pipes, DIR_FS_IMPORT);
  if (!is_resource($process)) {
   $messageStack->add("Unable to run the rm program", 'error');
   break;
  }
  if (($result = proc_close($process)) !== 0) {
   $messageStack->add("Unable to run the rm program: " . $result, 'error');
   break;
  }


  $process = proc_open('unzip ' . $products->file['tmp_name'], $null_descriptorspec, $pipes, DIR_FS_IMPORT);
  if (!is_resource($process)) {
   $messageStack->add("Unable to run the unzip program", 'error');
   break;
  }
  if (($result = proc_close($process)) !== 0) {
   $messageStack->add("Unable to run the unzip program: " . $result, 'error');
   break;
  }

  unlink($products->file['tmp_name']);  

  if ($file = fopen(DIR_FS_IMPORT . 'products.csv', 'r')) {
   $import_result[] = 'Impored products from products.csv';
   $keys = fgetcsv($file);

   $products_default_map = array(
     'products_quantity' => 0,
     'products_model' => False,
     'products_price' => 0,
     'products_virtual' => 0,
     'products_date_added' => 'now()',
     'products_last_modified' => 'now()',
     'products_date_available' => False,
     'products_weight' => False,
     'products_status' => 0,
     'products_quantity_order_min' => 1,
     'products_quantity_order_units' => 1,
     'product_is_free' => 0,
     'product_is_call' => 0,
     'products_quantity_mixed' => 0,
     'product_is_always_free_shipping' => 0,
     'products_qty_box_status' => 0,
     'products_quantity_order_max' => 0,
     'products_sort_order' => 0,
     'products_price_sorter' => 0,
     'products_mixed_discount_quantity' => 0,
     'metatags_title_status' => 0,
     'metatags_products_name_status' => 0,
     'metatags_model_status' => 0,
     'metatags_price_status' => 0,
     'metatags_title_tagline_status' => 0,
    );
   $products_default_keys = array_keys($products_default_map);
   $products_description_default_map = array('products_name' => false,
			                     'products_description' => false,
                                             'products_url' => false
					     );
   $products_description_default_keys = array_keys($products_description_default_map);
   while (($data = fgetcsv($file)) !== FALSE) {
    $data = array_combine($keys, array_map(zen_db_prepare_input, $data));
    $products_data = array_merge($products_default_map, array_intersect_key($data, $products_default_map));

    $categories = array();
    if (isset($data['categories'])) {
      $category_names = explode(', ', $data['categories']);
      foreach($category_names as $category_name) {
        $line = $db->Execute("select categories_id from categories_description where categories_name = '{$category_name}'");
        if ($line->RecordCount() > 0) {
          if ($line->RecordCount() > 1)
	    $messageStack->add("Category name '{$category_name}' is ambigous", 'warning');
	  $categories[] = $line->fields['categories_id'];
        } else
	  $messageStack->add("Unable to find the category named '{$category_name}'", 'error');
      }
    } else {
      $categories = array($current_category_id);
    }
    $products_data['master_categories_id'] = $categories[0];
    
    zen_db_perform(TABLE_PRODUCTS, $products_data);
    $products_id = zen_db_insert_id();
    zen_update_products_price_sorter($products_id);

    foreach ($categories as $category) {
      zen_db_perform(TABLE_PRODUCTS_TO_CATEGORIES, array('products_id' => $products_id, 'categories_id' => $category));
    }

    $products_description_data = array_intersect_key($data, $products_description_default_map);
    if ($products_description_data) {
     $products_description_data = array_merge($products_description_default_map, $products_description_data);
     $products_description_data['products_id'] = $products_id;
     zen_db_perform(TABLE_PRODUCTS_DESCRIPTION, $products_description_data);
     $products_description_id = zen_db_insert_id();
    }
    $import_result[] = "Added product '" . $products_data['products_model'] . "' #" . $products_id;
   }
   fclose($file);
  }

  if ($dir = opendir(DIR_FS_IMPORT . 'pictures/')) {
   $import_result[] = 'Importing pictures from pictures/';
   while (($file = readdir($dir)) !== false) {
    if ($file == '.' || $file == '..') continue;
    $model = substr($file, 0, strrpos($file, '.'));
    rename(DIR_FS_IMPORT . 'pictures/' . $file, DIR_FS_CATALOG_IMAGES . $file);
    zen_db_perform(TABLE_PRODUCTS, array('products_image' => $file), 'update', 'products_model = "' . $model . '"');
    $import_result[] = "Added picture '" . $file . "' fo '" . $model . "'.";
   }
   closedir($dir);
  }

  break;
}

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>

<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  // -->
</script>
</head>
<body onload="init()">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
        <td class="pageHeading" align="right"><?php echo zen_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
      </tr>
      <tr>
        <td colspan="2"><?php echo HEADING_HELP; ?></td>
      </tr>

<?php if ($action == 'import_products') { ?>
      <tr>
        <td colspan="2">
         <?php echo TEXT_IMPORT_PRODUCT_RESULTS; ?><br />
         <ul>
          <?php
            foreach ($import_result as $resultline) {
              echo "<li>{$resultline}</li>";
            }
          ?>
         </ul>
        </td>
      </tr>
<?php } ?>

<!-- bof: Import -->
      <tr>
        <td colspan="2"><br /><table border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td colspan="3" class="main" align="left" valign="middle"><?php echo TEXT_IMPORT_PRODUCTS; ?></td>
          </tr>

          <tr><form name = "locate_configure" action="<?php echo zen_href_link(FILENAME_IMPORT, "action=import_products&cPath={$_GET['cPath']}", 'NONSSL'); ?>"' method="post" enctype="multipart/form-data">
            <td class="main" align="left" valign="bottom"><?php echo '<strong>' . TEXT_IMPORT_PRODUCTS_FIELD . '</strong>' . '<br />' . zen_draw_file_field('import_products_products'); ?></td>
            <td class="main" align="right" valign="bottom"><?php echo zen_image_submit('button_import.gif', IMAGE_IMPORT); ?></td>
            <td class="main" align="right" valign="bottom"><?php echo "<a href='" . zen_href_link(FILENAME_CATEGORIES, "cPath={$cPath}") . "'>" . zen_image_button('button_back.gif', IMAGE_BACK) . "</a>"; ?></td>
          </form></tr>
        </table></td>
      </tr>
<!-- eof: Import -->

      <tr>
        <td colspan="2"><?php echo '<br />' . zen_draw_separator('pixel_black.gif', '100%', '2'); ?></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
