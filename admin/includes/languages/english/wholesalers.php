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

define('HEADING_TITLE', 'Wholesalers');

define('TABLE_HEADING_WHOLESALERS', 'Wholesalers');
define('TABLE_HEADING_ACTION', 'Action');

define('TEXT_HEADING_NEW_WHOLESALER', 'New Wholesaler');
define('TEXT_HEADING_EDIT_WHOLESALER', 'Edit Wholesaler');
define('TEXT_HEADING_DELETE_WHOLESALER', 'Delete Wholesaler');

define('TEXT_WHOLESALERS', 'Wholesalers:');
define('TEXT_DATE_ADDED', 'Date Added:');
define('TEXT_LAST_MODIFIED', 'Last Modified:');
define('TEXT_PRODUCTS', 'Products:');
define('TEXT_PRODUCTS_IMAGE_DIR', 'Upload to directory:');
define('TEXT_IMAGE_NONEXISTENT', 'IMAGE DOES NOT EXIST');

define('TEXT_NEW_INTRO', 'Please fill out the following information for the new wholesaler');
define('TEXT_EDIT_INTRO', 'Please make any necessary changes');

define('TEXT_WHOLESALERS_NAME', 'Wholesalers Name:');
define('TEXT_WHOLESALERS_CONTACT_FIRSTNAME', "Contact persons' first name:");
define('TEXT_WHOLESALERS_CONTACT_LASTNAME', "Contact persons' last name:");

define('TEXT_WHOLESALERS_EMAIL', 'Email:');
define('TEXT_WHOLESALERS_PHONE', 'Phone:');
define('TEXT_WHOLESALERS_MOBILE', 'Mobile:');
define('TEXT_WHOLESALERS_FAX', 'Fax:');

define('TEXT_WHOLESALERS_STREET_ADDRESS', 'Street address:');
define('TEXT_WHOLESALERS_SUBURB', 'Suburb:');
define('TEXT_WHOLESALERS_POSTCODE', 'Postcode:');
define('TEXT_WHOLESALERS_CITY', 'City:');
define('TEXT_WHOLESALERS_STATE', 'State:');
define('TEXT_WHOLESALERS_COUNTRY', 'Country:');

define('TEXT_WHOLESALERS_URL', 'Wholesalers URL:');

define('TEXT_DELETE_INTRO', 'Are you sure you want to delete this wholesaler?');
define('TEXT_DELETE_IMAGE', 'Delete wholesalers image?');
define('TEXT_DELETE_PRODUCTS', 'Delete products from this wholesaler? (including product reviews, products on special, upcoming products)');
define('TEXT_DELETE_WARNING_PRODUCTS', '<b>WARNING:</b> There are %s products still linked to this wholesaler!');

define('ERROR_DIRECTORY_NOT_WRITEABLE', 'Error: I can not write to this directory. Please set the right user permissions on: %s');
define('ERROR_DIRECTORY_DOES_NOT_EXIST', 'Error: Directory does not exist: %s');
?>