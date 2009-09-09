<?php
/*
** File:           nubuilder.php
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
$ses                             = $_GET['ses'];
$f                               = $_GET['f'];
$r                               = $_GET['r'];
$c                               = $_GET['c'];
$d                               = $_GET['d'];
$debug                           = $_GET['debug'];
$fb                              = '1';

include("../$dir/database.php");
include('common.php');

$form = formFields($f);
print "<html>\n";
print "<head><title>$form->sfo_title</title></head>\n";
print "<frameset frameborder='no' rows='100%,0%,0%,0%,0%,0%,0%,0%,0%,0%,0%,0%'>\n";
print "<body>\n";
if($f == 'index'){
	print "<frame name='index' scrolling='yes' src='form.php?x=1&f=$f&r=$r&dir=$dir&ses=$ses&c=$c&d=$d&debug=$debug'>\n";
}else{
	print "<frame name='main' scrolling='yes' src='form.php?x=1&f=$f&r=$r&dir=$dir&ses=$ses&c=$c&d=$d&debug=$debug'>\n";
}

for ($i = 0 ; $i < 11 ; $i++){
	print "<frame name='hide$i'  frameborder='$fb' src='blank.html'>\n";
}

print "</body>\n";
print "</frameset>\n";
print "</html>\n";
?>
