<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Webservices;

use Joomla\Webservices\Resource\Resource;
use Joomla\Webservices\Resource\ResourceItem;
use Joomla\Webservices\Resource\ResourceLink;
use Joomla\Webservices\Api\Hal\Transform\TransformInterface;
use Joomla\Webservices\Webservices\Exception\ConfigurationException;
use Joomla\Webservices\Webservices\Exception\KeyNotFoundException;
use Joomla\Webservices\Xml\XmlHelper;
use Joomla\Webservices\Layout\LayoutHelper;

use Joomla\Utilities\ArrayHelper;
use Joomla\DI\Container;
use Joomla\Event\Event;
use Joomla\Event\EventImmutable;
use Joomla\Registry\Registry;
use Joomla\Webservices\Uri\Uri;

/**
 * Class to execute webservice operations.
 *
 * @since  1.2
 */
abstract class Webservice extends WebserviceBase
{
	/**
	 * Webservice element name
	 * @var string
	 */
	public $elementName = null;

	/**
	 * @var    string  Name of the Client
	 * @since  1.2
	 */
	public $client = '';

	/**
	 * Name of the Webservice.  In REST terms this
	 * would be the name of the resource.
	 *
	 * @var    string  Name of the Webservice
	 * @since  1.2
	 */
	public $webserviceName = '';

	/**
	 * @var    string  Version of the Webservice
	 * @since  1.2
	 */
	public $webserviceVersion = '';

	/**
	 * @var    string  Folder path of the webservice
	 * @since  1.2
	 */
	public $webservicePath = '';

	/**
	 * @var    array  Installed webservice options
	 * @since  1.2
	 */
	public $webservice = '';

	/**
	 * For easier access of current configuration parameters
	 * @var  \SimpleXMLElement
	 */
	public $operationConfiguration = null;

	/**
	 * Main HAL resource object
	 * @var  Resource
	 */
	public $resource = null;

	/**
	 * Resource container that will be outputted
	 * @var array
	 */
	public $resources = array();

	/**
	 * Data container that will be used for resource binding
	 * @var array
	 */
	public $data = array();

	/**
	 * Uri parameters that will be added to each link
	 * @var array
	 */
	public $uriParams = array();

	/**
	 * @var    \SimpleXMLElement  Api Configuration
	 * @since  1.2
	 */
	public $configuration = null;

	/**
	 * @var    string  Rendered Documentation
	 * @since  1.2
	 */
	public $documentation = '';

	/**
	 * @var    string  Option name (optional)
	 * @since  1.3
	 */
	public $optionName = '';

	/**
	 * @var    string  View name (optional)
	 * @since  1.3
	 */
	public $viewName = '';

	/**
	 * @var    string  Authorization check method
	 * @since  1.4
	 */
	public $permissionCheck = 'joomla';

	/**
	 * Integration objects
	 *
	 * @var  array
	 */
	private $integration = array();

	/**
	 * Profile object.
	 */
	public $profile = null;

