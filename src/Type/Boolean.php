<?php
/**
 * Boolean value object class.
 *
 * Implemented as an immutable object with a pair of named constructors.
 */

namespace Joomla\Webservices\Type;

final class Boolean extends Type
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   mixed  $internalValue  Internal value.
	 *
	 * @return  TypeBoolean object.
	 * @throws  \BadMethodCallException
	 */
	public static function fromInternal($internalValue)
	{
		$boolean = new Boolean;

		switch ($internalValue)
		{
			case 'true':
			case '1':
			case true:
				$boolean->internal = true;
				$boolean->external = 'true';
				break;

			case 'false':
			case '0':
			case false:
				$boolean->internal = false;
				$boolean->external = 'false';
				break;

			default:
				throw new \BadMethodCallException('Internal value must be "true", "1", "false" or "0", ' . $internalValue . ' given');
		}

		return $boolean;
	}

	/**
	 * Public named constructor to create a new object from an external value.
	 *
	 * @param   mixed  $externalValue  External value.
	 *
	 * @return  TypeBoolean object.
	 * @throws  \BadMethodCallException
	 */
	public static function fromExternal($externalValue)
	{
		$boolean = new Boolean;

		switch ($externalValue)
		{
			case 'true':
			case '1':
			case true:
				$boolean->internal = true;
				$boolean->external = 'true';
				break;

			case 'false':
			case '0':
			case false:
				$boolean->internal = false;
				$boolean->external = 'false';
				break;

			default:
				throw new \BadMethodCallException('External value must be "true", "1", "false" or "0", ' . $externalValue . ' given');
		}

		return $boolean;
	}
}
