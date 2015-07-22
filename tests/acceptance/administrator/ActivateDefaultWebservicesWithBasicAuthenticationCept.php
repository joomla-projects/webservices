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
$I->amOnPage('administrator/index.php?option=com_plugins');
$I->waitForText('Plugins: Plugins',30,['css' => 'H1']);
$I->enablePlugin('Webservices - System plugin');
$I->searchForItem('Webservices - System plugin');
$I->click(['link' => 'Webservices - System plugin']);
$I->waitForText('Plugins: Webservices - System plugin',30,['css' => 'H1']);
$I->selectOptionInChosen('Enable webservices', 'Yes');
$I->selectOptionInChosen('Check user permission against','Joomla - Use already defined authorization checks in Joomla');
$I->click(['xpath' => "//div[@id='toolbar-apply']/button"]);
$I->waitForText('Plugin successfully saved.', 30,['id' => 'system-message-container']);
$I->click(['xpath' => "//div[@id='toolbar-cancel']/button"]);
$I->waitForElement(['link' => 'Webservices - System plugin'],30);
$I->amOnPage('administrator/index.php?option=com_webservices');
$I->waitForText('Webservice Manager', 30, ['css' => 'H1']);
$I->click(['class' => 'lc-not_installed_webservices']);
$I->click(['class' => 'lc-install_all_webservices']);
$I->waitForElement(['id' => 'mainComponentWebservices'], 30);
$I->see('administrator.contact.1.0.0.xml',['class' => 'lc-webservice-file']);
$I->see('site.contact.1.0.0.xml',['class' => 'lc-webservice-file']);
$I->see('site.users.1.0.0.xml',['class' => 'lc-webservice-file']);
