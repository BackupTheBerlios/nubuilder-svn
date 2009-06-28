<?php
/*
** File:           formgetlookup.php
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

include($GLOBALS['StartingDirectory']."/database.php");
include("general.php");
$setup=setup();
jinclude("general");

print "<script type='text/javascript' language='JavaScript'>\n\n";
print "function fillfields(){\n";
print "   for (var i=0; i < document.forms[0].elements.length; i++){\n";
print "      parent.frames[0].document.forms[0][document.forms[0].elements[i].name].value = document.forms[0].elements[i].value;\n";
print "      reformat(parent.frames[0].document.forms[0][document.forms[0].elements[i].name])\n";
print "   }\n";
$ns='0123456789';
if('SF'==substr($caller,0,2)){
	if(strpos($ns, substr($caller,2,1))!==false){
		if(strpos($ns, substr($caller,3,1))!==false){
			if(strpos($ns, substr($caller,4,1))!==false){
				if(strpos($ns, substr($caller,5,1))!==false){
					if(strpos($ns, substr($caller,6,1))!==false){
						$therow = substr($caller,0,8);
						print "   parent.frames[0].document.forms[0]['$therow'].checked=false;\n";
					}

				}
			}
		}
	}
}


//------run javascript
$js=RunJavascriptForQuery($q);
print "   $js\n";
print "\n";
if($caller!=''){
	print "   var TheURL = 'savevariable.php?xx=1';\n";
	print "   TheURL=TheURL+'&TheFieldName='+'$caller';\n";
	print "   TheURL=TheURL+'&TheFieldValue='+document.forms[0]['$caller'].value;\n";
	print "   TheURL=TheURL+'&TheFormQuery='+parent.frames[0].document.forms[0]['TheFormQuery'].value\n";
	print "   parent.frames['hideupdate'].document.location = TheURL\n";
	print "\n";
}
print "}\n";
print "\n";
print "</script>".ln(1);

print "<html><body onLoad='fillfields()'><form>".ln(1);

$tempListName=BuildQueryTable($q);
$sql=fixsql($q,$lookin,$tempListName);

$sql = str_replace('#sysuserID#',$GLOBALS['sysuserID'],$sql);
$sql = str_replace('#AccessLevel#',$GLOBALS['AccessLevel'],$sql);
$sql = str_replace('#sysuserloginID#',$GLOBALS['sysuserloginID'],$sql);
$vars=GetHashVariables($sql);

for($for=1;$for<count($vars);$for++){
	$PandF=substr($vars[$for],1,strlen($vars[$for])-2);//--Page and Field names minus the hashes
	$vt=RunQuery('SELECT * FROM sysvariable WHERE svaName = "'.$GLOBALS['GlobeSessionID'].'_'.$PandF.'"');
	$vr=mysql_fetch_object($vt);
	if($vr->sysvariableID!=''){//--a record was found
		$sql = str_replace($vars[$for],$vr->svaValue,$sql);
	}
}

$table = RunQuery($sql."'".$value."'");
$row = mysql_fetch_row($table);

$GLOBALS[TableID($q)]=$row[0];
$dq='"';
print "<INPUT name='$caller' value=$dq$row[0]$dq >\n";
print "<INPUT name='code$caller' value=$dq$row[1]$dq >\n";
print "<INPUT name='name$caller' value=$dq$row[2]$dq >\n";

setvar($TheFormQuery.'_'."$caller",$row[0]);
setvar($TheFormQuery.'_'."code$caller",$row[1]);
setvar($TheFormQuery.'_'."name$caller",$row[2]);

$t=RunQuery("SELECT sysqueryID FROM sysquery WHERE qPage = '$q'");
$QueryID=mysql_fetch_row($t);
$t=RunQuery("SELECT * FROM syslookup WHERE slosysqueryID = '$QueryID[0]'");
$rno=3;
while($r=mysql_fetch_object($t)){
	print "<TEXTAREA name='$therow$r->sloHTMLField'>$row[$rno]</TEXTAREA>\n";
	setvar($TheFormQuery.'_'."$therow$r->sloHTMLField",$row[$rno]);
	$rno=$rno+1;
}
print "</form></body></html>";

if($tempListName!=''){
    RunQuery("DROP TABLE $tempListName");
}


?>
