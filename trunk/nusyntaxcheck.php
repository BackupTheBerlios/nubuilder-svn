<?php
/*
** File:           nusyntaxcheck.php
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
?>
<html>
<head>
	<title>PHP Syntax Check Results</title>
</head>
<body>
<?php

require_once("config.php");

if (!$NUPHPExecutable)
{
	echo <<<EOHTML
	The path to the PHP executable is not set in <strong>config.php</strong>.<br>
	Set <strong>\$NUPHPExecutable</strong> to point to your PHP executable.<br>
	<br>
	The PHP code was not parsed for syntax errors.
EOHTML;
	die;
} // if

	// Get the PHP code from POST and create a unique filename to write to.
$codeToCheck = "<?php ".$_POST["codeToCheck"]." ?>";
$phpFilename = "./temppdf/".uniqid().".php";

	// Open the file in text mode.
$file = fopen($phpFilename, "wt");
if ($file == FALSE)
	die("Error opening <b>".$phpFilename."</b> for writing.");
	// Write the data to the file. Note: don't delete the file if writing failed, it may have written
	// something to the file which we could check.
if (fwrite($file, $codeToCheck, strlen($codeToCheck)) == FALSE)
	die("Error writing to <b>".$phpFilename."</b>");
fclose($file);

	// Run PHP in syntax checking mode on this file, then delete the temporary PHP file.
$result = shell_exec('"'.$NUPHPExecutable.'" -l "'.$phpFilename.'"');
shell_exec("rm -f ".$phpFilename);

	// Display the results of the syntax check.
echo "<pre>".$result."</pre>";

	// Check if there was an error parsing the file.
$errorMatches = NULL;
if (preg_match("/on line ([0-9]*)/", $result, $errorMatches))
{
		// Get the line number which the error occured on.
	$errorLineNumber = $errorMatches[1];
		// Echo some script which will automatically cause the edit area to go to the
		// line which contained an error. Doing that refocuses the edit window, so we
		// need to bring this window to the foreground so the errors can be read.
	echo <<<EOHTML
		<script language="javascript">
			window.opener.frames["frame_php_code"].editArea.go_to_line("$errorLineNumber");
			window.focus();
		</script>
EOHTML;
} // if

?>
</body>
</html>
