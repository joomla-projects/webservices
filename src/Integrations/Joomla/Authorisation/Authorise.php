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


class Authorise implements AuthorisationInterface
{
	/**
	 * The Joomla user object
	 *
	 * @var  \JUser
	 */
	private $user;

	/**
	 * @param   mixed  $id  The id of the User in the CMS to retrieve (either an id or a username)
	 */
	public function __construct($id)
	{
		$this->user = \JUser::getInstance($id);
	}

	public function authorise($action, $asset)
	{
		return $this->user->authorise($action, $asset);
	}
}
