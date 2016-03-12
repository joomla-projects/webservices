<?php
/**
 * Interface for integrations with 3rd party assets.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Integrations;

use Joomla\Authentication\AuthenticationStrategyInterface;

/**
 * Interface for integrations with third-party assets.
 *
 * @since  __DEPLOY_VERSION__
 */
interface IntegrationInterface
{
	/**
	 * Gets an authorisation object for a given user id
	 *
	 * @param   integer  $id  The user id
	 *
	 * @return  AuthorisationInterface
	 */
	public function getAuthorisation($id);

	/**
	 * Load model class for data manipulation
	 *
	 * @param   string             $elementName    Element name
	 * @param   \SimpleXMLElement  $configuration  Configuration for current action
	 *
	 * @return  mixed  Model class for data manipulation
	 */
	public function loadModel($elementName, $configuration);

	/**
	 * Load Authentication Strategies. Returned array should have a key of the strategy name.
	 *
	 * @return  AuthenticationStrategyInterface[]
	 */
	public function getStrategies();
}
