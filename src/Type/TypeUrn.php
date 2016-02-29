<?php
/**
 * URN value object class.
 *
 * Only URNs in the "joomla" namespace are acceptable.
 * The syntax of "joomla" URNs is as follows:
 *    urn:joomla:{resourceName}:{id}
 *
 * Implemented as an immutable object with a pair of named constructors.
 */

namespace Joomla\Webservices\Type;

final class TypeUrn extends AbstractType
{
	/**
	 * Public named constructor to create a new object from an internal value.
	 *
	 * @param   string  $internalValue  Internal value (must be a URN).
	 *
	 * @return  TypeUrn object.
	 * @throws  \BadMethodCallException
	 */
	public static function fromInternal($internalValue)
	{
		$state = new TypeUrn;
		$state->internal = $internalValue;
		$state->external = $internalValue;

        $parts = explode(':', $internalValue);

        if ($parts[0] != 'urn')
        {
            throw new \UnexpectedValueException('Internal value is not a URN: ' . $internalValue);
        }

        if ($parts[1] != 'joomla')
        {
            throw new \UnexpectedValueException('Internal value must be a URN in the "joomla" namespace, ' . $parts[1] . ' given');
        }

        if (count($parts) != 4)
        {
            throw new \UnexpectedValueException('Internal value must be a URN with exactly 4 parts');
        }

		return $state;
	}

	/**
	 * Public named constructor to create a new object from an external value.
	 *
	 * @param   string  $externalValue  External value.
	 *
	 * @return  TypeUrn object.
	 * @throws  \BadMethodCallException
	 */
	public static function fromExternal($externalValue)
	{
		$state = new TypeUrn;
		$state->external = $externalValue;
		$state->internal = $externalValue;

        $parts = explode(':', $externalValue);

        if ($parts[0] != 'urn')
        {
            throw new \UnexpectedValueException('External value is not a URN: ' . $externalValue);
        }

        if ($parts[1] != 'joomla')
        {
            throw new \UnexpectedValueException('External value must be a URN in the "joomla" namespace, ' . $parts[1] . ' given');
        }

        if (count($parts) != 4)
        {
            throw new \UnexpectedValueException('External value must be a URN with exactly 4 parts');
        }

        return $state;
	}

    /**
     * Return the raw id without the namespace and type stuff.
     *
     * @return  integer
     */
    public function getId()
    {
        $parts = explode(':', $this->internal);

        return $parts[3];
    }

    /**
     * Return the type of the URN.
     *
     * @return  string
     */
    public function getType()
    {
        $parts = explode(':', $this->internal);

        return $parts[2];
    }
}
