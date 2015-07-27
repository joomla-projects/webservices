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
		$result = $this->taskHelloWorld()->run();

		return $result;
	}

	/**
	 * Downloads and prepares a Joomla CMS site for testing
	 *
	 * @return mixed
	 */
	public function prepareSiteForSystemTests()
	{
		// Get Joomla Clean Testing sites
		if (is_dir('tests/joomla-cms3'))
		{
			$this->taskDeleteDir('tests/joomla-cms3')->run();
		}

		$this->_exec('git clone -b staging --single-branch --depth 1 https://github.com/joomla/joomla-cms.git tests/joomla-cms3');
		$this->say('Joomla CMS site created at tests/joomla-cms3');
	}

	/**
	 * Executes Selenium System Tests in your machine
	 *
	 * @param   array  $options  Use -h to see available options
	 *
	 * @return mixed
	 */
	public function runTest($options = [
		'test'         => null,
		'suite'         => 'acceptance',
		'selenium_path' => null
	])
	{
		if (!$options['selenium_path'])
		{
			$this->getSelenium();
		}

		$this->getComposer();

		$this->taskComposerInstall()->run();

		$this->runSelenium($options['selenium_path']);

		$this->taskWaitForSeleniumStandaloneServer()
		     ->run()
		     ->stopOnFail();

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

		$this->killSelenium();
	}

	/**
	 * Function to Run tests in a Group
	 *
	 * @param   array  $options  Array of options
	 *
	 * @return void
	 */
	public function runTests($options = ['selenium_path' => null])
	{
		$this->_exec('vendor/bin/phing -f extension_packager.xml autopack');

		$this->prepareSiteForSystemTests();

		if (!$options['selenium_path'])
		{
			$this->getSelenium();
		}

		$this->_copyDir(__DIR__.'/.dist', __DIR__.'/tests/joomla-cms3/package');

		$this->getComposer();

		$this->taskComposerInstall()->run();

		$this->runSelenium($options['selenium_path']);

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
	 * Downloads Selenium Standalone Server
	 *
	 * @return void
	 */
	private function getSelenium()
	{
		if (!file_exists('selenium-server-standalone.jar'))
		{
			$this->say('Downloading Selenium Server, this may take a while.');
			$this->_exec('curl'
			             . ' -sS'
			             . ' --retry 3 --retry-delay 5'
			             . ' http://selenium-release.storage.googleapis.com/2.46/selenium-server-standalone-2.46.0.jar'
			             . ' > selenium-server-standalone.jar');
		}
	}

	/**
	 * Stops Selenium Standalone Server
	 *
	 * @return void
	 */
	private function killSelenium()
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
	 * Runs Selenium Standalone Server
	 *
	 * @param   string  $path  Optional path to selenium standalone server
	 *
	 * @return void
	 */
	private function runSelenium($path = null)
	{
		if (!$path)
		{
			$path = 'selenium-server-standalone.jar';
		}

		// Running Selenium server
		$this->_exec("java -jar $path >> selenium.log 2>&1 &");
	}
}