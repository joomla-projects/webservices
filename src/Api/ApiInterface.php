<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Api;

use Joomla\Input\Input;

/**
 * Interface to handle api calls
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.2
 */
interface ApiInterface
{
	/**
	 * Method to execute task.
	 * 
	 * @param   Input  $input  An input object.
	 * 
	 * @return  $this
	 *
	 * @since   1.2
	 */
	public function execute(Input $input);

	/**
	 * Method to render the output.
	 *
	 * @return  string  The Api output result.
	 *
	 * @since   1.2
	 */
	public function render();
}
