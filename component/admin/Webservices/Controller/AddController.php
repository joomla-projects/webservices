<?php
/**
 * Webservices component for Joomla! CMS
 *
 * @copyright  Copyright (C) 2004 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Webservices\Controller;

use Webservices\Helper;
use Webservices\Model\WebserviceModel;

/**
 * Add Webservice Controller
 *
 * @package     Joomla!
 * @subpackage  Webservices
 * @since       1.0
 */
class AddController extends DisplayController
{
	/**
	 * The default view to display
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'webservice';

	/**
	 * Instantiate the controller
	 *
	 * @param   \JInput            $input  The input object.
	 * @param   \JApplicationBase  $app    The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(\JInput $input = null, \JApplicationBase $app = null)
	{
		parent::__construct($input, $app);

		// Set the context for the controller
		$this->context = \JApplicationHelper::getComponentName() . '.' . $this->getInput()->getCmd('view', $this->defaultView);
	}

	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		// Set up variables to build our classes
		$view   = $this->defaultView;
		$format = $this->getInput()->getCmd('format', 'html');

		// Register the layout paths for the view
		$paths = new \SplPriorityQueue;

		// Add the path for template overrides
		$paths->insert(JPATH_THEMES . '/' . $this->getApplication()->getTemplate() . '/html/' . \JApplicationHelper::getComponentName() . '/' . $view, 2);

		// Add the path for the default layouts
		$paths->insert(JPATH_BASE . '/components/' . \JApplicationHelper::getComponentName() . '/Webservices/View/' . ucfirst($view) . '/tmpl', 1);

		// Build the class names for the model and view
		$viewClass  = '\\Webservices\\View\\' . ucfirst($view) . '\\' . ucfirst($view) . ucfirst($format) . 'View';
		$modelClass = '\\Webservices\\Model\\' . ucfirst($view) . 'Model';

		// Sanity check - Ensure our classes exist
		if (!class_exists($viewClass))
		{
			// Try to use a default view
			$viewClass = '\\Webservices\\View\\Default' . ucfirst($format) . 'View';

			if (!class_exists($viewClass))
			{
				throw new \RuntimeException(
					sprintf('The view class for the %1$s view in the %2$s format was not found.', $view, $format), 500
				);
			}
		}

		if (!class_exists($modelClass))
		{
			throw new \RuntimeException(sprintf('The model class for the %s view was not found.', $view), 500);
		}

		// Initialize the model class now; need to do it before setting the state to get required data from it
		$model = new $modelClass($this->context, null, Helper::createDbo());

		// Initialize the state for the model
		//$model->setState($this->initializeState($model));

		// Initialize the view class now
		$view = new $viewClass($model, $paths);

		// Echo the rendered view for the application
		echo $view->render();

		// Finished!
		return true;
	}

	/**
	 * Method to get new Task HTML
	 *
	 * @return  void
	 */
	public function ajaxGetTask()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		$taskName = $input->getString('taskName', '');
		$model = $this->getModel();
		$model->formData['task-' . $taskName] = $model->bindPathToArray('//operations/taskResources', $model->defaultXmlFile);
		$model->setFieldsAndResources('task-' . $taskName, '//operations/taskResources', $model->defaultXmlFile);

		if (!empty($taskName))
		{
			echo JLayoutHelper::render(
				'webservice.operation',
				array(
					'view' => $model,
					'options' => array(
						'operation' => 'task-' . $taskName,
						'form'      => $model->getForm($model->formData, false),
						'tabActive' => ' active in ',
						'fieldList' => array('defaultValue', 'isRequiredField', 'isPrimaryField'),
					)
				)
			);
		}

