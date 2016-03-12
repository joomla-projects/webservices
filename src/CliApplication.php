<?php
/**
 * Application for Joomla Webservices.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices;

use Joomla\Application\AbstractCliApplication;
use Joomla\Application\Cli\CliOutput;
use Joomla\Input\Cli as Input;
use Joomla\Filter\InputFilter;
use Joomla\Registry\Registry;

use Joomla\Authentication\Authentication;

use Joomla\DI\Container;
use Joomla\DI\ContainerAwareTrait;
use Joomla\DI\ContainerAwareInterface;

use Joomla\Utilities\ArrayHelper;

use Joomla\Webservices\Api\Soap\SoapHelper;
use Joomla\Webservices\Webservices\Factory;
use Joomla\Webservices\Service\RendererProvider;

use Negotiation\Negotiator;

/**
 * Webservices bootstrap class
 *
 * @package     Red
 * @subpackage  System
 * @since       1.0
 */
class CliApplication extends AbstractCliApplication implements ContainerAwareInterface
{
	use ContainerAwareTrait;

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
	 * The application response headers.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $responseHeaders = array();

	/**
	 * The application response body.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $responseBody = '';

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  DI Container
	 * @param   Input      $input      An optional argument to provide dependency injection for the application's
	 *                                 input object.  If the argument is a Input object that object will become
	 *                                 the application's input object, otherwise a default input object is created.
	 * @param   CliOutput  $output     The output handler.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(Container $container, Input $input = null, CliOutput $output = null)
	{
		// Set the start time of the app - will be used to calculate run time in the renderer
		$this->startTime = microtime(true);

		// Force this value so Uri class does not fall over.
		$_SERVER['HTTP_HOST'] = '';

		$config = $container->get('config');

		parent::__construct($input, $config, $output);

		$container->set('Joomla\\Webservices\\Application', $this)
			->alias('Joomla\\Application\\AbstractCliApplication', 'Joomla\\Webservices\\Application')
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

		if ($input->getBool('help'))
		{
			$this->help();

			return;
		}

		// The supported content types are retrieved from the configuration (this
		// is temporary as it needs to come from the services themselves).
		$contentType = $this->negotiateContentType(
			$input->getString('accept', 'application/hal+json'),
			$this->get('webservices.content_types')
		);

		// Get the router.
		$router = Factory::getRouter(
			new Registry(['config_file' => JPATH_API . $this->get('webservices.routes')])
		);

		$match = $router->parseRoute($input->getString('path'), $input->getCmd('method', 'get'));

		$style = $match['controller']['style'];
		$resourceName = $match['controller']['resource'];
		$input->set('optionName', $resourceName);

		// Add parsed variables from the route into the input object.
		foreach ($match['vars'] as $key => $value)
		{
			$input->set($key, $value);
		}

		$options = array(
			'viewName'          => $input->getString('view'),
			'data'              => $this->getPostedData(),
			'dataGet'           => $input->getArray(),
			'accessToken'       => $input->getString($this->get('webservices.oauth2_token_param_name', 'access_token')),
			'absoluteHrefs'     => $input->getBool('absoluteHrefs', false),
		);

		$rendererOptions = [
			'charset'		=> 'utf-8',
			'language'		=> 'en-GB',
			'direction'		=> 'ltr',
			'link'			=> '',
			'base'			=> '',
			'absoluteHrefs'	=> $input->getBool('absoluteHrefs', false),
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
				->render();
		}
		catch (\Exception $e)
		{
			// @TODO Generally, application errors should be handled by the API object,
			// so the following code should probably be moved into the API class.
			$code = $e->getCode() > 0 ? $e->getCode() : 500;

			// Set the server response code.
			$this->setHeader('Status', $code . ' ' . $e->getMessage(), true);

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
					), JSON_UNESCAPED_SLASHES
				)
			);
		}

		// If user entered --sendHeaders then output headers.
		if ($this->input->getBool('sendHeaders'))
		{
			$this->sendHeaders();
		}

		// Output the message body.
		echo $this->getBody();
	}

	/**
	 * Output some helpful information.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function help()
	{
		// @TODO Language strings.

		$this->out('Usage:');
		$this->out();
		$this->out('php cli.php <args>');
		$this->out();
		$this->out('--api=<apiName>');
		$this->out('       API application style.  Can be either "rest" or "soap".');
		$this->out();
		$this->out('--method=<httpMethod>');
		$this->out('       Equivalent HTTP method.  Can be "GET", "PUT", "POST", "PATCH" or "DELETE".');
		$this->out();
		$this->out('--option=<componentName>');
		$this->out('       Specifies the Joomla component name.');
		$this->out();
		$this->out('--accept=<contentTypes>');
		$this->out('       Specifies the list of acceptable content types and their priorities.');
		$this->out();
		$this->out('--absoluteHrefs=<absoluteHrefs>');
		$this->out('       Determines whether hrefs will be absolute or relative.  Absolute hrefs don\'t make much');
		$this->out('       sense for a CLI script so you will probably just omit this option, which defaults to "0"');
		$this->out();
		$this->out('--sendHeaders');
		$this->out('       Prepend the output with HTTP headers');
		$this->out();
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
	 *
	 * @throws  RuntimeException
	 *
	 * @see https://tools.ietf.org/html/rfc7231#section-5.3.2
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function negotiateContentType($accept = '', $priorities = array())
	{
		$mediaType = (new Negotiator)->getBest($accept, $priorities);

		if (is_null($mediaType))
		{
			// @TODO Better error handling.
			throw new \RuntimeException('406 Not acceptable');
		}

		return $mediaType->getValue();
	}

	/**
	 * Login authentication function.
	 *
	 * @param   \Joomla\Authentication\AuthenticationStrategyInterface[]  $strategies  Array of authentication strategies.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function login($strategies)
	{
		$authenticate = new Authentication;

		foreach ($strategies as $name => $strategy)
		{
			$authenticate->addStrategy($name, $strategy);
		}

		return $authenticate->authenticate();
	}

	/**
	 * Logout authentication function.
	 *
	 * @param   integer  $userid  The user to load - Can be an integer or string - If string, it is converted to ID automatically.
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
	 * Get body content.
	 *
	 * @return  string
	 */
	public function getBody()
	{
		return $this->responseBody;
	}

