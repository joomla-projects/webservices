<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Api\Hal\Transform;

/**
 * Interface to transform api output
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.2
 */
abstract class TransformBase implements TransformInterface
{
	/**
	 * Method to transform an internal representation to an external one.
	 *
	 * @param   string  $definition  Field definition.
	 *
	 * @return string Transformed value.
	 */
	public static function toExternal($definition)
	{
		return $definition;
	}

	/**
	 * Method to transform an external representation to an internal one.
	 *
	 * @param   string  $definition  Field definition.
	 *
	 * @return string Transformed value.
	 */
	public static function toInternal($definition)
	{
		return $definition;
	}
}