	/**
	 * Method to instantiate the file-based api call.
	 *
	 * @param   Container  $container  The DIC object
	 * @param   Registry   $options    Custom options to load.
	 *
	 * @throws  \Exception
	 * @since   1.2
	 */
	public function __construct(Container $container, Registry $options)
	{
		parent::__construct($container, $options);

		$this->client = $options->get('webserviceClient', 'administrator');
		$this->webserviceVersion = $options->get('webserviceVersion', '');
		$this->webserviceName = $options->get('optionName');

		if (!empty($this->webserviceName))
		{
			/** @var \Joomla\Database\DatabaseDriver $db */
			$db = $container->get('db');

			if (empty($this->webserviceVersion))
			{
				$this->webserviceVersion = ConfigurationHelper::getNewestWebserviceVersion($this->client, $this->webserviceName, $db);
			}

			$this->webservice = ConfigurationHelper::getInstalledWebservice($this->client, $this->webserviceName, $this->webserviceVersion, $db);

			if (empty($this->webservice))
			{
				throw new \Exception($this->text->sprintf('LIB_WEBSERVICES_API_HAL_WEBSERVICE_NOT_INSTALLED', $this->webserviceName, $this->webserviceVersion));
			}

			if (empty($this->webservice['published']))
			{
				throw new \Exception($this->text->sprintf('LIB_WEBSERVICES_API_HAL_WEBSERVICE_UNPUBLISHED', $this->webserviceName, $this->webserviceVersion));
			}

			$this->webservicePath = $this->webservice['path'];

			try
			{
				$this->configuration = ConfigurationHelper::loadWebserviceConfiguration(
					$this->webserviceName,
					$this->webserviceVersion,
					$this->webservicePath,
					$this->client
				);
			}
			catch (ConfigurationException $e)
			{
				throw new \RuntimeException($this->text->translate('LIB_WEBSERVICES_API_HAL_WEBSERVICE_CONFIGURATION_FILE_UNREADABLE'), 500, $e);
			}

			// Set option and view name
			$this->setOptionViewName($this->webserviceName, $this->configuration);

			// Set base data
			$this->setBaseDataValues();
		}

		// Set initial status code
		$this->setStatusCode($this->statusCode);

		// Check for defined constants
		if (!defined('JSON_UNESCAPED_SLASHES'))
		{
			define('JSON_UNESCAPED_SLASHES', 64);
		}

		// OAuth2 check
		if ($this->app->get('webservices.webservices_permission_check', 1) == 0)
		{
			$this->permissionCheck = 'scope';
		}
		elseif ($this->app->get('webservices.webservices_permission_check', 1) == 1)
		{
			$this->permissionCheck = 'joomla';
		}
	}

	/**
	 * Load the model object from the integration layer.
	 *
	 * @param   string             $elementName    The element to load
	 * @param   \SimpleXMLElement  $configuration  The configuration for the current task
	 *
	 * @return  mixed
	 */
	public function loadModel($elementName, $configuration)
	{
		$this->setOptionViewName($elementName, $configuration);

		/** @var \Joomla\Webservices\Integrations\Joomla\Joomla $integrationObject */
		$integrationObject = $this->getIntegrationObject();

		return $integrationObject->loadModel($elementName, $configuration);
	}

	/**
	 * Sets default Base Data Values for resource binding
	 *
	 * @return  Api
	 *
	 * @since   1.4
	 */
	public function setBaseDataValues()
	{
		$webserviceUrlPath = '/index.php?option=' . $this->optionName;

		if (!empty($this->viewName))
		{
			$webserviceUrlPath .= '&amp;view=' . $this->viewName;
		}

		if (!empty($this->webserviceVersion))
		{
			$webserviceUrlPath .= '&amp;webserviceVersion=' . $this->webserviceVersion;
		}

		$webserviceUrlPath .= '&amp;webserviceClient=' . $this->client;

		$this->data['webserviceUrlPath'] = $webserviceUrlPath;
		$this->data['webserviceName'] = $this->webserviceName;
		$this->data['webserviceVersion'] = $this->webserviceVersion;

		return $this;
	}

	/**
	 * Execute the Api Documentation operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 * @deprecated Move to separate Webservices/Documentation.php file.
	 */
	public function apiDocumentation()
	{
		$currentConfiguration = $this->configuration;
		$documentationNone = false;

		if ($this->operationConfiguration['source'] == 'url')
		{
			if (!empty($this->operationConfiguration['url']))
			{
				$this->app->redirect($this->operationConfiguration['url']);
				$this->app->close();
			}

			$documentationNone = true;
		}

		if ($this->operationConfiguration['source'] == 'none' || $documentationNone)
		{
			$currentConfiguration = null;
		}

		$dataGet = $this->getOptions()->get('dataGet', array());

		$this->documentation = LayoutHelper::render(
			'webservice.documentation',
			array(
				'view' => $this,
				'options' => array (
					'xml' => $currentConfiguration,
					'soapEnabled' => $this->app->get('webservices.enable_soap', 0),
					'print' => isset($dataGet->print)
				),
				'text' => $this->text
			),
			JPATH_TEMPLATES
		);

		return $this;
	}

