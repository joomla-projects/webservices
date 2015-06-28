<?php
/**
 * Created by PhpStorm.
 * User: georg_000
 * Date: 28/06/2015
 * Time: 23:20
 */

namespace Joomla\Webservices\Integrations\Joomla\Strategy;

use Joomla\Authentication\AuthenticationStrategyInterface;
use Joomla\Authentication\Authentication;
use Joomla\Webservices\Webservices\ConfigurationHelper;

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
		$headers = ConfigurationHelper::getHeaderVariablesFromGlobals();

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