<?php
/**
 * JSON value object class.
 * 
 * @TODO This class should probably not exist.  Figure out a way to avoid its existence!
 *
 * Implemented as an immutable object with a pair of named constructors.
 */

namespace Joomla\Webservices\Type;

final class Json extends Type
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
		$json = new Json;
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
	 * @throws  \BadMethodCallException
	 */
	public static function fromExternal($externalValue)
	{
		$json = new Json;
		$json->external = $externalValue;
		$json->internal = json_decode($externalValue);

		return $json;
	}
}
