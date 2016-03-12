<?php
/**
 * @package     Redcore
 * @subpackage  Document
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Renderer\Application;

use Joomla\Webservices\Renderer\Renderer;
use Joomla\Webservices\Api\Soap\Soap as Api;

/**
 * ApiDocumentSoap class, provides an easy interface to parse and display XML output
 *
 * @package     Redcore
 * @subpackage  Document
 * @since       1.4
 */
class Soapxml extends Renderer
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
	 *
	 * @var    boolean
	 * @since  __DELPOY_VERSION__
	 */
	protected $absoluteHrefs = false;

	/**
	 * Document format (xml or json)
	 *
	 * @var    string
	 * @since  __DEPLOY__VERSION__
	 */
	protected $documentFormat;

	/**
	 * Soap object
	 *
	 * @var    Api
	 * @since  1.4
	 */
	public $soap = null;

	/**
	 * Class constructor
	 *
	 * @param   object  $application  The application.
	 * @param   array   $options      Associative array of options.
	 * @param   string  $mimeType     Document type.
	 *
	 * @since  1.4
	 */
	public function __construct($application, $options = array(), $mimeType = 'soap+xml')
	{
		parent::__construct($application, $options);

		$this->documentFormat = $options['documentFormat'];

		// Sanity check - fall back to XML format
		if (!in_array($this->documentFormat, array('xml', 'json')))
		{
			$this->documentFormat = 'xml';
		}

		// Set default mime type.
		$this->setMimeEncoding('application/' . $mimeType, false);

		// Set document type.
		$this->setType('xml');

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
		parent::render($cache, $params);
		$runtime = microtime(true) - $this->app->startTime;

		$this->app->setHeader('Status', $this->soap->statusCode . ' ' . $this->soap->statusText, true);
		$this->app->setHeader('Server', '', true);
		$this->app->setHeader('X-Runtime', $runtime, true);
		$this->app->setHeader('Access-Control-Allow-Origin', '*', true);
		$this->app->setHeader('Pragma', 'public', true);
		$this->app->setHeader('Expires', '0', true);
		$this->app->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
		$this->app->setHeader('Cache-Control', 'private', false);
		$this->app->setHeader('Content-type', $this->getMimeEncoding() . '; charset=' . $this->getCharset(), true);

		// $this->app->sendHeaders();

		// Get the Soap string from the buffer.
		$content = $this->getBuffer();

		return (string) $content;
	}

	/**
	 * Sets Soap object to the document
	 *
	 * @param   Api  $soap  Soap object
	 *
	 * @return  $this
	 *
	 * @since  1.4
	 */
	public function setApiObject(Api $soap)
	{
		$this->soap = $soap;

		return $this;
	}
}
