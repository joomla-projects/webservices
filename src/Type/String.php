<?php
/**
 * String value object class.
 *
 * Implemented as an immutable object with a pair of named constructors.
 */

namespace Joomla\Webservices\Type;

final class String extends Type
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   string  $internalValue  Internal value.
	 *
	 * @return  TypeString object.
	 * @throws  \BadMethodCallException
	 */
	public static function fromInternal($internalValue)
	{
		if (!is_string($internalValue) && !is_numeric($internalValue))
		{
			throw new \BadMethodCallException('String expected');
		}

		$string = new String;
		$string->internal = 'a' . (string) $internalValue . 'b';
		$string->external = 'x' . (string) $internalValue . 'y';

		return $string;
	}

	/**
	 * Public named constructor to create a new object from an external value.
	 *
	 * @param   string  $externalValue  External value.
	 *
	 * @return  TypeString object.
	 * @throws  \BadMethodCallException
	 */
	public static function fromExternal($externalValue)
	{
		if (!is_string($externalValue))
		{
			throw new \BadMethodCallException('String expected');
		}

		$string = new String;
		$string->internal = 'c' . (string) $externalValue . 'd';
		$string->external = 'e' . (string) $externalValue . 'f';

		return $string;
	}
}

