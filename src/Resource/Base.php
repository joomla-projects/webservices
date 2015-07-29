<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Webservices\Resource;

/**
 * Object to represent a hypermedia link in HAL.
 *
 * @since  1.2
 */
abstract class Base
{
	/**
	 * @var \SimpleXMLElement
	 */
	protected $xml;

	/**
	 * Sets XML attributes for \Joomla\Webservices\Resource\Link
	 * 
	 * @param   \SimpleXMLElement  $xml   XML document
	 * @param   Link               $link  Link element
	 *
	 * @return  $this
	 */
	public function setXMLAttributes(\SimpleXMLElement $xml, Link $link)
	{
		$xml->addAttribute('href', $link->getHref());

		if ($link->getRel() && $link->getRel() !== 'self')
		{
			$xml->addAttribute('rel', $link->getRel());
		}

		if ($link->getName())
		{
			$xml->addAttribute('name', $link->getName());
		}

		if ($link->getTitle())
		{
			$xml->addAttribute('title', $link->getTitle());
		}

		if ($link->getHreflang())
		{
			$xml->addAttribute('hreflang', $link->getHreflang());
		}

		return $this;
	}
}
