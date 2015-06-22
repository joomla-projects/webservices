<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Api;

use Joomla\Registry\Registry;

use Joomla\DI\Container;
use Joomla\DI\ContainerAwareTrait;
use Joomla\DI\ContainerAwareInterface;

/**
 * Interface to handle api calls
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.2
 */
abstract class ApiBase implements ApiInterface, ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * Options object
	 *
	 * @var    Registry
	 * @since  1.2
	 */
	public $options = null;

	/**
	 * Debug information messages
	 *
	 * @var    array
	 * @since  1.2
	 */
	protected $debugMessages = array();

	/**
	 * @var    int  Status code for current api call
	 * @since  1.2
	 */
	public $statusCode = 200;

	/**
	 * @var    string  Status text for current api call
	 * @since  1.2
	 */
	public $statusText = '';

	/**
	 * Standard status codes for RESTfull api
	 *
	 * @var    array
	 * @since  1.2
	 */
	public static $statusTexts = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
	);

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  The DIC object
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(Container $container)
	{
		$this->setContainer($container);
	}

	/**
	 * Set the options
	 *
	 * @param   mixed  $options  Array / JRegistry object with the options to load
	 *
	 * @return  $this
	 */
	public function setOptions($options = null)
	{
		// Received JRegistry
		if ($options instanceof Registry)
		{
			$this->options = $options;
		}
		// Received array
		elseif (is_array($options))
		{
			$this->options = new Registry($options);
		}
		else
		{
			$this->options = new Registry;
		}

		return $this;
	}

	/**
	 * Set status code for current api call
	 *
	 * @param   int     $statusCode  Status code
	 * @param   string  $text        Text to replace default api message
	 *
	 * @throws  \Exception
	 * @return  $this
	 */
	public function setStatusCode($statusCode, $text = null)
	{
		$this->statusCode = (int) $statusCode;
		$this->statusText = false === $text ? '' : (null === $text ? self::$statusTexts[$this->statusCode] : $text);

		if ($this->isInvalid())
		{
			throw new \Exception($this->getContainer()->get('Joomla\\Language\\LanguageFactory')->getText()->sprintf('LIB_WEBSERVICES_API_STATUS_CODE_INVALID', $statusCode));
		}

		return $this;
	}

	/**
	 * Checks if status code is invalid according to RFC specification http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	 *
	 * @return boolean
	 *
	 * @api
	 */
	public function isInvalid()
	{
		return $this->statusCode < 100 || $this->statusCode >= 600;
	}

	/**
	 * Get the options
	 *
	 * @return  Registry  Object with the options
	 *
	 * @since   1.2
	 */
	public function getOptions()
	{
		// Always return a JRegistry instance
		if (!($this->options instanceof Registry))
		{
			$this->resetOptions();
		}

		return $this->options;
	}

	/**
	 * Set the option
	 *
	 * @param   string  $key    Key on which to store the option
	 * @param   mixed   $value  Value of the option
	 *
	 * @return  $this
	 *
	 * @since   1.2
	 */
	public function setOption($key, $value)
	{
		$this->getOptions();
		$this->options->set($key, $value);

		return $this;
	}

	/**
	 * Function to empty all the options
	 *
	 * @return  $this
	 *
	 * @since   1.2
	 */
	public function resetOptions()
	{
		return $this->setOptions(null);
	}

	/**
	 * Get the debug messages array
	 *
	 * @return  array
	 *
	 * @since   1.2
	 */
	public function getDebugMessages()
	{
		return $this->debugMessages;
	}

	/**
	 * Render the list of debug messages
	 *
	 * @return  string  Output text/HTML code
	 *
	 * @since   1.2
	 */
	public function renderDebugMessages()
	{
		return implode($this->debugMessages, "\n");
	}

	/**
	 * Add a debug message to the debug messages array
	 *
	 * @param   string  $message  Message to save
	 *
	 * @return  void
	 *
	 * @since   1.2
	 */
	public function addDebugMessage($message)
	{
		$this->debugMessages[] = $message;
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
		$this->setOption('debug', (boolean) $debug);
	}
}
