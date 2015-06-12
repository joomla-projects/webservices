<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Redcore
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('JPATH_BASE') or die;

/**
 * Authentication plugin for redCORE OAuth2 server
 *
 * @package     Joomla.Plugin
 * @subpackage  System
 * @since       1.0
 */
class PlgAuthenticationRedcore_Oauth2 extends JPlugin
{
	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @param   array   $credentials  Array holding the user credentials
	 * @param   array   $options      Array of extra options
	 * @param   object  &$response    Authentication response object
	 *
	 * @return  bool
	 */
	public function onUserAuthenticate($credentials, $options, &$response)
	{
		$response->type = 'redcore_oauth2';
		$scopes = !empty($options['scopes']) ? $options['scopes'] : array();
		$format = !empty($options['format']) ? $options['format'] : 'json';

		// Check if redCORE OAuth2 server is installed
		if (class_exists('RApiOauth2Helper'))
		{
			/** @var $oauth2Response OAuth2\Response */
			$oauth2Response = RApiOauth2Helper::verifyResourceRequest($scopes);

			if ($oauth2Response instanceof OAuth2\Response)
			{
				if (!$oauth2Response->isSuccessful())
				{
					$response->status        = JAuthentication::STATUS_FAILURE;
					$response->error_message = JText::sprintf('PLG_AUTHENTICATION_REDCORE_OAUTH2_OAUTH2_SERVER_ERROR', $oauth2Response->getResponseBody($format));

					return false;
				}
			}
			elseif ($oauth2Response === false)
			{
				$response->status        = JAuthentication::STATUS_FAILURE;
				$response->error_message = JText::_('PLG_AUTHENTICATION_REDCORE_OAUTH2_OAUTH2_SERVER_IS_NOT_ACTIVE');

				return false;
			}
			else
			{
				$oauth2Response = json_decode($oauth2Response);

				if (!empty($oauth2Response->user_id))
				{
					$user = JFactory::getUser($oauth2Response->user_id);

					// Load the JUser class on application for this client
					JFactory::getApplication()->loadIdentity($user);
					JFactory::getSession()->set('user', $user);

					// Bring this in line with the rest of the system
					$response->email    = $user->email;
					$response->fullname = $user->name;

					if (JFactory::getApplication()->isAdmin())
					{
						$response->language = $user->getParam('admin_language');
					}
					else
					{
						$response->language = $user->getParam('language');
					}

					$response->status        = JAuthentication::STATUS_SUCCESS;
					$response->error_message = '';

					return true;
				}
			}
		}

		return false;
	}
}
