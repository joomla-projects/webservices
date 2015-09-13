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
use Joomla\Webservices\Xml\XmlHelper;

/**
 * Class to execute webservice operations.
 *
 * @since  1.2
 */
class Delete extends Webservice
{
	/**
	 * Execute the Api operation.
	 * 
	 * @param   Resource  $resource  A Resource object to be populated.
	 *
	 * @return  Resource  The populated Resource object.
	 *
	 * @since   1.2
	 * @throws  \Exception
	 */
	public function execute(Resource $resource)
	{
		$this->resource = $resource;

		// Check we have permission to perform this operation.
		if (!$this->triggerFunction('isOperationAllowed'))
		{
			return false;
		}

		$this->elementName = ucfirst(strtolower((string) $this->getConfig('config.name')));
		$this->operationConfiguration = $this->getConfig('operations.' . strtolower($this->operation));
		$this->triggerFunction('apiDelete');

		// Set links from resources to the main document
		$this->setDataValueToResource($this->resource, $this->resources, $this->data);

		return $this->resource;
	}

	/**
	 * Execute the Api Delete operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	public function apiDelete()
	{
		// Get resource list from configuration
		$this->getResourceProfile($this->operationConfiguration);

		// Delete function requires references and not values like we use in call_user_func_array so we use List delete function
		$model = $this->triggerFunction('loadModel', $this->elementName, $this->operationConfiguration);
		$functionName = XmlHelper::attributeToString($this->operationConfiguration, 'functionName', 'delete');
		$data = $this->triggerFunction('processPostData', $this->getOptions()->get('data', array()), $this->operationConfiguration);

		$data = $this->triggerFunction('validatePostData', $model, $data, $this->operationConfiguration);

		if ($data === false)
		{
			// Not Acceptable
			$this->setStatusCode(406);
			$this->triggerFunction('displayErrors', $model);
			$this->setData('result', $data);

			return;
		}

		$result = null;
		$args = $this->buildFunctionArgs($this->operationConfiguration, $data);

		// Prepare parameters for the function
		if (strtolower(XmlHelper::attributeToString($this->operationConfiguration, 'dataMode', 'model')) == 'table')
		{
			$primaryKeys = array();
			$this->apiFillPrimaryKeys($primaryKeys, $this->operationConfiguration);

			if (!empty($primaryKeys))
			{
				$result = $model->{$functionName}($primaryKeys);
			}
			else
			{
				$result = $model->{$functionName}($args);
			}
		}
		else
		{
			// Checks if that method exists in model class file and executes it
			if (method_exists($model, $functionName))
			{
				$result = $this->triggerCallFunction($model, $functionName, $args);
			}
			else
			{
				$this->setStatusCode(400);
			}
		}

		$this->setData('result', $result);

		$this->triggerFunction('displayErrors', $model);

		if ($this->statusCode < 400)
		{
			if ($result === false)
			{
				// If delete failed then we set it to Internal Server Error status code
				$this->setStatusCode(500);
			}
		}
	}
}
