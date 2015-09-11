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

jimport('joomla.filesystem.folder');

/**
 * Webservice Model
 *
 * @package     Joomla!
 * @subpackage  Webservices
 * @since       1.0
 */
class FormModel extends \JModelDatabase
{
	/**
	 * @var SimpleXMLElement
	 */
	public $xmlFile;

	/**
	 * @var SimpleXMLElement
	 */
	public $defaultXmlFile;

	/**
	 * @var string
	 */
	public $operationXml;

	/**
	 * @var array
	 */
	public $formData = array();

	/**
	 * @var array
	 */
	public $fields;

	/**
	 * @var array
	 */
	public $resources;

	/**
	 * The object context
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $context;

	/**
	 * The form name.
	 *
	 * @var  string
	 */
	protected $formName;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  Configuration array
	 *
	 * @throws  RuntimeException
	 */
	public function __construct($config = array())
	{
		// Guess the option from the class name (Option)Model(View).
		if (empty($this->option))
		{
			$r = null;

			if (!preg_match('/(.*)Model/i', get_class($this), $r))
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_MODEL_GET_NAME'), 500);
			}

			$this->option = 'com_' . strtolower($r[1]);

			$sub = explode("\\", $this->option);

			$this->option = $sub[0];
			$this->name = $sub[2];
		}

		$registry = new Registry;

		parent::__construct($registry, Helper::createDbo());

		if (is_null($this->context))
		{
			$this->context = strtolower($this->option . '.edit.' . $this->getName());
		}

		if (is_null($this->formName))
		{
			$this->formName = strtolower($this->getName());
		}