	/**
	 * Execute the Api Task operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 * @deprecated Move to separate Webservices/Task.php file.
	 */
	public function apiTask()
	{
		$task = $this->getOptions()->get('task', '');
		$result = false;

		if (!empty($task))
		{
			// Load resources directly from task group
			if (!empty($this->operationConfiguration->{$task}->resources))
			{
				$this->profile->getResources($this->getOptions());
			}

			$taskConfiguration = !empty($this->operationConfiguration->{$task}) ?
				$this->operationConfiguration->{$task} : $this->operationConfiguration;

			$model = $this->triggerFunction('loadModel', $this->elementName, $taskConfiguration);
			$functionName = XmlHelper::attributeToString($taskConfiguration, 'functionName', $task);
			$data = $this->profile->bindData((array) $this->getOptions()->get('data', array()));

			$data = $this->triggerFunction('validatePostData', $model, $data, $taskConfiguration);

			if ($data === false)
			{
				// Not Acceptable
				$this->setStatusCode(406);
				$this->triggerFunction('displayErrors', $model);
				$this->setData('result', $data);

				return;
			}

			// Prepare parameters for the function
			$args = $this->profile->buildFunctionArgs($data);
			$result = null;

			// Checks if that method exists in model class and executes it
			if (method_exists($model, $functionName))
			{
				$result = $this->triggerCallFunction($model, $functionName, $args);
			}
			else
			{
				$this->setStatusCode(400);
			}

			if (method_exists($model, 'getState'))
			{
				$this->setData('id', $model->getState(strtolower($this->elementName) . '.id'));
			}

			$this->triggerFunction('displayErrors', $model);
		}

		$this->setData('result', $result);
	}

	/**
	 * Binds data to a Resource using a profile for a specific method or task.
	 *
	 * The $profile array comes from the Profile XML and may have been filtered.
	 * The binding can be restricted to a particular scope.  The default scope is global.
	 *
	 * @param   Resource  $resource  Resource document for binding the resource.
	 * @param   array     $profile   Configuration for displaying object.
	 * @param   mixed     $data      Data to bind to the resource.
	 * @param   string    $scope     Scope specified by the resourceSpecific attribute in the profile.
	 *
	 * @return  Resource
	 */
	public function setDataValueToResource(Resource $resource, array $profile, $data, $scope = 'global')
	{
		// No properties to add to the Resource from this scope.
		if (empty($profile[$scope]))
		{
			return $resource;
		}

		// Add properties to the Resource from the data provided.
		foreach ($profile[$scope] as $profileItem)
		{
			// If no displayGroup was specified, then the data is added to the Resource as a top-level property.
			if (empty($profileItem['displayGroup']))
			{
				$resource->setData(
					$this->assignGlobalValueToResource($profileItem['displayName']),
					$this->assignValueToResource($profileItem, $data)
				);

				continue;
			}

			// Deal with links separately.
			if ($profileItem['displayGroup'] == '_links')
			{
				$linkRel = !empty($profileItem['linkRel']) ? $profileItem['linkRel'] : $this->assignGlobalValueToResource($profileItem['displayName']);

				// We will force curies as link array.
				$linkPlural = $linkRel == 'curies';

				// Create a link resource.
				$linkResource = new ResourceLink(
					$this->assignValueToResource($profileItem, $data),				// href
					$linkRel,														// rel
					$profileItem['linkTitle'],										// title
					$this->assignGlobalValueToResource($profileItem['linkName']),	// name
					$profileItem['hrefLang'],										// hreflang
					XmlHelper::isAttributeTrue($profileItem, 'linkTemplated')		// templated
				);

				// Add link to this resource.
				$resource->setLink($linkResource, false, $linkPlural);

				continue;
			}

			// Add data to a top-level group (other than _links).
			$resource->setDataGrouped(
				$profileItem['displayGroup'],
				$this->assignGlobalValueToResource($profileItem['displayName']),
				$this->assignValueToResource($profileItem, $data)
			);
		}

		return $resource;
	}

