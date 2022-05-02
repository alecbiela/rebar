<?php defined('C5_EXECUTE') or die('Access Denied');

// use Symfony\Component\Cache\Adapter\ApcuAdapter;
// use Symfony\Component\Cache\Adapter\ArrayAdapter;
// // use Symfony\Component\Cache\Adapter\ChainAdapter;
// use Symfony\Component\Cache\Adapter\CouchbaseBucketAdapter;
// use Symfony\Component\Cache\Adapter\CouchbaseCollectionAdapter;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
// use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\PdoAdapter;
// use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
// use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
// use Symfony\Component\Cache\Adapter\ProxyAdapter;
// use Symfony\Component\Cache\Adapter\RedisAdapter;

use Symfony\Contracts\Cache\ItemInterface;

class Concrete5_Library_Cache {

	private static $cache = null;
	private $adapter = null;
	private $cacheEnabled = true;

	/**
	 * Gets the current cache instance, or creates one if there isn't one
	 * @return Concrete5_Library_Cache
	 */
	public static function getLibrary(){
		if (null === self::$cache) {
			self::$cache = new self;
		}
		return self::$cache;
	}

	private $adapters = array(
		'doctrinedbal'=>'DoctrineDbalAdapter',
		'filesystem'=>'FilesystemTagAwareAdapter',
		'pdo'=>'PdoAdapter'
	);

	//This is the full list of Symfony-supported cache adapters
	//However, they are not all implemented yet.
	/*
	private $adapters = array(
		'apcu'=>'ApcuAdapter', 
		'array'=>'ArrayAdapter',
		'chain'=>'ChainAdapter',
		'couchbasebucket'=>'CouchbaseBucketAdapter', 
		'couchbasecollection'=>'CouchbaseCollectionAdapter',
		'doctrinedbal'=>'DoctrineDbalAdapter',
		'filesystem'=>'FilesystemAdapter',
		'memcached'=>'MemcachedAdapter',
		'pdo'=>'PdoAdapter',
		'phparray'=>'PhpArrayAdapter',
		'phpfiles'=>'PhpFilesAdapter',
		'proxy'=>'ProxyAdapter',
		'redis'=>'RedisAdapter'
	);*/
	
	public static function key($type, $id) {
		return md5($type . $id);
	}
	
	public function __construct() {
		$this->setAdapter();
	}
	
	public function startup() {
		Cache::getLibrary();
	}

	public function getAdapter(){
		return $this->adapter;
	}

	private function setAdapter(){
		$frontendOptions = array(
			'lifetime' => CACHE_LIFETIME,
			'automatic_serialization' => true,
			'cache_id_prefix' => CACHE_ID		
		);
		$backendOptions = array(
			'read_control' => false,
			'cache_dir' => DIR_FILES_CACHE,
			'file_locking' => false
		);
		if (defined('CACHE_BACKEND_OPTIONS')) {
			$opts = unserialize(CACHE_BACKEND_OPTIONS);
			foreach($opts as $k => $v) {
				$backendOptions[$k] = $v;
			}
		}
		if (defined('CACHE_FRONTEND_OPTIONS')) {
			$opts = unserialize(CACHE_FRONTEND_OPTIONS);
			foreach($opts as $k => $v) {
				$frontendOptions[$k] = $v;
			}
		}
		//Fall back to file system cache adapter as default
		if (!defined('CACHE_LIBRARY') || 
			(defined("CACHE_LIBRARY") && CACHE_LIBRARY == "default") ||
			(defined("CACHE_LIBRARY") && !isset($this->adapters[strtolower(CACHE_LIBRARY)]))
		) define('CACHE_LIBRARY', 'filesystem');

		$adapterName = $this->adapters[strtolower(CACHE_LIBRARY)];
		$ad = null;

		switch($adapterName){
			case 'DoctrineDbalAdapter':
				break;
			case 'FilesystemTagAwareAdapter':
				if (defined('DIR_FILES_CACHE')) {
					if (is_dir(DIR_FILES_CACHE) && is_writable(DIR_FILES_CACHE)) {
						$ad = new FilesystemTagAwareAdapter('',$frontendOptions['lifetime'],$backendOptions['cache_dir']);
					}//else throw an error for unreachable cache dir
				}//else throw an error for undefined cache dir
				break;
			case 'PdoAdapter':
				break;
			default:
				//not implemented, throw some kind of error here
				break;
		}

		if(!is_null($ad)){
			$this->adapter = $ad;
			//set the master cache key, for flushing purposes
			$this->adapter->get('MASTER_CACHE_KEY', function(ItemInterface $item){
				$item->expiresAfter(null);
				$item->tag('master_cache');
				return 'cache-enabled';
			});
		}
	}

