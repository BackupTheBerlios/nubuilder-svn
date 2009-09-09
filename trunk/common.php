<?php
/*
** File:           common.php
** Author:         nuSoftware
** Created:        2007/04/26
** Last modified:  2009/07/15
**
** Copyright 2004, 2005, 2006, 2007, 2008, 2009 nuSoftware
**
** This file is part of the nuBuilder source package and is licensed under the
** GPLv3. For support on developing in nuBuilder, please visit the nuBuilder
** wiki and forums. For details on contributing a patch for nuBuilder, please
** visit the `Project Contributions' forum.
**
**   Website:  http://www.nubuilder.com
**   Wiki:     http://wiki.nubuilder.com
**   Forums:   http://forums.nubuilder.com
*/

set_time_limit(0);

include('dbfunctions.php');

//----- returns url ie. 'https://www.nubuilder.com/productionnu/'
function getPHPurl() {
        if ($_SERVER[HTTPS] == 'on') {
                $start = "https://";
        } else {
                $start = "http://";
        }
        $base_host = $_SERVER[SERVER_NAME];
        $path = "";
        $pieces = explode("/", $_SERVER[SCRIPT_NAME]);
        for ($x=0; $x<count($pieces); $x++) {

                if (substr_count($pieces[$x], '.') == 0 ) {
                        $path = $path.$pieces[$x]."/";
                } else {
                        $x = count($pieces) + 1;
                }
        }
        $php_url = $start.$base_host.$path;
        return $php_url;
}

//-----setup php code just used for this database
$setup                           = nuSetup();
$sVariables                      = recordToHashArray('zzsys_session', 'zzsys_session_id', $_GET['ses']);  //--session values (access level and user etc. )

eval(replaceHashVariablesWithValues($sVariables, getLib()));                                  //--replace hash variables then run code
//eval(replaceHashVariablesWithValues($sVariables, $setup->set_php_code));                                  //--replace hash variables then run code

//--- see if activity can be run without being logged in
function activityPasswordNeeded($pReportID){

	$t = nuRunQuery("SELECT sat_all_zzsys_form_id FROM zzsys_activity WHERE sat_all_code = '$pReportID'");
	$r = db_fetch_row($t);
	return  passwordNeeded($r[0]);
	
}

// BEGIN - 2009/06/02 - Michael
setClientTimeZone();

function setClientTimeZone()
{
        global $setup;
        if ($setup->set_timezone)
                date_default_timezone_set($setup->set_timezone);
        else
                date_default_timezone_set("Australia/Adelaide");
} // func
// END - 2009/06/02 - Michael

//--- turn uniqid into a number

function hexNo($pLetter){
   $hex      = array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f');
   for($i=0;$i<count($hex);$i++){
      if($hex[$i]==$pLetter){return $i;}
   }
}

function buildIDNumber(){

   $id       = uniqid('1');
   $ar       = array_reverse(str_split($id));
   $value    = 0;

   for($i    = 0 ; $i < 7 ; $i++){
      $pow   = pow(16,$i) * hexNo($ar[$i]);
      $value = $value + $pow;
   }
   return $value;
}



function passwordNeeded($pFormID){

	$t   = nuRunQuery("SELECT * FROM zzsys_form WHERE zzsys_form_id = '$pFormID'");
	$r   = db_fetch_object($t);
	return $r->sfo_access_without_login != '1';

}



function addToLog($pSQL){
	

}



function str_hex($string){

    $hex='';
    for ($i=0; $i < strlen($string); $i++){
        $hex .= dechex(ord($string[$i]));
    }
    return $hex;

}


function hex_str($hex){

    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;

}



function makeCSS(){
	
	$class           = '*';
	$s               =      "SELECT * FROM `zzsys_style` ";
	$s               = $s . "INNER JOIN zzsys_style_property ON ";
	$s               = $s . "zzsys_style_id = ssp_zzsys_style_id ";
	$s               = $s . "ORDER BY sst_class DESC";
	$t               = nuRunQuery($s);
	$s               = "<style type='text/css'>";
	$endCurl         = '';
	while($r         = db_fetch_object($t)){
		if($class   != $r->sst_class){
			$class   = $r->sst_class;
			$s       = $s . "$endCurl\n$r->sst_class {";
		}
		$s           = $s . "$r->ssp_property_name:$r->ssp_property_value;";
		$endCurl     = '}';
	}	
	$s               = $s."}\n</style>\n";
	return $s;
}

function getSqlRecordValues($pSQL, $pRecordValues){
	
	$a              = getArrayFromString($pSQL,'#');
	for($i=0;$i<count($a);$i++){
		$pSQL       = str_replace('#' . $a[$i] . '#', $pRecordValues[$a[$i]], $pSQL);
	}
	return $pSQL;
}

function getSqlFormValues($pSQL, $pFormSessionID){
	
	$s              = array();
	$t              = nuRunQuery("SELECT sva_name, sva_value FROM zzsys_variable WHERE sva_id = '$pFormSessionID'");
	while($r=db_fetch_row($t)){
		$s[$r[0]]   = $r[1];
	}
	$a              = getArrayFromString($pSQL,'#');
	for($i=0;$i<count($a);$i++){
		$pSQL       = str_replace('#' . $a[$i] . '#', $s[$a[$i]], $pSQL);
	}
	return $pSQL;
}

function getglobalValue($session_id){
	$r                                    = nuSession($session_id);
	$globalValue['session_id']            = $r->zzsys_session_id;
	$globalValue['access_level']          = $r->sss_access_level;
	$globalValue['zzsys_user_id']         = $r->sss_zzsys_user_id;
	$globalValue['zzsys_user_group_name'] = $r->sss_zzsys_user_group_name;
	$globalValue['small']                 = $r->sss_small;
	return $globalValue;
}


