<?php
/**
 * @package     Joomla
 * @subpackage  webservices
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Load the Step Object Page
$I = new \AcceptanceTester($scenario);

$I->am('Administrator');
$I->wantToTest('Webservices installation in Joomla 3');
$I->doAdministratorLogin();
$url = $I->getConfiguration('package_url');
$I->installExtensionFromUrl($url);