<?php
/*
** File:           nuwysiwyg.php
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
?>
﻿<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" >
<head>
	<meta http-equiv="Content-Type" content="text/html;" />
	<title></title>
	<script type="text/javascript" src="tinymce/jscripts/tiny_mce/tiny_mce.js"></script>
	<script type="text/javascript">
tinyMCE.init({
	// General options
	mode : "textareas",
	theme : "advanced",
	plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

	// Theme options
	theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
	theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
	theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
	theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,

	// Example content CSS (should be your site CSS)
	content_css : "css/content.css",

	// Drop lists for link/image/media/template dialogs
	template_external_list_url : "lists/template_list.js",
	external_link_list_url : "lists/link_list.js",
	external_image_list_url : "lists/image_list.js",
	media_external_list_url : "lists/media_list.js",

	// Replace values for the template plugin
	template_replace_values : {
		username : "Some User",
		staffid : "991234"
	}
});


	// Save the old window.onresize event function so our handler can call it.
var oldWindowOnResize = window.onresize;
	// This variable is used to stop the 'onbeforeunload' confirm box from
	// showing up when one of the close buttons has been clicked and the button
	// calls a function which calls self.close();
var actionKnown = false;


function closeAndSave()
{
	window.onresize = oldWindowOnResize;
	window.opener.document.getElementById('____<?print $_GET['f'];?>').value='';
	window.opener.document.getElementById('<?print $_GET['f'];?>').value=tinyMCE.get('content').getContent();
	actionKnown = true;
	self.close();
}


function closeWithoutSaving()
{
	window.onresize = oldWindowOnResize;
	actionKnown = true;
	self.close();
}


function nuResizeWYSIWYG()
{
		// Call the old handler if there was one.
	if (oldWindowOnResize)
		oldWindowOnResize();
}


	// Set the new onresize handler to be our function.
window.onresize = nuResizeWYSIWYG;


	</script>
</head>
<body onload="document.getElementById('content').value = opener.document.getElementById('<?php print $_GET['f'];?>').value;" onbeforeunload="if (!actionKnown && confirm('Save changes?')) closeAndSave(); else closeWithoutSaving();">
<form action='' method='post'>
	<table width='100%' align='center'>
		<tr align='center' valign="top" height="40px">
			<td align='center'>
				<input type="button" name="ok" value="Close and Save" onclick="closeAndSave();"/>
				<input type="button" name="cancel" value="Close Without Saving" onclick="closeWithoutSaving();"/>
			</td>
		</tr>
	</table>
	<textarea name="content" id="content" style="height:600px;width:100%;"></textarea>
</form>
</body>
</html>
