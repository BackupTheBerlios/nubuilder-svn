<?php
/*
** File:           calendar.php
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

session_start( );


$target                          = $_GET['target'];
$theFormat                       = $_GET['theFormat'];
$theDay                          = $_GET['theDay'];

$dir                             = $_GET['dir'];
$ses                             = $_GET['ses'];
$f                               = $_GET['f'];
$r                               = $_GET['r'];
$c                               = $_GET['c'];
$delete                          = $_GET['delete'];

include("../$dir/database.php");
include('common.php');

	print "<html>\n<body>\n<form id='theform' action='' method='post'>\n";
	$formatArray                    = textFormatsArray();
	$returnDateFormat               = convertToPhpDateFormat($formatArray[$theFormat]->format);
	$returnDateFormat6              = convertToPhpDateFormat($formatArray[6]->format);
	$dayFirst                       = substr($formatArray[$theFormat]->format, 0, 1)=='d';
	$dq                             = '"';
	$dateButton                     = array();
	for($i=0;$i<42;$i++){
		$dateButton[$i]->tag        ='';
		$dateButton[$i]->title      ='.';
	}
// set date to yyyy-mm-dd
	if($theDay == ''){
//		$theDay                     = nuDateFormat(Today(),$returnDateFormat);
		$theDay                     = nuDateFormat(Today(),$returnDateFormat6);
//tofile("theDay  $theDay");
	}
	$splitDate                      = array();
	$splitDate                      = explode('-',$theDay);
	if(monthNumber($splitDate[0]) != ''){$splitDate[0] = monthNumber($splitDate[0]);}
	if(monthNumber($splitDate[1]) != ''){$splitDate[1] = monthNumber($splitDate[1]);}

	if($dayFirst){
		$theDay                     = $splitDate[2].'-'.$splitDate[1].'-'.$splitDate[0];
	}else{                                      //--month first
		$theDay                     = $splitDate[2].'-'.$splitDate[0].'-'.$splitDate[1];
	}
	$thisMonth                      = nuDateFormat($theDay,'m');
	$thisYear                       = nuDateFormat($theDay,'Y');
	$firstOfTheMonth                = nuDateAddDays("$thisYear-$thisMonth-01",0);
	$nextDate                       = $firstOfTheMonth;
	for($i=nuDateFormat($firstOfTheMonth,'w');$i<42;$i++){


		if(nuDateFormat($firstOfTheMonth,'m') == nuDateFormat($nextDate,'m')){
			$dateButton[$i]->tag    = nuDateFormat(nuDateAddDays($nextDate,0),$returnDateFormat);
			$dateButton[$i]->title  = nuDateFormat($nextDate,'d');
		}
		$nextDate                   = nuDateAddDays($nextDate,1);
	}
	$style                          = "";
	print "<table align='center' style='font-family : tahoma;'>\n";

		print "        <tr><td align='center'>S</td><td align='center'>M</td><td align='center'>T</td><td align='center'>W</td><td align='center'>T</td><td align='center'>F</td><td align='center'>S</td></td>\n";
	print "    <tr>\n";
	$weekNumber                     = 0;
	for($i=0;$i<42;$i++){
		if(nuDateFormat(Today(),'dmY')==$dateButton[$i]->title.$thisMonth.$thisYear){
			$co                     = 'red';
		}else{
			$co                     = 'black';
		}
		print "        <td id='id_$i' style='width:20;color:$co;background-color:lightgrey' onclick='pick($dq".$dateButton[$i]->tag."$dq)' onmouseover='MIN(this)' onmouseout='MOUT(this)'>";
		print $dateButton[$i]->title;
		print " </td>\n";
		$weekNumber                 = $weekNumber + 1;
		if($weekNumber == 7 and $i < 40){
			print "    </tr>\n";
			print "    <tr>\n";
			$weekNumber             = 0;
		}
	}
	$theYear                        = nuDateFormat($theDay,'Y');
	$theMonth                       = nuDateFormat($theDay,'m');
	$lastYear                       = nuDateAddDays("$theYear-$theMonth-01",-360);
	$lastMonth                      = nuDateAddDays("$theYear-$theMonth-01",-2);

	$nextYear                       = nuDateAddDays("$theYear-$theMonth-01",370);
	$nextMonth                      = nuDateAddDays("$theYear-$theMonth-01",32);

	print "   </tr>\n";
	print "   <tr>\n";
	print "        <td colspan='2' id='m1' onclick='reload($dq".nuDateFormat($lastMonth,$returnDateFormat6)."$dq)' onmouseover='MIN(this)' onmouseout='MOUT(this)' align='center' style='background-color:lightgrey' >".nuDateFormat($lastMonth,'M')."</td>\n";
	print "        <td colspan='3' align='center'><b>".nuDateFormat($theDay,'M')."</b></td>\n";
	print "        <td colspan='2' id='m2' onclick='reload($dq".nuDateFormat($nextMonth,$returnDateFormat6)."$dq)' onmouseover='MIN(this)' onmouseout='MOUT(this)' align='center' style='background-color:lightgrey' >".nuDateFormat($nextMonth,'M')."</td>\n";
	print "   </tr>\n";
	print "   <tr>\n";
	print "        <td colspan='2' id='y1' onclick='reload($dq".nuDateFormat($lastYear,$returnDateFormat6)."$dq)' onmouseover='MIN(this)' onmouseout='MOUT(this)' align='center' style='background-color:lightgrey' >".nuDateFormat($lastYear,'Y')."</td>\n";
	print "        <td colspan='3' align='center'><b>".nuDateFormat($theDay,'Y')."</b></td>\n";
	print "        <td colspan='2' id='y2' onclick='reload($dq".nuDateFormat($nextYear,$returnDateFormat6)."$dq)' onmouseover='MIN(this)' onmouseout='MOUT(this)' align='center' style='background-color:lightgrey' >".nuDateFormat($nextYear,'Y')."</td>\n";
	print "   </tr>\n";
	print "<table>\n";

    $s                              =      "function MIN(pthis){//---mouse over menu\n";
    $s                              = $s . "      document.getElementById(pthis.id).style.backgroundColor='gray';\n";
    $s                              = $s . "}\n\n";
    $s                              = $s . "function reload(pvalue){//---reload calendar\n";
    $s                              = $s . "      document.forms[0].action='calendar.php?theFormat=$theFormat&dir=$dir&theDay='+pvalue+'&target=$target';\n";
    $s                              = $s . "      document.forms[0].submit();\n";
    $s                              = $s . "}\n\n";
    $s                              = $s . "function pick(pvalue){//---select date\n";
    $s                              = $s . "      if(pvalue==''){return;}\n";
    if($target==''){
	    $s                          = $s . "      alert('no target was specified in URL');\n";
	}else{
//----next bit (setAttribute) worked in ie but not ff
//	    $s   = $s . "      window.opener.document.getElementById('$target').setAttribute('value',pvalue);\n";
	    $s                          = $s . "      window.opener.parent.frames[0].document.theform.$target.value = pvalue;\n";
	    $s                          = $s . "      window.opener.parent.frames[0].document.theform.$target.onchange();\n";

//	    $s   = $s . "      opener.document.forms[0]['$target'].value = pvalue;\n";
	}
    $s                              = $s . "      self.close();\n";
    $s                              = $s . "}\n\n";
    $s                              = $s . "function MOUT(pthis){//---mouse out menu\n";
    $s                              = $s . "   document.getElementById(pthis.id).style.backgroundColor='lightgrey';\n";
    $s                              = $s . "}\n";


	print "\n\n<script>\n";
	print $s;
	print "\n";
    print setFormatArray();
	print "\n";
	print "</script>\n\n\n";

	print "</form></body></html>\n";


?>
