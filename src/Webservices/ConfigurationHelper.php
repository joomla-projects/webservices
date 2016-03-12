<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Webservices;

use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Utilities\ArrayHelper;
use Joomla\Webservices\Uri\Uri;
use Joomla\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Webservices\Webservices\Exception\ConfigurationException;
use Joomla\Webservices\Xml\XmlHelper;

/**
 * A collection of static helper methods dealing with the reading
 * and interpretation of webservice XML configuration files.
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.2
 */
class ConfigurationHelper
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
	 * Returns an array containing all webservices
	 *
	 * @throws  \Exception
	 * @return  array  List of objects
	 */
	public static function getWebservices()
	{
		self::loadWebservices();

		return self::$webservices;
	}

	/**
	 * Loading of webservice XML file
	 *
	 * @param   string  $client          Client
	 * @param   string  $webserviceName  Webservice name
	 * @param   string  $version         Version of the webservice
	 *
	 * @throws  \Exception
	 * @return  array  List of objects
	 */
	public static function getWebservice($client = '', $webserviceName = '', $version = '1.0.0')
	{
		self::loadWebservices();

		if (!empty(self::$webservices[$client][$webserviceName][$version]))
		{
			return self::$webservices[$client][$webserviceName][$version];
		}

		return array();
	}

	/**
	 * Loading of related XML files
	 *
	 * @return  void
	 */
	public static function loadWebservices()
	{
		// If we've already run this before then abort
		if (!empty(self::$webservices))
		{
			return;
		}

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
				// Version, Extension and Client are already part of file name
				try
				{
					$xml = self::loadWebserviceConfiguration($webservice, '', trim($webserviceXmlPath));
				}
				catch (ConfigurationException $e)
				{
					$xml = null;
				}

				if (!empty($xml))
				{
					$client = self::getWebserviceClient($xml);
					$version = !empty($xml->config->version) ? (string) $xml->config->version : '';
					$xml->webservicePath = trim($webserviceXmlPath);
					self::$webservices[$client][(string) $xml->config->name][$version] = $xml;
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
	 * @param   string  $path            Path to webservice files
	 *
	 * @return  string  The full path to the api file
	 *
	 * @since   1.2
	 */
	public static function getWebserviceConfig($client, $webserviceName, $version = '', $path = '')
	{
		if (!empty($webserviceName))
		{
			$version = !empty($version) ? $version : '1.0.0';
			$webservicePath = !empty($path) ? WebserviceHelper::getWebservicesPath() . '/' . $path : WebserviceHelper::getWebservicesPath();

			// Search for suffixed versions. Example: content.1.0.0.xml
			$rawPath = $webserviceName . '.' . $version . '.xml';
			$rawPath = !empty($client) ? $client . '.' . $rawPath : $rawPath;

			$configurationFullPath = Path::find($webservicePath, $rawPath);

			if ($configurationFullPath)
			{
				return $configurationFullPath;
			}

			// See if we got passed the actual file name
			return Path::find($webservicePath, $webserviceName);
		}

		return null;
	}

	/**
	 * Method to finds the full real file path, checking possible overrides
	 *
	 * @param   string  $client          Client
	 * @param   string  $webserviceName  Name of the webservice
	 * @param   string  $version         Suffixes to the file name (ex. 1.0.0)
	 * @param   string  $path            Path to webservice files
	 *
	 * @return  string  The full path to the api file
	 *
	 * @since   1.2
	 */
	public static function getWebserviceHelper($client, $webserviceName, $version = '', $path = '')
	{
		if (empty($webserviceName))
		{
			return '';
		}

		$version = !empty($version) ? $version : '1.0.0';
		$webservicePath = !empty($path) ? WebserviceHelper::getWebservicesPath() . '/' . $path : WebserviceHelper::getWebservicesPath();

		// Search for suffixed versions. Example: content.1.0.0.xml
		$rawPath = $webserviceName . '.' . $version . '.php';
		$rawPath = !empty($client) ? $client . '.' . $rawPath : $rawPath;

		$configurationFullPath = Path::find($webservicePath, $rawPath);

		if ($configurationFullPath)
		{
			return $configurationFullPath;
		}

		// Fall back to standard version
		$rawPath = $webserviceName . '.php';
		$rawPath = !empty($client) ? $client . '.' . $rawPath : $rawPath;

		return Path::find($webservicePath, $rawPath);
	}

	/**
	 * Load configuration file and set all Api parameters
	 *
	 * @param   array   $webserviceName  Name of the webservice file
	 * @param   string  $version         Suffixes for loading of webservice configuration file
	 * @param   string  $path            Path to webservice files
	 * @param   string  $client          Client
	 *
	 * @return  \SimpleXMLElement  Loaded configuration object
	 *
	 * @since   1.2
	 * @throws  ConfigurationException
	 */
	public static function loadWebserviceConfiguration($webserviceName, $version = '', $path = '', $client = '')
	{
		// Check possible overrides, and build the full path to api file
		$configurationFullPath = self::getWebserviceConfig($client, strtolower($webserviceName), $version, $path);

		if (!is_readable($configurationFullPath))
		{
			throw new ConfigurationException('The configuration file is unreadable');
		}

		$content = @file_get_contents($configurationFullPath);

		if (is_string($content))
		{
			try
			{
				return new \SimpleXMLElement($content);
			}
			catch (\Exception $e)
			{
				throw new ConfigurationException('The XML in the configuration file is invalid', null, $e);
			}
		}

		throw new ConfigurationException('There was an error parsing the contents of the configuration file');
	}

	/**
	 * Get list of all installed and published webservices from database.
	 *
	 * @param   DatabaseDriver  $db  The database driver object.
	 *
	 * @return  array  Array of installed and published webservices.
	 */
	public static function getInstalledWebservices(DatabaseDriver $db)
	{
		if (!isset(self::$installedWebservices))
		{
			self::$installedWebservices = array();

			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__webservices'))
				->where($db->quoteName('published') . ' = 1')
				->order('created_date ASC');
			$webservices = $db->setQuery($query)->loadObjectList();

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

		if (empty($webservices[$client][$webserviceName][$version]))
		{
			return null;
		}

		return $webservices[$client][$webserviceName][$version];
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

		if (empty($installedWebservices))
		{
			return false;
		}

		if (empty($version))
		{
			$version = self::getNewestWebserviceVersion($client, $webserviceName, $db);
		}

		$webservice = $installedWebservices[$client][$webserviceName][$version];

		return !empty($webservice['published']);
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

		if (empty($installedWebservices) || !isset($installedWebservices[$client][$webserviceName]))
		{
			return '1.0.0';
		}

		// First element is always newest
		foreach ($installedWebservices[$client][$webserviceName] as $version => $webservice)
		{
			return $version;
		}
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
	public static function getWebserviceScopes(Text $text, $filterScopes, DatabaseDriver $db)
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
	 * Returns an array of data from <field> elements defined in the <fields> section
	 * of the configuration XML.
	 *
	 * @param   \SimpleXMLElement  $configuration  Configuration for current action
	 *
	 * @return  array
	 *
	 * @since   1.3
	 */
	public static function getAllFields(\SimpleXMLElement $configuration)
	{
		$fields = array();

		// If there are no fields, return an empty array.
		if (empty($configuration->fields))
		{
			return $fields;
		}

		foreach ($configuration->fields->field as $field)
		{
			$fields[] = XmlHelper::getXMLElementAttributes($field);
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

	/**
	 * Gets list of primary fields from operation configuration
	 *
	 * @param   \SimpleXMLElement  $configuration  Configuration for current action
	 *
	 * @return  array
	 *
	 * @since   1.3
	 */
	public static function getPrimaryFields(\SimpleXMLElement $configuration)
	{
		$primaryFields = array();

		// If there are no primary fields, return an empty array.
		if (empty($configuration->fields))
		{
			return $primaryFields;
		}

		foreach ($configuration->fields->field as $field)
		{
			if (XmlHelper::isAttributeTrue($field, 'isPrimaryField'))
			{
				$primaryFields[] = (string) $field['name'];
			}
		}

		return $primaryFields;
	}
}
