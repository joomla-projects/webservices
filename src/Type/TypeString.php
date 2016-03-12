<?php
/**
 * String value object class.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Type;

/**
 * String value object class.
 *
 * Implemented as an immutable object with a pair of named constructors.
 *
 * @since  __DEPLOY_VERSION__
 */
final class TypeString extends AbstractType
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   string  $internalValue  Internal value.
	 *
	 * @return  TypeString object.
	 *
	 * @throws  \BadMethodCallException
	 */
	public static function fromInternal($internalValue)
	{
		if (is_array($internalValue) || is_object($internalValue))
		{
			throw new \BadMethodCallException('String expected');
		}

		$string = new TypeString;
		$string->internal = (string) $internalValue;
		$string->external = (string) $internalValue;

		return $string;
	}

	/**
	 * Public named constructor to create a new object from an external value.
	 *
	 * @param   string  $externalValue  External value.
	 *
	 * @return  TypeString object.
	 *
	 * @throws  \BadMethodCallException
	 */
	public static function fromExternal($externalValue)
	{
		if (!is_string($externalValue))
		{
			throw new \BadMethodCallException('String expected');
		}

		$string = new TypeString;
		$string->internal = (string) $externalValue;
		$string->external = (string) $externalValue;

		return $string;
	}
}
