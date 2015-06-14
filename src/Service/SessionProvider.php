<?php
/**
 * Session Service Provider for Joomla Webservices.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Session\Session;
use Joomla\Session\Handler\FilesystemHandler;
use Joomla\Session\Storage\NativeStorage;


class SessionProvider implements ServiceProviderInterface
{
	public function register(Container $container)
	{
		$container->alias("session", "Joomla\\Session\\Session")
			->share(
				"Joomla\\Session\\Session",
				function ()
				{
					$storage = new NativeStorage(new FilesystemHandler);
					$session = new Session($storage);

					return $session;
				},
				true
			);
	}
}
