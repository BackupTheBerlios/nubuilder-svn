<?php
/*
** File:           formsetvalues.php
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
include('../' . $_GET['dir'] . '/database.php');
include('common.php');

$type                               = $_GET['type'];
$runphp                             = $_GET['runphp'];
$name                               = $_GET['name'];
$id                                 = $_GET['id'];
$ses                                = $_GET['ses'];

print $type . ' second time through:' . $runphp . ' ' . $name;
	if($runphp=='ok'){ // run second time through
		if($type=='list'){
			setnuList($id, nuDateAddDays(Today(),2), $name, $_POST['TheList']);
		}elseif($type=='lookup'){
			$name = substr($name,4);
			setnuVariable($id, nuDateAddDays(Today(),2), $name, $_POST['TheID']);
			setnuVariable($id, nuDateAddDays(Today(),2), 'code'.$name, $_POST['TheCode']);
			setnuVariable($id, nuDateAddDays(Today(),2), 'description'.$name, $_POST['TheName']);
		}else{
			setnuVariable($id, nuDateAddDays(Today(),2), $name, $_POST['TheField']);
		}
	}else{
		$s = "<html>\n<head>\n";
		$s = $s . "<meta http-equiv='Content-Type' content='text/html;'/>\n";
		$s = $s . "<script type='text/javascript'>\n";
		$s = $s . "/* <![CDATA[ */\n";

		if($type=='list'){
			$s = $s . "function getData(){\n";
			$s = $s . "   var myEle         = new Object();\n";
			$s = $s . "   var lb            = new Object(window.parent.frames[0].document.getElementById('theform').$name);\n";
			$s = $s . "   for (i=0 ; i < lb.length ; i++){\n";
			$s = $s . "      if(lb[i].selected){\n";
			$s = $s . "         myEle             = document.createElement('option');\n";
			$s = $s . "         myEle.value       = lb[i].value;\n";
			$s = $s . "         myEle.selected    = true;\n";
			$s = $s . "         var Lst           = document.getElementById('theform').TheList;\n";
			$s = $s . "         try{\n";
			$s = $s . "            Lst.add(myEle);\n";
			$s = $s . "         }\n";
			$s = $s . "         catch(ex){\n";
			$s = $s . "            Lst.add(myEle, null);\n";
			$s = $s . "         }\n";
			$s = $s . "      }\n";
			$s = $s . "   }\n";
			$s = $s . "   document.theform.submit();\n";
			$s = $s . "}\n";
		}elseif($type=='lookup'){
			$s = $s . "function getData(){\n";
			$s = $s . "   document.getElementById('TheID').value = window.parent.frames[0].document.getElementById('theform').$name.value;\n";
			$s = $s . "   document.getElementById('TheCode').value = window.parent.frames[0].document.getElementById('theform').code$name.value;\n";
			$s = $s . "   document.getElementById('TheName').value = window.parent.frames[0].document.getElementById('theform').description$name.value;\n";
			$s = $s . "   document.theform.submit();\n";
			$s = $s . "}\n";
		}else{
			$s = $s . "function getData(){\n";
			$s = $s . "   document.getElementById('TheField').value = window.parent.frames[0].document.getElementById('theform').$name.value;\n";
			$s = $s . "   document.theform.submit();\n";
			$s = $s . "}\n";
		}

		$s = $s . "/* ]]> */\n";
		$s = $s . "</script>\n</head>\n<body onload='getData()'>\n";
		$s = $s . "<form name='theform' id='theform' action='formsetvalues.php?runphp=ok&type=$type&id=$id&dir=" . $_GET['dir'] . "&ses=" . $_GET['ses'] . "&name=$name' method='post'>\n";
		$s = $s . "   <input value='' name='TheField' id='TheField'/>\n";
		$s = $s . "   <input value='' name='TheID' id='TheID'/>\n";
		$s = $s . "   <input value='' name='TheCode' id='TheCode'/>\n";
		$s = $s . "   <input value='' name='TheName' id='TheName'/>\n";
		$s = $s . "   <select  multiple='multiple' name='TheList[]' id='TheList'>\n";
		$s = $s . "	  </select>\n";
		$s = $s . "</form>\n</body>\n</html>\n";
	}
	print $s;


?>
