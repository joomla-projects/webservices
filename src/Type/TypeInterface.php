<?php
/**
 * Type interface.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Type;

/**
 * Type interface.
 *
 * @since  __DEPLOY_VERSION__
 */
interface TypeInterface
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   mixed  $internalValue  Internal value.
	 *
	 * @return  An object of the child's type.
	 *
	 * @throws  \BadMethodCallException
	 */
	public static function fromInternal($internalValue);

	/**
	 * Public named constructor to create a new object from an external value.
	 *
	 * @param   mixed  $externalValue  External value.
	 *
	 * @return  An object of the child's type.
	 *
	 * @throws  \BadMethodCallException
	 */
	public static function fromExternal($externalValue);

	/**
	 * Get the external value of the type.
	 *
	 * @return  mixed
	 */
	public function getExternal();

	/**
	 * Get the internal value of the type.
	 *
	 * @return  mixed
	 */
	public function getInternal();
}
