<?php
/**
 * Webservices component for Joomla! CMS
 *
 * @copyright  Copyright (C) 2004 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Webservices\Model;

use Joomla\Registry\Registry;

use Webservices\Helper;
use Webservices\Model\FormModel;
use Webservices\Model\WebservicesModel;

jimport('joomla.filesystem.folder');

/**
 * Webservice Model
 *
 * @package     Joomla!
 * @subpackage  Webservices
 * @since       1.0
 */
class WebserviceModel extends FormModel
{
	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @throws  RuntimeException
	 *
	 * @since   1.2
	 */
	public function save($data)
	{
		try
		{
			if (!$this->saveXml($data))
			{
				return false;
			}
		}
		catch (\Exception $e)
		{
			$this->setError(\JText::sprintf('COM_WEBSERVICES_WEBSERVICE_ERROR_SAVING_XML', $e->getMessage()));

			return false;
		}

		// Get Webservices model
		$model = new WebservicesModel($this->context);

		if ($id = $model->installWebservice(
				$data['main']['client'],
				$data['main']['name'],
				$data['main']['version'],
				$data['main']['path'],
				$data['main']['id']
		))
		{
			$this->setState($this->getName() . '.id', $id);
			$this->setState($this->getName() . '.new', empty($data['main']['id']));

			// Update created, modified flags
			return parent::save(array('id' => $id));
		}

		return false;
	}

	/**
	 * Method to save the form data to XML file.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @throws  RuntimeException
	 *
	 * @since   1.4
	 */
	public function saveXml($data)
	{
		$dataRegistry = new \JRegistry($data);
		$item = null;

		if (empty($data['main']['name']))
		{
			$this->setError(JText::_('COM_WEBSERVICES_WEBSERVICE_NAME_FIELD_CANNOT_BE_EMPTY'));

			return false;
		}

		if (!empty($data['main']['id']))
		{
			$item = $this->getItem($data['main']['id']);
		}

		$client = $dataRegistry->get('main.client', 'site');
		$name = $dataRegistry->get('main.name', '');
		$version = $dataRegistry->get('main.version', '1.0.0');
		$folder = $dataRegistry->get('main.path', '');
		$folder = !empty($folder) ? \JPath::clean('/' . $folder) : '';
		$webserviceBasePath = \Joomla\Webservices\Webservices\WebserviceHelper::getWebservicesPath();

		if (!\JFolder::exists($webserviceBasePath . $folder))
		{
			\JFolder::create($webserviceBasePath . $folder);
		}

		$fullPath = \JPath::clean($webserviceBasePath . $folder . '/' . $client . '.' . $name . '.' . $version . '.xml');

		$xml = new \SimpleXMLElement('<?xml version="1.0"?><apiservice client="' . $client . '"></apiservice>');

		$xml->addChild('name', $dataRegistry->get('main.title', $name));
		$xml->addChild('author', $dataRegistry->get('main.author', ''));
		$xml->addChild('copyright', $dataRegistry->get('main.copyright', ''));
		$xml->addChild('description', $dataRegistry->get('main.description', ''));

		$configXml = $xml->addChild('config');
		$configXml->addChild('name', $dataRegistry->get('main.name', ''));
		$configXml->addChild('version', $version);
		$configXml->addChild('authorizationAssetName', $dataRegistry->get('main.authorizationAssetName', ''));

		$operationsXml = $xml->addChild('operations');
		$readXml = null;
		$taskXml = null;

		foreach ($data as $operationName => $operation)
		{
			if ($operationName != 'main')
			{
				if (empty($operation['isEnabled']))
				{
					continue;
				}

				$operationNameSplit = explode('-', $operationName);

				if ($operationNameSplit[0] == 'read' && count($operationNameSplit) > 1)
				{
					if (is_null($readXml))
					{
						$readXml = $operationsXml->addChild('read');
					}

					$operationXml = $readXml->addChild($operationNameSplit[1]);
				}
				elseif ($operationNameSplit[0] == 'task' && count($operationNameSplit) > 1)
				{
					if (is_null($taskXml))
					{
						$taskXml = $operationsXml->addChild('task');
					}

					$operationXml = $taskXml->addChild($operationNameSplit[1]);
				}
				else
				{
					$operationXml = $operationsXml->addChild($operationNameSplit[0]);
				}

				$this->getOperationAttributesFromPost($operationXml, $data, $operationName);
				$this->getFieldsFromPost($operationXml, $data, $operationName);
				$this->getResourcesFromPost($operationXml, $data, $operationName);
			}
		}

		// Needed for formatting
		$dom = dom_import_simplexml($xml)->ownerDocument;
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;

		if ($dom->save($fullPath))
		{
			if (!empty($item->id))
			{
				$folder = !empty($item->path) ? '/' . $item->path : '';
				$oldPath = \JPath::clean($webserviceBasePath . $folder . '/' . $item->xmlFile);

				if ($oldPath != $fullPath)
				{
					if (JFile::exists($oldPath))
					{
						JFile::delete($oldPath);
					}
				}
			}

			$wsdl = \Joomla\Webservices\Api\Soap\SoapHelper::generateWsdl($xml, '');
			$domWsdl = dom_import_simplexml($wsdl)->ownerDocument;
			$domWsdl->preserveWhiteSpace = false;
			$domWsdl->formatOutput = true;
			$fullWsdlPath = substr($fullPath, 0, -4) . '.wsdl';

			if ($domWsdl->save($fullWsdlPath))
			{
				return true;
			}

			return false;
		}

		return false;
	}

