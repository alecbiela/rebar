<?php
/**
*
* Responsible for loading the indexed search class and initiating the reindex command.
* @package Utilities
*/

defined('C5_EXECUTE') or die("Access Denied.");
class Concrete5_Job_IndexSearchAll extends QueueableJob {

	public $jNotUninstallable=1;
	public $jSupportsQueue = true;

	public function getJobName() {
		return t("Index Search Engine - All");
	}
	
	public function getJobDescription() {
		return t("Empties the page search index and reindexes all pages.");
	}

	public function start(Queue $q) {
		Loader::library('database_indexed_search');
		$this->is = new IndexedSearch();

		Loader::model('attribute/categories/collection');
		Loader::model('attribute/categories/file');
		Loader::model('attribute/categories/user');
		$attributes = CollectionAttributeKey::getList();
		$attributes = array_merge($attributes, FileAttributeKey::getList());
		$attributes = array_merge($attributes, UserAttributeKey::getList());
		foreach($attributes as $ak) {
			$ak->updateSearchIndex();
		}

		$db = Loader::db();
		$db->Execute('truncate table PageSearchIndex');
		$r = $db->Execute('select Pages.cID from Pages left join CollectionSearchIndexAttributes csia on Pages.cID = csia.cID where (ak_exclude_search_index is null or ak_exclude_search_index = 0) and cIsActive = 1');
		while ($row = $r->FetchRow()) {
			$q->send($row['cID']);
		}
	}
	
	public function finish(Queue $q) {
		$db = Loader::db();
		$total = $db->GetOne('select count(*) from PageSearchIndex');
		return t('Index updated. %s pages indexed.', $total);
	}

	public function processQueueItem(QueueMessage $msg) {
		$c = Page::getByID($msg->getBody(), 'ACTIVE');
		$cv = $c->getVersionObject();
		if (is_object($cv)) {
			$c->reindex($this->is, true);
		}
	}


}