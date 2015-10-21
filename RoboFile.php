<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * Download robo.phar from http://robo.li/robo.phar and type in the root of the repo: $ php robo.phar
 * Or do: $ composer update, and afterwards you will be able to execute robo like $ php vendor/bin/robo
 *
 * @see  http://robo.li/
 */
require_once 'vendor/autoload.php';

/**
 * Class RoboFile
 *
 * @since  1.5
 */
class RoboFile extends \Robo\Tasks
{
	// Load tasks from composer, see composer.json
	use \joomla_projects\robo\loadTasks;

	/**
	 * Current RoboFile version
	 */
	private $version = '1.4';

	/**
	 * Hello World example task.
	 *
	 * @see  https://github.com/redCOMPONENT-COM/robo/blob/master/src/HelloWorld.php
	 * @link https://packagist.org/packages/redcomponent/robo
	 *
	 * @return object Result
	 */
	public function sayHelloWorld()
	{
		$this->say('HelloWorld');
	}

	/**
	 * Downloads and prepares a Joomla CMS site for testing
	 *
	 * @param   int  $use_htaccess  (1/0) Rename and enable embedded Joomla .htaccess file
	 *
	 * @return mixed
	 */
	public function prepareSiteForSystemTests($use_htaccess = 0)
	{
		// Get Joomla Clean Testing sites
		if (is_dir('tests/joomla-cms3'))
		{
			$this->taskDeleteDir('tests/joomla-cms3')->run();
		}

		$version = 'staging';

		/*
		 * When joomla Staging branch has a bug you can uncomment the following line as a tmp fix for the tests layer.
		 * Use as $version value the latest tagged stable version at: https://github.com/joomla/joomla-cms/releases
		 */
		// $version = '3.4.4';

		$this->_exec("git clone -b $version --single-branch --depth 1 https://github.com/joomla/joomla-cms.git tests/joomla-cms3");

		$this->say("Joomla CMS ($version) site created at tests/joomla-cms3");

		// Optionally uses Joomla default htaccess file
		if ($use_htaccess == 1)
		{
			$this->_copy('tests/joomla-cms3/htaccess.txt', 'tests/joomla-cms3/.htaccess');
			$this->_exec('sed -e "s,# RewriteBase /,RewriteBase /tests/joomla-cms3/,g" --in-place tests/joomla-cms3/.htaccess');
		}
	}

	/**
	 * Executes Selenium System Tests in your machine
	 *
	 * @param   array  $options  Use -h to see available options
	 *
	 * @return mixed
	 */
	public function runTest($options = [
		'test'	    => null,
		'suite'	    => 'acceptance'
	])
	{
		$this->getComposer();

		$this->taskComposerInstall()->run();

		if (isset($options['suite']) && 'api' === $options['suite'])
		{
			// Do not launch selenium when running API tests
		}
		else
		{
			$this->runSelenium();

			$this->taskWaitForSeleniumStandaloneServer()
				->run()
				->stopOnFail();
		}

		// Make sure to Run the Build Command to Generate AcceptanceTester
		$this->_exec("vendor/bin/codecept build");

		if (!$options['test'])
		{
			$this->say('Available tests in the system:');

			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator(
					'tests/' . $options['suite'],
					RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST);

			$tests = array();

			$iterator->rewind();
			$i = 1;

			while ($iterator->valid())
			{
				if (strripos($iterator->getSubPathName(), 'cept.php')
					|| strripos($iterator->getSubPathName(), 'cest.php'))
				{
					$this->say('[' . $i . '] ' . $iterator->getSubPathName());
					$tests[$i] = $iterator->getSubPathName();
					$i++;
				}

				$iterator->next();
			}

			$this->say('');
			$testNumber     = $this->ask('Type the number of the test  in the list that you want to run...');
			$options['test'] = $tests[$testNumber];
		}

		$pathToTestFile = 'tests/' . $options['suite'] . '/' . $options['test'];

		$this->taskCodecept()
			->test($pathToTestFile)
			->arg('--steps')
			->arg('--debug')
			->run()
			->stopOnFail();

		if (!'api' == $options['suite'])
		{
			$this->killSelenium();
		}
	}

