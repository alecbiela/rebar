<?php
defined('C5_EXECUTE') or die("Access Denied.");
class DashboardFilesController extends Controller {
	public function view() {
		$this->redirect('/dashboard/file_manager/search');
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
