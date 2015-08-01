<?php
/**
 * @package    Redcore.Admin
 *
 * @copyright  Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license    GNU General Public License version 2 or later, see LICENSE.
 */

defined('_JEXEC') or die;

$applicationPath = realpath(JPATH_ROOT . '/../../webservices');
$composerPath = $applicationPath . '/vendor/autoload.php';
define ('JPATH_API', $applicationPath);
require_once($composerPath);

JLoader::registerPrefix('Webservices', dirname(__FILE__));

$app = JFactory::getApplication();

// Check access.
if (!JFactory::getUser()->authorise('core.manage', 'com_webservices'))
{
	$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');

	return false;
}

// Load specific js component
JHtml::_('jquery.framework');
JHtml::_('script', 'webservices/component.min.js', true, true);

require_once JPATH_COMPONENT . '/helpers/webservices.php';

// Instanciate and execute the front controller.
$controller = JControllerLegacy::getInstance('Webservices');
$controller->execute($app->input->get('task'));
$controller->redirect();
