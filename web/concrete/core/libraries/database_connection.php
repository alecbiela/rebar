<?php

defined('C5_EXECUTE') or die("Access Denied.");

include_once(DIR_BASE_CORE . '/libraries/3rdparty/Rebar/vendor/adodb/adodb-php/drivers/adodb-mysqli.inc.php');
class Concrete5_Library_DatabaseConnection extends ADODB_mysqli{

    public function __construct(){

        parent::__construct();
    }

    /**
     * Overridden from parent class - only 1 line is changed (look for comment near bottom)
     * 
	 * Return an array of information about a table's columns.
	 *
	 * @param string $table The name of the table to get the column info for.
	 * @param bool $normalize (Optional) Unused.
	 *
	 * @return ADOFieldObject[]|bool An array of info for each column, or false if it could not determine the info.
	 */
	/*function MetaColumns($table, $normalize = true) {
		$false = false;
		if (!$this->metaColumnsSQL)
			return $false;

		global $ADODB_FETCH_MODE;
		$save = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		if ($this->fetchMode !== false)
			$savem = $this->SetFetchMode(false);*/
		/*
		* Return assoc array where key is column name, value is column type
		*    [1] => int unsigned
		*/

		/*$SQL = "SELECT column_name, column_type
				  FROM information_schema.columns
				 WHERE table_schema='{$this->database}'
				   AND table_name='$table'";

		$schemaArray = $this->getAssoc($SQL);
		$schemaArray = array_change_key_case($schemaArray,CASE_LOWER);

		$rs = $this->Execute(sprintf($this->metaColumnsSQL,$table));
		if (isset($savem)) $this->SetFetchMode($savem);
		$ADODB_FETCH_MODE = $save;
		if (!is_object($rs))
			return $false;

		$retarr = array();
		while (!$rs->EOF) {
			$fld = new ADOFieldObject();
			$fld->name = $rs->fields[0];
			$type = $rs->fields[1];
*/
			/*
			* Type from information_schema returns
			* the same format in V8 mysql as V5
			*/
			/*$type = $schemaArray[strtolower($fld->name)];

			// split type into type(length):
			$fld->scale = null;
			if (preg_match("/^(.+)\((\d+),(\d+)/", $type, $query_array)) {
				$fld->type = $query_array[1];
				$fld->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
				$fld->scale = is_numeric($query_array[3]) ? $query_array[3] : -1;
			} elseif (preg_match("/^(.+)\((\d+)/", $type, $query_array)) {
				$fld->type = $query_array[1];
				$fld->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
			} elseif (preg_match("/^(enum)\((.*)\)$/i", $type, $query_array)) {
				$fld->type = $query_array[1];
				$arr = explode(",",$query_array[2]);
				$fld->enums = $arr;
				$zlen = max(array_map("strlen",$arr)) - 2; // PHP >= 4.0.6
				$fld->max_length = ($zlen > 0) ? $zlen : 1;
			} else {
				$fld->type = $type;
				$fld->max_length = -1;
			}

			$fld->not_null = ($rs->fields[2] != 'YES');
			$fld->primary_key = ($rs->fields[3] == 'PRI');
			$fld->auto_increment = (strpos($rs->fields[5], 'auto_increment') !== false);
			$fld->binary = (strpos($type,'blob') !== false);
			$fld->unsigned = (strpos($type,'unsigned') !== false);
			$fld->zerofill = (strpos($type,'zerofill') !== false);

			if (!$fld->binary) {
				$d = $rs->fields[4];
				if ($d != '' && $d != 'NULL') {
					$fld->has_default = true;
					$fld->default_value = $d;
				} else {
					$fld->has_default = false;
				}
			}

			if ($save == ADODB_FETCH_NUM) {
				$retarr[] = $fld;
			} else {
                //This is the only line changed in the override
				$retarr[$fld->name] = $fld;
			}
			$rs->moveNext();
		}

		$rs->close();
		return $retarr;
	}*/

    /**
     * Overriding the default Replace() since it tries to insert with mismatched col names
     */
    function Replace($table, $fieldArray, $keyCol, $autoQuote=false, $has_autoinc=false){
        // Add Quote around table name to support use of spaces / reserved keywords
        $table=sprintf('%s%s%s', $this->nameQuote,$table,$this->nameQuote);

        if (count($fieldArray) == 0) return 0;

        if (!is_array($keyCol)) {
            $keyCol = array($keyCol);
        }
        $uSet = '';
        foreach($fieldArray as $k => $v) {
            if ($v === null) {
                $v = 'NULL';
                $fieldArray[$k] = $v;
            } else if ($autoQuote && strcasecmp($v,$this->null2null)!=0) {
                $v = $this->qstr($v);
                $fieldArray[$k] = $v;
            }
            if (in_array($k,$keyCol)) continue; // skip UPDATE if is key

            // Add Quote around column name to support use of spaces / reserved keywords
            $uSet .= sprintf(',%s%s%s=%s',$this->nameQuote,$k,$this->nameQuote,$v);
        }
        $uSet = ltrim($uSet, ',');

        // Add Quote around column name in where clause
        $where = '';
        $fieldArrayKeys = array_keys($fieldArray);
        foreach ($keyCol as $k=>$v) {
            $keyColName = ($v == '') ? $k : $v; //colname might be in the key with an empty string
            foreach($fieldArrayKeys as $fk){
                if(strcasecmp($keyColName, $fk) == 0){ //case-insensitive match on a keyCol name
                    $where .= sprintf(' and %s%s%s=%s ', $this->nameQuote,$fk,$this->nameQuote,$fieldArray[$fk]);
                }
            }
        }
        if ($where) {
            $where = substr($where, 5);
        }

        if ($uSet && $where) {
            $update = "UPDATE $table SET $uSet WHERE $where";
            $rs = $this->Execute($update);

            if ($rs) {
                if ($this->poorAffectedRows) {
                    // The Select count(*) wipes out any errors that the update would have returned.
                    // PHPLens Issue No: 5696
                    if ($this->ErrorNo()<>0) return 0;

                    // affected_rows == 0 if update field values identical to old values
                    // for mysql - which is silly.
                    $cnt = $this->GetOne("select count(*) from $table where $where");
                    if ($cnt > 0) return 1; // record already exists
                } else {
                    if (($this->Affected_Rows()>0)) return 1;
                }
            } else
                return 0;
        }

        $iCols = $iVals = '';
        foreach($fieldArray as $k => $v) {
            if ($has_autoinc && in_array($k,$keyCol)) continue; // skip autoinc col

            // Add Quote around Column Name
            $iCols .= sprintf(',%s%s%s',$this->nameQuote,$k,$this->nameQuote);
            $iVals .= ",$v";
        }
        $iCols = ltrim($iCols, ',');
        $iVals = ltrim($iVals, ',');

        $insert = "INSERT INTO $table ($iCols) VALUES ($iVals)";
        $rs = $this->Execute($insert);
        return ($rs) ? 2 : 0;
    }
}