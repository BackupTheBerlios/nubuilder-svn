<?php
/*
** File:           graph_report.php
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

	//-- $activityID is passed by the URL
	//-- $graph_name is also
	//-- $dir is also
	$dir = $_GET['dir'];
	include("../$dir/database.php");
	include('common.php');
	

	$graphTable = TT();
	$s  = "SELECT * FROM zzsys_activity_graph WHERE sag_zzsys_activity_id = '$activityID' AND sag_graph_name = '$graph_name'";
	$tA = nuRunQuery($s);
	$graphObject = db_fetch_object($tA);

	$s  = "SELECT * FROM zzsys_graph WHERE zzsys_graph_id = '$graphObject->sag_graph_zzsys_graph_id'";
	$tB = nuRunQuery($s);
	$graphType = db_fetch_object($tB);

//----------create an array of hash variables that can be used in any "hashString" 
	$arrayOfHashVariables                   = recordToHashArray('zzsys_session', 'zzsys_session_id', $ses);  //--session values (access level and user etc. )
	$arrayOfHashVariables['#id#']           = $theRecordID;  //--this record's id
	$arrayOfHashVariables['#clone#']        = $clone;        //--if it is a clone
	$arrayOfHashVariables['#graphTable#']   = $graphTable;   //--temp table name

	foreach ($_GET as $key => $value) {
		$arrayOfHashVariables["#$key#"]     = $value;
	}
	
	//-- create a temp table called $graphTable
	eval(replaceHashVariablesWithValues($arrayOfHashVariables, $graphObject->sag_graph_code)); //--replace hash variables then run code

	//-- turn $graphTable into this type of graph
	eval(replaceHashVariablesWithValues($arrayOfHashVariables, $graphType->sgr_graph_code)); //--replace hash variables then run code

	//-----------custom code end---------------------------------------------------

	//-- create a temp table called $graphTable
//	eval($graphObject->sag_graph_code);

	//-- turn $graphTable into this type of graph
//	eval($graphType->sgr_graph_code);
	nuRunQuery("DROP TABLE $graphTable");
      
?>
