<?php
/**
 * @package     Redcore
 * @subpackage  Field
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

/**
 * Field to load a list of available webservice scopes
 *
 * @package     Redcore
 * @subpackage  Field
 * @since       1.0
 */
class JFormFieldWebservicescopes extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0
	 */
	public $type = 'Webservicescopes';

	/**
	 * Cached array of the items.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected static $cache = array();

	/**
	 * Method to get the options to populate to populate list
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   1.0
	 */
	protected function getOptions()
	{
		// Accepted modifiers
		$hash = md5($this->element);

		try
		{
			$container = (new Joomla\DI\Container)
				->registerServiceProvider(new Joomla\Webservices\Service\ConfigurationProvider)
				->registerServiceProvider(new Joomla\Language\Service\LanguageFactoryProvider)
				->registerServiceProvider(new Joomla\Webservices\Service\DatabaseProvider);
		}
		catch (\Exception $e)
		{
			throw new RuntimeException(JText::sprintf('COM_WEBSERVICES_WEBSERVICE_ERROR_DATABASE_CONNECTION', $e->getMessage()), 500, $e);
		}

		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = $container->get('db');

		/** @var \Joomla\Language\LanguageFactory $languageFactory */
		$languageFactory = $container->get('Joomla\\Language\\LanguageFactory');
		$languageFactory->getLanguage()->load('lib_webservices');
		$text = $languageFactory->getText();

		if (!isset(static::$cache[$hash]))
		{
			static::$cache[$hash] = parent::getOptions();

			$options = \Joomla\Webservices\Webservices\ConfigurationHelper::getWebserviceScopes($text, array(), $db);

			static::$cache[$hash] = array_merge(static::$cache[$hash], $options);
		}

		return static::$cache[$hash];
	}

	/**
	 * Method to get the field input markup for OAuth2 Scope Lists.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		return JLayoutHelper::render(
			'webservice.scopes',
			array(
				'view' => $this,
				'options' => array (
					'scopes' => $this->getOptions(),
					'id' => $this->id,
					'name' => $this->name,
					'label' => $this->label,
					'value' => $this->value,
				)
			)
		);
	}
}
