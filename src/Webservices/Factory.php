<?php
/**
 * Factory for Joomla Webservices.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Webservices;

use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\Registry\Registry;
use Joomla\Router\Router;

/**
 * Factory for webservices.
 *
 * @since  __DEPLOY_VERSION__
 */
class Factory
{
	/**
	 * Gets an instance of the main API class.
	 *
	 * The API class deals with interaction style and could be considered
	 * roughly equivalent to a controller in an MVC design.
	 *
	 * @param   Container  $container  Dependency injection container.
	 * @param   string     $style      Interaction style (eg. 'rest' or 'soap').
	 * @param   Registry   $options    Options to be passed to the API object.
	 *
	 * @return  ApiInterface
	 *
	 * @throws  \RuntimeException
	 */
	public static function getApi(Container $container, $style, Registry $options)
	{
		// Construct the class name.
		$apiClass = 'Joomla\\Webservices\\Api\\' . ucfirst($style) . '\\' . ucfirst($style);

		if (!class_exists($apiClass))
		{
			throw new \RuntimeException($container->get('text')->sprintf('LIB_WEBSERVICES_API_UNABLE_TO_LOAD_API', $style));
		}

		try
		{
			/** @var \Joomla\Webservices\Api\ApiBase $api */
			$api = new $apiClass($container, $options);
		}
		catch (\RuntimeException $e)
		{
			throw new \RuntimeException($container->get('text')->sprintf('LIB_WEBSERVICES_API_UNABLE_TO_CONNECT_TO_API', $e->getMessage()));
		}

		return $api;
	}

	/**
	 * Gets instance of helper object class if it exists.
	 *
	 * @param   string  $version  Webservice version.
	 * @param   string  $client   Webservice client ('administrator' or 'site').
	 * @param   string  $name     Webservice name.
	 * @param   string  $path     Webservice path.
	 *
	 * @return  mixed It will return Api helper class or false if it does not exist.
	 *
	 * @since   1.2
	 */
	public static function getHelper($version, $client, $name, $path)
	{
		static $apiHelper = null;

		// If we already have it, return it.
		if (!is_null($apiHelper))
		{
			return $apiHelper;
		}

		$helperFile = ConfigurationHelper::getWebserviceHelper($client, strtolower($name), $version, $path);

		if (file_exists($helperFile))
		{
			require_once $helperFile;
		}

		$webserviceName = preg_replace('/[^A-Z0-9_\.]/i', '', $name);
		$helperClassName = 'JApiHalHelper' . ucfirst($client) . ucfirst(strtolower($webserviceName));

		if (!class_exists($helperClassName))
		{
			return false;
		}

		$apiHelper = new $helperClassName;

		return $apiHelper;
	}

	/**
	 * Get a profile object.
	 *
	 * @param   DatabaseDriver  $db            Database driver.
	 * @param   string          $clientName    Client name (eg. 'administrator' or 'site').
	 * @param   string          $resourceName  Name of resource for which the profile is sought.
	 * @param   string          $version       Version of the resource profile sought.
	 * @param   string          $operation     Operation name (eg. 'read', 'update').
	 *
	 * @return  Profile
	 */
	public static function getProfile(DatabaseDriver $db, $clientName, $resourceName, $version, $operation)
	{
		// If no version number has been specified, get the lastest for the given resource.
		if ($version == '')
		{
			$version = ConfigurationHelper::getNewestWebserviceVersion($clientName, $resourceName, $db);
		}

		// Get profile data from the database.  We need this in order to get the path.
		$profileData = ConfigurationHelper::getInstalledWebservice($clientName, $resourceName, $version, $db);

		// Get the profile XML schema.
		$profileXml = ConfigurationHelper::loadWebserviceConfiguration($resourceName, $version, $profileData['path'], $clientName);

		// Instantiate a profile object using the XML schema.
		$profile = new Profile($profileXml->operations->$operation);

		return $profile;
	}

	/**
	 * Get a router preloaded with routes.
	 *
	 * @param   Registry  $options  Options to be passed to the API object.
	 *
	 * @return  Router
	 */
	public static function getRouter(Registry $options)
	{
		// Get the file path for the router configuration file.
		$file = $options->get('config_file', JPATH_API . '/routes.json');

		// Verify the configuration exists and is readable.
		if (!is_readable($file))
		{
			throw new \RuntimeException('Routes file does not exist or is unreadable: ' . $file);
		}

		// Load the configuration file into an object.
		$resources = json_decode(file_get_contents($file));

		if ($resources === null)
		{
			throw new \RuntimeException(sprintf('Unable to parse the routes file %s.', $file));
		}

		// Instantiate a new router.
		$router = new Router;

		// Load the routes into the router.
		foreach ($resources as $resource)
		{
			// Instead of a controller name, supply an array.
			$controller = [
				'style'		=> $resource->style,
				'resource'	=> $resource->name,
			];

			// Get optional regular expressions for named arguments.
			$regex = isset($resource->regex) ? (array) $resource->regex : [];

			// Add routes to router.
			foreach ($resource->routes as $route => $methods)
			{
				foreach ($methods as $method)
				{
					$router->addRoute($method, $route, $controller, $regex);
				}
			}
		}

		return $router;
	}

	/**
	 * Get a webservice object.
	 *
	 * @param   Container  $container      Dependency injection container.
	 * @param   string     $operationName  Operation name (eg. 'create', 'read').
	 * @param   Registry   $options        Array of options to be passed to the webservice.
	 *
	 * @return  Webservice object.
	 */
	public static function getWebservice(Container $container, $operationName, Registry $options)
	{
		$webserviceClass = 'Joomla\\Webservices\\Webservices\\' . ucfirst($operationName);

		if (!class_exists($webserviceClass))
		{
			throw new \RuntimeException($container->get('text')->sprintf('LIB_WEBSERVICES_API_UNABLE_TO_LOAD_WEBSERVICE', $operationName));
		}

		try
		{
			$webservice = new $webserviceClass($container, $options);
		}
		catch (\RuntimeException $e)
		{
			throw new \RuntimeException($container->get('text')->sprintf('LIB_WEBSERVICES_API_UNABLE_TO_INSTANTIATE_WEBSERVICE', $e->getMessage()));
		}

		return $webservice;
	}
}
