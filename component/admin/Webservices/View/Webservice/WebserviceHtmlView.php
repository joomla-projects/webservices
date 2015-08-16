<?php
/**
 * @package     Redcore.Admin
 * @subpackage  Views
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

namespace Webservices\View\Webservice;

use Webservices\Helper;
use Webservices\View\DefaultHtmlView;

/**
 * View class for a form of webservices.
 *
 * @since  1.0
 */
class WebserviceHtmlView extends DefaultHtmlView
{
	/**
	 * @var JForm
	 */
	public $form;

	/**
	 * @var object
	 */
	public $item;

	/**
	 * @var array
	 */
	public $fields;

	/**
	 * @var array
	 */
	public $resources;

	/**
	 * @var array
	 */
	public $formData;

	/**
	 * @var JForm
	 */
	public $paths_arr;

	/**
	 * Layout name
	 *
	 * @var    string
	 */
	protected $_layout = 'default';

	/**
	 * Layout extension
	 *
	 * @var    string
	 */
	protected $_layoutExt = 'php';

	/**
	 * Layout template
	 *
	 * @var    string
	 */
	protected $_layoutTemplate = '_';

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$model = $this->model;

		$this->form	= $model->getForm();
		$this->state         = $model->getState();

		// Check if option is enabled
		try
		{
			$container = (new \Joomla\DI\Container)
				->registerServiceProvider(new \Joomla\Webservices\Service\ConfigurationProvider);
		}
		catch (\Exception $e)
		{
			throw new RuntimeException(JText::sprintf('COM_WEBSERVICES_WEBSERVICE_ERROR_CONFIGURATION', $e->getMessage()), 500, $e);
		}

		$config = $container->get("config");

		if ($config->get('webservices.enable_webservices', 0) == 0)
		{
			\JFactory::getApplication()->enqueueMessage(
				\JText::sprintf(
					'COM_WEBSERVICES_WEBSERVICES_PLUGIN_LABEL_WARNING',
					'<a href="index.php?option=com_plugins&view=plugins&filter_search=webservices">' . JText::_('COM_WEBSERVICES_CONFIGURE') . '</a>'
				),
				'error');
		}

		$this->canDo = \JHelperContent::getActions('com_webservices');
		$input = \JFactory::getApplication()->input;
		$input->set('hidemainmenu', true);

		$this->addToolbar();

		return parent::render();
	}

	/**
	 * Get the view title.
	 *
	 * @return string  The view title.
	 */
	public function getTitle()
	{
		return \JText::_('COM_WEBSERVICES_WEBSERVICE_TITLE');
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
		$user = \JFactory::getUser();
		$isNew = isset($this->item->id) ? ($this->item->id == 0) : true;

		// Prepare the toolbar.
		\JToolbarHelper::title(
			$this->getTitle(),
			'folder ' . ($isNew ? 'add' : 'edit')
		);

		// For new records, check the create permission.
		if ($user->authorise('core.admin', 'com_webservices'))
		{
			\JToolbarHelper::apply('webservice.apply');
			\JToolbarHelper::save('webservice.save');
			\JToolbarHelper::save2new('webservice.save2new');
		}

		if (empty($this->item->id))
		{
			\JToolbarHelper::cancel('webservice.cancel');
		}
		else
		{
			\JToolbarHelper::cancel('webservice.cancel', 'JTOOLBAR_CLOSE');
		}

		\JToolbarHelper::divider();
	}
}