	/**
	 * Resets specific Resource list or all Resources
	 *
	 * @param   string  $resourceSpecific  Resource specific string that separates resources
	 *
	 * @return  $this
	 */
	public function resetDocumentResources($resourceSpecific = '')
	{
		if (!empty($resourceSpecific))
		{
			if (isset($this->resources[$resourceSpecific]))
			{
				unset($this->resources[$resourceSpecific]);
			}

			return $this;
		}

		$this->resources = array();

		return $this;
	}

	/**
	 * Used for ordering arrays
	 *
	 * @param   string  $a  Current array
	 * @param   string  $b  Next array
	 *
	 * @return  $this
	 */
	public function sortResourcesByDisplayGroup($a, $b)
	{
		$sort = strcmp($a["displayGroup"], $b["displayGroup"]);

		if (!$sort)
		{
			return ($a['original_order'] < $b['original_order'] ? -1 : 1);
		}

		return $sort;
	}

	/**
	 * Method to fill response with requested data
	 *
	 * @param   array  $data  Data to set to Hal document if needed
	 *
	 * @return  string  Api call output
	 *
	 * @since   1.2
	 * @deprecated
	 */
	public function getBody($data = array())
	{
		// Add data
		$data = null;

		if (!empty($data))
		{
			foreach ($data as $k => $v)
			{
				$this->resource->$k = $v;
			}
		}

		return $this->resource;
	}

	/**
	 * Prepares body for response
	 *
	 * @param   string  $message  The return message
	 *
	 * @return  string	The message prepared
	 *
	 * @since   1.2
	 */
	public function prepareBody($message)
	{
		return $message;
	}

	/**
	 * Saves data into the global data buffer.
	 *
	 * @param   string  $key   Rel element
	 * @param   mixed   $data  Data for the resource
	 *
	 * @return  $this
	 */
	public function setData($key, $data = null)
	{
		if (is_array($key) && null === $data)
		{
			foreach ($key as $k => $v)
			{
				$this->data[$k] = $v;
			}
		}
		else
		{
			$this->data[$key] = $data;
		}

		return $this;
	}

	/**
	 * Set the Uri parameters
	 *
	 * @param   string  $uriKey    Uri Key
	 * @param   string  $uriValue  Uri Value
	 *
	 * @return  $this
	 */
	public function setUriParams($uriKey, $uriValue)
	{
		$this->uriParams[$uriKey] = $uriValue;

		return $this;
	}

	/**
	 * Validates posted data
	 *
	 * @param   object             $model          Model
	 * @param   array              $data           Raw Posted data
	 * @param   \SimpleXMLElement  $configuration  Configuration for displaying object
	 *
	 * @return  mixed  Array with posted data or false.
	 *
	 * @since   1.3
	 */
	public function validatePostData($model, $data, $configuration)
	{
		$data = (array) $data;

		// Get required fields without values.
		$requiredFields = $this->profile->checkRequiredFields($data);

		// Queue an error for each required field without a value.
		if (!empty($requiredFields))
		{
			foreach ($requiredFields as $fieldName)
			{
				$this->app->enqueueMessage(
					$this->text->sprintf('LIB_WEBSERVICES_API_HAL_WEBSERVICE_ERROR_REQUIRED_FIELD', $fieldName), 'error'
				);
			}

			return false;
		}

		$validateMethod = strtolower(XmlHelper::attributeToString($configuration, 'validateData', 'none'));

		if ($validateMethod == 'none')
		{
			return $data;
		}

		if ($validateMethod == 'form')
		{
			if (method_exists($model, 'getForm'))
			{
				// Validate the posted data.
				// Sometimes the form needs some posted data, such as for plugins and modules.
				$form = $model->getForm($data, false);

				if (!$form)
				{
					return $data;
				}

				// Test whether the data is valid.
				$validData = $model->validate($form, $data);

				// Common functions are not checking this field so we will
				$validData['params'] = isset($validData['params']) ? $validData['params'] : null;
				$validData['associations'] = isset($validData['associations']) ? $validData['associations'] : array();

				return $validData;
			}

			$this->app->enqueueMessage($this->text->translate('LIB_WEBSERVICES_API_HAL_WEBSERVICE_FUNCTION_DONT_EXIST'), 'error');

			return false;
		}

		if ($validateMethod == 'function')
		{
			$validateMethod = strtolower(XmlHelper::attributeToString($configuration, 'validateDataFunction', 'validate'));

			if (method_exists($model, $validateMethod))
			{
				$result = $model->{$validateMethod}($data);

				return $result;
			}

			$this->app->enqueueMessage($this->text->translate('LIB_WEBSERVICES_API_HAL_WEBSERVICE_FUNCTION_DONT_EXIST'), 'error');

			return false;
		}

		return false;
	}

