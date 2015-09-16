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
$form = !empty($displayData['options']['form']) ? $displayData['options']['form'] : null;
$heading = !empty($displayData['options']['heading']) ?
	$displayData['options']['heading'] : JText::_('COM_WEBSERVICES_WEBSERVICE_RESOURCES_LABEL');
$headingDescription = !empty($displayData['options']['headingDescription']) ?
	$displayData['options']['headingDescription'] : JText::_('COM_WEBSERVICES_WEBSERVICE_RESOURCES_DESCRIPTION');

?>
<div class="ws-rows ws-Resource-<?php echo $operation; ?>">
	<hr/>
	<fieldset>
		<legend>
			<span class="hasTooltip" title="<?php echo $headingDescription; ?>">
				<?php echo $heading; ?>
			</span>
		</legend>
		<div class="control-group">
			<div class="control-label">
				<?php echo $form->getLabel('description', $operation . '.resources'); ?>
			</div>
			<div class="controls">
				<?php echo $form->getInput('description', $operation . '.resources'); ?>
			</div>
		</div>
		<div class="form-inline">
			<button type="button" class="btn btn-default btn-primary fields-add-new-row">
				<input type="hidden" name="addNewRowType" value="Resource" />
				<input type="hidden" name="addNewRowOperation" value="<?php echo $operation; ?>" />
				<input type="hidden" name="addNewRowList" value="" />
				<i class="icon-plus"></i>
				<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_RESOURCE_ADD_NEW_LABEL'); ?>
			</button>
			<button type="button" class="btn btn-default btn-primary fields-add-new-row">
				<input type="hidden" name="addNewRowType" value="Resource" />
				<input type="hidden" name="addNewRowOperation" value="<?php echo $operation; ?>" />
				<input type="hidden" name="addNewRowList" value="link" />
				<i class="icon-plus"></i>
				<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_RESOURCE_ADD_NEW_LINK_LABEL'); ?>
			</button>
			<span class="input-group">
				<span class="input-group-btn">
					<button class="btn btn-primary fields-add-new-row" type="button"><i class="icon-plus"></i>
						<input type="hidden" name="addNewRowType" value="Resource" />
						<input type="hidden" name="addNewOptionType" value="ResourceFromDatabase" />
						<input type="hidden" name="addNewRowOperation" value="<?php echo $operation; ?>" />
						<input type="hidden" name="addNewRowList" value="" />
						<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FIELD_ADD_NEW_FROM_DATABASE_LABEL'); ?>
					</button>
				</span>
				<span class="input-group-addon">
					<?php echo $form->getInput('addFromDatabase', 'main'); ?>
				</span>
			</span>
			<span class="input-group">
				<span class="input-group-btn">
					<button class="btn btn-primary fields-add-new-row hasTooltip" type="button"
					        data-original-title="<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_LIST_DESCRIPTION'); ?>">
						<i class="icon-plus"></i>
						<input type="hidden" name="addNewRowType" value="Resource" />
						<input type="hidden" name="addNewOptionType" value="ConnectWebservice" />
						<input type="hidden" name="addNewRowOperation" value="<?php echo $operation; ?>" />
						<input type="hidden" name="addNewRowList" value="" />
						<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_RESOURCE_ADD_CONNECTION_LABEL'); ?>
					</button>
				</span>
				<span class="input-group-addon">
					<?php echo $form->getInput('connectWebservice', 'main'); ?>
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
				<th class="center">
					<?php echo JText::_('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_RESOURCE_NAME'); ?>
				</th>
				<th class="nowrap center">
					<?php echo JText::_('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_RESOURCE_GROUP'); ?>
				</th>
				<th class="nowrap center">
					<?php echo JText::_('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_RESOURCE_FORMAT'); ?>
				</th>
				<th class="nowrap center">
					<?php echo JText::_('LIB_WEBSERVICES_API_HAL_WEBSERVICE_DOCUMENTATION_RESOURCE_PARAMETERS'); ?>
				</th>
				<th class="nowrap center">
					<?php echo JText::_('JGLOBAL_DESCRIPTION'); ?>
				</th>
			</tr>
			</thead>
			<tbody class="ws-row-list">
				<?php
				if (!empty($view->resources[$operation])) :
					foreach ($view->resources[$operation] as $resourceSpecific) :
						foreach ($resourceSpecific as $resource) :
							$displayData['options']['form'] = $resource;
							echo $this->sublayout('resource', $displayData);
						endforeach;
					endforeach;
				endif;
				?>
			</tbody>
		</table>
	</fieldset>
	<hr/>
</div>
