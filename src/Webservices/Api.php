<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Webservices;

use Joomla\DI\Container;
use Joomla\Webservices\Application;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Language\Text;

/**
 * Interface to handle api calls
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.2
 */
abstract class Api extends ApiBase implements DispatcherAwareInterface
{
	/**
	 * @var    string  Name of the Api
	 * @since  1.2
	 */
	public $apiName = '';

	/**
	 * @var    string  Operation that will be preformed with this Api call. supported: CREATE, READ, UPDATE, DELETE
	 * @since  1.2
	 */
	public $operation = 'read';

	/**
	 * Application Object
	 *
	 * @var    Application
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app = null;

	/**
	 * Event Dispatcher Object
	 *
	 * @var    DispatcherInterface
	 * @since  __DEPLOY_VERSION__
	 */
	protected $dispatcher = null;

	/**
	 * The text translation object
	 *
	 * @var    Text
	 * @since  __DELPOY_VERSION__
	 */
	protected $text = null;

	/**
	 * Method to instantiate the file-based api call.
	 *
	 * @param   Container  $container  The DIC object
	 * @param   mixed      $options    Optional custom options to load. JRegistry or array format
	 *
	 * @since   1.2
	 */
	public function __construct(Container $container, $options = null)
	{
		$this->app = $container->get('app');
		$this->text = $container->get('Joomla\\Language\\LanguageFactory')->getText();

		// Initialise / Load options
		$this->setOptions($options);

		// Main properties
		$this->setApi($this->options->get('api', 'hal'));

		$this->setDispatcher($container->get('Joomla\\Event\\Dispatcher'));

		parent::__construct($container);
	}

	/**
	 * Set the dispatcher to use.
	 *
	 * @param   DispatcherInterface  $dispatcher  The dispatcher to use.
	 *
	 * @return  DispatcherAwareInterface  This method is chainable.
	 *
	 * @since   1.0
	 */
	public function setDispatcher(DispatcherInterface $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Change the Api
	 *
	 * @param   string  $apiName  Api instance to render
	 *
	 * @return  void
	 *
	 * @since   1.2
	 */
	public function setApi($apiName)
	{
		$this->apiName = $apiName;
	}
}
