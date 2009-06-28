<?php
/*
** File:           formduplicate.php
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

session_start();

$f         = $_GET['f'];
$r         = $_GET['r'];
$dir       = $_GET['dir'];
$ses       = $_GET['ses'];
$form_ses  = $_GET['form_ses'];
$debug     = $_GET['debug'];

include('../' . $dir . '/database.php');
include('common.php');

$formID                     = $f;
$recordID                   = $r;
$sessionID                  = $ses;
$value                      = array();
$form                       = formFields($formID);
$t                          = nuRunQuery("SELECT * FROM zzsys_object WHERE sob_zzsys_form_id = '$f'");
$dq                         = '"';



print "<html>\n";
print "<head>\n";
print "<meta http-equiv='Content-Type' content='text/html'/>\n";
print "<title></title>\n";
print "<script type='text/javascript' src='common.js' language='javascript'></script>\n";


print "<!-- Form Functions -->\n";
print "<script type='text/javascript'>\n";
print "/* <![CDATA[ */\n\n";

print "function checkDuplicate(){\n";
while($r               = db_fetch_array($t)){
	if($r['sob_'.$r['sob_all_type'].'_no_duplicates'] == '1'){ // eg 'sob_dropdown_no_duplicates'
		$field         = $r['sob_all_name'];
//		$value         = getFormValue($sessionID, $r['sob_all_name']);
		$value         = getFormValue($form_ses, $r['sob_all_name']);
		$newvalue      = $value[0];
		if($r['sob_'.$r['sob_all_type'].'_format']!=''){
			$newvalue = reformatField($value[0], $r['sob_'.$r['sob_all_type'].'_format'],false);
		}
		if(isDuplicate($sessionID, $form, $recordID, $field, $newvalue)){

			$T         = nuRunQuery("SELECT sob_all_title FROM zzsys_object WHERE zzsys_object_id = '".$r['zzsys_object_id']."'");
			$R         = db_fetch_object($T);
			$S         = "'there is already a record with a $R->sob_all_title of $dq".str_replace('"','',$value[0])."$dq'";
			print "   alert($S);\n";
			print "   return;\n";
		}
	}
}
print "   parent.frames['main'].document.forms[0]['beenedited'].value = '0';\n";
print "   parent.frames['main'].document.forms[0].action = 'formupdate.php?x=1&r=$recordID&dir=$dir&ses=$ses&f=$formID&debug=$debug';\n";
print "   parent.frames['main'].document.forms[0].submit();\n";
print "}\n";

print "/* ]]> */ ";
print "</script>";
print "<!-- End Of Form Functions -->";

print "</head>\n";
print "<body onload='checkDuplicate()'>\n";
print "<form>\n";
print "</form>\n";
print "</body>\n";
print "</html>\n";



function isDuplicate($session, $form, $recordID, $field, $value){

    $value   = str_replace("'","\\'",$value);
	$s       = "SELECT count(*) FROM $form->sfo_table ";
	$s       = $s . "WHERE $field = '$value' ";
	$s       = $s . "AND $form->sfo_primary_key != '$recordID'";
	$t       = nuRunQuery($s);
	$r       = db_fetch_row($t);
	if($r[0]!= 0){
		return true;
	}
	$id      = uniqid('1');
	$theDate = date('Y-m-d H:i:s');
	$s       = "INSERT INTO zzsys_duplicate (zzsys_duplicate_id, sdu_session_id, sdu_table_name, sdu_field_name, sdu_value, sdu_date)";
	$s       = $s . "VALUES ('$id', '$session', '$form->sfo_table', '$field', '$value', '$theDate')";
	nuRunQuery($s,false);
	$s       = "SELECT count(*) FROM zzsys_duplicate ";
	$s       = $s . "WHERE sdu_session_id = '$session' ";
	$s       = $s . "AND sdu_table_name = '$form->sfo_table' ";
	$s       = $s . "AND sdu_field_name = '$field' ";
	$s       = $s . "AND sdu_value = '$value' ";
	$t       = nuRunQuery($s);
	$r       = db_fetch_row($t);
	if($r[0]!= 0){
		return false;
	}
}


		
?>
