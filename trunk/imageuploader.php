<?php
/*
** File:           imageuploader.php
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
ignore_user_abort(true); 
set_time_limit(0);



$dir             = $_GET['dir'];
$iid             = $_GET['iid'];  //-- recordID (zzsys_image_id)

include("../$dir/database.php");
include('common.php');

print makeCSS();
print    "<html><head><title>Upload</title></head>\n";

if($_POST['filename'] == ''){
	
	$filename = uniqid('1');

	print    "<body>";
	print    "<form name='upload' enctype='multipart/form-data' method='POST' action = 'imageuploader.php?dir=$dir&iid=$iid'>\n";
	print    "<table class='upload'><tr align='center'><td class='nuBorder' align='center'><input type='hidden' name='MAX_FILE_SIZE' value='4500000' />\n";
	print    "Choose an image to upload: ";
	print    "<input type='file'   name='uploadedfile'  value=''/>\n";
	print    "<input type='hidden' name='filename'      value='$filename'/> \n <br/> \n";
	print    "<input type='submit'                      value='Upload File' />\n";
	print    "</td></tr></table></form>\n";
	
}else{


	$filename = $_FILES['uploadedfile']['tmp_name'];
	$handle   = fopen($filename, "rb");
	$contents = fread($handle, filesize($filename));
	$contents = addslashes($contents);
	fclose($handle);
	$newRecord = false;
	if($iid == '-1' or $iid == ''){
		$iid = uniqid('1');
		$newRecord = true;
	}
	if($newRecord){
		nuRunQuery("INSERT INTO zzsys_image (zzsys_image_id, sim_blob) VALUES ('$iid','$contents')");
tofile('new');
	}else{
		nuRunQuery("UPDATE zzsys_image SET sim_blob = '$contents' WHERE zzsys_image_id = '$iid'");
tofile('old');
	}

	print "<script>\n\n";
	print "function closedown(){\n";

//--- set zzsys_image_id on opener page


	print "   window.opener.document.getElementById('recordID').value = '$iid';\n\n";
	print "   if(window.opener.nuAfterUpload){\n";
	print "      window.opener.nuAfterUpload('recordID');\n";
	print "   };\n\n";
	print "   self.close();\n\n";

	print "}\n";
	print "</script>\n";

	print "<body onload=' closedown()'>\n";


}

print "</body>";
print "</html>";



?>




