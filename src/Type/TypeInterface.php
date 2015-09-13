<?php
/**
 * Type interface.
 */

namespace Joomla\Webservices\Type;

interface TypeInterface
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   mixed  $internalValue  Internal value.
	 *
	 * @return  An object of the child's type.
	 * @throws  \BadMethodCallException
	 */
	public static function fromInternal($internalValue);

	/**
	 * Public named constructor to create a new object from an external value.
	 *
	 * @param   mixed  $externalValue  External value.
	 *
	 * @return  An object of the child's type.
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
