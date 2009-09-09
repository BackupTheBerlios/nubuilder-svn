<?php
/*
** File:           formimage.php
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

$dir                             = $_GET['dir'];
$imageID                         = $_GET['iid'];

include("../$dir/database.php");
include('common.php');

$s = "SELECT * FROM zzsys_image WHERE zzsys_image_id = '$imageID'";
$t = nuRunQuery($s);
$r = db_fetch_object($t);

Header ("Content-type: image/png"); 
print $r->sim_blob; 

?>
