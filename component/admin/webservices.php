<?php
/**
 * Webservices component for Joomla! CMS
 *
 * @copyright  Copyright (C) 2004 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_webservices'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

jimport('joomla.filesystem.file');

$applicationPath = realpath(JPATH_ROOT . '/libraries/webservices');
$composerPath = $applicationPath . '/vendor/autoload.php';

// Check if composer is installed.
if (!JFile::exists($composerPath))
{
	JFactory::getApplication()->enqueueMessage(JText::_('COM_WEBSERVICES_WEBSERVICE_COMPOSER_ERROR'), 'error');
	JFactory::getApplication()->redirect(JRoute::_(JUri::base(), false));
}

define ('JPATH_API', $applicationPath);
require_once($composerPath);

// Application reference
$app = JFactory::getApplication();

// Register the component namespace to the autoloader
JLoader::registerNamespace('Webservices', __DIR__);

// Build the controller class name based on task
$task = $app->input->getCmd('task', 'display');

// If $task is an empty string, apply our default since JInput might not
if ($task === '')
{
	$task = 'display';
}
$class = '\\Webservices\\Controller\\' . ucfirst(strtolower($task)) . 'Controller';

// Instantiate and execute the controller
$controller = new $class($app->input, $app);
$controller->execute();