		$app->close();
	}

	/**
	 * Method to get new Field HTML
	 *
	 * @return  void
	 */
	public function ajaxGetField()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		$operation = $input->getString('operation', 'read');
		$fieldList = $input->getString('fieldList', '');
		$fieldList = explode(',', $fieldList);

		echo JLayoutHelper::render(
			'webservice.fields.field',
			array(
				'view' => $this,
				'options' => array(
					'operation' => $operation,
					'fieldList' => $fieldList,
				)
			)
		);

		$app->close();
	}

	/**
	 * Method to get new Fields from Database Table in HTML
	 *
	 * @return  void
	 */
	public function ajaxGetFieldFromDatabase()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		$operation = $input->getString('operation', 'read');
		$fieldList = $input->getString('fieldList', '');
		$fieldList = explode(',', $fieldList);
		$tableName = $input->getCmd('tableName', '');

		if (!empty($tableName))
		{
			$db = JFactory::getDbo();
			$columns = $db->getTableColumns('#__' . $tableName, false);

			if ($columns)
			{
				foreach ($columns as $columnKey => $column)
				{
					$form = array(
						'name' => $column->Field,
						'transform' => WebservicesHelper::getTransformElementByDbType($column->Type),
						'defaultValue' => $column->Default,
						'isPrimaryField' => $column->Key == 'PRI' ? 'true' : 'false',
						'description' => $column->Comment,
					);

					echo JLayoutHelper::render(
						'webservice.fields.field',
						array(
							'view' => $this,
							'options' => array(
								'operation' => $operation,
								'fieldList' => $fieldList,
								'form' => $form,
							)
						)
					);
				}
			}
		}

		$app->close();
	}

	/**
	 * Method to get new Resources from Database Table in HTML
	 *
	 * @return  void
	 */
	public function ajaxGetResourceFromDatabase()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		$operation = $input->getString('operation', 'read');
		$fieldList = $input->getString('fieldList', '');
		$fieldList = explode(',', $fieldList);
		$tableName = $input->getCmd('tableName', '');

		if (!empty($tableName))
		{
			$db = JFactory::getDbo();
			$columns = $db->getTableColumns('#__' . $tableName, false);

			if ($columns)
			{
				foreach ($columns as $columnKey => $column)
				{
					$form = array(
						'displayName' => $column->Field,
						'transform' => WebservicesHelper::getTransformElementByDbType($column->Type),
						'resourceSpecific' => 'rcwsGlobal',
						'fieldFormat' => '{' . $column->Field . '}',
						'description' => $column->Comment,
					);

					echo JLayoutHelper::render(
						'webservice.resources.resource',
						array(
							'view' => $this,
							'options' => array(
								'operation' => $operation,
								'fieldList' => $fieldList,
								'form' => $form,
							)
						)
					);
				}
			}
		}

		$app->close();
	}

	/**
	 * Method to get new Field HTML
	 *
	 * @return  void
	 */
	public function ajaxGetConnectWebservice()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		$operation = $input->getString('operation', 'read');
		$fieldList = $input->getString('fieldList', '');
		$webserviceId = $input->getString('webserviceId', '');

		if (!empty($webserviceId))
		{
			$model = $this->getModel();
			$item = $model->getItem($webserviceId);

			$link = '/index.php?option=' . $item->name;
			$link .= '&amp;webserviceVersion=' . $item->version;
			$link .= '&amp;webserviceClient=' . $item->client;
			$link .= '&amp;id={' . $item->name . '_id}';

			$form = array(
				'displayName' => $item->name,
				'linkTitle' => $item->title,
				'transform' => 'string',
				'resourceSpecific' => 'rcwsGlobal',
				'displayGroup' => '_links',
				'linkTemplated' => 'true',
				'fieldFormat' => $link,
				'description' => JText::sprintf('COM_WEBSERVICES_WEBSERVICE_RESOURCE_ADD_CONNECTION_DESCRIPTION_LABEL', $item->name, '{' . $item->name . '_id}'),
			);

			echo JLayoutHelper::render(
				'webservice.resources.resource',
				array(
					'view' => $this,
					'options' => array(
						'operation' => $operation,
						'fieldList' => $fieldList,
						'form' => $form,
					)
				)
			);
		}

		$app->close();
	}

	/**
	 * Method to get new Field HTML
	 *
	 * @return  void
	 */
	public function ajaxGetResource()
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		$operation = $input->getString('operation', 'read');
		$fieldList = $input->getString('fieldList', '');

		echo JLayoutHelper::render(
			'webservice.resources.resource',
			array(
				'view' => $this,
				'options' => array(
					'operation' => $operation,
					'fieldList' => $fieldList,
				)
			)
		);

		$app->close();
	}
}