function nuSession($id, $newPage = ''){

if($_COOKIE['security_check'] != $id){$id=$id.'x';}; //change to invalid $id
	$setup          = nuSetup();
	$time           = time();
	$allowed        = $setup->set_time_out_minutes * 60;
	$s              =      "SELECT IF($time - sss_session_seconds <= $allowed, '1', '') AS foundOK, ";
	$s              = $s . "zzsys_session.*, $time AS the_time FROM zzsys_session WHERE zzsys_session_id = '$id' ";
	$t              = nuRunQuery($s);
	$r              = db_fetch_object($t);
	$T              = nuRunQuery("UPDATE zzsys_session SET sss_session_seconds = '" . time() . "' WHERE zzsys_session_id = '$id' ");
	if($r->foundOK == ''){
		nuRunQuery("DELETE FROM zzsys_session WHERE zzsys_session_id = '$id'");
	}
	return $r;

}

function nuSessionSet($id, $access_level, $user_id, $user_group, $small , $parameter = ''){

	$s = "INSERT INTO zzsys_session (zzsys_session_id, sss_access_level, sss_zzsys_user_id, ";
	$s = $s . "sss_small, sss_zzsys_user_group_name, sss_session_seconds, sss_session_date, sss_parameter) ";
	$s = $s . "VALUES ('$id', '$access_level', '$user_id', ";
	$s = $s . "'$small', '$user_group', '" . time() . "', '" . date('Y-n-d') . "', '$parameter')";
	nuRunQuery($s);
}

function getSelectionFormVariables($pID){
	
	$v                                  = array();
	$t                                  = nuRunQuery("SELECT count(*) AS thecount, sva_name, sva_value, sva_table FROM zzsys_variable WHERE sva_id = '$pID' GROUP BY sva_name, sva_table");
	while($r                            = db_fetch_object($t)){
		if($r->sva_table                != '1'){
			$v[$r->sva_name]            = $r->sva_value;
		}else{
	    	$T                          = nuRunQuery("SELECT * FROM zzsys_variable WHERE sva_id = '$pID' AND sva_name = '$r->sva_name'");
			$tableName                  = TT();
			$v[$r->sva_name]            = $tableName;
			nuRunQuery("CREATE TABLE $tableName (id VARCHAR(15) NOT NULL, $tableName VARCHAR(15) NULL ,PRIMARY KEY (id), INDEX ($tableName))");
	    	while($R                    = db_fetch_object($T)){
	    		$id                     = uniqid('1');
	    		nuRunQuery("INSERT INTO $tableName (id, $tableName) VALUES ('$id', '$R->sva_value')");
	    	}
		}
	}
	return $v;
}
    

function getSelectionFormTempTableNames($pID, $vArray){
	
	$v                                  = array();
	$t                                  = nuRunQuery("SELECT sva_name FROM zzsys_variable WHERE sva_id = '$pID' AND sva_table = '1' GROUP BY sva_name");
	while($r                            = db_fetch_object($t)){
		$v[]                            = $vArray[$r->sva_name];
	}
	return $v;
}
    

function formatTextValue($pValue, $pFormatNumber){

	if($pFormatNumber == ''){return $pValue;}
	$format=textFormatsArray();
	if($format[$pFormatNumber]->type=='number'){

		if($pValue==''){
			return '';
		}else{
			return number_format($pValue, $format[$pFormatNumber]->format, $format[$pFormatNumber]->decimal, $format[$pFormatNumber]->separator);
		}
	}

	if($format[$pFormatNumber]->type=='date'){
		if($pValue=='' or $pValue=='0000-00-00'){
			return '';
		}else{
			return nuDateFormat($pValue,$format[$pFormatNumber]->phpdate);
		}
	}
	return $pValue;

}



function top_left(){
	$s = $s . "<div style='top:0;left:0;width:8;height:8;position:absolute;font-size:100px;font-family:arial;overflow:hidden;'>\n";
	$s = $s . "<div class='unselected' style='top:-30;left:-4;position:absolute;font-size:70px;font-family:arial;overflow:hidden;'>&bull;</div>\n";
	$s = $s . "</div>\n";
	return $s;
}

function bottom_left(){
	$s = $s . "<div style='bottom:0;left:0;width:8;height:8;position:absolute;font-size:100px;font-family:arial;overflow:hidden;'>\n";
	$s = $s . "<div class='unselected' style='top:-39;left:-4;position:absolute;font-size:70px;font-family:arial;overflow:hidden;'>&bull;</div>\n";
	$s = $s . "</div>\n";
	return $s;
}

function top_right(){
	$s = $s . "<div style='top:0;right:0;width:8;height:8;position:absolute;font-size:100px;font-family:arial;overflow:hidden;'>\n";
	$s = $s . "<div class='unselected' style='top:-30;left:-13;position:absolute;font-size:70px;font-family:arial;overflow:hidden;'>&bull;</div>\n";
	$s = $s . "</div>\n";
	return $s;
}

function bottom_right(){
	$s = $s . "<div style='bottom:0;right:0;width:8;height:8;position:absolute;font-size:100px;font-family:arial;overflow:hidden;'>\n";
	$s = $s . "<div class='unselected' style='top:-39;left:-13;position:absolute;font-size:70px;font-family:arial;overflow:hidden;'>&bull;</div>\n";
	$s = $s . "</div>\n";
	return $s;
}

class sqlString{

    public  $from         = '';
    public  $where        = '';
    public  $groupBy      = '';
    public  $having       = '';
    public  $orderBy      = '';
    public  $fields       = array();
    public  $Dselect      = '';
    public  $Dfrom        = '';
    public  $Dwhere       = '';
    public  $DgroupBy     = '';
    public  $Dhaving      = '';
    public  $DorderBy     = '';
    public  $Dfields      = array();
    public  $SQL          = '';

    function __construct($sql){

        $sql              = str_replace(chr(13), ' ', $sql);//----remove carrige returns
        $sql              = str_replace(chr(10), ' ', $sql);//----remove line feeds

        $select_string    = $sql;
        $from_string      = stristr($sql, ' from ');
        $where_string     = stristr($sql, ' where ');
        $groupBy_string   = stristr($sql, ' group by ');
        $having_string    = stristr($sql, ' having ');
        $orderBy_string   = stristr($sql, ' order by ');
        
        $from             = str_replace($where_string,   '', $from_string);
        $from             = str_replace($groupBy_string, '', $from);
        $from             = str_replace($having_string,  '', $from);
        $from             = str_replace($orderBy_string, '', $from);
        
        $where            = str_replace($groupBy_string, '', $where_string);
        $where            = str_replace($having_string,  '', $where);
        $where            = str_replace($orderBy_string, '', $where);
        
        $groupBy          = str_replace($having_string,  '', $groupBy_string);
        $groupBy          = str_replace($orderBy_string, '', $groupBy);
        
        $having           = str_replace($orderBy_string, '', $having_string);
        
        $orderBy          = $orderBy_string;
        $this->from       = $from;
        $this->where      = $where;
        $this->groupBy    = $groupBy;
        $this->having     = $having;
        $this->orderBy    = $orderBy;

        $this->Dfrom      = $this->from;
        $this->Dwhere     = $this->where;
        $this->DgroupBy   = $this->groupBy;
        $this->Dhaving    = $this->having;
        $this->DorderBy   = $this->orderBy;

    	$this->buildSQL();
      }

