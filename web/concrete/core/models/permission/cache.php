<?php
defined('C5_EXECUTE') or die("Access Denied.");
class Concrete5_Model_PermissionCache {
	
	static $enabled = true;

	public static function disable() {
		PermissionCache::$enabled = false;
	}
	
	public static function getResponse($object) {
		if (!PermissionCache::$enabled) {
			return false;
		}
		$cl = CacheLocal::get();
		if ($cl->getEnabled()) {
			$identifier = 'pr:' . get_class($object) . ':' . $object->getPermissionObjectIdentifier();
			if (!is_null($cl->getValue($identifier))) {
				return $cl->getValue($identifier);
			}
		}
	}

	public static function addResponse($object, PermissionResponse $pr) {
		if (!PermissionCache::$enabled) {
			return false;
		}
		$cl = CacheLocal::get();
		if ($cl->getEnabled()) {
			$identifier = 'pr:' . get_class($object) . ':' . $object->getPermissionObjectIdentifier();
			$cl->setValue($identifier, $pr);
		}
	}

	public static function getPermissionAccessObject($paID, PermissionKey $pk) {
		if (!PermissionCache::$enabled) {
			return false;
		}
		$cl = CacheLocal::get();
		if ($cl->getEnabled()) {
			$identifier = 'pao:' . $pk->getPermissionKeyID() . ':' . $paID;
			if (!is_null($cl->getValue($identifier))) {
				return $cl->getValue($identifier);
			}
		}
	}

	public static function addPermissionAccessObject($paID, PermissionKey $pk, $obj) {
		if (!PermissionCache::$enabled) {
			return false;
		}
		$cl = CacheLocal::get();
		if ($cl->getEnabled()) {
			$identifier = 'pao:' . $pk->getPermissionKeyID() . ':' . $paID;
			$cl->setValue($identifier, $obj);
		}
	}
	
	public static function validate(PermissionKey $pk) {
		if (!PermissionCache::$enabled) {
			return -1;
		}
		$cl = CacheLocal::get();
		if (!$cl->getEnabled()) {
			return -1;
		}
		
		$object = $pk->getPermissionObject();
		if (is_object($object)) {
			$identifier = 'pk:' . $pk->getPermissionKeyHandle() . ':' . $object->getPermissionObjectIdentifier();
		} else {
			$identifier = 'pk:' . $pk->getPermissionKeyHandle();
		}

		if (!is_null($cl->getValue($identifier))) {
			return $cl->getValue($identifier);
		}

		return -1;
	}

	public static function addValidate(PermissionKey $pk, $valid) {
		if (!PermissionCache::$enabled) {
			return false;
		}
		$cl = CacheLocal::get();
		if ($cl->getEnabled()) {
			$object = $pk->getPermissionObject();
			if (is_object($object)) {
				$identifier = 'pk:' . $pk->getPermissionKeyHandle() . ':' . $object->getPermissionObjectIdentifier();
			} else {
				$identifier = 'pk:' . $pk->getPermissionKeyHandle();
			}
			$cl->setValue($identifier, $valid);
		}
	}
	
	public static function addAccessObject(PermissionKey $pk, $object, $pa) {
		if (!PermissionCache::$enabled) {
			return false;
		}
		$cl = CacheLocal::get();
		if ($cl->getEnabled()) {
			$identifier = 'pka:' . $pk->getPermissionKeyHandle() . ':' . $object->getPermissionObjectIdentifier();
			$cl->setValue($identifier, $pa);
		}
	}

	public static function clearAccessObject(PermissionKey $pk, $object) {
		if (!PermissionCache::$enabled) {
			return false;
		}
		$cl = CacheLocal::get();
		if ($cl->getEnabled()) {
			$identifier = 'pka:' . $pk->getPermissionKeyHandle() . ':' . $object->getPermissionObjectIdentifier();
			$cl->deleteValue($identifier);
		}
	}

	public static function getAccessObject($pk, $object) {
		if (!PermissionCache::$enabled) {
			return false;
		}
		$cl = CacheLocal::get();
		if ($cl->getEnabled()) {
			$identifier = 'pka:' . $pk->getPermissionKeyHandle() . ':' . $object->getPermissionObjectIdentifier();
			if (!is_null($cl->getValue($identifier))) {
				return $cl->getValue($identifier);
			}
		}
		return false;
	}
	



}
