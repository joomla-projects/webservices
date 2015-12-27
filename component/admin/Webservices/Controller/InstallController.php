<?php
/**
 * Webservices component for Joomla! CMS
 *
 * @copyright  Copyright (C) 2004 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Webservices\Controller;

use Webservices\Helper;
use Webservices\Model\WebservicesModel;

/**
 * Install Webservice Controller
 *
 * @package     Joomla!
 * @subpackage  Webservices
 * @since       1.0
 */
class InstallController extends DisplayController
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
    // Get model
    $model = new WebservicesModel;

    // Return url
    $this->returnurl = 'index.php?option=com_webservices';

    // Initialize the state for the model
    $model->setState($this->initializeState($model));

    $input = $this->getInput();

    $webservice = $input->getString('webservice');
    $version = $input->getString('version');
    $folder = $input->getString('folder');
    $client = $input->getString('client');

		try
		{
      if ($webservice == 'all')
  		{
        // @@ TODO: Fix this
  			//$this->batchWebservices('install');
  		}
  		else
  		{
        if ($model->installWebservice($client, $webservice, $version, $folder))
  			{
  				$msg = \JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_INSTALLED');
  			}
  		}

			$type = 'message';
		}
		catch (\Exception $e)
		{
			$msg  = $e->getMessage();
			$type = 'error';
		}

		$this->getApplication()->enqueueMessage($msg, $type);
		$this->getApplication()->redirect(\JRoute::_($this->returnurl, false));
	}
}
