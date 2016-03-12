<?php
/**
 * Boolean value object class.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Type;

/**
 * Boolean value object class.
 *
 * Implemented as an immutable object with a pair of named constructors.
 *
 * @since  __DEPLOY_VERSION__
 */
final class TypeBoolean extends AbstractType
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   mixed  $internalValue  Internal value.
	 *
	 * @return  TypeBoolean object.
	 *
	 * @throws  \BadMethodCallException
	 */
	public static function fromInternal($internalValue)
	{
		$boolean = new TypeBoolean;

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
	 *
	 * @throws  \BadMethodCallException
	 */
	public static function fromExternal($externalValue)
	{
		$boolean = new TypeBoolean;

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
