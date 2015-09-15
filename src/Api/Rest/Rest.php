<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Api\Rest;

use Joomla\Webservices\Api\ApiBase;
use Joomla\Webservices\Resource\Item;
use Joomla\Webservices\Webservices\Webservice;
use Joomla\Webservices\Webservices\Factory;

use Joomla\DI\Container;
use Joomla\Event\Event;
use Joomla\Event\EventImmutable;
use Joomla\Registry\Registry;

/**
 * Class to represent a REST interaction style.
 *
 * @since  1.2
 */
class Rest extends ApiBase
{
	/**
	 * @var    string  Name of the Api
	 * @since  1.2
	 */
	public $apiName = 'rest';

	/**
	 * Profile object.
	 * 
	 * @var  Profile
	 */
	private $profile = null;

	/**
	 * Main resource object
	 * @var  Resource
	 */
	public $resource = null;

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  The Dependency Injection Container object.
	 * @param   Registry   $options    Optional custom options to load.
	 *
	 * @throws  \Exception
	 * 
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(Container $container, Registry $options)
	{
		parent::__construct($container, $options);

		// Set initial status code.
		$this->setStatusCode(200);
	}

	/**
	 * Execute the Api operation.
	 *
	 * @return  mixed  Webservice object with information on success, boolean false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  \Exception
	 */
	public function execute()
	{
		$options	= $this->getOptions();

		$method		= $options->get('method', 'GET');
		$task		= $options->get('task', '');
		$format		= $options->get('format', '');
		$clientName	= $options->get('webserviceClient');
		$version	= $options->get('webserviceVersion');
		$resourceName = $options->get('optionName');

		// Map HTTP methods to service operations.
		switch (strtolower($method))
		{
			case 'get':
				$operation = !empty($task) ? 'task' : 'read';
				break;

			case 'put':
				$operation = 'update';
				break;

			case 'post':
				$operation = !empty($task) ? 'task' : 'create';
				break;

			case 'patch':
				$operation = 'patch';
				break;

			case 'delete':
				$operation = 'delete';
				break;
		}

		// If task is pointing to some other operation like apply, update or delete.
//		if (!empty($task) && !empty($this->configuration->operations->task->{$task}['useOperation']))
//		{
//			$useOperation = strtoupper((string) $this->configuration->operations->task->{$task}['useOperation']);
//
//			if (in_array($useOperation, array('create', 'read', 'update', 'delete', 'documentation')))
//			{
//				$operation = $useOperation;
//			}
//		}

		// Get the Profile object for the webservice requested.
		$this->profile = Factory::getProfile($this->getContainer(), $clientName, $resourceName, $version, $operation);

		$this->webservice = Factory::getWebservice($this->getContainer(), $operation, $this->getOptions());

		// Set initial status code to OK.
		$this->setStatusCode(200);

		// We do not want some unwanted text to appear before output.
		ob_start();

		try
		{
			// Execute the web service operation.
			$this->resource = $this->webservice->execute($this->profile);

			$executionErrors = ob_get_contents();
			ob_end_clean();
//			ob_end_flush();		// TEMPORARY so we can see any error messages.
		}
		catch (\Exception $e)
		{
			$executionErrors = ob_get_contents();
			ob_end_clean();

			throw $e;
		}

		$messages = $this->app->getMessageQueue();

		if (!empty($executionErrors))
		{
			$messages[] = array('message' => $executionErrors, 'type' => 'notice');
		}

		if (!empty($messages))
		{
			// If we are not in debug mode we will take out everything except errors.
			if ($this->app->get('webservices.debug_webservices', 0) == 0)
			{
				foreach ($messages as $key => $message)
				{
					if ($message['type'] != 'error')
					{
						unset($messages[$key]);
					}
				}
			}

			$this->resource->setData('_messages', $messages);
		}

		return $this;
	}

	/**
	 * Method to send the application response to the client.
	 * All headers will be sent prior to the main application output data.
	 *
	 * @return  void
	 *
	 * @since   1.2
	 */
	public function render()
	{
		// Set token to uri if used in that way
		$token = $this->getOptions()->get('accessToken', '');
		$client = $this->getOptions()->get('webserviceClient', '');

		if (!empty($token))
		{
			$this->webservice->setUriParams($this->app->get('webservices.oauth2_token_param_name', 'access_token'), $token);
		}

		if ($client == 'administrator')
		{
			$this->webservice->setUriParams('webserviceClient', $client);
		}

		// Calculate runtime to this point.
		$runtime = microtime(true) - $this->app->startTime;

		// Set headers.
		$this->app->setHeader('Status', $this->webservice->statusCode . ' ' . $this->webservice->statusText, true);
		$this->app->setHeader('Server', '', true);
		$this->app->setHeader('X-Runtime', $runtime, true);
		$this->app->setHeader('Access-Control-Allow-Origin', '*', true);
		$this->app->setHeader('Pragma', 'public', true);
		$this->app->setHeader('Expires', '0', true);
		$this->app->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
		$this->app->setHeader('Cache-Control', 'private', false);
		$this->app->setHeader('Webservice-Name', $this->webservice->webserviceName, true);
		$this->app->setHeader('Webservice-Version', $this->webservice->webserviceVersion, true);

		// Push results into the document.
		$this->app->setBody(
			$this->getContainer()->get('renderer')->render($this->resource)
		);
	}
}
