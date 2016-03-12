<?php
/**
 * @package     Redcore
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Renderer\Application;

use Joomla\Webservices\Renderer\Renderer;
use Joomla\Webservices\Webservices\Webservice;
use Joomla\Webservices\Resource\Resource;
use Joomla\Webservices\Resource\ResourceHome;
use Joomla\Webservices\Resource\ResourceItem;
use Joomla\Webservices\Uri\Uri;

/**
 * ApiDocumentHal class, provides an easy interface to parse and display HAL+JSON or HAL+XML output
 *
 * @package     Redcore
 * @subpackage  Document
 * @see         http://stateless.co/hal_specification.html
 * @since       1.2
 */
class Haljson extends Renderer
{
	/**
	 * API interaction style.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $style = 'rest';

	/**
	 * Render all hrefs as absolute, relative is default
	 *
	 * @var    boolean
	 * @since  __DEPLOY_VERSION__
	 */
	protected $absoluteHrefs = false;

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
		$this->setMimeEncoding('application/hal+json', false);

		// Set document type.
		$this->setType('hal+json');

		// Set absolute/relative hrefs.
		$this->absoluteHrefs = isset($options['absoluteHrefs']) ? $options['absoluteHrefs'] : false;

		// Set token if needed.
		$this->uriParams = isset($options['uriParams']) ? $options['uriParams'] : array();
	}

	/**
	 * Render the document.
	 *
	 * @param   Resource  $resource  A populated resource object.
	 *
	 * @return  string   The rendered data
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function render(Resource $resource)
	{
		// Adjust hrefs in the _links object.
		$this->relToAbs($resource, $this->absoluteHrefs);

		return parent::render($resource);
	}

	/**
	 * Render a representation of a ResourceHome object.
	 *
	 * @param   Resource  $resource  A resource home object.
	 *
	 * @return  A representation of the object.
	 */
	public function renderResourceHome(Resource $resource)
	{
		return $this->renderResourceItem($resource);
	}

	/**
	 * Render a representation of a ResourceItem object.
	 *
	 * @param   Resource  $resource  A resource item object.
	 *
	 * @return  A representation of the object.
	 */
	public function renderResourceItem(Resource $resource)
	{
		$properties = array();

		// Iterate through the links and add them to the _links element.
		foreach ($resource->getLinks() as $rel => $link)
		{
			if ($link instanceof Resource)
			{
				$properties['_links'][$rel] = $this->render($link);

				continue;
			}

			// An array of Link resources.
			foreach ($link as $linkResource)
			{
				$properties['_links'][$rel][] = $this->render($linkResource);
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
			if ($embedded instanceof Resource)
			{
				$properties['_embedded'][$rel] = $this->render($embedded);
			}
		}

		return json_encode($properties);
	}

	/**
	 * Render a representation of a ResourceLink object.
	 *
	 * @param   Resource  $resource  A resource item object.
	 *
	 * @return  A representation of the object.
	 */
	public function renderResourceLink(Resource $resource)
	{
		return $resource->toArray();
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

		// Iterate through the links and add them to the _links element.
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
				$properties['_links'][$rel] = $this->render($link);

				continue;
			}

			// An array of Link resources.
			foreach ($link as $linkResource)
			{
				$properties['_links'][$rel][] = $this->render($linkResource);
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

	/**
	 * Method to convert relative to absolute links.
	 *
	 * @param   Resource  $resource       Resource object which contains links (_links).
	 * @param   boolean   $absoluteHrefs  Should we replace link Href with absolute.
	 *
	 * @return  void
	 */
	protected function relToAbs(Resource $resource, $absoluteHrefs)
	{
		// Adjust hrefs in the _links object.
		foreach ($resource->getLinks() as $link)
		{
			if (is_array($link))
			{
				/* @var $arrayLink \Joomla\Webservices\Resource\Link */
				foreach ($link as $group => $arrayLink)
				{
					$href = $arrayLink->getHref();
					$href = $this->addUriParameters($href, $absoluteHrefs);
					$arrayLink->setHref($href);
					$resource->setReplacedLink($arrayLink, $group);
				}
			}
			else
			{
				/* @var $link \Joomla\Webservices\Resource\Link */
				$href = $link->getHref();
				$href = $this->addUriParameters($href, $absoluteHrefs);
				$link->setHref($href);
				$resource->setReplacedLink($link);
			}
		}

		// Adjust hrefs in the _embedded object (if there is one).
		foreach ($resource->getEmbedded() as $resources)
		{
			if (is_object($resources))
			{
				$this->relToAbs($resources, $absoluteHrefs);
			}
			elseif (is_array($resources))
			{
				foreach ($resources as $resource)
				{
					if (is_object($resource))
					{
						$this->relToAbs($resource, $absoluteHrefs);
					}
				}
			}
		}
	}

	/**
	 * Prepares link
	 *
	 * @param   string   $href           Link location
	 * @param   boolean  $absoluteHrefs  Should we replace link Href with absolute.
	 *
	 * @return  string  Modified link
	 *
	 * @since   1.2
	 */
	public function addUriParameters($href, $absoluteHrefs)
	{
		$uri = Uri::getInstance($href);

		if ($absoluteHrefs && substr($href, 0, 1) == '/')
		{
			$href = rtrim($uri->base(), '/') . $href;
			$uri = Uri::getInstance($href);
		}

		if (!empty($this->uriParams))
		{
			foreach ($this->uriParams as $paramKey => $param)
			{
				if (!$uri->hasVar($paramKey))
				{
					$uri->setVar($paramKey, $param);
				}
			}
		}

		return $uri->toString();
	}
}