	/**
	 * Checks if operation is allowed from the configuration file
	 *
	 * @return object This method may be chained.
	 *
	 * @throws  \Exception
	 */
	public function isOperationAllowed()
	{
		// Check if webservice is published.
		$isPublished = ConfigurationHelper::isPublishedWebservice(
			$this->client,
			$this->webserviceName,
			$this->webserviceVersion,
			$this->getContainer()->get('db')
		);

		if (!$isPublished && !empty($this->webserviceName))
		{
			throw new \Exception($this->text->sprintf('LIB_WEBSERVICES_API_HAL_WEBSERVICE_IS_UNPUBLISHED', $this->webserviceName));
		}

		// Check for allowed operations
		$allowedOperations = $this->getConfig('operations');

		if (!isset($allowedOperations->{$this->operation}))
		{
			$this->setStatusCode(405);

			return false;
		}

		$scope = $this->operation;
		$authorizationGroups = !empty($allowedOperations->{$this->operation}['authorization']) ?
			(string) $allowedOperations->{$this->operation}['authorization'] : '';
		$terminateIfNotAuthorized = true;

		// Check if operation is available and check if it needs authorization
		if ($this->operation == 'task')
		{
			$task = $this->getOptions()->get('task', '');
			$scope .= '.' . $task;

			if (!isset($allowedOperations->task->{$task}))
			{
				$this->setStatusCode(405);

				return false;
			}

			$authorizationGroups = !empty($allowedOperations->task->{$task}['authorization']) ?
				(string) $allowedOperations->task->{$task}['authorization'] : '';

			if (isset($allowedOperations->task->{$task}['authorizationNeeded'])
				&& strtolower($allowedOperations->task->{$task}['authorizationNeeded']) == 'false')
			{
				$terminateIfNotAuthorized = false;
			}
		}
		elseif ($this->operation == 'read')
		{
			// Disable authorization on operation read level
			if (isset($allowedOperations->{$this->operation}['authorizationNeeded'])
				&& strtolower($allowedOperations->{$this->operation}['authorizationNeeded']) == 'false')
			{
				$terminateIfNotAuthorized = false;
			}
			else
			{
				// Get data from request.
				$data = (array) $this->getOptions()->get('dataGet', array());

				// Determine if the request if for an item or a list.
				$readType = $this->profile->isItem($data) ? 'item' : 'list';

				if (isset($allowedOperations->read->{$readType}['authorizationNeeded'])
					&& strtolower($allowedOperations->read->{$readType}['authorizationNeeded']) == 'false')
				{
					$terminateIfNotAuthorized = false;
				}
			}
		}
		elseif (isset($allowedOperations->{$this->operation}['authorizationNeeded'])
			&& strtolower($allowedOperations->{$this->operation}['authorizationNeeded']) == 'false')
		{
			$terminateIfNotAuthorized = false;
		}

		// Set scope for this webservice permission check
		$scopes = array(
			// Webservice scope
			$this->client . '.' . $this->webserviceName . '.' . $scope,
			// Global operation scope check
			$this->client . '.' . $this->operation
		);

		// Login user
		$loggedIn = $this->app->login($this->getIntegrationObject()->getStrategies());

		// Public access
		if (!$terminateIfNotAuthorized)
		{
			return true;
		}

		// If restricted access and user not logged in we exit
		if ($terminateIfNotAuthorized && !$loggedIn)
		{
			$this->setStatusCode(401);

			return false;
		}

		$eventData = array(
			'scopes' => $scopes,
			'options' => $this->getOptions(),
			'permissionCheck' => $this->permissionCheck,
			'authorized' => false
		);

		$event = new Event('JApiHalPermissionCheck', $eventData);
		$this->dispatcher->triggerEvent($event);

		// See if authorized variable has been changed
		$authorized = $event->getArgument('authorized', false);

		if ($authorized)
		{
			return $authorized;
		}

		// Does user have permission to access the webservice
		if ($this->permissionCheck == 'scope')
		{
			// @todo Implement a scope check instead of redCORE oauth2 scope check
			$authorized = false;
		}
		// Joomla permission check
		elseif ($this->permissionCheck == 'joomla')
		{
			$authorized = false;

			// Use Joomla to authorize
			if (!empty($authorizationGroups))
			{
				/** @var \Joomla\Webservices\Integrations\Joomla\Joomla $integration */
				$integration = $this->getIntegrationObject();
				$authorisation = $integration->getAuthorisation($loggedIn);

				$authorizationGroups = explode(',', $authorizationGroups);
				$configAssetName = !empty($this->configuration->config->authorizationAssetName) ?
					(string) $this->configuration->config->authorizationAssetName : null;

				foreach ($authorizationGroups as $authorizationGroup)
				{
					$authorization = explode(':', trim($authorizationGroup));
					$action = $authorization[0];
					$assetName = !empty($authorization[1]) ? $authorization[1] : $configAssetName;

					if ($authorisation->authorise(trim($action), trim($assetName)))
					{
						$authorized = true;
						break;
					}
				}
			}
			else
			{
				// If no authorization group is provided we will enable the access
				$authorized = true;
			}
		}

		if (!$authorized)
		{
			$this->setStatusCode(405);

			return false;
		}

		return true;
	}

