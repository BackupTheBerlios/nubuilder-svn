<?php
/*
** File:           formupdate.php
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

session_start();

$f       = $_GET['f'];
$r       = $_GET['r'];
$s       = $_GET['s'];
$dir     = $_GET['dir'];
$ses     = $_GET['ses'];
$changed = $_POST;

include('../' . $dir . '/database.php');
include('common.php');

$session = nuSession($ses);

$setup                        = nuSetup();


$sysformID                    = $_GET['f'];
$recordID                     = $_GET['r'];
$fieldProperties              = array();
$recordFields                 = array();
$formFields                   = array();
$formValues                   = array();
$form                         = formFields($sysformID);
$dq                           = '"';
$bs                           = '\\';
// get all fields used on this form
$t                            = nuRunQuery("SELECT * FROM $form->sfo_table WHERE FALSE");
while ($f                     = db_fetch_field($t)){
	$recordFields[]           = $f->name;
}
//  get formatting properties for this form's fields
$t                            = nuRunQuery("SELECT sob_all_name, sob_text_format FROM zzsys_object WHERE sob_all_type = 'text' AND sob_zzsys_form_id = '$sysformID'");
while ($r                     = db_fetch_object($t)){
	$fieldProperties[$r->sob_all_name] = $r->sob_text_format;
}
while(list($key, $value)      = each($_POST)){
	if(in_array ($key, $recordFields) and ($changed['____'.$key] == '' or $recordID == '-1')){ //--valid fld name, has been changed or its a new record
//	if(in_array ($key, $recordFields) ){
		$formFields[]       = $key;
		$formValues[]       = "'" . str_replace("'","\'",str_replace($bs,$bs.$bs,reformatField($value,$fieldProperties[$key],false))) . "'";
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
	$formValues[]           = "'" . $session->sss_zzsys_user_id . "'";
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
if(count($formFields) > 0){ //--dont update if there has been nothing changed
	if($recordID                == '-1'){
		$recordID               = $newID; // new record ID

		$s                      = str_replace('#fields#', $insertFields, "INSERT INTO $form->sfo_table (#fields#) VALUES (#values#)");
		$s                      = str_replace('#values#', $insertValues, $s);
	}else{
		$s                      = "UPDATE $form->sfo_table SET $updateString WHERE $form->sfo_primary_key = '$recordID'";
	}

	nuRunQuery($s);
	
}

reset($_POST);

for($i = 0 ; $i < $_POST['TheSubforms'] ; $i++){
	$SF->Name                   = $_POST['SubformNumber'.$i];
	$recordFields               = array();
	$SF->Table                  = $_POST['table'.$SF->Name];
	$t                          = nuRunQuery("SELECT * FROM $SF->Table WHERE FALSE");
	while ($f                   = db_fetch_field($t)){
		$recordFields[]         = $f->name;
	}

	$SF->ID                     = $_POST['subformid'.$SF->Name];
	$SF->Rows                   = $_POST['rows'.$SF->Name];
	$SF->Columns                = $_POST['columns'.$SF->Name];
	$SF->ForeignKey             = $_POST['foreignkey'.$SF->Name];
	$SF->PrimaryKey             = $_POST['primarykey'.$SF->Name];
	$SF->ReadOnly               = $_POST['readonly'.$SF->Name];
	$SF->ColumnName             = array();

	for($I=0 ; $I < $SF->Columns ; $I++){
		if(in_array($_POST[$SF->Name.$I], $recordFields)){
			$SF->ColumnName[]   = $_POST[$SF->Name.$I];
		}
	}
	if($SF->ReadOnly !=1 ){ //--will not update readonly subforms
		updateSubform($_POST, $SF, $recordID);
	}
}
//----------create an array of hash variables that can be used in any "hashString" 
	$sesVariables                       = recordToHashArray('zzsys_session', 'zzsys_session_id', $ses);  //--session values (access level and user etc. )
	$sysVariables                       = postVariablesToHashArray();                                    //--values in $_POST
	$arrayOfHashVariables               = joinHashArrays($sysVariables, $sesVariables);                  //--join the arrays together
    $arrayOfHashVariables['#newID#']    = $recordID;
	$nuHashVariables                    = $arrayOfHashVariables;   //--added by sc 23-07-2009

//----------allow for custom code----------------------------------------------

    $code                               = replaceHashVariablesWithValues($arrayOfHashVariables, $form->sfo_custom_code_run_after_save);

    if($_GET['debug']!=''){
        tofile('sfo_custom_code_run_after_save hash variables : debug value:'.$_GET['debug']);
        tofile(print_r($arrayOfHashVariables,true));
        tofile($code);
    }

	eval($code);

        print "<html>\n";
		print "<head>\n";
		print "<meta http-equiv='Content-Type' content='text/html;'/>\n";
		print "<title></title>\n";

		print "<!-- Form Functions -->\n";
		print "<script type='text/javascript'>\n";
		print "/* <![CDATA[ */\n";
		print "function reload(pthis){//  reload form.php\n";
		if($_POST['refresh_after_save'] == '1'){
			print "      try{if(parent.opener.document.forms[0].name=='thebrowse'){parent.opener.document.forms[0].submit();}}catch(err){}\n";
		}
		if($_POST['close_after_save'] == '1'){
			print "   parent.window.close();\n";
			if($_POST['refresh_after_save'] == '1'){
				print "   parent.opener.document.focus();\n";
				print "   parent.opener.document.forms[0].submit();\n";
			}
		}else{
			if($_POST['refresh_after_save'] == '1'){
                $dbg = '&debug=' . $_GET['debug'];
				print "   parent.frames['main'].document.forms[0].action = 'form.php?x=1&r=$recordID&dir=$dir&ses=$ses&f=$sysformID$dbg';\n";
				print "   parent.frames['main'].document.forms[0].submit();\n";
			}
		}
		print "}\n";
		print "/* ]]> */ \n";
		print "</script>\n";
		print "<!-- End Of Form Functions -->\n";

		print "</head>\n";
        print "<body onload='reload()'>\n";
        print "<form name='theform' action='' method='post'>\n";


