<?php
/**
 * Interface for user objects.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Integrations;

/**
 * Interface for user objects.
 *
 * @since  __DEPLOY_VERSION__
 */
interface AuthorisationInterface
{
	/**
	 * Authorise the user in the class to perform the action
	 *
	 * @param   string  $action  The action to check the user has permission to do
	 * @param   mixed   $asset   The asset the action is to performed on (either a string or an integer)
	 *
	 * @return mixed
	 */
	public function authorise($action, $asset);
}
