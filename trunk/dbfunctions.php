<?php
/*
** File:           dbfunctions.php
** Author:         nuSoftware
** Created:        2007/04/26
** Last modified:  2009/06/22
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


function dbQuery($DBHost, $DBName, $DBUserID, $DBPassWord, $pSQL, $pStopOnError){
//---------open connection and database and return query
	$con             = mysql_connect($DBHost,$DBUserID,$DBPassWord)  or die ("Could not connect to database");
	//mysql_query("SET NAMES utf8");
	//mysql_query("SET CHARACTER_SET utf8");
	mysql_select_db($DBName,$con) or die ("Could not select database");
	if($pStopOnError){//-----stop if there is an error
		$t           = mysql_query($pSQL) or die ("<HR/>Could not execute : -- <br/>\n<HR/>".$pSQL);
//---------log SQL statements
		if(strtoupper(substr(trim($pSQL), 0, 7))!='SELECT'){
			$t1       = mysql_query("Select * From zzsys_setup");
			$setup   = mysql_fetch_object($t1);
			if($setup->set_log_sql == '1'){
				$hex = str_hex($pSQL);
				mysql_query("INSERT INTO zzsys_sql_log (sql_sql) VALUES ('$hex')");
			}
		}
	}else{
		$t           = mysql_query($pSQL);
//---------log SQL statements
		if(strtoupper(substr(trim($pSQL), 0, 7))!='SELECT'){
			$t1       = mysql_query("Select * From zzsys_setup");
			$setup   = mysql_fetch_object($t1);
			if($setup->set_log_sql == '1'){
				$hex = str_hex($pSQL);
				mysql_query("INSERT INTO zzsys_sql_log (sql_sql) VALUES ('$hex')");
			}
		}
	}
	return $t;
}



function db_fetch_object($resource){
	return mysql_fetch_object($resource);
}

function db_fetch_row($resource){
	return mysql_fetch_row($resource);
}

function db_fetch_array($resource){
	return mysql_fetch_array($resource);
}
function db_fetch_field($resource){
	return mysql_fetch_field($resource);
}

function db_insert_id($resource){
	return mysql_insert_id();
}

function db_num_rows($resource){
	return mysql_num_rows($resource);
}

function tableFieldNamesToArray($resource){
    $to               = mysql_num_fields($resource);
    $nameArray        = array();

    for($i = 0 ; $i < $to ; $i++){
        $nameArray[]  = mysql_field_name($resource,$i);
    }
    return $nameArray;
}


?>
