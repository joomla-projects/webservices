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
use Joomla\Webservices\Resource\ResourceLink;
use Joomla\Webservices\Resource\Resource;
use Joomla\Webservices\Type\TypeUrn;
use Joomla\Webservices\Webservices\Webservice;
use Joomla\Webservices\Webservices\Factory;

use Joomla\DI\Container;
use Joomla\Event\Event;
use Joomla\Event\EventImmutable;
use Joomla\Input\Input;
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
	 * @param   Input  $input  An input object.
	 *
	 * @return  mixed  Webservice object with information on success, boolean false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  \Exception
	 */
	public function execute(Input $input)
	{
		$container	= $this->getContainer();
		$options	= $this->getOptions();

		$method		= strtoupper($input->getCmd('method', 'get'));
		$task		= $input->getCmd('task', '');
		$clientName	= $input->getString('webserviceClient', 'site');
		$version	= $input->getString('webserviceVersion');
		$resourceName = $input->getString('optionName');
		$linkedResourceName  = $input->getString('resource');

		$this->setOption('webserviceClient', $clientName);
		$this->setOption('webserviceVersion', $version);
		$this->setOption('optionName', $resourceName);

		$operation = 'read';

		// Map HTTP methods to service operations.
		switch (strtolower($method))
		{
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

		$this->setOption('operation', $operation);

		// If task is pointing to some other operation like apply, update or delete.
		if (!empty($task) && !empty($this->configuration->operations->task->{$task}['useOperation']))
		{
			$useOperation = strtoupper((string) $this->configuration->operations->task->{$task}['useOperation']);

			if (in_array($useOperation, array('create', 'read', 'update', 'delete', 'documentation')))
			{
				$operation = $useOperation;
			}
		}

		// Get the Profile object for the webservice requested.
		$this->profile = Factory::getProfile($container->get('db'), $clientName, $resourceName, $version, $operation);

		// Build a webservice object to handle the request.
		$this->webservice = Factory::getWebservice($container, $operation, $this->getOptions());

		// We do not want some unwanted text to appear before output.
		ob_start();

		try
		{
			// Execute the web service operation.
			$this->resource = $this->webservice->execute($this->profile);

			// Are we being asked for a resource linked from this one?
			if ($linkedResourceName != '')
			{
				$this->resource = $this->getLinkedResource($linkedResourceName, $clientName, $version);
			}

			$executionErrors = ob_get_contents();
			ob_end_clean();
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
	 * Get a resource that is linked to the current resource.
	 *
	 * This is used to handle routes like "/categories/:id/contacts" where the categories
	 * resource with the given id is retrieved first, then this is used to determine
	 * the contacts resource to return.
	 *
	 * @param   string  $linkedResourceName  Name of the linked resource to retrieve.
	 * @param   string  $clientName          Name of the client (eg. 'site' or 'administrator').
	 * @param   string  $version             Version of the webservice.
	 *
	 * @return  Resource
	 */
	private function getLinkedResource($linkedResourceName, $clientName, $version)
	{
		// Get resource data.
		$data = $this->resource->getData(true);

		$linkedResources = $this->profile->getSubprofile('item')->resources;
		$linkField = '';

		foreach ($linkedResources->children() as $linkedResource)
		{
			// We only want properties that are links.
			if ($linkedResource['displayGroup'] != '_links')
			{
				continue;
			}

			// We only want the link with the matching name.
			if ($linkedResource['displayName'] == $linkedResourceName)
			{
				$linkField = (string) $linkedResource['linkField'];

				break;
			}
		}

		// Get the Profile object for the webservice requested.
		$profile = Factory::getProfile($this->getContainer()->get('db'), $clientName, $linkedResourceName, $version, 'read');

		// Clone the options from the source resource and save the resource name for later.
		$options = clone $this->getOptions();
		$resourceName = $options->get('optionName');

		// Decode the URN which is the id of the resource we're linking from.
		$urn = TypeUrn::fromInternal($data['id']);

		// Check that the URN is of the expected type.
		if ($urn->getType() != $resourceName)
		{
			throw new RuntimeException('URN is not of the expected type \'' . $resourceName . '\': ' . $data['id']);
		}

		// Set the options up for retrieving the linked resource.
		$options->set('optionName', $linkedResourceName);
		$options->set('dataGet', ['filter' => [$linkField => $urn->getId()]]);

		// Build a webservice object to handle the request.
		$webservice = Factory::getWebservice($this->getContainer(), 'read', $options);

		// Execute the web service operation.
		$resource = $webservice->execute($profile);

		// We need to overwrite the self link for a linked resource.
		$resource->setLink(new ResourceLink('/' . $resourceName . '/' . $urn->getId() . '/' . $linkedResourceName), true);

		return $resource;
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
		if ($this->resource instanceof Resource)
		{
			$this->app->setBody(
				$this->getContainer()->get('renderer')->render($this->resource)
			);
		}
	}
}
