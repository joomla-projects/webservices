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
class Halxml extends Renderer
{
	/**
	 * Resource name.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $resourceName = '';

	/**
	 * XML document object.
	 *
	 * @var    \DOMDocument
	 * @since  __DEPLOY_VERSION__
	 */
	protected $xml = null;

	/**
	 * XML document root node.
	 *
	 * @var   \DOMElement
	 * @since  __DEPLOY_VERSION__
	 */
	protected $xmlRoot = null;

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
		$this->setMimeEncoding('application/hal+xml', false);

		// Set document type.
		$this->setType('hal+xml');

		// Set absolute/relative hrefs.
		$this->absoluteHrefs = isset($options['absoluteHrefs']) ? $options['absoluteHrefs'] : false;

		// Set token if needed.
		$this->uriParams = isset($options['uriParams']) ? $options['uriParams'] : array();

		// Get the resource name.
		$this->resourceName = $options['resourceName'];

		// Create a new XML document object.
		$this->xml = new \DOMDocument;
		$this->xmlRoot = $this->xml->createElement('resource');
		$this->xml->appendChild($this->xmlRoot);
		$this->xml->formatOutput = true;
	}

	/**
	 * Create a DOMElement node from a ResourceItem object.
	 *
	 * @param   \DOMElement  $xml       An XML node.
	 * @param   Resource     $resource  A resource item object.
	 * @param   boolean      $root      True if this is the root resource element.
	 *
	 * @return  \DOMElement.
	 */
	public function createResourceItem(\DOMElement $xml, Resource $resource, $root = true)
	{
		$doc = $this->xml;

		$properties = array();

		// Iterate through the links and add them to the _links element.
		foreach ($resource->getLinks() as $rel => $link)
		{
			if ($link instanceof Resource)
			{
				// The self link is odd because it gets moved into attributes of the resource element.
				if ($link->getRel() == 'self')
				{
					// Create a rel attribute.
					$rel = $doc->createAttribute('rel');
					$rel->appendChild($doc->createTextNode($root ? 'self' : $this->resourceName));

					// Create an href attribute.
					$href = $doc->createAttribute('href');
					$href->appendChild($doc->createTextNode($link->getHref()));

					// Add the attributes to item resource node.
					$xml->appendChild($rel);
					$xml->appendChild($href);
				}
				else
				{
					$xml->appendChild($this->createResourceLink($link));
				}

				continue;
			}

			// An array of Link resources.
			foreach ($link as $linkResource)
			{
				$xml->appendChild($this->createResourceLink($linkResource));
			}
		}

		// Iterate through the data properties and add them to the top-level array.
		foreach ($resource->getData() as $name => $property)
		{
			// @TODO Check if skipping arrays is the correct behaviour.  It was added because sometimes there is a _messages array.
			if (is_array($property))
			{
				continue;
			}

			$xml->appendChild($doc->createElement($name, $property));
		}

		// Iterate through the embedded resources and add them to the _embedded element.
		foreach ($resource->getEmbedded() as $rel => $embedded)
		{
			foreach ($embedded as $item)
			{
				if ($item instanceof ResourceItem)
				{
					$xml->appendChild($this->createResourceItem($xml, $item, false));
				}
			}
		}

		return $xml;
	}

	/**
	 * Create a DOMElement node from a ResourceLink object.
	 *
	 * @param   Resource  $resource  A resource link object.
	 *
	 * @return  \DOMElement
	 */
	public function createResourceLink(Resource $resource)
	{
		$doc = $this->xml;

		// Create a rel attribute.
		$rel = $doc->createAttribute('rel');
		$rel->appendChild($doc->createTextNode($resource->getRel()));

		// Create an href attribute.
		$href = $doc->createAttribute('href');
		$href->appendChild($doc->createTextNode($resource->getHref()));

		// Create a link element and add the attributes to it.
		$link = $doc->createElement('link');
		$link->appendChild($rel);
		$link->appendChild($href);

		return $link;
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
		$this->xmlRoot = $this->createResourceItem($this->xmlRoot, $resource, true);

		return (string) $this->xml->saveXML();
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
		$doc = $this->xml;
		$xml = $this->xmlRoot;

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

			if ($link instanceof Resource)
			{
				// The self link is odd because it gets moved into attributes of the resource element.
				if ($link->getRel() == 'self')
				{
					// Create a rel attribute.
					$rel = $doc->createAttribute('rel');
					$rel->appendChild($doc->createTextNode('self'));

					// Create an href attribute.
					$href = $doc->createAttribute('href');
					$href->appendChild($doc->createTextNode($link->getHref()));

					// Add the attributes to item resource node.
					$xml->appendChild($rel);
					$xml->appendChild($href);
				}
				else
				{
					$xml->appendChild($this->createResourceLink($link));
				}

				continue;
			}

			// An array of Link resources.
			foreach ($link as $linkResource)
			{
				$xml->appendChild($this->createResourceLink($linkResource));
			}
		}

		// Iterate through the data properties and add them to the top-level array.
		foreach ($resource->getData() as $name => $property)
		{
			// @TODO Check if skipping arrays is the correct behaviour.  It was added because sometimes there is a _messages array.
			if (is_array($property))
			{
				continue;
			}

			$xml->appendChild($doc->createElement($name, $property));
		}

		// Iterate through the embedded resources and add them to the _embedded element.
		foreach ($resource->getEmbedded() as $rel => $embedded)
		{
			foreach ($embedded as $item)
			{
				if ($item instanceof ResourceItem)
				{
					$embeddedResource = $doc->createElement('resource');
					$xml->appendChild($this->createResourceItem($embeddedResource, $item, false));
				}
			}
		}

		return (string) $doc->saveXML();
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
