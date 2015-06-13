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
 * Interface to transform api output for SOAP
 *
 * @package     Redcore
 * @subpackage  Api
 * @since       1.4
 */
final class JApiSoapTransformArray extends JApiSoapTransformBase
{
	/**
	 * string $type
	 *
	 * @var    string  Base SOAP type
	 * @since  1.4
	 */
	public $type = 'tns:ArrayOfStringType';

	/**
	 * string $defaultValue
	 *
	 * @var    string  Default value when not null
	 * @since  1.4
	 */
	public $defaultValue = 'array()';
}
