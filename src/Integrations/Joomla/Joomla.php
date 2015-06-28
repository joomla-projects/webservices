<?php
/**
 * Created by PhpStorm.
 * User: georg_000
 * Date: 28/06/2015
 * Time: 21:36
 */

namespace Joomla\Webservices\Integrations\Joomla;

use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\Container;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Registry\Registry;
use Joomla\Webservices\Integrations\Joomla\Authorisation\Authorise;
use Joomla\Webservices\Integrations\AuthorisationInterface;
use Joomla\Webservices\Webservices\Webservice;
use Joomla\Webservices\Xml\XmlHelper;

/**
 * Integration class for Joomla! CMS 3.x
 *
 * @package Joomla\Webservices\Integrations\Joomla
 */
class Joomla implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * The webservice object
	 *
	 * @var  Webservice
	 */
	private $webservice;

	public function __construct(Container $container, Webservice $webservice)
	{
		$this->setContainer($container);
		$this->webservice = $webservice;

		/**
		 * Constant that is checked in included files to prevent direct access.
		 * define() is used in the installation folder rather than "const" to not error for PHP 5.2 and lower
		 */
		define('_JEXEC', 1);

		require_once JPATH_BASE . '/includes/defines.php';
		require_once JPATH_BASE . '/includes/framework.php';

		/** @var \Joomla\Session\Session $session */
		// Don't let the session load twice!
		$session =  $container->get("session");
		$data = array(
			'session' => false,
			'session_name' => $session->getName()
		);

		$config = new Registry($data);
		$app = new \JApplicationSite(null, $config);
		\JFactory::$application = $app;
	}

	/**
	 * Gets a Joomla authorisation object
	 *
	 * @param   mixed  $id  Unique identifier for the user - either an id or a username
	 *
	 * @return  AuthorisationInterface
	 */
	public function getAuthorisation($id)
	{
		return new Authorise($id);
	}

	/**
	 * Load model class for data manipulation
	 *
	 * @param   string             $elementName    Element name
	 * @param   \SimpleXMLElement  $configuration  Configuration for current action
	 *
	 * @return  mixed  Model class for data manipulation
	 *
	 * @since   1.2
	 */
	public function loadModel($elementName, $configuration)
	{
		$isAdmin = XmlHelper::isAttributeTrue($configuration, 'isAdminClass');
		$this->addModelIncludePaths($isAdmin, $this->webservice->optionName);
		$this->loadExtensionLanguage($this->webservice->optionName, $isAdmin ? JPATH_ADMINISTRATOR : JPATH_SITE);
		$this->loadExtensionLibrary($this->webservice->optionName);
		$dataMode = strtolower(XmlHelper::attributeToString($configuration, 'dataMode', 'model'));

		if ($dataMode == 'helper')
		{
			return $this->getHelperObject();
		}

		if ($dataMode == 'table')
		{
			return $this->getDynamicModelObject($configuration);
		}

		if (!empty($configuration['modelClassName']))
		{
			$modelClass = (string) $configuration['modelClassName'];

			if (!empty($configuration['modelClassPath']))
			{
				require_once JPATH_SITE . '/' . $configuration['modelClassPath'];

				if (class_exists($modelClass))
				{
					return new $modelClass;
				}
			}
			else
			{
				$componentName = ucfirst(strtolower(substr($this->optionName, 4)));
				$prefix = $componentName . 'Model';

				$model = \JModelAdmin::getInstance($modelClass, $prefix);

				if ($model)
				{
					return $model;
				}
			}
		}

		if (!empty($this->viewName))
		{
			$elementName = $this->viewName;
		}

		$componentName = ucfirst(strtolower(substr($this->webservice->optionName, 4)));
		$prefix = $componentName . 'Model';

		return \JModelAdmin::getInstance($elementName, $prefix);
	}

	/**
	 * Add include paths for model class
	 *
	 * @param   boolean  $isAdmin     Is client admin or site
	 * @param   string   $optionName  Option name
	 *
	 * @return  void
	 *
	 * @since   1.3
	 */
	public function addModelIncludePaths($isAdmin, $optionName)
	{
		if ($isAdmin)
		{
			$this->loadExtensionLanguage($optionName, JPATH_ADMINISTRATOR);
			$path = JPATH_ADMINISTRATOR . '/components/' . $optionName;
			\JModelLegacy::addIncludePath($path . '/models');
			\JTable::addIncludePath($path . '/tables');
			\JForm::addFormPath($path . '/models/forms');
			\JForm::addFieldPath($path . '/models/fields');
		}
		else
		{
			$this->loadExtensionLanguage($optionName);
			$path = JPATH_SITE . '/components/' . $optionName;
			\JModelLegacy::addIncludePath($path . '/models');
			\JTable::addIncludePath($path . '/tables');
			\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/' . $optionName . '/tables');
			\JForm::addFormPath($path . '/models/forms');
			\JForm::addFieldPath($path . '/models/fields');
		}

		if (!defined('JPATH_COMPONENT'))
		{
			define('JPATH_COMPONENT', $path);
		}
	}

	/**
	 * Include library classes
	 *
	 * @param   string  $element  Option name
	 *
	 * @return  void
	 *
	 * @since   1.4
	 */
	private function loadExtensionLibrary($element)
	{
		$element = strpos($element, 'com_') === 0 ? substr($element, 4) : $element;
		\JLoader::import(strtolower($element) . '.library');
	}

	/**
	 * Load extension language file.
	 *
	 * @param   string  $option  Option name
	 * @param   string  $path    Path to language file
	 *
	 * @return  object
	 */
	private function loadExtensionLanguage($option, $path = JPATH_SITE)
	{
		/** @var \Joomla\Language\Language $lang */
		$lang = $this->getContainer()->get('Joomla\\Language\\LanguageFactory')->getLanguage();

		// Load common and local language files.
		$lang->load($option, $path, null, false, false)
		|| $lang->load($option, $path . "/components/$option", null, false, false)
		|| $lang->load($option, $path, $lang->getDefault(), false, false)
		|| $lang->load($option, $path . "/components/$option", $lang->getDefault(), false, false);

		return $this;
	}

	public function getStrategies()
	{
		return array(
			'joomla' => new \Joomla\Webservices\Integrations\Joomla\Strategy\Joomla
		);
	}
}