<?php
/**
 * Integer value object class.
 *
 * Implemented as an immutable object with a pair of named constructors.
 */

namespace Joomla\Webservices\Type;

final class Integer extends Type
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   integer  $internalValue  Internal value.
	 *
	 * @return  TypeInteger object.
	 * @throws  \BadMethodCallException
	 */
	public static function fromInternal($internalValue)
	{
//		if (!is_integer($internalValue))
//		{
//			throw new \BadMethodCallException('Integer expected');
//		}

		$integer = new Integer;
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
	 * @throws  \BadMethodCallException
	 */
	public static function fromExternal($externalValue)
	{
//		if (!is_integer($externalValue))
//		{
//			throw new \BadMethodCallException('Integer expected');
//		}

		$integer = new Integer;
		$integer->internal = $externalValue;
		$integer->external = $externalValue;

		return $integer;
	}
}

