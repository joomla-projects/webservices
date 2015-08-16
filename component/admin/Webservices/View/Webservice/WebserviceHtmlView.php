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
class WebserviceHtmlView extends \JViewHtml
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
	 * Load a template file -- first look in the templates folder for an override
	 *
	 * @param   string  $tpl  The name of the template source file; automatically searches the template paths and compiles as needed.
	 *
	 * @return  string  The output of the the template script.
	 *
	 * @since   12.2
	 * @throws  Exception
	 */
	public function loadTemplate($tpl = null)
	{
		// Clear prior output
		$this->_output = null;

		$template = \JFactory::getApplication()->getTemplate();
		$layout = $this->getLayout();
		$layoutTemplate = $this->getLayoutTemplate();

		// Create the template file name based on the layout
		$file = isset($tpl) ? $layout . '_' . $tpl : $layout;

		// Clean the file name
		$file = preg_replace('/[^A-Z0-9_\.-]/i', '', $file);
		$tpl = isset($tpl) ? preg_replace('/[^A-Z0-9_\.-]/i', '', $tpl) : $tpl;

		// Load the language file for the template
		$lang = \JFactory::getLanguage();
		$lang->load('tpl_' . $template, JPATH_BASE, null, false, true)
			|| $lang->load('tpl_' . $template, JPATH_THEMES . "/$template", null, false, true);

		// Change the template folder if alternative layout is in different template
		if (isset($layoutTemplate) && $layoutTemplate != '_' && $layoutTemplate != $template)
		{
			$this->_path['template'] = str_replace($template, $layoutTemplate, $this->_path['template']);
		}

		// Load the template script
		jimport('joomla.filesystem.path');
		$filetofind = $this->_createFileName('template', array('name' => $file));

		if (empty($this->paths_arr))
		{
			$paths = $this->paths;

			$this->paths_arr = array();
			while($paths->valid()){

				$this->paths_arr[] = $paths->current();

			  $paths->next();
			}
		}

		$this->_template = \JPath::find($this->paths_arr, $filetofind);

		// If alternate layout can't be found, fall back to default layout
		if ($this->_template == false)
		{
			$filetofind = $this->_createFileName('', array('name' => 'default' . (isset($tpl) ? '_' . $tpl : $tpl)));

			$this->_template = \JPath::find($this->paths_arr, $filetofind);
		}

		if ($this->_template != false)
		{
			// Unset so as not to introduce into template scope
			unset($tpl);
			unset($file);

			// Never allow a 'this' property
			if (isset($this->this))
			{
				unset($this->this);
			}

			// Start capturing output into a buffer
			ob_start();

			// Include the requested template filename in the local scope
			// (this will execute the view logic).
			include $this->_template;

			// Done with the requested template; get the buffer and
			// clear it.
			$this->_output = ob_get_contents();
			ob_end_clean();

			return $this->_output;
		}
		else
		{
			throw new \Exception(\JText::sprintf('JLIB_APPLICATION_ERROR_LAYOUTFILE_NOT_FOUND', $file), 500);
		}
	}

	/**
	 * Get the layout template.
	 *
	 * @return  string  The layout template name
	 */
	public function getLayoutTemplate()
	{
		return $this->_layoutTemplate;
	}

	/**
	 * Create the filename for a resource
	 *
	 * @param   string  $type   The resource type to create the filename for
	 * @param   array   $parts  An associative array of filename information
	 *
	 * @return  string  The filename
	 *
	 * @since   12.2
	 */
	protected function _createFileName($type, $parts = array())
	{
		switch ($type)
		{
			case 'template':
				$filename = strtolower($parts['name']) . '.' . $this->_layoutExt;
				break;

			default:
				$filename = strtolower($parts['name']) . '.php';
				break;
		}

		return $filename;
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
