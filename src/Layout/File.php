<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Layout;

use Joomla\Filesystem\Path;

/**
 * Base class for rendering a display layout
 * loaded from from a layout file
 *
 * @see    https://docs.joomla.org/Sharing_layouts_across_views_or_extensions_with_JLayout
 * @since  3.0
 */
class File extends Base
{
	/**
	 * @var    string  Dot separated path to the layout file, relative to base path
	 * @since  3.0
	 */
	protected $layoutId = '';

	/**
	 * @var    string  Base path to use when loading layout files
	 * @since  3.0
	 */
	protected $basePath = null;

	/**
	 * @var    string  Full path to actual layout files, after possible template override check
	 * @since  3.0.3
	 */
	protected $fullPath = null;

	/**
	 * Paths to search for layouts
	 *
	 * @var    array
	 * @since  3.2
	 */
	protected $includePaths = array();

	/**
	 * Method to instantiate the file-based layout.
	 *
	 * @param   string  $layoutId  Dot separated path to the layout file, relative to base path
	 * @param   string  $basePath  Base path to use when loading layout files
	 * @param   mixed   $options   Optional custom options to load. Registry or array format [@since 3.2]
	 *
	 * @since   3.0
	 */
	public function __construct($layoutId, $basePath = null, $options = null)
	{
		// Initialise / Load options
		$this->setOptions($options);

		// Main properties
		$this->setLayout($layoutId);
		$this->basePath = $basePath;
	}

	/**
	 * Method to render the layout.
	 *
	 * @param   object  $displayData  Object which properties are used inside the layout file to build displayed output
	 *
	 * @return  string  The necessary HTML to display the layout
	 *
	 * @since   3.0
	 */
	public function render($displayData)
	{
		$layoutOutput = '';

		// Check possible overrides, and build the full path to layout file
		$path = $this->getPath();

		if ($this->options->get('debug', false))
		{
			echo "<pre>" . $this->renderDebugMessages() . "</pre>";
		}

		// If there exists such a layout file, include it and collect its output
		if (!empty($path))
		{
			ob_start();
			include $path;
			$layoutOutput = ob_get_contents();
			ob_end_clean();
		}

		return $layoutOutput;
	}

	/**
	 * Method to finds the full real file path, checking possible overrides
	 *
	 * @return  string  The full path to the layout file
	 *
	 * @since   3.0
	 */
	protected function getPath()
	{
		if (is_null($this->fullPath) && !empty($this->layoutId))
		{
			$this->addDebugMessage('<strong>Layout:</strong> ' . $this->layoutId);

			// Refresh paths
			$this->refreshIncludePaths();

			$this->addDebugMessage('<strong>Include Paths:</strong> ' . print_r($this->includePaths, true));

			$suffixes = $this->options->get('suffixes', array());

			// Search for suffixed versions. Example: tags.j31.php
			if (!empty($suffixes))
			{
				$this->addDebugMessage('<strong>Suffixes:</strong> ' . print_r($suffixes, true));

				foreach ($suffixes as $suffix)
				{
					$rawPath  = str_replace('.', '/', $this->layoutId) . '.' . $suffix . '.php';
					$this->addDebugMessage('<strong>Searching layout for:</strong> ' . $rawPath);

					if ($this->fullPath = Path::find($this->includePaths, $rawPath))
					{
						$this->addDebugMessage('<strong>Found layout:</strong> ' . $this->fullPath);

						return $this->fullPath;
					}
				}
			}

			// Standard version
			$rawPath  = str_replace('.', '/', $this->layoutId) . '.php';
			$this->addDebugMessage('<strong>Searching layout for:</strong> ' . $rawPath);

			$this->fullPath = Path::find($this->includePaths, $rawPath);

			if ($this->fullPath)
			{
				$this->addDebugMessage('<strong>Found layout:</strong> ' . $this->fullPath);
			}
		}

		return $this->fullPath;
	}

	/**
	 * Add one path to include in layout search. Proxy of addIncludePaths()
	 *
	 * @param   string  $path  The path to search for layouts
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function addIncludePath($path)
	{
		$this->addIncludePaths($path);
	}

	/**
	 * Add one or more paths to include in layout search
	 *
	 * @param   string  $paths  The path or array of paths to search for layouts
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function addIncludePaths($paths)
	{
		if (!empty($paths))
		{
			if (is_array($paths))
			{
				$this->includePaths = array_unique(array_merge($paths, $this->includePaths));
			}
			else
			{
				array_unshift($this->includePaths, $paths);
			}
		}
	}

	/**
	 * Remove one path from the layout search
	 *
	 * @param   string  $path  The path to remove from the layout search
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function removeIncludePath($path)
	{
		$this->removeIncludePaths($path);
	}

	/**
	 * Remove one or more paths to exclude in layout search
	 *
	 * @param   string  $paths  The path or array of paths to remove for the layout search
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function removeIncludePaths($paths)
	{
		if (!empty($paths))
		{
			$paths = (array) $paths;

			$this->includePaths = array_diff($this->includePaths, $paths);
		}
	}

	/**
	 * Change the layout
	 *
	 * @param   string  $layoutId  Layout to render
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function setLayout($layoutId)
	{
		$this->layoutId = $layoutId;
		$this->fullPath = null;
	}

	/**
	 * Refresh the list of include paths
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function refreshIncludePaths()
	{
		// Reset includePaths
		$this->includePaths = array();

		// (1 - lower priority) Frontend base layouts
		$this->addIncludePaths(JPATH_TEMPLATES);

		// (2 - highest priority) Received a custom high priority path ?
		if (!is_null($this->basePath))
		{
			$this->addIncludePath(rtrim($this->basePath, DIRECTORY_SEPARATOR));
		}
	}

	/**
	 * Change the debug mode
	 *
	 * @param   boolean  $debug  Enable / Disable debug
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function setDebug($debug)
	{
		$this->options->set('debug', (boolean) $debug);
	}

	/**
	 * Render a layout with the same include paths & options
	 *
	 * @param   object  $layoutId     Object which properties are used inside the layout file to build displayed output
	 * @param   mixed   $displayData  Data to be rendered
	 *
	 * @return  string  The necessary HTML to display the layout
	 *
	 * @since   3.2
	 */
	public function sublayout($layoutId, $displayData)
	{
		// Sublayouts are searched in a subfolder with the name of the current layout
		if (!empty($this->layoutId))
		{
			$layoutId = $this->layoutId . '.' . $layoutId;
		}

		$sublayout = new static($layoutId, $this->basePath, $this->options);
		$sublayout->includePaths = $this->includePaths;

		return $sublayout->render($displayData);
	}
}
