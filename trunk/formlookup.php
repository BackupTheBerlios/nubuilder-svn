<?php
/*
** File:           formlookup.php
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

session_start( );


	$dir          = $_GET['dir'];
	$form_ses     = $_GET['form_ses'];
	$ses          = $_GET['ses'];
	$prefix       = $_GET['p'];
	$o            = $_GET['o'];
	$r            = $_GET['r'];
	$n            = $_GET['n'];

	include("../$dir/database.php");
	include('common.php');

	$object       = objectFields($r);
	$lookupForm   = formFields($object->sob_lookup_zzsysform_id);
	$TT           = TT();
	$browseTable  = $TT;
	$updateField  = array();

//----------create an array of hash variables that can be used in any "hashString" 
	$sesVariables                    = recordToHashArray('zzsys_session', 'zzsys_session_id', $ses);  //--session values (access level and user etc. )
	$sesVariables['#TT#']            = $TT;
	$sesVariables['#browseTable#']   = $TT;
	$sesVariables['#formSessionID#'] = $form_ses;
	$sesVariables['#rowPrefix#']     = $prefix;
	$sysVariables                    = sysVariablesToHashArray($form_ses);                            //--values in sysVariables from the calling lookup page
	$arrayOfHashVariables            = joinHashArrays($sysVariables, $sesVariables);                  //--join the arrays together
	$nuHashVariables                 = $arrayOfHashVariables;   //--added by sc 23-07-2009
	//----------allow for custom code----------------------------------------------
	$globalValue                     = getglobalValue($session);
	eval(replaceHashVariablesWithValues($arrayOfHashVariables, $lookupForm->sfo_custom_code_run_before_browse));

	if(	$o  == 'id'){  //id or code
		$lookIn   =  $object->sob_lookup_id_field;
	}else{
		$lookIn   =  $object->sob_lookup_code_field;
	}
	$newID        = $n;
	$fieldNames   = array();

	$t            = nuRunQuery("SELECT * FROM zzsys_form WHERE zzsys_form_id = '$object->sob_lookup_zzsysform_id'");
	$form         = db_fetch_object($t);

	print "<html>\n";
	print "<head>\n";
	print "<meta http-equiv='Content-Type' content='text/html;'/>\n";
	print "<title></title>\n";
	print "<script type='text/javascript' src='common.js' language='javascript'></script>\n";
	print "\n\n<!-- Form Functions -->\n";
	print "<script type='text/javascript'>\n";
	print "/* <![CDATA[ */\n\n";

    print "var nuScreen = window.parent.frames[0];\n";
    print "var theName  = '';\n\n";

	print "function customDirectory(){\n";
	print "   return '$dir';\n";
	print "}\n";
	print "\n";
	print "function form_session_id(){\n";
	print "   return '$form_ses';\n";
	print "}\n";
	print "function fillfields(){\n";
	print "   for (var i=0; i < document.forms[0].elements.length-3; i++){\n";
	print "      theName = document.forms[0].elements[i].name;\n";
	print "      nuScreen.document.forms[0][theName].value = document.forms[0].elements[i].value;\n";
	print "      nuFormat(nuScreen.document.forms[0][theName]);\n";

	print "      if(nuScreen.document.getElementById('____' + theName)){\n";                //--check if there is such a fieldname
	print "      	nuScreen.document.getElementById('____' + theName).value = '';\n";      //--set value to blank so that it gets updated
	print "      }\n";
	

	print "   }\n";
	if(hasDeleteBox($object->sob_zzsys_form_id)){
		print "   window.parent.frames[0].document.forms[0]['row$prefix'].checked = false;\n";
	}
    if($o=='id'){ //--set focus to back to code fielde
    	print "   window.parent.frames[0].document.forms[0][document.forms[0].elements[1].name].focus();\n";
    }
	print "   $object->sob_lookup_javascript;\n";
	print "}\n";
	print setFormatArray();

	print "/* ]]> */ \n";
	print "</script>\n<!-- End Of Form Functions -->\n\n";

	$old_sql_string                = $form->sfo_sql;
	$new_sql_string                = replaceHashVariablesWithValues($arrayOfHashVariables, $old_sql_string);
	
	$SQL               = new sqlString($new_sql_string);
	
	if($SQL->where == ''){
		$SQL->setWhere("WHERE $lookIn = '$newID'");
	}else{
		$SQL->setWhere("$SQL->where AND ($lookIn = '$newID')");		
	}
	
	$SQL->removeAllFields();
	$SQL->addField($object->sob_lookup_id_field);
	$fieldNames[]                          = $prefix.$object->sob_all_name;
	$SQL->addField($object->sob_lookup_code_field);
	$fieldNames[]                          = 'code'.$prefix.$object->sob_all_name;

	if($object->sob_lookup_no_description != '1' and $object->sob_lookup_description_field != ''){
		$fieldNames[]                      = 'description'.$prefix.$object->sob_all_name;
		$SQL->addField($object->sob_lookup_description_field);
	}
	
	$t                                     = nuRunQuery("SELECT * FROM zzsys_lookup WHERE slo_zzsys_object_id = '$object->zzsys_object_id'");
	while($r                               = db_fetch_object($t)){
		$SQL->addField($r->zzsys_slo_table_field_name);
		$fieldNames[]                      = $prefix.$r->zzsys_slo_page_field_name;
	}

	$sql1                                  = 'old    : '.$old_sql_string;
	$sql2                                  = 'new    : '.$new_sql_string;
	$sql3                                  = 'newest : '.$SQL->SQL;
	$T                                     = nuRunQuery($SQL->SQL);
	$R                                     = db_fetch_row($T);

	$arrayOfHashVariables['#selectedID#']  = $R[0];                                                                   //--ID of selected record

	eval(replaceHashVariablesWithValues($arrayOfHashVariables, $lookupForm->sfo_custom_code_run_after_browse));
	
	$s                                     = "<body onLoad='fillfields()'><form>\n";
	for($i = 0; $i < count($fieldNames) ; $i++){
		$s                                 = $s . "   <textarea name='$fieldNames[$i]'>$R[$i]</textarea>\n";
		update_zzsys_variables($fieldNames[$i], $R[$i], $field_array);
	}
	while(list($key, $value)               = each($updateField)){
		$s                                 = $s . "   <textarea name='$key'>$value</textarea>\n";
		update_zzsys_variables($key, $value, $field_array);
	}
	$s                                     = $s . "   <textarea name='sql1_old'>$sql1</textarea>\n";
	$s                                     = $s . "   <textarea name='sql2_new'>$sql2</textarea>\n";
	$s                                     = $s . "   <textarea name='sql3_newest'>$sql3</textarea>\n";
	$s                                     = $s . "</form></body></html>\n";

	print $s;

function hasDeleteBox($pParentID){
	
	$t = nuRunQuery("SELECT * FROM zzsys_object WHERE zzsys_object_id = '$pParentID'");
	$r = db_fetch_object($t);
	return $r->sob_subform_delete_box == '1';

}


function update_zzsys_variables($pField, $pValue, $field_array){

	$form_ses             = $_GET['form_ses'];
	setnuVariable($form_ses, nuDateAddDays(Today(),2), $pField, $pValue);

}


?>
