<?php defined('C5_EXECUTE') or die("Access Denied."); 

$form = Loader::helper('form');
$uh = Loader::helper('concrete/urls');
Loader::model('file_set');

//function to display custom imagery for checkbox states, since tri-state checkboxes aren't built in to HTML
//may change this
// state 0 - none of the selected files are in the set
// state 1 - SOME of the selected files are in the set
// state 2 - ALL files are in the set
function checkbox($field, $value, $state, $miscFields = array()) {

	$mf = '';
	if (is_array($miscFields)) {
		foreach($miscFields as $k => $v) {
			$mf .= $k . '="' . $v . '" ';
		}
	}

	$src = ASSETS_URL_IMAGES . '/checkbox_state_' . $state . '.png';
					
	$str = '<input type="hidden" value="' . $state . '" name="' . $field . ':' . $value . '" /><a href="javascript:void(0)" ccm-tri-state-startup="' . $state . '" ccm-tri-state-selected="' . $state . '" ><img width="16" height="16" src="' . $src . '" ' . $mf . ' /></a>';
	return $str;
}
?> 

<div class="ccm-ui">

	<?php 
		$files = array();
		$searchInstance = Loader::helper('text')->entities($_REQUEST['searchInstance']);
		$extensions = array();

		if (is_array($_REQUEST['fID'])) {
			foreach($_REQUEST['fID'] as $fID) {
				$f = File::getByID($fID);
				$fp = new Permissions($f);
				if ($fp->canViewFile()) {
					$files[] = $f;
					$extensions[] = strtolower($f->getExtension());
				}
			}
		} else {
			$f = File::getByID($_REQUEST['fID']);
			$fp = new Permissions($f);
			if ($fp->canViewFile()) {
				$files[] = $f;
				$extensions[] = strtolower($f->getExtension());
			}
		}

		$extensions = array_unique($extensions);
		$sets = array();

		$s1 = FileSet::getMySets();
		foreach($s1 as $fs) {
			
			$foundInSets = 0;

			foreach($files as $f) {
				if ($f->inFileSet($fs)) {
					$foundInSets++;
				}
			}

			if ($foundInSets == 0) {
				$state = 0;	//no files in set
			} else if ($foundInSets == count($files)) {
				$state = 2;	//all files in set
			} else {
				$state = 1;	//some files in set
			}
			
			$fs->state = $state;
			$sets[] = $fs;
		}


		if ($_POST['task'] == 'add_to_sets') {
			
			foreach($_POST as $key => $value) {
			
				if (preg_match('/fsID:/', $key)) {
					$fsIDst = explode(':', $key);
					$fsID = $fsIDst[1];
					
					// so the affected file set is $fsID, the state of the thing is $value
					$fs = FileSet::getByID($fsID);
					$fsp = new Permissions($fs);
					if ($fsp->canAddFile($f)) {
						switch($value) {
							case '0':
								foreach($files as $f) {
									$fs->removeFileFromSet($f);
								}
								break;
							case '1':
								// do nothing
								break;
							case '2':
								foreach($files as $f) {
									$fs->addFileToSet($f);
								}
								break;
						}		
					}			
				}
			}

			if ($_POST['fsNew']) {
				$type = ($_POST['fsNewShare'] == 1) ? FileSet::TYPE_PUBLIC : FileSet::TYPE_PRIVATE;
				$fs = FileSet::createAndGetSet($_POST['fsNewText'], $type);
				//print_r($fs);
				foreach($files as $f) {
					$fs->addFileToSet($f);
				}
			}
			
			exit;
		}
	?>

	<script type="text/javascript">
		$(function() {
			ccm_alSetupSetsForm('<?= $searchInstance; ?>');
		});
	</script>

	<form method="post" id="ccm-<?= $searchInstance; ?>-add-to-set-form" action="<?= $uh->getToolsURL('files/add_to'); ?>">
		<?= $form->hidden('task', 'add_to_sets'); ?>
		<?php foreach($files as $f) { ?>
			<input type="hidden" name="fID[]" value="<?= $f->getFileID();?>" />
		<?php } ?>
		<div class="clear"></div>
		<div class="ccm-search-bar">
			<?= $form->text('fsAddToSearchName', $searchRequest['fsSearchName'], array('autocomplete' => 'off')); ?>
		</div>

		<?php if (count(FileSet::getMySets()) > 0) { ?>
			<div class="clearfix">
				<ul id="ccm-file-search-add-to-sets-list" class="inputs-list">
					<?php foreach($sets as $s) { 
						$displaySet = true;
						
						$pf = new Permissions($s);
						if (!$pf->canAddFiles()) { 
							$displaySet = false;
						} else {
							foreach($extensions as $ext) {
								if (!$pf->canAddFileType($ext)) {
									$displaySet = false;
								}
							}
						}
						
						if ($displaySet) { ?>
							<li class="ccm-file-set-add-cb">
								<label><?= checkbox('fsID', $s->getFileSetID(), $s->state); ?> <span><?= $s->getFileSetName(); ?></span></label>
							</li>
					<?php } 
					} ?>
				</ul>
			</div>
		<?php } else { ?>
			<?= t('You have not created any file sets yet.'); ?>
		<?php } ?>

		<?php if (count($extensions) > 1) { ?>
			<div class="alert-message info"><p><?= t('If a file set does not appear above, you either have no access to add files to it, or it does not accept the file types %s.', implode(', ', $extensions));?></p></div>
		<?php } ?>

		<h3><?= t('Add to New Set'); ?></h3>
		<?= $form->checkbox('fsNew', 1); ?> <?= $form->text('fsNewText', array('style' => 'width: 120px', 'onclick' => '$(\'input[name=fsNew]\').attr(\'checked\',true)'))?> <?= $form->checkbox('fsNewShare', 1, true); ?> <?= t('Make set public'); ?>
		<br/><br/>
		<?php
			$h = Loader::helper('concrete/interface');
			$b1 = $h->submit(t('Update'), false, 'left');
			print $b1;
		?>
	</form>
</div>