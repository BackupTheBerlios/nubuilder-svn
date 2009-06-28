<?php
/*
** File:           FMLib.php
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

function getFMSettings() {
	
	$RS = nuRunQuery("SELECT * FROM financial_module_setting");
	return mysql_fetch_object($RS);
}

function getLedgerCode($ledgerID) {
	
	$RS 		= nuRunQuery("SELECT led_code FROM ledger WHERE ledger_id = '$ledgerID'");
	$ledgerObj 	= mysql_fetch_object($RS);
	return $ledgerObj->led_code;  
		
}

?>
