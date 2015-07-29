<?php
/**
 * Entry file for Joomla webservices application.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Application constants
define('JPATH_API',      dirname(__DIR__));
define('JPATH_TEMPLATES', JPATH_API . '/layouts');
define('JPATH_CMS',      dirname(__DIR__) . DIRECTORY_SEPARATOR . 'staging.joomla.org' . DIRECTORY_SEPARATOR . 'www');

// Ensure we've initialized Composer
if (!file_exists(JPATH_API . '/vendor/autoload.php'))
{
	header('HTTP/1.1 500 Internal Server Error', null, 500);
	echo 'Composer is not set up properly, please run "composer install".';

	exit;
}

require JPATH_API . '/vendor/autoload.php';

// Wrap in a try/catch so we can display an error if need be
try
{
	$container = (new Joomla\DI\Container)
		->registerServiceProvider(new Joomla\Webservices\Service\ConfigurationProvider)
		->registerServiceProvider(new Joomla\Webservices\Service\DatabaseProvider)
		->registerServiceProvider(new Joomla\Language\Service\LanguageFactoryProvider)
		->registerServiceProvider(new Joomla\Webservices\Service\EventProvider)
		->registerServiceProvider(new Joomla\Webservices\Service\SessionProvider);

	// Set error reporting based on config
	$errorReporting = (int) $container->get('config')->get('errorReporting', 0);
	error_reporting($errorReporting);
}
catch (\Exception $e)
{
	header('HTTP/1.1 500 Internal Server Error', null, 500);
	header('Content-Type: text/html; charset=utf-8');
	echo 'An error occurred while booting the application: ' . $e->getMessage();

	exit;
}

// Execute the application
try
{
	(new Joomla\Webservices\Application($container))->execute();
}
catch (\Exception $e)
{
	header('HTTP/1.1 500 Internal Server Error', null, 500);
	header('Content-Type: text/html; charset=utf-8');
	echo 'An error occurred while executing the application: ' . $e->getMessage();

	exit;
}