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

/**
 * Renderer service provider.
 *
 * @since  __DEPLOY_VERSION__
 */
class RendererProvider implements ServiceProviderInterface
{
	/**
	 * The Application object.
	 */
	private $application = null;

	/**
	 * Content type.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 * @see    http://tools.ietf.org/html/rfc6838
	 */
	private $contentType = '';

	/**
	 * Options to be passed to the renderer.
	 *
	 * @var    Registry
	 * @since  __DEPLOY_VERSION__
	 */
	private $options = null;

	/**
	 * Provide a renderer object.
	 *
	 * A unique renderer is associated with a given content type.  To add your own renderer
	 * class, given a content type of "type/subtype", the class name should be "Subtype" in
	 * the namespace "Type".  See the existing code for examples.
	 *
	 * @param   object    $application  The Application object.
	 * @param   string    $contentType  Content type (eg. "application/hal+json").
	 * @param   Registry  $options      Array of options to be passed to the renderer.
	 */
	public function __construct($application, $contentType, Registry $options)
	{
		$this->application = $application;
		$this->contentType = $contentType;
		$this->options = $options;
	}

	/**
	 * Return a Renderer for the content type.
	 *
	 * @param   Container  $container  Dependency injection container.
	 *
	 * @return  Renderer object.
	 */
	public function register(Container $container)
	{
		$application = $this->application;
		$contentType = $this->contentType;
		$options = $this->options;

		$container->share('renderer',
			function () use ($container, $application, $contentType, $options)
			{
				// Split type and subtype strings from content type.
				$parts = explode('/', strtolower($contentType));
				$typeName = ucfirst($parts[0]);
				$subtypeName = ucfirst(str_replace(['+', '-', '.'], '', $parts[1]));

				// Construct the class name.
				$rendererClass = 'Joomla\\Webservices\\Renderer\\' . $typeName . '\\' . $subtypeName;

				if (!class_exists($rendererClass))
				{
					throw new \RuntimeException($container->get('text')->sprintf('LIB_WEBSERVICES_API_UNABLE_TO_LOAD_RENDERER', $rendererClass));
				}

				try
				{
					$renderer = new $rendererClass($application, $options);
				}
				catch (\RuntimeException $e)
				{
					throw new \RuntimeException($container->get('text')->sprintf('LIB_WEBSERVICES_API_UNABLE_TO_INSTANTIATE_RENDERER', $e->getMessage()));
				}

				return $renderer;
			},
			true
		);
	}
}
