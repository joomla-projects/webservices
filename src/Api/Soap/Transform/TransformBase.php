<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Api\Soap\Transform;

use Joomla\Webservices\Xml\XmlHelper;

/**
 * Interface to transform api output for SOAP
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.4
 */
class TransformBase implements TransformInterface
{
	/**
	 * SimpleXMLElement element
	 *
	 * @var    \SimpleXMLElement  Element added to the sequence
	 * @since  1.4
	 */
	public $element = null;

	/**
	 * string $type
	 *
	 * @var    string  Base SOAP type
	 * @since  1.4
	 */
	public $type = 's:string';

	/**
	 * string $defaultValue
	 *
	 * @var    string  Default value when not null
	 * @since  1.4
	 */
	public $defaultValue = '\'\'';

	/**
	 * Constructor function
	 *
	 * @since  1.4
	 */
	public function __construct()
	{
	}

	/**
	 * Method to transform a type to publish it in the WSDL file
	 *
	 * @param   array              $field             Field definition.
	 * @param   \SimpleXMLElement  &$sequence         XML with the fields sequence
	 * @param   \SimpleXMLElement  &$typeSchema       XML of the typeSchema in case new derived types need to be added
	 * @param   string             $elementName       Parent element name to add the new derived types with unique names
	 * @param   boolean            $validateOptional  Optional parameter to validate if the field is optional.  Otherwise it's always set as required
	 * @param   array              $extraFields       Array of extra fields to process - in case of array types
	 *
	 * @return void
	 */
	public function wsdlField($field, &$sequence, &$typeSchema, $elementName, $validateOptional = false, $extraFields = array())
	{
		if (!isset($this->element))
		{
			$this->element = $sequence->addChild('element', null, 'http://www.w3.org/2001/XMLSchema');
		}

		if (!isset($this->element['minOccurs']))
		{
			$this->element->addAttribute(
				'minOccurs',
				(($validateOptional && XmlHelper::isAttributeTrue($field, 'isRequiredField') || !$validateOptional) ? '1' : '0')
			);
		}

		if (!isset($this->element['maxOccurs']))
		{
			$this->element->addAttribute('maxOccurs', XmlHelper::attributeToString($field, 'maxOccurs', 1));
		}

		if (!isset($this->element['name']) && isset($field['name']))
		{
			$this->element->addAttribute('name', $field['name']);
		}

		if ($this->type != '')
		{
			$this->element->addAttribute('type', $this->type);
		}
	}
}
