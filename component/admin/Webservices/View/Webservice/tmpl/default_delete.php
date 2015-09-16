<?php
/**
 * @package     Redcore.Admin
 * @subpackage  Templates
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */
defined('_JEXEC') or die;

echo \JLayoutHelper::render(
	'operation',
	array(
		'view' => $this,
		'options' => array(
			'operation' => 'delete',
			'form'      => $this->form,
			'tabActive' => ' active in ',
			'fieldList' => array('defaultValue', 'isRequiredField', 'isPrimaryField'),
		)
	),
	JPATH_COMPONENT_ADMINISTRATOR.'/Webservices/Layout'
);
