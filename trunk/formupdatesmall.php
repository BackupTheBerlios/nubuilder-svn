<?php
/*
** File:           formupdatesmall.php
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

$dir  = $_GET['dir'];     //-- directory of database.php
$ses  = $_GET['ses'];     //-- zzsys_session_id in zzsys_session
$f    = $_GET['f'];       //-- zzsys_form_id in zzsys_form
$r    = $_GET['fr'];      //-- form's record id
//$s    = $_GET['s'];

include('../' . $dir . '/database.php');
include('common.php');

$setup                       = nuSetup();

while(list($key, $value)    = each($_POST)){
	$uniq                   = uniqid('1');
//	nuRunQuery("INSERT INTO zzsys_small_form_value (zzsys_small_form_value_id, sfv_form_record, sfv_name, sfv_value) VALUES ('$uniq', '$f$r$ses', '$key', '$value')");
}


$formID                     = $f;
$recordID                   = $r;
$value                      = array();
$form                       = formFields($formID);
$t                          = nuRunQuery("SELECT * FROM zzsys_object WHERE sob_zzsys_form_id = '$f'");
$dq                         = '"';

print "<html>\n";
print "<head>\n";
print "<meta http-equiv='Content-Type' content='text/html;'/>\n";
print "<title></title>\n";
print "<script type='text/javascript' src='common.js' language='javascript'></script>\n";
print "<!-- Form Functions -->\n";
print "<script type='text/javascript'>\n";
print "/* <![CDATA[ */\n\n";

print "function loaded(){\n";
$duplicateFound        = false;
while($r               = db_fetch_array($t)){
	if(!$duplicateFound){
		if($r['sob_'.$r['sob_all_type'].'_no_duplicates'] == '1'){ // eg 'sob_dropdown_no_duplicates'
			$field         = $r['sob_all_name'];
			$value         = $_POST[$r['sob_all_name']];
			if(isDuplicate($sessionID, $form, $recordID, $field, $value)){
				$T         = nuRunQuery("SELECT sob_all_title FROM zzsys_object WHERE zzsys_object_id = '".$r['zzsys_object_id']."'");
				$R         = db_fetch_object($T);
				$S         = "'there is already a record with a ".str_replace('"','',$R->sob_all_title)." of $dq".str_replace('"','',$value)."$dq'";
				print "   alert($S);\n";
//				print "   return;\n";
				$duplicateFound = true;
			}
		}
	}
}
if(!$duplicateFound){
//-------------------------update record

	reset($_POST);
	$_SESSION['holdingVaribles']  = false; 
	$fieldProperties              = array();
	$recordFields                 = array();
	$formFields                   = array();
	$formValues                   = array();
	$form                         = formFields($formID);
	$dq                           = '"';
	// get all fields used on this form
	$t                            = nuRunQuery("SELECT * FROM $form->sfo_table WHERE FALSE");
	while ($f                     = db_fetch_field($t)){
		$recordFields[]           = $f->name;
	}
	//  get formatting properties for this form's fields
	$t                            = nuRunQuery("SELECT sob_all_name, sob_text_format FROM zzsys_object WHERE sob_all_type = 'text' AND sob_zzsys_form_id = '$formID'");
	while ($r                     = db_fetch_object($t)){
		$fieldProperties[$r->sob_all_name] = $r->sob_text_format;
	}
	while(list($key, $value)      = each($_POST)){
		if(in_array ($key, $recordFields)){
			$formFields[]       = $key;
			$formValues[]       = reformatField($value,$fieldProperties[$key]);
		}
	}
	reset($_POST);
	//  if the following fields are in the table they will be updated
	if(in_array ('sys_added', $recordFields) and $recordID == '-1'){
		$formFields[]           = 'sys_added';
		$formValues[]           = "'" . date('Y-m-d H:i:s') . "'";
	}
	if(in_array ('sys_changed', $recordFields)){
		$formFields[]           = 'sys_changed';
		$formValues[]           = "'" . date('Y-m-d H:i:s') . "'";
	}
	if(in_array ('sys_user_id', $recordFields)){
		$formFields[]           = 'sys_user_id';
		$formValues[]           = "'" . $_SESSION['zzsys_user_id'] . "'";
	}
	$insertFields               = '';
	$insertValues               = '';
	$updateString               = '';
	
	for($i = 0 ; $i < count($formFields) ; $i++){
	
		if($insertFields        == ''){
			$newID              = uniqid('1');
			$insertFields       = "$form->sfo_primary_key, $formFields[$i]";
			$insertValues       = "$dq$newID$dq, $formValues[$i]";
			$updateString       = "$formFields[$i] = $formValues[$i]";
		}else{
			$insertFields       = "$insertFields, $formFields[$i]";
			$insertValues       = "$insertValues, $formValues[$i]";
			$updateString       = "$updateString, $formFields[$i] = $formValues[$i]";
		}
	
	}

	if(count($formFields) > 0){
		if($recordID                == '-1'){
			$recordID               = $newID; // new record ID
			$s                      = str_replace('#fields#', $insertFields, $form->sfo_insert_sql);
			$s                      = str_replace('#values#', $insertValues, $s);
		}else{
			$s                      = "UPDATE $form->sfo_table SET $updateString WHERE $form->sfo_primary_key = '$recordID'";
		}
		nuRunQuery($s);
	}
	


//-------------------------end of update record
}
print "   document.forms[0].action = 'formsmall.php?x=1&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&fr=$recordID&f=$formID';\n";
print "   document.forms[0].submit();\n";
print "}\n";

print "/* ]]> */ ";
print "</script>";
print "<!-- End Of Form Functions -->";

print "</head>\n";
print "<body onload='loaded()'>\n";
print "<form method='post'>\n";
print "</form>\n";
print "</body>\n";
print "</html>\n";


function isDuplicate($session, $form, $recordID, $field, $value){
	
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
	$s       = "INSERT INTO zzsys_duplicate (zzsys_duplicate_id, sdu_session_id, sdu_table_name, sdu_field_name, sdu_value, sdu_date) ";
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
