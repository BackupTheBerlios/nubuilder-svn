<?php
/*
** File:           reportemaillib.php
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

function sendReportEmail($to, $from = "noreply@nubuilder.com", $content = "nuBuilder Email", $html = false, $subject = "", $wordWrap = 120, $filesource, $filename) {

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
        if(empty($from)){
            $mail->From = 'noreply@nusoftware.com.au';
        }else{
            $mail->From = $from;
        }

        $mail->FromName = $fromname;
        $mail->AddAddress($to);
        $mail->WordWrap = $wordWrap;
        $mail->IsHTML($html);
        $mail->AddAttachment($filesource,$filename);
        $mail->Subject = $subject;
        $mail->Body    = $content;
        return $mail->Send();
}

function getReportFile($fqurl, $ext) {

	$url = fopen($fqurl, "r");

	$content = '';
	while (!feof($url)) {
  		$content .= fread($url, 8192);
	}
	ob_flush();
	$emailID = uniqid();
	$pfile = dirname(__FILE__)."/temppdf/".$emailID.date("YmdHis").$ext;
	$fp = fopen($pfile,"w");
	$fwritetest=fwrite($fp,$content);
	fclose($fp);
	
	return $pfile;
}


?>
