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
class Create extends Webservice
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

		$this->triggerFunction('apiCreate');

		$this->resource = new ResourceItem($this->profile);

		// Set links from resources to the main document.
		$this->setDataValueToResource($this->resource, $this->resources, $this->data);

		return $this->resource;
	}

	/**
	 * Execute the Api Create operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	public function apiCreate()
	{
		// Get resource profile from configuration.
        $profile = $this->profile->getResources($this->getOptions());

		$model = $this->triggerFunction('loadModel', $this->elementName, $this->operationConfiguration);
		$functionName = XmlHelper::attributeToString($this->operationConfiguration, 'functionName', 'save');

		$data = $this->triggerFunction('processPostData', $this->getOptions()->get('data', array()), $this->operationConfiguration);

		$data = $this->triggerFunction('validatePostData', $model, $data, $this->operationConfiguration);

		if ($data === false)
		{
			// Data failed validation.
			$this->setStatusCode(406);
			$this->triggerFunction('displayErrors', $model);
			$this->setData('result', $data);

			return;
		}

		// Prepare parameters for the function.
		$args = $this->buildFunctionArgs($this->operationConfiguration, $data);
		$result = null;
		$id = 0;

		// Checks if that method exists in model class file and executes it.
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
			$id = $model->getState($model->getName() . '.id');
			$this->setData('id', $id);
		}

		$this->setData('result', $result);
		$this->triggerFunction('displayErrors', $model);

		if ($this->statusCode < 400)
		{
			if ($result === false)
			{
				$this->setStatusCode(404);
			}
			else
			{
				$this->setStatusCode(201);
				$this->app->setHeader('Location', $this->webserviceName . '/' . $id, true);
			}
		}
	}
}
