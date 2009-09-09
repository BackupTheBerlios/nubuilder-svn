<?php
/*
** File:           fileuploader.php
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
$udir            = $_GET['udir'];
$ses             = $_GET['ses'];
$field           = $_GET['field'];

include("../$dir/database.php");
include('common.php');

$setup           = nuSetup();
$session         = nuSession($ses, '');
if($session->foundOK == ''){
	print 'you have been logged out..';
	return;
}
print makeCSS();
print    "<html><head><title>Upload</title></head>\n";

if($_POST['filename'] == ''){
	
	$filename = uniqid('1');

	print    "<body>";
	print    "<form name='upload' enctype='multipart/form-data' method='POST' action = 'fileuploader.php?dir=$dir&udir=$udir&ses=$ses&field=$field'>\n";
	print    "<table class='upload'><tr align='center'><td class='nuBorder' align='center'><input type='hidden' name='MAX_FILE_SIZE' value='4500000' />\n";
	print    "Choose a file to upload: ";
	print    "<input type='file'   name='uploadedfile'  value=''/>\n";
	print    "<input type='hidden' name='filename'      value='$filename'/> \n <br/> \n";
	print    "<input type='submit'                      value='Upload File' />\n";
	print    "</td></tr></table></form>\n";
	
}else{

	$fullname = $_POST['filename'].strrchr($_FILES['uploadedfile']['name'],'.');
	print    "<script>\n";
	print    "\n";
	print    "function closedown(){\n\n";
	
	$target_path = $setup->set_file_path . $fullname;
	
	//move file
	if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
			print "   window.opener.document.getElementById('$field').value = '$fullname';\n\n";
			print "   if(window.opener.nuAfterUpload){\n";
			print "      window.opener.nuAfterUpload('$field');\n";
			print "   };\n\n";
	    print "   alert('The file has been successfully uploaded');\n";
	} else{
	    print "   alert('There was an error uploading the file, please try again! .. Error: ".$_FILES['uploadedfile']['error']."');\n";
	}	

	print "   self.close();\n\n";

	print "}\n";
	print "</script>\n";

	print "<body onload=' closedown()'>\n";


}

print "</body>";
print "</html>";



?>




