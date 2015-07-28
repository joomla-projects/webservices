<?php
/**
 * Helper for Joomla Webservices.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Webservices;

/**
 * Configuration for webservices
 *
 * @since  __DEPLOY_VERSION__
 */
class WebserviceHelper
{
	const webservicePath = 'www/media/webservices/webservices';

	/**
	 * Get Webservices path
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getWebservicesPath()
	{
		return JPATH_API . '/' . self::webservicePath;
	}

	/**
	 * Get Webservices path
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getWebservicesRelativePath()
	{
		return self::webservicePath;
	}
}