return;


function updateSubform($P, $SF, $recordID){

	$bs                           = '\\';
	$dq                           = '"';
	$insertFields                 = '';
	$insertValues                 = '';
//  get subform's insert sql statement
//	$t                            = nuRunQuery("SELECT sob_subform_insert_sql, sob_subform_table, sob_subform_primary_key FROM zzsys_object WHERE zzsys_object_id = '$SF->ID'");
	$t                            = nuRunQuery("SELECT sob_subform_table, sob_subform_primary_key FROM zzsys_object WHERE zzsys_object_id = '$SF->ID'");
	$subform                      = db_fetch_object($t);
//  get formatting properties for this form's fields
	$t                            = nuRunQuery("SELECT sob_all_name, sob_text_format FROM zzsys_object WHERE sob_all_type = 'text' AND sob_zzsys_form_id = '$SF->ID'");
	while ($r                     = db_fetch_object($t)){
		$fieldProperties[$r->sob_all_name] = $r->sob_text_format;
	}

	for($i = 0 ; $i < $SF->Rows ; $i++){

		$PRE                  = $SF->Name . right('000'.$i,4);
		$checkBox             = 'row' . $PRE;
		$primaryKey           = $P[$PRE . $SF->PrimaryKey];
		$foreignKey           = $recordID;

		if($P[$checkBox] == 'on' AND $primaryKey != ''){
			nuRunQuery("DELETE FROM $subform->sob_subform_table WHERE $subform->sob_subform_primary_key = '$primaryKey'");
		}
		if($P[$checkBox] <> 'on'){// delete box not ticked
			for($I = 0 ; $I < count($SF->ColumnName) ; $I++){
				if($P['____'.$PRE.$SF->ColumnName[$I]] == '' or $primaryKey == ''){   //---has been edited or is a new record
				
					$fieldTitle           = $SF->ColumnName[$I];
					$fieldValue           = $P["$PRE$fieldTitle"];
					$fieldValue           = "'" . str_replace("'","\'",str_replace($bs,$bs.$bs,reformatField($fieldValue,$fieldProperties[$fieldTitle],false))) . "'";
					if($primaryKey        == ''){
						if($insertFields  == ''){
							$newID        = uniqid('1');
							$insertFields = "$SF->PrimaryKey, $SF->ForeignKey, $fieldTitle";
							$insertValues = "$dq$newID$dq, $dq$foreignKey$dq, $fieldValue";
						}else{
							$insertFields = "$insertFields, $fieldTitle";
							$insertValues = "$insertValues, $fieldValue";
						}
					}else{
						if($insertFields  == ''){
							$insertFields = "$SF->ForeignKey = $dq$foreignKey$dq, $fieldTitle = $fieldValue";
						}else{
							$insertFields = "$insertFields, $fieldTitle = $fieldValue";
						}
					}
				}
			}
			if($insertFields  != ''){ //-- only update if something was changed or added
				if($primaryKey            == ''){
					$s                    = str_replace('#fields#', $insertFields, "INSERT INTO $subform->sob_subform_table (#fields#) VALUES (#values#)");
					$runSQL               = str_replace('#values#', $insertValues, $s);
				}else{
					$s                    = "UPDATE $subform->sob_subform_table SET #fields# WHERE $subform->sob_subform_primary_key = '$primaryKey'";
					$runSQL               = str_replace('#fields#', $insertFields, $s);
				}
				nuRunQuery($runSQL);
				$insertFields             = '';
				$insertValues             = '';
			}
		}
	}
}

?>
