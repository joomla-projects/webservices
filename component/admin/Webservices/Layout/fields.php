<?php
/**
 * @package     Redcore.Webservice
 * @subpackage  Layouts
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('_JEXEC') or die;

$view = $displayData['view'];

$operation = !empty($displayData['options']['operation']) ? $displayData['options']['operation'] : 'read-list';
$fieldList = !empty($displayData['options']['fieldList']) ? $displayData['options']['fieldList'] : array();
$form = !empty($displayData['options']['form']) ? $displayData['options']['form'] : null;
$readListValues = $operation == 'read-list' ? ',isFilterField,isSearchableField' : '';

?>
<div class="ws-rows ws-Field-<?php echo $operation; ?>">
	<hr/>
	<fieldset>
		<legend><?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELDS_LABEL'); ?></legend>
		<div class="control-group">
			<div class="control-label">
				<?php echo $form->getLabel('description', $operation . '.fields'); ?>
			</div>
			<div class="controls">
				<?php echo $form->getInput('description', $operation . '.fields'); ?>
			</div>
		</div>
		<div class="form-inline">
			<button type="button" class="btn btn-default btn-primary fields-add-new-row">
				<input type="hidden" name="addNewRowType" value="Field" />
				<input type="hidden" name="addNewRowOperation" value="<?php echo $operation; ?>" />
				<input type="hidden" name="addNewRowList" value="defaultValue,isRequiredField,isPrimaryField<?php echo $readListValues; ?>" />
				<i class="icon-plus"></i>
				<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_ADD_NEW_LABEL'); ?>
			</button>
			<span class="input-group">
				<span class="input-group-btn">
					<button class="btn btn-primary fields-add-new-row" type="button"><i class="icon-plus"></i>
						<input type="hidden" name="addNewRowType" value="Field" />
						<input type="hidden" name="addNewOptionType" value="FieldFromDatabase" />
						<input type="hidden" name="addNewRowOperation" value="<?php echo $operation; ?>" />
						<input type="hidden" name="addNewRowList" value="defaultValue,isRequiredField,isPrimaryField<?php echo $readListValues; ?>" />
						<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_ADD_NEW_FROM_DATABASE_LABEL'); ?>
					</button>
				</span>
				<span class="input-group-addon">
					<?php echo $form->getInput('addFromDatabase', 'main'); ?>
				</span>
			</span>
		</div>
		<hr/>
		<table class="table table-striped">
			<thead>
			<tr>
				<th width="10%" class="nowrap center">
					<strong><?php echo JText::_('JOPTIONS'); ?></strong>
				</th>
				<th width="1%" class="center">
					<?php echo JText::_('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_FIELD_NAME'); ?>
				</th>
				<th width="1%" class="nowrap center">
					<?php echo JText::_('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_FIELD_TRANSFORM'); ?>
				</th>
				<?php if (in_array('defaultValue', $fieldList)) : ?>
					<th class="center">
						<?php echo JText::_('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_FIELD_DEFAULT_VALUE'); ?>
					</th>
				<?php endif; ?>
				<?php if (in_array('isRequiredField', $fieldList)) : ?>
					<th class="center">
						<?php echo JText::_('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_FIELD_REQUIRED'); ?>
					</th>
				<?php endif; ?>
				<?php if (in_array('isFilterField', $fieldList)) : ?>
					<th class="center">
						<?php echo JText::_('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_FIELD_FILTER'); ?>
					</th>
				<?php endif; ?>
				<?php if (in_array('isSearchableField', $fieldList)) : ?>
					<th class="center">
						<?php echo JText::_('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_FIELD_SEARCHABLE'); ?>
					</th>
				<?php endif; ?>
				<?php if (in_array('isPrimaryField', $fieldList)) : ?>
					<th class="center">
						<?php echo JText::_('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_FIELD_PRIMARY'); ?>
					</th>
				<?php endif; ?>
				<th class="center">
					<?php echo JText::_('JGLOBAL_DESCRIPTION'); ?>
				</th>
			</tr>
			</thead>
			<tbody class="ws-row-list">
				<?php
				if (!empty($view->fields[$operation])) :
					foreach ($view->fields[$operation] as $field) :
						$displayData['options']['form'] = $field;
						echo $this->sublayout('field', $displayData);
					endforeach;
				endif;
				?>
			</tbody>
		</table>
	</fieldset>
	<hr/>
</div>
