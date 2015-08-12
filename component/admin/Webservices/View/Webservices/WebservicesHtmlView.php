<?php
/**
 * Webservices component for Joomla! CMS
 *
 * @copyright  Copyright (C) 2004 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Webservices\View\Webservices;

use Webservices\Helper;
use Webservices\View\DefaultHtmlView;

/**
 * View class for a list of pull requests.
 *
 * @since  2.0
 */
class WebservicesHtmlView extends DefaultHtmlView
{
	/**
	 * Array containing environment errors
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $envErrors = array();

	/**
	 * Array of open pull requests
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $items;

	/**
	 * The model object - redeclared for proper type hinting.
	 *
	 * @var    \Webservices\Model\WebservicesModel
	 * @since  2.0
	 */
	protected $model;

	/**
	 * State object
	 *
	 * @var    \Joomla\Registry\Registry
	 * @since  2.0
	 */
	protected $state;

	/**
	 * Pagination object
	 *
	 * @var    \JPagination
	 * @since  2.0
	 */
	protected $pagination;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   2.0
	 */
	public function render()
	{
		if (!extension_loaded('openssl'))
		{
			$this->envErrors[] = \JText::_('COM_WEBSERVICES_REQUIREMENT_OPENSSL');
		}

		if (!in_array('https', stream_get_wrappers()))
		{
			$this->envErrors[] = \JText::_('COM_WEBSERVICES_REQUIREMENT_HTTPS');
		}
/*
		// Only process the data if there are no environment errors
		if (!count($this->envErrors))
		{
			$this->state      = $this->model->getState();
			$this->items      = $this->model->getItems();
			$this->pagination = $this->model->getPagination();
		}

		$this->addToolbar();
*/

		$model = $this->model;

		$this->state         = $model->getState();
		$this->items         = $model->getItems();
		$this->pagination    = $model->getPagination();
		$this->filterForm    = $model->getFilterForm();
		//$this->activeFilters = $model->getActiveFilters();

		$this->xmlFiles = $model->getXmlFiles();
		$this->xmlFilesAvailable = $model->xmlFilesAvailable;

		$this->return = base64_encode('index.php?option=com_webservices&view=webservices');

		// Check if option is enabled
		try
		{
			$container = (new \Joomla\DI\Container)
				->registerServiceProvider(new \Joomla\Webservices\Service\ConfigurationProvider);
		}
		catch (\Exception $e)
		{
			throw new RuntimeException(\JText::sprintf('COM_WEBSERVICES_WEBSERVICE_ERROR_CONFIGURATION', $e->getMessage()), 500, $e);
		}

		$config = $container->get("config");

		if ($config->get('webservices.enable_webservices', 0) == 0)
		{
			\JFactory::getApplication()->enqueueMessage(
				\JText::sprintf(
					'COM_WEBSERVICES_WEBSERVICES_PLUGIN_LABEL_WARNING',
					'<a href="index.php?option=com_plugins&view=plugins&filter_search=webservices">' . \JText::_('COM_WEBSERVICES_CONFIGURE') . '</a>'
				),
				'error');
		}

		$this->addToolbar();

		// Load the submenu.
		Helper::addSubmenu('webservices');
		//$this->sidebar = \JHtmlSidebar::render();

		return parent::render();
	}

	/**
	 * Get the view title.
	 *
	 * @return  string  The view title.
	 */
	public function getTitle()
	{
		return \JText::_('COM_WEBSERVICES_WEBSERVICES_MANAGE');
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
		$canDo		= \JHelperContent::getActions('com_webservices');

		// Get the toolbar object instance
		$bar = \JToolBar::getInstance('toolbar');

		// Prepare the toolbar.
		\JToolbarHelper::title($this->getTitle(), 'folder');

		if ($canDo->get('core.create'))
		{
			\JToolbarHelper::addNew('webservice.add');
		}

		if ($canDo->get('core.edit') || $canDo->get('core.edit.own'))
		{
			\JToolbarHelper::editList('webservice.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			\JToolbarHelper::publish('webservices.publish', 'JTOOLBAR_PUBLISH', true);
			\JToolbarHelper::unpublish('webservices.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}

		if (\JFactory::getUser()->authorise('core.admin'))
		{
			\JToolbarHelper::checkin('webservices.checkin');
		}

		if ($canDo->get('core.delete'))
		{
			\JToolbarHelper::deleteList('', 'webservices.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   2.0
	 */
	protected function getSortFields()
	{
		return array(
			'w.name'   => \JText::_('JGLOBAL_TITLE'),
			'w.id' => \JText::_('COM_WEBSERVICES_ID'),
			'applied'   => \JText::_('JSTATUS')
		);
	}
}
