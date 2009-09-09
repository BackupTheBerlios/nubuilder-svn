<?php
/*
** File:           formlookupsmall.php
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

$dir                   = $_GET['dir'];     //-- directory of database.php
$ses                   = $_GET['ses'];     //-- zzsys_session_id in zzsys_session
$f                     = $_GET['f'];       //-- zzsys_form_id in zzsys_form
$rID                   = $_GET['r'];       //-- lookup's selected record id
$fr                    = $_GET['fr'];      //-- form's record id
$ob                    = $_GET['ob'];      //-- zzsys_object_id in zzsys_object of selected lookup
$value                 = $_GET['value'];   //-- selected value to go back into lookup field
include("../$dir/database.php");
include('common.php');
$TT                    = TT();
$object                = objectFields($ob);
$browse                = formFields($object->sob_lookup_zzsysform_id);

$SQL                   = new sqlString(replaceVariablesInString($TT,$browse->sfo_sql, ''));
if($SQL->where == ''){
	$SQL->setWhere(" WHERE $object->sob_lookup_id_field = '$rID'");
}else{
	$SQL->setWhere(" $SQL->where AND ($object->sob_lookup_id_field = '$rID')");
}
$SQL->removeAllFields();
$SQL->addField($object->sob_lookup_id_field);
$fieldNames[]      = $object->sob_all_name;
$SQL->addField($object->sob_lookup_code_field);
$fieldNames[]      = 'code'.$object->sob_all_name;
$SQL->addField($object->sob_lookup_description_field);
$fieldNames[]      = 'description'.$object->sob_all_name;
	
$t                 = nuRunQuery("SELECT * FROM zzsys_lookup WHERE slo_zzsys_object_id = '$object->zzsys_object_id'");
while($r           = db_fetch_object($t)){
	$SQL->addField($r->zzsys_slo_table_field_name);
	$fieldNames[]  = $r->zzsys_slo_page_field_name;
}

$t                 = nuRunQuery($SQL->SQL);
$fieldArray        = tableFieldNamesToArray($t);
$T                 = nuRunQuery($SQL->SQL);
$R                 = db_fetch_row($T);
for($i = 0 ; $i < count($fieldArray) ; $i++){
tofile("UPDATE zzsys_small_form_value SET sfv_value = '" . $R[$i]. "'  WHERE sfv_form_record = '$f$fr$ses' AND sfv_name = '" . $fieldNames[$i] . "'");
	nuRunQuery("UPDATE zzsys_small_form_value SET sfv_value = '" . $R[$i]. "'  WHERE sfv_form_record = '$f$fr$ses' AND sfv_name = '" . $fieldNames[$i] . "'");
}
$url = "formsmall.php?x=11&f=$f&fr=$fr&r=$rID&dir=$dir&ses=$ses";

print "<html>\n<body onload=\"document.forms.lookup.submit()\">\n";
print "<form name='lookup' method='POST' action='$url'>\n</form>\n";
print "</body>\n</html>\n";
return;

?>


