<?php
/**
 * @package     Redcore
 * @subpackage  Api
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

/**
 * Object to represent a hypermedia link in HAL.
 *
 * @since  1.2
 */
abstract class JApiHalDocumentBase
{
	/**
	 * @var SimpleXMLElement
	 */
	protected $xml;

	/**
	 * Sets XML attributes for JApiHalDocumentLink
	 * 
	 * @param   SimpleXMLElement     $xml   XML document
	 * @param   JApiHalDocumentLink  $link  Link element
	 *
	 * @return JApiHalDocumentBase
	 */
	public function setXMLAttributes(SimpleXMLElement $xml, JApiHalDocumentLink $link)
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
