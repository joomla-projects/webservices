<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Api\Hal;

use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Utilities\ArrayHelper;
use Joomla\Webservices\Uri\Uri;
use Joomla\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Webservices\Xml\XmlHelper;
use Joomla\Webservices\Webservices\WebserviceHelper;

/**
 * Interface to handle api calls
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.2
 */
class HalHelper
{
	/**
	 * An array to hold webservices xmls
	 *
	 * @var    array
	 * @since  1.2
	 */
	public static $webservices = array();

	/**
	 * An array to hold installed Webservices data
	 *
	 * @var    array
	 * @since  1.2
	 */
	public static $installedWebservices = null;

	/**
	 * Get Default scopes for all webservices
	 *
	 * @param   Text  $text  The language text object for translations
	 *
	 * @return  array
	 *
	 * @since   1.2
	 */
	public static function getDefaultScopes($text)
	{
		return array(
			array('scope' => 'site.create',
				'scopeDisplayName' => $text->translate('JSITE') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_CREATE')),
			array('scope' => 'site.read',
				'scopeDisplayName' => $text->translate('JSITE') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_READ')),
			array('scope' => 'site.update',
				'scopeDisplayName' => $text->translate('JSITE') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_UPDATE')),
			array('scope' => 'site.delete',
				'scopeDisplayName' => $text->translate('JSITE') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_DELETE')),
			array('scope' => 'site.task',
				'scopeDisplayName' => $text->translate('JSITE') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_TASKS')),
			array('scope' => 'site.documentation',
				'scopeDisplayName' => $text->translate('JSITE') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_DOCUMENTATION')),
			array('scope' => 'administrator.create',
				'scopeDisplayName' => $text->translate('JADMINISTRATOR') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_CREATE')),
			array('scope' => 'administrator.read',
				'scopeDisplayName' => $text->translate('JADMINISTRATOR') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_READ')),
			array('scope' => 'administrator.update',
				'scopeDisplayName' => $text->translate('JADMINISTRATOR') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_UPDATE')),
			array('scope' => 'administrator.delete',
				'scopeDisplayName' => $text->translate('JADMINISTRATOR') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_DELETE')),
			array('scope' => 'administrator.task',
				'scopeDisplayName' => $text->translate('JADMINISTRATOR') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_TASKS')),
			array('scope' => 'administrator.documentation',
				'scopeDisplayName' => $text->translate('JADMINISTRATOR') . ' - ' . $text->translate('LIB_WEBSERVICES_API_SCOPES_ALL_WEBSERVICES_DOCUMENTATION')),
		);
	}

	/**
	 * Loading of webservice XML file
	 *
	 * @param   string  $client             Client
	 * @param   string  $webserviceName     Webservice name
	 * @param   string  $version            Version of the webservice
	 * @param   string  $path               Path to webservice files
	 * @param   bool    $showNotifications  Show notifications
	 *
	 * @throws  \Exception
	 * @return  array  List of objects
	 */
	public static function getWebservices($client = '', $webserviceName = '', $version = '1.0.0', $path = '', $showNotifications = false)
	{
		if (empty(self::$webservices) || (!empty($webserviceName) && empty(self::$webservices[$client][$webserviceName][$version])))
		{
			try
			{
				self::loadWebservices($client, $webserviceName, $version, $path);
			}
			catch (\Exception $e)
			{
				if ($showNotifications)
				{
					JFactory::getApplication()->enqueueMessage($e->getMessage(), 'message');
				}
				else
				{
					throw $e;
				}
			}
		}

		if (empty($webserviceName))
		{
			return self::$webservices;
		}

		if (!empty(self::$webservices[$client][$webserviceName][$version]))
		{
			return self::$webservices[$client][$webserviceName][$version];
		}

		return array();
	}

	/**
	 * Loading of related XML files
	 *
	 * @param   string  $client             Client
	 * @param   string  $webserviceName     Webservice name
	 * @param   string  $version            Version of the webservice
	 * @param   string  $path               Path to webservice files
	 * @param   bool    $showNotifications  Show notifications
	 *
	 * @throws  \Exception
	 * @return  array  List of objects
	 */
	public static function loadWebservices($client = '', $webserviceName = '', $version = '1.0.0', $path = '', $showNotifications = false)
	{
		if (empty($webserviceName))
		{
			$folders = Folder::folders(WebserviceHelper::getWebservicesPath(), '.', true);
			$webserviceXmls[' '] = Folder::files(WebserviceHelper::getWebservicesPath(), '.xml');

			foreach ($folders as $folder)
			{
				$webserviceXmls[$folder] = Folder::files(WebserviceHelper::getWebservicesPath() . '/' . $folder, '.xml');
			}

			foreach ($webserviceXmls as $webserviceXmlPath => $webservices)
			{
				foreach ($webservices as $webservice)
				{
					try
					{
						// Version, Extension and Client are already part of file name
						$xml = self::loadWebserviceConfiguration($webservice, $version = '', $extension = '', trim($webserviceXmlPath));

						if (!empty($xml))
						{
							$client = self::getWebserviceClient($xml);
							$version = !empty($xml->config->version) ? (string) $xml->config->version : $version;
							$xml->webservicePath = trim($webserviceXmlPath);
							self::$webservices[$client][(string) $xml->config->name][$version] = $xml;
						}
					}
					catch (\Exception $e)
					{
						if ($showNotifications)
						{
							JFactory::getApplication()->enqueueMessage($e->getMessage(), 'message');
						}
						else
						{
							throw $e;
						}
					}
				}
			}
		}
		else
		{
			try
			{
				$xml = self::loadWebserviceConfiguration($webserviceName, $version, 'xml', $path, $client);

				if (!empty($xml))
				{
					$client = self::getWebserviceClient($xml);
					$version = !empty($xml->config->version) ? (string) $xml->config->version : $version;
					$xml->webservicePath = trim($path);
					self::$webservices[$client][(string) $xml->config->name][$version] = $xml;
				}
			}
			catch (\Exception $e)
			{
				if ($showNotifications)
				{
					JFactory::getApplication()->enqueueMessage($e->getMessage(), 'message');
				}
				else
				{
					throw $e;
				}
			}
		}
	}

	/**
	 * Method to finds the full real file path, checking possible overrides
	 *
	 * @param   string  $client          Client
	 * @param   string  $webserviceName  Name of the webservice
	 * @param   string  $version         Suffixes to the file name (ex. 1.0.0)
	 * @param   string  $extension       Extension of the file to search
	 * @param   string  $path            Path to webservice files
	 *
	 * @return  string  The full path to the api file
	 *
	 * @since   1.2
	 */
	public static function getWebserviceFile($client, $webserviceName, $version = '', $extension = 'xml', $path = '')
	{
		if (!empty($webserviceName))
		{
			$version = !empty($version) ? array(Path::clean($version)) : array('1.0.0');
			$webservicePath = !empty($path) ? WebserviceHelper::getWebservicesPath() . '/' . $path : WebserviceHelper::getWebservicesPath();

			// Search for suffixed versions. Example: content.1.0.0.xml
			if (!empty($version))
			{
				foreach ($version as $suffix)
				{
					$rawPath = $webserviceName . '.' . $suffix;
					$rawPath = !empty($extension) ? $rawPath . '.' . $extension : $rawPath;
					$rawPath = !empty($client) ? $client . '.' . $rawPath : $rawPath;

					if ($configurationFullPath = Path::find($webservicePath, $rawPath))
					{
						return $configurationFullPath;
					}
				}
			}

			// Standard version
			$rawPath = !empty($extension) ? $webserviceName . '.' . $extension : $webserviceName;
			$rawPath = !empty($client) ? $client . '.' . $rawPath : $rawPath;

			return Path::find($webservicePath, $rawPath);
		}

		return null;
	}

	/**
	 * Load configuration file and set all Api parameters
	 *
	 * @param   array   $webserviceName  Name of the webservice file
	 * @param   Text    $text            The text object
	 * @param   string  $version         Suffixes for loading of webservice configuration file
	 * @param   string  $extension       File extension name
	 * @param   string  $path            Path to webservice files
	 * @param   string  $client          Client
	 *
	 * @return  \SimpleXMLElement  Loaded configuration object
	 *
	 * @since   1.2
	 * @throws  \Exception
	 */
	public static function loadWebserviceConfiguration($webserviceName, Text $text, $version = '', $extension = 'xml', $path = '', $client = '')
	{
		// Check possible overrides, and build the full path to api file
		$configurationFullPath = self::getWebserviceFile($client, strtolower($webserviceName), $version, $extension, $path);

		if (!is_readable($configurationFullPath))
		{
			throw new \Exception($text->translate('LIB_WEBSERVICES_API_HAL_WEBSERVICE_CONFIGURATION_FILE_UNREADABLE'));
		}

		$content = @file_get_contents($configurationFullPath);

		if (is_string($content))
		{
			return new \SimpleXMLElement($content);
		}

		return null;
	}

	/**
	 * Upload Webservices config files to webservices media location
	 *
	 * @param   array  $files  The array of Files (file descriptor returned by PHP)
	 *
	 * @return  boolean  Returns true if Upload was successful
	 */
	public static function uploadWebservice($files = array())
	{
		$uploadOptions = array(
			'allowedFileExtensions' => 'xml',
			'allowedMIMETypes'      => 'application/xml, text/xml',
			'overrideExistingFile'  => true,
		);

		foreach ($files as $key => &$file)
		{
			$objectFile = new JObject($file);

			try
			{
				$content = file_get_contents($objectFile->tmp_name);
				$fileContent = null;

				if (is_string($content))
				{
					$fileContent = new \SimpleXMLElement($content);
				}

				$name = (string) $fileContent->config->name;
				$version = !empty($fileContent->config->version) ? (string) $fileContent->config->version : '1.0.0';

				$client = self::getWebserviceClient($fileContent);

				$file['name'] = $client . '.' . $name . '.' . $version . '.xml';
			}
			catch (\Exception $e)
			{
				unset($files[$key]);
				JFactory::getApplication()->enqueueMessage(JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_FILE_NOT_VALID'), 'message');
			}
		}

		return \WebservicesHelper::uploadFiles($files, WebserviceHelper::getWebservicesPath() . '/upload', $uploadOptions);
	}

	/**
	 * Get list of all webservices from webservices parameters
	 *
	 * @param   DatabaseDriver  $db  The database driver object
	 *
	 * @return  array  Array or table with columns columns
	 */
	public static function getInstalledWebservices(DatabaseDriver $db)
	{
		if (!isset(self::$installedWebservices))
		{
			self::$installedWebservices = array();

			$query = $db->getQuery(true)
				->select('*')
				->from('#__webservices')
				->order('created_date ASC');

			$db->setQuery($query);
			$webservices = $db->loadObjectList();

			if (!empty($webservices))
			{
				foreach ($webservices as $webservice)
				{
					self::$installedWebservices[$webservice->client][$webservice->name][$webservice->version] = ArrayHelper::fromObject($webservice);
				}
			}
		}

		return self::$installedWebservices;
	}

	/**
	 * Get installed webservice options
	 *
	 * @param   string          $client          Client
	 * @param   string          $webserviceName  Webservice Name
	 * @param   string          $version         Webservice version
	 * @param   DatabaseDriver  $db              The database driver object
	 *
	 * @return  array  Array of webservice options
	 */
	public static function getInstalledWebservice($client, $webserviceName, $version, DatabaseDriver $db)
	{
		// Initialise Installed webservices
		$webservices = self::getInstalledWebservices($db);

		if (!empty($webservices[$client][$webserviceName][$version]))
		{
			return $webservices[$client][$webserviceName][$version];
		}

		return null;
	}

	/**
	 * Checks if specific Webservice is installed and active
	 *
	 * @param   string          $client          Client
	 * @param   string          $webserviceName  Webservice Name
	 * @param   string          $version         Webservice version
	 * @param   DatabaseDriver  $db              The database driver object
	 *
	 * @return  array  Array or table with columns columns
	 */
	public static function isPublishedWebservice($client, $webserviceName, $version, DatabaseDriver $db)
	{
		$installedWebservices = self::getInstalledWebservices($db);

		if (!empty($installedWebservices))
		{
			if (empty($version))
			{
				$version = self::getNewestWebserviceVersion($client, $webserviceName, $db);
			}

			$webservice = $installedWebservices[$client][$webserviceName][$version];

			return !empty($webservice['state']);
		}

		return false;
	}

	/**
	 * Checks if specific Webservice is installed and active
	 *
	 * @param   string          $client          Client
	 * @param   string          $webserviceName  Webservice Name
	 * @param   DatabaseDriver  $db              The database driver object
	 *
	 * @return  array  Array or table with columns columns
	 */
	public static function getNewestWebserviceVersion($client, $webserviceName, DatabaseDriver $db)
	{
		$installedWebservices = self::getInstalledWebservices($db);

		if (!empty($installedWebservices) && isset($installedWebservices[$client][$webserviceName]))
		{
			// First element is always newest
			foreach ($installedWebservices[$client][$webserviceName] as $version => $webservice)
			{
				return $version;
			}
		}

		return '1.0.0';
	}

	/**
	 * Returns Client of the webservice
	 *
	 * @param   \SimpleXMLElement|array  $xmlElement  XML object
	 *
	 * @return  string
	 */
	public static function getWebserviceClient($xmlElement)
	{
		return !empty($xmlElement['client']) && strtolower($xmlElement['client']) == 'administrator' ? 'administrator' : 'site';
	}

	/**
	 * Returns Scopes of the webservice
	 *
	 * @param   Text            $text          The language text object for translations
	 * @param   array           $filterScopes  Scopes that will be used as a filter
	 * @param   DatabaseDriver  $db            The database driver object
	 *
	 * @return  array
	 */
	public static function getWebserviceScopes(Text $text, $filterScopes = array(), DatabaseDriver $db)
	{
		$options = array();
		$installedWebservices = self::getInstalledWebservices($db);

		if (empty($filterScopes))
		{
			// Options for all webservices
			$options[$text->translate('COM_WEBSERVICES_OAUTH_CLIENTS_SCOPES_ALL_WEBSERVICES')] = self::getDefaultScopes($text);
		}

		if (!empty($installedWebservices))
		{
			foreach ($installedWebservices as $webserviceClient => $webserviceNames)
			{
				foreach ($webserviceNames as $webserviceName => $webserviceVersions)
				{
					foreach ($webserviceVersions as $version => $webservice)
					{
						$webserviceDisplayName = $text->translate('J' . $webserviceClient) . ' '
							. (!empty($webservice['title']) ? $webservice['title'] : $webserviceName);

						if (!empty($webservice['scopes']))
						{
							$scopes = json_decode($webservice['scopes'], true);

							foreach ($scopes as $scope)
							{
								$scopeParts = explode('.', $scope['scope']);

								// For global check of filtered scopes using $client . '.' . $operation
								$globalCheck = $scopeParts[0] . '.' . $scopeParts[2];

								if (empty($filterScopes) || in_array($scope['scope'], $filterScopes) || in_array($globalCheck, $filterScopes))
								{
									$options[$webserviceDisplayName][] = $scope;
								}
							}
						}
					}
				}
			}
		}

		return $options;
	}

	/**
	 * Returns list of transform elements
	 *
	 * @return  array
	 */
	public static function getTransformElements()
	{
		static $transformElements = null;

		if (!is_null($transformElements))
		{
			return $transformElements;
		}

		$transformElementsFiles = Folder::files(JPATH_API . '/src/Api/Hal/Transform', '.php');
		$transformElements = array();

		foreach ($transformElementsFiles as $transformElement)
		{
			if (!in_array($transformElement, array('interface.php', 'base.php')))
			{
				$name = str_replace('.php', '', $transformElement);
				$transformElements[] = array(
					'value' => $name,
					'text' => $name,
				);
			}
		}

		return $transformElements;
	}

	/**
	 * Returns transform element that is appropriate to db type
	 *
	 * @param   string  $type  Database type
	 *
	 * @return  string
	 */
	public static function getTransformElementByDbType($type)
	{
		$type = explode('(', $type);
		$type = strtoupper(trim($type[0]));

		// We do not test for Varchar because fallback Transform Element String
		switch ($type)
		{
			case 'TINYINT':
			case 'SMALLINT':
			case 'MEDIUMINT':
			case 'INT':
			case 'BIGINT':
				return 'int';
			case 'FLOAT':
			case 'DOUBLE':
			case 'DECIMAL':
				return 'float';
			case 'DATE':
			case 'DATETIME':
			case 'TIMESTAMP':
			case 'TIME':
				return 'datetime';
		}

		return 'string';
	}

	/**
	 * Returns uri to the webservice
	 *
	 * @param   string  $client        Client
	 * @param   string  $name          Name
	 * @param   string  $version       Version
	 * @param   string  $appendApi     Append api at the end or the URI
	 * @param   string  $appendFormat  Append format at the end or the URI
	 *
	 * @return  string
	 */
	public static function buildWebserviceUri($client, $name, $version, $appendApi = '', $appendFormat = '')
	{
		$uri = 'webserviceClient=' . $client
			. '&webserviceVersion=' . $version;

		// Views are separated by dash
		$view = explode('-', $name);
		$name = $view[0];

		$uri .= '&option=' . $name;

		if (!empty($view[1]))
		{
			$uri .= '&view=' . $view[1];
		}

		if (!empty($appendApi))
		{
			$uri .= '&api=' . $appendApi;
		}

		if (!empty($appendFormat))
		{
			$uri .= '&format=' . $appendFormat;
		}

		return $uri;
	}

	/**
	 * Returns Full URL to the webservice
	 *
	 * @param   string  $client        Client
	 * @param   string  $name          Name
	 * @param   string  $version       Version
	 * @param   string  $appendApi     Append api at the end or the URI
	 * @param   string  $appendFormat  Append format at the end or the URI
	 *
	 * @return  string
	 */
	public static function buildWebserviceFullUrl($client, $name, $version, $appendApi = '', $appendFormat = '')
	{
		$uri = self::buildWebserviceUri($client, $name, $version, $appendApi, $appendFormat);
		$baseUri = Uri::getInstance();

		return rtrim($baseUri->base(), '/') . '/index.php?' . $uri;
	}

	/**
	 * Returns user credentials from globals
	 *
	 * @return  array
	 */
	public static function getCredentialsFromGlobals()
	{
		$credentials = array();
		$headers = self::getHeaderVariablesFromGlobals();

		if (isset($headers['PHP_AUTH_USER']) && isset($headers['PHP_AUTH_PW']))
		{
			return $credentials = array(
				'username'	 => $headers['PHP_AUTH_USER'],
				'password'	 => $headers['PHP_AUTH_PW']
			);
		}

		return $credentials;
	}

	/**
	 * Returns header variables from globals
	 *
	 * @return  array
	 */
	public static function getHeaderVariablesFromGlobals()
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

		if (isset($server['PHP_AUTH_USER']))
		{
			$headers['PHP_AUTH_USER'] = $server['PHP_AUTH_USER'];
			$headers['PHP_AUTH_PW'] = isset($server['PHP_AUTH_PW']) ? $server['PHP_AUTH_PW'] : '';
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

			if (isset($server['HTTP_AUTHORIZATION']))
			{
				$authorizationHeader = $server['HTTP_AUTHORIZATION'];
			}
			elseif (isset($server['REDIRECT_HTTP_AUTHORIZATION']))
			{
				$authorizationHeader = $server['REDIRECT_HTTP_AUTHORIZATION'];
			}
			elseif (function_exists('apache_request_headers'))
			{
				$requestHeaders = (array) apache_request_headers();

				// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
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
	 * Returns an array of fields from Element Fields properties
	 *
	 * @param   \SimpleXMLElement  $xmlElement   Xml element
	 * @param   boolean            $primaryKeys  Only extract primary keys
	 *
	 * @return  array
	 */
	public static function getFieldsArray($xmlElement, $primaryKeys = false)
	{
		$fields = array();

		if (isset($xmlElement->fields->field))
		{
			foreach ($xmlElement->fields->field as $field)
			{
				$fieldAttributes = XmlHelper::getXMLElementAttributes($field);

				if (($primaryKeys && XmlHelper::isAttributeTrue($field, 'isPrimaryField'))
					|| !$primaryKeys)
				{
					$fields[$fieldAttributes['name']] = $fieldAttributes;
				}
			}
		}

		// If there are no primary keys defined we will use id field as default
		if (empty($fields) && $primaryKeys)
		{
			$fields['id'] = array('name' => 'id', 'transform' => 'int');
		}

		return $fields;
	}

	/**
	 * Gets list of filter fields from operation configuration
	 *
	 * @param   \SimpleXMLElement  $configuration   Configuration for current action
	 * @param   boolean            $excludeSearch   Exclude the search element, maintaining just the xml-provided fields
	 * @param   boolean            $fullDefinition  Gets the full definition of the filter, not just the name
	 *
	 * @return  array
	 *
	 * @since   1.3
	 */
	public static function getFilterFields($configuration, $excludeSearch = false, $fullDefinition = false)
	{
		// We have one search filter field
		$filterFields = array();

		if (!$excludeSearch)
		{
			if ($fullDefinition)
			{
				$filterFields[] = array(
					'name' => 'search',
					'isRequiredField' => 'false',
					'transform' => 'string'
				);
			}
			else
			{
				$filterFields[] = 'search';
			}
		}

		if (!empty($configuration->fields))
		{
			foreach ($configuration->fields->field as $field)
			{
				if (XmlHelper::isAttributeTrue($field, 'isFilterField'))
				{
					if ($fullDefinition)
					{
						$required = 'false';

						if (XmlHelper::isAttributeTrue($field, 'isRequiredField'))
						{
							$required = 'true';
						}

						$filterFields[] = array(
							'name' => (string) $field['name'],
							'isRequiredField' => $required,
							'transform' => (isset($field['transform'])) ? (string) $field['transform'] : 'string'
						);
					}
					else
					{
						$filterFields[] = (string) $field["name"];
					}
				}
			}
		}

		return $filterFields;
	}
}
