<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Based on manufacturers.php                                           |
// | Copyright (c) 2003 The zen-cart developers                           |
// | Portions Copyright (c) 2003 osCommerce                               |
// | Derivation and changes                                               |
// | Copyright (c) 2006 RedHog (Egil Möller) <redhog@redhog.org>          |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
//

  require('includes/application_top.php');

  $action = (isset($_GET['action']) ? $_GET['action'] : '');

  if (zen_not_null($action)) {
    switch ($action) {
      case 'insert':
      case 'save':
        if (isset($_GET['mID'])) $wholesalers_id = zen_db_prepare_input($_GET['mID']);
        $wholesalers_name = zen_db_prepare_input($_POST['wholesalers_name']);

        $sql_data_array = array('wholesalers_name' => $wholesalers_name);

        if ($action == 'insert') {
          $insert_sql_data = array('date_added' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

          zen_db_perform(TABLE_WHOLESALERS, $sql_data_array);
          $wholesalers_id = zen_db_insert_id();
        } elseif ($action == 'save') {
          $update_sql_data = array('last_modified' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $update_sql_data);

          zen_db_perform(TABLE_WHOLESALERS, $sql_data_array, 'update', "wholesalers_id = '" . (int)$wholesalers_id . "'");
        }

        $wholesalers_image = new upload('wholesalers_image');
        $wholesalers_image->set_destination(DIR_FS_CATALOG_IMAGES . $_POST['img_dir']);
        if ( $wholesalers_image->parse() &&  $wholesalers_image->save()) {
          // remove image from database if none
          if ($wholesalers_image->filename != 'none') {
            $db->Execute("update " . TABLE_WHOLESALERS . "
                          set wholesalers_image = '" .  $_POST['img_dir'] . $wholesalers_image->filename . "'
                          where wholesalers_id = '" . (int)$wholesalers_id . "'");
          } else {
            $db->Execute("update " . TABLE_WHOLESALERS . "
                          set wholesalers_image = ''
                          where wholesalers_id = '" . (int)$wholesalers_id . "'");
          }
        }

        $languages = zen_get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $wholesalers_url_array = $_POST['wholesalers_url'];
          $language_id = $languages[$i]['id'];

          $sql_data_array = array('wholesalers_url' => zen_db_prepare_input($wholesalers_url_array[$language_id]));

          if ($action == 'insert') {
            $insert_sql_data = array('wholesalers_id' => $wholesalers_id,
                                     'languages_id' => $language_id);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            zen_db_perform(TABLE_WHOLESALERS_INFO, $sql_data_array);
          } elseif ($action == 'save') {
            zen_db_perform(TABLE_WHOLESALERS_INFO, $sql_data_array, 'update', "wholesalers_id = '" . (int)$wholesalers_id . "' and languages_id = '" . (int)$language_id . "'");
          }
        }

        zen_redirect(zen_href_link(FILENAME_WHOLESALERS, (isset($_GET['page']) ? 'page=' . $_GET['page'] . '&' : '') . 'mID=' . $wholesalers_id));
        break;
      case 'deleteconfirm':
        // demo active test
        if (zen_admin_demo()) {
          $_GET['action']= '';
          $messageStack->add_session(ERROR_ADMIN_DEMO, 'caution');
          zen_redirect(zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page']));
        }
        $wholesalers_id = zen_db_prepare_input($_GET['mID']);

        if (isset($_POST['delete_image']) && ($_POST['delete_image'] == 'on')) {
          $wholesaler = $db->Execute("select wholesalers_image
                                        from " . TABLE_WHOLESALERS . "
                                        where wholesalers_id = '" . (int)$wholesalers_id . "'");

          $image_location = DIR_FS_CATALOG_IMAGES . $wholesaler->fields['wholesalers_image'];

          if (file_exists($image_location)) @unlink($image_location);
        }

        $db->Execute("delete from " . TABLE_WHOLESALERS . "
                      where wholesalers_id = '" . (int)$wholesalers_id . "'");
        $db->Execute("delete from " . TABLE_WHOLESALERS_INFO . "
                      where wholesalers_id = '" . (int)$wholesalers_id . "'");
	$db->Execute("delete from " . TABLE_PRODUCTS_WHOLESALERS . "
                      where wholesaler = '" . (int)$wholesalers_id . "'");
        zen_redirect(zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page']));
        break;
    }
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
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo zen_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_WHOLESALERS; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
  $wholesalers_query_raw = "select wholesalers_id, wholesalers_name, wholesalers_image, date_added, last_modified from " . TABLE_WHOLESALERS . " order by wholesalers_name";
  $wholesalers_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $wholesalers_query_raw, $wholesalers_query_numrows);
  $wholesalers = $db->Execute($wholesalers_query_raw);
  while (!$wholesalers->EOF) {
    if ((!isset($_GET['mID']) || (isset($_GET['mID']) && ($_GET['mID'] == $wholesalers->fields['wholesalers_id']))) && !isset($mInfo) && (substr($action, 0, 3) != 'new')) {
      $mInfo = new objectInfo($wholesalers->fields);
    }

    if (isset($mInfo) && is_object($mInfo) && ($wholesalers->fields['wholesalers_id'] == $mInfo->wholesalers_id)) {
      echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $wholesalers->fields['wholesalers_id'] . '&action=edit') . '\'">' . "\n";
    } else {
      echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $wholesalers->fields['wholesalers_id'] . '&action=edit') . '\'">' . "\n";
    }
?>
                <td class="dataTableContent"><?php echo $wholesalers->fields['wholesalers_name']; ?></td>
                <td class="dataTableContent" align="right">
                  <?php echo '<a href="' . zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $wholesalers->fields['wholesalers_id'] . '&action=edit') . '">' . zen_image(DIR_WS_IMAGES . 'icon_edit.gif', ICON_EDIT) . '</a>'; ?>
                  <?php echo '<a href="' . zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $wholesalers->fields['wholesalers_id'] . '&action=delete') . '">' . zen_image(DIR_WS_IMAGES . 'icon_delete.gif', ICON_DELETE) . '</a>'; ?>
                  <?php if (isset($mInfo) && is_object($mInfo) && ($wholesalers->fields['wholesalers_id'] == $mInfo->wholesalers_id)) { echo zen_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . zen_href_link(FILENAME_WHOLESALERS, zen_get_all_get_params(array('mID')) . 'mID=' . $wholesalers->fields['wholesalers_id']) . '">' . zen_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>
                </td>
              </tr>
<?php
    $wholesalers->MoveNext();
  }
?>
              <tr>
                <td colspan="2"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText" valign="top"><?php echo $wholesalers_split->display_count($wholesalers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_WHOLESALERS); ?></td>
                    <td class="smallText" align="right"><?php echo $wholesalers_split->display_links($wholesalers_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
                  </tr>
                </table></td>
              </tr>
<?php
  if (empty($action)) {
?>
              <tr>
                <td align="right" colspan="2" class="smallText"><?php echo '<a href="' . zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $mInfo->wholesalers_id . '&action=new') . '">' . zen_image_button('button_insert.gif', IMAGE_INSERT) . '</a>'; ?></td>
              </tr>
<?php
  }
?>
            </table></td>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'new':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_NEW_WHOLESALER . '</b>');

      $contents = array('form' => zen_draw_form('wholesalers', FILENAME_WHOLESALERS, 'action=insert', 'post', 'enctype="multipart/form-data"'));
      $contents[] = array('text' => TEXT_NEW_INTRO);
      $contents[] = array('text' => '<br>' . TEXT_WHOLESALERS_NAME . '<br>' . zen_draw_input_field('wholesalers_name', '', zen_set_field_length(TABLE_WHOLESALERS, 'wholesalers_name')));
      $contents[] = array('text' => '<br>' . TEXT_WHOLESALERS_IMAGE . '<br>' . zen_draw_file_field('wholesalers_image'));
      $dir = @dir(DIR_FS_CATALOG_IMAGES);
      $dir_info[] = array('id' => '', 'text' => "Main Directory");
      while ($file = $dir->read()) {
        if (is_dir(DIR_FS_CATALOG_IMAGES . $file) && strtoupper($file) != 'CVS' && $file != "." && $file != "..") {
          $dir_info[] = array('id' => $file . '/', 'text' => $file);
        }
      }

      $default_directory = 'wholesalers/';

      $contents[] = array('text' => '<BR />' . TEXT_PRODUCTS_IMAGE_DIR . zen_draw_pull_down_menu('img_dir', $dir_info, $default_directory));

      $wholesaler_inputs_string = '';
      $languages = zen_get_languages();
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        $wholesaler_inputs_string .= '<br>' . zen_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . zen_draw_input_field('wholesalers_url[' . $languages[$i]['id'] . ']', '', zen_set_field_length(TABLE_WHOLESALERS_INFO, 'wholesalers_url') );
      }

      $contents[] = array('text' => '<br>' . TEXT_WHOLESALERS_URL . $wholesaler_inputs_string);
      $contents[] = array('align' => 'center', 'text' => '<br>' . zen_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $_GET['mID']) . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    case 'edit':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_EDIT_WHOLESALER . '</b>');

      $contents = array('form' => zen_draw_form('wholesalers', FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $mInfo->wholesalers_id . '&action=save', 'post', 'enctype="multipart/form-data"'));
      $contents[] = array('text' => TEXT_EDIT_INTRO);
      $contents[] = array('text' => '<br />' . TEXT_WHOLESALERS_NAME . '<br>' . zen_draw_input_field('wholesalers_name', $mInfo->wholesalers_name, zen_set_field_length(TABLE_WHOLESALERS, 'wholesalers_name')));
      $contents[] = array('text' => '<br />' . TEXT_WHOLESALERS_IMAGE . '<br>' . zen_draw_file_field('wholesalers_image') . '<br />' . $mInfo->wholesalers_image);
      $dir = @dir(DIR_FS_CATALOG_IMAGES);
      $dir_info[] = array('id' => '', 'text' => "Main Directory");
      while ($file = $dir->read()) {
        if (is_dir(DIR_FS_CATALOG_IMAGES . $file) && strtoupper($file) != 'CVS' && $file != "." && $file != "..") {
          $dir_info[] = array('id' => $file . '/', 'text' => $file);
        }
      }
      $default_directory = substr( $mInfo->wholesalers_image, 0,strpos( $mInfo->wholesalers_image, '/')+1);
      $contents[] = array('text' => '<BR />' . TEXT_PRODUCTS_IMAGE_DIR . zen_draw_pull_down_menu('img_dir', $dir_info, $default_directory));
      $contents[] = array('text' => '<br />' . zen_info_image($mInfo->wholesalers_image, $mInfo->wholesalers_name));
      $wholesaler_inputs_string = '';
      $languages = zen_get_languages();
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        $wholesaler_inputs_string .= '<br>' . zen_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . zen_draw_input_field('wholesalers_url[' . $languages[$i]['id'] . ']', zen_get_wholesaler_url($mInfo->wholesalers_id, $languages[$i]['id']), zen_set_field_length(TABLE_WHOLESALERS_INFO, 'wholesalers_url'));
      }

      $contents[] = array('text' => '<br>' . TEXT_WHOLESALERS_URL . $wholesaler_inputs_string);
      $contents[] = array('align' => 'center', 'text' => '<br>' . zen_image_submit('button_save.gif', IMAGE_SAVE) . ' <a href="' . zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $mInfo->wholesalers_id) . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_HEADING_DELETE_WHOLESALER . '</b>');

      $contents = array('form' => zen_draw_form('wholesalers', FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $mInfo->wholesalers_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $mInfo->wholesalers_name . '</b>');
      $contents[] = array('text' => '<br>' . zen_draw_checkbox_field('delete_image', '', true) . ' ' . TEXT_DELETE_IMAGE);

      $contents[] = array('align' => 'center', 'text' => '<br>' . zen_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $mInfo->wholesalers_id) . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (isset($mInfo) && is_object($mInfo)) {
        $heading[] = array('text' => '<b>' . $mInfo->wholesalers_name . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $mInfo->wholesalers_id . '&action=edit') . '">' . zen_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . zen_href_link(FILENAME_WHOLESALERS, 'page=' . $_GET['page'] . '&mID=' . $mInfo->wholesalers_id . '&action=delete') . '">' . zen_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');
        $contents[] = array('text' => '<br>' . TEXT_DATE_ADDED . ' ' . zen_date_short($mInfo->date_added));
        if (zen_not_null($mInfo->last_modified)) $contents[] = array('text' => TEXT_LAST_MODIFIED . ' ' . zen_date_short($mInfo->last_modified));
        $contents[] = array('text' => '<br>' . zen_info_image($mInfo->wholesalers_image, $mInfo->wholesalers_name));
      }
      break;
  }

  if ( (zen_not_null($heading)) && (zen_not_null($contents)) ) {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
?>
          </tr>
        </table></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
