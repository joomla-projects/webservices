<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Api\Hal;

use Joomla\Webservices\Api\ApiBase;
use Joomla\Webservices\Renderer\Hal as Document;
use Joomla\Webservices\Webservices\Webservice;

use Joomla\DI\Container;

/**
 * Class to represent a HAL standard object.
 *
 * @since  1.2
 */
class Hal extends ApiBase
{
	/**
	 * Method to instantiate the file-based api call.
	 *
	 * @param   Container  $container  The DIC object
	 * @param   mixed      $options    Optional custom options to load. Registry or array format
	 *
	 * @throws  \Exception
	 * @since   1.4
	 */
	public function __construct(Container $container, $options = null)
	{
		parent::__construct($container);

		$this->webservice = new Webservice($container, $options);
		$this->webservice->authorizationCheck = 'joomla';

		// Set initial status code
		$this->setStatusCode(200);
	}

	/**
	 * Method to execute task.
	 *
	 * @return  $this
	 *
	 * @since   1.2
	 */
	public function execute()
	{
		$this->webservice->execute();

		return $this;
	}

	/**
	 * Method to send the application response to the client.  All headers will be sent prior to the main
	 * application output data.
	 *
	 * @return  void
	 *
	 * @since   1.2
	 */
	public function render()
	{
		// Set token to uri if used in that way
		$token = $this->webservice->options->get('accessToken', '');
		$client = $this->webservice->options->get('webserviceClient', '');
		$format = $this->webservice->options->get('format', 'json');

		if (!empty($token))
		{
			$this->webservice->setUriParams($this->app->get('webservices.oauth2_token_param_name', 'access_token'), $token);
		}

		if ($client == 'administrator')
		{
			$this->webservice->setUriParams('webserviceClient', $client);
		}

		$this->webservice->setUriParams('api', 'Hal');

		if ($format == 'doc')
		{
			// This is already in HTML format
			$this->app->setBody($this->webservice->documentation);
		}
		else
		{
			$documentOptions = array(
				'absoluteHrefs' => $this->webservice->options->get('absoluteHrefs', false),
				'documentFormat' => $format,
				'uriParams' => $this->webservice->uriParams,
			);
			$halDocument = new Document($this->getContainer(), $documentOptions);

			$body = $this->webservice->getBody();

			// Push results into the document.
			$this->app->setBody(
				$halDocument
					->setHal($this->webservice)
					->setBuffer($body)
					->render(false)
			);
		}
	}
}
