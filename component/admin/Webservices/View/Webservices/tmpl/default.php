<?php
/**
 * @package     Redcore.Admin
 * @subpackage  Views
 *
 * @copyright   Copyright (C) 2008 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('_JEXEC') or die;

\JHtml::_('bootstrap.tooltip');
\JHtml::_('behavior.multiselect');
\JHtml::_('formbehavior.chosen', 'select');
\JHtml::_('behavior.keepalive');

$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$action     = \JRoute::_('index.php?option=com_webservices&view=webservices');
$user		= \JFactory::getUser();
$userId		= $user->get('id');
?>
<script type="text/javascript">
	function setWebservice(client, webservice, version, folder, task)
	{
		document.getElementById('client').value = client;
		document.getElementById('webservice').value = webservice;
		document.getElementById('version').value = version;
		document.getElementById('folder').value = folder;

		if (task != '')
		{
			if (task == 'webservices.delete')
			{
				if (confirm('<?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_UNINSTALL_CONFIRM', true); ?>'))
					submitAction(task, document.getElementById('adminForm'));
			}
			else if (task == 'webservices.deleteWebservice')
			{
				if (confirm('<?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_DELETE_CONFIRM', true); ?>'))
					submitAction(task, document.getElementById('adminForm'));
			}
			else
			{
				submitAction(task, document.getElementById('adminForm'));
			}
		}
	}
	jQuery(document).ready(function () {
		jQuery('[data-target="#webservicePreview"]').click(function(e) {
			e.preventDefault();
			var url = jQuery(this).attr('data-remote');
			var format = jQuery(this).attr('data-remote-format');
			var dataType = format == 'json' ? 'json' : 'text';

			jQuery.get(url, null, function(data){
				if (format == 'json')
				{
					data = syntaxHighlight(data);
					data = jQuery('<pre></pre>').html(data);
				}
				else if (format == 'doc')
				{
					data = jQuery(data).contents();
				}

				jQuery('#webservicePreview .modal-body').html(data);
				jQuery('#webservicePreview').modal('show');
				jQuery('#webservicePreview').data('url', url);
			}, dataType);

		});
	});

	function syntaxHighlight(json) {
		if (typeof json != 'string') {
			json = JSON.stringify(json, undefined, 4);
		}
		json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
			var cls = 'number';
			if (/^"/.test(match)) {
				if (/:$/.test(match)) {
					cls = 'key';
				} else {
					cls = 'string';
				}
			} else if (/true|false/.test(match)) {
				cls = 'boolean';
			} else if (/null/.test(match)) {
				cls = 'null';
			}
			return '<span class="' + cls + '">' + match + '</span>';
		});
	}

	function submitAction(task, form)
	{
		if (typeof Joomla.submitform == 'function')
		{
			Joomla.submitform(task, form);
		}
		else
		{
			if (typeof(task) !== 'undefined' && task !== "") {
				document.getElementById('adminForm').task.value = task;
			}

			// Submit the form.
			if (typeof form.onsubmit == 'function') {
				form.onsubmit();
			}
			if (typeof form.fireEvent == "function") {
				form.fireEvent('submit');
			}
			form.submit();
		}
	}

	function printDocumentation()
	{
		url = jQuery('#webservicePreview').data('url') + '&print';
		window.open(url);
	}
</script>
<style>
	pre {outline: 1px solid #ccc; padding: 5px; margin: 5px; }
	.string { color: green; }
	.number { color: darkorange; }
	.boolean { color: blue; }
	.null { color: magenta; }
	.key { color: red; }
	.modal.large {
		width: 80%;
		margin-left:-40%;
	}
</style>
<form action="<?php echo $action; ?>" id="adminForm" method="post" name="adminForm" autocomplete="off" class="adminForm form-validate form-horizontal" enctype="multipart/form-data">
	<div id="j-sidebar-container" class="span2">
		<?php //echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php
		// Search tools bar
		echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>
		<hr/>
		<div class="modal fade"
		     id="webservicePreview"
		     tabindex="-1"
		     role="dialog"
		     aria-labelledby="webservicePreview"
		     aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title"><?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_PREVIEW') ?></h4>
					</div>
					<div class="modal-body"><pre></pre>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn" onclick="printDocumentation();"><i class="icon-print"></i></button>
						<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo JText::_('JTOOLBAR_CLOSE') ?></button>
					</div>
				</div>
			</div>
		</div>
		<ul class="nav nav-tabs" id="mainTabs">
			<li role="presentation">
				<a href="#mainComponentWebservices" data-toggle="tab"><?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_INSTALLED_WEBSERVICES'); ?></a>
			</li>
			<li role="presentation">
				<a href="#mainComponentWebservicesXmls" data-toggle="tab" class="lc-not_installed_webservices">
					<?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_AVAILABLE_WEBSERVICES'); ?> <span class="badge"><?php echo $this->xmlFilesAvailable; ?></span>
				</a>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane" id="mainComponentWebservices">
				<p class="tab-description"><?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_DESC'); ?></p>

				<div class="row">
					<table class="table table-striped table-hover" id="oauthClientsList">
						<thead>
						<tr>
							<th class="hidden-xs">
								<input type="checkbox" name="checkall-toggle" value=""
								       title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)"/>
							</th>
							<th class="nowrap center">
								<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'w.state', $listDirn, $listOrder); ?>
							</th>
							<th class="nowrap">
								<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'w.title', $listDirn, $listOrder); ?>
							</th>
							<th>
								<?php echo JHtml::_('searchtools.sort', 'COM_WEBSERVICES_WEBSERVICE_CLIENT_LABEL', 'w.client', $listDirn, $listOrder); ?>
							</th>
							<th>
								<?php echo JHtml::_('searchtools.sort', 'COM_WEBSERVICES_WEBSERVICES_PATH_LABEL', 'w.path', $listDirn, $listOrder); ?>
							</th>
							<th class="nowrap hidden-xs">
								<?php echo JHtml::_('searchtools.sort', 'COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_AVAILABLE_SCOPES', 'w.scopes', $listDirn, $listOrder); ?>
							</th>
						</tr>
						</thead>
						<?php if ($this->items): ?>
							<tbody>
							<?php foreach ($this->items as $i => $item):
								$canEdit    = $user->authorise('core.edit',       'webservice.' . $item->id);
								$canCheckin = $user->authorise('core.admin',      'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
								$canEditOwn = $user->authorise('core.edit.own',   'webservice.' . $item->id) && $item->created_by == $userId;
								$canChange  = $user->authorise('core.edit.state', 'webservice.' . $item->id) && $canCheckin;
								?>
								<tr>
									<td>
										<?php echo JHtml::_('grid.id', $i, $item->id); ?>
									</td>
									<td class="center">
										<?php echo JHtml::_('jgrid.published', $item->state, $i, 'webservices.', $canChange); ?>
									</td>
									<td>
										<?php if ($item->checked_out) : ?>
											<?php echo JHtml::_('jgrid.checkedout', $i, $item->checked_out, $item->checked_out_time, 'webservices.', $canCheckin); ?>
										<?php endif; ?>
										<?php if ($canEdit || $canEditOwn) : ?>
											<a href="<?php echo JRoute::_('index.php?option=com_webservices&task=edit&id=' . $item->id); ?>">
												<?php echo $this->escape($item->title); ?></a>
										<?php else : ?>
											<?php echo $this->escape($item->title); ?>
										<?php endif; ?>
										<span> (<?php echo $item->name; ?> <?php echo $item->version; ?>)</span>
										<br />

										<?php if ($item->xml): ?>
											<?php  $webserviceClientUri = '&webserviceClient=' . $item->client; ?>
											<em><?php echo $item->xml->description; ?></em>
											<br />
											<button
												class="btn btn-xs btn-primary"
												type="button"
												<?php if (\Joomla\Webservices\Xml\XmlHelper::isAttributeTrue($item->xml->operations->documentation, 'authorizationNeeded', true)) : ?>
													disabled="disabled"
												<?php endif; ?>
												data-remote-format="doc"
												data-remote="../index.php?api=Hal&format=doc&option=<?php echo $item->xml->config->name . $webserviceClientUri; ?>"
												data-target="#webservicePreview">
												<i class="icon-file-text"></i>
												<?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_DOCUMENTATION') ?>
											</button>
											<button
												class="btn btn-xs btn-primary"
												type="button"
												<?php if (\Joomla\Webservices\Xml\XmlHelper::isAttributeTrue($item->xml->operations->read, 'authorizationNeeded', true)) : ?>
													disabled="disabled"
												<?php endif; ?>
												data-remote-format="json"
												data-remote="../index.php?api=Hal&option=<?php echo $item->xml->config->name . $webserviceClientUri; ?>"
												data-target="#webservicePreview">
												<i class="icon-file-text"></i>
												<?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_PREVIEW_JSON') ?>
											</button>
										<?php endif; ?>
									</td>
									<td>
										<?php echo JText::_('J' . $item->client); ?>
									</td>
									<td style="word-wrap:break-word;">
										<?php $folder = !empty($item->path) ? '/' . $item->path : ''; ?>
										<?php echo \Joomla\Webservices\Webservices\WebserviceHelper::getWebservicesRelativePath(); ?>
										<strong><?php echo $folder . '/<span class="lc-webservice_file">' . $item->xmlFile; ?></span></strong>

										<?php if (!JFile::exists(\Joomla\Webservices\Webservices\WebserviceHelper::getWebservicesPath() . $folder . '/' . $item->xmlFile)) : ?>
											<span class="label label-danger"><?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_XML_MISSING'); ?></span>
										<?php elseif ($item->xmlHashed != md5($item->xml)) : ?>
											<span class="label label-warning"><?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_XML_CHANGED'); ?></span>
										<?php else : ?>
											<span class="label label-success"><?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_XML_VALID'); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<?php if (!empty($item->scopes)) : ?>
											<?php foreach ($item->scopes as $scope): ?>
												<span class="badge"><?php echo $scope['scopeDisplayName']; ?></span>&nbsp;
											<?php endforeach; ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						<?php endif; ?>
					</table>
					<?php echo $this->pagination->getListFooter(); ?>
				</div>

			</div>
			<div class="tab-pane" id="mainComponentWebservicesXmls">
				<?php if (empty($this->xmlFiles)): ?>
					<br />
					<div class="alert alert-info">
						<button type="button" class="close" data-dismiss="alert">&times;</button>
						<div class="pagination-centered">
							<h3><?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_NO_FILES_AVAILABLE') ?></h3>
						</div>
					</div>
				<?php else : ?>

					<div class="row">
						<div class="col-md-6 well">
							<div class="form-group">
								<div class="control-label">
									<?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_TITLE'); ?>
								</div>
								<div class="controls">
									<input type="file" multiple="multiple" name="webservicesWebservice[]" id="webservicesWebservice" accept="application/xml" class="inputbox" />
									<button
										class="btn btn-success"
										type="button"
										onclick="setWebservice('', '', '', '', 'upload')">
										<i class="icon-upload"></i>
										<?php echo JText::_('JTOOLBAR_UPLOAD') ?>
									</button>
								</div>
							</div>
							<div class="form-group" style="margin-top:40px;margin-bottom: 0;">
								<div class="control-label">
									<?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_ALL'); ?>
								</div>
								<div class="controls">
									<button
										class="btn btn-success lc-install_all_webservices"
										type="button"
										onclick="setWebservice('', 'all', '', '', 'install')">
										<i class="icon-cogs"></i>
										<?php echo JText::_('JTOOLBAR_INSTALL'); ?>
									</button>
									<button
										class="btn btn-danger"
										type="button"
										onclick="setWebservice('', 'all', '', '', 'delete')">
										<i class="icon-remove"></i>
										<?php echo JText::_('JTOOLBAR_DELETE') ?>
									</button>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
					<?php if (empty($this->xmlFiles)): ?>
						<div class="alert alert-info">
							<button type="button" class="close" data-dismiss="alert">&times;</button>
							<div class="pagination-centered">
								<h3><?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_NO_FILES_AVAILABLE') ?></h3>
							</div>
						</div>
					<?php else : ?>

						<?php foreach ($this->xmlFiles as $clients => $webserviceNames): ?>
							<?php $column = 0;; ?>
							<div class='clearfix'></div>
							<h3><?php echo JText::_('J' . $clients); ?></h3>
							<?php foreach ($webserviceNames as $webserviceVersions):
								foreach ($webserviceVersions as $webservice):
									$webserviceClient = \Joomla\Webservices\Webservices\ConfigurationHelper::getWebserviceClient($webservice);
									?>
									<div class="col-md-4 well">
										<h4>
											<?php echo $webservice->name; ?> (<?php echo $webservice->config->name; ?>&nbsp;
											<?php echo !empty($webservice->config->version) ? $webservice->config->version : ''; ?>)
										</h4>
										<table class="table table-striped adminlist">
											<tbody>
											<tr>
												<td>
													<strong><?php echo JText::_('JAUTHOR'); ?>:</strong>
												</td>
												<td>
													<strong><?php echo !empty($webservice->author) ? $webservice->author : ''; ?></strong>
												</td>
											</tr>
											<tr>
												<td>
													<strong><?php echo JText::_('COM_WEBSERVICES_WEBSERVICE_PATH_LABEL'); ?>:</strong>
												</td>
												<td>
													<strong><?php echo $webservice->webservicePath; ?></strong>
												</td>
											</tr>
											<tr>
												<td>
													<strong><?php echo JText::_('JGLOBAL_DESCRIPTION'); ?>:</strong>
												</td>
												<td>
													<strong><?php echo !empty($webservice->description) ? $webservice->description : ''; ?></strong>
												</td>
											</tr>
											</tbody>
										</table>
										<button
											class="btn btn-xs btn-success lc-install_webservice_<?php echo $webserviceClient; ?>_<?php echo $webservice->config->name; ?>"
											type="button"
											onclick="setWebservice('<?php echo $webserviceClient; ?>', '<?php echo $webservice->config->name; ?>', '<?php echo $webservice->config->version; ?>', '<?php echo $webservice->webservicePath; ?>', 'install')">
											<i class="icon-cogs"></i>
											<?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_INSTALL_XML') ?>
										</button>
										<button
											class="btn btn-xs btn-danger"
											type="button"
											onclick="setWebservice('<?php echo $webserviceClient; ?>', '<?php echo $webservice->config->name; ?>', '<?php echo $webservice->config->version; ?>', '<?php echo $webservice->webservicePath; ?>', 'webservices.deleteWebservice')">
											<i class="icon-remove"></i>
											<?php echo JText::_('COM_WEBSERVICES_WEBSERVICES_WEBSERVICE_DELETE_XML') ?>
										</button>
									</div>
									<?php if ((++$column) % 3 == 0 ) : ?>
										</div>
										<div class="row">
									<?php endif; ?>
								<?php endforeach; ?>
							<?php endforeach; ?>
						<?php endforeach; ?>
					<?php endif; ?>
					</div>
					<?php endif; ?>
					<div class="clearfix"></div>
				</div>
			</div>
		<div>
			<input type="hidden" name="return" value="<?php echo $this->return; ?>" />
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="client" id="client" value="" />
			<input type="hidden" name="webservice" id="webservice" value="" />
			<input type="hidden" name="version" id="version" value="" />
			<input type="hidden" name="folder" id="folder" value="" />
			<input type="hidden" name="boxchecked" value="0">
		</div>
		<?php echo JHtml::_('form.token'); ?>
	<div>
</form>
<script type="text/javascript">
	jQuery(document).ready(function () {
		jQuery('#mainTabs a[href="#mainComponentWebservices"]').tab('show');
	});
</script>
