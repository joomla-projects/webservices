<?php
/**
 * @package     Joomla
 * @subpackage  webservices
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


$I = new AcceptanceTester($scenario);
$I->wantTo('Uninstall Webservices Extension');
$I->doAdministratorLogin();
$I->amOnPage('/administrator/index.php?option=com_installer&view=manage');
$I->waitForText('Extensions: Manage',30, ['css' => 'H1']);
$I->searchForItem('Webservices package');
$I->waitForElement(['id' => 'manageList']);
$I->checkAllResults();
$I->click(['xpath' => "//div[@id='toolbar-delete']/button"]);
$I->waitForElement(['id' => 'system-message-container'],30);
$I->see('Uninstalling the package was successful.', ['id' => 'system-message-container']);
$I->searchForItem('Webservices package');
$I->waitForElement(['class' => 'alert-no-items'],30);
$I->see('There are no extensions installed matching your query.', ['class' => 'alert-no-items']);
