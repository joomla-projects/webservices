<?php
/**
 * Type value object class.
 *
 * Implemented as an immutable object with a pair of named constructors.
 * You should always create a new object of the required type by either
 * calling the static fromInternal method to create an object from an
 * internal value, or the static fromExternal method to create an object
 * from an external value.  Validation is done in these constructors
 * during object instantiation and since the object is immutable it is
 * always valid and no separate validation methods are required.
 */

namespace Joomla\Webservices\Type;

abstract class AbstractType implements TypeInterface
{
	/**
	 * Has this class been instantiated yet?
	 */
	private $constructed = false;

	/**
	 * Internal value.
	 */
	protected $internal = null;

	/**
	 * External value.
	 */
	protected $external = null;

	/**
	 * Hide the public constructor to force use of named constructors instead.
	 * Also prevent calling the constructor more than once.
	 *
	 * @throws \BadMethodCallException
	 */
	final protected function __construct()
	{
		if ($this->constructed === true)
		{
			throw new \BadMethodCallException('This is an immutable object');
		}

		$this->constructed = true;
	}

	/**
	 * Prevent setting undeclared properties.
	 *
	 * @param   string  $name   This is an immutable object, setting $name is not allowed.
	 * @param   mixed   $value  This is an immutable object, setting $value is not allowed.
	 *
	 * @return  null  This method always throws an exception.
	 *
	 * @throws  \BadMethodCallException
	 */
	public function __set($name, $value)
	{
		throw new \BadMethodCallException('This is an immutable object');
	}

	/**
	 * Get the external value of the type.
	 *
	 * @return  mixed
	 */
	public function getExternal()
	{
		return $this->external;
	}

	/**
	 * Get the internal value of the type.
	 *
	 * @return  mixed
	 */
	public function getInternal()
	{
		return $this->internal;
	}
}
