<?php
/**
 * Integer value object class.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Type;

/**
 * Integer value object class.
 *
 * Implemented as an immutable object with a pair of named constructors.
 *
 * @since  __DEPLOY_VERSION__
 */
final class TypeInteger extends AbstractType
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   integer  $internalValue  Internal value.
	 *
	 * @return  TypeInteger object.
	 *
	 * @throws  \BadMethodCallException
	 */
	public static function fromInternal($internalValue)
	{
		$integer = new TypeInteger;
		$integer->internal = $internalValue;
		$integer->external = $internalValue;

		return $integer;
	}

	/**
	 * Public named constructor to create a new object from an external value.
	 *
	 * @param   integer  $externalValue  External value.
	 *
	 * @return  TypeInteger object.
	 *
	 * @throws  \BadMethodCallException
	 */
	public static function fromExternal($externalValue)
	{
		$integer = new TypeInteger;
		$integer->internal = $externalValue;
		$integer->external = $externalValue;

		return $integer;
	}
}