    public function restoreDefault($pString){

    	if($pString == 'f'){$this->from      = $this->Dfrom;}
    	if($pString == 'w'){$this->where     = $this->Dwhere;}
    	if($pString == 'g'){$this->groupBy   = $this->DgroupBy;}
    	if($pString == 'h'){$this->having    = $this->Dhaving;}
    	if($pString == 'o'){$this->orderBy   = $this->DorderBy;}
    	$this->buildSQL();

    }

    public function setFrom($pString){

    	$this->from          = $pString; 
    	$this->buildSQL();

    }

    public function setWhere($pString){

    	$this->where         = $pString; 
    	$this->buildSQL();

    }

    public function getWhere(){
    	return $this->where; 
    }

    public function setGroupBy($pString){

    	$this->groupBy       = $pString; 
    	$this->buildSQL();

    }

    public function setHaving($pString){

    	$this->having        = $pString; 
    	$this->buildSQL();

    }

    public function setOrderBy($pString){

    	$this->orderBy       = $pString; 
    	$this->buildSQL();

    }

    public function addField($pString){

    	$this->fields[]      = $pString; 
    	$this->buildSQL();

    }

    public function removeField($pFieldOrderNumber){
    	
    	$newList              = array();
    	for($i = 0 ; $i < count($this->fields) ; $i++){
    		if($i != $pFieldOrderNumber){
    			$newList[]    = $this->fields[$i];
    		}
    	}
    	$this->fields         = $newList;
    }

    public function removeAllFields(){
    	
		while (count($this->fields)> 0){
			$this->removeField(0);
		}
    }

    private function buildSQL(){

    	$this->SQL           = 'SELECT '; 
    	for($i = 0 ; $i < count($this->fields) ; $i++){
    		if($i == 0){
	    		$this->SQL   = $this->SQL . ' ' . $this->fields[$i];
    		}else{
	    		$this->SQL   = $this->SQL . ', ' . $this->fields[$i];
    		}
    	}
    	$this->SQL           = $this->SQL . ' ' . $this->from;
    	$this->SQL           = $this->SQL . ' ' . $this->where;
    	$this->SQL           = $this->SQL . ' ' . $this->groupBy;
    	$this->SQL           = $this->SQL . ' ' . $this->having;
    	$this->SQL           = $this->SQL . ' ' . $this->orderBy;
    }

}

function displayCondition($pHashArray, $pSQLString){

	$string = replaceHashVariablesWithValues($pHashArray, $pSQLString);
	if($string==''){return true;}


    if($_GET['debug']!=''){
        tofile('displayCondition hash variables : debug value:'.$_GET['debug']);
        tofile(print_r($pHashArray,true));
        tofile($string);
    }



    if($_GET['debug']!=''){
        tofile("display Condition : $string");
        tofile(print_r($pHashArray,true));
    }
	$t      = nuRunQuery($string);
	$r      = db_fetch_row($t);
	return $r[0] == '1';

}



function displayCondition_old($string, $recordID, $recordValues){

    if($string==''){return true;}
    //--- replace variables in string with GLOBAL variables and/or $id
    $string     = replaceVariablesInString('', $string, $recordID);
    //--- get array of other values that need replacing
    $hash       = getArrayFromString($string,'#');
    //--- loop through array replacing variables in string with values
	for($i=0;$i<count($hash);$i++){
		$fieldname=$hash[$i];
		$string=str_replace('#'.$hash[$i].'#',$recordValues[$fieldname],$string);
	}

	//--- Run string as a select statement
//    $t          = nuRunQuery('SELECT '.$string);
    $t          = nuRunQuery($string);

    $answer     = db_fetch_row($t);
    return $answer[0]=='1';  //---if answer = 1 then return True

}

function getFormValue($pFormID, $pFieldName){
	$answer = array();
	$t = nuRunQuery("SELECT sva_value FROM zzsys_variable WHERE sva_id = '$pFormID' AND sva_name = '$pFieldName'");
	while($r = db_fetch_row($t)){
		$answer[] = $r[0];
	}
	return $answer;
}

function replaceVariablesInString($pTT, $pString, $pID){

	$ses     = nuSession($GLOBALS['security_check']);
//	$ses     = nuSession($_GET['ses']);
	$pString = str_replace("#session_parameter#"     ,$ses->sss_parameter                ,$pString);
	$pString = str_replace("#access_level#"          ,$ses->sss_access_level             ,$pString);
	$pString = str_replace("#zzsys_user_id#"         ,$ses->sss_zzsys_user_id            ,$pString);
	$pString = str_replace("#zzsys_user_group_name#" ,$ses->sss_zzsys_user_group_name    ,$pString);
	$pString = str_replace("#clone#"                 ,$_GET['c']                         ,$pString);
	$pString = str_replace("#id#"                    ,$pID                               ,$pString);
	$pString = str_replace("#TT#"                    ,$pTT                               ,$pString);
	$pString = str_replace("#browseTable#"           ,$pTT                               ,$pString);
	$pString = str_replace("#small#"                 ,$ses->sss_small                    ,$pString);
	return $pString;

}



