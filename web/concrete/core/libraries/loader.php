<?php defined('C5_EXECUTE') or die("Access Denied.");

/**
 * @package Core
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */

/**
 * A wrapper for loading core files, libraries, applications and models. Whenever possible the loader class should be used because it will always know where to look for the proper files, in the proper order.
 * @package Core
 * @author Andrew Embler <andrew@concrete5.org>
 * @category Concrete
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */
 
 class Concrete5_Library_Loader {
		
		static $autoloadClasses = array();
		
		/** 
		 * Loads a library file, either from the site's files or from Concrete's
		 */
		public static function library($lib, $pkgHandle = null) {
			$env = Environment::get();
			require_once($env->getPath(DIRNAME_LIBRARIES . '/' . $lib . '.php', $pkgHandle));
		}

		/** 
		 * Loads a job file, either from the site's files or from Concrete's
		 */
		public static function job($job, $pkgHandle = null) {
			$env = Environment::get();
			require_once($env->getPath(DIRNAME_JOBS . '/' . $job . '.php', $pkgHandle));
		}

		/** 
		 * Loads a model from either an application, the site, or the core Concrete directory
		 */
		public static function model($mod, $pkgHandle = null) {
			$env = Environment::get();
			$r = self::legacyModel($mod);
			if (!$r) {
				require_once($env->getPath(DIRNAME_MODELS . '/' . $mod . '.php', $pkgHandle));
			}
		}
		
		protected static function legacyModel($model) {
			switch($model) {
				case 'collection_attributes':
					self::model('attribute/categories/collection');
					return true;
					break;
				case 'user_attributes':
					self::model('attribute/categories/user');
					return true;
					break;
				case 'file_attributes':
					self::model('attribute/categories/file');
					return true;
					break;
				default:
					return false;
					break;
			}
		}
		
		/** 
		 * @access private
		 */
		public function packageElement($file, $pkgHandle, $args = null) {
			self::element($file, $args, $pkgHandle);
		}

		/** 
		 * Loads an element from C5 or the site
		 */
		public static function element($_file, $args = null, $_pkgHandle= null) {
			if (is_array($args)) {
				$collisions = array_intersect(array('_file', '__file', '__function', '_pkgHandle'), array_keys($args));
				if ($collisions) {
					throw new Exception(t("Illegal variable name '%s' in element args.", implode(', ', $collisions)));
				}
				$collisions = null;
			} else {
				$args = array();
			}
			$__file = Environment::get()->getPath(DIRNAME_ELEMENTS . '/' . $_file . '.php', $_pkgHandle);
			if (isset($this)) {
				extract($args);
				include $__file;
			} else {
				$thisObject = null;
				foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS) as $backtrace) {
					if (isset($backtrace['object'])) {
						$thisObject = $backtrace['object'];
						break;
					}
				}
				unset($backtrace);
				if ($thisObject === null) {
					unset($thisObject);
					extract($args);
					include $__file;
				} else {
					$closure = Closure::bind(
						function() use ($_file, $args, $_pkgHandle, $__file) {
							extract($args);
							include $__file;
							
						},
						$thisObject
					);
					$closure();
				}
			}
		}

		 /**
		 * Loads a tool file from c5 or site
		 */
		public static function tool($file, $args = null, $pkgHandle= null) {
		   if (is_array($args)) {
			   extract($args);
		   }
			$env = Environment::get();
			require_once($env->getPath(DIRNAME_TOOLS . '/' . $file . '.php', $pkgHandle));
		}
		
		/** 
		 * Registers a component with concrete5's autoloader.
		 */
		public static function registerAutoload($classes) {
			foreach($classes as $class => $data) {	
				if (strpos($class, ',') > -1) {
					$subclasses = explode(',', $class);
					foreach($subclasses as $subclass) {
						self::$autoloadClasses[$subclass] = $data;
					}
				} else {
					self::$autoloadClasses[$class] = $data;
				}
			}				
		}
		
		protected static function getFileFromCorePath($found) {
			$cl = array_key_exists($found, self::$autoloadClasses) ? self::$autoloadClasses[$found] : false;
			if ($cl) {
				$file = $cl[1];
			} else {
				$file = str_replace('_', '/', $found);
				$path = explode('/', $file);
				if (count($path) > 0) {
					$file = '';
					for ($i = 0; $i < count($path); $i++) {
						$p = $path[$i];
						$file .= ConcreteObject::uncamelcase($p);
						if (($i + 1) < count($path)) {
							$file .= '/';
						}							
					}
				} else {
					$file = ConcreteObject::uncamelcase($file);				
				}
			}
			return $file;
		}
		
		public static function autoloadCore($class) {
			if (strpos($class, $m = 'Concrete5_Model_') === 0) {
				$file = self::getFileFromCorePath(substr($class, strlen($m)));
				require_once(DIR_BASE_CORE . '/' . DIRNAME_CORE_CLASSES . '/' . DIRNAME_MODELS . '/' . $file . '.php');
			}
			elseif (strpos($class, $m = 'Concrete5_Library_') === 0) {
				$file = self::getFileFromCorePath(substr($class, strlen($m)));
				require_once(DIR_BASE_CORE . '/' . DIRNAME_CORE_CLASSES . '/' . DIRNAME_LIBRARIES . '/' . $file . '.php');
			}
			elseif (strpos($class, $m = 'Concrete5_Helper_') === 0) {
				$file = self::getFileFromCorePath(substr($class, strlen($m)));
				require_once(DIR_BASE_CORE . '/' . DIRNAME_CORE_CLASSES . '/' . DIRNAME_HELPERS . '/' . $file . '.php');
			}
			elseif (strpos($class, $m = 'Concrete5_Controller_Block_') === 0) {
				$file = self::getFileFromCorePath(substr($class, strlen($m)));
				require_once(DIR_BASE_CORE . '/' . DIRNAME_CORE_CLASSES . '/' . DIRNAME_CONTROLLERS . '/' . DIRNAME_BLOCKS . '/' . $file. '.php');
			}
			elseif (strpos($class, $m = 'Concrete5_Controller_PageType_') === 0) {
				$file = self::getFileFromCorePath(substr($class, strlen($m)));
				require_once(DIR_BASE_CORE . '/' . DIRNAME_CORE_CLASSES . '/' . DIRNAME_CONTROLLERS . '/' . DIRNAME_PAGE_TYPES . '/' . $file. '.php');
			}
			elseif (strpos($class, $m = 'Concrete5_Controller_AttributeType_') === 0) {
				$file = self::getFileFromCorePath(substr($class, strlen($m)));
				require_once(DIR_BASE_CORE . '/' . DIRNAME_CORE_CLASSES . '/' . DIRNAME_MODELS . '/' . DIRNAME_ATTRIBUTES . '/' . DIRNAME_ATTRIBUTE_TYPES . '/' . $file . '.php');
			}
			elseif (strpos($class, $m = 'Concrete5_Controller_') === 0) {
				$file = self::getFileFromCorePath(substr($class, strlen($m)));
				require_once(DIR_BASE_CORE . '/' . DIRNAME_CORE_CLASSES . '/' . DIRNAME_CONTROLLERS . '/' . DIRNAME_PAGES . '/' . $file . '.php');
			}
			elseif (strpos($class, $m = 'Concrete5_Job_') === 0) {
				$file = self::getFileFromCorePath(substr($class, strlen($m)));
				require_once(DIR_BASE_CORE . '/' . DIRNAME_CORE_CLASSES . '/' . DIRNAME_JOBS . '/' . $file . '.php');
			}
		}
		
		/** 
		 * @private
		 */
		public static function autoload($class) {
			$classes = self::$autoloadClasses;
			$cl = $classes[$class];
			if ($cl) {
				call_user_func_array(array(__CLASS__, $cl[0]), array_slice($cl, 1, 2));
			} else {
				/* lets handle some things slightly more dynamically */				
				if (strpos($class, 'BlockController') > 0) {
					$class = substr($class, 0, strpos($class, 'BlockController'));
					$handle = ConcreteObject::uncamelcase($class);
					self::block($handle);
				} else if (strpos($class, 'AttributeType') > 0) {
					$class = substr($class, 0, strpos($class, 'AttributeType'));
					$handle = ConcreteObject::uncamelcase($class);
					$at = AttributeType::getByHandle($handle);
				} else 	if (strpos($class, 'Helper') > 0) {
					$class = substr($class, 0, strpos($class, 'Helper'));
					$handle = ConcreteObject::uncamelcase($class);
					$handle = preg_replace('/^site_/', '', $handle);
					self::helper($handle);
				}
			}
		}
		
		/** 
		 * Loads a block's controller/class into memory. 
		 * <code>
		 * <?php self::block('autonav'); ?>
		 * </code>
		 */
		public static function block($bl) {
			$db = self::db();
			$pkgHandle = $db->GetOne('select pkgHandle from Packages left join BlockTypes on BlockTypes.pkgID = Packages.pkgID where BlockTypes.btHandle = ?', array($bl));
			$env = Environment::get();
			require_once($env->getPath(DIRNAME_BLOCKS . '/' . $bl . '/' . FILENAME_BLOCK_CONTROLLER, $pkgHandle));
		}
		
		/** 
		 * Loads the various files for the database abstraction layer. We would bundle these in with the db() method below but
		 * these need to be loaded before the models which need to be loaded before db() 
		 */
		public static function database() {
			global $ADODB_NEWCONNECTION;
			$ADODB_NEWCONNECTION = 'c5_db_driver';
			function& c5_db_driver($driver){
				return new DatabaseConnection();
			}
			require(DIR_BASE_CORE . '/libraries/3rdparty/Rebar/vendor/adodb/adodb-php/adodb.inc.php');
			require(DIR_BASE_CORE . '/libraries/3rdparty/Rebar/vendor/adodb/adodb-php/adodb-exceptions.inc.php');
			require(DIR_BASE_CORE . '/libraries/3rdparty/Rebar/vendor/adodb/adodb-php/adodb-active-record.inc.php');
			require(DIR_BASE_CORE . '/libraries/3rdparty/Rebar/vendor/adodb/adodb-php/adodb-xmlschema03.inc.php');
			require(DIR_BASE_CORE . '/libraries/database.php');
			require(DIR_BASE_CORE . '/libraries/database_connection.php');
		}
		
		/** 
		 * Returns the database object, or loads it if not yet created
		 * <code>
		 * <?php
		 * $db = Loader::db();
		 * $db->query($sql);
		 * </code>
		 * @return ADOConnection
		 */
		public static function db($server = null, $username = null, $password = null, $database = null, $create = false, $autoconnect = true) {
			static $_dba;
			if ((!isset($_dba) || $create) && ($autoconnect)) {
				if ($server == null && defined('DB_SERVER')) {	
					$dsn = DB_TYPE . '://' . DB_USERNAME . ':' . rawurlencode(DB_PASSWORD) . '@' . rawurlencode(DB_SERVER) . '/' . DB_DATABASE;
				} else if ($server) {
					$dsn = DB_TYPE . '://' . $username . ':' . rawurlencode($password) . '@' . rawurlencode($server) . '/' . $database;
				}

				if (isset($dsn) && $dsn) {

					$_dba = @NewADOConnection($dsn);
					if (is_object($_dba)) {
						$_dba->setFetchMode(ADODB_FETCH_ASSOC);
						if (DB_CHARSET != '') {
							$names = 'SET NAMES \'' . DB_CHARSET . '\'';
							if (DB_COLLATE != '') {
								$names .= ' COLLATE \'' . DB_COLLATE . '\'';
							}
							$_dba->Execute($names);
						}
						 try {
							$sqlMode = $_dba->GetOne('select @@SESSION.sql_mode');
							if (is_string($sqlMode)) {
								$sqlMode = preg_split('/\s*,\s*'.'/', $sqlMode);
								foreach (array(
									'ONLY_FULL_GROUP_BY',
									'NO_ENGINE_SUBSTITUTION',
									'STRICT_TRANS_TABLES',
									'STRICT_ALL_TABLES',
								) as $stripSqlMode) {
									$i = array_search($stripSqlMode, $sqlMode);
									if ($i !== false) {
										unset($sqlMode[$i]);
									}
								}
								$sqlMode = implode(',', $sqlMode);
								$_dba->Execute('SET sql_mode=' . $_dba->qstr($sqlMode));
							}
						} catch (Exception $foo) {
						}
						ADOdb_Active_Record::SetDatabaseAdapter($_dba);
					} else if (defined('DB_SERVER')) {
						$v = View::getInstance();
						$v->renderError(t('Unable to connect to database.'), t('A database error occurred while processing this request.'));
					}
				} else {
					return false;
				}
			}
			
			//$_dba->LogSQL(true);
			//global $ADODB_PERF_MIN;
			//$ADODB_PERF_MIN = 0;

			return $_dba;
		}
		
		/** 
		 * Loads a helper file. If the same helper file is contained in both the core concrete directory and the site's directory, it will load the site's first, which could then extend the core.
		 */
		public static function helper($file, $pkgHandle = false) {
		
			static $instances = array();

			$class = ConcreteObject::camelcase($file) . "Helper";
			$siteclass = "Site" . ConcreteObject::camelcase($file) . "Helper";

			if (array_key_exists($class, $instances)) {
            	$instance = $instances[$class];
			} else if (array_key_exists($siteclass, $instances)) {
            	$instance = $instances[$siteclass];
			} else {

				$env = Environment::get();
				$f1 = $env->getRecord(DIRNAME_HELPERS . '/' . $file . '.php', $pkgHandle);
				require_once($f1->file);
				if ($f1->override) {
					if (class_exists($siteclass, false)) {
						$class = $siteclass;
					}
				} else if ($pkgHandle) {
					$pkgclass = ConcreteObject::camelcase($pkgHandle . '_' . $file) . "Helper";
					if (class_exists($pkgclass, false)) {
						$class = $pkgclass;
					}
				}

	            $instances[$class] = new $class();
    	        $instance = $instances[$class];
			}
			
			if(method_exists($instance,'reset')) {
				$instance->reset();
			}
			
			return $instance;
		}
		
		/**
		 * @access private
		 */
		public function package($pkgHandle) {
			// loads and instantiates the object
			$env = Environment::get();
			$path = $env->getPath(FILENAME_PACKAGE_CONTROLLER, $pkgHandle);

			if (file_exists($path)) {
				require_once($path);
				$isValidPkgHandle = true;
			}
			else {
				$msg = t('Warning - failed to load package with pkgHandle \'%1$s\'. Could not find package controller file: \'%2$s\'',
					$pkgHandle, $path);
				Log::addEntry($msg, 'packages');
			}

			$class = ConcreteObject::camelcase($pkgHandle) . "Package";
			if (class_exists($class)) {
				$cl = new $class;
				return $cl;
			}
			else {
				// $class might not exist due to an invalid $pkgHandle (thus a wrong 
				// $class value), in which case a more relevant message will already be logged.
				if ($isValidPkgHandle) {
					$msg = t('Warning - failed to load package in directory \'%1$s\'. The package controller does not define the expected class: \'%2$s\'',
						$pkgHandle, $class);
					Log::addEntry($msg, 'packages');
				}
			}
		}
		
		/**
		 * @access private
		 */
		public function startingPointPackage($pkgHandle) {
			// loads and instantiates the object
			$dir = (is_dir(DIR_STARTING_POINT_PACKAGES . '/' . $pkgHandle)) ? DIR_STARTING_POINT_PACKAGES : DIR_STARTING_POINT_PACKAGES_CORE;
			if (file_exists($dir . '/' . $pkgHandle . '/' . FILENAME_PACKAGE_CONTROLLER)) {
				require_once($dir . '/' . $pkgHandle . '/' . FILENAME_PACKAGE_CONTROLLER);
				$class = ConcreteObject::camelcase($pkgHandle) . "StartingPointPackage";
				if (class_exists($class)) {
					$cl = new $class;
					return $cl;
				}
			}
		}
		

		/** 
		 * Gets the path to a particular page type controller
		 */
		public static function pageTypeControllerPath($ctHandle) {			
			self::model('collection_types');
			$ct = CollectionType::getByHandle($ctHandle);
			if (!is_object($ct)) {
				return false;
			}			
			$pkgHandle = $ct->getPackageHandle();
			$env = Environment::get();
			$path = $env->getPath(DIRNAME_CONTROLLERS . '/' . DIRNAME_PAGE_TYPES . '/' . $ctHandle . '.php', $pkgHandle);
			if (file_exists($path)) {
				return $path;
			}
		}
		
		/** 
		 * Loads a controller for either a page or view
		 */
		public static function controller($item) {
			
			$include = false;
			
			if (is_string($item)) {
				$db = self::db();
				if (is_object($db)) {
					try {
						$_item = Page::getByPath($item);
						if ($_item->isError()) {
							$path = $item;
						} else {
							$item = $_item;
						}
					} catch(Exception $e) {
						$path = $item;
					}
				} else {
					$path = $item;
				}
			}
			
			if ($item instanceof Page) {
				$c = $item;
				if ($c->getCollectionTypeID() > 0) {					
					$ctHandle = $c->getCollectionTypeHandle();
					$path = self::pageTypeControllerPath($ctHandle, $item->getPackageHandle());
					if ($path != false) {
						require_once($path);
						$class = ConcreteObject::camelcase($ctHandle) . 'PageTypeController';
					}
				} else if ($c->isGeneratedCollection()) {
					$file = $c->getCollectionFilename();
					if ($file != '') {
						// strip off PHP suffix for the $path variable, which needs it gone
						if (strpos($file, '/' . FILENAME_COLLECTION_VIEW) !== false) {
							$path = substr($file, 0, strpos($file, '/'. FILENAME_COLLECTION_VIEW));
						} else {
							$path = substr($file, 0, strpos($file, '.php'));
						}
					}
				}
			} else if ($item instanceof Block || $item instanceof BlockType) {
				
				$class = ConcreteObject::camelcase($item->getBlockTypeHandle()) . 'BlockController';
				if ($item instanceof BlockType) {
					$controller = new $class($item);
				}
				
				if ($item instanceof Block) {
					$c = $item->getBlockCollectionObject();
				}				
			}
			
			$controllerFile = $path . '.php';

			if ($path != '') {
				
				$env = Environment::get();
				$pkgHandle = false;
				if (is_object($item)) {
					$pkgHandle = $item->getPackageHandle();
				}
				
				$f1 = $env->getPath(DIRNAME_CONTROLLERS . $path . '/' . FILENAME_COLLECTION_CONTROLLER, $pkgHandle);
				$f2 = $env->getPath(DIRNAME_CONTROLLERS . $controllerFile, $pkgHandle);
				if (file_exists($f2)) {
					$include = true;
					require_once($f2);
				} else if (file_exists($f1)) {
					$include = true;
					require_once($f1);
				}
				
				if ($include) {
					$class = ConcreteObject::camelcase($path) . 'Controller';
				}
			}
			
			if (!isset($controller)) {
				if (isset($class) && class_exists($class)) {
					// now we get just the filename for this guy, so we can extrapolate
					// what our controller is named
					$controller = new $class($item);
				} else {
					$controller = new Controller($item);
				}
			}
			
			if (isset($c) && is_object($c)) {
				$controller->setCollectionObject($c);
			}
			
			return $controller;
		}

	}
