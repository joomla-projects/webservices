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
 * Interface to handle api calls
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.2
 */
interface JApiInterface
{
	/**
	 * Method to execute task.
	 *
	 * @return  JApi  The Api output result.
	 *
	 * @since   1.2
	 */
	public function execute();

	/**
	 * Method to render the output.
	 *
	 * @return  string  The Api output result.
	 *
	 * @since   1.2
	 */
	public function render();
}
