<?php
/**
 * Profile class.
 *
 * Profiles are like schemas and define a kind of template which is used to construct a resource
 * object in memory.  Representations can then be derived from the resource object.  A profile
 * defines the name and type of each property and could be extended with other information.
 * An automatic process could be written to generate machine-readable profile documents, such as
 * ALPS or JSON-LD contexts from the profile.
 *
 * New data types can be added by adding a class file in the Type directory.  The new type can
 * then be referenced in the profile.
 */

namespace Joomla\Webservices\Webservices;

class Profile
{
	/**
	 * Profile schema.
	 * 
	 * @var  \SimpleXMLSchema
	 */
	private $schema = null;

	/**
	 * Array of resource property definitions.
	 */
	private $properties = array();

	/**
	 * Constructor.
	 *
	 * @param   \SimpleXMLElement  $profileSchema  A schema describing the profile.
	 */
	public function __construct(\SimpleXMLElement $profileSchema)
	{
		$this->schema = $profileSchema;

		// Save the name of the profile.
		$this->name = $profileSchema->name;

		// Match each property to a data type.
//		foreach ($profileSchema->properties as $property)
//		{
//			$this->properties[$property->name] = $property->type;
//		}
	}

	/**
	 * Get the array of property definition objects.
	 *
	 * @return  array of property definition objects.
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * Get an individual property definition.
	 *
	 * @param   string  $name  Name of the property.
	 *
	 * @return  Property definition object or false if it doesn't exist.
	 */
	public function getProperty($name)
	{
		if (!isset($this->properties[$name]))
		{
			return false;
		}

		return $this->properties[$name];
	}

	/**
	 * Get a subprofile within the main profile.  Only needed for read profiles.
	 * 
	 * @TODO Consider splitting the XML files so this is unnecessary.
	 * 
	 * @param   string  $target  Target ('item' or 'list').
	 * 
	 * @return  \SimpleXMLElement
	 */
	public function getSubprofile($target)
	{
		return $this->schema->$target;
	}
}