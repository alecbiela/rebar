<?php defined('C5_EXECUTE') or die('Access Denied');

class Concrete5_Library_CacheLocal {

	private static $cacheLocal = null;
	private $cache = array();
	private $enabled = true;
	
	/**
	 * Properties
	 */
	public function getEntries() {
		return $this->cache;
	}

	public function setEnabled($val){
		$this->enabled = $val;
	}

	public function getEnabled(){
		return $this->enabled;
	}

	public static function get(){
		if (null === self::$cacheLocal) {
			self::$cacheLocal = new self;
		}
		return self::$cacheLocal;
	}

	/**
	 * Gets an item from the local cache based on its key
	 * @param $key A cache key to get (returned by Cache::key())
	 * @return mixed|null cache data if it exists
	 */
	public function getValue($key){
		return (isset($this->cache[$key])) ? $this->cache[$key] : null;
	}

	/**
	 * Gets an item from the local cache based on its key
	 * @param $key A cache key to delete (returned by Cache::key())
	 * @return bool true if a value was deleted, false otherwise
	 */
	public function deleteValue($key){
		if(isset($this->cache[$key])){
			unset($this->cache[$key]);
			return true;
		}

		return false;
	}

	/**
	 * Inserts/Updates an item in the local cache based on its key
	 * @param $key A cache key to delete (returned by Cache::key())
	 * @param $data The data to store in the cache
	 * @param $subgroup (optional) a hard-coded string to nest the key into
	 * @return bool true if a value was added/modified, false otherwise
	 */
	public function setValue($key, $data, $subgroup = false){
		if(!$this->enabled) return false;

		$r = (is_object($data)) ? clone $data : $data;

		if($subgroup !== false && is_string($subgroup)){
			$this->cache[$subgroup][$key] = $r;
		} else $this->cache[$key] = $r;

		return true;
	}

	/**
	 * Flushes the local cache
	 */
	public function flushCache(){
		$this->cache = array();
	}
	
	/**
	 * Statics - These are called all over the place
	 * Basically just gets the instance of the local cache and runs the above methods
	 */
	public static function getEntry($type, $id) {
		$loc = CacheLocal::get();
		$key = Cache::key($type, $id);
		return $loc->getValue($key);
	}
		
	public static function delete($type, $id) {
		$loc = CacheLocal::get();
		$key = Cache::key($type, $id);
		return $loc->deleteValue($key);
	}	

	public static function flush() {
		$loc = CacheLocal::get();
		$loc->flushCache();
	}
	
	public static function set($type, $id, $object) {
		$loc = CacheLocal::get();
		$key = Cache::key($type, $id);
		return $loc->setValue($key, $object);
	}
}
