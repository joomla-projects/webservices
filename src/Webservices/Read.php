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
		if ($this->webserviceName == 'home')
		{
			return $this->triggerFunction('apiDefaultPage');
		}

		// Check we have permission to perform this operation.
		if (!$this->triggerFunction('isOperationAllowed'))
		{
			return false;
		}

		// Get name for integration model/table.  Could be different from the webserviceName.
		$this->elementName = ucfirst(strtolower((string) $this->getConfig('config.name')));

		$this->triggerFunction('apiRead');

		// Set links from resources to the main document
		$this->setDataValueToResource($this->resource, $this->resources, $this->data);

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
		$this->resource = new ResourceHome($this->profile);

		// Add standard Joomla namespace as curie.
		$documentationCurieAdmin = new ResourceLink('/index.php?option={rel}&amp;format=doc&amp;webserviceClient=administrator',
			'curies', 'Documentation Admin', 'Admin', null, true
		);
		$documentationCurieSite = new ResourceLink('/index.php?option={rel}&amp;format=doc&amp;webserviceClient=site',
			'curies', 'Documentation Site', 'Site', null, true
		);

		// Add basic hypermedia links.
		$this->resource->setLink($documentationCurieAdmin, false, true);
		$this->resource->setLink($documentationCurieSite, false, true);

		// Add link to contents page (actually the same as the "self" link for this page).
		$uri = Uri::getInstance();
		$this->resource->setLink(
			new ResourceLink(
				$uri->base(),
				'contents',
				$this->text->translate('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_DEFAULT_PAGE')
			)
		);

		$webservices = ConfigurationHelper::getInstalledWebservices($this->getContainer()->get('db'));

		foreach ($webservices as $webserviceClient => $webserviceNames)
		{
			foreach ($webserviceNames as $webserviceName => $webserviceVersions)
			{
				foreach ($webserviceVersions as $webserviceVersion => $webservice)
				{
					if ($webservice['state'] == 1)
					{
						// Set option and view name
						$this->setOptionViewName($webservice['name'], $this->configuration);
						$webserviceUrlPath = '/index.php?option=' . $this->optionName
							. '&amp;webserviceVersion=' . $webserviceVersion;

						if (!empty($this->viewName))
						{
							$webserviceUrlPath .= '&view=' . $this->viewName;
						}

						// We will fetch only top level webservices.
						$this->resource->setLink(
							new ResourceLink(
								$webserviceUrlPath . '&webserviceClient=' . $webserviceClient,
								$webservice['name'],
								$webservice['name'],
								$webservice['title']
							)
						);

						break;
					}
				}
			}
		}

		return $this->resource;
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
		// Get primary keys and determine if the resource is an item or a list.
		$primaryKeys = array();
		$isReadItem = $this->apiFillPrimaryKeys($primaryKeys);
		$displayTarget = $isReadItem ? 'item' : 'list';

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

			$this->setData(
				'pagination.previous',
				isset($paginationPages['previous']['data']->base) ? $paginationPages['previous']['data']->base : $pagination->limitstart
			);
			$this->setData(
				'pagination.next',
				isset($paginationPages['next']['data']->base) ? $paginationPages['next']['data']->base : $pagination->limitstart
			);
			$this->setData('pagination.limit', $pagination->limit);
			$this->setData('pagination.limitstart', $pagination->limitstart);
			$this->setData('pagination.totalItems', $pagination->total);
			$this->setData('pagination.totalPages', max($pagination->pagesTotal, 1));
			$this->setData('pagination.page', max($pagination->pagesCurrent, 1));
			$this->setData('pagination.last', ((max($pagination->pagesTotal, 1) - 1) * $pagination->limit));
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
	 * Set document content for Item view
	 *
	 * @param   object|array       $item         Item content.
	 * @param   \SimpleXMLElement  $subprofile   Profile for the read item.
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

		$this->resource = new ResourceItem($this->profile);

		// Get resource profile from configuration.
		$profile = $this->getResourceProfile($subprofile);

		// Filter out all fields that are not in the profile and apply appropriate transform rules.
		foreach ($item as $key => $value)
		{
			// If the property is present in the profile then determine its value.
			if (!empty($profile['rcwsGlobal'][$key]))
			{
				// Determine value from the item data and from global data.
				$value = $this->assignValueToResource($profile['rcwsGlobal'][$key], $item);
			}

			// Copy the value into the global data buffer.
			$this->setData($this->assignGlobalValueToResource($key), $value);
		}

		return $this->resource;
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
	public function bindDataToResourceList($items, $subprofile)
	{
		if (empty($items))
		{
			return;
		}

		$this->resource = new ResourceList($this->profile);

		// Get resource profile from configuration.
		$profile = $this->getResourceProfile($subprofile);

		$listResourcesKeys = array_keys($profile['listItem']);

		// Filter out all fields that are not in the profile and apply appropriate transform rules.
		foreach ($items as $itemValue)
		{
			$item = ArrayHelper::fromObject($itemValue);

			foreach ($item as $key => $value)
			{
				if (!in_array($key, $listResourcesKeys))
				{
					unset($item[$key]);
					continue;
				}

				$item[$this->assignGlobalValueToResource($key)] = $this->assignValueToResource(
					$profile['listItem'][$key], $item
				);
			}

			$embedItem = new ResourceItem('item', $item);
			$embedItem = $this->setDataValueToResource($embedItem, $profile, $itemValue, 'listItem');
			$this->resource->setEmbedded($this->webserviceName, $embedItem);
		}

		return $this->resource;
	}
}
