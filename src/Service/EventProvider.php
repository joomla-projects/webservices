<?php
/**
 * Event Service Provider for Joomla Webservices.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\Dispatcher;


class EventProvider implements ServiceProviderInterface
{
	public function register(Container $container)
	{
		$container->share(
			"Joomla\\Event\\Dispatcher",
			function ()
			{
				return new Dispatcher;
			},
			true
		);
	}
}
