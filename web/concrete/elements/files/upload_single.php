<?php defined('C5_EXECUTE') or die("Access Denied.");
$uh = Loader::helper('concrete/urls');

$valt = Loader::helper('validation/token'); 
if ($mode == 'replace') { ?>
    <div id="ccm-files-add-asset-replace">
        <h3><?php echo t('Upload File')?>:</h3>
        <form method="post" enctype="multipart/form-data" action="/tools/files/importers/single" class="ccm-file-manager-submit-single">
            <input type="file" name="Filedata" size="12" class="ccm-al-upload-single-file" />
            <?php echo $valt->output('upload');?>
            <input type="hidden" name="searchInstance" value="<?php echo $searchInstance?>" />
            <input type="hidden" name="fID" value="<?php echo $fID?>" />
            <img class="ccm-al-upload-single-loader" style="display:none;" src="<?php echo ASSETS_URL_IMAGES?>/dashboard/sitemap/loading.gif" />
            <input class="ccm-al-upload-single-submit btn" type="submit" value="<?php echo t('Upload')?>" />    
        </form>
    </div>
<?php } else { 
    $form = Loader::helper("form");
    $fp = FilePermissions::getGlobal();
    if ($fp->canAddFiles()) {
?>
    <div id="ccm-files-add-asset" class="clearfix">
        <button type="button" id="show_upload_files" class="btn info">
            <i class="icon-upload icon-white" aria-hidden="true"></i> <?= t('Upload Files'); ?>
        </button>
    </div>
    <div id="upload_files_modal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="uploadFilesLabel" aria-hidden="true">
        <div class="modal-header ccm-pane-header">
            <h3 id="uploadFilesLabel">Upload Files</h3>
        </div>
        <div class="modal-body"></div>
        <div class="modal-footer">
            <button style="display: none;" type="button" id="import_remote_start" class="btn primary"><?= t('Get Files'); ?></button>
            <button style="display: none;" type="button" id="import_incoming_start" class="btn primary"><?= t('Start Import'); ?></button>
            <button id="upload_files_close" class="btn" data-dismiss="modal">Close</button>
        </div>
    </div>
    <div id="files_uploaded_modal" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="filesUploadedLabel" aria-hidden="true">
        <div class="modal-header ccm-pane-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="filesUploadedLabel">Uploaded File Summary</h3>
        </div>
        <div class="modal-body"></div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal">Close</button>
        </div>
    </div>
    <script type="text/javascript">
        //Used for tracking file IDs that are uploaded
        var upl_uploadedFiles = [];

        $('#show_upload_files').click(function(e){
            e.preventDefault();
            var bd = (CCM_EDIT_MODE && CCM_EDIT_MODE === true) ? false : 'static';
            var ufModal = $('#upload_files_modal').modal({
                "backdrop": bd,
                "remote": "<?= $uh->getToolsURL('files/import'); ?>?ocID=<?= $ocID; ?>&searchInstance=<?= $searchInstance; ?>"
            });
            ufModal.on('hidden', function(){
                //TODO: Reset the lists within the modal
                //So that the modal is fresh for next time
                var dz = Dropzone.forElement("#dz_upload_multiple");
                if(dz){
                    dz.removeAllFiles();
                }

                if(upl_uploadedFiles.length > 0){
                	setTimeout(function() { 
                		upl_filesUploadedDialog('<?= $searchInstance; ?>');
                	}, 100);
                }

                //removes the remote loaded data to refresh for next time
                $(this).removeData('modal');
            });
            return false;
        });

        function upl_filesUploadedDialog(searchInstance) {
            //prepare list of uploaded files
            var fIDstring='';
            for(var i=0; i<upl_uploadedFiles.length; i++)
                fIDstring=fIDstring+'&fID[]='+upl_uploadedFiles[i];

            //open a new modal with remote set to bulk_properties tool,
            //passing in all uploaded file IDs
            var bd = (CCM_EDIT_MODE && CCM_EDIT_MODE === true) ? false : true;
            var bpModal = $('#files_uploaded_modal').modal({
                "backdrop": bd,
                "remote": "<?= $uh->getToolsURL('files/bulk_properties'); ?>?" + fIDstring + '&uploaded=true&searchInstance=' + searchInstance
            });
            bpModal.on('hidden', function(){
                ccm_deactivateSearchResults(searchInstance);
                $("#ccm-" + searchInstance + "-advanced-search").ajaxSubmit(function(resp) {
                    ccm_parseAdvancedSearchResponse(resp, searchInstance);
                });

                //removes the remote loaded data to refresh for next time
                $(this).removeData('modal');
            });

            //reset the list of uploaded files for next time
            upl_uploadedFiles=[];
        }
    </script>
<?php }
}
?>

