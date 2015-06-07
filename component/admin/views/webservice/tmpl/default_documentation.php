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
<div class="ws-params ws-params-documentation">
	<h2><?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_DOCUMENTATION_LABEL'); ?></h2>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('authorizationNeeded', 'documentation'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('authorizationNeeded', 'documentation'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('source', 'documentation'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('source', 'documentation'); ?>
		</div>
	</div>
	<div class="control-group ws-documentationSource ws-documentationSource-url">
		<div class="control-label">
			<?php echo $this->form->getLabel('url', 'documentation'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('url', 'documentation'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo $this->form->getLabel('description', 'documentation'); ?>
		</div>
		<div class="controls">
			<?php echo $this->form->getInput('description', 'documentation'); ?>
		</div>
	</div>

	<?php echo $this->form->getInput('isEnabled', 'documentation'); ?>
</div>
