<?php
/*
** File:           runprocedure.php
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

$formValue = getSelectionFormVariables($form_ses);
$setup                                      = nuSetup();

$T                                          = nuRunQuery("SELECT * FROM zzsys_activity WHERE sat_all_code = '$report'");
$A                                          = db_fetch_object($T);

//----------allow for custom code----------------------------------------------
$globalValue                                = getglobalValue($ses);




//----------create an array of hash variables that can be used in any "hashString" 
	$sesVariables                       = recordToHashArray('zzsys_session', 'zzsys_session_id', $ses);  //--session values (access level and user etc. )
	$sysVariables                       = sysVariablesToHashArray($form_ses);                            //--values in sysVariables from the calling lookup page
	$arrayOfHashVariables               = joinHashArrays($sysVariables, $sesVariables);                  //--join the arrays together
	$newFormArray                       = arrayToHashArray($formValue);
	$arrayOfHashVariables               = joinHashArrays($arrayOfHashVariables, $newFormArray);             //--join the arrays together
	eval(replaceHashVariablesWithValues($arrayOfHashVariables, $A->sat_procedure_code));

?>
