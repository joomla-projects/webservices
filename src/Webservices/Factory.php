<?php
/**
 * Factory for Joomla Webservices.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Webservices;

use Joomla\DI\Container;
use Joomla\Registry\Registry;

/**
 * Factory for webservices.
 *
 * @since  __DEPLOY_VERSION__
 */
class Factory
{
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
	 * @param   Container  $container     Dependency injection container.
	 * @param   string     $clientName    Client name (eg. 'administrator' or 'site').
	 * @param   string     $resourceName  Name of resource for which the profile is sought.
	 * @param   string     $version       Version of the resource profile sought.
	 * @param   string     $operation     Operation name (eg. 'read', 'update').
	 * 
	 * @return  Profile
	 */
	public static function getProfile(Container $container, $clientName, $resourceName, $version, $operation)
	{
		$db = $container->get('db');

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