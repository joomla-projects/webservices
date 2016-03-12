<?php
/**
 * Dummy value object class.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Type;

/**
 * Dummy value object class.
 *
 * Does not apply any transformation to the given values.
 * Implemented as an immutable object with a pair of named constructors.
 *
 * @since  __DEPLOY_VERSION__
 */
final class TypeNone extends AbstractType
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
		$none = new TypeNone;
		$none->internal = $internalValue;
		$none->external = $internalValue;

		return $none;
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
		$none = new TypeNone;
		$none->internal = $externalValue;
		$none->external = $externalValue;

		return $none;
	}
}