function recordToHashArray($pTable, $pPrimaryKey, $pID){ //-- put session values from zzsys_session into an array

	$t = nuRunQuery("SELECT * FROM $pTable WHERE $pPrimaryKey = '$pID'");
	$r = db_fetch_array($t);
	$a = array();
	$f = false;
	$replace = iif($pTable == 'zzsys_session','sss_','');
	$r['#NOTHING#'] = ''; //-- add just in case there was nothing added
	while(list($key, $value) = each($r)){
		if($f){ //-- jump every second one
			$a['#'.str_replace($replace   ,'', $key).'#'] = $value;
		}
		$f = !$f;
	}
	$a['#NOTHING#'] = ''; //-- add just in case there was nothing added
	if($pTable == 'zzsys_session'){
		$a['#session_id#']        = $a['#zzsys_session_id#'];
		$a['#ses#']               = $a['#zzsys_session_id#'];
		$a['#session_parameter#'] = $a['#parameter#'];
	} //--either / or
	return $a;
}


function replaceHashVariablesWithValues($pArray, $pString){ //-- replace hash variables in "hashString" with values in array

	$pArray['#NOTHING#'] = ''; //-- add just in case there was nothing added
	while(list($key, $value) = each($pArray)){
		$pString = str_replace($key, $value, $pString);
	}
	return $pString;
}


function arrayToHashArray($pArray){ //-- put current $_POST variables into a hashArray

	$a = array();
	while(list($key, $value) = each($pArray)){
		$a['#'.$key.'#'] = $value;
	}
	$a['#NOTHING#'] = ''; //-- add just in case there was nothing added
	return $a;
}


function postVariablesToHashArray(){ //-- put current $_POST variables into a hashArray

	$a = array();
	while(list($key, $value) = each($_POST)){
		$a['#'.$key.'#'] = $value;
	}
	$a['#NOTHING#'] = ''; //-- add just in case there was nothing added
	return $a;
}


function sysVariablesToHashArray($pFormID){ //-- put current records in zzsys_variables into a hashArray

	$a = array();
	$t = nuRunQuery("SELECT * FROM zzsys_variable WHERE sva_id = '$pFormID'");
	while($r = db_fetch_object($t)){
		$a['#'.$r->sva_name.'#'] = $r->sva_value;
	}
	$a['#NOTHING#'] = ''; //-- add just in case there was nothing added
	return $a;
}


function joinHashArrays($pArray, $pArrayToAdd){ //--join one hash array to another

	reset ($pArray);
	while(list($key, $value) = each($pArrayToAdd)){
		$pArray[$key] = $value;
	}
	return $pArray;

}

function getArrayFromString($string,$delimiter){

	$array = array();
   	for($i=0;$i<strlen($string);$i++){
		$startOfArray=strpos($string,$delimiter,$i); //---find first instance of $delimiter
		if($startOfArray===false){
          break;
        }
		$i=$startOfArray;
		$endOfArray=strpos($string,$delimiter,$i+1); //---find second instance of $delimiter
		if($endOfArray===false){
          break;
        }
		$array[]=substr($string, $startOfArray+1, $endOfArray-$startOfArray-1);
		$i=$endOfArray;
	}
	return $array;

}

function tempnuRunQuery($pSQL,$pStopOnError = true){

	$DBHost          = '127.0.0.1';
	$DBName          = 'pcp2000';
	$DBUserID        = 'globeadmin';
	$DBPassWord      = '6495ED';
	if($pSQL         == ''){
		$a           = array();
		$a[0]        = $DBHost;
		$a[1]        = $DBName;
		$a[2]        = $DBUserID;
		$a[3]        = $DBPassWord;
		return $a;
	}
//---------open connection and database and return query
	$con             = mysql_connect($DBHost,$DBUserID,$DBPassWord)  or die ("Could not connect to database");
	mysql_select_db($DBName,$con) or die ("Could not select database");
	if($pStopOnError){
		$t               = mysql_query($pSQL) or die ("<HR/>Could not execute : -- <br/>\n<HR/>".$pSQL);
	}else{
		$t               = mysql_query($pSQL);
	}
	return $t;
}

function formFields($formID){
//---returns row of zzsysform as an object (eg. $r->sfo_title)
	$t = nuRunQuery("SELECT * FROM zzsys_form WHERE zzsys_form_id = '$formID'");
	return db_fetch_object($t);
}

function objectFields($objectID){
//---returns row of zzsysobject as an object (eg. $r->sob_all_title)
	$t = nuRunQuery("SELECT * FROM zzsys_object WHERE zzsys_object_id = '$objectID'");
	return db_fetch_object($t);
}

function listtest($s){
	$t = nuRunQuery($s);
	$r = db_fetch_row($t);
}

function addEscapes($pValue){

	$bs     = '\\';
	$pValue = str_replace($bs,$bs.$bs,$pValue);
	$pValue = str_replace("'","\'",$pValue);
	return $pValue;
	
}

function setnuList($pID, $pExpire, $pName, $pValues){

	$now   = date('Y-m-d H:i:s');
	$dq    = '"';
	$ses    = $_GET['ses'];

	$s     = "DELETE FROM zzsys_variable WHERE sva_id = '$pID' AND sva_name = '$pName'";
	nuRunQuery($s);

	for($i=0; $i< count($pValues);$i++){
		$id    = uniqid('1');
		$fixed = addEscapes($pValues[$i]);
		$s     = "INSERT INTO zzsys_variable (zzsys_variable_id, sva_id, sva_session_id, sva_expiry_date, sva_name, sva_value, sva_table) ";
		$s     = $s . "VALUES ('$id', '$pID', '$ses', '$pExpire', '$pName', '$fixed', '1')";
		nuRunQuery($s);
	}

}

function setnuVariable($pID, $pExpire, $pName, $pValue){

	$id     = uniqid('1');
	$now    = date('Y-m-d H:i:s');
	$dq     = '"';
	$pValue = addEscapes($pValue);
	$ses    = $_GET['ses'];
nudebug("ses:$ses");

	$s      = "DELETE FROM zzsys_variable WHERE sva_id = '$pID' AND sva_name = '$pName'";
	nuRunQuery($s);

	$s      = "INSERT INTO zzsys_variable (zzsys_variable_id, sva_id, sva_session_id, sva_expiry_date, sva_name, sva_value, sys_added)  ";
	$s      = $s . "VALUES ('$id', '$pID', '$ses', '$pExpire', '$pName', '$pValue', '$now')";
	nuRunQuery($s);
}

