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
use Joomla\Input\Input;
use Joomla\Filter\InputFilter;
use Joomla\Registry\Registry;
use Joomla\Session\Session;

use Joomla\Authentication\Authentication;

use Joomla\DI\Container;
use Joomla\DI\ContainerAwareTrait;
use Joomla\DI\ContainerAwareInterface;

use Joomla\Utilities\ArrayHelper;

use Joomla\Webservices\Api\Api;
use Joomla\Webservices\Api\Soap\SoapHelper;

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
	 * The session object
	 *
	 * @var    Session
	 * @since  __DEPLOY_VERSION__
	 */
	protected $session;

	/**
	 * The application message queue.
	 *
	 * @var    array
	 * @since  __DEPLOY_VERSION__
	 */
	protected $messageQueue = array();

	/**
	 * The start time for measuring the execution time.
	 *
	 * @var    float
	 * @since  __DEPLOY_VERSION__
	 */
	public $startTime;

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  DI Container
	 * @param   Input      $input      An optional argument to provide dependency injection for the application's
	 *                                 input object.  If the argument is a Input object that object will become
	 *                                 the application's input object, otherwise a default input object is created.
	 * @param   WebClient  $client     An optional argument to provide dependency injection for the application's
	 *                                 client object.  If the argument is a Web\WebClient object that object will become
	 *                                 the application's client object, otherwise a default client object is created.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(Container $container, Input $input = null, WebClient $client = null)
	{
		// Set the start time of the app - will be used to calculate run time in the renderer
		$this->startTime = microtime(true);

		$config = $container->get('config');

		parent::__construct($input, $config, $client);

		$container->set('Joomla\\Webservices\\Application', $this)
			->alias('Joomla\\Application\\AbstractWebApplication', 'Joomla\\Webservices\\Application')
			->alias('Joomla\\Application\\AbstractApplication', 'Joomla\\Webservices\\Application')
			->alias('app', 'Joomla\\Webservices\\Application')
			->set('Joomla\\Input\\Input', $this->input)
			->set('Joomla\\DI\\Container', $container);

		$session = $container->get('session');
		$this->session = $session;

		$this->setContainer($container);
	}

	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function doExecute()
	{
		/** @var \Joomla\Language\LanguageFactory $languageFactory */
		$languageFactory = $this->getContainer()->get('Joomla\\Language\\LanguageFactory');
		$languageFactory->getLanguage()->load('lib_webservices');
		$text = $languageFactory->getText();

		$input = $this->input;
		$apiName = $input->getString('api');

		if (!$this->isApiEnabled($apiName))
		{
			return;
		}

		if (empty($apiName))
		{
			return;
		}

		try
		{
			$this->clearHeaders();
			$webserviceClient = $input->getString('webserviceClient');
			$optionName       = $input->getString('option');
			$optionName       = strpos($optionName, 'com_') === 0 ? substr($optionName, 4) : $optionName;
			$viewName         = $input->getString('view');
			$version          = $input->getString('webserviceVersion');

			$token = $input->getString($this->get('webservices.oauth2_token_param_name', 'access_token'));
			$apiName = ucfirst($apiName);
			$method  = strtoupper($input->getMethod());
			$task    = $this->getTask();
			$data    = $this->getPostedData();
			$dataGet = $input->getArray();

			if (empty($webserviceClient))
			{
				$webserviceClient = $this->isAdmin() ? 'administrator' : 'site';
			}

			$options = array(
				'api'               => $apiName,
				'optionName'        => $optionName,
				'viewName'          => $viewName,
				'webserviceVersion' => $version,
				'webserviceClient'  => $webserviceClient,
				'method'            => $method,
				'task'              => $task,
				'data'              => $data,
				'dataGet'           => $dataGet,
				'accessToken'       => $token,
				'format'            => $input->getString('format', $this->get('webservices.webservices_default_format', 'hal')),
				'id'                => $input->getString('id'),
				'absoluteHrefs'     => $input->getBool('absoluteHrefs', true),
			);

			$apiClass = 'Joomla\\Webservices\\Api\\' . $apiName . '\\' . $apiName;

			if (!class_exists($apiClass))
			{
				throw new \RuntimeException($text->sprintf('LIB_WEBSERVICES_API_UNABLE_TO_LOAD_API', $options['api']));
			}

			try
			{
				/** @var \Joomla\Webservices\Api\ApiBase $api */
				$api = new $apiClass($this->getContainer(), new Registry($options));
			}
			catch (\RuntimeException $e)
			{
				throw new \RuntimeException($text->sprintf('LIB_WEBSERVICES_API_UNABLE_TO_CONNECT_TO_API', $e->getMessage()));
			}

			// Run the api task
			$api->execute();

			// Display output
			$api->render();
		}
		catch (\Exception $e)
		{
			$code = $e->getCode() > 0 ? $e->getCode() : 500;

			// Set the server response code.
			$this->header('Status: ' . $code, true, $code);

			if (strtolower($apiName) == 'soap')
			{
				$this->setBody(SoapHelper::createSoapFaultResponse($e->getMessage()));
			}
			else
			{
				// Check for defined constants
				if (!defined('JSON_UNESCAPED_SLASHES'))
				{
					define('JSON_UNESCAPED_SLASHES', 64);
				}

				// An exception has been caught, echo the message and exit.
				$this->setBody(json_encode(array('message' => $e->getMessage(), 'code' => $e->getCode(), 'type' => get_class($e)), JSON_UNESCAPED_SLASHES));
			}
		}
	}

	/**
	 * Checks if given api name is currently install and enabled on this server
	 *
	 * @param   string  $apiName  Api name
	 *
	 * @return  bool
	 * @since   __DEPLOY_VERSION__
	 */
	private function isApiEnabled($apiName)
	{
		$apiName = strtolower($apiName);

		return ($this->get('webservices.enable_webservices', 0) == 1 && $apiName == 'hal')
		|| ($this->get('webservices.enable_soap', 0) == 1 && $apiName == 'soap');
	}

	/**
	 * Checks whether we are in the site or admin of the Joomla CMS.
	 *
	 * @return  bool
	 * @todo    Implement a check here
	 */
	public function isAdmin()
	{
		return true;
	}

	/**
	 * Login authentication function.
	 *
	 * @param   \Joomla\Authentication\AuthenticationStrategyInterface[]  $strategies
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function login($strategies)
	{
		$authenticate = new Authentication();

		foreach ($strategies as $name => $strategy)
		$authenticate->addStrategy($name, $strategy);

		return $authenticate->authenticate();
	}

	/**
	 * Logout authentication function.
	 *
	 * @param   integer  $userid   The user to load - Can be an integer or string - If string, it is converted to ID automatically
	 *
	 * @return  boolean  True on success
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function logout($userid = null)
	{
		return false;
	}

	/**
	 * Enqueue a system message.
	 *
	 * @param   string  $msg   The message to enqueue.
	 * @param   string  $type  The message type. Default is message.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function enqueueMessage($msg, $type = 'message')
	{
		// Don't add empty messages.
		if (!strlen($msg))
		{
			return;
		}

		// For empty queue, if messages exists in the session, enqueue them first.
		$this->getMessageQueue();

		// Enqueue the message.
		$this->messageQueue[] = array('message' => $msg, 'type' => strtolower($type));
	}

	/**
	 * Get the system message queue.
	 *
	 * @return  array  The system message queue.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getMessageQueue()
	{
		// For empty queue, if messages exists in the session, enqueue them.
		if (!count($this->messageQueue))
		{
			$sessionQueue = $this->session->get('application.queue');

			// Check if we have any messages in the session from a previous page
			if (count($sessionQueue))
			{
				$this->messageQueue = $sessionQueue;
				$this->session->set('application.queue', null);
			}
		}

		return $this->messageQueue;
	}

	/**
	 * Redirect to another URL overriden to ensure all messages are enqueued into the session
	 *
	 * @param   string   $url    The URL to redirect to. Can only be http/https URL
	 * @param   boolean  $moved  True if the page is 301 Permanently Moved, otherwise 303 See Other is assumed.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function redirect($url, $moved = false)
	{
		// Persist messages if they exist.
		if (count($this->messageQueue))
		{
			$this->session->set('application.queue', $this->messageQueue);
		}

		// Hand over processing to the parent now
		parent::redirect($url, $moved);
	}

	/**
	 * Method to get Task from request
	 *
	 * @return  string Task name
	 *
	 * @since   1.2
	 */
	public function getTask()
	{
		$command  = $this->input->get('task', '');

		// Check for array format.
		$filter = new InputFilter;

		if (is_array($command))
		{
			$command = $filter->clean(array_pop(array_keys($command)), 'cmd');
		}
		else
		{
			$command = $filter->clean($command, 'cmd');
		}

		// Check for a controller.task command.
		if (strpos($command, '.') !== false)
		{
			// Explode the controller.task command.
			list ($type, $task) = explode('.', $command);
		}
		else
		{
			$task = $command;
		}

		return $task;
	}

	/**
	 * Returns posted data in array format
	 *
	 * @return  array
	 *
	 * @since   1.2
	 */
	public function getPostedData()
	{
		$input = $this->input;
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
