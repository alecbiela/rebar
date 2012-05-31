<?
defined('C5_EXECUTE') or die("Access Denied.");
class PagePermissionKey extends PermissionKey {
	
	protected $multiplePageArray; // bulk operations
	public function setMultiplePageArray($pages) {
		$this->multiplePageArray = $pages;
	}
	
	public function getPermissionKeyToolsURL($task = false) {
		if (isset($this->multiplePageArray)) {
			$cIDStr = '';
			foreach($this->multiplePageArray as $sc) {
				$cIDStr .= '&cID[]=' . $sc->getCollectionID();
			}
			return parent::getPermissionKeyToolsURL($task) . $cIDStr;
		} else {
			return parent::getPermissionKeyToolsURL($task) . '&cID=' . $this->getPermissionObject()->getCollectionID();
		}
	}

	public function clearPermissionAssignment() {
		$db = Loader::db();
		$db->Execute('update PagePermissionAssignments set paID = 0 where pkID = ? and cID = ?', array($this->pkID, $this->getPermissionObject()->getPermissionsCollectionID()));
	}
	
	public function assignPermissionAccess(PermissionAccess $pa) {
		$db = Loader::db();
		$db->Replace('PagePermissionAssignments', array('cID' => $this->getPermissionObject()->getPermissionsCollectionID(), 'paID' => $pa->getPermissionAccessID(), 'pkID' => $this->pkID), array('cID', 'pkID'), true);
		$pa->markAsInUse();
	}

	public function getPermissionAccessID() {
		$db = Loader::db();
		return $db->GetOne('select paID from PagePermissionAssignments where cID = ? and pkID = ?', array($this->getPermissionObject()->getPermissionsCollectionID(), $this->getPermissionKeyID()));
	}

}

class PagePermissionAccess extends PermissionAccess {


}

class PagePermissionAccessListItem extends PermissionAccessListItem {


}