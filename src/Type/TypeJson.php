<?php
/**
 * JSON value object class.
 *
 * @TODO This class should probably not exist.  Figure out a way to avoid its existence!
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Type;

/**
 * JSON value object class.
 *
 * @TODO This class should probably not exist.  Figure out a way to avoid its existence!
 *
 * Implemented as an immutable object with a pair of named constructors.
 *
 * @since  __DEPLOY_VERSION__
 */
final class TypeJson extends AbstractType
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   string  $internalValue  Internal value.
	 *
	 * @return  TypeState object.
	 *
	 * @throws  \BadMethodCallException
	 */
	public static function fromInternal($internalValue)
	{
		$json = new TypeJson;
		$json->internal = $internalValue;
		$json->external = json_encode($internalValue);

		return $json;
	}

	/**
	 * Public named constructor to create a new object from an external value.
	 *
	 * @param   string  $externalValue  External value.
	 *
	 * @return  TypeState object.
	 *
	 * @throws  \BadMethodCallException
	 */
	public static function fromExternal($externalValue)
	{
		$json = new TypeJson;
		$json->external = $externalValue;
		$json->internal = json_decode($externalValue);

		return $json;
	}
}
