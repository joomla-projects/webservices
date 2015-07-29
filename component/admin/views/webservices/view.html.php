<?php
/**
 * @package     Redcore.Admin
 * @subpackage  Views
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('_JEXEC') or die;

/**
 * Webservices View
 *
 * @package     Redcore.Admin
 * @subpackage  Views
 * @since       1.2
 */
class WebservicesViewWebservices extends JViewLegacy
{
	/**
	 * @var  array
	 */
	public $items;

	/**
	 * @var  object
	 */
	public $state;

	/**
	 * @var  JPagination
	 */
	public $pagination;

	/**
	 * @var  JForm
	 */
	public $filterForm;

	/**
	 * @var array
	 */
	public $activeFilters;

	/**
	 * @var array
	 */
	public $stoolsOptions = array();

	/**
	 * @var  array
	 */
	public $xmlFiles;

	/**
	 * @var  int
	 */
	public $xmlFilesAvailable;

	/**
	 * Display method
	 *
	 * @param   string  $tpl  The template name
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$model = $this->getModel();

		$this->state         = $model->getState();
		$this->items         = $model->getItems();
		$this->pagination    = $model->getPagination();
		$this->filterForm    = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();

		$this->xmlFiles = $model->getXmlFiles();
		$this->xmlFilesAvailable = $model->xmlFilesAvailable;

		$this->return = base64_encode('index.php?option=com_webservices&view=webservices');

		// Check if option is enabled
		try
		{
			$container = (new Joomla\DI\Container)
				->registerServiceProvider(new Joomla\Webservices\Service\ConfigurationProvider);
		}
		catch (\Exception $e)
		{
			throw new RuntimeException('Help!', 500);
		}

		$config = $container->get("config");

		if ($config->get('enable_webservices', 0) == 0)
		{
			JFactory::getApplication()->enqueueMessage(
				JText::sprintf(
					'COM_WEBSERVICES_WEBSERVICES_PLUGIN_LABEL_WARNING',
					'<a href="index.php?option=com_plugins&view=plugins&filter_search=webservices">' . JText::_('COM_WEBSERVICES_CONFIGURE') . '</a>'
				),
				'error');
		}

		$this->addToolbar();

		// Load the submenu.
		WebservicesHelper::addSubmenu('webservices');
		$this->sidebar = JHtmlSidebar::render();

		parent::display($tpl);
	}

	/**
	 * Get the view title.
	 *
	 * @return  string  The view title.
	 */
	public function getTitle()
	{
		return JText::_('COM_WEBSERVICES_WEBSERVICES_MANAGE');
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function addToolbar()
	{
		$canDo		= JHelperContent::getActions('com_webservices');

		// Get the toolbar object instance
		$bar = JToolBar::getInstance('toolbar');

		// Prepare the toolbar.
		JToolbarHelper::title($this->getTitle(), 'folder');

		if ($canDo->get('core.create'))
		{
			JToolbarHelper::addNew('webservice.add');
		}

		if ($canDo->get('core.edit') || $canDo->get('core.edit.own'))
		{
			JToolbarHelper::editList('webservice.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('webservices.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('webservices.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}

		if (JFactory::getUser()->authorise('core.admin'))
		{
			JToolbarHelper::checkin('webservices.checkin');
		}

		if ($canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'webservices.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
	}
}
