<?php
/**
 * Webservices component for Joomla! CMS
 *
 * @copyright  Copyright (C) 2004 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Webservices\Table;

use Joomla\Database\DatabaseDriver;

/**
 * Webservice table.
 *
 * @package     Joomla!
 * @subpackage  Webservices
 * @since       1.0
 */
class WebserviceTable extends \JTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  Database driver object.
	 *
	 * @throws  UnexpectedValueException
	 */
	public function __construct($db)
	{
		$this->_tableName = '#__webservices';
		$this->_tbl_key = 'id';

		parent::__construct($this->_tableName, $this->_tbl_key, $db);
	}

	/**
	 * Checks that the object is valid and able to be stored.
	 *
	 * This method checks that the parent_id is non-zero and exists in the database.
	 * Note that the root node (parent_id = 0) cannot be manipulated with this class.
	 *
	 * @return  boolean  True if all checks pass.
	 */
	public function check()
	{
		// Check if client is not already created with this id.
		$client = clone $this;

		$this->client = trim($this->client);
		$this->name = trim($this->name);
		$this->version = trim($this->version);

		if (empty($this->version))
		{
			$this->version = '1.0.0';
		}
		else
		{
			$matches = array();

			// Major
			$versionPattern = '/^(?<version>[0-9]+\.[0-9]+\.[0-9]+)$/';

			// Match the possible parts of a SemVer
			$matched = preg_match(
				$versionPattern,
				$this->version,
				$matches
			);

			// No match, invalid
			if (!$matched)
			{
				$this->setError(JText::_('COM_WEBSERVICES_WEBSERVICE_VERSION_WRONG_FORMAT'));

				return false;
			}

			$this->version = $matches['version'];
		}

		if (empty($this->name))
		{
			$this->setError(JText::_('COM_WEBSERVICES_WEBSERVICE_NAME_FIELD_CANNOT_BE_EMPTY'));

			return false;
		}

		if (empty($this->client))
		{
			$this->setError(JText::_('COM_WEBSERVICES_WEBSERVICE_CLIENT_FIELD_CANNOT_BE_EMPTY'));

			return false;
		}

		if ($client->load(array('client' => $this->client, 'name' => $this->name, 'version' => $this->version)) && $client->id != $this->id)
		{
			$this->setError(JText::_('COM_WEBSERVICES_WEBSERVICE_ALREADY_EXISTS'));

			return false;
		}

		return true;
	}
}
