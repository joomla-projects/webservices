<?php
/**
 * Bootstrap file.
 * Including this file into your application and executing JBootstrap::bootstrap() will make webservices available to use.
 *
 * @package    Redcore
 * @copyright  Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license    GNU General Public License version 2 or later, see LICENSE.
 */

defined('JPATH_PLATFORM') or die;

if (!defined('JPATH_WEBSERVICES'))
{
	// Sets WEBSERVICES path variable, to avoid setting it twice
	define('JPATH_WEBSERVICES', dirname(__FILE__));
}

/**
 * Webservices bootstrap class
 *
 * @package     Red
 * @subpackage  System
 * @since       1.0
 */
class JBootstrap
{
	/**
	 * Webservices configuration
	 *
	 * @var    JRegistry
	 */
	public static $config = null;

	/**
	 * Gets Webservices config param
	 *
	 * @param   string  $key      Config key
	 * @param   mixed   $default  Default value
	 *
	 * @return  mixed
	 */
	public static function getConfig($key, $default = null)
	{
		if (is_null(self::$config))
		{
			$plugin = JPluginHelper::getPlugin('system', 'webservices');

			if ($plugin)
			{
				if (is_string($plugin->params))
				{
					self::$config = new JRegistry($plugin->params);
				}
				elseif (is_object($plugin->params))
				{
					self::$config = $plugin->params;
				}
			}

			return null;
		}

		return self::$config->get($key, $default);
	}

	/**
	 * Effectively bootstrap webservices.
	 *
	 * @return  void
	 */
	public static function bootstrap()
	{
		if (!defined('WEBSERVICES_LIBRARY_LOADED'))
		{
			// Sets bootstrapped variable, to avoid bootstrapping webservices twice
			define('WEBSERVICES_LIBRARY_LOADED', 1);

			// Register the classes for autoload.
			JLoader::registerPrefix('J', JPATH_WEBSERVICES);

			// Setup the Loader.
			JLoader::setup();

			// Load library language
			$lang = JFactory::getLanguage();
			$lang->load('lib_webservices', JPATH_SITE);

			// For Joomla! 2.5 compatibility we add some core functions
			if (version_compare(JVERSION, '3.0', '<'))
			{
				JLoader::registerPrefix('J',  JPATH_LIBRARIES . '/webservices/joomla', false, true);
			}
		}
	}
}