	/**
	 * Set body content.  If body content already defined, this will replace it.
	 *
	 * @param   string  $content  The content to set as the response body.
	 *
	 * @return  AbstractWebApplication  Instance of $this to allow chaining.
	 *
	 * @since   1.0
	 */
	public function setBody($content)
	{
		$this->responseBody = (string) $content;

		return $this;
	}

	/**
	 * Method to set a response header.  If the replace flag is set then all headers
	 * with the given name will be replaced by the new one.  The headers are stored
	 * in an internal array to be sent when the site is sent to the browser.
	 *
	 * @param   string   $name     The name of the header to set.
	 * @param   string   $value    The value of the header to set.
	 * @param   boolean  $replace  True to replace any headers with the same name.
	 *
	 * @return  AbstractWebApplication  Instance of $this to allow chaining.
	 *
	 * @since   1.0
	 */
	public function setHeader($name, $value, $replace = false)
	{
		// Sanitize the input values.
		$name = (string) $name;
		$value = (string) $value;

		// If the replace flag is set, unset all known headers with the given name.
		if ($replace)
		{
			foreach ($this->responseHeaders as $key => $header)
			{
				if ($name == $header['name'])
				{
					unset($this->responseHeaders[$key]);
				}
			}

			// Clean up the array as unsetting nested arrays leaves some junk.
			$this->responseHeaders = array_values($this->responseHeaders);
		}

		// Add the header to the internal array.
		$this->responseHeaders[] = array('name' => $name, 'value' => $value);

		return $this;
	}

	/**
	 * Send the response headers.
	 *
	 * @return  CliApplication  Instance of $this to allow chaining.
	 *
	 * @since   1.0
	 */
	public function sendHeaders()
	{
		foreach ($this->responseHeaders as $header)
		{
			$this->out($header['name'] . ': ' . $header['value']);
		}

		return $this;
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
