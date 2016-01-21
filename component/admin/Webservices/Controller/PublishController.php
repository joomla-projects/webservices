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
 * Publish Webservice Controller
 *
 * @package     Joomla!
 * @subpackage  Webservices
 * @since       1.0
 */
class PublishController extends DisplayController
{
	/**
	 * Execute the controller.
	 *
	 * @return  void  Redirects the application
	 *
	 * @since   2.0
	 */
	public function execute()
	{
		// Check for request forgeries
		\JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

		// Get items to publish from the request.
		$input = \JFactory::getApplication()->input;

		// Get the values
		$cid = $input->get('cid', array(), 'array');
		$data = array('publish' => 1, 'unpublish' => 0, 'archive' => 2, 'trash' => -2, 'report' => -3);
		$task = $input->get('task');
		$value = \JArrayHelper::getValue($data, $task, 0, 'int');

		if (empty($cid))
		{
			\JLog::add(\JText::_('COM_WEBSERVICES_NO_ITEM_SELECTED'), \JLog::WARNING, 'jerror');
		}
		else
		{
			// Get and initialize the state for the model
			$model = new WebserviceModel;
			$model->setState($this->initializeState($model));

			$table = $model->getTable();

			// Make sure the item ids are integers
			\JArrayHelper::toInteger($cid);

			// Publish the items.
			try
			{
				$table->publish($cid, $value, \JFactory::getUser()->id);

				if ($value == 1)
				{
					$ntext = \JText::plural('COM_WEBSERVICES_N_ITEMS_PUBLISHED', count($cid));
				}
				elseif ($value == 0)
				{
					$ntext = \JText::plural('COM_WEBSERVICES_N_ITEMS_UNPUBLISHED', count($cid));
				}
				elseif ($value == 2)
				{
					$ntext = \JText::plural('COM_WEBSERVICES_N_ITEMS_ARCHIVED', count($cid));
				}
				else
				{
					$ntext = \JText::plural('COM_WEBSERVICES_N_ITEMS_TRASHED', count($cid));
				}

				$type = 'message';
			}
			catch (\Exception $e)
			{
				$type = 'error';
			}
		}

		$this->getApplication()->enqueueMessage($ntext, $type);

		$this->returnurl = 'index.php?option=com_webservices&view=webservices';
		parent::execute();
	}
}