	/**
	 * Method to get operation attributes from Post
	 *
	 * @param   SimpleXMLElement  &$xml  Xml element
	 * @param   array             $data  The form data.
	 * @param   string            $name  Name to fetch
	 *
	 * @return  void
	 *
	 * @since   1.4
	 */
	public function getOperationAttributesFromPost(&$xml, $data, $name)
	{
		if (!empty($data[$name]))
		{
			foreach ($data[$name] as $attributeKey => $attributeValue)
			{
				if (in_array($attributeKey, array('isEnabled')))
				{
					continue;
				}

				if (!is_array($attributeValue))
				{
					if ($attributeKey != 'description')
					{
						$xml->addAttribute($attributeKey, $attributeValue);
					}
					else
					{
						if (!empty($attributeValue))
						{
							$this->addChildWithCDATA($xml, $attributeKey, $attributeValue);
						}
					}
				}
			}
		}
	}

	/**
	 * Method to get fields from Post
	 *
	 * @param   SimpleXMLElement  &$xml  Xml element
	 * @param   array             $data  The form data.
	 * @param   string            $name  Name to fetch
	 *
	 * @return  void
	 *
	 * @since   1.4
	 */
	public function getFieldsFromPost(&$xml, $data, $name)
	{
		$mainFieldsXml = null;

		if (!empty($data[$name]['fields']['field']) || !empty($data[$name]['fields']['description']))
		{
			$mainFieldsXml = $xml->addChild('fields');
		}

		if (!empty($data[$name]['fields']['field']))
		{
			foreach ($data[$name]['fields']['field'] as $fieldJson)
			{
				$field = json_decode($fieldJson, true);

				if (!empty($field))
				{
					$fieldChild = $mainFieldsXml->addChild('field');

					foreach ($field as $attributeKey => $attributeValue)
					{
						if ($attributeKey != 'description')
						{
							$fieldChild->addAttribute($attributeKey, $attributeValue);
						}
						else
						{
							if (!empty($attributeValue))
							{
								$this->addChildWithCDATA($fieldChild, 'description', $attributeValue);
							}
						}
					}
				}
			}
		}

		if (!empty($data[$name]['fields']['description']))
		{
			$this->addChildWithCDATA($mainFieldsXml, 'description', $data[$name]['fields']['description']);
		}
	}

