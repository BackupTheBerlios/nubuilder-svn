<?php
/*
** File:           formdelete.php
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

	$ses                          = $_GET['ses'];
	$recordID                     = $_GET['r'];
	$sysformID                    = $_GET['f'];
	$dir                          = $_GET['dir'];
	
	include("../$dir/database.php");
	include('common.php');

    if(passwordNeeded($sysformID)){
    	$session = nuSession($ses);
    	if($session->foundOK == ''){
    		print 'you have been logged out..';
    		return;
    	}
    }

	$setup                         = nuSetup();

//----------create an array of hash variables that can be used in any "hashString" 
	$sVariables                             = recordToHashArray('zzsys_session', 'zzsys_session_id', $ses);  //--session values (access level and user etc. )
//----------allow for custom code----------------------------------------------
//	eval(replaceHashVariablesWithValues($sVariables, $setup->set_php_code)); //--replace hash variables then run code


	print "<html>\n";
	print "<head>\n";
	print "<meta http-equiv='Content-Type' content='text/html'/>\n";
	print "<title></title>\n";
	
	print "<!-- Form Functions -->\n";
	print "<script type='text/javascript'>\n";
	print "/* <![CDATA[ */\n";
	print "function reload(pthis){//  reload form.php\n";
	if($_POST['del_ok'] == '1'){
		print "   parent.window.close();\n";
		print "   parent.opener.document.focus();\n";
		print "   parent.opener.document.forms[0].submit();\n";
		$theform = formFields($sysformID);
		nuRunQuery("DELETE FROM $theform->sfo_table WHERE $theform->sfo_primary_key = '$recordID'");
		$t = nuRunQuery("SELECT * FROM zzsys_object WHERE sob_zzsys_form_id = '$sysformID' and sob_all_type = 'subform'");
		while($r = db_fetch_object($t)){
			nuRunQuery("DELETE FROM $r->sob_subform_table WHERE $r->sob_subform_foreign_key = '$recordID'");
		}

//----------create an array of hash variables that can be used in any "hashString" 
		$arrayOfHashVariables1         = postVariablesToHashArray();//--values of this record
		$arrayOfHashVariables1['#id#'] = $theRecordID;                                                  //--this record's id
		$arrayOfHashVariables          = recordToHashArray('zzsys_session', 'zzsys_session_id', $ses);  //--session values (access level and user etc. )
		$arrayOfHashVariables          = joinHashArrays($arrayOfHashVariables, $arrayOfHashVariables1);      //--join the arrays together
		$nuHashVariables               = $arrayOfHashVariables;   //--added by sc 23-07-2009
//----------allow for custom code----------------------------------------------
		eval(replaceHashVariablesWithValues($arrayOfHashVariables, $theform->sfo_custom_code_run_after_delete));
	}else{
		print "   parent.frames['main'].document.forms[0].action = 'form.php?x=1&r=$recordID&dir=$dir&ses=$ses&f=$sysformID';\n";
		print "   parent.frames['main'].document.forms[0].submit();\n";
	}
	print "}\n";
	print "/* ]]> */ \n";
	print "</script>\n";
	print "<!-- End Of Form Functions -->\n";
	
	print "</head>\n";
	print "<body onload='reload()'>\n";
	print "<form name='theform' action='' method='post'>\n";


?>
