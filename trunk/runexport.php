<?php
/*
** File:           runexport.php
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

Header ("Content-Disposition: attachment; filename=file.csv");

$ses                             = $_GET['ses'];
$form_ses                        = $_GET['form_ses'];
$report                          = $_GET['r'];
$dir                             = $_GET['dir'];

include("../$dir/database.php");
include('common.php');

if(activityPasswordNeeded($report)){
	$session = nuSession($ses, false);
	if($session->foundOK == ''){
		print 'you have been logged out..';
		return;
	}
}


//$session = nuSession($ses, false);
//if($session->foundOK == ''){
//	print 'you have been logged out..';
//	return;
//}


$setup                                      = nuSetup();
//eval($setup->set_php_code);
$T                                          = nuRunQuery("SELECT * FROM zzsys_activity WHERE sat_all_code = '$report'");
$A                                          = db_fetch_object($T);
$dataTable                                  = TT();
$formValue                                  = getSelectionFormVariables($form_ses);

//----------allow for custom code----------------------------------------------
$globalValue                                = getglobalValue($_GET['ses']);







//----------create an array of hash variables that can be used in any "hashString" 
	$sesVariables                       = recordToHashArray('zzsys_session', 'zzsys_session_id', $ses);  //--session values (access level and user etc. )
	$sysVariables                       = sysVariablesToHashArray($form_ses);                            //--values in sysVariables from the calling lookup page
	$sesVariables['#dataTable#']        = $dataTable;
	$arrayOfHashVariables               = joinHashArrays($sysVariables, $sesVariables);                  //--join the arrays together
	$newFormArray                       = arrayToHashArray($formValue);
	$arrayOfHashVariables               = joinHashArrays($arrayOfHashVariables, $newFormArray);             //--join the arrays together
	eval(replaceHashVariablesWithValues($arrayOfHashVariables, $A->sat_export_data_code));











//eval($A->sat_export_data_code);

$ascii                                      = $A->sat_export_delimiter;

if ($A->sat_export_use_quotes == '0') {
	$dq	= '';
} else {
	$dq	= '"';

}

$t                                          = nuRunQuery("SELECT * FROM $dataTable");
$Field                                      = array();
$Field                                      = tableFieldNamesToArray($t);

if($A->sat_export_header != '0'){
	
	for ($i = 0 ; $i < count($Field) ; $i++) {
	    if($i > 0){print chr($ascii);}
	    print $Field[$i];
	}
	print "\r\n";

}

$t                                          = nuRunQuery("SELECT * FROM $dataTable");
while($r = db_fetch_array($t)){
    for($f = 0 ; $f < count($Field) ; $f++){
       if($f > 0){print chr($ascii);}
//       $theFieldValue                       = str_replace(chr(13).chr(10), "<br/>", $r[$Field[$f]]);
//       $theFieldValue                       = str_replace(chr(13), "<br/>", $theFieldValue);
//       $theFieldValue                       = str_replace(chr(10), "<br/>", $theFieldValue);
       $theFieldValue                       = str_replace('"', '', $r[$Field[$f]]);
       print $dq.$theFieldValue.$dq;
    }
    print "\r\n";
}
nuRunQuery("DROP TABLE $dataTable");
?>