	/**
	 * Sets option and view name
	 *
	 * @param   string             $elementName    Element name
	 * @param   \SimpleXMLElement  $configuration  Configuration for current action
	 *
	 * @return  void
	 *
	 * @since   1.3
	 */
	public function setOptionViewName($elementName, $configuration)
	{
		// Views are separated by dash
		$view = explode('-', $elementName);
		$elementName = $view[0];
		$viewName = '';

		if (!empty($view[1]))
		{
			$viewName = $view[1];
		}

		$optionName = !empty($configuration['optionName']) ? $configuration['optionName'] : $elementName;

		// Add com_ to the element name if not exist
		$optionName = (strpos($optionName, 'com_') === 0 ? '' : 'com_') . $optionName;

		$this->optionName = $optionName;
		$this->viewName = $viewName;
	}

	/**
	 * Checks if operation is allowed from the configuration file
	 *
	 * @param   string  $path  Path to the configuration setting
	 *
	 * @return mixed May return single value or array
	 */
	public function getConfig($path = '')
	{
		$path = explode('.', $path);
		$configuration = $this->configuration;

		foreach ($path as $pathInstance)
		{
			if (isset($configuration->{$pathInstance}))
			{
				$configuration = $configuration->{$pathInstance};
			}
		}

		return is_string($configuration) ? (string) $configuration : $configuration;
	}