	/**
	 * Function to Run tests in a Group
	 *
	 * @param   int  $use_htaccess  (1/0) Rename and enable embedded Joomla .htaccess file
	 *
	 * @return void
	 */
	public function runTests($use_htaccess = 0)
	{
		$this->prepareSiteForSystemTests($use_htaccess);

		$this->_copyDir(__DIR__.'/vendor', __DIR__.'/tests/joomla-cms3/vendor/');
		$this->_copyDir(__DIR__.'/www', __DIR__.'/tests/joomla-cms3/www/');
		$this->_copyDir(__DIR__.'/src', __DIR__.'/tests/joomla-cms3/src/');
		if (file_exists('./config.json'))
		{
			$this->_copy(__DIR__.'/config.json', __DIR__.'/tests/joomla-cms3/config.json');
		}
		else
		{
			$this->_copy(__DIR__.'/config.dist.json', __DIR__.'/tests/joomla-cms3/config.json');
		}

		$this->getComposer();

		$this->taskComposerInstall()->run();

		$this->runSelenium();

		$this->taskWaitForSeleniumStandaloneServer()
			->run()
			->stopOnFail();

		// Make sure to Run the Build Command to Generate AcceptanceTester
		$this->_exec("vendor/bin/codecept build");

		$this->taskCodecept()
		     ->arg('--steps')
		     ->arg('--debug')
		     ->arg('--fail-fast')
		     ->arg('tests/acceptance/install/')
		     ->run()
		     ->stopOnFail();

		$this->taskCodecept()
		     ->arg('--steps')
		     ->arg('--debug')
		     ->arg('--fail-fast')
		     ->arg('tests/acceptance/administrator/')
		     ->run()
		     ->stopOnFail();

		$this->taskCodecept()
		     ->arg('--steps')
		     ->arg('--debug')
		     ->arg('--fail-fast')
		     ->arg('api')
		     ->run()
		     ->stopOnFail();

		$this->taskCodecept()
		     ->arg('--steps')
		     ->arg('--debug')
		     ->arg('--fail-fast')
		     ->arg('tests/acceptance/uninstall/')
		     ->run()
		     ->stopOnFail();

		$this->killSelenium();
	}

	/**
	 * Stops Selenium Standalone Server
	 *
	 * @return void
	 */
	public function killSelenium()
	{
		$this->_exec('curl http://localhost:4444/selenium-server/driver/?cmd=shutDownSeleniumServer');
	}

	/**
	 * Downloads Composer
	 *
	 * @return void
	 */
	private function getComposer()
	{
		// Make sure we have Composer
		if (!file_exists('./composer.phar'))
		{
			$this->_exec('curl --retry 3 --retry-delay 5 -sS https://getcomposer.org/installer | php');
		}
	}

	/**
	 * Runs Selenium Standalone Server.
	 *
	 * @return void
	 */
	public function runSelenium()
	{
		$this->_exec("vendor/bin/selenium-server-standalone >> selenium.log 2>&1 &");
	}


	public function sendScreenshotToCloudinary($cloudName, $apiKey, $apiSecret)
	{
		$error = false;

		// Loop throught Codeception snapshots
		if ($handler = opendir('tests/_output'))
		{
			while (false !== ($errorSnapshot = readdir($handler)))
			{
				// Avoid sending system files or html files
				if (!('png' === pathinfo($errorSnapshot, PATHINFO_EXTENSION)))
				{
					continue;
				}

				$error = true;
				$this->say("Uploading screenshots: $errorSnapshot");

				Cloudinary::config(
					array(
						'cloud_name' => $cloudName,
						'api_key'    => $apiKey,
						'api_secret' => $apiSecret
					)
				);

				$result = \Cloudinary\Uploader::upload(realpath(dirname(__FILE__) . '/tests/_output/' . $errorSnapshot));
				$this->say($result);
				$this->say($errorSnapshot . 'Image sent');
			}
		}

		if ($error)
		{
			// @todo
		}
	}
}
