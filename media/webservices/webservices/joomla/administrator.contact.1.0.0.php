<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

/**
 * Api Helper class for overriding default methods
 *
 * @package     Redcore
 * @subpackage  Api Helper
 * @since       1.2
 */
class JApiHalHelperAdministratorContact
{
	/**
	 * Checks if operation is allowed from the configuration file
	 *
	 * @return object This method may be chained.
	 *
	 * @throws  RuntimeException
	 */
	/* public function isOperationAllowed(JApiHalHal $apiHal){} */

	/**
	 * Execute the Api Default Page operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiDefaultPage(JApiHalHal $apiHal){} */

	/**
	 * Execute the Api Create operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiCreate(JApiHalHal $apiHal){} */

	/**
	 * Execute the Api Read operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiRead(JApiHalHal $apiHal){} */

	/**
	 * Execute the Api Delete operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiDelete(JApiHalHal $apiHal){} */

	/**
	 * Execute the Api Update operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiUpdate(JApiHalHal $apiHal){} */

	/**
	 * Execute the Api Task operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiTask(JApiHalHal $apiHal){} */

	/**
	 * Execute the Api Documentation operation.
	 *
	 * @return  mixed  JApi object with information on success, boolean false on failure.
	 *
	 * @since   1.2
	 */
	/* public function apiDocumentation(JApiHalHal $apiHal){} */

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
	/* public function processPostData($data, $configuration, JApiHalHal $apiHal){} */

	/**
	 * Set document content for List view
	 *
	 * @param   array             $items          List of items
	 * @param   SimpleXMLElement  $configuration  Configuration for displaying object
	 *
	 * @return void
	 */
	/* public function setForRenderList($items, $configuration, JApiHalHal $apiHal){} */

	/**
	 * Set document content for Item view
	 *
	 * @param   object            $item           List of items
	 * @param   SimpleXMLElement  $configuration  Configuration for displaying object
	 *
	 * @return void
	 */
	/* public function setForRenderItem($item, $configuration, JApiHalHal $apiHal){} */

	/**
	 * Prepares body for response
	 *
	 * @param   string  $message  The return message
	 *
	 * @return  string	The message prepared
	 *
	 * @since   1.2
	 */
	/* public function prepareBody($message, JApiHalHal $apiHal){} */

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
	/* public function loadModel($elementName, $configuration, JApiHalHal $apiHal){} */

	/**
	 * Set Method for Api to be performed
	 *
	 * @return  JApi
	 *
	 * @since   1.2
	 */
	/* public function setApiOperation(JApiHalHal $apiHal){} */

	/**
	 * Include library classes
	 *
	 * @param   string  $element  Option name
	 *
	 * @return  void
	 *
	 * @since   1.4
	 */
	/*public function loadExtensionLibrary($element, JApiHalHal $apiHal){} */

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
	/*public function validatePostData($model, $data, $configuration, JApiHalHal $apiHal){} */

	/**
	 * Gets errors from model and places it into Application message queue
	 *
	 * @param   object  $model  Model
	 *
	 * @return void
	 */
	/*public function displayErrors($model, JApiHalHal $apiHal)*/
}
