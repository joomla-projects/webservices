<?php
/**
 * XML Helper for Joomla Webservices.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Xml;

/**
 * Simple XML Helper class
 *
 * @since  __DEPLOY_VERSION__
 */
class XmlHelper
{
	/**
	 * Method to get a string attribute from an XML element.
	 * 
	 * Example:
	 *   $element is an XML element with <resource displayGroup="something">
	 *   $key is "displayGroup"
	 *   Then this method will return "something".
	 *
	 * @param   \SimpleXMLElement|Array  $element  XML object or array
	 * @param   string                   $key      Key to check
	 * @param   string                   $default  Default value to return
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function attributeToString($element, $key, $default = '')
	{
		if (!isset($element[$key]))
		{
			return $default;
		}

		$value = (string) $element[$key];

		return !empty($value) ? $value : $default;
	}

	/**
	 * Method to transform XML to array and get XML attributes
	 *
	 * @param   \SimpleXMLElement|Array  $element  XML object or array
	 * @param   string                   $key      Key to check
	 * @param   boolean                  $default  Default value to return
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function isAttributeTrue($element, $key, $default = false)
	{
		if (!isset($element[$key]))
		{
			return $default;
		}

		return strtolower($element[$key]) == "true" ? true : false;
	}

	/**
	 * Method to transform XML to array and get XML attributes
	 *
	 * @param   \SimpleXMLElement  $xmlElement      XML object to transform
	 * @param   boolean            $onlyAttributes  return only attributes or all elements
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getXMLElementAttributes($xmlElement, $onlyAttributes = true)
	{
		$transformedXML = json_decode(json_encode((array) $xmlElement), true);

		return $onlyAttributes ? $transformedXML['@attributes'] : $transformedXML;
	}
}