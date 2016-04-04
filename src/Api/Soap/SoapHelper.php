<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Api\Soap;

use Joomla\Filesystem\Path;
use Joomla\Webservices\Xml\XmlHelper;
use Joomla\Webservices\Webservices\WebserviceHelper;
use Joomla\Webservices\Api\Soap\Transform\TransformInterface;

/**
 * Helper class for SOAP calls
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.4
 */
class SoapHelper
{
	/**
	 * Returns generated WSDL file for the webservice
	 *
	 * @param   string  $message    Message for the soap Fault
	 * @param   string  $faultCode  Fault code for soap response
	 *
	 * @return  string
	 */
	public static function createSoapFaultResponse($message, $faultCode = 'SOAP-ENV:Server')
	{
		return '<?xml version="1.0" encoding="UTF-8"?>
			<SOAP-ENV:Envelope
			    xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
			    <SOAP-ENV:Body>
			        <SOAP-ENV:Fault>
			            <faultcode>' . $faultCode . '</faultcode>
			            <faultstring>' . $message . '</faultstring>
			        </SOAP-ENV:Fault>
			    </SOAP-ENV:Body>
			</SOAP-ENV:Envelope>';
	}

	/**
	 * Returns generated WSDL file for the webservice
	 *
	 * @param   \SimpleXMLElement  $webservice  Webservice configuration xml
	 * @param   string             $wsdlPath    Path of WSDL file
	 *
	 * @return  \SimpleXMLElement
	 */
	public static function generateWsdl($webservice, $wsdlPath)
	{
		$wsdl = new Wsdl($webservice);

		return $wsdl->generateWsdl($wsdlPath);
	}

	/**
	 * Add an element with a certain collection of fields
	 *
	 * @param   array              $fields            Array of fields to add
	 * @param   \SimpleXMLElement  &$typeSchema       typeSchema to add the new elements to
	 * @param   string             $typeName          Name of the complexType to create (if $elementName is included, this is ignored)
	 * @param   boolean            $validateOptional  Optional parameter to validate if the fields are optional.
	 *                                                Otherwise they're always set as required
	 * @param   string             $elementName       Name of the optional element to create
	 *
	 * @return  void
	 */
	public static function addElementFields($fields, &$typeSchema, $typeName, $validateOptional = false, $elementName = '')
	{
		if ($elementName != '')
		{
			// Element
			$element = $typeSchema->addChild('element', null, 'http://www.w3.org/2001/XMLSchema');
			$element->addAttribute('name', $elementName);

			// Complex type
			$complexType = $element->addChild('complexType', null, 'http://www.w3.org/2001/XMLSchema');
		}
		else
		{
			// Complex type
			$complexType = $typeSchema->addChild('complexType', null, 'http://www.w3.org/2001/XMLSchema');
			$complexType->addAttribute('name', $typeName);
		}

		if ($fields && !empty($fields))
		{
			// Sequence
			$sequence = $complexType->addChild('sequence', null, 'http://www.w3.org/2001/XMLSchema');

			foreach ($fields as $field)
			{
				// @todo deal with ArrayDefined and ArrayRequired having uppercase chars
				$transformClass = '\\Joomla\\Webservices\\Api\\Soap\\Transform\\Transform' . ucfirst(isset($field['transform']) ? $field['transform'] : 'string');

				if (!class_exists($transformClass))
				{
					$transformClass = '\\Joomla\\Webservices\\Api\\Soap\\Transform\\TransformBase';
				}

				/** @var \Joomla\Webservices\Api\Soap\Transform\TransformInterface $transform */
				$transform = new $transformClass;

				if (!($transform instanceof TransformInterface))
				{
					throw new \RuntimeException('Transform class must implement the transform interface');
				}

				$transform->wsdlField(
					$field, $sequence, $typeSchema,
					($elementName != '' ? $elementName : $typeName),
					$validateOptional,
					(isset($field['fields']) ? $field['fields'] : array())
				);
			}
		}
	}

	/**
	 * Returns output resources by filtering out _links and _messages
	 *
	 * @param   \SimpleXMLElement  $xmlElement        Xml element
	 * @param   string             $resourceSpecific  Optionally limits the results to a certain specific resource
	 * @param   boolean            $namesOnly         Optionally create an array of names only
	 *
	 * @return  array
	 */
	public static function getOutputResources($xmlElement, $resourceSpecific = '', $namesOnly = false)
	{
		$outputResources = array();

		if (isset($xmlElement->resources->resource))
		{
			/** @var \SimpleXMLElement $resource */
			foreach ($xmlElement->resources->resource as $resource)
			{
				$displayGroup = XmlHelper::attributeToString($resource, 'displayGroup');

				switch ($displayGroup)
				{
					case '_links':
					case '_messages':
						break;

					default:
						if (($resourceSpecific != '' && XmlHelper::attributeToString($resource, 'resourceSpecific') == $resourceSpecific)
							|| $resourceSpecific == '')
						{
							if ($namesOnly)
							{
								$outputResources[] = XmlHelper::attributeToString($resource, 'displayName');
							}
							else
							{
								$resource->addAttribute('name', $resource['displayName']);
								$outputResources[] = $resource;
							}
						}
				}
			}
		}

		return $outputResources;
	}

