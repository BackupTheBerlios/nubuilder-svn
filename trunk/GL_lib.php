<?php
/*
** File:           GL_lib.php
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


function insertledgerlogheader($lldDate,$lldDescription){
	  
	  $lldBatchID = nextNumber('sysnumber');
	  $ledgerlogID = uniqid('1');
      $sql  = "INSERT INTO ledgerlog(ledgerlogID, lldBatchID, lldDate,lldDescription,lldPosted) VALUES ";
      $sql .= "('$ledgerlogID','$lldBatchID','$lldDate','$lldDescription',1)";
      RunQuery($sql);
      return $ledgerlogID;
}

function insertledgerlogitem($lliLedgerlogID,$lliLedgerID,$lliAmount){
	
	
		  $ledgerlogitemID = uniqid('1');
	      $sql  = "INSERT INTO ledgerlogitem(ledgerlogitemID,lliLedgerID, lliLedgerlogID,lliAmount) VALUES ";
	      $sql .= "('$ledgerlogitemID','$lliLedgerID','$lliLedgerlogID',0$lliAmount)";
	      RunQuery($sql);
	
}
	
?>

