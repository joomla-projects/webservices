<?php
/**
 * Webservices component for Joomla! CMS
 *
 * @copyright  Copyright (C) 2004 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Webservices\Controller;

use Joomla\Registry\Registry;

/**
 * Default display controller
 *
 * @since  2.0
 */
class DisplayController extends \JControllerBase
{
	/**
	 * The object context
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $context;

	/**
	 * Default ordering column
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $defaultOrderColumn = 'w.id';

	/**
	 * Default sort direction
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $defaultDirection = 'DESC';

	/**
	 * The default view to display
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $defaultView = 'webservices';

	/**
	 * Instantiate the controller
	 *
	 * @param   \JInput            $input  The input object.
	 * @param   \JApplicationBase  $app    The application object.
	 *
	 * @since   2.0
	 */
	public function __construct(\JInput $input = null, \JApplicationBase $app = null)
	{
		parent::__construct($input, $app);

		// Set the context for the controller
		$this->context = \JApplicationHelper::getComponentName() . '.' . $this->getInput()->getCmd('view', $this->defaultView);
	}

	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		// Set up variables to build our classes
		$view   = $this->getInput()->getCmd('view', $this->defaultView);
		$format = $this->getInput()->getCmd('format', 'html');

		// Register the layout paths for the view
		$paths = new \SplPriorityQueue;

		// Add the path for template overrides
		$paths->insert(JPATH_THEMES . '/' . $this->getApplication()->getTemplate() . '/html/' . \JApplicationHelper::getComponentName() . '/' . $view, 2);

		// Add the path for the default layouts
		$paths->insert(JPATH_BASE . '/components/' . \JApplicationHelper::getComponentName() . '/Webservices/View/' . ucfirst($view) . '/tmpl', 1);

		// Build the class names for the model and view
		$viewClass  = '\\Webservices\\View\\' . ucfirst($view) . '\\' . ucfirst($view) . ucfirst($format) . 'View';
		$modelClass = '\\Webservices\\Model\\' . ucfirst($view) . 'Model';

		// Sanity check - Ensure our classes exist
		if (!class_exists($viewClass))
		{
			// Try to use a default view
			$viewClass = '\\Webservices\\View\\Default' . ucfirst($format) . 'View';

			if (!class_exists($viewClass))
			{
				throw new \RuntimeException(
					sprintf('The view class for the %1$s view in the %2$s format was not found.', $view, $format), 500
				);
			}
		}

		if (!class_exists($modelClass))
		{
			throw new \RuntimeException(sprintf('The model class for the %s view was not found.', $view), 500);
		}

		// Initialize the model class now; need to do it before setting the state to get required data from it
		$model = new $modelClass($this->context, null, \JFactory::getDbo());

		// Initialize the state for the model
		$model->setState($this->initializeState($model));

		// Initialize the view class now
		$view = new $viewClass($model, $paths);

		// Echo the rendered view for the application
		echo $view->render();

		// Finished!
		return true;
	}

	/**
	 * Sets the state for the model object
	 *
	 * @param   \JModel  $model  Model object
	 *
	 * @return  Registry
	 *
	 * @since   2.0
	 * @todo    This should really be more generic for a default controller
	 */
	protected function initializeState(\JModel $model)
	{
		$state = new Registry;

		// Load the filter state.
		$search = $this->getApplication()->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '');
		$state->set('filter.search', $search);

		$applied = $this->getApplication()->getUserStateFromRequest($this->context . '.filter.applied', 'filter_applied', '');
		$state->set('filter.applied', $applied);

		// Load the parameters.
		$params = \JComponentHelper::getParams('com_webservices');

		$state->set('params', $params);
		//$state->set('github_user', $params->get('org', 'joomla'));
		//$state->set('github_repo', $params->get('repo', 'joomla-cms'));

		// Pre-fill the limits
		$limit = $this->getApplication()->getUserStateFromRequest('global.list.limit', 'limit', $this->getApplication()->get('list_limit'), 'uint');
		$state->set('list.limit', $limit);

		// Check if the ordering field is in the white list, otherwise use the incoming value.
		$value = $this->getApplication()->getUserStateFromRequest($this->context . '.ordercol', 'filter_order', $this->defaultOrderColumn);

		if (method_exists($model, 'getSortFields'))
		{
			if (!in_array($value, $model->getSortFields()))
			{
				$value = $this->defaultOrderColumn;
				$this->getApplication()->setUserState($this->context . '.ordercol', $value);
			}
		}

		$state->set('list.ordering', $value);

		// Check if the ordering direction is valid, otherwise use the incoming value.
		$value = $this->getApplication()->getUserStateFromRequest($this->context . '.orderdirn', 'filter_order_Dir', $this->defaultDirection);

		if (!in_array(strtoupper($value), array('ASC', 'DESC', '')))
		{
			$value = $this->defaultDirection;
			$this->getApplication()->setUserState($this->context . '.orderdirn', $value);
		}

		$state->set('list.direction', $value);

		$value = $this->getApplication()->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0);
		$limitstart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
		$state->set('list.start', $limitstart);

		return $state;
	}
}
