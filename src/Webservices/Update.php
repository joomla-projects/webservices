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
class Update extends Webservice
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
		$this->triggerFunction('apiUpdate');

		// Set links from resources to the main document
		$this->setDataValueToResource($this->resource, $this->resources, $this->data);

		return $this->resource;
	}

	/**
	 * Execute the Api Update operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	public function apiUpdate()
	{
		// Get resource list from configuration.
        $this->profile->getResources($this->getOptions());

		$model = $this->triggerFunction('loadModel', $this->elementName, $this->operationConfiguration);
		$functionName = XmlHelper::attributeToString($this->operationConfiguration, 'functionName', 'save');
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

		// Prepare parameters for the function
		$args = $this->buildFunctionArgs($this->operationConfiguration, $data);
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

		$this->setData('result', $result);
		$this->triggerFunction('displayErrors', $model);

		if ($this->statusCode < 400)
		{
			if ($result === false)
			{
				// If update failed then we set it to Internal Server Error status code
				$this->setStatusCode(500);
			}
		}
	}
}
