<?php
/**
* Configuration Service Provider for Joomla Webservices.
*
* @package    Webservices
* @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
* @license    GNU General Public License version 2 or later; see LICENSE.txt
*/

namespace Joomla\Webservices\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;

/**
* Configuration service provider
*
* @since  1.0
*/
class WebconfigProvider implements ServiceProviderInterface
{
	/**
	* Configuration instance
	*
	* @var    Registry
	* @since  1.0
	*/
	private $config;

	/**
	* Constructor.
	*
	* @since   1.0
	* @throws  \RuntimeException
	*/
	public function __construct()
	{
		// Set the configuration file path for the application.
		$file = JPATH_API . '/config.dist.json';

		// Verify the configuration exists and is readable.
		if (!is_readable($file))
		{
			throw new \RuntimeException('Configuration file does not exist or is unreadable.');
		}

		// Load the configuration file into an object.
		$configObject = json_decode(file_get_contents($file));

		if ($configObject === null)
		{
			throw new \RuntimeException(sprintf('Unable to parse the configuration file %s.', $file));
		}

		// Get the Joomla! configuration parameters
		$config = new \JConfig();

		// Set the correct database values for config object
		$configObject->database->driver = $config->dbtype;
		$configObject->database->host = $config->host;
		$configObject->database->user = $config->user;
		$configObject->database->password = $config->password;
		$configObject->database->prefix = $config->dbprefix;

		$this->config = (new Registry)->loadObject($configObject);
		$this->config->set('language.basedir', JPATH_API . '/src');
	}

	/**
	* Registers the service provider with a DI container.
	*
	* @param   Container  $container  The DI container.
	*
	* @return  void
	*
	* @since   1.0
	*/
	public function register(Container $container)
	{
		$container->set('config',
			function ()
			{
				return $this->config;
			},
			true, true
		);
	}
}
