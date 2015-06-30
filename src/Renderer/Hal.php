<?php
/**
 * @package     Redcore
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Renderer;

use Joomla\Webservices\Webservices\Webservice as Api;
use Joomla\Webservices\Resource\Resource;

use Joomla\DI\Container;
use Joomla\Webservices\Uri\Uri;

/**
 * ApiDocumentHal class, provides an easy interface to parse and display HAL+JSON or HAL+XML output
 *
 * @package     Redcore
 * @subpackage  Document
 * @see         http://stateless.co/hal_specification.html
 * @since       1.2
 */
class Hal extends Base
{
	/**
	 * Render all hrefs as absolute, relative is default
	 *
	 * @var    boolean
	 * @since  __DELPOY_VERSION__
	 */
	protected $absoluteHrefs = false;

	/**
	 * Document format (xml or json)
	 *
	 * @var    string
	 * @since  __DEPLOY__VERSION__
	 */
	protected $documentFormat;

	/**
	 * @var    Api  Main HAL object
	 * @since  1.2
	 */
	public $hal = null;

	/**
	 * Class constructor
	 *
	 * @param   Container  $container  The DIC object
	 * @param   array      $options    Associative array of options
	 *
	 * @since  1.2
	 */
	public function __construct(Container $container, $options = array())
	{
		parent::__construct($container, $options);

		$this->documentFormat = $options['documentFormat'];

		// Sanity check - fall back to XML format
		if (!in_array($this->documentFormat, array('xml', 'json')))
		{
			$this->documentFormat = 'json';
		}

		// Set default mime type.
		$this->setMimeEncoding('application/hal+' . $this->documentFormat, false);

		// Set document type.
		$this->setType('hal+' . $this->documentFormat);

		// Set absolute/relative hrefs.
		$this->absoluteHrefs = isset($options['absoluteHrefs']) ? $options['absoluteHrefs'] : false;

		// Set token if needed
		$this->uriParams = isset($options['uriParams']) ? $options['uriParams'] : array();
	}

	/**
	 * Render the document.
	 *
	 * @param   boolean  $cache   If true, cache the output
	 * @param   array    $params  Associative array of attributes
	 *
	 * @return  string   The rendered data
	 *
	 * @since  1.2
	 */
	public function render($cache = false, $params = array())
	{
		parent::render($cache, $params);
		$runtime = microtime(true) - $this->app->startTime;

		$this->app->setHeader('Status', $this->hal->statusCode . ' ' . $this->hal->statusText, true);
		$this->app->setHeader('Server', '', true);
		$this->app->setHeader('X-Runtime', $runtime, true);
		$this->app->setHeader('Access-Control-Allow-Origin', '*', true);
		$this->app->setHeader('Pragma', 'public', true);
		$this->app->setHeader('Expires', '0', true);
		$this->app->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
		$this->app->setHeader('Cache-Control', 'private', false);
		$this->app->setHeader('Content-type', $this->getMimeEncoding() . '; charset=' . $this->getCharset(), true);
		$this->app->setHeader('Webservice name', $this->hal->webserviceName, true);
		$this->app->setHeader('Webservice version', $this->hal->webserviceVersion, true);

		$this->app->sendHeaders();

		// Get the HAL object from the buffer.
		/* @var $hal \Joomla\Webservices\Resource\Resource */
		$hal = $this->getBuffer();

		// If required, change relative links to absolute.
		if (is_object($hal))
		{
			// Adjust hrefs in the _links object.
			$this->relToAbs($hal, $this->absoluteHrefs);
		}

		if ($this->documentFormat == 'xml')
		{
			return $hal->getXML()->asXML();
		}
		else
		{
			return (string) $hal;
		}
	}

	/**
	 * Sets HAL object to the document
	 *
	 * @param   Api  $hal  Hal object
	 *
	 * @return  $this
	 *
	 * @since  1.2
	 */
	public function setHal(Api $hal)
	{
		$this->hal = $hal;

		return $this;
	}

	/**
	 * Method to convert relative to absolute links.
	 *
	 * @param   Resource  $hal            Hal object which contains links (_links).
	 * @param   boolean   $absoluteHrefs  Should we replace link Href with absolute.
	 *
	 * @return  void
	 */
	protected function relToAbs(Resource $hal, $absoluteHrefs)
	{
		if ($links = $hal->getLinks())
		{
			// Adjust hrefs in the _links object.
			foreach ($links as $link)
			{
				if (is_array($link))
				{
					/* @var $arrayLink \Joomla\Webservices\Resource\Link */
					foreach ($link as $group => $arrayLink)
					{
						$href = $arrayLink->getHref();
						$href = $this->addUriParameters($href, $absoluteHrefs);
						$arrayLink->setHref($href);
						$hal->setReplacedLink($arrayLink, $group);
					}
				}
				else
				{
					/* @var $link \Joomla\Webservices\Resource\Link */
					$href = $link->getHref();
					$href = $this->addUriParameters($href, $absoluteHrefs);
					$link->setHref($href);
					$hal->setReplacedLink($link);
				}
			}
		}

		// Adjust hrefs in the _embedded object (if there is one).
		if ($embedded = $hal->getEmbedded())
		{
			foreach ($embedded as $resources)
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
