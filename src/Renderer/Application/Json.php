<?php
/**
 * @package     Redcore
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Renderer\Application;

use Joomla\Webservices\Resource\Resource;
use Joomla\Webservices\Resource\ResourceItem;

/**
 * Pure JSON renderer.
 * 
 * This overrides the HAL + JSON renderer at the moment.
 * The only thing it does differently is that it sends
 * links as HTTP headers instead of including them in
 * the (HAL) content.
 * @see https://tools.ietf.org/html/rfc5988
 *
 * @package     Redcore
 * @subpackage  Document
 * @since       __DEPLOY_VERSION__
 */
class Json extends Haljson
{
	/**
	 * Class constructor
	 *
	 * @param   object  $application  The application.
	 * @param   array   $options      Associative array of options.
	 *
	 * @since  1.2
	 */
	public function __construct($application, $options = array())
	{
		parent::__construct($application, $options);

		// Set default mime type.
		$this->setMimeEncoding('application/json', false);

		// Set document type.
		$this->setType('json');
	}

	/**
	 * Render a representation of a ResourceLink object.
	 * 
	 * Adds an HTTP Link header to the application header buffer.
	 *
	 * @param   Resource  $resource  A resource item object.
	 *
	 * @return  void
	 * 
	 * @see https://tools.ietf.org/html/rfc5988
	 */
	public function renderResourceLink(Resource $resource)
	{
		$linkText = '<' . $resource->getHref() . '>;'
			. ' rel="' . $resource->getRel() . '"'
			. ($resource->getTitle() != '' ? ' title="' . $resource->getTitle() . '"' : '')
			. ($resource->getTemplated() ? ' templated="true"' : '')
			;

		$this->app->setHeader('Link', $linkText, false);
	}

	/**
	 * Render a representation of a ResourceList object.
	 *
	 * @param   Resource  $resource  A resource list object.
	 *
	 * @return  A representation of the object.
	 */
	public function renderResourceList(Resource $resource)
	{
		$properties = array();
		$data = $resource->getData();

		// Iterate through the links and add them as headers.
		foreach ($resource->getLinks() as $rel => $link)
		{
			// Drop first and previous page links on first page.
			if ($data['page'] == 1)
			{
				if ($rel == 'first' || $rel == 'previous')
				{
					continue;
				}
			}

			// Drop last and next page links on last page.
			if ($data['page'] == $data['totalPages'])
			{
				if ($rel == 'last' || $rel == 'next')
				{
					continue;
				}
			}

			// Add link to _links element.
			if ($link instanceof Resource)
			{
				$this->renderResourceLink($link);

				continue;
			}

			// An array of Link resources.
			foreach ($link as $linkResource)
			{
				$this->renderResourceLink($linkResource);
			}
		}

		// Iterate through the data properties and add them to the top-level array.
		foreach ($resource->getData() as $name => $property)
		{
			$properties[$name] = $property;
		}

		// Iterate through the embedded resources and add them to the _embedded element.
		foreach ($resource->getEmbedded() as $rel => $embedded)
		{
			foreach ($embedded as $item)
			{
				if ($item instanceof ResourceItem)
				{
					$properties['_embedded'][$rel][] = json_decode($this->render($item));
				}
			}
		}

		return json_encode($properties);
	}
}
