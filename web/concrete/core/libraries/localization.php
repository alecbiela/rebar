<?php
	defined('C5_EXECUTE') or die("Access Denied.");

	use Symfony\Component\Intl\Locales;
	use Symfony\Component\Translation\Translator;
	use Symfony\Component\Translation\Loader\MoFileLoader;

	class Concrete5_Library_Localization {

		private static $loc = null;

		public function init() {
			$loc = Localization::getInstance();
			$loc->getTranslate();
		}

		/**
		* @return Localization
		*/
		public static function getInstance() {
			if (null === self::$loc) {
				self::$loc = new self;
			}
			return self::$loc;
		}

		/** Changes the currently active locale
		* @param string $locale The locale to activate (for example: 'en_US')
		* @param bool $coreOnly = false Set to true to load only the core translation files, set to false (default) to load also packages and site locale translations
		*/
		public static function changeLocale($locale, $coreOnly = false) {
			$loc = Localization::getInstance();
			$loc->setLocale($locale, $coreOnly);
		}
		/** Returns the currently active locale
		* @return string
		* @example 'en_US'
		*/
		public static function activeLocale() {
			$loc = Localization::getInstance();
			return $loc->getLocale();
		}
		/** Returns the language for the currently active locale
		* @return string
		* @example 'en'
		*/
		public static function activeLanguage() {
			return current(explode('_', self::activeLocale()));
		}

		/** The current Translator instance (null if and only if locale is en_US)
		* @var Symfony\Component\Translation\Translator|null
		*/
		protected $translate;

		public function __construct() {
			Loader::library('3rdparty/Zend/Date');
			$cache = Cache::getLibrary();
			if (is_object($cache)) {
				Zend_Date::setOptions(array('cache'=>$cache));
			}
			$locale = defined('ACTIVE_LOCALE') ? ACTIVE_LOCALE : 'en_US';
			$this->setLocale($locale);
			Zend_Date::setOptions(array('format_type' => 'php'));
		}

		/** Changes the currently active locale
		* @param string $locale The locale to activate (for example: 'en_US')
		* @param bool $coreOnly = false Set to true to load only the core translation files, set to false (default) to load also packages and site locale translations
		*/
		public function setLocale($locale, $coreOnly = false) {
			$localeNeededLoading = false;
			if (($locale == 'en_US') && (!ENABLE_TRANSLATE_LOCALE_EN_US)) {
				if(isset($this->translate)) {
					unset($this->translate);
				}
				return;
			}
			if (is_dir(DIR_LANGUAGES . '/' . $locale)) {
				$languageDir = DIR_LANGUAGES . '/' . $locale;
			}
			elseif (is_dir(DIR_LANGUAGES_CORE . '/' . $locale)) {
				$languageDir = DIR_LANGUAGES_CORE . '/' . $locale;
			}
			
			// don't try to load translations that don't exist
			if (!file_exists($languageDir)) {
				return;
			}
			
			$options = array(
				'adapter' => 'mo',
				'content' => $languageDir.'/LC_MESSAGES/messages.mo',
				'locale'  => $locale
			);
			if (defined('TRANSLATE_OPTIONS')) {
				$_options = unserialize(TRANSLATE_OPTIONS);
				if (is_array($_options)) {
					$options = array_merge($options, $_options);
				}
			}

			if (is_null($this->translate)) {
				$this->translate = new Translator($options['locale'],null,DIR_FILES_TRANSLATION_CACHE);
				$this->translate->addLoader($options['adapter'], new MoFileLoader());
				$localeNeededLoading = true;
			} else {
				if (!in_array($options['locale'], $this->translate->getCatalogues())) $localeNeededLoading = true;
				$this->translate->setLocale($options['locale']);
			}
			$this->translate->addResource($options['adapter'], $options['content'], $options['locale']);
			$this->translate->getCatalogue($options['locale']); //forces load of message bank if it's not loaded already

			if(!$coreOnly) {
				$this->addSiteInterfaceLanguage($locale);
				global $config_check_failed;
				if(!(isset($config_check_failed) && $config_check_failed)) {
					foreach(PackageList::get(1)->getPackages() as $p) {
						// skip packages that have been removed on the file system to avoid an endless loop					
						if (!file_exists($p->getPackagePath())) {
							continue;
						}
						$pkg = Loader::package($p->getPackageHandle());
						if (is_object($pkg)) {
							$pkg->setupPackageLocalization($locale, null, $this->translate);
						}
					}
					//reload the catalogue to pick up any new translations added by packages
					$localeNeededLoading = true;
					$this->translate->getCatalogue($locale);
				}
			}
			if($localeNeededLoading) {
				Events::fire('on_locale_load', $locale);
			}
		}

		public function getLocale() {
			return isset($this->translate) ? $this->translate->getLocale() : 'en_US';
		}

		/** Returns the current Translator instance (null if and only if locale is en_US)
		* @var Symfony\Component\Translation\Translator|null
		*/
		public function getActiveTranslateObject() {
			return $this->translate;
		}

		/** Loads the site interface locale.
		* @param string $locale = null The locale to load (for instance: 'en_US'). If empty we'll use the currently active locale
		*/
		public function addSiteInterfaceLanguage($locale = null) {
			if (is_object($this->translate)) {
				if(!(is_string($locale) && strlen($locale))) {
					$locale = $this->translate->getLocale();
				}
				$path = DIR_LANGUAGES_SITE_INTERFACE . '/' . $locale . '.mo';
				if(is_file($path)) {
					$this->translate->addTranslation($path, $locale);
				}
			}
		}

		/** Returns the current Translator instance (null if and only if locale is en_US)
		* @var Symfony\Component\Translation\Translator|null
		*/
		public static function getTranslate() {
			$loc = Localization::getInstance();
			return $loc->getActiveTranslateObject();
		}

		public static function getAvailableInterfaceLanguages() {
			$languages = array();
			$fh = Loader::helper('file');

			if (file_exists(DIR_LANGUAGES)) {
				$contents = $fh->getDirectoryContents(DIR_LANGUAGES);
				foreach($contents as $con) {
					if (is_dir(DIR_LANGUAGES . '/' . $con) && file_exists(DIR_LANGUAGES . '/' . $con . '/LC_MESSAGES/messages.mo')) {
						$languages[] = $con;
					}
				}
			}
			if (file_exists(DIR_LANGUAGES_CORE)) {
				$contents = $fh->getDirectoryContents(DIR_LANGUAGES_CORE);
				foreach($contents as $con) {
					if (is_dir(DIR_LANGUAGES_CORE . '/' . $con) && file_exists(DIR_LANGUAGES_CORE . '/' . $con . '/LC_MESSAGES/messages.mo') && (!in_array($con, $languages))) {
						$languages[] = $con;
					}
				}
			}

			return $languages;
		}

		/**
		 * Generates a list of all available languages and returns an array like
		 * [ "de_DE" => "Deutsch (Deutschland)",
		 *   "en_US" => "English (United States)",
		 *   "fr_FR" => "Français (France)"]
		 * The result will be sorted by the key.
		 * If the $displayLocale is set, the language- and region-names will be returned in that language
		 * @param string $displayLocale Language of the description
		 * @return Array An associative Array with locale as the key and description as content
		 */
		public static function getAvailableInterfaceLanguageDescriptions($displayLocale = null) {
			$languages = self::getAvailableInterfaceLanguages();
			if (count($languages) > 0) {
				array_unshift($languages, 'en_US');
			}
			$locales = array();
			foreach($languages as $lang) {
				$locales[$lang] = self::getLanguageDescription($lang,$displayLocale);
			}
			natcasesort($locales);
			return $locales;
		}

		/**
		 * Get the description of a locale consisting of language and region description
		 * e.g. "French (France)"
		 * @param string $locale Locale that should be described
		 * @param string $displayLocale Language of the description
		 * @return string Description of a language
		 */
		public static function getLanguageDescription($locale, $displayLocale = null) {
			//check to make sure the locales exist
			if (!Locales::exists($locale)) return $locale;
			if (!is_null($displayLocale) && !Locales::exists($displayLocale)) $displayLocale = null;
			$displayLocale = $displayLocale?:$locale;

			return Locales::getName($locale, $displayLocale);
		}

	}

