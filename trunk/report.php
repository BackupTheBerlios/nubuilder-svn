<?php
/*
** File:           report.php
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
include("editlibrary.php");

$setup=setup();


$uniq=uniqid(1);
//------------ validate user
	$ck=checkuser($access);
	if ($ck==''){
		return;
	}

$GLOBALS['ArrayName'][]='';
jinclude("general");


$t = RunQuery("SELECT * FROM sysscreen WHERE ssQuery = 'SCANYREPORT'");
$r = mysql_fetch_object($t);
$PHPjavascript = $r->ssJavaScript;

if($PHPjavascript!=''){
	print "<script type='text/javascript'  language='javascript'>\n\n\n";
	print $PHPjavascript;
	print "\n\n\n</script>\n\n";
}


//-----get information for this report
$table = RunQuery("SELECT * FROM sysreport WHERE sysreportID = '$id'");
$report = mysql_fetch_object($table);
$id=-1;//---to show default values
$reportType = $report->srReportType;
$srPrintButton = $report->srPrintButton;
$srEmailButton = $report->srEmailButton;
$srPrintPDFButton = $report->srPrintPDFButton;
$srEMailPDFButton = $report->srEMailPDFButton;

print "<script type='text/javascript'  language='javascript'>\n\n".ln(1);


print "self.setInterval('checknuC()', 1000); \n\n";

print "function checknuC(){ \n";
print "   if(nuReadCookie('nuC') == null){ \n";
print "      pop = window.open('', '_parent');\n";
print "      pop.close();\n";
print "   }\n";
print "}\n";


$s="SELECT * FROM sysedit WHERE Page='$report->srParameters' ORDER BY TabNumber, ColumnNumber, OrderNumber";
$table = RunQuery("SELECT * FROM sysreport WHERE sysreportID = '0'");
$syseditRecords = RunQuery($s);
$fields = mysql_num_fields($table);

$lasttab=0;
$lastcol=0;
$newtab=true;
$newcol=true;
$tablevalues = mysql_fetch_array($table);
$variablePrefix = $GLOBALS['GlobeSessionID'].'_'.$uniq.'_'; //---theprefix used in sysvariable to hold values
setvar($uniq.'_ReportTitle',$report->srDescription);
setvar($uniq.'_ReportCode',$report->srCode);
setvar($uniq.'_srPageLength',$report->srPageLength);
//function to open the new window
print "function emailit(){\n\n";
print "   if(noblanks()!=''){alert(noblanks());return;}\n";
print "   document.forms[0]['EMAIL_ADDRESS'].value=prompt('Email to..','');\n";
print "   if(document.forms[0]['EMAIL_ADDRESS'].value!=''){;\n";
print "		 var emailSubject=prompt('Subject line to include in email..','Emailed report : ' + document.forms[0]['EMAIL_SUBJECT'].value);\n";
print "      document.forms[0]['EMAIL_MESSAGE'].value=prompt('Short message to include in email..','');\n";
$emailaddress='"EMAIL_ADDRESS"';
$emailsubject='"EMAIL_SUBJECT"';
$emailmessage='"EMAIL_MESSAGE"';
print "      var url='$setup->sePHPURL/reportemail.php?sd=$setup->seStartingDirectory&report=$report->sysreportID&p=$variablePrefix&e='+document.forms[0][$emailaddress].value+'&s='+emailSubject+'&m='+document.forms[0][$emailmessage].value;\n";
print "      window.open (url,'_blank','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes');\n";
print "   }else{alert('No Email Address');};\n";
print "}\n\n";
//function to open the new window
print "function pdfemailit(){\n\n";
print "   if(noblanks()!=''){alert(noblanks());return;}\n";
print "   document.forms[0]['EMAIL_ADDRESS'].value=prompt('Email to..','');\n";
print "   if(document.forms[0]['EMAIL_ADDRESS'].value!=''){;\n";
print "		 var emailSubject=prompt('Subject line to include in email..','Emailed report : ' + document.forms[0]['EMAIL_SUBJECT'].value);\n";
print "      document.forms[0]['EMAIL_MESSAGE'].value=prompt('Short message to include in email..','');\n";
$emailaddress='"EMAIL_ADDRESS"';
$emailsubject='"EMAIL_SUBJECT"';
$emailmessage='"EMAIL_MESSAGE"';
print "      var url='$setup->sePHPURL/reportpdfemail.php?sd=$setup->seStartingDirectory&emailid=$uniq&report=$report->sysreportID&p=$variablePrefix&e='+document.forms[0][$emailaddress].value+'&s='+emailSubject+'&m='+document.forms[0][$emailmessage].value;\n";
print "      window.open (url,'_blank','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes');\n";
print "   }else{alert('No Email Address');};\n";
print "}\n\n";
print "function printit(){\n\n";
print "   if(window.nuBeforeSave){\n";
print "      if(!nuBeforeSave()){\n";
print "         return;\n";
print "      }\n";
print "   }\n";
print "   if(noblanks()!=''){alert(noblanks());return;}\n";
print "   var url='$setup->sePHPURL/reportrun.php?sd=$setup->seStartingDirectory&report=$report->sysreportID&p=$variablePrefix';\n";
print "   window.open (url,'_blank','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes');\n";
print "}\n\n";
print "function runprocedure(){\n\n";
print "   if(window.nuBeforeSave){\n";
print "      if(!nuBeforeSave()){\n";
print "         return;\n";
print "      }\n";
print "   }\n";
print "   if(noblanks()!=''){alert(noblanks());return;}\n";
print "   var url='$setup->sePHPURL/runprocedure.php?&db=$setup->seStartingDirectory&procfile=$report->srReportData&p=$variablePrefix';\n"; //first url with the datafile.php
print "   window.open (url,'_blank','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes');\n";
print "}\n\n";
print "function exportit(){\n\n";
print "   if(window.nuBeforeSave){\n";
print "      if(!nuBeforeSave()){\n";
print "         return;\n";
print "      }\n";
print "   }\n";
print "   if(noblanks()!=''){alert(noblanks());return;}\n";
print "   var url='export.php?&data=$report->srReportData&p=$variablePrefix&d=$report->srReportType';\n"; //first url with the datafile.php
print "   window.open (url,'_blank','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes');\n";
print "}\n\n";
print "function pdfit(){\n\n";
print "   if(window.nuBeforeSave){\n";
print "      if(!nuBeforeSave()){\n";
print "         return;\n";
print "      }\n";
print "   }\n";
print "   if(noblanks()!=''){alert(noblanks());return;}\n";
print "   var url='$setup->sePHPURL/reportrunPDF.php?sd=$setup->seStartingDirectory&report=$report->sysreportID&p=$variablePrefix';\n";
print "   window.open (url,'_blank','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes');\n";
print "}\n\n";

print "function getblank(){\n\n";
print "   var answer = noblanks();\n";
print "   var url='export.php?&data=$report->srReportData&p=$variablePrefix';\n"; //first url with the datafile.php
print "   window.open (url,'_blank','toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes');\n";
print "}\n\n";

print "</script>\n\n";


reportnoblanks("SELECT * FROM sysedit WHERE Page = '$report->srParameters'",$id);


print "<html><head></head>\n";

while ($edit = mysql_fetch_object($syseditRecords)){
	if($lasttab!=$edit->TabNumber){                     // if last tab is finished...
		if($lasttab!=0){                               // if this is not the first tab
			print "</TD></TR></TABLE></div>";             // close last tab
			print "<TR><td class='edittable'>".stringofbreaks(40)."</TD></TR></TABLE>".ln(5);
			addbreaks(36);
		}else{
			print "<script type='text/javascript'  language='javascript'>\n\n";
			print "function load(){\n";
			print "   resize();\n";
			print "   runpopulate();\n";
			print "}\n\n";

            print "function USA(){\n";
            if($setup->seUSADate=='1'){
                print "   return true;\n";
            }else{
                print "   return false;\n";
            }
            print "}\n\n";

			print "</script>\n\n";
			print "<link rel='stylesheet' href='".$GLOBALS['StartingDirectory']."/style.css'>\n";
			print "<body onload=load()>".ln(1);
			print "<form name='selecta' method='POST'>".ln(10);
			print "<input name='HasBeenEdited' type='hidden' value='0'>\n";
			print "<input name='TheFormQuery' type='hidden' value='$uniq'>\n";
			print "<input name='TheFormID' type='hidden' value='$uniq'>\n";
			print "<input name='user' type=hidden value='$user'>\n";
			print "<input name='ReportTitle' type=hidden value='$report->srDescription'>\n";
			print "<input name='ReportCode' type=hidden value='$report->srCode'>\n";
			print "<input name='ReportQuery' type=hidden value='$report->srParameters'>\n";
            print "<input name='EMAIL_ADDRESS' type=hidden value=''>\n";
            print "<input name='EMAIL_SUBJECT' type=hidden value='Report:$report->srDescription'>\n";
            print "<input name='EMAIL_MESSAGE' type=hidden value=''>\n";
            print "<input name='UseFrame' type='hidden' value='1'>\n\n";
		}
		$tabtitle=str_replace(" ", "_",$edit->TabTitle);
		print "<div id='d_$q$tabtitle' align=center style='overflow:hidden;position:absolute;visibility:visible;top:0;left:10;width:976;height:630'>";


//---------------------------------edit buttons
		print ln(3);
		print "<TABLE width='100%' style='border-style:Solid;border-color:white;' border=0>";
		
		print "<TR align=left>";
		
		print "<td align='left'><img TABINDEX=-1 style='WIDTH: 65px; HEIGHT: 35px' src='".$GLOBALS['StartingDirectory']."/editlogo.bmp' border='0' onclick='backToIndex()'/></td>";
		print "<td align='center'><B>$report->srDescription</B></TD>";
		print "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></TR>";
		
		print "<tr><td align=center colspan=3>";
		if ($reportType=='E' or $reportType=='C'){
    		print "<input class=button type=button value='Export File' name=save onclick='exportit();window.close()'>".ln(1);
        }
		if ($reportType=='R'){
			if($srPrintButton =='1'){print "<input class=button type=button value='Create Report' name=save onclick='printit();window.close()'>".ln(1);}
			if($srEmailButton =='1'){print "<input class=button type=button value='Email Report' name=save onclick='emailit();window.close()'>".ln(1);}
			if($srPrintPDFButton =='1'){print "<input class=button type=button value='PDF Report' name=save onclick='pdfit();window.close()'>".ln(1);}
			if($srEMailPDFButton =='1'){print "<input class=button type=button value='Email PDF' name=save onclick='pdfemailit();window.close()'>".ln(1);}
        }
		if ($reportType=='P'){
            print "<input class=button type=button value='Run Procedure' name=save onclick='runprocedure();window.close()'>".ln(1);
        }

		print "</TD></tr>";
		print "</TABLE>".ln(1);
//---------------------------------edit buttons


		print "<TABLE align=CENTER width='100%'><br></TABLE>";
		createtabs($tabarray,$tabtitle,$width,$divarray);
		print "<TABLE align=CENTER WIDTH=100% class='edittable'>";  // start a new tab
		if ($q!='index'){ //main startup screen
			print "<tr><td class='edittable'><br></td></tr>";
		}
		$lastcol=0;                                    //set column number back to 0
		$lasttab=$edit->TabNumber;
	}
	if($lastcol!=$edit->ColumnNumber){                 // if last column is finished...
		if($lastcol!=0){                              // if this is not the first column
			print "</TABLE></TD>".ln(1);                  // close last column
		}
		$lastcol=$edit->ColumnNumber;
		print "<td class='edittable'><TABLE align=CENTER>".ln(1);           // start a new column
	}
		print "<TR><td class='edittable' align=right>".iif($edit->FieldType=='button' or $edit->FieldType=='words','',$edit->Title)."</TD><td class='edittable'>"; //left hand side of table (eg.title))
		$gstarray = makeddarray($edit->FieldType,$edit->TableArray);       //--- setup array for dropdown
		BuildDropDownArray($edit,'','',-1);  //---builde javascript arrays for dropdowns
		inputtype($q,$edit,$tablevalues,$id,'','',$gstarray,'',$uniq);                 //right hand side of table (eg.input)

		print "</TD></TR>";
//	}
}


print "</TABLE><TR><td class='edittable'>".stringofbreaks(40)."</TD></TR>".ln(1);
print "</TABLE></div>".ln(1);
addrows(2);  //make a gap between fields and the end of the table
addbreaks(30);



//--- write out javascript arrays for populating dropdowns
print "<script type='text/javascript'  language='javascript'>\n\n";
	for($i=0;$i<count($GLOBALS['theArray']);$i++){
		print $GLOBALS['theArray'][$i]."\n";
	}
//--- fill dropdowns with javascript arrays
print "\n\nfunction runpopulate(){\n";
	for($i=0;$i<count($GLOBALS['thePopulator']);$i++){
		print $GLOBALS['thePopulator'][$i]."\n";
	}
print "}\n\n";
print "</script>\n".ln(1);

?>

<div id='Footer' align=center style='overflow:hidden;position:absolute;visibility:visible;top:620;height:20;left:10;width:990'>
<table width='102%'><tr width='100%'><td  width='100%' class='stab' ><a class="blueLink" href="http://www.nubuilder.com" target="_blank">Powered by nuBuilder</a></td></tr>
	<tr>
		<td style='background-color:blue' align='left'><img src='css/bluebl.jpg' border='0'/></td>
		<td style='background-color:green' align='right'><img src='css/bluebr.jpg' border='0'/></td>
	</tr>
</table>
</div>

<div id='FooterL' align=center style='overflow:hidden;position:absolute;visibility:visible;top:110;height:530;left:1;width:20'>
<table width='5%'><tr width='100%'><td  width='100%' class='stab' >
<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>
<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>
<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>
<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>
</td></tr></table>
</div>
<div id='FooterR' align=center style='overflow:hidden;position:absolute;visibility:visible;top:110;height:530;left:975;width:20'>
<table width='5%'><tr width='100%'><td  width='100%' class='stab' >
<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>
<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>
<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>
<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>
</td></tr></table>
</div>
</form></body></html>
