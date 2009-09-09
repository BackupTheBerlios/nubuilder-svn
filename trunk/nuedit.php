<?php
/*
** File:           nuedit.php
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

include("config.php");
$allowCheckSyntax = ($_GET['l'] == 'php') && $NUPHPExecutable;
?>
﻿<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" >
<head>
	<meta http-equiv="Content-Type" content="text/html;" />
	<title></title>
	<script language="Javascript" type="text/javascript" src="edit_area/edit_area_full.js"></script>
	<script language="Javascript" type="text/javascript">
	// Initialize an edit area for our code.
editAreaLoader.init({id:"php_code",start_highlight:true,allow_resize:"both",allow_toggle:true,language:"en",syntax:"<?php print $_GET['l'];?>"});


	// Save the old window.onresize event function so our handler can call it.
	// This is incase the EditArea code sets it's own onresize handler.
var oldWindowOnResize = window.onresize;
	// This variable is used to stop the 'onbeforeunload' confirm box from
	// showing up when one of the close buttons has been clicked and the button
	// calls a function which calls self.close();
var actionKnown = false;


function closeAndSave()
{
		// Restore the old onresise handler. This is because we need to toggle off
		// the editAreaLoader below, and our onresize handler always turns it back
		// on. The onresize handler is called by self.close().
	window.onresize = oldWindowOnResize;
	window.opener.document.getElementById('____<?php print $_GET['f'];?>').value='';
	window.opener.document.getElementById('<?php print $_GET['f'];?>').value=editAreaLoader.getValue('php_code');
		// The editAreaLoader needs to be turned off or a javascript error will
		// display when the window is closed.
	editAreaLoader.toggle_off("php_code");
	actionKnown = true;
	self.close();
}


function closeWithoutSaving()
{
		// Restore the old onresise handler. This is because we need to toggle off
		// the editAreaLoader below, and our onresize handler always turns it back
		// on. The onresize handler is called by self.close().
	window.onresize = oldWindowOnResize;
		// The editAreaLoader needs to be turned off or a javascript error will
		// display when the window is closed.
	editAreaLoader.toggle_off("php_code");
	actionKnown = true;
	self.close();
}


function nuResizeEditArea()
{
		// Get our text area which we store our code in and set it's height.
		// The -90 is to allow for the 40 pixel table and line breaks EditArea puts at the bottom
		// of the page.
	var codeTable = document.getElementById("php_code");
	codeTable.style.height = (document.getElementsByTagName("html")[0].clientHeight - 100) + "px";
		// Check if the edit area has been created (a frame will exist for it), and call the 
		// toggle_off/on functions to reset the size to match that of "php_code".
	var editAreaFrame = document.getElementById("frame_php_code");
	if (editAreaFrame)
	{
		editAreaLoader.toggle_off("php_code");
		editAreaLoader.toggle_on("php_code");
	}
		// Call the old handler if there was one.
	if (oldWindowOnResize)
		oldWindowOnResize();
}


	// Set the new onresize handler to be our function.
window.onresize = nuResizeEditArea;


<?php
if ($allowCheckSyntax)
{
	echo "function checkSyntax(){\n";
	echo "	var form = document.forms[\"syntaxCheckForm\"];\n";
	echo "	form[\"codeToCheck\"].value = editAreaLoader.getValue(\"php_code\");\n";
	echo "	form.submit();\n";
	echo "}\n";
}
?>


	</script>
</head>
<body onload="document.getElementById('php_code').value = opener.document.getElementById('<?print $_GET['f'];?>').value;" onbeforeunload="if (!actionKnown && confirm('Save changes?')) closeAndSave(); else closeWithoutSaving();">
<form action='' method='post'>
	<table width='100%' align='center'>
		<tr align='center' valign="top" height="40px">
			<td align='center'>
<?php
if ($allowCheckSyntax)
	echo "      <input type=\"button\" name=\"CheckSyntax\" value=\"Check Syntax\" onclick=\"checkSyntax();\"/>\n";
?>
				<input type="button" name="ok" value="Close and Save" onclick="closeAndSave();"/>
				<input type="button" name="cancel" value="Close Without Saving" onclick="closeWithoutSaving();"/>
			</td>
		</tr>
	</table>
	<textarea name="php_code" id="php_code" style="height:0px;width:100%;"></textarea>
</form>
<?php
if ($allowCheckSyntax)
{
	echo "<form name=\"syntaxCheckForm\" id=\"syntaxCheckForm\" action=\"nusyntaxcheck.php\" method=\"post\" target=\"_blank\">\n";
	echo "	<textarea name=\"codeToCheck\" id=\"codeToCheck\" style=\"visibility: hidden;\"></textarea>\n";
	echo "</form>\n";
}
?>
</body>
</html>
