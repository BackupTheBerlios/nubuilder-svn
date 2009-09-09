<?php
/*
** File:           graph_object.php
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

	//-- $graphID is passed by the URL
	//-- $dir is also
	
	$formID      = $_GET['f'];
	$recordID    = $_GET['id'];
	$ses         = $_GET['ses'];
	$dir         = $_GET['dir'];
	$graphID     = $_GET['graphID'];

	include("../$dir/database.php");
	include('common.php');
	$graphTable = TT();
	$tA = nuRunQuery("SELECT * FROM zzsys_object WHERE zzsys_object_id = '$graphID'");
	$graphObject = db_fetch_object($tA);

	$tB = nuRunQuery("SELECT * FROM zzsys_graph WHERE zzsys_graph_id = '$graphObject->sob_graph_zzsys_graph_id'");
	$graphType = db_fetch_object($tB);


	$form = formFields($formID);
	
//----------create an array of hash variables that can be used in any "hashString" 
	$T                                      = nuRunQuery("SELECT ".$form->sfo_table.".* FROM ".$form->sfo_table." WHERE ".$form->sfo_primary_key." = '$recordID'");
	$recordValues                           = db_fetch_array($T);
	$arrayOfHashVariables                   = recordToHashArray($form->sfo_table, $form->sfo_primary_key, $recordID);//--values of this record
	$arrayOfHashVariables['#id#']           = $theRecordID;  //--this record's id
	$arrayOfHashVariables['#recordID#']     = $theRecordID;  //--this record's id
	$arrayOfHashVariables['#clone#']        = $clone;        //--if it is a clone
	$arrayOfHashVariables['#graphTable#']   = $graphTable;   //--temp table name
	$arrayOfHashVariables['#dataTable#']    = $graphTable;   //--temp table name
	$sVariables                             = recordToHashArray('zzsys_session', 'zzsys_session_id', $ses);  //--session values (access level and user etc. )
	$arrayOfHashVariables                   = joinHashArrays($arrayOfHashVariables, $sVariables);       //--join the arrays together

	//-- create a temp table called $graphTable
	eval(replaceHashVariablesWithValues($arrayOfHashVariables, $graphObject->sob_graph_code)); //--replace hash variables then run code

	//-- turn $graphTable into this type of graph
	eval(replaceHashVariablesWithValues($arrayOfHashVariables, $graphType->sgr_graph_code)); //--replace hash variables then run code

	nuRunQuery("DROP TABLE $graphTable");
      
?>
