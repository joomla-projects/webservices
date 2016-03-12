<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Api\Hal\Transform;

use Joomla\Utilities\ArrayHelper;

/**
 * Transform api output
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.2
 */
class TransformArray extends TransformBase
{
	/**
	 * Method to transform an internal representation to an external one.
	 *
	 * @param   mixed  $definition  Field definition.
	 *
	 * @return array Transformed value.
	 */
	public static function toExternal($definition)
	{
		return is_object($definition) ? ArrayHelper::fromObject($definition) : (array) $definition;
	}

	/**
	 * Method to transform an external representation to an internal one.
	 *
	 * @param   mixed  $definition  Field definition.
	 *
	 * @return array Transformed value.
	 */
	public static function toInternal($definition)
	{
		return !empty($definition) ? (array) $definition : array();
	}
}
