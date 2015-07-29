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
 * Field to load a list of database tables
 *
 * @package     Redcore
 * @subpackage  Field
 * @since       1.4
 */
class JFormFieldWebservicepaths extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 */
	public $type = 'Webservicepaths';

	/**
	 * A static cache.
	 *
	 * @var  array
	 */
	protected $cache = array();

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		$options = array();

		// Get the paths.
		$items = $this->getPaths();

		// Build the field options.
		if (!empty($items))
		{
			foreach ($items as $item)
			{
				$options[] = JHtml::_('select.option', $item->path, $item->path);
			}
		}

		return array_merge(parent::getOptions(), $options);
	}

	/**
	 * Method to get the list of paths.
	 *
	 * @return  array  An array of path names.
	 */
	protected function getPaths()
	{
		if (empty($this->cache))
		{
			// We should have already loaded composer in one of the models - but play safe in case
			if (!defined('JPATH_API'))
			{
				$applicationPath = realpath(JPATH_ROOT . '/../../webservices');
				$composerPath    = $applicationPath . '/vendor/autoload.php';

				define('JPATH_API', $applicationPath);
				require_once($composerPath);
			}

			try
			{
				$container = (new Joomla\DI\Container)
					->registerServiceProvider(new Joomla\Webservices\Service\ConfigurationProvider)
					->registerServiceProvider(new Joomla\Webservices\Service\DatabaseProvider);
			}
			catch (\Exception $e)
			{
				throw new RuntimeException('Help!', 500);
			}

			$db = $container->get('db');

			$query = $db->getQuery(true)
				->select('path')
				->from('#__webservices')
				->order('path')
				->group('path');

			$db->setQuery($query);

			$result = $db->loadObjectList();

			if (is_array($result))
			{
				$this->cache = $result;
			}
		}

		return $this->cache;
	}
}
