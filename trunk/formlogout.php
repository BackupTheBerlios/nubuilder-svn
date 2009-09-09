<?php
/*
** File:           formlogout.php
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
include('../' . $_GET['dir'] . '/database.php');
include('common.php');

$id                                        = uniqid('1');
$now                                       = date('Y-n-d H:i:s');
$_SESSION['customDirectory']               = '';
$setup                                     = nuSetup();
// 2009/06/02 - Michael
$session                                   = nuSession($_GET['f']);

nuRunQuery("DELETE FROM zzsys_variable WHERE sva_session_id = '" . $_GET['f'] . "'");
nuRunQuery("DELETE FROM zzsys_session WHERE zzsys_session_id = '" . $_GET['f'] . "'");
// 2009/06/02 - Michael - added IP logging.
nuRunQuery("INSERT INTO zzsys_user_log (zzsys_user_log_id, sul_zzsys_user_id, sul_ip, sul_end) VALUES ('$id', '".$session->sss_zzsys_user_id."', '{$_SERVER['REMOTE_ADDR']}', '$now')");
//nuRunQuery("INSERT INTO zzsys_user_log (zzsys_user_log_id, sul_zzsys_user_id, sul_end) VALUES ('$id', '".$_SESSION['zzsys_user_id']."', '$now')");


//---direct back to..
if($setup->set_logout_page==''){
//	$url = "http://www.nubuilder.com";
	$url = "/".$_GET['dir'];
}else{
//	$url = $setup->set_logout_page;
	$url = $setup->set_logout_page;
}


print "<html>\n";
print "<body onload='closeall()'>\n";
print "<form action='$url' method='POST'>\n";
print "</form>\n";
print "</body>\n";
print "</html>\n";
print "<script>\n";
print "function closeall(){\n";
print "\n\ndocument.forms[0].submit();\n\n";
print "}\n";
print "</script>\n";

?>

