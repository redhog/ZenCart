<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006 RedHog (Egil Möller) <redhog@redhog.org>          |
// |                                                                      |
// | http://redhog.org                                                    |
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


  $details = $db->Execute("select *
			  from " . TABLE_WHOLESALERS . " as wholesalers, " . TABLE_WHOLESALERS_INFO . " as wholesalers_info, " . TABLE_COUNTRIES . " as countries
			  where wholesalers.wholesalers_id = '{$_GET['wholesaler']}'
			  and wholesalers_info.wholesalers_id = '{$_GET['wholesaler']}'
                          and wholesalers_info.languages_id = '{$_SESSION['languages_id']}'
			  and wholesalers.wholesalers_country_id = countries.countries_id");

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/menu.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">

<!-- body_text //-->
<table border="0" width="100%" cellspacing="0" cellpadding="2">
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="2">
      <tr>
        <td class="main"><b><?php echo WHOLESALER_ADDRESS; ?></b></td>
        <td class="main"><b><?php echo WHOLESALER_PHONES; ?></b></td>
      </tr>
      <tr>
        <td class="main" valign="top">
          <?php
	   $address = array('company' => $details->fields['wholesalers_name'],
			    'firstname' => $details->fields['wholesalers_contact_firstname'],
			    'lastname' => $details->fields['wholesalers_contact_lastname'],
			    'street_address' => $details->fields['wholesalers_street_address'],
			    'suburb' => $details->fields['wholesalers_suburb'],
			    'postcode' => $details->fields['wholesalers_postcode'],
			    'city' => $details->fields['wholesalers_city'],
			    'state' => $details->fields['wholesalers_state'],
			    'country_id' => $details->fields['wholesalers_country_id']);
           echo zen_address_format($details->fields['address_format_id'], $address, 1, '', '<br>');
          ?>
        </td>
        <td class="main" valign="top">
          <?php echo WHOLESALER_EMAIL . " <a href='mailto:{$details->fields["wholesalers_email"]}'>{$details->fields["wholesalers_email"]}</a>"; ?><br />
          <?php echo WHOLESALER_WEBPAGE . " <a href='{$details->fields["wholesalers_url"]}'>{$details->fields["wholesalers_url"]}</a>"; ?><br />
          <?php echo WHOLESALER_PHONE . ' ' . $details->fields["wholesalers_phone"]; ?><br />
          <?php echo WHOLESALER_MOBILE . ' ' . $details->fields["wholesalers_mobile"]; ?><br />
          <?php echo WHOLESALER_FAX . ' ' . $details->fields["wholesalers_fax"]; ?><br />
        </td>
      </tr>
    </table></td>
    <td class="pageHeading" align="right" valign="top"><?php echo zen_image(DIR_WS_IMAGES . HEADER_LOGO_IMAGE, HEADER_ALT_TEXT); ?></td>
  </tr>
</table>
<!-- body_text_eof //-->

<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
