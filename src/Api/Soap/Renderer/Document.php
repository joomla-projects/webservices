<?php
/**
 * @package     Redcore
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Api\Soap\Renderer;

use Joomla\Webservices\Api\Soap\Soap;
use Joomla\Webservices\Renderer\Document as JDocument;
use Joomla\DI\Container;

/**
 * ApiDocumentSoap class, provides an easy interface to parse and display XML output
 *
 * @package     Redcore
 * @subpackage  Document
 * @since       1.4
 */
class Document extends JDocument
{
	/**
	 * Document name
	 *
	 * @var    string
	 * @since  1.4
	 */
	protected $name = 'joomla';

	/**
	 * Render all hrefs as absolute, relative is default
	 */
	protected $absoluteHrefs = false;

	/**
	 * Document format (xml or json)
	 */
	protected $documentFormat = false;

	/**
	 * @var    string  Content
	 * @since  1.4
	 */
	public $outputContent = null;

	/**
	 * @var    Soap  Soap object
	 * @since  1.4
	 */
	public $soap = null;

	/**
	 * Class constructor
	 *
	 * @param   Container  $container  The DIC object
	 * @param   array   $options   Associative array of options
	 * @param   string  $mimeType  Document type
	 *
	 * @since  1.4
	 */
	public function __construct(Container $container, $options = array(), $mimeType = 'soap+xml')
	{
		parent::__construct($container, $options);

		$this->documentFormat = $options['documentFormat'];

		if (!in_array($this->documentFormat, array('xml', 'json')))
		{
			$this->documentFormat = 'xml';
		}

		// Set default mime type.
		$this->_mime = 'application/' . $mimeType;

		// Set document type.
		$this->_type = 'xml';

		// Set absolute/relative hrefs.
		$this->absoluteHrefs = isset($options['absoluteHrefs']) ? $options['absoluteHrefs'] : true;

		// Set token if needed
		$this->uriParams = isset($options['uriParams']) ? $options['uriParams'] : array();
	}

	/**
	 * Render the document.
	 *
	 * @param   boolean  $cache   If true, cache the output
	 * @param   array    $params  Associative array of attributes
	 *
	 * @return  string   The rendered data
	 *
	 * @since  1.4
	 */
	public function render($cache = false, $params = array())
	{
		$runtime = microtime(true) - $this->soap->startTime;

		$this->app->setHeader('Status', $this->soap->statusCode . ' ' . $this->soap->statusText, true);
		$this->app->setHeader('Server', '', true);
		$this->app->setHeader('X-Runtime', $runtime, true);
		$this->app->setHeader('Access-Control-Allow-Origin', '*', true);
		$this->app->setHeader('Pragma', 'public', true);
		$this->app->setHeader('Expires', '0', true);
		$this->app->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
		$this->app->setHeader('Cache-Control', 'private', false);
		$this->app->setHeader('Content-type', $this->_mime . '; charset=UTF-8', true);

		$this->app->sendHeaders();

		// Get the Soap string from the buffer.
		$content = $this->getBuffer();

		echo (string) $content;
	}

	/**
	 * Returns the document name
	 *
	 * @return  string
	 *
	 * @since  1.4
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Sets Soap object to the document
	 *
	 * @param   Soap  $soap  Soap object
	 *
	 * @return  $this
	 *
	 * @since  1.4
	 */
	public function setApiObject($soap)
	{
		$this->soap = $soap;

		return $this;
	}

	/**
	 * Sets the document name
	 *
	 * @param   string  $name  Document name
	 *
	 * @return  $this
	 *
	 * @since   1.4
	 */
	public function setName($name = 'joomla')
	{
		$this->name = $name;

		return $this;
	}
}
