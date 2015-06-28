<?php
/**
 * Interface for user objects.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Integrations;


interface AuthorisationInterface {
	public function authorise($action, $asset);
}