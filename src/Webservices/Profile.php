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

use Joomla\Registry\Registry;
use Joomla\Webservices\Xml\XmlHelper;

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
     * The XML taken as the argument to this constructor is not the full XML but
     * only the level beneath <operations>.  eg. <read>, <delete>, etc.
	 *
	 * @param   \SimpleXMLElement  $profileSchema  A schema describing the profile.
	 */
	public function __construct(\SimpleXMLElement $profileSchema)
	{
		$this->schema = $profileSchema;

		// Save the name of the profile (eg. "read", "delete", etc.).
		$this->name = $profileSchema->getName();

		// Match each property to a data type.
//		foreach ($profileSchema->properties as $property)
//		{
//			$this->properties[$property->name] = $property->type;
//		}
	}

    /**
     * Takes a resource array and fills in missing attributes with default values.
     *
     * @param   array   $resource          Resource array.
     * @param   string  $resourceSpecific  Resource specific container.
     *
     * @return  array
     */
    public function defaultResourceField($resource = array(), $resourceSpecific = 'rcwsGlobal')
    {
        $defaultResource = array(
            'resourceSpecific' => !empty($resource['resourceSpecific']) ? $resource['resourceSpecific'] : $resourceSpecific,
            'displayGroup'     => !empty($resource['displayGroup']) ? $resource['displayGroup'] : '',
            'displayName'      => !empty($resource['displayName']) ? $resource['displayName'] : '',
            'fieldFormat'      => !empty($resource['fieldFormat']) ? $resource['fieldFormat'] : '',
            'transform'        => !empty($resource['transform']) ? $resource['transform'] : '',
            'linkName'         => !empty($resource['linkName']) ? $resource['linkName'] : '',
            'linkTitle'        => !empty($resource['linkTitle']) ? $resource['linkTitle'] : '',
            'hrefLang'         => !empty($resource['hrefLang']) ? $resource['hrefLang'] : '',
            'linkTemplated'    => !empty($resource['linkTemplated']) ? $resource['linkTemplated'] : '',
            'linkRel'          => !empty($resource['linkRel']) ? $resource['linkRel'] : '',
            'description'      => !empty($resource['description']) ? $resource['description'] : '',
        );

        return array_merge($resource, $defaultResource);
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
     * Returns an array of resources and their attributes from the profile.
     * 
     * Returns an array of the data loaded from the <resources> section of the
     * XML configuration file for the relevant task.  Some <resource> elements may
     * be filtered out depending on options passed in the constructor.  An optional
     * subprofile name may be specified where, for example, the <read> element has
     * a <list> or <item> subelement.
     *
     * Options:
     *   filterOutResourcesGroups  Array of displayGroup values to be ignored.
     *   filterResourcesSpecific   Only elements with this resourceSpecific value will be returned.
     *   filterDisplayName         Only elements with this displayName value will be returned. (This doesn't appear to be used).
     *
     * @param   Registry  $options     A registry containing options.
     * @param   string    $subprofile  Optional subprofile name within the main profile.
     *
     * @return  array of resource properties.
     */
    public function getResources(Registry $options, $subprofile = '')
    {
        $resources = array();
        $resourcesRoot = $this->schema;

        // If we have a subprofile, then adjust the root.
        if ($subprofile != '' && isset($resourcesRoot->$subprofile))
        {
            $resourcesRoot = $resourcesRoot->$subprofile;
        }

        // Do we have at least one <resource> element?
        if (!isset($resourcesRoot->resources->resource))
        {
            return $resources;
        }

        // Go through each of the <resource> elements in turn.
        foreach ($resourcesRoot->resources->resource as $resourceXML)
        {
            $resource = XmlHelper::getXMLElementAttributes($resourceXML);

            // Filters out specified displayGroup values.
            if ($options->get('filterOutResourcesGroups') != ''
                && in_array($resource['displayGroup'], $options->get('filterOutResourcesGroups')))
            {
                continue;
            }

            // Filters out if the optional resourceSpecific filter is not the one defined.
            if ($options->get('filterResourcesSpecific') != ''
                && $resource['resourceSpecific'] != $options->get('filterResourcesSpecific'))
            {
                continue;
            }

            // Filters out if the optional displayName filter is not the one defined.
            if ($options->get('filterDisplayName') != ''
                && $resource['displayName'] != $options->get('filterDisplayName'))
            {
                continue;
            }

            if (!empty($resourceXML->description))
            {
                $resource['description'] = $resourceXML->description;
            }

            $resource = $this->defaultResourceField($resource);
            $resourceName = $resource['displayName'];
            $resourceSpecific = $resource['resourceSpecific'];

            $resources[$resourceSpecific][$resourceName] = $resource;
        }

        return $resources;
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