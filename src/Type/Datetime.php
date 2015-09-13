<?php
/**
 * Datetime value object class.
 *
 * Implemented as an immutable object with a pair of named constructors.
 */

namespace Joomla\Webservices\Type;

final class Datetime extends Type
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   string  $internalValue  Internal value (must be in SQL format).
	 *
	 * @return  TypeDatetime object.
	 * @throws  \BadMethodCallException
	 */
	public static function fromInternal($internalValue)
	{
		$datetime = new Datetime;

		try
		{
			$datetime->internal = \DateTime::createFromFormat('Y-m-d H:i:s', $internalValue);

			if ($datetime->internal instanceof \DateTime)
			{
				$datetime->external = $datetime->internal->format(\DateTime::ISO8601);
			}
		}
		catch (\Exception $e)
		{
			$errors = \DateTime::getLastErrors();
			$errorMessage = 'Date/time parse error(s): ';
			$errorMessage .= implode(', ', array_merge($errors['warnings'], $errors['errors']));

			throw new \BadMethodCallException($errorMessage);
		}

		return $datetime;
	}

	/**
	 * Public named constructor to create a new object from an external value.
	 *
	 * @param   string  $externalValue  External value (must be ISO8601 format).
	 *
	 * @return  TypeDatetime object.
	 * @throws  \BadMethodCallException
	 */
	public static function fromExternal($externalValue)
	{
		$datetime = new Datetime;

		try
		{
			$datetime->external = \DateTime::createFromFormat('Y-m-d H:i:s', $externalValue);

			if ($datetime->external instanceof \DateTime)
			{
				$datetime->internal = $datetime->external->format('Y-m-d H:i:s');
			}
		}
		catch (\Exception $e)
		{
			$errors = \DateTime::getLastErrors();
			$errorMessage = 'Date/time parse error(s): ';
			$errorMessage .= implode(', ', array_merge($errors['warnings'], $errors['errors']));

			throw new \BadMethodCallException($errorMessage);
		}

		return $datetime;
	}
}
