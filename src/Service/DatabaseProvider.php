<?php
/**
 * Database Service Provider for Joomla Webservices.
 *
 * @package    Webservices
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Database\DatabaseDriver;

class DatabaseProvider implements ServiceProviderInterface
{
	public function register(Container $container)
	{
		$container->alias("db", "Joomla\\Database\\DatabaseDriver")
			->share(
				"Joomla\\Database\\DatabaseDriver",
				function () use ($container)
				{
					$config = $container->get("config");

					return DatabaseDriver::getInstance((array) $config["database"]);
				},
				true
			);
	}
}
