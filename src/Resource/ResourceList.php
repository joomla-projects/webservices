<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Resource;

use Joomla\Webservices\Resource\ResourceLink;
use Joomla\Webservices\Webservices\Profile;

/**
 * Object to represent a hypermedia resource in HAL.
 *
 * @since  1.2
 */
class ResourceList extends Resource
{
	/**
	 * Constructor.
	 *
	 * @param   Profile  $profile  A profile which will shape the resource.
	 */
	public function __construct(Profile $profile)
	{
		$this->profile = $profile;
	}

	/**
	 * Replace existing link to the resource.
	 *
	 * @param   ResourceLink  $link   Link
	 * @param   mixed         $group  Grouped link container
	 *
	 * @return  $this
	 */
	public function setReplacedLink(ResourceLink $link, $group = '')
	{
		$rel = $link->getRel();

		if ($group !== '')
		{
			$this->links[$rel][$group] = $link;
		}
		else
		{
			$this->links[$rel] = $link;
		}

		return $this;
	}

	/**
	 * Sets data to the resource
	 *
	 * @param   string  $rel       Rel element
	 * @param   string  $key       Key value for the resource
	 * @param   string  $data      Data value for the resource
	 * @param   bool    $singular  Force overwrite of the existing data
	 * @param   bool    $plural    Force plural mode even if only one link is present
	 *
	 * @return  $this
	 */
	public function setDataGrouped($rel, $key = '', $data = '', $singular = false, $plural = false)
	{
		if ($singular || (!isset($this->data[$rel]) && !$plural))
		{
			$this->data[$rel][$key] = $data;
		}
		else
		{
			if (isset($this->data[$rel]) && !is_array($this->data[$rel]))
			{
				$orig_link = $this->data[$rel];
				$this->data[$rel] = array($orig_link);
			}

			$this->data[$rel][$key] = $data;
		}

		return $this;
	}

	/**
	 * Converts current Resource object to Array
	 *
	 * @return array
	 */
	public function toArray()
	{
		$data = array();

		foreach ($this->links as $rel => $link)
		{
			$links = $this->_recourseLinks($link);

			if (!empty($links))
			{
				$data['_links'][$rel] = $links;
			}
		}

		foreach ($this->data as $key => $value)
		{
			$data[$key] = $value;
		}

		foreach ($this->embedded as $rel => $embed)
		{
			$data['_embedded'][$rel] = $this->_recourseEmbedded($embed);
		}

		return $data;
	}

	/**
	 * Recourse function for Embedded objects
	 *
	 * @param   Resource|null|array  $embedded  Embedded object
	 *
	 * @return array
	 */
	protected function _recourseEmbedded($embedded)
	{
		if (is_null($embedded))
		{
			return null;
		}

		$result = array();

		if ($embedded instanceof self)
		{
			$result = $embedded->toArray();
		}
		else
		{
			foreach ($embedded as $embed)
			{
				if ($embed instanceof self)
				{
					$result[] = $embed->toArray();
				}
			}
		}

		return $result;
	}

	/**
	 * Recourse function for Link objects
	 *
	 * @param   array|Link  $links  Link object
	 *
	 * @return array
	 */
	protected function _recourseLinks($links)
	{
		$result = array();

		if (!is_array($links))
		{
			$result = $links->toArray();
		}
		else
		{
			/** @var \Joomla\Webservices\Resource\Link $link */
			foreach ($links as $link)
			{
				$result[] = $link->toArray();
			}
		}

		return $result;
	}

	/**
	 * Method to load an object or an array into this object.
	 *
	 * @param   object  $object  Object whose properties are to be loaded.
	 *
	 * @return object This method may be chained.
	 */
	public function load($object)
	{
		foreach ($object as $name => $value)
		{
			// For _links and _embedded, we merge rather than replace.
			if ($name == '_links')
			{
				$this->links = array_merge((array) $this->links, (array) $value);
			}
			elseif ($name == '_embedded')
			{
				$this->embedded = array_merge((array) $this->embedded, (array) $value);
			}
			else
			{
				$this->data[$name] = $value;
			}
		}

		return $this;
	}

	/**
	 * Creates empty array of configuration Resource field
	 *
	 * @param   array   $resource          Resource array
	 * @param   string  $resourceSpecific  Resource specific container
	 *
	 * @return  array
	 */
	public static function defaultResourceField($resource = array(), $resourceSpecific = 'rcwsGlobal')
	{
		$defaultResource = array(
			'resourceSpecific' => !empty($resource['resourceSpecific']) ? $resource['resourceSpecific'] : $resourceSpecific,
			'displayGroup'     => !empty($resource['displayGroup']) ? $resource['displayGroup'] : '',
			'displayName'      => !empty($resource['displayName']) ? $resource['displayName'] : '',
			'fieldFormat'      => !empty($resource['fieldFormat']) ? $resource['fieldFormat'] : '',
			'transform'        => !empty($resource['transform']) ? $resource['transform'] : '',
			'linkName'         => !empty($resource['linkName']) ? $resource['linkName'] : '',
			'linkTitle'        => !empty($resource['linkTitle']) ? $resource['linkTitle'] : '',
			'hrefLang'         => !empty($resource['hrefLang']) ? $resource['hrefLang'] : '',
			'linkTemplated'    => !empty($resource['linkTemplated']) ? $resource['linkTemplated'] : '',
			'linkRel'          => !empty($resource['linkRel']) ? $resource['linkRel'] : '',
			'description'      => !empty($resource['description']) ? $resource['description'] : '',
		);

		return array_merge($resource, $defaultResource);
	}

	/**
	 * Merges two resource fields
	 *
	 * @param   array  $resourceMain   Resource array main
	 * @param   array  $resourceChild  Resource array child
	 *
	 * @return  array
	 */
	public static function mergeResourceFields($resourceMain = array(), $resourceChild = array())
	{
		foreach ($resourceMain as $key => $value)
		{
			$resourceMain[$key] = !empty($resourceChild[$key]) ? $resourceChild[$key] : $resourceMain[$key];
		}

		return $resourceMain;
	}
}
