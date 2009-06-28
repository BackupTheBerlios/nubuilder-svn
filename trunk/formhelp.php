<?php
/*
** File:           formhelp.php
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

include('../' . $_GET['dir'] . '/database.php');
include('common.php');

$f                                  = $_GET['f'];

tofile("SELECT * FROM zzsys_form WHERE zzsys_form_id = '$f'");
$t = nuRunQuery("SELECT * FROM zzsys_form WHERE zzsys_form_id = '$f'");
$r = db_fetch_object($t);

print "<html><head><title>Help for $r->sfo_title Screen</title></head>";
print "<body bgcolor=#ffffff text=#000000 link=#0000cc vlink=#551a8b alink=#ff0000>";

print $r->sfo_help;

print "</body></html>";
?>
