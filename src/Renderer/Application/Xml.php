<?php
/**
 * @package     Redcore
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Renderer\Application;

/**
 * ApiDocumentHal class, provides an easy interface to parse and display HAL+JSON or HAL+XML output
 *
 * @package     Redcore
 * @subpackage  Document
 * @see         http://stateless.co/hal_specification.html
 * @since       1.2
 */
class Xml extends Halxml
{
	/**
	 * Class constructor.
	 *
	 * @param   object  $application  The application.
	 * @param   array   $options      Associative array of options.
	 *
	 * @since  1.2
	 */
	public function __construct($application, $options = array())
	{
		parent::__construct($application, $options);

		// Set default mime type.
		$this->setMimeEncoding('application/xml', false);

		// Set document type.
		$this->setType('xml');
	}
}