function getnuVariable($pName, $pID){

	$s   = "SELECT sva_value FROM zzsys_variable WHERE sva_name = '$pName' AND sva_id = '$pID'";
	$t   = nuRunQuery($s);
	$r   = db_fetch_object($t);

	if(count($r) > 1){
		return $r;                  //---return array
	}else{
		return $r->sva_value;       //---return 1 value
	}

}

function nuObjects(){

	$nuObject                = array();
	$nuObject[0]             = 'button';
	$nuObject[1]             = 'display';
	$nuObject[2]             = 'dropdown';
	$nuObject[3]             = 'graph';
	$nuObject[4]             = 'image';
	$nuObject[5]             = 'inarray';
	$nuObject[6]             = 'listbox';
	$nuObject[7]             = 'lookup';
	$nuObject[8]             = 'password';
	$nuObject[9]             = 'subform';
	$nuObject[10]            = 'text';
	$nuObject[11]            = 'textarea';
	$nuObject[12]            = 'words';

	return $nuObject;
}

function addCentury($pValue){

	if($pValue > 70){
		return '19'.$pValue;
	}
	return '20'.$pValue;

}

function setFormatArray(){

	$textFormat=textFormatsArray();

    $s   =      "var aType         = new Array();\n";
    $s   = $s . "var aFormat       = new Array();\n";
    $s   = $s . "var aDecimal      = new Array();\n";
    $s   = $s . "var aSeparator    = new Array();\n\n";

    for($i = 0 ; $i < count($textFormat) ; $i++){

		$type       = $textFormat[$i]->type;
		$format     = $textFormat[$i]->format;
		$decimal    = $textFormat[$i]->decimal;
		$separator  = $textFormat[$i]->separator;
		$s          = $s . "    aType[$i]        = ['$type'];\n";
		$s          = $s . "    aFormat[$i]      = ['$format'];\n";
		$s          = $s . "    aDecimal[$i]     = ['$decimal'];\n";
		$s          = $s . "    aSeparator[$i]   = ['$separator'];\n\n";

	}
    return $s;

}

function reformatField($pValue, $pFormat,$addSingleQuotes = true){
// reformats value ready for insertion into database table
//originally formatted via rules in textFormatsArray()
	$FORMAT = textFormatsArray();
	$sq     = "";
	if($FORMAT[$pFormat]->type == 'date' AND $pValue == ''){return 'NULL';} //--save null to a date field
	if($addSingleQuotes){$sq = "'";}
	if($pFormat == '' OR $pValue == ''){return $sq . $pValue . $sq;} // not a text field or nothing to format
	if($pFormat == '6'){ // dd-mmm-yyyy
		return $sq . substr($pValue,-4)              . '-' . monthNumber(substr($pValue,3,3))  . '-' . substr($pValue,0,2) . $sq;
	}
	if($pFormat == '7'){ // dd-mm-yyyy
		return $sq . substr($pValue,-4)              . '-' . substr($pValue,3,2)               . '-' . substr($pValue,0,2) . $sq;
	}
	if($pFormat == '8'){ // mmm-dd-yyyy
		return $sq . substr($pValue,-4)              . '-' . monthNumber(substr($pValue,0,3))  . '-' . substr($pValue,4,2) . $sq;
	}
	if($pFormat == '9'){ // mm-dd-yyyy
		return $sq . substr($pValue,-4)              . '-' . substr($pValue,0,2)               . '-' . substr($pValue,3,2) . $sq;
	}
	if($pFormat == '10'){ // dd-mmm-yy
		return $sq . addCentury(substr($pValue,-2))  . '-' . monthNumber(substr($pValue,3,3))  . '-' . substr($pValue,0,2) . $sq;
	}
	if($pFormat == '11'){ // dd-mm-yy
		return $sq . addCentury(substr($pValue,-2))  . '-' . substr($pValue,3,2)               . '-' . substr($pValue,0,2) . $sq;
	}
	if($pFormat == '12'){ // mmm-dd-yy
		return $sq . addCentury(substr($pValue,-2))  . '-' . monthNumber(substr($pValue,0,3))  . '-' . substr($pValue,4,2) . $sq;
	}
	if($pFormat == '13'){ // mm-dd-yy
		return $sq . addCentury(substr($pValue,-2))  . '-' . substr($pValue,0,2)               . '-' . substr($pValue,3,2) . $sq;
	}

    if (in_array($pFormat, array('14','15','16','17','18','19'))){ //---number with commas
		return $sq . str_replace(',', '', $pValue) . $sq;
    }
	return  $sq . $pValue . $sq;

}


