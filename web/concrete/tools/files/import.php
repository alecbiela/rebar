<?php defined('C5_EXECUTE') or die("Access Denied.");

$u = new User();
$ch = Loader::helper('concrete/file');
$h = Loader::helper('concrete/interface');
$uh = Loader::helper('concrete/urls');
$fh = Loader::helper('validation/file');
Loader::library('file/types');
$form = Loader::helper('form');

$fp = FilePermissions::getGlobal();
if (!$fp->canAddFiles()) die(t("Unable to add files."));
$types = $fp->getAllowedFileExtensions();
$types = $ch->serializeUploadFileExtensions($types);

$searchInstance = Loader::helper('text')->entities($_REQUEST['searchInstance']);
$ocID = 0;

if (Loader::helper('validation/numbers')->integer($_REQUEST['ocID'])) {
	$ocID = $_REQUEST['ocID'];
}

$valt = Loader::helper('validation/token');

$umf = ini_get('upload_max_filesize');
$umf = str_ireplace(array('M', 'K', 'G'), array('MB', 'KB', 'GB'), $umf);

$incoming_contents = $ch->getIncomingDirectoryContents();
?>
<div class="ccm-ui">
	<div class="tabbable">
		<ul class="nav nav-tabs" id="upload_multiple_tab_headers">
			<li class="active"><a href="#ccm-file-add-multiple" data-toggle="tab"><?= t('Upload Multiple'); ?></a></li>
			<li><a href="#ccm-file-add-incoming" data-toggle="tab"><?= t('Add Incoming')?></a></li>
			<li><a href="#ccm-file-add-remote" data-toggle="tab"><?= t('Add Remote Files')?></a></li>
		</ul>
		<div class="tab-content">
			<div id="ccm-file-add-multiple" class="tab-pane active">
				<div style="float: right">
					<div class="help-block" style="margin-top: 11px">
						<?= t('Upload Max: %s.', str_ireplace(array('M', 'K', 'G'), array('MB', 'KB', 'GB'), ini_get('upload_max_filesize'))); ?>
						<?= t('Post Max: %s', str_ireplace(array('M', 'K', 'G'), array('MB', 'KB', 'GB'), ini_get('post_max_size'))); ?>
					</div>
				</div>

				<h3><?= t('Upload Multiple Files'); ?></h3>

				<script type="text/javascript">
					$(function() { 

						var dz = new Dropzone("div#dz_upload_multiple", {
							url: "<?= $uh->getToolsURL('files/importers/dropzone'); ?>",
							maxFilesize: "<?= $umf; ?>",
							maxFiles: 100,
							thumbnailWidth: 110,
							thumbnailHeight: 110,
							thumbnailMethod: 'contain',
							clickable: true,
							hiddenInputContainer: '#upload_files_modal .modal-body',

							//Localize Dropzone using concrete5's t() function
							dictDefaultMessage: "<?= t("Drop files here or click to upload"); ?>",
							dictFallbackMessage: "<?= t("Your browser does not support drag and drop file uploads."); ?>",
							dictFallbackText: "<?= t("Please use the fallback form below to upload your files like in the olden days."); ?>",
							dictFileTooBig: "<?= t("File is too big"). "({{filesize}}MiB). ". t("Max filesize:"). " {{maxFilesize}}MiB."; ?>",
							dictInvalidFileType: "<?= t("You can't upload files of this type."); ?>",
							dictResponseError: "<?= t("Server responded with {{statusCode}} code."); ?>",
							dictCancelUpload: "<?= t("Cancel upload"); ?>",
							dictUploadCanceled: "<?= t("Upload canceled."); ?>",
							dictCancelUploadConfirmation: "<?= t("Are you sure you want to cancel this upload?"); ?>",
							dictRemoveFile: "<?= t("Remove file"); ?>",
							dictRemoveFileConfirmation: null,
							dictMaxFilesExceeded: "<?= t("You can not upload any more files."); ?>",

							//Define custom event listeners for Dropzone here
							init: function(){
								this.on('sending', function(file, xhr, formData){
									formData.append("ccm-session", "<?= session_id(); ?>");
									formData.append("searchInstance", "<?= $searchInstance; ?>");
									formData.append("ocID", "<?= $ocID; ?>");
									formData.append("ccm_token", "<?= $valt->generate("upload"); ?>");
								});
								this.on('queuecomplete', function(){
									$('#upload_files_close').removeAttr('disabled');
								});
								this.on('success', function(file, serverData){
									if(serverData['id'] && upl_uploadedFiles) upl_uploadedFiles.push(serverData['id']);
								});
								this.on('addedfile', function(file){
									$('#upload_files_close').attr('disabled', 'disabled');
								});
							}
						});
					});
				</script>

				<div id="dz_upload_multiple" class="dropzone dz-upload-multiple"></div>
				<div class="ccm-spacer">&nbsp;</div>
				<br/>
			</div>
			<div id="ccm-file-add-incoming" class="tab-pane">

				<h3><?= t('Add from Incoming Directory'); ?></h3>

				<?php if(!empty($incoming_contents)) { ?>
					<form style="margin: 0;" id="ccm-file-manager-multiple-incoming" method="post" action="<?= $uh->getToolsURL('files/importers/incoming_ajax'); ?>">
						<input type="hidden" name="searchInstance" value="<?= $searchInstance; ?>" />
						<input type="hidden" name="ocID" value="<?= $ocID; ?>" />
						<table id="incoming_file_table" class="table table-bordered" width="100%" cellpadding="0" cellspacing="0">
							<thead>
								<tr>
									<th width="10%" valign="middle" class="center theader"><input type="checkbox" id="check_all_imports" name="check_all_imports" onclick="ccm_alSelectMultipleIncomingFiles(this);" value="" /></th>
									<th width="20%" valign="middle" class="center theader"></th>
									<th width="45%" valign="middle" class="theader"><?= t('Filename'); ?></th>
									<th width="25%" valign="middle" class="center theader"><?= t('Size'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($incoming_contents as $filenum=>$file_array) { 
									$ft = FileTypeList::getType($file_array['name']); ?>
									<tr>
										<td width="10%" valign="middle" class="center">
											<?php if($fh->extension($file_array['name'])) { ?>
												<input type="checkbox" name="send_file<?= $filenum; ?>" class="ccm-file-select-incoming" value="<?= $file_array['name']; ?>" />
											<?php } ?>
										</td>
										<td width="20%" valign="middle" class="center"><?= $ft->getThumbnail(1); ?></td>
										<td width="45%" valign="middle"><?= $file_array['name']; ?></td>
										<td width="25%" valign="middle" class="center"><?= Loader::helper('number')->formatSize($file_array['size'], 'KB'); ?></td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
						<label style="cursor: pointer;" for="remove_after_post">
							<input style="display: inline-block; vertical-align: baseline;" type="checkbox" id="remove_after_post" name="removeFilesAfterPost" value="1" /> <?= t('Remove files from "incoming" directory.'); ?>
						</label>
						<?= $valt->output('import_incoming'); ?>
						<p id="incoming_ajax_message" style="margin: 8px 0 0 0;"></p>
					</form>
					<script type="text/javascript">
						$('#import_incoming_start').on('click', function(){
							$('#import_incoming_start, #upload_files_close').attr('disabled', 'disabled');
							$('#incoming_ajax_message').html('').css('display','none');

							$.ajax({
								method: $('#ccm-file-manager-multiple-incoming').attr('method'),
								url: $('#ccm-file-manager-multiple-incoming').attr('action'),
								data: $('#ccm-file-manager-multiple-incoming').serialize(),
								success: function(data, status, xhr){
									if(data.files && data.files.length > 0){
										for(var i=0; i<data.files.length; i++){
											upl_uploadedFiles.push(data.files[i]);
										}
									}
									$('#upload_files_modal').modal('hide');
								},
								error: function(xhr){
									var msg = JSON.parse(xhr.responseText);
									$('#incoming_ajax_message').html('<span style="color: red;">Error: '+msg.error+'</span>').slideDown(300);
								},
								complete: function(){
									//re-enable button
									$('#import_incoming_start, #upload_files_close').removeAttr('disabled');
								}
							});
						});
					</script>
				<?php } else { ?>
					<?= t('No files found in %s', DIR_FILES_INCOMING); ?>
				<?php } ?>
			</div>
			<div id="ccm-file-add-remote" class="tab-pane">

				<h3><?php echo t('Add From Remote URL')?></h3>

				<form method="POST" id="ccm-file-manager-multiple-remote" action="<?= $uh->getToolsURL('files/importers/remote_ajax'); ?>">
					<input type="hidden" name="searchInstance" value="<?= $searchInstance; ?>" />
					<input type="hidden" name="ocID" value="<?= $ocID; ?>" />
					<p><?= t('Enter URL to valid file(s)'); ?></p>
					<?php echo $valt->output('import_remote');?>

					<?= $form->text('url_upload_1', array('style' => 'width:calc(100% - 10px);')); ?><br/><br/>
					<?= $form->text('url_upload_2', array('style' => 'width:calc(100% - 10px);')); ?><br/><br/>
					<?= $form->text('url_upload_3', array('style' => 'width:calc(100% - 10px);')); ?><br/><br/>
					<?= $form->text('url_upload_4', array('style' => 'width:calc(100% - 10px);')); ?><br/><br/>
					<?= $form->text('url_upload_5', array('style' => 'width:calc(100% - 10px);')); ?><br/>
					<p id="remote_ajax_message" style="margin: 8px 0 0 0;"></p>
				</form>
				<script type="text/javascript">
						$('#import_remote_start').on('click', function(){
							$('#import_remote_start, #upload_files_close').attr('disabled', 'disabled');
							$('#remote_ajax_message').html('').css('display','none');

							$.ajax({
								method: $('#ccm-file-manager-multiple-remote').attr('method'),
								url: $('#ccm-file-manager-multiple-remote').attr('action'),
								data: $('#ccm-file-manager-multiple-remote').serialize(),
								success: function(data, status, xhr){
									if(data.files && data.files.length > 0){
										for(var i=0; i<data.files.length; i++){
											upl_uploadedFiles.push(data.files[i]);
										}
									}
									$('#upload_files_modal').modal('hide');
								},
								error: function(xhr){
									var msg = JSON.parse(xhr.responseText);
									$('#remote_ajax_message').html('<span style="color: red;">Error: '+msg.error+'</span>').slideDown(300);
									
									//with remote uploading, some files may upload before an error is thrown. If so, their IDs will be passed.
									if(data.files && data.files.length > 0){
										for(var i=0; i<data.files.length; i++){
											upl_uploadedFiles.push(data.files[i]);
										}
									}
								},
								complete: function(){
									//re-enable button
									$('#import_remote_start, #upload_files_close').removeAttr('disabled');
								}
							});
						});
					</script>
			</div>
		</div>
	</div>
</div>
<?php //handles switching tabs to dynamically show/hide buttons for uploading in modal footer ?>
<script type="text/javascript">
	$('#upload_multiple_tab_headers li a').click(function(){
		switch($(this).attr('href')){
			case '#ccm-file-add-multiple':
				$('#import_incoming_start').css('display','none');
				$('#import_remote_start').css('display','none');
				break;
			case '#ccm-file-add-incoming':
				$('#import_incoming_start').css('display','inline-block');
				$('#import_remote_start').css('display','none');
				break;
			case '#ccm-file-add-remote':
				$('#import_incoming_start').css('display','none');
				$('#import_remote_start').css('display','inline-block');
				break;
		}
	});
</script>
