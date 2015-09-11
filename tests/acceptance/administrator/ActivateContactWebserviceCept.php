<?php
/**
 * @package     Joomla
 * @subpackage  webservices
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

$I = new \AcceptanceTester($scenario);
$I->am('Administrator');
$I->wantToTest('Activate the default webservices');
$I->doAdministratorLogin();
$I->comment('I enable basic authentication');
$I->amOnPage('administrator/index.php?option=com_webservices');
$I->waitForText('Webservice Manager', 30, ['css' => 'H1']);
$I->click(['class' => 'lc-not_installed_webservices']);
$I->click(['class' => 'lc-install_webservice_administrator_contact']);
$I->waitForElement(['id' => 'mainComponentWebservices'], 30);
$I->see('administrator.contact.1.0.0.xml',['class' => 'lc-webservice_file']);

