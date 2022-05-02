<?php
/**
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

/**
 * Grabs a list of countries commonly used in web forms.
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

defined('C5_EXECUTE') or die("Access Denied.");

use Symfony\Component\Intl\Countries;

class Concrete5_Helper_Lists_Countries {

	/** Locale for which we currently loaded the data.
	* @var string
	*/
	protected $locale = null;

	protected $countries = array();

	public function reset() {
		$locale = Localization::activeLocale();
		if($locale === $this->locale) {
			return;
		}
		$this->locale = $locale;
		$countries = Countries::getNames($locale);

		$countriesFromEvent = Events::fire('on_get_countries_list', $countries);
		if(is_array($countriesFromEvent)) {
			$countries = $countriesFromEvent;
		} else {
			asort($countries, SORT_LOCALE_STRING);
		}
		$this->countries = $countries;
	}

	/** Returns an array of countries with their short name as the key and their full name as the value
	* @return array Keys are the country codes, values are the county names
	*/
	public function getCountries() {
		return $this->countries;
	}

	/** Gets a country full name given its code
	* @param string $code The country code
	* @return string
	*/
	public function getCountryName($code) {
		$countries = $this->getCountries(true);
		return $countries[$code];
	}

}
