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
	 * @param   Profile  $profile  A profile which shapes the resource.
	 * 
	 * @return  Resource  A populated Resource object.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function execute(Profile $profile)
	{
		$this->profile = $profile;

		// Check we have permission to perform this operation.
		if (!$this->triggerFunction('isOperationAllowed'))
		{
			return false;
		}

		// Get name for integration model/table.  Could be different from the webserviceName.
		$this->elementName = ucfirst(strtolower((string) $this->getConfig('config.name')));

		$this->operationConfiguration = $this->getConfig('operations.' . strtolower($this->operation));

		$this->triggerFunction('apiDelete');

		$this->resource = new ResourceItem($this->profile);

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
		// Get resource list from configuration.
        $this->profile->getResources($this->getOptions());

		// Delete function requires references and not values like we use in call_user_func_array so we use List delete function
		$model = $this->triggerFunction('loadModel', $this->elementName, $this->operationConfiguration);
		$functionName = XmlHelper::attributeToString($this->operationConfiguration, 'functionName', 'delete');

        // Get data from request and validate it.
        // Note that we actually get the data from the request URI, but we process/validate it as if the data came from the request body.
		$data = $this->triggerFunction('processPostData', $this->getOptions()->get('dataGet', array()), $this->operationConfiguration);
		$data = $this->triggerFunction('validatePostData', $model, $data, $this->operationConfiguration);

		if ($data === false)
		{
			// 406 = Not acceptable.
			$this->setStatusCode(406);
			$this->triggerFunction('displayErrors', $model);
			$this->setData('result', $data);

			return;
		}

		$result = null;
        $args = $this->profile->buildFunctionArgs($data);

		// Prepare parameters for the function
		if (strtolower(XmlHelper::attributeToString($this->operationConfiguration, 'dataMode', 'model')) == 'table')
		{
            $primaryKeys = $this->profile->bindDataToPrimaryKeys($data, $this->operationConfiguration->getName());

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