	/**
	 * Method to get resources from Post
	 *
	 * @param   SimpleXMLElement  &$xml  Xml element to add resources
	 * @param   array             $data  The form data.
	 * @param   string            $name  Name to fetch
	 *
	 * @return  void
	 *
	 * @since   1.4
	 */
	public function getResourcesFromPost(&$xml, $data, $name)
	{
		$mainResourcesXml = null;

		if (!empty($data[$name]['resources']['resource']) || !empty($data[$name]['resources']['description']))
		{
			$mainResourcesXml = $xml->addChild('resources');
		}

		if (!empty($data[$name]['resources']['resource']))
		{
			foreach ($data[$name]['resources']['resource'] as $resourceJson)
			{
				$resource = json_decode($resourceJson, true);

				if (!empty($resource))
				{
					$resourceChild = $mainResourcesXml->addChild('resource');

					foreach ($resource as $attributeKey => $attributeValue)
					{
						if ($attributeKey != 'description')
						{
							$resourceChild->addAttribute($attributeKey, $attributeValue);
						}
						else
						{
							if (!empty($attributeValue))
							{
								$this->addChildWithCDATA($resourceChild, 'description', $attributeValue);
							}
						}
					}
				}
			}
		}

		if (!empty($data[$name]['resources']['description']))
		{
			$this->addChildWithCDATA($mainResourcesXml, 'description', $data[$name]['resources']['description']);
		}
	}

	/**
	 * Method to add child with text inside CDATA
	 *
	 * @param   SimpleXMLElement  &$xml   Xml element
	 * @param   string            $name   Name of the child
	 * @param   string            $value  Value of the child
	 *
	 * @return  SimpleXMLElement
	 *
	 * @since   1.4
	 */
	public function addChildWithCDATA(&$xml, $name, $value = '')
	{
		$newChild = $xml->addChild($name);

		if (!is_null($newChild))
		{
			$node = dom_import_simplexml($newChild);
			$no   = $node->ownerDocument;
			$node->appendChild($no->createCDATASection($value));
		}

		return $newChild;
	}

	/**
	 * Return mapped array for the form data
	 *
	 * @return  array
	 *
	 * @since   1.4
	 */
	public function bindXMLToForm()
	{
		// Read operation is a special because it is part of the two separate read types
		$this->formData = array('read-list' => array(), 'read-item' => array());

		if (empty($this->xmlFile))
		{
			return $this->formData;
		}

		$this->formData['main'] = array(
			'author' => (string) $this->xmlFile->author,
			'copyright' => (string) $this->xmlFile->copyright,
			'description' => (string) $this->xmlFile->description,
			'authorizationAssetName' => !empty($this->xmlFile->config->authorizationAssetName) ? (string) $this->xmlFile->config->authorizationAssetName : '',
		);

		// Get attributes and descriptions
		if ($operations = $this->xmlFile->xpath('//operations'))
		{
			$operations = $operations[0];

			foreach ($operations as $name => $operation)
			{
				if ($name == 'read')
				{
					$this->formData[$name . '-list'] = $this->bindPathToArray('//operations/' . $name . '/list', $this->xmlFile);
					$this->formData[$name . '-item'] = $this->bindPathToArray('//operations/' . $name . '/item', $this->xmlFile);

					$this->setFieldsAndResources($name . '-list', '//operations/' . $name . '/list', $this->xmlFile);
					$this->setFieldsAndResources($name . '-item', '//operations/' . $name . '/item', $this->xmlFile);

					if (!empty($this->formData[$name . '-list']) && !isset($this->formData[$name . '-list']['isEnabled']))
					{
						// Since this operation exists in XML file we are enabling it by default
						$this->formData[$name . '-list']['isEnabled'] = 1;
					}

					if (!empty($this->formData[$name . '-item']) && !isset($this->formData[$name . '-item']['isEnabled']))
					{
						// Since this operation exists in XML file we are enabling it by default
						$this->formData[$name . '-item']['isEnabled'] = 1;
					}
				}
				elseif ($name == 'task')
				{
					if ($tasks = $this->xmlFile->xpath('//operations/task'))
					{
						$tasks = $tasks[0];

						foreach ($tasks as $taskName => $task)
						{
							$this->formData['task-' . $taskName] = $this->bindPathToArray('//operations/task/' . $taskName, $this->xmlFile);
							$this->setFieldsAndResources('task-' . $taskName, '//operations/task/' . $taskName, $this->xmlFile);

							if (!empty($this->formData['task-' . $taskName]) && !isset($this->formData['task-' . $taskName]['isEnabled']))
							{
								// Since this operation exists in XML file we are enabling it by default
								$this->formData['task-' . $taskName]['isEnabled'] = 1;
							}
						}
					}
				}
				else
				{
					$this->formData[$name] = $this->bindPathToArray('//operations/' . $name, $this->xmlFile);
					$this->setFieldsAndResources($name, '//operations/' . $name, $this->xmlFile);

					if (!empty($this->formData[$name]) && !isset($this->formData[$name]['isEnabled']))
					{
						// Since this operation exists in XML file we are enabling it by default
						$this->formData[$name]['isEnabled'] = 1;
					}
				}
			}
		}

		// Set default operations if not present in loaded XML file
		if ($operations = $this->defaultXmlFile->xpath('//operations'))
		{
			$operations = $operations[0];

			foreach ($operations as $name => $operation)
			{
				if (empty($this->formData[$name]))
				{
					if ($name == 'read')
					{
						if (empty($this->formData[$name . '-list']))
						{
							$this->formData[$name . '-list'] = $this->bindPathToArray('//operations/' . $name . '/list', $this->defaultXmlFile);
							$this->setFieldsAndResources($name . '-list', '//operations/' . $name . '/list', $this->defaultXmlFile);
						}

						if (empty($this->formData[$name . '-item']))
						{
							$this->formData[$name . '-item'] = $this->bindPathToArray('//operations/' . $name . '/item', $this->defaultXmlFile);
							$this->setFieldsAndResources($name . '-item', '//operations/' . $name . '/item', $this->defaultXmlFile);
						}
					}
					else
					{
						$this->formData[$name] = $this->bindPathToArray('//operations/' . $name, $this->defaultXmlFile);
						$this->setFieldsAndResources($name, '//operations/' . $name, $this->defaultXmlFile);
					}
				}
			}
		}

		return $this->formData;
	}

