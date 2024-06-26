<?php

defined('C5_EXECUTE') or die("Access Denied.");

/**
* Concrete Model Class
* The model class extends the ADOdb active record class, allowing items that inherit from it to use the automatic create, updating, read and delete functionality it provides.
* @link http://phplens.com/lens/adodb/docs-active-record.htm
* @author Andrew Embler <andrew@concrete5.org>
* @link http://www.concrete5.org
* @package Utilities
* @license http://www.opensource.org/licenses/mit-license.php MIT
*
*/
class Concrete5_Library_Model extends ADOdb_Active_Record {

	public function __construct() {
		$db = Loader::db();
		parent::__construct();
	}

    /**
     * Makes this class have case-insensitive variable setting
     * Since ADODB sets column names in all-uppercase
     * But the C5 core sets them in camel-case
     * Since ADODB populates column names on instantiation, we will default to those
     */
    // public function __set($name, $value){
    //     //get list of available obj vars
    //     $oVars = get_object_vars($this);
    //     foreach($oVars as $variable=>$val){
    //         if(strcasecmp($name, $variable) == 0){
    //             $this->{$variable} = $value;
    //         } else {
    //             $this->{strtoupper($name)} = $value;
    //         }
    //     }
    // }

    /**
     * Override the default `doquote` method to better sanitize numeric values.
     *
     * @param ADOConnection $db
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    public function doquote(&$db, $value, $type) {
        switch ($type) {
            case 'L':
            case 'I':
            case 'I1':
            case 'I2':
            case 'I4':
            case 'I8':
            case 'F':
            case 'N':
                if (!is_numeric($value)) {
                    if (is_null($value)) {
                        return null;
                    }
                    if ($value === true) {
                        return 1;
                    }
                    if ($value === false) {
                        return 0;
                    }
                    $db->outp_throw('Numeric field type "' . $type . '" requires numeric value.', 'DOQUOTE');
                    return 0;
                }
            default:
                return parent::doquote($db, $value, $type);
        }
    }

}
