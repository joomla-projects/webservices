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
 * Ajaxgettask Webservice Controller
 *
 * @package     Joomla!
 * @subpackage  Webservices
 * @since       1.0
 */
class AjaxController extends DisplayController
{
	/**
	 * Execute the controller.
	 *
	 * @return  void  Redirects the application
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->app = $this->getApplication();
		$this->input = $this->app->input;

		$type = $this->input->get('type');

		$this->$type();

		$this->app->close();
	}

	/**
	 * Method to get the model
	 *
	 * @return  void
	 */
	private function getModel()
	{
		return new WebserviceModel;
	}

	/**
	 * Method to get new Task HTML
	 *
	 * @return  void
	 */
	public function getTask()
	{
		$taskName = $this->input->getString('taskName', '');
		$model = $this->getModel();
		$model->formData['task-' . $taskName] = $model->bindPathToArray('//operations/taskResources', $model->defaultXmlFile);
		$model->setFieldsAndResources('task-' . $taskName, '//operations/taskResources', $model->defaultXmlFile);

		if (!empty($taskName))
		{
			echo \JLayoutHelper::render(
				'operation',
				array(
					'view' => $model,
					'options' => array(
						'operation' => 'task-' . $taskName,
						'form'      => $model->getForm($model->formData, false),
						'tabActive' => ' active in ',
						'fieldList' => array('defaultValue', 'isRequiredField', 'isPrimaryField'),
					)
				),
				JPATH_COMPONENT_ADMINISTRATOR.'/Webservices/Layout'
			);
		}
	}

	/**
	 * Method to get new Field HTML
	 *
	 * @return  void
	 */
	public function getField()
	{
		$operation = $this->input->getString('operation', 'read');
		$fieldList = $this->input->getString('fieldList', '');
		$fieldList = explode(',', $fieldList);

		echo \LayoutHelper::render(
			'webservice.fields.field',
			array(
				'view' => $this,
				'options' => array(
					'operation' => $operation,
					'fieldList' => $fieldList,
				)
			),
			JPATH_COMPONENT_ADMINISTRATOR.'/Webservices/Layout'
		);
	}

	/**
	 * Method to get new Fields from Database Table in HTML
	 *
	 * @return  void
	 */
	public function ajaxGetFieldFromDatabase()
	{
		$operation = $this->input->getString('operation', 'read');
		$fieldList = $this->input->getString('fieldList', '');
		$fieldList = explode(',', $fieldList);
		$tableName = $this->input->getCmd('tableName', '');

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

					echo \JLayoutHelper::render(
						'webservice.fields.field',
						array(
							'view' => $this,
							'options' => array(
								'operation' => $operation,
								'fieldList' => $fieldList,
								'form' => $form,
							)
						),
						JPATH_COMPONENT_ADMINISTRATOR.'/Webservices/Layout'
					);
				}
			}
		}
	}

	/**
	 * Method to get new Resources from Database Table in HTML
	 *
	 * @return  void
	 */
	public function ajaxGetResourceFromDatabase()
	{
		$operation = $this->input->getString('operation', 'read');
		$fieldList = $this->input->getString('fieldList', '');
		$fieldList = explode(',', $fieldList);
		$tableName = $this->input->getCmd('tableName', '');

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

					echo \JLayoutHelper::render(
						'webservice.resources.resource',
						array(
							'view' => $this,
							'options' => array(
								'operation' => $operation,
								'fieldList' => $fieldList,
								'form' => $form,
							)
						),
						JPATH_COMPONENT_ADMINISTRATOR.'/Webservices/Layout'
					);
				}
			}
		}
	}

	/**
	 * Method to get new Field HTML
	 *
	 * @return  void
	 */
	public function ajaxGetConnectWebservice()
	{
		$operation = $this->input->getString('operation', 'read');
		$fieldList = $this->input->getString('fieldList', '');
		$webserviceId = $this->input->getString('webserviceId', '');

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
				'resourceSpecific' => 'global',
				'displayGroup' => '_links',
				'linkTemplated' => 'true',
				'fieldFormat' => $link,
				'description' => JText::sprintf('COM_WEBSERVICES_WEBSERVICE_RESOURCE_ADD_CONNECTION_DESCRIPTION_LABEL', $item->name, '{' . $item->name . '_id}'),
			);

			echo \JLayoutHelper::render(
				'webservice.resources.resource',
				array(
					'view' => $this,
					'options' => array(
						'operation' => $operation,
						'fieldList' => $fieldList,
						'form' => $form,
					)
				),
				JPATH_COMPONENT_ADMINISTRATOR.'/Webservices/Layout'
			);
		}
	}

	/**
	 * Method to get new Field HTML
	 *
	 * @return  void
	 */
	public function ajaxGetResource()
	{
		$operation = $this->input->getString('operation', 'read');
		$fieldList = $this->input->getString('fieldList', '');

		echo \JLayoutHelper::render(
			'webservice.resources.resource',
			array(
				'view' => $this,
				'options' => array(
					'operation' => $operation,
					'fieldList' => $fieldList,
				)
			),
			JPATH_COMPONENT_ADMINISTRATOR.'/Webservices/Layout'
		);
	}
}
