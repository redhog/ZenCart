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

$imported_products = 0;
$imported_product_pictures = 0;
$imported_categories = 0;
$imported_category_pictures = 0;

function getCategoryByPathStr($parent, $path, $create) {
  if ($path == '')
    return $parent;
  return getCategoryByPath($parent, explode("/", $path), $create);
}

function getCategoryByPath($parent, $path, $create) {
  global $db, $messageStack;
  foreach ($path as $item) {
    $line = $db->Execute("select categories_description.categories_id as categories_id " .
                         "from " . TABLE_CATEGORIES . " as categories, " . TABLE_CATEGORIES_DESCRIPTION . " as categories_description " .
                         "where categories_description.categories_name = '{$item}' " .
                         "and categories.categories_id = categories_description.categories_id " .
                         "and categories.parent_id = '{$parent}'");
    if ($line->RecordCount() > 0) {
      if ($line->RecordCount() > 1)
	$messageStack->add("Category name '{$item}' is ambigous", 'warning');
      $parent = $line->fields['categories_id'];
    } else
      if ($create) {
	zen_db_perform(TABLE_CATEGORIES, array('parent_id' => $parent));
	$parent = zen_db_insert_id();
	zen_db_perform(TABLE_CATEGORIES_DESCRIPTION, array("categories_id" => $parent, 'language_id' => $_SESSION['languages_id'], 'categories_name' => $item));
      } else {
        $messageStack->add("Unable to find the category named '{$item}'", 'error');
        return -1;
      }
  }
  return $parent;
}

function getProductByModel($model, $create, $inCurrentCategory) {
  global $db, $messageStack;
  $line = $db->Execute("select products_id " .
                       "from " . TABLE_PRODUCTS . " " .
                       "where products_model = '{$model}'");
  if ($line->RecordCount() > 0) {
    if ($line->RecordCount() > 1)
      $messageStack->add("Product model '{$item}' is ambigous", 'warning');
    return $line->fields['products_id'];
  } else {
    if ($create) {
      $products_data = array('products_model' => $model);
      if ($inCurrentCategory) $products_data['master_categories_id'] = $current_category_id;
      zen_db_perform(TABLE_PRODUCTS, $products_data);
      $products_id = zen_db_insert_id();
      zen_db_perform(TABLE_PRODUCTS_DESCRIPTION, array('products_id' => $products_id,
                                                       'language_id' => $_SESSION['languages_id'],
						       'products_date_added' => 'now()',
						       'products_last_modified' => 'now()'));
      if ($inCurrentCategory)
       zen_db_perform(TABLE_PRODUCTS_TO_CATEGORIES, array('products_id' => $products_id, 'categories_id' => $current_category_id));
      return $products_id;
    } else {
      $messageStack->add("Unable to find the product with model '{$model}'", 'error');
      return -1;
   }
  }
}


function getWholesalerByName($name, $create) {
  global $db, $messageStack;
  $line = $db->Execute("select wholesalers_id " .
                       "from " . TABLE_WHOLESALERS . " " .
                       "where wholesalers_name = '{$name}'");
  if ($line->RecordCount() > 0) {
    if ($line->RecordCount() > 1)
      $messageStack->add("Wholesaler name '{$item}' is ambigous", 'warning');
    return $line->fields['wholesalers_id'];
  } else {
    if ($create) {
      zen_db_perform(TABLE_WHOLESALERS, array('wholesalers_name' => $name));
      $wholesalers_id = zen_db_insert_id();
      zen_db_perform(TABLE_WHOLESALERS_INFO, array('wholesalers_id' => $wholesalers_id, 'languages_id' => $_SESSION['languages_id']));
      return $wholesalers_id;
    } else {
      $messageStack->add("Unable to find the wholesaler with name '{$name}'", 'error');
      return -1;
   }
  }
}

function appendPath($path1, $path2) {
  if ($path1 and $path2)
    return $path1 . '/' . $path2;
  if ($path1)
    return $path1;
  return $path2;
}

