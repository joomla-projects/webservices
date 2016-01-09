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
use Joomla\Router\Router;
use Joomla\Session\Session;

use Joomla\Authentication\Authentication;

use Joomla\DI\Container;
use Joomla\DI\ContainerAwareTrait;
use Joomla\DI\ContainerAwareInterface;

use Joomla\Utilities\ArrayHelper;

use Joomla\Webservices\Api\Soap\SoapHelper;
use Joomla\Webservices\Webservices\Factory;
use Joomla\Webservices\Service\RendererProvider;
use Joomla\Webservices\Uri\Uri;

use Negotiation\Negotiator;

/**
 * Webservices bootstrap class
 *
 * @package     Red
 * @subpackage  System
 * @since       1.0
 */
class WebApplication extends AbstractWebApplication implements ContainerAwareInterface
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
		$this->getContainer()->set('text', $languageFactory->getText());

		$input = $this->input;
		$method = $input->getMethod();

		// The supported content types are retrieved from the configuration (this
		// is temporary as it needs to come from the services themselves).
		$contentType = $this->negotiateContentType(
			$input->server->get('HTTP_ACCEPT', '*/*', 'RAW'),
			$this->get('webservices.content_types')
		);

		$this->clearHeaders();

		// Get the router.
		$router = Factory::getRouter(
			new Registry(['config_file' => JPATH_API . $this->get('webservices.routes')])
		);

		// Get the request URI and tell the router to parse it.
		$uri = Uri::getInstance();
		$path = str_replace($uri->base(), '', $uri->current());
		$match = $router->parseRoute($path, $method);

		// Get data from the matched route.
		$style = $match['controller']['style'];
		$resourceName = $match['controller']['resource'];
		$input->set('optionName', $resourceName);
		$input->set('method', $method);

		// Add parsed variables from the route into the input object.
		foreach ($match['vars'] as $key => $value)
		{
			$input->set($key, $value);
		}

		$options = array(
			'viewName'      => $input->getString('view'),
			'data'          => $this->getPostedData(),
			'dataGet'       => $input->getArray(),
			'accessToken'   => $input->getString($this->get('webservices.oauth2_token_param_name', 'access_token')),
			'absoluteHrefs' => $input->getBool('absoluteHrefs', true),
		);

		$rendererOptions = [
			'charset'		=> 'utf-8',
			'language'		=> 'en-GB',
			'direction'		=> 'ltr',
			'link'			=> '',
			'base'			=> '',
			'absoluteHrefs'	=> $input->getBool('absoluteHrefs', true),
			'uriParams'		=> [],
			'resourceName'	=> $resourceName,
		];

		try
		{
			// Instantiate a renderer, based on content negotiation.
			$this->container->registerServiceProvider(new RendererProvider($this, $contentType, new Registry($rendererOptions)));

			// Load the interaction style (api) class, execute it, then render the result.
			Factory::getApi($this->container, $style, new Registry($options))
				->execute($input)
				->render()
				;
		}
		catch (\Exception $e)
		{
			// @TODO Generally, application errors should be handled by the API object,
			// so the following code should probably be moved into the API class.
			$code = $e->getCode() > 0 ? $e->getCode() : 500;

			// Set the server response code.
			$this->header('Status: ' . $code, true, $code);

			// @TODO Move this code to the SOAP code.
//			if (strtolower($apiStyle) == 'soap')
//			{
//				$this->setBody(SoapHelper::createSoapFaultResponse($e->getMessage()));
//			}

			// Check for defined constants (required prior to PHP 5.4.0).
			if (!defined('JSON_UNESCAPED_SLASHES'))
			{
				define('JSON_UNESCAPED_SLASHES', 64);
			}

			// An exception has been caught, echo the message and exit.
			$this->setBody(
				json_encode(
					array(
						'message' => $e->getMessage(),
						'code' => $code,
						'type' => get_class($e),
						'trace' => $e->getTrace(),
					),
				JSON_UNESCAPED_SLASHES)
			);
		}
	}

	/**
	 * Content type negotiation.
	 * 
	 * If no match can be found a RuntimeException is thrown.
	 * 
	 * @param   string  $accept      An "Accept" string formatted as per RFC7231.
	 * @param   array   $priorities  Array of content types accepted in priority order.
	 * 
	 * @return  string  Best match content type.
	 * @throws  RuntimeException
	 * 
	 * @see https://tools.ietf.org/html/rfc7231#section-5.3.2
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function negotiateContentType($accept = '', $priorities = array())
	{
		$mediaType = (new Negotiator())->getBest($accept, $priorities);

		if (is_null($mediaType))
		{
			throw new \RuntimeException('406 Not acceptable');		// @TODO Better error handling
		}

		return $mediaType->getValue();
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
		{
			$authenticate->addStrategy($name, $strategy);
		}

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
		// Hand over processing to the parent now
		parent::redirect($url, $moved);
	}

	/**
	 * Method to get Task from request
	 *
	 * @return  string Task name
	 *
	 * @since   1.2
	 * @deprecated
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