	public function setCacheEnabled($val){
		$this->cacheEnabled = $val;
	}

	public function getCacheEnabled(){
		return $this->cacheEnabled;
	}
	
	public static function disableCache() {
		$cache = Cache::getLibrary();
		$cache->setCacheEnabled(false);
	}
	
	public static function enableCache() {
		$cache = Cache::getLibrary();
		$cache->setCacheEnabled(true);
	}

	public static function disableLocalCache() {
		CacheLocal::get()->setEnabled(false);
	}
	public static function enableLocalCache() {
		CacheLocal::get()->setEnabled(true);
	}
	
	/** 
	 * Inserts or updates an item to the cache
	 * the cache must always be enabled for (getting remote data, etc..)
	 */	
	public function set($type, $id, $obj, $expire = false) {
		if(!$this->cacheEnabled) return false;

		$loc = CacheLocal::get();
		if ($loc->getEnabled()) {
			if (is_object($obj)) {
				$r = clone $obj;
			} else {
				$r = $obj;
			}
			$loc->setValue(Cache::key($type, $id), $r);
		}
		$cache = Cache::getLibrary()->getAdapter();
		if (is_null($cache)) return false;

		//The callable on cache->get is only executed on a miss, so we need
		//to delete the key preemptively as the value won't be updated if it's not expired
		$key = Cache::key($type, $id);
		$cache->delete($key);
		$cache->get($key, function(ItemInterface $item){
			if($expire !== false) $item->expiresAfter($expire);
			$item->tag('master_cache');

			return $r;
		});
	}
	
	/** 
	 * Retrieves an item from the cache
	 */	
	public function get($type, $id, $mustBeNewerThan = false) {
		if(!$this->cacheEnabled) return false;

		$loc = CacheLocal::get();
		$key = Cache::key($type, $id);
		if ($loc->getEnabled() && array_key_exists($key, $loc->getEntries())) {
			return $loc->getValue($key);
		}
			
		$cache = Cache::getLibrary()->getAdapter();
		if (is_null($cache)) {
			if ($loc->getEnabled()) $loc->setValue($key, false);
			return false;
		}

		// get the cache value in $loaded, or flag as a miss if not found
		$miss = false;
		$loaded = $cache->get($key, function(ItemInterface $item){
			$miss = true;
		});

		// if missed, also remove from local cache and return false
		if ($miss && $loc->getEnabled()) {
			$loc->deleteValue($key);
			return false;
		}
		
		// else, load the value into local cache and return it
		if ($loc->getEnabled()) $loc->setValue($key, $loaded);
		return $loaded;
	}
	
	/** 
	 * Removes an item from the cache
	 */	
	public function delete($type, $id){
		$cache = Cache::getLibrary()->getAdapter();
		$key = Cache::key($type, $id);
		if ($cache) $cache->delete($key);

		$loc = CacheLocal::get();
		if ($loc->getEnabled() && !is_null($loc->getValue($key))) $loc->deleteValue($key);
	}
	
	/** 
	 * Completely flushes the cache
	 */	
	public function flush() {
		$db = Loader::db();
		$r = $db->MetaTables();

		// flush the CSS cache
		if (is_dir(DIR_FILES_CACHE . '/' . DIRNAME_CSS)) {
			$fh = Loader::helper("file");
			$fh->removeAll(DIR_FILES_CACHE . '/' . DIRNAME_CSS);
		}
		
		$pageCache = PageCache::getLibrary();
		if (is_object($pageCache)) {
			$pageCache->flush();
		}
		
		if (in_array('Config', $r)) {
			// clear the environment overrides cache
			$env = Environment::get();
			$env->clearOverrideCache();

			if(in_array('btCachedBlockRecord', $db->MetaColumnNames('Blocks'))) {
				$db->Execute('update Blocks set btCachedBlockRecord = null');
			}
			if (in_array('CollectionVersionBlocksOutputCache', $r)) {
				$db->Execute('truncate table CollectionVersionBlocksOutputCache');
			}
		}
		
		// flush the local cache
		CacheLocal::flush();

		// flush the cache adapter
		// we just clear everything by invalidating the 'master_cache' tag
		// symfony contracts will take care of the rest
		$cache = Cache::getLibrary()->getAdapter();
		if ($cache) {
			$cache->invalidateTags(['master_cache']);
		}

		if (function_exists('apc_clear_cache')) {
			apc_clear_cache();
		}        

		// flush the translations cache
		if (is_dir(DIR_FILES_TRANSLATION_CACHE)) {
			$fh = Loader::helper("file");
			$fh->removeAll(DIR_FILES_TRANSLATION_CACHE);
		}

		Events::fire('on_cache_flush', $cache);
		return true;
	}
		
}