	/**
	 * Return mapped array for the form data
	 *
	 * @param   string            $path  Path to the XML element
	 * @param   SimpleXMLElement  $xml   XML file
	 *
	 * @return  array
	 *
	 * @since   1.4
	 */
	public function bindPathToArray($path, $xml)
	{
		if ($element = $xml->xpath($path))
		{
			$element = $element[0];

			return $this->bindElementToArray($element);
		}

		return array();
	}

	/**
	 * Return mapped array for the form data
	 *
	 * @param   SimpleXMLElement  $element  XML element
	 *
	 * @return  array
	 *
	 * @since   1.4
	 */
	public function bindElementToArray($element)
	{
		$data = array();

		if (!empty($element))
		{
			foreach ($element->attributes() as $key => $val)
			{
				$data[$key] = (string) $val;
			}

			$data['description'] = !empty($element->description) ? (string) $element->description : '';

			if (!empty($element->fields->description))
			{
				$data['fields']['description'] = (string) $element->fields->description;
			}

			if (!empty($element->resources->description))
			{
				$data['resources']['description'] = (string) $element->resources->description;
			}
		}

		return $data;
	}

	/**
	 * Gets Fields and Resources from given path
	 *
	 * @param   string            $name  Operation or task name
	 * @param   string            $path  Path to the operation or the task
	 * @param   SimpleXMLElement  $xml   XML file
	 *
	 * @return  void
	 *
	 * @since   1.4
	 */
	public function setFieldsAndResources($name, $path, $xml)
	{
		// Get fields
		if ($fields = $xml->xpath($path . '/fields/field'))
		{
			foreach ($fields as $field)
			{
				$fieldArray = $this->bindElementToArray($field);
				$displayName = (string) $fieldArray['name'];
				$this->fields[$name][$displayName] = $fieldArray;
			}
		}

		// Get resources
		if ($resources = $xml->xpath($path . '/resources/resource'))
		{
			foreach ($resources as $resource)
			{
				$resourceArray = $this->bindElementToArray($resource);
				$displayName = (string) $resourceArray['displayName'];
				$resourceSpecific = !empty($resourceArray['resourceSpecific']) ? (string) $resourceArray['resourceSpecific'] : 'rcwsGlobal';

				$this->resources[$name][$resourceSpecific][$displayName] = $resourceArray;
			}
		}
	}
}
