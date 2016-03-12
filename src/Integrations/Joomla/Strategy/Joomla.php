<?php
/**
 * Joomla authorisation strategy for Joomla webservices application.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Integrations\Joomla\Strategy;

use Joomla\Authentication\AuthenticationStrategyInterface;
use Joomla\Authentication\Authentication;
use Joomla\Webservices\Webservices\ConfigurationHelper;

/**
 * Joomla authorisation strategy for Joomla webservices application.
 *
 * @since  __DEPLOY_VERSION__
 */
class Joomla implements AuthenticationStrategyInterface
{
	/**
	 * The last authentication status.
	 *
	 * @var    integer
	 * @since  __DEPLOY_VERSION__
	 */
	protected $status;

	/**
	 * Attempt authentication.
	 *
	 * @return  string|boolean  A string containing a username if authentication is successful, false otherwise.
	 *
	 * @since   1.0
	 */
	public function authenticate()
	{
		$credentials = array();
		$headers = $this->getHeaderVariablesFromGlobals();

		if (isset($headers['PHP_AUTH_USER']) && isset($headers['PHP_AUTH_PW']))
		{
			$credentials = array(
				'username'	 => $headers['PHP_AUTH_USER'],
				'password'	 => $headers['PHP_AUTH_PW']
			);
		}

		if (empty($credentials['username']))
		{
			$this->status = Authentication::INCOMPLETE_CREDENTIALS;

			return false;
		}

		if (empty($credentials))
		{
			$this->status = Authentication::NO_CREDENTIALS;

			return false;
		}

		$options = array(
			'silent' => true
		);

		// Log into the CMS
		$result = \JFactory::getApplication()->login($credentials, $options);

		if ($result === true)
		{
			$this->status = Authentication::SUCCESS;

			return $credentials['username'];
		}

		$this->status = Authentication::NO_SUCH_USER;

		return false;
	}

	/**
	 * Returns header variables from globals
	 *
	 * @return  array
	 */
	public function getHeaderVariablesFromGlobals()
	{
		$headers = array();

		foreach ($_SERVER as $key => $value)
		{
			if (strpos($key, 'HTTP_') === 0)
			{
				$headers[substr($key, 5)] = $value;
			}
			// CONTENT_* are not prefixed with HTTP_
			elseif (in_array($key, array('CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE')))
			{
				$headers[$key] = $value;
			}
		}

		if (isset($_SERVER['PHP_AUTH_USER']))
		{
			$headers['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];
			$headers['PHP_AUTH_PW'] = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
		}
		else
		{
			/*
			 * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
			 * For this workaround to work, add this line to your .htaccess file:
			 * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
			 *
			 * A sample .htaccess file:
			 * RewriteEngine On
			 * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
			 * RewriteCond %{REQUEST_FILENAME} !-f
			 * RewriteRule ^(.*)$ app.php [QSA,L]
			 */

			$authorizationHeader = null;

			if (isset($_SERVER['HTTP_AUTHORIZATION']))
			{
				$authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
			}
			elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']))
			{
				$authorizationHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
			}
			elseif (function_exists('apache_request_headers'))
			{
				$requestHeaders = (array) apache_request_headers();

				// Server-side fix for bug in old Android versions.
				// A nice side-effect of this fix means we don't care about capitalization for Authorization.
				$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));

				if (isset($requestHeaders['Authorization']))
				{
					$authorizationHeader = trim($requestHeaders['Authorization']);
				}
			}

			if (null !== $authorizationHeader)
			{
				$headers['AUTHORIZATION'] = $authorizationHeader;

				// Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
				if (0 === stripos($authorizationHeader, 'basic'))
				{
					$exploded = explode(':', base64_decode(substr($authorizationHeader, 6)));

					if (count($exploded) == 2)
					{
						list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
					}
				}
			}
		}

		// PHP_AUTH_USER/PHP_AUTH_PW
		if (isset($headers['PHP_AUTH_USER']))
		{
			$headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ':' . $headers['PHP_AUTH_PW']);
		}

		return $headers;
	}

	/**
	 * Get last authentication result.
	 *
	 * @return  integer  An integer from Authentication class constants with the authentication result.
	 *
	 * @since   1.0
	 */
	public function getResult()
	{
		return $this->status;
	}
}
