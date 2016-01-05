<?php
/**
 * Dummy value object class.
 * 
 * Does not apply any transformation to the given values.
 * Implemented as an immutable object with a pair of named constructors.
 */

namespace Joomla\Webservices\Type;

final class TypeNone extends AbstractType
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   string  $internalValue  Internal value.
	 *
	 * @return  TypeState object.
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
