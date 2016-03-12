<?php
/**
 * @package    Webservices
 *
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Renderer;

/**
 * Interface for Renderer classes.
 *
 * @since  __DEPLOY_VERSION__
 */
interface RendererInterface
{
	/**
	 * Build a resource from a representation.
	 *
	 * @param   Resource  $resource       A resource object to build.
	 * @param   string    $serialisation  A serialisation for parsing.
	 *
	 * @return  string
	 */
	// public function build(Resource $resource, $serialisation);

	/**
	 * Render a representation of a resource.
	 *
	 * @param   Resource  $resource  A resource object to render.
	 *
	 * @return  string
	 */
	// public function render(Resource $resource);

	/**
	 * Parse a serialisation into a ResourceItem object.
	 *
	 * @param   Resource  $resource       A resource object to build.
	 * @param   string    $serialisation  A serialisation for parsing.
	 *
	 * @return  void
	 */
	// public function parseResourceItem(ResourceItem $resource, $serialisation);

	/**
	 * Render a representation of a ResourceCurie object.
	 *
	 * @param   ResourceCurie  $resource  A resource curie object.
	 *
	 * @return  A representation of the object.
	 */
	// public function renderResourceCurie(ResourceCurie $resource);

	/**
	 * Render a representation of a ResourceData object.
	 *
	 * @param   ResourceData  $resource  A resource data object.
	 *
	 * @return  A representation of the object.
	 */
	// public function renderResourceData(ResourceData $resource);

	/**
	 * Render a representation of a ResourceEmbedded object.
	 *
	 * @param   ResourceEmbedded  $resource  An embedded resources object.
	 *
	 * @return  A representation of the object.
	 */
	// public function renderResourceEmbedded(ResourceEmbedded $resource);

	/**
	 * Render a representation of a ResourceItem object.
	 *
	 * @param   ResourceItem  $resource  A resource item object.
	 *
	 * @return  A representation of the object.
	 */
	// public function renderResourceItem(ResourceItem $resource);

	/**
	 * Render a representation of a ResourceLink object.
	 *
	 * @param   ResourceLink  $resource  A resource link object.
	 *
	 * @return  A representation of the object.
	 */
	// public function renderResourceLink(ResourceLink $resource);

	/**
	 * Render a representation of a ResourceLinks object.
	 *
	 * @param   ResourceLinks  $resource  A resource links object.
	 *
	 * @return  A representation of the object.
	 */
	// public function renderResourceLinks(ResourceLinks $resource);

	/**
	 * Render a representation of a ResourceMetadata object.
	 *
	 * @param   ResourceMetadata  $resource  A resource metadata object.
	 *
	 * @return  A representation of the object.
	 */
	// public function renderResourceMetadata(ResourceMetadata $resource);
}
