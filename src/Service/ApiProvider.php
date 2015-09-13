<?php
/**
 * Renderer Service Provider for Joomla Webservices.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;

class ApiProvider implements ServiceProviderInterface
{
	/**
	 * Options to be passed to the API object.
	 *
	 * @var    Registry
	 * @since  __DEPLOY_VERSION__
	 */
	private $options = null;

	/**
	 * Provide an API object.
	 * 
	 * @param   Registry   $options      Array of options to be passed to the API object.
	 */
	public function __construct(Registry $options)
	{
		$this->options = $options;
	}

	/**
	 * Return an API object.
	 * 
	 * @return  Object with ApiInterface.
	 */
	public function register(Container $container)
	{
		$options = $this->options;

		$container->share('api',
			function () use ($container, $options)
			{
				// Get the renderer given the content type.
				$renderer = $container->get('renderer');
		
				// Get the interaction style from the renderer.
				$apiStyle = ucfirst($renderer->getInteractionStyle());
		
				// Construct the class name.
				$apiClass = 'Joomla\\Webservices\\Api\\' . $apiStyle . '\\' . $apiStyle;
		
				if (!class_exists($apiClass))
				{
					throw new \RuntimeException($container->get('text')->sprintf('LIB_WEBSERVICES_API_UNABLE_TO_LOAD_API', $apiStyle));
				}
		
				try
				{
					/** @var \Joomla\Webservices\Api\ApiBase $api */
					$api = new $apiClass($container, $options);
				}
				catch (\RuntimeException $e)
				{
					throw new \RuntimeException($container->get('text')->sprintf('LIB_WEBSERVICES_API_UNABLE_TO_CONNECT_TO_API', $e->getMessage()));
				}
		
				return $api;
			},
			true
		);
	}
}
