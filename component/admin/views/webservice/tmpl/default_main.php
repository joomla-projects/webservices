<?php
/**
 * @package     Redcore.Admin
 * @subpackage  Templates
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */
defined('_JEXEC') or die;

?>
<div id="main-params" class="form-horizontal">
	<h2><?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_TAB_GENERAL'); ?></h2>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('client', 'main'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('client', 'main'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('name', 'main'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('name', 'main'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('version', 'main'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('version', 'main'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('path', 'main'); ?>
		</div>
		<div class="controls">
			<span class="input-group-addon hasTooltip" title="<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_PATH_DESCRIPTION'); ?>">
				/<?php echo JApiHalHelper::getWebservicesRelativePath(); ?>/
			</span>
			<?php echo $this->form->getInput('path', 'main'); ?>
			<span class="input-group-addon hasTooltip" title="<?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_FILE_DESCRIPTION'); ?>">
					/<?php echo $this->form->getValue('xmlFile', 'main'); ?>
				</span>
		</div>
	</div>

	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('authorizationAssetName', 'main'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('authorizationAssetName', 'main'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('title', 'main'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('title', 'main'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('author', 'main'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('author', 'main'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('copyright', 'main'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('copyright', 'main'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('state', 'main'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('state', 'main'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('description', 'main'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('description', 'main'); ?>
		</div>
	</div>
</div>
