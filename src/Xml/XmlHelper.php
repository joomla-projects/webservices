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
 * @since       __DELPOY_VERSION__
 */
class XmlHelper
{
	/**
	 * Method to transform XML to array and get XML attributes
	 *
	 * @param   \SimpleXMLElement|Array  $element  XML object or array
	 * @param   string                   $key      Key to check
	 * @param   string                   $default  Default value to return
	 *
	 * @return  boolean
	 *
	 * @since   __DELPOY_VERSION__
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
}