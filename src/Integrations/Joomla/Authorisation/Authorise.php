<?php
/**
 * User access check for Joomla webservices application.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Integrations\Joomla\Authorisation;

use Joomla\Webservices\Integrations\AuthorisationInterface;

/**
 * Joomla 3.x Authorisation class
 *
 * @package    Webservices
 */
class Authorise implements AuthorisationInterface
{
	/**
	 * The Joomla user object
	 *
	 * @var  \JUser
	 */
	private $user;

	/**
	 * Public class constructor
	 *
	 * @param   mixed  $id  The id of the User in the CMS to retrieve (either an id or a username)
	 */
	public function __construct($id)
	{
		$this->user = \JUser::getInstance($id);
	}

	/**
	 * Authorise the user in the class to perform the action
	 *
	 * @param   string  $action  The action to check the user has permission to do
	 * @param   mixed   $asset   The asset the action is to performed on (either a string or an integer)
	 *
	 * @return mixed
	 */
	public function authorise($action, $asset)
	{
		return $this->user->authorise($action, $asset);
	}
}
