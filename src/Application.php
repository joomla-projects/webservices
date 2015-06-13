<?php
/**
 * Application for Joomla Webservices.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices;

use Joomla\Application\AbstractWebApplication;
use Joomla\Application\Web\WebClient;
use Joomla\Registry\Registry;
use Joomla\Input\Input;

use Joomla\DI\Container;
use Joomla\DI\ContainerAwareTrait;
use Joomla\DI\ContainerAwareInterface;

/**
 * Webservices bootstrap class
 *
 * @package     Red
 * @subpackage  System
 * @since       1.0
 */
class Application extends AbstractWebApplication implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  DI Container
	 * @param   Input      $input      An optional argument to provide dependency injection for the application's
	 *                                 input object.  If the argument is a Input object that object will become
	 *                                 the application's input object, otherwise a default input object is created.
	 * @param   Registry   $config     An optional argument to provide dependency injection for the application's
	 *                                 config object.  If the argument is a Registry object that object will become
	 *                                 the application's config object, otherwise a default config object is created.
	 * @param   WebClient  $client     An optional argument to provide dependency injection for the application's
	 *                                 client object.  If the argument is a Web\WebClient object that object will become
	 *                                 the application's client object, otherwise a default client object is created.
	 *
	 * @since   1.0
	 */
	public function __construct(Container $container, Input $input = null, Registry $config = null, WebClient $client = null)
	{
		if ($config === null)
		{
			$config = $this->initialiseConfig();
		}

		parent::__construct($input, $config, $client);

		$container->set('Joomla\\Webservices\\Application', $this)
			->alias('Joomla\\Application\\AbstractWebApplication', 'Joomla\\Webservices\\Application')
			->alias('Joomla\\Application\\AbstractApplication', 'Joomla\\Webservices\\Application')
			->alias('app', 'Joomla\\Webservices\\Application')
			->set('Joomla\\Input\\Input', $this->input)
			->set('Joomla\\DI\\Container', $container);

		$this->setContainer($container);
	}

	/**
	 * Initialises Webservices configuration
	 *
	 * @return  Registry|null
	 */
	public function initialiseConfig()
	{
		$plugin = \JPluginHelper::getPlugin('system', 'webservices');
		$config = null;

		if ($plugin)
		{
			if (is_string($plugin->params))
			{
				$config = new Registry($plugin->params);
			}
			elseif (is_object($plugin->params))
			{
				$config = $plugin->params;
			}
		}

		return $config;
	}

	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function doExecute()
	{
		// Load library language
		$lang = \JFactory::getLanguage();
		$lang->load('lib_webservices', JPATH_SITE);
	}
}
