<?php
/**
 * @package     Redcore.Backend
 * @subpackage  Controllers
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('_JEXEC') or die;

/**
 * Webservices Controller
 *
 * @package     Redcore.Backend
 * @subpackage  Controllers
 * @since       1.0
 */
class WebservicesControllerWebservices extends JControllerAdmin
{
	/**
	 * Method to install Webservice.
	 *
	 * @return  boolean  True if successful, false otherwise.
	 */
	public function installWebservice()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$app = JFactory::getApplication();

		/** @var WebservicesModelWebservices $model */
		$model = $this->getModel('webservices');

		$webservice = $app->input->getString('webservice');
		$version = $app->input->getString('version');
		$folder = $app->input->getString('folder');
		$client = $app->input->getString('client');

		if ($webservice == 'all')
		{
			$this->batchWebservices('install');
		}
		else
		{
			if ($model->installWebservice($client, $webservice, $version, $folder))
			{
				JFactory::getApplication()->enqueueMessage(JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_INSTALLED'), 'message');
			}
		}

		$this->redirectAfterAction();
	}

	/**
	 * Method to delete Content Element file.
	 *
	 * @return  boolean  True if successful, false otherwise.
	 */
	public function deleteWebservice()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$app   = JFactory::getApplication();

		/** @var WebservicesModelWebservices $model */
		$model = $this->getModel('webservices');

		$webservice = $app->input->getString('webservice');
		$version = $app->input->getString('version');
		$folder = $app->input->getString('folder');
		$client = $app->input->getString('client');

		if ($webservice == 'all')
		{
			$this->batchWebservices('delete');
		}
		else
		{
			$model->deleteWebservice($client, $webservice, $version, $folder);
		}

		$this->redirectAfterAction();
	}

	/**
	 * Method to upload Content Element file.
	 *
	 * @return  boolean  True if successful, false otherwise.
	 */
	public function uploadWebservice()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$app   = JFactory::getApplication();
		$files  = $app->input->files->get('webservicesWebservice', array(), 'array');

		if (!empty($files))
		{
			$uploadOptions = array(
				'allowedFileExtensions' => 'xml',
				'allowedMIMETypes'      => 'application/xml, text/xml',
				'overrideExistingFile'  => true,
			);

			foreach ($files as $key => &$file)
			{
				$objectFile = new JObject($file);

				try
				{
					$content = file_get_contents($objectFile->tmp_name);
					$fileContent = null;

					if (is_string($content))
					{
						$fileContent = new \SimpleXMLElement($content);
					}

					$name = (string) $fileContent->config->name;
					$version = !empty($fileContent->config->version) ? (string) $fileContent->config->version : '1.0.0';

					$client = Joomla\Webservices\Webservices\ConfigurationHelper::getWebserviceClient($fileContent);

					$file['name'] = $client . '.' . $name . '.' . $version . '.xml';
				}
				catch (\Exception $e)
				{
					unset($files[$key]);
					JFactory::getApplication()->enqueueMessage(JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_FILE_NOT_VALID'), 'message');
				}
			}

			$uploadedFiles = WebservicesHelper::uploadFiles($files, Joomla\Webservices\Webservices\WebserviceHelper::getWebservicesPath() . '/upload', $uploadOptions);

			if (!empty($uploadedFiles))
			{
				$app->enqueueMessage(JText::_('COM_WEBSERVICES_WEBSERVICES_UPLOAD_SUCCESS'));
			}
		}
		else
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_WEBSERVICES_WEBSERVICES_UPLOAD_FILE_NOT_FOUND'), 'warning');
		}

		$this->redirectAfterAction();
	}

	/**
	 * Preforms Batch action against all Webservices
	 *
	 * @param   string  $action  Action to preform
	 *
	 * @return  boolean  Returns true if Action was successful
	 */
	public function batchWebservices($action)
	{
		$webservices = Joomla\Webservices\Webservices\ConfigurationHelper::getWebservices();

		if (!empty($webservices))
		{
			/** @var WebservicesModelWebservices $model */
			$model = $this->getModel('webservices');

			/** @var Joomla\Database\DatabaseDriver $db */
			$db = $model->getDbo();
			$installedWebservices = Joomla\Webservices\Webservices\ConfigurationHelper::getInstalledWebservices($db);

			foreach ($webservices as $webserviceNames)
			{
				foreach ($webserviceNames as $webserviceVersions)
				{
					foreach ($webserviceVersions as $webservice)
					{
						$client = Joomla\Webservices\Webservices\ConfigurationHelper::getWebserviceClient($webservice);
						$path = $webservice->webservicePath;
						$name = (string) $webservice->config->name;
						$version = (string) $webservice->config->version;

						// If it is already install then we skip it
						if (!empty($installedWebservices[$client][$name][$version]))
						{
							continue;
						}

						switch ($action)
						{
							case 'install':
								$model->installWebservice($client, $name, $version, $path);
								break;
							case 'delete':
								$model->deleteWebservice($client, $name, $version, $path);
								break;
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Method to redirect after action.
	 *
	 * @return  boolean  True if successful, false otherwise.
	 */
	public function redirectAfterAction()
	{
		if ($returnUrl = $this->input->get('return'))
		{
			$this->setRedirect(JRoute::_(base64_decode($returnUrl), false));
		}
		else
		{
			parent::display();
		}
	}
}