function addCategoryPictures($root, $path) {
 global $db, $messageStack, $imported_category_pictures, $current_category_id;
  if ($dir = opendir(appendPath($root, $path))) {
    while (($sub = readdir($dir)) !== false) {
      if ($sub == '.' || $sub == '..') continue;
      $subpath = appendPath($path, $sub);
      if (is_dir(appendPath($root, $subpath))) {
	addCategoryPictures($root, $subpath);
      } else {
	mkdir(appendPath(DIR_FS_CATALOG_IMAGES, $path), 0777, true);
	rename(appendPath($root, $subpath), appendPath(DIR_FS_CATALOG_IMAGES, $subpath));
	$categorypath = substr($subpath, 0, strrpos($subpath, '.'));
	$category = getCategoryByPathStr($current_category_id, $categorypath, 1);
	zen_db_perform(TABLE_CATEGORIES, array('categories_image' => $subpath), 'update', "categories_id='{$category}'");
	$imported_category_pictures += 1;
      }
    }
    closedir($dir);
  }
}

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

  $process = proc_open('rm -rf ' . DIR_FS_IMPORT . '*', $null_descriptorspec, $pipes);
  if (!is_resource($process)) {
   $messageStack->add("Unable to run the rm program", 'error');
   break;
  }
  if (($result = proc_close($process)) !== 0) {
   $messageStack->add("Unable to run the rm program: " . $result, 'error');
   break;
  }


  $process = proc_open('cd ' . DIR_FS_IMPORT . '; unzip ' . $products->file['tmp_name'], $null_descriptorspec, $pipes);
  if (!is_resource($process)) {
   $messageStack->add("Unable to run the unzip program", 'error');
   break;
  }
  if (($result = proc_close($process)) !== 0) {
   $messageStack->add("Unable to run the unzip program: " . $result, 'error');
   break;
  }

  unlink($products->file['tmp_name']);  

  if ($file = fopen(appendPath(DIR_FS_IMPORT, 'categories.csv'), 'r')) {
    $keys = fgetcsv($file, 65536);

    $categories_description_map = array(
      'categories_description' => 0,
     );

    while (($data = fgetcsv($file, 65536)) !== FALSE) {
      $data = array_combine($keys, array_map(zen_db_prepare_input, $data));
      $categories_id = getCategoryByPathStr($current_category_id, $data['path'], 1);
      $categories_description_data = array_intersect_key($data, $categories_description_map);

      zen_db_perform(TABLE_CATEGORIES_DESCRIPTION, $categories_description_data, 'update', "categories_id='{$categories_id}' and language_id={$_SESSION['languages_id']}");
      $imported_categories += 1;
    }
  }

  addCategoryPictures(appendPath(DIR_FS_IMPORT, 'categories'), '');

  if ($file = fopen(appendPath(DIR_FS_IMPORT, 'products.csv'), 'r')) {
   $keys = fgetcsv($file, 65536);

   $products_map = array(
     'products_quantity' => 0,
     'products_price' => 0,
     'products_virtual' => 0,
     'products_date_added' => 0,
     'products_last_modified' => 0,
     'products_date_available' => 0,
     'products_weight' => 0,
     'products_status' => 0,
     'products_quantity_order_min' => 0,
     'products_quantity_order_units' => 0,
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
     'metatags_title_tagline_status' => 0
    );
   $products_description_map = array('products_name' => 0,
			                     'products_description' => 0,
                                             'products_url' => 0
					     );
   while (($data = fgetcsv($file, 65536)) !== FALSE) {
    $data = array_combine($keys, array_map(zen_db_prepare_input, $data));

    $products_id = getProductByModel($data['products_model'], 1, !isset($data['categories']));
    $products_data = array_intersect_key($data, $products_map);

    if (isset($data['categories'])) {
      $categories = array();
      $category_paths = explode(',', $data['categories']);
      foreach($category_paths as $category_path) {
        $categories[] = getCategoryByPathStr($current_category_id, $category_path, 1);
      }
      $products_data['master_categories_id'] = $categories[0];
      foreach ($categories as $category) {
        $line = $db->Execute("select products_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id = '{$products_id}' and categories_id = '{$category}'");
        if ($line->RecordCount() == 0)
 	  zen_db_perform(TABLE_PRODUCTS_TO_CATEGORIES, array('products_id' => $products_id, 'categories_id' => $category));
      }
    }

    zen_db_perform(TABLE_PRODUCTS, $products_data, 'update', "products_id='{$products_id}'");
    zen_update_products_price_sorter($products_id);

    if (isset($data['parts']) && $data['parts'] != '') {
      $part_exprs = explode(',', $data['parts']);
      foreach($part_exprs as $part_expr) {
        $part_items = explode("|", $part_expr);
        $part_model = $part_items[0];
        $line = $db->Execute("select products_id from " . TABLE_PRODUCTS . " as products where products_model = '{$part_model}'");
        if ($line->RecordCount() > 0) {
          if ($line->RecordCount() > 1)
	    $messageStack->add("Part part number '{$part_model}' is ambigous", 'warning');
	  zen_db_perform(TABLE_PRODUCTS_PARTS, array('product' => $products_id,
						     'product_part' => $line->fields['products_id'],
						     'amount' => isset($part_items[1]) ? $part_items[1] : 1,
						     'visible' => isset($part_items[2]) ? $part_items[2] : 1));
        } else
	  $messageStack->add("Unable to find the part with part number '{$part_model}'", 'error');
      }
    }

    if (isset($data['wholesaler']) && isset($data['wholesaler_price'])) {
     zen_db_perform(TABLE_PRODUCTS_WHOLESALERS, array('product' => $products_id,
						      'wholesaler' => getWholesalerByName($data['wholesaler'], 1),
						      'model' => isset($data['wholesaler_model']) ? $data['wholesaler_model'] : $data['products_model'],
						      'amount' => isset($data['wholesaler_amount']) ? $data['wholesaler_amount'] : 1,
						      'price' => $data['wholesaler_price']));
    }

    $products_description_data = array_intersect_key($data, $products_description_map);
    if ($products_description_data) {
     zen_db_perform(TABLE_PRODUCTS_DESCRIPTION, $products_description_data, 'update', "products_id='{$products_id}' and language_id={$_SESSION['languages_id']}");
     $products_description_id = zen_db_insert_id();
    }
    $imported_products += 1;
   }
   fclose($file);
  }

  if ($dir = opendir(appendPath(DIR_FS_IMPORT, 'products'))) {
   while (($file = readdir($dir)) !== false) {
    if ($file == '.' || $file == '..') continue;
    $model = substr($file, 0, strrpos($file, '.'));
    rename(appendPath(appendPath(DIR_FS_IMPORT, 'products'), $file), appendPath(DIR_FS_CATALOG_IMAGES, $file));
    zen_db_perform(TABLE_PRODUCTS, array('products_image' => $file), 'update', 'products_id = "' . getProductByModel($model, 1, 1) . '"');
    $imported_product_pictures += 1;
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
          <li>Imported products: <?php echo $imported_products; ?></li>
          <li>Imported product pictures: <?php echo $imported_product_pictures; ?></li>
          <li>Imported categories: <?php echo $imported_categories; ?></li>
          <li>Imported category pictures: <?php echo $imported_category_pictures; ?></li>
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

          <tr><form name = "import" action="<?php echo zen_href_link(FILENAME_IMPORT, "action=import_products&cPath={$_GET['cPath']}", 'NONSSL'); ?>"' method="post" enctype="multipart/form-data">
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
