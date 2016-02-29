<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Webservices;

use Joomla\Utilities\ArrayHelper;
use Joomla\Webservices\Resource\ResourceHome;
use Joomla\Webservices\Resource\ResourceItem;
use Joomla\Webservices\Resource\ResourceLink;
use Joomla\Webservices\Resource\ResourceList;
use Joomla\Webservices\Resource\Resource;
use Joomla\Webservices\Uri\Uri;
use Joomla\Webservices\Xml\XmlHelper;

/**
 * Class to execute webservice operations.
 *
 * @since  __DEPLOY_VERSION__
 */
class Read extends Webservice
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

		// Home page is special.
		if ($this->webserviceName == 'contents')
		{
			$this->resource = $this->triggerFunction('apiDefaultPage');

			return $this->resource;
		}

		// Check we have permission to perform this operation.
		if (!$this->triggerFunction('isOperationAllowed'))
		{
			return false;
		}

		// Get name for integration model/table.  Could be different from the webserviceName.
		$this->elementName = ucfirst(strtolower((string) $this->getConfig('config.name')));

		// Construct resource from data retrieved from integration layer.
		$this->resource = $this->triggerFunction('apiRead');

		return $this->resource;
	}

	/**
	 * Execute the Api Default Page operation.
	 *
	 * @return  Resource  A populated Resource object.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function apiDefaultPage()
	{
		// If default page needs authorisation then make sure that we have it.
		if ($this->app->get('webservices.webservices_default_page_authorization', 0) == 1
		 && !$this->app->login($this->getIntegrationObject()->getStrategies()))
		{
			// @TODO Return an error resource?
			return false;
		}

		// Instantiate a new Home resource.
		$resource = new ResourceHome($this->profile);

		// Get data on all installed webservices.
		$webservices = ConfigurationHelper::getInstalledWebservices($this->getContainer()->get('db'));

		// @TODO: Remove client name level.  There should be only one API.
		foreach ($webservices as $webserviceClient => $webserviceNames)
		{
			foreach ($webserviceNames as $webserviceName => $webserviceVersions)
			{
				foreach ($webserviceVersions as $webserviceVersion => $webservice)
				{
					// If webservice is not "published", don't list it.
					if ($webservice['published'] != 1)
					{
						continue;
					}

					// Set option and view name
					$this->setOptionViewName($webservice['name'], $this->configuration);

					// We will fetch only top level webservices.
					$resource->setLink(
						new ResourceLink(
							'/' . ($webservice['name'] != 'contents' ? $webservice['name'] : ''),
							$webservice['name'],
							$webservice['title'],
							$webservice['name']
						)
					);
				}
			}
		}

		return $resource;
	}

	/**
	 * Execute the Api Read operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function apiRead()
	{
	    // Get data from request.
        $data = (array) $this->getOptions()->get('dataGet', array());

        // Determine if the request if for an item or a list.
        $displayTarget = $this->profile->isItem($data) ? 'item' : 'list';

		// Get data bound to primary keys.
        $primaryKeys = $this->profile->bindDataToPrimaryKeys($data, $displayTarget);

		// Get the part of the profile that deals with the item or the list.
		$subprofile = $this->profile->getSubprofile($displayTarget);

		// Get the model object from the integration layer.
		$model = $this->triggerFunction('loadModel', $this->elementName, $subprofile);
		$this->assignFiltersList($model);

		// Build the resource.
		$methodName = 'apiRead' . $displayTarget;
		$resource = $this->$methodName($primaryKeys, $model, $subprofile);

		return $resource;
	}

	/**
	 * Execute the API read operation for a list.
	 *
	 * Data is retrieved from the model
	 *
	 * @param   array              $primaryKeys  Array of primary keys.
	 * @param   mixed              $model        A model from the integration.
	 * @param   \SimpleXMLElement  $subprofile   Profile for the read list.
	 *
	 * @return  Resource  A populated Resource object.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function apiReadList(array $primaryKeys, $model, \SimpleXMLElement $subprofile)
	{
		// Get the name of the method in the model that will return a list of items.
		$functionName = XmlHelper::attributeToString($subprofile, 'functionName', 'getItems');

		// Call the model method.
		$items = method_exists($model, $functionName) ? $model->{$functionName}() : array();

		// If the model has a getPagination method, call it.
		// @TODO Decouple from the hard-wired Joomla implementation here?
		if (method_exists($model, 'getPagination'))
		{
			$pagination = $model->getPagination();
			$paginationPages = $pagination->getPaginationPages();
            $pageData = array(
                'previous'  => isset($paginationPages['previous']['data']->base) ? $paginationPages['previous']['data']->base : $pagination->limitstart,
                'next'      => isset($paginationPages['next']['data']->base) ? $paginationPages['next']['data']->base : $pagination->limitstart,
                'limit'     => $pagination->limit,
                'limitstart' => $pagination->limitstart,
                'totalItems' => $pagination->total,
                'totalPages' => max($pagination->pagesTotal, 1),
                'page'      => max($pagination->pagesCurrent, 1),
                'last'      => ((max($pagination->pagesTotal, 1) - 1) * $pagination->limit),
            );
            $this->setData('pagination', $pageData);
		}

		$resource = $this->triggerFunction('bindDataToResourceList', $items, $subprofile);

		return $resource;
	}

	/**
	 * Execute the API read operation for an item.
	 *
	 * @param   array              $primaryKeys  Array of primary keys.
	 * @param   mixed              $model        A model from the integration.
	 * @param   \SimpleXMLElement  $subprofile   Profile for the read list.
	 *
	 * @return  Resource  A populated Resource object.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function apiReadItem(array $primaryKeys, $model, \SimpleXMLElement $subprofile)
	{
		$primaryKeys = count($primaryKeys) > 1 ? array($primaryKeys) : $primaryKeys;

		// Getting single item.
		$functionName = XmlHelper::attributeToString($subprofile, 'functionName', 'getItem');
		$messagesBefore = $this->app->getMessageQueue();
		$itemObject = method_exists($model, $functionName) ? call_user_func_array(array(&$model, $functionName), $primaryKeys) : array();
		$messagesAfter = $this->app->getMessageQueue();

		// Check to see if we have the item or not since it might return default properties.
		if (count($messagesBefore) != count($messagesAfter))
		{
			foreach ($messagesAfter as $messageKey => $messageValue)
			{
				$messageFound = false;

				foreach ($messagesBefore as $key => $value)
				{
					if ($messageValue['type'] == $value['type'] && $messageValue['message'] == $value['message'])
					{
						$messageFound = true;
						break;
					}
				}

				if (!$messageFound && $messageValue['type'] == 'error')
				{
					$itemObject = null;
					break;
				}
			}
		}

		$resource = $this->triggerFunction('bindDataToResourceItem', $itemObject, $subprofile);

		return $resource;
	}

	/**
	 * We set filters and List parameters to the model object
	 *
	 * @param   object  &$model  Model object
	 *
	 * @return  array
	 */
	public function assignFiltersList(&$model)
	{
		if (method_exists($model, 'getState'))
		{
			// To initialize populateState
			$model->getState();
		}

		// Set state for Filters and List
		if (method_exists($model, 'setState'))
		{
			$dataGet = $this->getOptions()->get('dataGet', array());

			if (is_object($dataGet))
			{
				$dataGet = ArrayHelper::fromObject($dataGet);
			}

			if (isset($dataGet['list']))
			{
				foreach ($dataGet['list'] as $key => $value)
				{
					$model->setState('list.' . $key, $value);
				}
			}

			if (isset($dataGet['filter']))
			{
				foreach ($dataGet['filter'] as $key => $value)
				{
					$model->setState('filter.' . $key, $value);
				}
			}
		}
	}

	/**
	 * Set document content for Item view.
	 *
	 * @param   object|array       $item        Item content.
	 * @param   \SimpleXMLElement  $subprofile  Profile for the read item.
	 *
	 * @return  Resource  A populated resource object.
	 *
	 * @throws  \Exception
	 */
	public function bindDataToResourceItem($item, $subprofile)
	{
		// If the item is not valid, then return a 404 Not found response.
		if (empty($item) || !(is_array($item) || is_object($item)))
		{
			// 404 => 'Not found'
			$this->setStatusCode(404);

			throw new \Exception($this->text->translate('LIB_WEBSERVICES_API_HAL_WEBSERVICE_ERROR_NO_CONTENT'), 404);
		}

		// Initialise a new resource object.
		$resource = new ResourceItem($this->profile);

		// Get resource profile from configuration.
        $profile = $this->profile->getResources($this->getOptions(), 'item');

		// Bind top-level properties into the Resource.
		$this->setDataValueToResource($resource, $profile, $item, 'rcwsGlobal');

		return $resource;
	}

	/**
	 * Set document content for List view.
	 *
	 * @param   array              $items        List of items.
	 * @param   \SimpleXMLElement  $subprofile   Profile for the read item.
	 *
	 * @return  Resource  A populated resource object.
	 *
	 * @throws  \Exception
	 */
	public function bindDataToResourceList(array $items, \SimpleXMLElement $subprofile)
	{
		// Initialise a new resource object.
		$resource = new ResourceList($this->profile);

		// Get resource profile from configuration.
        $profile = $this->profile->getResources($this->getOptions(), 'list');

		// Bind top-level properties into the Resource.
		$this->setDataValueToResource($resource, $profile, $this->data, 'rcwsGlobal');

		// Embed secondary resource items into the list resource.
		foreach ($items as $itemValue)
		{
			// Convert object to array.
			$item = ArrayHelper::fromObject($itemValue);

			// Create a new (empty) item Resource.
			$embedItem = new ResourceItem('item', array());

			// Bind data into the new Resource using the profile.
			$embedItem = $this->setDataValueToResource($embedItem, $profile, $item, 'listItem');

			// Embed the new Resource into the list resource.
			$resource->setEmbedded($this->webserviceName, $embedItem);
		}

		return $resource;
	}
}
