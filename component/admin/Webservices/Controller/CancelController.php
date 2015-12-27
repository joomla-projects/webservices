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
 * Cancel Webservice Controller
 *
 * @package     Joomla!
 * @subpackage  Webservices
 * @since       1.0
 */
class CancelController extends DisplayController
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
		$this->getApplication()->enqueueMessage($msg, $type);
		$this->getApplication()->redirect(\JRoute::_('index.php?option=com_webservices', false));
	}
}