	/**
	 * Gets errors from model and places it into Application message queue
	 *
	 * @param   object  $model  Model
	 *
	 * @return void
	 */
	public function displayErrors($model)
	{
		if (method_exists($model, 'getErrors'))
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up all validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n; $i++)
			{
				if ($errors[$i] instanceof \Exception)
				{
					/** @var \Exception $exception */
					$exception = $errors[$i];

					$this->app->enqueueMessage($exception->getMessage(), 'warning');
				}
				else
				{
					$this->app->enqueueMessage($errors[$i], 'warning');
				}
			}
		}
	}

	/**
	 * Assign value to Resource (really a property of a resource).
	 *
	 * Given a property (specified as an array of attributes taken from the profile) and some data,
	 * determine the value to be assigned to that property.  Also uses global data if required.
	 *
	 * Example:
	 *   <resource displayName="id" transform="int" fieldFormat="{id}" displayGroup="" resourceSpecific="rcwsGlobal"/>
	 *
	 *   $resource contains
	 *       ['displayName' => 'id', 'transform' => 'int', 'fieldFormat' => '{id}', 'displayGroup' => '', 'resourceSpecific' => 'rcwsGlobal']
	 *   $data contains an array|object containing all available data from some internal resource object.
	 *   $attribute contains 'fieldFormat'
	 *
	 *   Then this method will take the 'fieldFormat' from $resource, which is '{id}' and looks for a property called 'id'
	 *   in the $data.  It will then perform the substitution, using the 'transform' called 'int' to return the final value.
	 *
	 * @param   array  $resource  Array of resource property attribute key-value pairs.
	 * @param   mixed  $data      Data key-value pairs available for substitution in the data template.
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function assignValueToResource(array $resource, $data)
	{
		// Get the template from the profile.
		// This template defines the value to be returned.  eg. "{id}".
		$output = $template = $resource['fieldFormat'];

		$search = $replace = array();

		// Look for substitution codes in the template.
		$stringsToReplace = array();
		preg_match_all('/\{([^}]+)\}/', $template, $stringsToReplace);

		// Build search and replace arrays.
		foreach ($stringsToReplace[1] as $replacementKey)
		{
			// Look for the key in the data and get the value associated with it.
			try
			{
				$replacementValue = $this->getValueFromData($data, $replacementKey);
			}
			catch (KeyNotFoundException $e)
			{
				continue;
			}

			// If the key was found in the data, add to search and replace arrays.
			if (!is_null($replacementValue))
			{
				$search[] = '{' . $replacementKey . '}';
				$replace[] = $replacementValue;
			}
		}

		// Perform search and replace on the template.
		$output = str_replace($search, $replace, $template);

		// Look for substitutions from global data as well.
		$output = $this->assignGlobalValueToResource($output);

		// Transform internal format to external format.
		$output = $this->profile->transformField($resource['transform'], $output, true);

		return $output;
	}

	/**
	 * Get a value from various types of data.
	 *
	 * The key is used to locate a value in objects or arrays, which may be nested.
	 * For other data types (eg. string), the data is returned as its own value.
	 * The key may be a dot-separated path so that data can be retrieved from a
	 * hierarchy of array, object, JSON-encoded or simple data.
	 *
	 * @param   mixed   $data  Array, object, etc., containing the data.
	 * @param   string  $key   Property of the data to be retrieved.
	 *
	 * @return  Value associated with the key.
	 *
	 * @throws  KeyNotFoundException for objects or arrays when the key is not found.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function getValueFromData($data, $key)
	{
		// Copy the arguments so we don't accidentally cause side-effects.
		$innerData = $data;
		$innerKey = $key;

		// Get the parts of the key.
		$parts = explode('.', $innerKey);

		// If the key is not simple, break it down.
		if (count($parts) > 1)
		{
			// Look for the first part.
			$innerData = $this->getValueFromData($innerData, array_shift($parts));

			// If we are given a string, try JSON-decoding it.
			if (is_string($innerData))
			{
				$tmp = json_decode($innerData);

				if (json_last_error() === JSON_ERROR_NONE)
				{
					$innerData = $tmp;
				}
			}

			// Re-assemble the remainder of the key.
			$innerKey = implode('.', $parts);

			// If necessary, recurse further.
			if (count($parts) > 1)
			{
				return $this->getValueFromData($innerData, $innerKey);
			}
		}

		// Key is simple, data is an object.
		if (is_object($innerData) && property_exists($innerData, $innerKey))
		{
			return $innerData->{$innerKey};
		}

		// Key is simple, data is an array.
		if (is_array($innerData) && isset($innerData[$innerKey]))
		{
			return $innerData[$innerKey];
		}

		// Key is simple, but not found when data is object or array.
		if (is_object($innerData) || is_array($innerData))
		{
			throw new KeyNotFoundException($innerKey);
		}

		// Key is simple, data is simple.
		return $innerData;
	}

	/**
	 * Determine the value of a resource property using data from the global data buffer.
	 *
	 * The template contains substitution codes that determine which global data values
	 * will comprise the property value.  Note that this does not transform data,
	 * because global data has already been transformed.
	 *
	 * @param   string  $template  Template to parse. (eg. "{id}").
	 *
	 * @return  string value of the resource property using the template.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function assignGlobalValueToResource($template)
	{
		if (empty($template))
		{
			return $template;
		}

		$output = $template;

		$stringsToReplace = array();
		preg_match_all('/\{([^}]+)\}/', $template, $stringsToReplace);

		foreach ($stringsToReplace[1] as $replacementKey)
		{
			// Look for the key in the data and get the value associated with it.
			try
			{
				$replacementValue = $this->getValueFromData($this->data, $replacementKey);
			}
			catch (KeyNotFoundException $e)
			{
				continue;
			}

			$search = '{' . $replacementKey . '}';
			$output = str_replace($search, $replacementValue, $template);
		}

		return $output;
	}

	/**
	 * Calls method from helper file if exists or method from this class,
	 * Additionally it Triggers plugin call for specific function in a format JApiHalFunctionName
	 *
	 * @param   string  $functionName  Field type.
	 *
	 * @return mixed Result from callback function
	 */
	public function triggerFunction($functionName)
	{
		$version = $this->getOptions()->get('webserviceVersion', '');
		$apiHelperClass = Factory::getHelper($version, $this->client, $this->webserviceName, $this->webservicePath);
		$args = func_get_args();

		// Remove function name from arguments
		array_shift($args);

		// PHP 5.3 workaround
		$temp = array();

		foreach ($args as &$arg)
		{
			$temp[] = &$arg;
		}

		// We will add this instance of the object as last argument for manipulation in plugin and helper
		$temp[] = &$this;

		// @TODO: The event name should not be tied to HAL.
		$event = new Event('JApiHalBefore' . $functionName, $temp);
		$result = $this->dispatcher->triggerEvent($event);

		// Checks if that method exists in helper file and executes it
		if (method_exists($apiHelperClass, $functionName))
		{
			$result = call_user_func_array(array($apiHelperClass, $functionName), $temp);
		}
		else
		{
			$result = call_user_func_array(array($this, $functionName), $temp);
		}

		// @TODO: The event name should not be tied to HAL.
		$event = new EventImmutable('JApiHalAfter' . $functionName, $temp);
		$this->dispatcher->triggerEvent($event);

		return $result;
	}

	/**
	 * Calls method from defined object as some Joomla methods require referenced parameters
	 *
	 * @param   object  $object        Object to run function on
	 * @param   string  $functionName  Function name
	 * @param   array   $args          Arguments for the function
	 *
	 * @return mixed Result from callback function
	 */
	public function triggerCallFunction($object, $functionName, $args)
	{
		switch (count($args))
		{
			case 0:
				return $object->{$functionName}();
			case 1:
				return $object->{$functionName}($args[0]);
			case 2:
				return $object->{$functionName}($args[0], $args[1]);
			case 3:
				return $object->{$functionName}($args[0], $args[1], $args[2]);
			case 4:
				return $object->{$functionName}($args[0], $args[1], $args[2], $args[3]);
			case 5:
				return $object->{$functionName}($args[0], $args[1], $args[2], $args[3], $args[4]);
			default:
				return call_user_func_array(array($object, $functionName), $args);
		}
	}

	/**
	 * Loads the integration for the webservice
	 *
	 * @param   string  $name  Name of the integration.
	 *
	 * @return mixed
	 */
	public function getIntegrationObject($name = 'joomla')
	{
		$name  = ucFirst($name);

		if (isset($this->integration[$name]))
		{
			return $this->integration[$name];
		}

		$class = '\\Joomla\\Webservices\\Integrations\\' . $name . '\\' . $name;
		$this->integration[$name] = new $class($this->getContainer(), $this);

		return $this->integration[$name];
	}
}
