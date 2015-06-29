<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\Webservices\Webservices\Webservice;

defined('JPATH_API') or die;

/**
 * Api Helper class for overriding default methods
 *
 * @package     Redcore
 * @subpackage  Api Helper
 * @since       1.2
 */
class JWebserviceHelperAdministratorContact
{
	/**
	 * Checks if operation is allowed from the configuration file
	 *
	 * @return  $this
	 *
	 * @throws  RuntimeException
	 */
	/* public function isOperationAllowed(Webservice $webservice){} */

	/**
	 * Execute the Api Default Page operation.
	 *
	 * @return  mixed  Webservice object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiDefaultPage(Webservice $webservice){} */

	/**
	 * Execute the Api Create operation.
	 *
	 * @return  mixed  Webservice object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiCreate(Webservice $webservice){} */

	/**
	 * Execute the Api Read operation.
	 *
	 * @return  mixed  Webservice object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiRead(Webservice $webservice){} */

	/**
	 * Execute the Api Delete operation.
	 *
	 * @return  mixed  Webservice object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiDelete(Webservice $webservice){} */

	/**
	 * Execute the Api Update operation.
	 *
	 * @return  mixed  Webservice object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiUpdate(Webservice $webservice){} */

	/**
	 * Execute the Api Task operation.
	 *
	 * @return  mixed  Webservice object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiTask(Webservice $webservice){} */

	/**
	 * Execute the Api Documentation operation.
	 *
	 * @return  mixed  Webservice object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiDocumentation(Webservice $webservice){} */

	/**
	 * Process posted data from json or object to array
	 *
	 * @param   mixed             $data           Raw Posted data
	 * @param   SimpleXMLElement  $configuration  Configuration for displaying object
	 *
	 * @return  mixed  Array with posted data.
	 *
	 * @since   1.2
	 */
	/* public function processPostData($data, $configuration, Webservice $webservice){} */

	/**
	 * Set document content for List view
	 *
	 * @param   array             $items          List of items
	 * @param   SimpleXMLElement  $configuration  Configuration for displaying object
	 *
	 * @return void
	 */
	/* public function setForRenderList($items, $configuration, Webservice $webservice){} */

	/**
	 * Set document content for Item view
	 *
	 * @param   object            $item           List of items
	 * @param   SimpleXMLElement  $configuration  Configuration for displaying object
	 *
	 * @return void
	 */
	/* public function setForRenderItem($item, $configuration, Webservice $webservice){} */

	/**
	 * Prepares body for response
	 *
	 * @param   string  $message  The return message
	 *
	 * @return  string	The message prepared
	 *
	 * @since   1.2
	 */
	/* public function prepareBody($message, Webservice $webservice){} */

	/**
	 * Load model class for data manipulation
	 *
	 * @param   string            $elementName    Element name
	 * @param   SimpleXMLElement  $configuration  Configuration for current action
	 *
	 * @return  mixed  Model class for data manipulation
	 *
	 * @since   1.2
	 */
	/* public function loadModel($elementName, $configuration, Webservice $webservice){} */

	/**
	 * Set Method for Api to be performed
	 *
	 * @return  $this
	 *
	 * @since   1.2
	 */
	/* public function setApiOperation(Webservice $webservice){} */

	/**
	 * Include library classes
	 *
	 * @param   string  $element  Option name
	 *
	 * @return  void
	 *
	 * @since   1.4
	 */
	/*public function loadExtensionLibrary($element, Webservice $webservice){} */

	/**
	 * Validates posted data
	 *
	 * @param   object            $model          Model
	 * @param   array             $data           Raw Posted data
	 * @param   SimpleXMLElement  $configuration  Configuration for displaying object
	 *
	 * @return  mixed  Array with posted data or false.
	 *
	 * @since   1.3
	 */
	/*public function validatePostData($model, $data, $configuration, Webservice $webservice){} */

	/**
	 * Gets errors from model and places it into Application message queue
	 *
	 * @param   object  $model  Model
	 *
	 * @return void
	 */
	/*public function displayErrors($model, Webservice $webservice)*/
}
