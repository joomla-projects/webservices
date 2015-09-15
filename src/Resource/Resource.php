<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Resource;

/**
 * Object to represent a hypermedia link in HAL.
 *
 * @since  1.2
 */
abstract class Resource
{
	/**
	 * @var \SimpleXMLElement
	 */
	protected $xml;

	/**
	 * Internal storage of Link objects
	 * @var array
	 */
	protected $links = array();

	/**
	 * Internal storage of primitive types
	 * @var array
	 */
	protected $data = array();

	/**
	 * Internal storage of `Resource` objects
	 * @var array
	 */
	protected $embedded = array();

	/**
	 * Get data (not links, embedded, etc.).
	 * 
	 * @return  array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Gets all Embedded elements
	 *
	 * @return array
	 */
	public function getEmbedded()
	{
		return $this->embedded;
	}

	/**
	 * Gets all links
	 *
	 * @return array
	 */
	public function getLinks()
	{
		return $this->links;
	}

	/**
	 * Gets self link
	 *
	 * @return  Link
	 */
	public function getSelf()
	{
		return $this->links['self'];
	}

	/**
	 * Sets data to the resource
	 *
	 * @param   string  $rel   Rel element
	 * @param   mixed   $data  Data for the resource
	 *
	 * @return  $this
	 */
	public function setData($rel, $data = null)
	{
		if (is_array($rel) && null === $data)
		{
			foreach ($rel as $k => $v)
			{
				$this->data[$k] = $v;
			}
		}
		else
		{
			$this->data[$rel] = $data;
		}

		return $this;
	}

	/**
	 * Sets Embedded resource
	 *
	 * @param   string    $rel       Relation of the resource
	 * @param   Resource  $resource  Resource
	 * @param   bool      $singular  Force overwrite of the existing embedded element
	 *
	 * @return  $this
	 */
	public function setEmbedded($rel, Resource $resource = null, $singular = false)
	{
		if ($singular)
		{
			$this->embedded[$rel] = $resource;
		}
		else
		{
			$this->embedded[$rel][] = $resource;
		}

		return $this;
	}

	/**
	 * Add a link to the resource.
	 *
	 * Per the JSON-HAL specification, a link relation can reference a
	 * single link or an array of links. By default, two or more links with
	 * the same relation will be treated as an array of links. The $singular
	 * flag will force links with the same relation to be overwritten. The
	 * $plural flag will force links with only one relation to be treated
	 * as an array of links. The $plural flag has no effect if $singular
	 * is set to true.
	 *
	 * @param   ResourceLink  $link      Link
	 * @param   boolean       $singular  Force overwrite of the existing link
	 * @param   boolean       $plural    Force plural mode even if only one link is present
	 *
	 * @return  $this
	 */
	public function setLink(ResourceLink $link, $singular = false, $plural = false)
	{
		$rel = $link->getRel();

		if ($singular || (!isset($this->links[$rel]) && !$plural))
		{
			$this->links[$rel] = $link;
		}
		else
		{
			if (isset($this->links[$rel]) && !is_array($this->links[$rel]))
			{
				$orig_link = $this->links[$rel];
				$this->links[$rel] = array($orig_link);
			}

			$this->links[$rel][] = $link;
		}

		return $this;
	}

	/**
	 * Set multiple links at once.
	 *
	 * @param   array  $links     List of links
	 * @param   bool   $singular  Force overwrite of the existing link
	 * @param   bool   $plural    Force plural mode even if only one link is present
	 *
	 * @return  $this
	 */
	public function setLinks(array $links, $singular = false, $plural = false)
	{
		foreach ($links as $link)
		{
			$this->setLink($link, $singular, $plural);
		}

		return $this;
	}

	/**
	 * Sets XML attributes for \Joomla\Webservices\Resource\Link
	 * 
	 * @param   \SimpleXMLElement  $xml   XML document
	 * @param   Link               $link  Link element
	 *
	 * @return  $this
	 */
	public function setXMLAttributes(\SimpleXMLElement $xml, Link $link)
	{
		$xml->addAttribute('href', $link->getHref());

		if ($link->getRel() && $link->getRel() !== 'self')
		{
			$xml->addAttribute('rel', $link->getRel());
		}

		if ($link->getName())
		{
			$xml->addAttribute('name', $link->getName());
		}

		if ($link->getTitle())
		{
			$xml->addAttribute('title', $link->getTitle());
		}

		if ($link->getHreflang())
		{
			$xml->addAttribute('hreflang', $link->getHreflang());
		}

		return $this;
	}
}
