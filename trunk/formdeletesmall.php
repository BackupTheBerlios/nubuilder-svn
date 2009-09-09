<?php
/*
** File:           formdeletesmall.php
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


$ses                             = $_GET['ses'];
$recordID                        = $_GET['r'];
$sysformID                       = $_GET['f'];
$dir                             = $_GET['dir'];

include("../$dir/database.php");
include('common.php');

$session = nuSession($ses);
if($session->foundOK == ''){
	print 'you have been logged out..';
	return;
}
	print "<html>\n";
	print "<head>\n";
	print "<meta http-equiv='Content-Type' content='text/html;'/>\n";
	print "<title></title>\n";
	
	print "<!-- Form Functions -->\n";
	print "<script type='text/javascript'>\n";
	print "/* <![CDATA[ */\n";
	print "function reload(pthis){//  reload form.php\n";
	if($_POST['del_ok'] == '1'){
		print "   document.forms[0].action = 'browsesmall.php?x=1&p=1&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&f=$sysformID';\n";
		print "   document.forms[0].submit();\n";
		$theform = formFields($sysformID);
		nuRunQuery("DELETE FROM $theform->sfo_table WHERE $theform->sfo_primary_key = '$recordID'");
		$t = nuRunQuery("SELECT * FROM zzsys_object WHERE sob_zzsys_form_id = '$sysformID' and sob_all_type = 'subform'");
		while($r = db_fetch_object($t)){
			nuRunQuery("DELETE FROM $r->sob_subform_table WHERE $r->sob_subform_foreign_key = '$recordID'");
		}
//---allow for custom code
        if($theform->sfo_custom_file_run_after_delete != ''){
			include("../$dir/custom/$theform->sfo_custom_file_run_after_delete");
			$return             = customDelete($recordID, $_POST, $sysformID);
			if($return != ''){
				$recordID  = $return;
	        }
       }
	}else{
		print "   document.forms[0].action = 'formsmall.php?x=1&r=$recordID&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&f=$sysformID';\n";
		print "   document.forms[0].submit();\n";
	}
	print "}\n";
	print "/* ]]> */ \n";
	print "</script>\n";
	print "<!-- End Of Form Functions -->\n";
	
	print "</head>\n";
	print "<body onload='reload()'>\n";
	print "<form name='theform' action='' method='post'>\n";


?>
