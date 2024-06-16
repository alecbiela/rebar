<?php
defined('C5_EXECUTE') or die("Access Denied.");
class DashboardFilesAddSetController extends Controller {

	public $helpers = array('form','validation/token','concrete/interface'); 

	public function do_add() {
		extract($this->getHelperObjects());
		Loader::model('file_set');
		
		if (!$validation_token->validate("file_sets_add")) {
			$this->set('error', array($validation_token->getErrorMessage()));
			return;
		}
		
		if (!trim($this->post('file_set_name'))) {
			$this->set('error', array(t('Please Enter a Name')));
			return;
		}
		$setName = trim($this->post('file_set_name'));
		if (preg_match('/[<>;{}?"`]/i', $setName)) {
			$this->set('error', array(t('File Set Name cannot contain the characters: %s', Loader::helper('text')->entities('<>;{}?"`'))));
			return;
		}

		//print('<pre>');print_r(get_included_files());print('</pre>');
		$u = new User();				
		$file_set 			= new FileSet();
		//AS: Adodb Active record is complaining a ?/value array mismatch unless
		//we explicatly set the primary key ID field to null		
		$file_set->fsID		= null;
		$file_set->fsName 	= $setName;
		$file_set->fsType 	= FileSet::TYPE_PUBLIC;
		$file_set->uID		= $u->getUserID();
		$file_set->fsOverrideGlobalPermissions = ($this->post('fsOverrideGlobalPermissions') == 1) ? 1 : 0;
		$file_set->save();
		$this->redirect('/dashboard/files/sets', 'file_set_added');		
	}

	public function on_start(){
		$hh = Loader::helper('html');
		$v = View::getInstance();
		$v->addFooterItem($hh->javascript('custom-fmpopup.js'));
		$v->addHeaderItem($hh->javascript('dropzone.min.js'));
		$v->addHeaderItem($hh->javascript('bootstrap/bootstrap.modal.js'));
		$v->addHeaderItem($hh->javascript('bootstrap/bootstrap.tab.js'));
		$v->addHeaderItem($hh->css('dropzone.min.css'));
		$v->addHeaderItem($hh->css('bootstrap.modals.css'));
		$v->addHeaderItem($hh->css('dropzone-custom.css'));
	}
	
}
