<?php
/**
 * State value object class.
 *
 * Implemented as an immutable object with a pair of named constructors.
 */

namespace Joomla\Webservices\Type;

final class State extends Type
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
		$state = new State;
		$state->internal = $internalValue;
		$state->external = '';

		switch ($internalValue)
		{
			case '0':
				$state->external = 'unpublished';
				break;

			case '1':
				$state->external = 'published';
				break;

			case '2':
				$state->external = 'archived';
				break;

			case '-2':
				$state->external = 'trashed';
				break;

			default:
				throw new \UnexpectedValueException('Internal value must be "0", "1", "2" or "-2", ' . $internalValue . ' given');
		}

		return $state;
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
		$state = new State;
		$state->external = $externalValue;
		$state->internal = '';

		switch ($externalValue)
		{
			case 'unpublished':
				$state->internal = '0';
				break;

			case 'published':
				$state->internal = '1';
				break;

			case 'archived':
				$state->internal = '2';
				break;

			case 'trashed':
				$state->internal = '-2';
				break;

			default:
				$message = 'External value must be "unpublished", "published", "archived" or "trashed", ' . $externalValue . ' given';
				throw new \UnexpectedValueException($message);
		}

		return $state;
	}
}