function textFormatsArray(){

//-----number formats
	$format = array();
	$format[0]->type         = 'number';
	$format[0]->format       = '0';
	$format[0]->decimal      = '.';
	$format[0]->separator    = '';
	$format[0]->sample       = '10000';
	$format[0]->phpdate      = '';
	$format[0]->sql          = 'REPLACE(FORMAT(??,0), ",", "")';

	$format[1]->type         = 'number';
	$format[1]->format       = '1';
	$format[1]->decimal      = '.';
	$format[1]->separator    = '';
	$format[1]->sample       = '10000.0';
	$format[1]->phpdate      = '';
	$format[1]->sql          = 'REPLACE(FORMAT(??,1), ",", "")';

	$format[2]->type         = 'number';
	$format[2]->format       = '2';
	$format[2]->decimal      = '.';
	$format[2]->separator    = '';
	$format[2]->sample       = '10000.00';
	$format[2]->phpdate      = '';
	$format[2]->sql          = 'REPLACE(FORMAT(??,2), ",", "")';

	$format[3]->type         = 'number';
	$format[3]->format       = '3';
	$format[3]->decimal      = '.';
	$format[3]->separator    = '';
	$format[3]->sample       = '10000.000';
	$format[3]->phpdate      = '';
	$format[3]->sql          = 'REPLACE(FORMAT(??,3), ",", "")';

	$format[4]->type         = 'number';
	$format[4]->format       = '4';
	$format[4]->decimal      = '.';
	$format[4]->separator    = '';
	$format[4]->sample       = '10000.0000';
	$format[4]->phpdate      = '';
	$format[4]->sql          = 'REPLACE(FORMAT(??,4), ",", "")';

	$format[5]->type         = 'number';
	$format[5]->format       = '5';
	$format[5]->decimal      = '.';
	$format[5]->separator    = '';
	$format[5]->sample       = '10000.00000';
	$format[5]->phpdate      = '';
	$format[5]->sql          = 'REPLACE(FORMAT(??,5), ",", "")';

//-----date formats

	$format[6]->type         = 'date';
	$format[6]->format       = 'dd-mmm-yyyy';
	$format[6]->decimal      = '.';
	$format[6]->separator    = '';
	$format[6]->sample       = '13-Jan-2007';
	$format[6]->phpdate      = 'd-M-Y';
	$format[6]->sql          = 'DATE_FORMAT(??,"%d-%b-%Y")';

	$format[7]->type         = 'date';
	$format[7]->format       = 'dd-mm-yyyy';
	$format[7]->decimal      = '.';
	$format[7]->separator    = '';
	$format[7]->sample       = '13-01-2007';
	$format[7]->phpdate      = 'd-m-Y';
	$format[7]->sql          = 'DATE_FORMAT(??,"%d-%m-%Y")';

	$format[8]->type         = 'date';
	$format[8]->format       = 'mmm-dd-yyyy';
	$format[8]->decimal      = '.';
	$format[8]->separator    = '';
	$format[8]->sample       = 'Jan-13-2007';
	$format[8]->phpdate      = 'M-d-Y';
	$format[8]->sql          = 'DATE_FORMAT(??,"%b-%d-%Y")';

	$format[9]->type         = 'date';
	$format[9]->format       = 'mm-dd-yyyy';
	$format[9]->decimal      = '.';
	$format[9]->separator    = '';
	$format[9]->sample       = '01-13-2007';
	$format[9]->phpdate      = 'm-d-Y';
	$format[9]->sql          = 'DATE_FORMAT(??,"%m-%d-%Y")';

	$format[10]->type        = 'date';
	$format[10]->format      = 'dd-mmm-yy';
	$format[10]->decimal     = '.';
	$format[10]->separator   = '';
	$format[10]->sample      = '13-Jan-07';
	$format[10]->phpdate     = 'd-M-y';
	$format[10]->sql         = 'DATE_FORMAT(??,"%d-%b-%y")';

	$format[11]->type        = 'date';
	$format[11]->format      = 'dd-mm-yy';
	$format[11]->decimal     = '.';
	$format[11]->separator   = '';
	$format[11]->sample      = '13-01-07';
	$format[11]->phpdate     = 'd-m-y';
	$format[11]->sql         = 'DATE_FORMAT(??,"%d-%m-%y")';

	$format[12]->type        = 'date';
	$format[12]->format      = 'mmm-dd-yy';
	$format[12]->decimal     = '.';
	$format[12]->separator   = '';
	$format[12]->sample      = 'Jan-13-07';
	$format[12]->phpdate     = 'M-d-y';
	$format[12]->sql         = 'DATE_FORMAT(??,"%b-%d-%y")';

	$format[13]->type        = 'date';
	$format[13]->format      = 'mm-dd-yy';
	$format[13]->decimal     = '.';
	$format[13]->separator   = '';
	$format[13]->sample      = '01-13-07';
	$format[13]->phpdate     = 'm-d-y';
	$format[13]->sql         = 'DATE_FORMAT(??,"%m-%d-%y")';

//-----number formats

	$format[14]->type        = 'number';
	$format[14]->format      = '0';
	$format[14]->decimal     = '.';
	$format[14]->separator   = ',';
	$format[14]->sample      = '10,000';
	$format[14]->phpdate     = '';
	$format[14]->sql         = 'FORMAT(??,0)';

	$format[15]->type        = 'number';
	$format[15]->format      = '1';
	$format[15]->decimal     = '.';
	$format[15]->separator   = ',';
	$format[15]->sample      = '10,000.0';
	$format[15]->phpdate     = '';
	$format[15]->sql         = 'FORMAT(??,1)';

	$format[16]->type        = 'number';
	$format[16]->format      = '2';
	$format[16]->decimal     = '.';
	$format[16]->separator   = ',';
	$format[16]->sample      = '10,000.00';
	$format[16]->phpdate     = '';
	$format[16]->sql         = 'FORMAT(??,2)';

	$format[17]->type        = 'number';
	$format[17]->format      = '3';
	$format[17]->decimal     = '.';
	$format[17]->separator   = ',';
	$format[17]->sample      = '10,000.000';
	$format[17]->phpdate     = '';
	$format[17]->sql         = 'FORMAT(??,3)';

	$format[18]->type        = 'number';
	$format[18]->format      = '4';
	$format[18]->decimal     = '.';
	$format[18]->separator   = ',';
	$format[18]->sample      = '10,000.0000';
	$format[18]->phpdate     = '';
	$format[18]->sql         = 'FORMAT(??,4)';

	$format[19]->type        = 'number';
	$format[19]->format      = '5';
	$format[19]->decimal     = '.';
	$format[19]->separator   = ',';
	$format[19]->sample      = '10,000.00000';
	$format[19]->phpdate     = '';
	$format[19]->sql         = 'FORMAT(??,5)';

	return $format;

}

//--auto colors for bar graphs
function setColourArray() {

    $colourarray = Array();
    $colourarray[0] = "aqua";
    $colourarray[1] = "red";
    $colourarray[2] = "blue";
    $colourarray[3] = "gold";
    $colourarray[4] = "green";
    $colourarray[5] = "orange";
    $colourarray[6] = "purple";
    $colourarray[7] = "pink";
    $colourarray[8] = "brown";
    $colourarray[9] = "goldenrod";
    $colourarray[10] = "khaki";
    $colourarray[11] = "lawngreen";
    $colourarray[12] = "orangered";
    $colourarray[13] = "magenta";
    $colourarray[14] = "lightblue";
    $colourarray[15] = "silver";
    $colourarray[16] = "tan";
    $colourarray[17] = "deeppink";
    $colourarray[18] = "eggplant";
    $colourarray[19] = "lime";
    $colourarray[20] = "peru";
    $colourarray[21] = "lightred";
    $colourarray[22] = "lightblue";
    return $colourarray;

}