	/**
	 * Returns the resoult resource from a certain operation
	 *
	 * @param   \SimpleXMLElement  $xmlElement  Xml element
	 *
	 * @return  array
	 */
	public static function getResultResource($xmlElement)
	{
		if (isset($xmlElement->resources->resource))
		{
			/** @var \SimpleXMLElement $resource */
			foreach ($xmlElement->resources->resource as $resource)
			{
				$displayName = XmlHelper::attributeToString($resource, 'displayName');
				$resourceSpecific = XmlHelper::attributeToString($resource, 'resourceSpecific');

				if ($displayName == 'result' && $resourceSpecific == 'global')
				{
					$resource->addAttribute('name', $resource['displayName']);

					return $resource;
				}
			}
		}

		$resource = new \SimpleXMLElement('<resource name="result" displayName="result" transform="boolean" fieldFormat="{result}" />');

		return $resource;
	}

	/**
	 * Method to determine the wsdl file name
	 *
	 * @param   string  $client          Client
	 * @param   string  $webserviceName  Name of the webservice
	 * @param   string  $version         Suffixes to the file name (ex. 1.0.0)
	 * @param   string  $path            Path to webservice files
	 *
	 * @return  string  The full path to the api file
	 *
	 * @since   1.4
	 */
	public static function getWsdlFilePath($client, $webserviceName, $version = '', $path = '')
	{
		if (!empty($webserviceName))
		{
			$version = !empty($version) ? Path::clean($version) : '1.0.0';
			$webservicePath = !empty($path) ? WebserviceHelper::getWebservicesRelativePath() . '/' . $path : WebserviceHelper::getWebservicesRelativePath();

			$rawPath = $webserviceName . '.' . $version . '.wsdl';
			$rawPath = !empty($client) ? $client . '.' . $rawPath : $rawPath;

			return $webservicePath . '/' . $rawPath;
		}

		return '';
	}

	/**
	 * Select resources from output array to display them in SOAP output list
	 *
	 * @param   array  $outputResources  Selected output resources from the ws xml config file
	 * @param   array  $items            Output resources with final values
	 *
	 * @return  array  Array of selected resources and value in simple array for SOAP output
	 *
	 * @since   1.4
	 */
	public static function selectListResources($outputResources, $items)
	{
		$response = array();

		if ($items)
		{
			foreach ($items as $item)
			{
				$object = new \stdClass;
				$i = 0;

				foreach ($item as $field => $value)
				{
					if (is_array($value))
					{
						foreach ($value as $fieldint => $valueint)
						{
							if (in_array($fieldint, $outputResources))
							{
								$object->$fieldint = $valueint;
								$i++;
							}
						}
					}
					else
					{
						if (in_array($field, $outputResources))
						{
							$object->$field = $value;
							$i++;
						}
					}
				}

				$response[] = $object;
			}
		}

		return $response;
	}

	/**
	 * Gets an array of fields ready for SOAP documentation purposes
	 *
	 * @param   array    $fields       Array of fields using their xml properties (using 'name' for the field name itself)
	 * @param   boolean  $allRequired  Mark all the fields as required
	 * @param   string   $assignation  Assignation operation
	 *
	 * @return  array
	 *
	 * @since   1.4
	 */
	public static function documentationFields($fields, $allRequired = false, $assignation = '=')
	{
		$fieldsArray = array();

		if ($fields && is_array($fields))
		{
			foreach ($fields as $field)
			{
				$transform = XmlHelper::attributeToString($field, 'transform', 'string');
				$defaultValue = XmlHelper::attributeToString($field, 'defaultValue', 'null');

				if ($defaultValue == 'null' && ($allRequired || XmlHelper::isAttributeTrue($field, 'isRequiredField')))
				{
					$transformClass = '\\Joomla\\Webservices\\Api\\Soap\\Transform\\Transform' . ucfirst($transform);

					if (!class_exists($transformClass))
					{
						$transformClass = '\\Joomla\\Webservices\\Api\\Soap\\Transform\\TransformBase';
					}

					$transformObject = new $transformClass;
					$defaultValue = $transformObject->defaultValue;
				}

				$fieldsArray[] = '$' .
					XmlHelper::attributeToString($field, 'name') .
					' ' . $assignation . ' (' . $transform . ') ' .
					$defaultValue;
			}
		}

		return $fieldsArray;
	}
}
