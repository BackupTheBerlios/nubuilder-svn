<?php
/*
** File:           reportemail.php
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

require("phpmailer/class.phpmailer.php");
require_once("config.php");

if (strtoupper($_GET['reporttype']) == 'PDF') {
	$toRun = "runpdf.php";
	$ext   = ".PDF";
} else {
	$toRun = "runreport.php";
        $ext   = ".HTM";
}

$report_url     = $_GET['report_url'].$toRun;
$x              = $_GET['x'];
$dir            = $_GET['dir'];
$ses            = $_GET['ses'];
$form_ses       = $_GET['form_ses'];
$r              = $_GET['r'];

$to             = (empty($_GET['to'])) ? ('error@nusoftware.com.au') : ($_GET['to']);
$from           = (empty($_GET['from'])) ? ('noreply@nusoftware.com.au') : ($_GET['from']);
$receipt        = (!empty($_GET['receipt'])) ? ($_GET['receipt']) : 'false';
// BEGIN - 2009/05/29 - Michael
// Added urldecode to these GET variables.
$subject        = (!empty($_GET['subject']))  ? urldecode($_GET['subject'])  : 'nuBuilder Report';
$message        = (!empty($_GET['message']))  ? urldecode($_GET['message'])  : 'Please save the attached file to the desktop before opening.';
$filename       = (!empty($_GET['filename'])) ? urldecode($_GET['filename']) : 'attached';
// END - 2009/05/29 - Michael

//put url together
$fqurl          = $report_url."?x=".$x."&dir=".$dir."&ses=".$ses."&form_ses=".$form_ses."&r=".$r."&thisauth=4887aa210c4f420080724070105&emailer=1";

//create file on server
$pfile = getReportFile($fqurl, $ext);

// BEGIN - 2009/05/29 - Michael
	// Check if there was an error getting the report file.
	// sendResponse terminates the script.
if (!$pfile)
	sendResponse("report_error");
// END - Michael

//send email
// BEGIN - 2009/05/29
	// Check if there was an error sending the email.
if (!sendReportEmail($to, $from, $message, $html = true, $subject, $wordWrap = 120, $pfile, $filename.$ext, $receipt))
	sendResponse("email_error");
// END - 2009/05/29 - Michael

//debug email
//sendReportEmail('shane@nusoftware.com.au', 'shane@nusoftware.com.au', $fqurl, $html = true, 'debug', $wordWrap = 120, $pfile, $filename.$ext);

//delete file
@unlink($pfile);

// 2009/05/29 - Michael
sendResponse($form_ses);
//set cookie

function sendReportEmail($to, $from = "noreply@nubuilder.com", $content = "nuBuilder Email", $html = false, $subject = "", $wordWrap = 120, $filesource, $filename, $receipt = "false") {
        $mail = new PHPMailer();

// BEGIN - 2009/06/09 - Michael
        switch ($GLOBALS["NUMailMethod"])
        {
			// Use the sendmail binary.
        case "sendmail":
        {
                $mail->IsSendmail();
                break;
        } // case
			// Use an SMTP server to send the mail.
        case "smtp":
        {
                $mail->IsSMTP();
                $mail->Host     = (!empty($GLOBALS["NUSMTPHost"]))     ? $GLOBALS["NUSMTPHost"] : "127.0.0.1";
                $mail->SMTPAuth = (!empty($GLOBALS["NUSMTPUsername"])) ? true : false;
                if ($mail->SMTPAuth)
                {
                        $mail->Username = (!empty($GLOBALS["NUSMTPUsername"])) ? $GLOBALS["NUSMTPUsername"] : "";
                        $mail->Password = (!empty($GLOBALS["NUSMTPPassword"])) ? $GLOBALS["NUSMTPPassword"] : "";
                } // if
	} // case
			// Use PHP's built-in mail function.
	case "mail":
        default:
	{
					// Nothing to do, "mail" is the PHPMailer default.
        } // default
        } // switch
// END - 2009/06/09 - Michael

	if($receipt == "true"){
		$mail->ConfirmReadingTo = $from;
	}
		
        if(empty($from)){
            $mail->From = 'noreply@nusoftware.com.au';
        }else{
            $mail->From = $from;
        }

        $mail->FromName = $fromname;
		$toArray = explode(',',$to);
		for($i = 0; $i < count($toArray); $i++){
			if($toArray[$i]){
				$mail->AddAddress($toArray[$i]);
			}
		}
        $mail->WordWrap = $wordWrap;
        $mail->IsHTML($html);
        $mail->AddAttachment($filesource,$filename);
        $mail->Subject = $subject;
        $mail->Body    = $content;
        return $mail->Send();
}

function getReportFile($fqurl, $ext) {
// BEGIN - 2009/06/10 - Michael
// Changed the code that gets the report's content from fopen to CURL.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $fqurl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$content = curl_exec($ch);
	curl_close($ch);
// END - 2009/06/10 - Michael
	$emailID = uniqid();
	$pfile = dirname(__FILE__)."/temppdf/".$emailID.date("YmdHis").$ext;
	$fp = @fopen($pfile,"w");
// BEGIN - 2009/05/29 - Michael
		// Make sure we could open our temp file.
	if (!$fp)
		return NULL;
	// Make sure we could write to our temp file.
	if (!@fwrite($fp,$content))
	{
			// We need to close the file because we did open it.
			fclose($fp);
		return NULL;
	} // if
// END - 2009/05/29
	fclose($fp);
        return $pfile;
}

// 2009/05/29 - Michael
// sendResponse()
//
// Sets the "emailREPORT" cookie
function sendResponse($cookie_value)
{
	setcookie("emailREPORT", $cookie_value);
	echo <<<EOHTML
			<html>
			<head>
				<script type='text/javascript'>
				window.parent.emailSendResponse();
				</script>
			</head>
			</html>
EOHTML;
	die;
} // func

?>