//---convert dd-mm-yyyy format to d-m-Y
function convertToPhpDateFormat($format){

	$newFormat = str_replace('dd', 'd', $format);
	$newFormat = str_replace('mmm', 'M', $newFormat);
	$newFormat = str_replace('mm', 'm', $newFormat);
	$newFormat = str_replace('yyyy', 'Y', $newFormat);
	$newFormat = str_replace('yy', 'y', $newFormat);
    return $newFormat;

}



//---add days (or subtract) to a date. (returns format '2006-11-20')
function nuDateAddDays($Date,$Days){

    $d=substr($Date,-2);
    $m=substr($Date,5,2);
    $y=substr($Date,0,4);
    return date ("Y-m-d", mktime (0,0,0,$m,$d+$Days,$y));

}

//---formats a date with php date() format strings
function nuDateFormat($Date,$Format){

	if($Date=='NULL'){return '';}
    $d=substr($Date,-2);
    $m=substr($Date,5,2);
    $y=substr($Date,0,4);
//tofile("Date,Format   $Date,$Format");
    return date ($Format, mktime (0,0,0,$m,$d,$y));

}

function Today(){
    return date("Y-m-d");
}





//---send an email
function nu_mail($pTo, $pFrom, $pSubject, $pMessage){

//--using http://www.pear.php.net
    require_once('Mail.php');
    $headers['To'] = $pTo;
    if($pFrom == ''){
    	$headers['From'] = 'admin@pcp2000.com.au';    	
    }else{
    	$headers['From'] = $pFrom;    	
    }
    $headers['Subject'] = $pSubject;
    $headers['MIME-Version'] = '1.0';
    $headers['Content-type'] = 'text/html; charset=iso-8859-1';

    $params['host'] = '127.0.0.1';
    $mail_object = & Mail::factory('smtp', $params);
    return $mail_object->send($pTo, $headers, $pMessage);

}

//----takes value returned by RunQuery as a parameter and returns all its fieldnames in an array
function TableFieldNames($t){
    $to=mysql_num_fields($t);

    for($i=0;$i<$to;$i++){
        $fn=mysql_field_name($t,$i);
        $FieldName[]=$fn;
    }
    return $FieldName;
}

//----spits a paragraph into lines of text and puts them in an array starting from 1 (not 0)
//---- 1st parameter is the paragraph, 2nd parameter is the maximum length of each string of text
function splitText($s,$l){

    $line[]='';
    while(strlen($s)<>0){
        $chop=ChopPosition($s,$l);
        $line[]=substr($s,0,$chop);
        $s=substr($s,$chop);
    }
    return $line;

}

//-------- finds position to chop off text (ready for the next line)
function ChopPosition($string,$maxLength){

    $S=substr($string,0,$maxLength+1);
//--find next carriage return
    if(strpos($S, "\n")===false){
    }else{
        return strpos($S, "\n")+1;
    }
//--find last space within maximum length
    if(strrpos($S, " ")===false OR strlen($S)<=$maxLength){
      return $maxLength;
    }else{
      return strrpos($S, " ")+1;
    }

}

function TT(){
//--create a unique name for a Temp Table
	return '___nu'.uniqid('1').'___';
}


function StringIsTrue($pstring){
	$t=nuRunQuery("SELECT $pstring",0);
	$r=mysql_fetch_row($t);
	return $r[0];
}

function GetHashVariables($SQL){
	$var[]='';
	for($i=0;strlen($SQL);$i++){
		$firsthash=strpos($SQL,'#',$i);
		if($firsthash===false){return $var;}
		$i=$firsthash;
		$secondhash=strpos($SQL,'#',$i+1);
		if($secondhash===false){return $var;}
		$var[]=substr($SQL, $firsthash, $secondhash-$firsthash+1);
		$i=$secondhash;
	}



}

function FromToday($years,$months,$days){;
	$d=date('d');
	$m=date('m');
	$y=date('y');
	$new= date("Y-m-d", mktime (0,0,0,$m+$months,$d+$days,$y+$years));
	return $new;
}

function Dlookup($f,$t,$c){
	$t=nuRunQuery("SELECT $f FROM $t WHERE $c");
	$r = mysql_fetch_row($t);
	return $r[0];
}


function onerecord($table,$id){
//---returns row as an object (eg. $r->Name)
	$t = nuRunQuery("Select * FROM $table WHERE $table"."ID = '$id'");
	$r = mysql_fetch_object($t);
	return $r;
}

function align($pAlign){
	if($pAlign == 'l'){return 'left';}
	if($pAlign == 'r'){return 'right';}
	if($pAlign == 'c'){return 'center';}
}

function nuSetup(){

	$t=nuRunQuery("Select * From zzsys_setup");
	$r = db_fetch_object($t);
	return $r;

}



function tofile($text){
//----writes to a systrap for debuging
	$text=str_replace("'", "\'", $text);
	nuRunQuery("Insert INTO zzsys_trap (tra_message, sys_added) VALUES ('$text',".date('"Y-m-d h:i:s"').")");
	return $text;
}


function nuDebug($text){
//----writes to a systrap for debuging
	$text=str_replace("'", "\'", $text);
	nuRunQuery("Insert INTO zzsys_trap (tra_message, sys_added) VALUES ('$text',".date('"Y-m-d h:i:s"').")");
	return $text;
}



function jsdate($pdate){
//---------creates a javascript date string from a mysql date
	$newmth='01';
	$mth = $pdate[5].$pdate[6];
	if($mth=='01'){$newmth='Jan';}
	if($mth=='02'){$newmth='Feb';}
	if($mth=='03'){$newmth='Mar';}
	if($mth=='04'){$newmth='Apr';}
	if($mth=='05'){$newmth='May';}
	if($mth=='06'){$newmth='Jun';}
	if($mth=='07'){$newmth='Jul';}
	if($mth=='08'){$newmth='Aug';}
	if($mth=='09'){$newmth='Sep';}
	if($mth=='10'){$newmth='Oct';}
	if($mth=='11'){$newmth='Nov';}
	if($mth=='12'){$newmth='Dec';}
	return $pdate[8].$pdate[9]."-".$newmth."-".$pdate[0].$pdate[1].$pdate[2].$pdate[3];

}

