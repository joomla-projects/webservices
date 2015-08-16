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
$name = !empty($displayData['options']['form']['name']) ? $displayData['options']['form']['name'] : '';
$transform = !empty($displayData['options']['form']['transform']) ? $displayData['options']['form']['transform'] : 'string';
$defaultValue = !empty($displayData['options']['form']['defaultValue']) ? $displayData['options']['form']['defaultValue'] : '';
$description = !empty($displayData['options']['form']['description']) ? $displayData['options']['form']['description'] : '';
$isRequiredField = !empty($displayData['options']['form']['isRequiredField']) ? $displayData['options']['form']['isRequiredField'] : 'false';
$isFilterField = !empty($displayData['options']['form']['isFilterField']) ? $displayData['options']['form']['isFilterField'] : 'false';
$isSearchableField = !empty($displayData['options']['form']['isSearchableField']) ? $displayData['options']['form']['isSearchableField'] : 'false';
$isPrimaryField = !empty($displayData['options']['form']['isPrimaryField']) ? $displayData['options']['form']['isPrimaryField'] : 'false';
$displayData['options']['form'] = !empty($displayData['options']['form']) ? $displayData['options']['form'] : array();

$id = WebservicesHelper::getUniqueName($operation);
?>
<tr class="row-stripped">
	<td>
		<button type="button" class="btn btn-default btn-xs btn-primary ws-row-display fields-edit-row">
			<i class="icon-edit"></i>
			<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_RESOURCE_EDIT_LABEL'); ?>
		</button>
		<button type="button" class="btn btn-default btn-xs btn-success ws-row-edit fields-apply-row" style="display: none;">
			<i class="icon-save"></i>
			<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_RESOURCE_APPLY_LABEL'); ?>
		</button>
		<button type="button" class="btn btn-default btn-xs btn-danger fields-remove-row">
			<i class="icon-minus"></i>
			<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_REMOVE_LABEL'); ?>
		</button>
		<input type="hidden" class="ws-row-original" name="jform[<?php echo $operation;?>][fields][field][]"
		       value="<?php echo $this->escape(json_encode($displayData['options']['form'])); ?>" />
	</td>
	<td class="ws-row-display-cell-name">
		<?php echo $name;?>
	</td>
	<td class="ws-row-display-cell-transform">
		<?php echo $transform;?>
	</td>
	<?php if (in_array('defaultValue', $fieldList)) : ?>
		<td class="ws-row-display-cell-defaultValue">
			<?php echo $defaultValue;?>
		</td>
	<?php endif; ?>
	<?php if (in_array('isRequiredField', $fieldList)) : ?>
		<td class="ws-row-display-cell-isRequiredField">
			<?php echo $isRequiredField;?>
		</td>
	<?php endif; ?>
	<?php if (in_array('isFilterField', $fieldList)) : ?>
		<td class="ws-row-display-cell-isFilterField">
			<?php echo $isFilterField;?>
		</td>
	<?php endif; ?>
	<?php if (in_array('isSearchableField', $fieldList)) : ?>
		<td class="ws-row-display-cell-isSearchableField">
			<?php echo $isSearchableField;?>
		</td>
	<?php endif; ?>
	<?php if (in_array('isPrimaryField', $fieldList)) : ?>
		<td class="ws-row-display-cell-isPrimaryField">
			<?php echo $isPrimaryField;?>
		</td>
	<?php endif; ?>
	<td class="ws-row-display-cell-description">
		<?php echo $description;?>
	</td>
