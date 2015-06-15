<?php
/**
 * Application for Joomla Webservices. Based on https://github.com/mbabker/joomla-next/blob/master/libraries/core/Uri/Uri.php
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Uri;

use Joomla\Uri\Uri as BaseUri;

/**
 * Joomla! CMS URI Class
 *
 * @since  1.0
 */
class Uri extends BaseUri
{
	/**
	 * An array of Uri instances.
	 *
	 * @var    Uri[]
	 * @since  1.0
	 */
	private static $instances = [];

	/**
	 * The current calculated base URL segments.
	 *
	 * @var    array
	 * @since  1.0
	 */
	private static $base = [];

	/**
	 * The current calculated root URL segments.
	 *
	 * @var    array
	 * @since  1.0
	 */
	private static $root = [];

	/**
	 * The current URL.
	 *
	 * @var    string
	 * @since  1.0
	 */
	private static $current;

	/**
	 * Returns the global Uri object, only creating it if it doesn't already exist.
	 *
	 * @param   string  $uri  The URI to parse.  [optional: if null uses script URI]
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public static function getInstance($uri = 'SERVER')
	{
		if (empty(self::$instances[$uri]))
		{
			// Are we obtaining the URI from the server?
			if ($uri == 'SERVER')
			{
				// Determine if the request was over SSL (HTTPS).
				if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off'))
				{
					$https = 's://';
				}
				else
				{
					$https = '://';
				}

				/*
				 * Since we are assigning the URI from the server variables, we first need
				 * to determine if we are running on apache or IIS.  If PHP_SELF and REQUEST_URI
				 * are present, we will assume we are running on apache.
				 */

				if (!empty($_SERVER['PHP_SELF']) && !empty($_SERVER['REQUEST_URI']))
				{
					// To build the entire URI we need to prepend the protocol, and the http host
					// to the URI string.
					$theURI = 'http' . $https . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				}
				else
				{
					/*
					 * Since we do not have REQUEST_URI to work with, we will assume we are
					 * running on IIS and will therefore need to work some magic with the SCRIPT_NAME and
					 * QUERY_STRING environment variables.
					 *
					 * IIS uses the SCRIPT_NAME variable instead of a REQUEST_URI variable... thanks, MS
					 */
					$theURI = 'http' . $https . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

					// If the query string exists append it to the URI string
					if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']))
					{
						$theURI .= '?' . $_SERVER['QUERY_STRING'];
					}
				}

				// Extra cleanup to remove invalid chars in the URL to prevent injections through the Host header
				$theURI = str_replace(array("'", '"', '<', '>'), array("%27", "%22", "%3C", "%3E"), $theURI);
			}
			else
			{
				// We were given a URI
				$theURI = $uri;
			}

			self::$instances[$uri] = new static($theURI);
		}

		return self::$instances[$uri];
	}

	public function setBase(array $base)
	{
		self::$base = $base;

		return $this;
	}

	/**
	 * Returns the base URI for the request.
	 *
	 * @param   boolean  $pathonly  If false, prepend the scheme, host and port information. Default is false.
	 *
	 * @return  string  The base URI string
	 *
	 * @since   1.0
	 */
	public function base($pathonly = false)
	{
		// Attempt to find the base request path if not set by the app.
		if (empty(self::$base))
		{
			$uri = static::getInstance();
			self::$base['host'] = $uri->toString(array('scheme', 'host', 'port'));

			if (strpos(php_sapi_name(), 'cgi') !== false && !ini_get('cgi.fix_pathinfo') && !empty($_SERVER['REQUEST_URI']))
			{
				// PHP-CGI on Apache with "cgi.fix_pathinfo = 0"

				// We shouldn't have user-supplied PATH_INFO in PHP_SELF in this case
				// because PHP will not work with PATH_INFO at all.
				$script_name = $_SERVER['PHP_SELF'];
			}
			else
			{
				// Others
				$script_name = $_SERVER['SCRIPT_NAME'];
			}

			self::$base['path'] = rtrim(dirname($script_name), '/\\');
		}

		return $pathonly === false ? self::$base['host'] . self::$base['path'] . '/' : self::$base['path'];
	}

	/**
	 * Returns the root URI for the request.
	 *
	 * @param   boolean  $pathonly  If false, prepend the scheme, host and port information. Default is false.
	 * @param   string   $path      The path
	 *
	 * @return  string  The root URI string.
	 *
	 * @since   1.0
	 */
	public function root($pathonly = false, $path = null)
	{
		// Get the scheme
		if (empty(self::$root))
		{
			$uri = static::getInstance(static::base());
			self::$root['host'] = $uri->toString(array('scheme', 'host', 'port'));
			self::$root['path'] = rtrim($uri->toString(array('path')), '/\\');
		}

		// Get the scheme
		if (isset($path))
		{
			self::$root['path'] = $path;
		}

		return $pathonly === false ? self::$root['host'] . self::$root['path'] . '/' : self::$root['path'];
	}

	/**
	 * Returns the URL for the request, minus the query.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function current()
	{
		// Get the current URL.
		if (empty(self::$current))
		{
			$uri = static::getInstance();
			self::$current = $uri->toString(array('scheme', 'host', 'port', 'path'));
		}

		return self::$current;
	}

	/**
	 * Method to reset class static members for testing and other various issues.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function reset()
	{
		self::$instances = [];
		self::$base = [];
		self::$root = [];
		self::$current = '';
	}

	/**
	 * Checks if the supplied URL is internal
	 *
	 * @param   string  $url  The URL to check.
	 *
	 * @return  boolean  True if Internal.
	 *
	 * @since   1.0
	 */
	public function isInternal($url)
	{
		$uri = static::getInstance($url);
		$base = $uri->toString(array('scheme', 'host', 'port', 'path'));
		$host = $uri->toString(array('scheme', 'host', 'port'));

		if (stripos($base, static::base()) !== 0 && !empty($host))
		{
			return false;
		}

		return true;
	}
}
