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
	}

    /**
     * Bind data to primary key fields.
     * 
     * @param   array   $data        Array of data value to bind.
     * @param   string  $subprofile  Optional subprofile name within the main profile.
     * 
     * @return  array of bound primary key-value pairs.
     */
    public function bindDataToPrimaryKeys(array $data = array(), $subprofile = '')
    {
        $boundPrimaryKeys = array();

        // Get primary keys.
        $primaryKeys = $this->getFields($subprofile, true);

        // Scan through all the primary key fields.
        foreach ($primaryKeys as $primaryKey => $primaryKeyAttributes)
        {
            // Set the default value.
            $boundPrimaryKeys[$primaryKey] = null;

            // If we have a non-empty data value for the field then override the default.
            if (isset($data[$primaryKey]) && $data[$primaryKey] != '')
            {
                $boundPrimaryKeys[$primaryKey] = $this->transformField($primaryKeyAttributes['transform'], $data[$primaryKey], false);
            }
        }

        return $boundPrimaryKeys;
    }

    /**
     * Get all defined fields and transform them if needed to expected format.
     * Returns them in an array for use in function calls.
     *
     * @param   array  $data  Data array.
     *
     * @return  array of key-value pairs to pass to the function.
     */
    public function buildFunctionArgs(array $data = array())
    {
        $args = array();
        $result = null;

        if (empty($this->schema['functionArgs']))
        {
            $args[] = $data;

            return $args;
        }

        $functionArgs = explode(',', (string) $this->schema['functionArgs']);

        foreach ($functionArgs as $functionArg)
        {
            $parameter = explode('{', $functionArg);

            // First field is the name of the data field and second is transformation.
            $parameter[0] = trim($parameter[0]);
            $parameter[1] = !empty($parameter[1]) ? strtolower(trim(str_replace('}', '', $parameter[1]))) : 'string';
            $parameterValue = null;

            // If we set argument to value, then it will not be transformed, instead we will take field name as a value
            if ($parameter[1] == 'value')
            {
                $parameterValue = $parameter[0];
            }
            else
            {
                if (isset($data[$parameter[0]]))
                {
                    $parameterValue = $this->transformField($parameter[1], $data[$parameter[0]]);
                }
                else
                {
                    $parameterValue = null;
                }
            }

            $args[] = $parameterValue;
        }

        return $args;
    }

    /**
     * Checks that all required fields have values.
     *
     * @param   array  $data  Raw Posted data.
     *
     * @return  array of required field names that did not have values in the data.
     */
    public function checkRequiredFields(array $data = array())
    {
        $requiredFields = array();

        // Get fields from the profile.
        $fields = $this->getFields();

        // Look at each field in turn and return false if we find a required field without a value.
        foreach ($fields as $fieldName => $attributes)
        {
            // Field is not required.
            if (!isset($attributes['isRequiredField']) || $attributes['isRequiredField'] == 'false')
            {
                continue;
            }

            // Field is required; check that we have a value for it.
            if (is_null($data[$fieldName]) || $data[$fieldName] == '')
            {
                $requiredFields[] = $fieldName;
            }
        }

        return $requiredFields;
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
     * Returns an array of data from <field> elements defined in the <fields> section
     * of the configuration XML.
     * 
     * Optionally retrieves primary key fields only.  These are defined as <field>
     * elements which have the attribute isPrimaryField set to true.
     *
     * @param   string   $subprofile   Optional subprofile name within the main profile.
     * @param   boolean  $primaryKeys  Only extract primary keys.
     *
     * @return  array
     */
    public function getFields($subprofile = '', $primaryKeys = false)
    {
        $fields = array();
        $resourcesRoot = $this->schema;

        // If we have a subprofile, then adjust the root.
        if ($subprofile != '' && isset($resourcesRoot->$subprofile))
        {
            $resourcesRoot = $resourcesRoot->$subprofile;
        }

        if (isset($resourcesRoot->fields->field))
        {
            foreach ($resourcesRoot->fields->field as $field)
            {
                $fieldAttributes = XmlHelper::getXMLElementAttributes($field);

                if (($primaryKeys && XmlHelper::isAttributeTrue($field, 'isPrimaryField'))
                    || !$primaryKeys)
                {
                    $fields[$fieldAttributes['name']] = $fieldAttributes;
                }
            }
        }

        // If there are no primary keys defined we will use id field as default
        if (empty($fields) && $primaryKeys)
        {
            $fields['id'] = array('name' => 'id', 'transform' => 'int');
        }

        return $fields;
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

    /**
     * Is the request data for an item or a list?
     * 
     * @param   array  $primaryKeys  Array of primary key-value pairs.
     * 
     * @return  boolean true if request is for an item; false for a list.
     */
    public function isItem(array $data = array())
    {
        // First try to bind the data to the item primary fields.
        $itemPrimaryFields = $this->bindDataToPrimaryKeys($data, 'item');

        // If any primary field is null, return false (= list).
        foreach ($itemPrimaryFields as $primaryKey => $primaryKeyField)
        {
            if (is_null($primaryKeyField))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Transform a source field data value using a transform class.
     *
     * @param   string   $fieldType          Field type.  Determines the transform class to use.
     * @param   mixed    $value              Field value (internal or external, depending on context).
     * @param   boolean  $directionExternal  True to convert from internal to external; false otherwise.
     *
     * @return  mixed Transformed data.
     * 
     * @throws  \InvalidArgumentException
     */
    public function transformField($fieldType, $value, $directionExternal = true)
    {
        $className = 'Joomla\\Webservices\\Type\\Type' . ucfirst($fieldType);

        // If there is no data type throw an exception.
        if (!class_exists($className))
        {
            throw new \InvalidArgumentException('Missing class ' . $className);
        }

        // Convert an internal value to its external equivalent.
        if ($directionExternal)
        {
            return $className::fromInternal($value)->getExternal();
        }

        // Convert an external value to its internal equivalent.
        return $className::fromExternal($value)->getInternal();
    }
}