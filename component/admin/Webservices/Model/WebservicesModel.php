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

/**
 * Model class for the webservices list view
 *
 * @since  1.0
 */
class WebservicesModel extends \JModelDatabase
{
	/**
	 * The object context
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $context;

	/**
	 * Name of the filter form to load
	 *
	 * @var  string
	 */
	protected $filterFormName = 'filter_webservices';

	/**
	 * Limitstart field used by the pagination
	 *
	 * @var  string
	 */
	protected $limitField = 'webservices_limit';

	/**
	 * xml Files from webservice folder
	 *
	 * @var  array
	 */
	public $xmlFiles = array();

	/**
	 * Installed xml Files from webservice folder
	 *
	 * @var  array
	 */
	public $installedXmlFiles = array();

	/**
	 * Number of available xml files for install
	 *
	 * @var  int
	 */
	public $xmlFilesAvailable = 0;

	/**
	 * Array of fields the list can be sorted on
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $sortFields = array();

	/**
	 * Instantiate the model.
	 *
	 * @param   string            $context  The model context.
	 * @param   Registry          $state    The model state.
	 * @param   \JDatabaseDriver  $db       The database adpater.
	 *
	 * @since   1.0
	 */
	public function __construct($context, Registry $state = null, \JDatabaseDriver $db = null)
	{
		parent::__construct($state, $db);

		try
		{
			$container = (new \Joomla\DI\Container)
				->registerServiceProvider(new \Joomla\Webservices\Service\ConfigurationProvider)
				->registerServiceProvider(new \Joomla\Webservices\Service\DatabaseProvider);
		}
		catch (\Exception $e)
		{
			throw new RuntimeException(JText::sprintf('COM_WEBSERVICES_WEBSERVICE_ERROR_DATABASE_CONNECTION', $e->getMessage()), 500, $e);
		}

		$this->context    = $context;
		$this->sortFields = array('w.id');
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 */
	protected function setXmlFiles()
	{
		$xmlFiles = \Joomla\Webservices\Webservices\ConfigurationHelper::getWebservices();

		if (!empty($xmlFiles))
		{
			$db	= $this->getDb();

			$query = $db->getQuery(true)
				->select('CONCAT(' . $db->qn('client') . ', ' . $db->qn('name') . ', ' . $db->qn('version') . ')')
				->from($db->qn('#__webservices', 'w'));

			$db->setQuery($query);
			$webservices = $db->loadColumn();

			foreach ($xmlFiles as $client => $webserviceNames)
			{
				foreach ($webserviceNames as $name => $webserviceVersions)
				{
					foreach ($webserviceVersions as $version => $xmlWebservice)
					{
						$this->xmlFilesAvailable++;

						if (!empty($webservices))
						{
							foreach ($webservices as $webservice)
							{
								if ($webservice == $client . $name . $version)
								{
									// We store it so we can use it in webservice list so we do not load files twice
									$this->installedXmlFiles[$client][$name][$version] = $xmlWebservice;

									// We remove it from the list
									unset($xmlFiles[$client][$name][$version]);
									$this->xmlFilesAvailable--;
									break;
								}
							}
						}
					}
				}
			}

			$this->xmlFiles = $xmlFiles;
		}
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$db	= $this->getDb();

		$query = $db->getQuery(true)
			->select('w.*')
			->from($db->qn('#__webservices', 'w'));

		// Filter by client.
		if ($client = $this->getState()->get('filter.client'))
		{
			$query->where('w.client = ' . $db->quote($db->escape($client, true)));
		}

		// Filter by path.
		if ($path = $this->getState()->get('filter.path'))
		{
			$query->where('w.path = ' . $db->quote($db->escape($path, true)));
		}

		// Filter by state.
		$state = $this->getState()->get('filter.state');

		if (is_numeric($state))
		{
			$query->where('w.state = ' . $db->quote($db->escape((int) $state, true)));
		}

		// Filter search
		$search = $this->getState()->get('filter.search_webservices');

		if (!empty($search))
		{
			$search = $db->quote('%' . $db->escape($search, true) . '%');
			$query->where('(w.name LIKE ' . $search . ') OR (w.title LIKE ' . $search . ')');
		}

		// Ordering
		$orderList = $this->getState()->get('list.ordering');
		$directionList = $this->getState()->get('list.direction');

		$order = !empty($orderList) ? $orderList : 'w.title';
		$direction = !empty($directionList) ? $directionList : 'ASC';
		$query->order($db->escape($order) . ' ' . $db->escape($direction));

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.2
	 */
	public function getItems()
	{
		// We are loading all webservice XML files with this
		$this->setXmlFiles();

		// Load the list items.
		$query = $this->_getListQuery();

		try
		{
			$items = $this->_getList($query, $this->getStart(), $this->getState()->get('list.limit'));
		}
		catch (RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		if (!empty($items))
		{
			foreach ($items as $item)
			{
				$item->xml = !empty($this->installedXmlFiles[$item->client][$item->name][$item->version]) ?
					$this->installedXmlFiles[$item->client][$item->name][$item->version] : false;

				$item->scopes = json_decode($item->scopes, true);
			}
		}

		return $items;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.2
	 */
	public function getXmlFiles()
	{
		return $this->xmlFiles;
	}

	/**
	 * Install Webservice from site
	 *
	 * @param   string  $client      Client
	 * @param   string  $webservice  Webservice Name
	 * @param   string  $version     Webservice version
	 * @param   string  $path        Path to webservice files
	 * @param   int     $id          Path to webservice files
	 *
	 * @return  boolean  Returns id if Webservice was successfully installed
	 */
	public function installWebservice($client = '', $webservice = '', $version = '1.0.0', $path = '', $id = 0)
	{
		$webserviceXml = \Joomla\Webservices\Webservices\ConfigurationHelper::getWebservice($client, $webservice, $version);

		if (!empty($webserviceXml))
		{
			$operations = array();
			$scopes = array();
			$client = \Joomla\Webservices\Webservices\ConfigurationHelper::getWebserviceClient($webserviceXml);
			$version = !empty($webserviceXml->config->version) ? (string) $webserviceXml->config->version : $version;

			if (!empty($webserviceXml->operations))
			{
				foreach ($webserviceXml->operations as $operation)
				{
					foreach ($operation as $key => $method)
					{
						if ($key == 'task')
						{
							foreach ($method as $taskKey => $task)
							{
								$displayName = !empty($task['displayName']) ? (string) $task['displayName'] : $key . ' ' . $taskKey;
								$scopes[] = array(
									'scope' => strtolower($client . '.' . $webservice . '.' . $key . '.' . $taskKey),
									'scopeDisplayName' => ucfirst($displayName)
								);
							}
						}
						else
						{
							$operations[] = strtoupper(str_replace(array('read', 'create', 'update'), array('GET', 'PUT', 'POST'), $key));
							$displayName = !empty($method['displayName']) ? (string) $method['displayName'] : $key;
							$scopes[] = array(
								'scope' => strtolower($client . '.' . $webservice . '.' . $key),
								'scopeDisplayName' => ucfirst($displayName)
							);
						}
					}
				}
			}

			\Joomla\Webservices\Webservices\ConfigurationHelper::$installedWebservices[$client][$webservice][$version] = array(
				'name'          => $webservice,
				'version'       => $version,
				'title'         => (string) $webserviceXml->name,
				'path'          => (string) $path,
				'xmlFile'       => $client . '.' . $webservice . '.' . $version . '.xml',
				'xmlHashed'     => md5($webserviceXml),
				'operations'    => json_encode($operations),
				'scopes'        => json_encode($scopes),
				'client'        => $client,
				'state'         => 1,
				'id'            => $id,
			);

			/** @var WebservicesTableWebservice $table */
			$table = \JTable::getInstance('Webservice', 'WebservicesTable', array('dbo' => $this->getDb()));
			$table->bind(\Joomla\Webservices\Webservices\ConfigurationHelper::$installedWebservices[$client][$webservice][$version]);

			// Check the data.
			if (!$table->check())
			{
				return false;
			}

			if (!$table->store())
			{
				if (empty($id))
				{
					$this->setError(JText::sprintf('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_NOT_INSTALLED', $table->getError()));
				}

				return false;
			}

			$this->setState($this->getName() . '.id', $table->id);

			return $table->id;
		}

		return false;
	}

	/**
	 * Uninstalls Webservice access and deletes XML file
	 *
	 * @param   string  $client      Client
	 * @param   string  $webservice  Webservice name
	 * @param   string  $version     Webservice version
	 * @param   string  $path        Path to webservice files
	 *
	 * @return  boolean  Returns true if Content element was successfully purged
	 */
	public function deleteWebservice($client, $webservice = '', $version = '1.0.0', $path = '')
	{
		$xmlFilePath = \Joomla\Webservices\Webservices\ConfigurationHelper::getWebserviceConfig($client, strtolower($webservice), $version, $path);
		$helperFilePath = \Joomla\Webservices\Webservices\ConfigurationHelper::getWebserviceHelper($client, strtolower($webservice), $version, $path);

		try
		{
			JFile::delete($xmlFilePath);

			if (!empty($helperFilePath))
			{
				JFile::delete($helperFilePath);
			}
		}
		catch (Exception $e)
		{
			JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_DELETE_ERROR', $e->getMessage()), 'error');

			return false;
		}

		JFactory::getApplication()->enqueueMessage(JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_DELETED'), 'message');

		return true;
	}

	/**
	 * Method to get a JPagination object for the data set.
	 *
	 * @return  \JPagination  A JPagination object for the data set.
	 *
	 * @since   2.0
	 */
	public function getPagination()
	{
		// Get a storage key.
		$store = $this->getStoreId('getPagination');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Create the pagination object.
		$limit = (int) $this->getState()->get('list.limit') - (int) $this->getState()->get('list.links');
		$page = new \JPagination($this->getTotal(), $this->getStart(), $limit);

		// Add the object to the internal cache.
		$this->cache[$store] = $page;

		return $this->cache[$store];
	}

	/**
	 * Retrieves the array of authorized sort fields
	 *
	 * @return  array
	 *
	 * @since   2.0
	 */
	public function getSortFields()
	{
		return $this->sortFields;
	}

	/**
	 * Method to get a store id based on the model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  An identifier string to generate the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   2.0
	 */
	protected function getStoreId($id = '')
	{
		// Add the list state to the store id.
		$id .= ':' . $this->getState()->get('list.start');
		$id .= ':' . $this->getState()->get('list.limit');
		$id .= ':' . $this->getState()->get('list.ordering');
		$id .= ':' . $this->getState()->get('list.direction');

		return md5($this->context . ':' . $id);
	}

	/**
	 * Method to get the starting number of items for the data set.
	 *
	 * @return  integer  The starting number of items available in the data set.
	 *
	 * @since   2.0
	 */
	public function getStart()
	{
		$store = $this->getStoreId('getStart');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$start = $this->getState()->get('list.start');
		$limit = $this->getState()->get('list.limit');
		$total = $this->getTotal();

		if ($start > $total - $limit)
		{
			$start = max(0, (int) (ceil($total / $limit) - 1) * $limit);
		}

		// Add the total to the internal cache.
		$this->cache[$store] = $start;

		return $this->cache[$store];
	}

	/**
	 * Method to get the total number of items for the data set.
	 *
	 * @return  integer  The total number of items available in the data set.
	 *
	 * @since   2.0
	 */
	public function getTotal()
	{
		// Get a storage key.
		$store = $this->getStoreId('getTotal');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Load the total.
		$query = $this->_getListQuery();

		$total = (int) $this->_getListCount($query);

		// Add the total to the internal cache.
		$this->cache[$store] = $total;

		return $this->cache[$store];
	}

	/**
	 * Gets an array of objects from the results of database query.
	 *
	 * @param   \JDatabaseQuery|string  $query       The query.
	 * @param   integer                 $limitstart  Offset.
	 * @param   integer                 $limit       The number of records.
	 *
	 * @return  array  An array of results.
	 *
	 * @since   2.0
	 * @throws  RuntimeException
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$this->getDb()->setQuery($query, $limitstart, $limit);
		$result = $this->getDb()->loadObjectList();

		return $result;
	}

	/**
	 * Returns a record count for the query.
	 *
	 * @param   \JDatabaseQuery|string  $query  The query.
	 *
	 * @return  integer  Number of rows for query.
	 *
	 * @since   2.0
	 */
	protected function _getListCount($query)
	{
		// Use fast COUNT(*) on JDatabaseQuery objects if there no GROUP BY or HAVING clause:
		if ($query instanceof \JDatabaseQuery && $query->type == 'select' && $query->group === null && $query->having === null)
		{
			$query = clone $query;
			$query->clear('select')->clear('order')->select('COUNT(*)');

			$this->getDb()->setQuery($query);

			return (int) $this->getDb()->loadResult();
		}

		// Otherwise fall back to inefficient way of counting all results.
		$this->getDb()->setQuery($query)->execute();

		return (int) $this->getDb()->getNumRows();
	}

	/**
	 * Method to cache the last query constructed.
	 *
	 * This method ensures that the query is constructed only once for a given state of the model.
	 *
	 * @return  \JDatabaseQuery  A JDatabaseQuery object
	 *
	 * @since   2.0
	 */
	protected function _getListQuery()
	{
		// Capture the last store id used.
		static $lastStoreId;

		// Compute the current store id.
		$currentStoreId = $this->getStoreId();

		// If the last store id is different from the current, refresh the query.
		if ($lastStoreId != $currentStoreId || empty($this->query))
		{
			$lastStoreId = $currentStoreId;
			$this->query = $this->getListQuery();
		}

		return $this->query;
	}

	/**
	 * Get the filter form
	 *
	 * @param   array    $data      data
	 * @param   boolean  $loadData  load current data
	 *
	 * @return  JForm/false  the JForm object or false
	 *
	 * @since   3.2
	 */
	public function getFilterForm($data = array(), $loadData = true)
	{
		$form = null;

		// Try to locate the filter form automatically. Example: ContentModelArticles => "filter_articles"
		if (empty($this->filterFormName))
		{
			$classNameParts = explode('Model', get_called_class());

			if (count($classNameParts) == 2)
			{
				$this->filterFormName = 'filter_' . strtolower($classNameParts[1]);
			}
		}

		if (!empty($this->filterFormName))
		{
			// Get the form.
			$form = $this->loadForm($this->context . '.filter', $this->filterFormName, array('control' => '', 'load_data' => $loadData));
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
	 * @since   3.2
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
		//\JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');

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
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 *
	 * @since	3.2
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = \JFactory::getApplication()->getUserState($this->context, new \stdClass);

// $this->getState()->get('filter.client')

		// Pre-fill the list options
		if (!property_exists($data, 'list'))
		{
			$data->list = array(
				'direction' => $this->getState()->get('list.direction'),
				'limit'     => $this->getState()->get('list.limit'),
				'ordering'  => $this->getState()->get('list.ordering'),
				'start'     => $this->getState()->get('list.start')
			);
		}

		return $data;
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
	 * @since   3.2
	 * @throws  Exception if there is an error in the form event.
	 */
	protected function preprocessForm(\JForm $form, $data, $group = 'content')
	{
		// Import the appropriate plugin group.
		\JPluginHelper::importPlugin($group);

		// Get the dispatcher.
		$dispatcher = \JDispatcher::getInstance();

		// Trigger the form preparation event.
		$results = $dispatcher->trigger('onContentPrepareForm', array($form, $data));

		// Check for errors encountered while preparing the form.
		if (count($results) && in_array(false, $results, true))
		{
			// Get the last error.
			$error = $dispatcher->getError();

			if (!($error instanceof Exception))
			{
				throw new \Exception($error);
			}
		}
	}
}
