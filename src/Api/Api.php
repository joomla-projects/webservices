<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Api;

use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;
use Joomla\DI\Container;
use Joomla\Webservices\Application;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherInterface;

/**
 * Interface to handle api calls
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.2
 */
class Api extends ApiBase implements DispatcherAwareInterface
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

		// Initialise / Load options
		$this->setOptions($options);

		// Main properties
		$this->setApi($this->options->get('api', 'hal'));

		$this->setDispatcher($container->get('Joomla\\Event\\Dispatcher'));

		parent::__construct($container);

		// Load Library language
		//$this->loadExtensionLanguage('lib_joomla', JPATH_ADMINISTRATOR);
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

	/**
	 * Execute the Api operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		return null;
	}

	/**
	 * Method to render the api call output.
	 *
	 * @return  string  Api call output
	 *
	 * @since   1.2
	 */
	public function render()
	{
		return '';
	}

	/**
	 * Change the debug mode
	 *
	 * @param   boolean  $debug  Enable / Disable debug
	 *
	 * @return  void
	 *
	 * @since   1.2
	 */
	public function setDebug($debug)
	{
		$this->options->set('debug', (boolean) $debug);
	}

	/**
	 * Load extension language file.
	 *
	 * @param   string  $option  Option name
	 * @param   string  $path    Path to language file
	 *
	 * @return  object
	 */
	public function loadExtensionLanguage($option, $path = JPATH_SITE)
	{
		/** @var \Joomla\Language\Language $lang */
		$lang = $this->getContainer()->get('Joomla\\Language\\LanguageFactory')->getLanguage();

		// Load common and local language files.
		$lang->load($option, $path, null, false, false)
		|| $lang->load($option, $path . "/components/$option", null, false, false)
		|| $lang->load($option, $path, $lang->getDefault(), false, false)
		|| $lang->load($option, $path . "/components/$option", $lang->getDefault(), false, false);

		return $this;
	}

	/**
	 * Returns posted data in array format
	 *
	 * @param   Container  $container  The DIC object
	 *
	 * @return  array
	 *
	 * @since   1.2
	 */
	public static function getPostedData(Container $container)
	{
		/** @var \Joomla\Input\Input $input */
		$input = $container->get('app')->input;
		$inputData = file_get_contents("php://input");

		if (is_object($inputData))
		{
			$inputData = ArrayHelper::fromObject($inputData);
		}
		elseif(is_string($inputData))
		{
			$parsedData = null;

			// We try to transform it into JSON
			if ($data_json = @json_decode($inputData, true))
			{
				if (json_last_error() == JSON_ERROR_NONE)
				{
					$parsedData = (array) $data_json;
				}
			}

			// We try to transform it into XML
			if (is_null($parsedData) && $xml = @simplexml_load_string($inputData))
			{
				$json = json_encode((array) $xml);
				$parsedData = json_decode($json, true);
			}

			// We try to transform it into Array
			if (is_null($parsedData) && !empty($inputData) && !is_array($inputData))
			{
				parse_str($inputData, $parsedData);
			}

			$inputData = $parsedData;
		}
		else
		{
			$inputData = $input->post->getArray();
		}

		// Filter data with Input default filter
		$postedData = new Input($inputData);

		return $postedData->getArray();
	}
}
