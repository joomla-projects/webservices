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
 * OAuth Client View
 *
 * @package     Redcore.Admin
 * @subpackage  Views
 * @since       1.2
 */
class WebservicesViewWebservice extends JViewLegacy
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
	 * Display method
	 *
	 * @param   string  $tpl  The template name
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$model = $this->getModel('webservice');
		$this->form	= $this->get('Form');
		$this->item	= $this->get('Item');
		$this->state = $this->get('State');
		$this->fields = $model->fields;
		$this->resources = $model->resources;
		$this->formData = $model->formData;

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

		if ($config->get('webservices.enable_webservices', 0) == 0)
		{
			JFactory::getApplication()->enqueueMessage(
				JText::sprintf(
					'COM_WEBSERVICES_WEBSERVICES_PLUGIN_LABEL_WARNING',
					'<a href="index.php?option=com_plugins&view=plugins&filter_search=webservices">' . JText::_('COM_WEBSERVICES_CONFIGURE') . '</a>'
				),
				'error');
		}

		$this->canDo = JHelperContent::getActions('com_webservices');
		$input = JFactory::getApplication()->input;
		$input->set('hidemainmenu', true);

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Get the view title.
	 *
	 * @return string  The view title.
	 */
	public function getTitle()
	{
		return JText::_('COM_WEBSERVICES_WEBSERVICE_TITLE');
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
		$user = JFactory::getUser();
		$isNew = ($this->item->id == 0);

		// Prepare the toolbar.
		JToolbarHelper::title(
			$this->getTitle(),
			'folder ' . ($isNew ? 'add' : 'edit')
		);

		// For new records, check the create permission.
		if ($user->authorise('core.admin', 'com_webservices'))
		{
			JToolbarHelper::apply('webservice.apply');
			JToolbarHelper::save('webservice.save');
			JToolbarHelper::save2new('webservice.save2new');
		}

		if (empty($this->item->id))
		{
			JToolbarHelper::cancel('webservice.cancel');
		}
		else
		{
			JToolbarHelper::cancel('webservice.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolbarHelper::divider();
	}
}
