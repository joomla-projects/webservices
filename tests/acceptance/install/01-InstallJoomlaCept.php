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

$I->wantToTest('Joomla 3 Installation');
$I->installJoomlaRemovingInstallationFolder();
$I->doAdministratorLogin();
$I->setErrorReportingToDevelopment();