function left($s,$places){
	return substr($s, 0, $places);
}

function right($s,$places){
	return substr($s, $places*-1);
}
function iif($condition,$true,$false){
//---------immediate if function
	if($condition){
		return $true;
	}else{
		return $false;
	}
}

function msql_date($pdate){
//---------creates a date string eg. '2004-10-25' from a formatted javascript date eg '25-Oct-2004', with single quotes that can go into mysql
	$pdate=iif(strlen($pdate)==10,'0'.$pdate,$pdate);
	$newmth='01';
	$mth = $pdate[3].$pdate[4].$pdate[5];
	if($mth=='Jan'){$newmth='01';}
	if($mth=='Feb'){$newmth='02';}
	if($mth=='Mar'){$newmth='03';}
	if($mth=='Apr'){$newmth='04';}
	if($mth=='May'){$newmth='05';}
	if($mth=='Jun'){$newmth='06';}
	if($mth=='Jul'){$newmth='07';}
	if($mth=='Aug'){$newmth='08';}
	if($mth=='Sep'){$newmth='09';}
	if($mth=='Oct'){$newmth='10';}
	if($mth=='Nov'){$newmth='11';}
	if($mth=='Dec'){$newmth='12';}
	return "'".$pdate[7].$pdate[8].$pdate[9].$pdate[10].$pdate[6].monthNumber($mth).$pdate[6].$pdate[0].$pdate[1]."'";
}


function mysql_date($pdate){
//---------creates a date string eg. '2004-10-25' from a formatted javascript date eg '25-Oct-2004', with single quotes that can go into mysql
	$pdate=iif(strlen($pdate)==10,'0'.$pdate,$pdate);
	$newmth='01';
	$mth = $pdate[3].$pdate[4].$pdate[5];
	if($mth=='Jan'){$newmth='01';}
	if($mth=='Feb'){$newmth='02';}
	if($mth=='Mar'){$newmth='03';}
	if($mth=='Apr'){$newmth='04';}
	if($mth=='May'){$newmth='05';}
	if($mth=='Jun'){$newmth='06';}
	if($mth=='Jul'){$newmth='07';}
	if($mth=='Aug'){$newmth='08';}
	if($mth=='Sep'){$newmth='09';}
	if($mth=='Oct'){$newmth='10';}
	if($mth=='Nov'){$newmth='11';}
	if($mth=='Dec'){$newmth='12';}
	return "'".$pdate[7].$pdate[8].$pdate[9].$pdate[10].$pdate[6].monthNumber($mth).$pdate[6].$pdate[0].$pdate[1]."'";
}

function monthNumber($pMonth){

	if($pMonth=='Jan'){return '01';}
	if($pMonth=='Feb'){return '02';}
	if($pMonth=='Mar'){return '03';}
	if($pMonth=='Apr'){return '04';}
	if($pMonth=='May'){return '05';}
	if($pMonth=='Jun'){return '06';}
	if($pMonth=='Jul'){return '07';}
	if($pMonth=='Aug'){return '08';}
	if($pMonth=='Sep'){return '09';}
	if($pMonth=='Oct'){return '10';}
	if($pMonth=='Nov'){return '11';}
	if($pMonth=='Dec'){return '12';}
	return '';
}

function msql_date_nq($pdate){
//---------creates a date string eg. 2004-10-25 from a formatted javascript date eg '25-Oct-2004', with no quotes
	$pdate=iif(strlen($pdate)==10,'0'.$pdate,$pdate);
	$newmth='01';
	$mth = $pdate[3].$pdate[4].$pdate[5];
	if($mth=='Jan'){$newmth='01';}
	if($mth=='Feb'){$newmth='02';}
	if($mth=='Mar'){$newmth='03';}
	if($mth=='Apr'){$newmth='04';}
	if($mth=='May'){$newmth='05';}
	if($mth=='Jun'){$newmth='06';}
	if($mth=='Jul'){$newmth='07';}
	if($mth=='Aug'){$newmth='08';}
	if($mth=='Sep'){$newmth='09';}
	if($mth=='Oct'){$newmth='10';}
	if($mth=='Nov'){$newmth='11';}
	if($mth=='Dec'){$newmth='12';}
	return $pdate[7].$pdate[8].$pdate[9].$pdate[10].$pdate[6].$newmth.$pdate[6].$pdate[0].$pdate[1];
}

function mysql_date_nq($pdate){
//---------creates a date string eg. 2004-10-25 from a formatted javascript date eg '25-Oct-2004', with no quotes
	$pdate=iif(strlen($pdate)==10,'0'.$pdate,$pdate);
	$newmth='01';
	$mth = $pdate[3].$pdate[4].$pdate[5];
	if($mth=='Jan'){$newmth='01';}
	if($mth=='Feb'){$newmth='02';}
	if($mth=='Mar'){$newmth='03';}
	if($mth=='Apr'){$newmth='04';}
	if($mth=='May'){$newmth='05';}
	if($mth=='Jun'){$newmth='06';}
	if($mth=='Jul'){$newmth='07';}
	if($mth=='Aug'){$newmth='08';}
	if($mth=='Sep'){$newmth='09';}
	if($mth=='Oct'){$newmth='10';}
	if($mth=='Nov'){$newmth='11';}
	if($mth=='Dec'){$newmth='12';}
	return $pdate[7].$pdate[8].$pdate[9].$pdate[10].$pdate[6].$newmth.$pdate[6].$pdate[0].$pdate[1];
}

Function nz($pValue,$pIfNull){

	if($pValue == ""){$pValue = $pIfNull;}
	Return $pValue;
}

function getLib() {

        $result = "";
        $sql = "SELECT slb_code FROM zzsys_library";
        $rs  = nuRunQuery($sql);
        while ($obj = mysql_fetch_object($rs)) {
		$result .= "\n".$obj->slb_code;
        }
        return $result;
}

//added by Nick 21/07/09
//takes a string, returns the first integer found within the string, as an integer/number
//if the string has no numbers, return 0
function parseInt($string) {
	if(preg_match('/(\d+)/', $string, $array)) {
		return $array[1];
	} else {
		return 0;
	}
}

?>
