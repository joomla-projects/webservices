<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Redcore
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('JPATH_BASE') or die;

/**
 * System plugin for webservices
 *
 * @package     Joomla.Plugin
 * @subpackage  System
 * @since       1.0
 */
class PlgSystemWebservices extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 *
	 * @since   1.5
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		$webservicesLoader = JPATH_LIBRARIES . '/webservices/bootstrap.php';

		if (file_exists($webservicesLoader))
		{
			require_once $webservicesLoader;

			// Sets plugin parameters for further use
			JBootstrap::$config = $this->params;
			JBootstrap::bootstrap(false);
		}
	}

	/**
	 * Method to register custom library.
	 *
	 * @return  void
	 */
	public function onAfterInitialise()
	{
		if (defined('WEBSERVICES_LIBRARY_LOADED'))
		{
			$apiName = JFactory::getApplication()->input->getString('api');

			if ($this->isApiEnabled($apiName))
			{
				$input = JFactory::getApplication()->input;

				if (!empty($apiName))
				{
					try
					{
						JError::setErrorHandling(E_ERROR, 'message');
						JFactory::getApplication()->clearHeaders();
						$webserviceClient = $input->get->getString('webserviceClient', '');
						$optionName = $input->get->getString('option', '');
						$optionName = strpos($optionName, 'com_') === 0 ? substr($optionName, 4) : $optionName;
						$viewName = $input->getString('view', '');
						$version = $input->getString('webserviceVersion', '');

						// This is decapricated in favor of Pluging trigger
						//$token = $input->getString(JBootstrap::getConfig('oauth2_token_param_name', 'access_token'), '');
						$token = '';
						$apiName = ucfirst($apiName);
						$method = strtoupper($input->getMethod());
						$task = JApiHalHelper::getTask();
						$data = JApi::getPostedData();
						$dataGet = $input->get->getArray();

						if (empty($webserviceClient))
						{
							$webserviceClient = JFactory::getApplication()->isAdmin() ? 'administrator' : 'site';
						}

						$options = array(
							'api'               => $apiName,
							'optionName'        => $optionName,
							'viewName'          => $viewName,
							'webserviceVersion' => $version,
							'webserviceClient'  => $webserviceClient,
							'method'            => $method,
							'task'              => $task,
							'data'              => $data,
							'dataGet'           => $dataGet,
							'accessToken'       => $token,
							'format'            => $input->getString('format', $this->params->get('webservices_default_format', 'json')),
							'id'                => $input->getString('id', ''),
							'absoluteHrefs'     => $input->get->getBool('absoluteHrefs', true),
						);

						// Create instance of Api and fill all required options
						$api = JApi::getInstance($options);

						// Run the api task
						$api->execute();

						// Display output
						$api->render();
					}
					catch (Exception $e)
					{
						$code = $e->getCode() > 0 ? $e->getCode() : 500;

						// Set the server response code.
						header('Status: ' . $code, true, $code);

						if (strtolower($apiName) == 'soap')
						{
							echo JApiSoapHelper::createSoapFaultResponse($e->getMessage());
						}
						else
						{
							// Check for defined constants
							if (!defined('JSON_UNESCAPED_SLASHES'))
							{
								define('JSON_UNESCAPED_SLASHES', 64);
							}

							// An exception has been caught, echo the message and exit.
							echo json_encode(array('message' => $e->getMessage(), 'code' => $e->getCode(), 'type' => get_class($e)), JSON_UNESCAPED_SLASHES);
						}
					}

					JFactory::getApplication()->close();
				}
			}
		}
	}

	/**
	 * Checks if given api name is currently install and enabled on this server
	 *
	 * @param   string  $apiName  Api name
	 *
	 * @return bool
	 */
	private function isApiEnabled($apiName)
	{
		$apiName = strtolower($apiName);

		return ($this->params->get('enable_webservices', 0) == 1 && $apiName == 'hal')
		|| ($this->params->get('enable_soap', 0) == 1 && $apiName == 'soap');
	}
}
