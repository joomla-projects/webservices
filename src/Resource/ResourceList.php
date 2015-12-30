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
	 * Json option
	 */
	const JSON_NUMERIC_CHECK_ON = true;

	/**
	 * Json option
	 */
	const JSON_NUMERIC_CHECK_OFF = false;

	/**
	 * @var bool
	 */
	protected $jsonNumericCheck = self::JSON_NUMERIC_CHECK_OFF;

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
	 * @param   mixed         $group  Groupped link container
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
	 * Convert function to Json format
	 *
	 * @return string
	 */
	public function toJson()
	{
		// Check for defined constants
		if (!defined('JSON_UNESCAPED_SLASHES'))
		{
			define('JSON_UNESCAPED_SLASHES', 64);
		}

		if (defined(JSON_NUMERIC_CHECK) && $this->jsonNumericCheck)
		{
			return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
		}

		return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * Convert function to string format
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}

	/**
	 * Get XML document of current resource
	 *
	 * @param   \SimpleXMLElement|null  $xml  XML document
	 *
	 * @return  \SimpleXMLElement
	 */
	public function getXML($xml = null)
	{
		if (!($xml instanceof \SimpleXMLElement))
		{
			$xml = new \SimpleXMLElement('<resource></resource>');
		}

		$this->xml = $xml;

		foreach ($this->links as $links)
		{
			if (is_array($links))
			{
				foreach ($links as $link)
				{
					$this->_addLinks($link);
				}
			}
			else
			{
				$this->_addLinks($links);
			}
		}

		$this->_addData($this->xml, $this->data);
		$this->_getEmbedded($this->embedded);

		return $this->xml;
	}

	/**
	 * Get Embedded of current resource
	 *
	 * @param   mixed        $embedded  Embedded list
	 * @param   string|null  $_rel      Relation of embedded object
	 *
	 * @return void
	 */
	protected function _getEmbedded($embedded, $_rel = null)
	{
		print_r($embedded);
		exit(__METHOD__);

		/* @var $embed Resource */
		foreach ($embedded as $rel => $embed)
		{
			if ($embed instanceof Resource)
			{
				$rel = is_numeric($rel) ? $_rel : $rel;
				$this->_getEmbRes($embed)->addAttribute('rel', $rel);
			}
			else
			{
				if (!is_null($embed))
				{
					$this->_getEmbedded($embed, $rel);
				}
				else
				{
					$rel = is_numeric($rel) ? $_rel : $rel;
					$this->xml->addChild('resource')->addAttribute('rel', $rel);
				}
			}
		}
	}

	/**
	 * Get Embedded of current resource in XML format
	 *
	 * @param   Resource  $embed  Embedded object
	 *
	 * @return  \SimpleXMLElement
	 */
	protected function _getEmbRes(Resource $embed)
	{
		$resource = $this->xml->addChild('resource');

		return $embed->getXML($resource);
	}

	/**
	 * Sets XML document to this resource
	 *
	 * @param   \SimpleXMLElement  $xml  XML document
	 *
	 * @return  $this
	 */
	public function setXML(\SimpleXMLElement $xml)
	{
		$this->xml = $xml;

		return $this;
	}

	/**
	 * Adds data to the current resource
	 *
	 * @param   \SimpleXMLElement  $xml          XML document
	 * @param   array              $data         Data
	 * @param   string             $keyOverride  Key override
	 *
	 * @return void
	 */
	protected function _addData(\SimpleXMLElement $xml, array $data, $keyOverride = null)
	{
		foreach ($data as $key => $value)
		{
			// Alpha-numeric key => array value
			if (!is_numeric($key) && is_array($value))
			{
				$c = $xml->addChild($key);
				$this->_addData($c, $value, $key);
			}
			// Alpha-numeric key => non-array value
			elseif (!is_numeric($key) && !is_array($value))
			{
				$xml->addChild($key, $value);
			}
			// Numeric key => array value
			elseif (is_array($value))
			{
				$this->_addData($xml, $value);
			}
			// Numeric key => non-array value
			else
			{
				$xml->addChild($keyOverride, $value);
			}
		}
	}

	/**
	 * Adds links to the current resource
	 *
	 * @param   Link  $link  Link
	 *
	 * @return void
	 */
	protected function _addLinks(Link $link)
	{
		if ($link->getRel() != 'self' && !is_numeric($link->getRel()))
		{
			$this->_addLink($link);
		}
	}

	/**
	 * Adds link to the current resource
	 *
	 * @param   Link  $link  Link
	 *
	 * @return  $this
	 */
	protected function _addLink(Link $link)
	{
		$this->setXMLAttributes($this->xml->addChild('link'), $link);

		return $this;
	}

	/**
	 * Method to load an object or an array into this HAL object.
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
	 * Sets the ability to perform numeric to int conversion of the JSON output.
	 *
	 * <b>Example Usage:</b>
	 * <code>
	 * $hal->setJsonNumericCheck($jsonNumericCheck = self::JSON_NUMERIC_CHECK_OFF);
	 * $hal->setJsonNumericCheck($jsonNumericCheck = self::JSON_NUMERIC_CHECK_ON);
	 * </code>
	 *
	 * @param   bool  $jsonNumericCheck  Json numeric check
	 *
	 * @return  $this
	 */
	public function setJsonNumericCheck($jsonNumericCheck = self::JSON_NUMERIC_CHECK_OFF)
	{
		$this->jsonNumericCheck = $jsonNumericCheck;

		return $this;
	}

	/**
	 * Creates empty array of Xml configuration Resource field
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