</tr>
<tr class="ws-row-edit" style="display: none;">
	<td class="form-horizontal" colspan="7">
		<div class="control-group">
			<div class="control-label">
				<label class="input-group-addon hasTooltip" title="<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_NAME_DESCRIPTION'); ?>">
					<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_NAME_LABEL'); ?>
				</label>
			</div>
			<div class="controls">
				<input type="text" name="name" value="<?php echo $name;?>" class="form-control" />
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<label class="input-group-addon hasTooltip" title="<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_TRANSFORM_DESCRIPTION'); ?>">
					<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_TRANSFORM_LABEL'); ?>
				</label>
			</div>
			<div class="controls">
				<?php echo JHtml::_(
					'select.genericlist',
					WebservicesHelper::getTransformElements(),
					'transform',
					' class="required form-control" ',
					'value',
					'text',
					$transform
				); ?>
			</div>
		</div>
		<?php if (in_array('defaultValue', $fieldList)) : ?>
			<div class="control-group">
				<div class="control-label">
					<label class="input-group-addon hasTooltip" title="<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_DEFAULTVALUE_DESCRIPTION'); ?>">
						<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_DEFAULTVALUE_LABEL'); ?>
					</label>
				</div>
				<div class="controls">
					<input type="text" name="defaultValue" value="<?php echo $defaultValue;?>" class="form-control" />
				</div>
			</div>
		<?php endif; ?>
		<?php if (in_array('isRequiredField', $fieldList)) : ?>
			<div class="control-group">
				<div class="control-label">
					<label class="input-group-addon hasTooltip" title="<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_ISREQUIREDFIELD_DESCRIPTION'); ?>">
						<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_ISREQUIREDFIELD_LABEL'); ?>
					</label>
				</div>
				<div class="controls">
					<fieldset class="radio btn-group">
						<input id="<?php echo $id;?>_isRequiredField1" type="radio" name="<?php echo $id;?>_isRequiredField"
						       value="true" <?php echo $isRequiredField == 'false' ? '' : ' checked="checked" '; ?> />
						<label for="<?php echo $id;?>_isRequiredField1" class="btn btn-default"><?php echo JText::_('JYES'); ?></label>
						<input id="<?php echo $id;?>_isRequiredField0" type="radio" name="<?php echo $id;?>_isRequiredField"
						       value="false" <?php echo $isRequiredField == 'false' ? ' checked="checked" ' : ''; ?> />
						<label for="<?php echo $id;?>_isRequiredField0" class="btn btn-default"><?php echo JText::_('JNO'); ?></label>
					</fieldset>
				</div>
			</div>
		<?php endif; ?>
		<?php if (in_array('isFilterField', $fieldList)) : ?>
			<div class="control-group">
				<div class="control-label">
					<label class="input-group-addon hasTooltip" title="<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_ISFILTERFIELD_DESCRIPTION'); ?>">
						<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_ISFILTERFIELD_LABEL'); ?>
					</label>
				</div>
				<div class="controls">
					<fieldset class="radio btn-group">
						<input id="<?php echo $id;?>_isFilterField0" type="radio" name="<?php echo $id;?>_isFilterField"
						       value="true" <?php echo $isFilterField == 'false' ? '' : ' checked="checked" '; ?> />
						<label for="<?php echo $id;?>_isFilterField0" class="btn btn-default"><?php echo JText::_('JYES'); ?></label>
						<input id="<?php echo $id;?>_isFilterField1" type="radio" name="<?php echo $id;?>_isFilterField"
						       value="false" <?php echo $isFilterField == 'false' ? ' checked="checked" ' : ''; ?> />
						<label for="<?php echo $id;?>_isFilterField1" class="btn btn-default"><?php echo JText::_('JNO'); ?></label>
					</fieldset>
				</div>
			</div>
		<?php endif; ?>
		<?php if (in_array('isSearchableField', $fieldList)) : ?>
			<div class="control-group">
				<div class="control-label">
					<label class="input-group-addon hasTooltip" title="<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_ISSEARCHABLEFIELD_DESCRIPTION'); ?>">
						<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_ISSEARCHABLEFIELD_LABEL'); ?>
					</label>
				</div>
				<div class="controls">
					<fieldset class="radio btn-group">
						<input id="<?php echo $id;?>_isSearchableField0" type="radio" name="<?php echo $id;?>_isSearchableField"
						       value="true" <?php echo $isSearchableField == 'false' ? '' : ' checked="checked" '; ?> />
						<label for="<?php echo $id;?>_isSearchableField0" class="btn btn-default"><?php echo JText::_('JYES'); ?></label>
						<input id="<?php echo $id;?>_isSearchableField1" type="radio" name="<?php echo $id;?>_isSearchableField"
						       value="false" <?php echo $isSearchableField == 'false' ? ' checked="checked" ' : ''; ?> />
						<label for="<?php echo $id;?>_isSearchableField1" class="btn btn-default"><?php echo JText::_('JNO'); ?></label>
					</fieldset>
				</div>
			</div>
		<?php endif; ?>
		<?php if (in_array('isPrimaryField', $fieldList)) : ?>
			<div class="control-group">
				<div class="control-label">
					<label class="input-group-addon hasTooltip" title="<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_ISRIMARYFIELD_DESCRIPTION'); ?>">
						<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_ISRIMARYFIELD_LABEL'); ?>
					</label>
				</div>
				<div class="controls">
					<fieldset class="radio btn-group">
						<input id="<?php echo $id;?>_isPrimaryField0" type="radio" name="<?php echo $id;?>_isPrimaryField"
						       value="true" <?php echo $isPrimaryField == 'false' ? '' : ' checked="checked" '; ?> />
						<label for="<?php echo $id;?>_isPrimaryField0" class="btn btn-default"><?php echo JText::_('JYES'); ?></label>
						<input id="<?php echo $id;?>_isPrimaryField1" type="radio" name="<?php echo $id;?>_isPrimaryField"
						       value="false" <?php echo $isPrimaryField == 'false' ? ' checked="checked" ' : ''; ?> />
						<label for="<?php echo $id;?>_isPrimaryField1" class="btn btn-default"><?php echo JText::_('JNO'); ?></label>
					</fieldset>
				</div>
			</div>
		<?php endif; ?>
		<div class="control-group">
			<div class="control-label">
				<label class="input-group-addon hasTooltip" title="<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_DESCRIPTION_FIELD_DESCRIPTION'); ?>">
					<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_DESCRIPTION_LABEL'); ?>
				</label>
			</div>
			<div class="controls">
				<input type="text" name="description" value="<?php echo $description;?>" class="form-control" />
			</div>
		</div>
	</td>
</tr>