		$this->defaultXmlFile = new \SimpleXMLElement(file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . '/Webservices/Model/Forms/webservice_defaults.xml'));
	}

	/**
	 * Method to get the model name
	 *
	 * The model name. By default parsed using the classname or it can be set
	 * by passing a $config['name'] in the class constructor
	 *
	 * @return  string  The name of the model
	 *
	 * @since   12.2
	 * @throws  Exception
	 */
	public function getName()
	{
		if (empty($this->name))
		{
			$r = null;

			if (!preg_match('/Model(.*)/i', get_class($this), $r))
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_MODEL_GET_NAME'), 500);
			}

			$this->name = strtolower($r[1]);

			$this->name = str_replace("\\", "", $this->name);
		}

		return $this->name;
	}

	/**
	 * Method to load a operation form template.
	 *
	 * @return  string  Xml
	 */
	public function loadFormOperationXml()
	{
		if (is_null($this->operationXml))
		{
			$this->operationXml = @file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . '/Webservices/Model/Forms/webservice_operation.xml');
		}

		return $this->operationXml;
	}

	/**
	 * Method to get a form object.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm(
			$this->context . '.' . $this->formName, $this->formName,
			array(
				'control' => 'jform',
				'load_data' => $loadData
			)
		);

		if ($form)
		{
			// Load dynamic form for operations
			$form->load(str_replace('"operation"', '"create"', $this->loadFormOperationXml()));
			$form->load(str_replace('"operation"', '"read-list"', $this->loadFormOperationXml()));
			$form->load(str_replace('"operation"', '"read-item"', $this->loadFormOperationXml()));
			$form->load(str_replace('"operation"', '"update"', $this->loadFormOperationXml()));
			$form->load(str_replace('"operation"', '"delete"', $this->loadFormOperationXml()));

			if (!empty($data))
			{
				foreach ($data as $operationName => $operation)
				{
					if (substr($operationName, 0, strlen('task-')) === 'task-')
					{
						$form->load(str_replace('"operation"', '"' . $operationName . '"', $this->loadFormOperationXml()));
					}
				}
			}

			if (!empty($this->xmlFile) && $tasks = $this->xmlFile->xpath('//operations/task'))
			{
				$tasks = $tasks[0];

				foreach ($tasks as $taskName => $task)
				{
					$form->load(str_replace('"operation"', '"task-' . $taskName . '"', $this->loadFormOperationXml()));
				}
			}

			$form->bind($this->formData);
		}

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get a form object.
	 *
	 * @param   string   $name     The name of the form.
	 * @param   string   $source   The form source. Can be XML string if file flag is set to false.
	 * @param   array    $options  Optional array of options for the form creation.
	 * @param   boolean  $clear    Optional argument to force load a new form.
	 * @param   string   $xpath    An optional xpath to search for the fields.
	 *
	 * @return  mixed  JForm object on success, False on error.
	 *
	 * @see     JForm
	 * @since   12.2
	 */
	protected function loadForm($name, $source = null, $options = array(), $clear = false, $xpath = false)
	{
		// Handle the optional arguments.
		$options['control'] = \JArrayHelper::getValue($options, 'control', false);

		// Create a signature hash.
		$hash = md5($source . serialize($options));

		// Check if we can use a previously loaded form.
		if (isset($this->_forms[$hash]) && !$clear)
		{
			return $this->_forms[$hash];
		}

		// Get the form.
		\JForm::addFormPath(JPATH_COMPONENT . '/Webservices/Model/Forms');
		\JForm::addFieldPath(JPATH_COMPONENT . '/Webservices/Model/Fields');
		\JForm::addFormPath(JPATH_COMPONENT . '/Webservices/Model/Form');
		\JForm::addFieldPath(JPATH_COMPONENT . '/Webservices/Model/Field');

		try
		{
			$form = \JForm::getInstance($name, $source, $options, false, $xpath);

			if (isset($options['load_data']) && $options['load_data'])
			{
				// Get the data for the form.
				$data = $this->loadFormData();
			}
			else
			{
				$data = array();
			}

			// Allow for additional modification of the form, and events to be triggered.
			// We pass the data because plugins may require it.
			$this->preprocessForm($form, $data);

			// Load the data into the form after the plugins have operated.
			$form->bind($data);
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		// Store the form for later.
		$this->_forms[$hash] = $form;

		return $form;
	}

	/**
	 * Method to allow derived classes to preprocess the form.
	 *
	 * @param   JForm   $form   A JForm object.
	 * @param   mixed   $data   The data expected for the form.
	 * @param   string  $group  The name of the plugin group to import (defaults to "content").
	 *
	 * @return  void
	 *
	 * @see     JFormField
	 * @since   12.2
	 * @throws  Exception if there is an error in the form event.
	 */
	protected function preprocessForm(\JForm $form, $data, $group = 'content')
	{
		// Import the appropriate plugin group.
		\JPluginHelper::importPlugin($group);

		// Get the dispatcher.
		$dispatcher = \JEventDispatcher::getInstance();

		// Trigger the form preparation event.
		$results = $dispatcher->trigger('onContentPrepareForm', array($form, $data));

		// Check for errors encountered while preparing the form.
		if (count($results) && in_array(false, $results, true))
		{
			// Get the last error.
			$error = $dispatcher->getError();

			if (!($error instanceof \Exception))
			{
				throw new \Exception($error);
			}
		}
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = \JFactory::getApplication()->getUserState(
			$this->context . '.data',
			array()
		);

		if (empty($data))
		{
			$dataDb = $this->getItem();
			$data = $this->bindXMLToForm();

			$dataArray = \JArrayHelper::fromObject($dataDb);
			$dataEmpty = array('main' => array());
			$data = array_merge($dataEmpty, $data);

			$data['main'] = array_merge($dataArray, $data['main']);
		}

		return $data;
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 *
	 * @since   1.0
	 */
	public function getTable($type = 'WebserviceTable', $prefix = '', $config = array())
	{
		$ret = new \Webservices\Table\WebserviceTable(Helper::createDbo());

		return $ret;
	}

	/**
	 * Load item object
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since   1.2
	 */
	public function getItem($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) \JFactory::getApplication()->input->get('id');

		if (!$item = $this->_getItem($pk))
		{
			return $item;
		}

		if (!empty($item->id) && is_null($this->xmlFile))
		{
			try
			{
				$this->xmlFile = \Joomla\Webservices\Webservices\ConfigurationHelper::loadWebserviceConfiguration(
					$item->name, $item->version, $item->path, $item->client
				);
			}
			catch (Exception $e)
			{
				JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_WEBSERVICES_WEBSERVICE_ERROR_LOADING_XML', $e->getMessage()), 'error');
			}
		}

		// Add default webservice parameters since this is new webservice
		if (empty($this->xmlFile))
		{
			$this->xmlFile = $this->defaultXmlFile;
		}

		return $item;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since   12.2
	 */
	public function _getItem($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState()->get($this->getName() . '.id');
		$table = $this->getTable();

		if ($pk > 0)
		{
			// Attempt to load the row.
			$return = $table->load($pk);

			// Check for a table object error.
			if ($return === false && $table->getError())
			{
				$this->setError($table->getError());

				return false;
			}
		}

		// Convert to the JObject before adding other data.
		$properties = $table->getProperties(1);
		$item = \JArrayHelper::toObject($properties, 'JObject');

		if (property_exists($item, 'params'))
		{
			$registry = new Registry;
			$registry->loadString($item->params);
			$item->params = $registry->toArray();
		}

		return $item;
	}
}
