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
 * Apply Webservice Controller
 *
 * @package     Joomla!
 * @subpackage  Webservices
 * @since       1.0
 */
class ApplyController extends DisplayController
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
		try
		{
			$model = new WebserviceModel;

			// Initialize the state for the model
			$model->setState($this->initializeState($model));

			$id = $this->getInput()->getUint('id');

			$data = $this->getInput()->getArray()['jform'];

			if ($model->save($data))
			{
				$msg = \JText::_('COM_WEBSERVICES_APPLY_OK');
			}
			else
			{
				$msg = \JText::_('COM_WEBSERVICES_APPLY_ERROR');
			}

			$type = 'message';
			$url = 'index.php?option=com_webservices&task=edit&id=' . $id;
		}
		catch (\Exception $e)
		{
			$msg  = $e->getMessage();
			$type = 'error';
		}

		$url = isset($this->returnurl) ? $this->returnurl : $url;

		$this->getApplication()->enqueueMessage($msg, $type);
		$this->getApplication()->redirect(\JRoute::_($url, false));
	}
}
