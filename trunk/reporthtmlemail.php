<?php
/*
** File:           reporthtmlemail.php
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

//$report_url 	= "https://www.nubuilder.com/productionnu2/";
$report_url     = $_GET['report_url']."nologinrunreport.php";
$x              = $_GET['x'];
$dir            = $_GET['dir'];
$ses            = $_GET['ses'];
$form_ses       = $_GET['form_ses'];
$r              = $_GET['r'];

$to             = $_GET['to'];
$from           = $_GET['from'];
$subject        = $_GET['subject'];
$message        = $_GET['message'];

//put url together
$fqurl		= $report_url."?x=".$x."&dir=".$dir."&ses=".$ses."&form_ses=".$form_ses."&r=".$r."&thisauth=4887aa210c4f420080724070105";

//create file on server
$url = fopen($fqurl, "r");

$content = '';
while (!feof($url)) {
  $content .= fread($url, 8192);
}
ob_flush();
$emailID = uniqid();
$pfile = dirname(__FILE__)."/temppdf/".$emailID.date("YmdHis").".html";
$fp = fopen($pfile,"w");
$fwritetest=fwrite($fp,$content);
fclose($fp);

$ok=HTML_sendEmail($to, $from, '', $message, $subject, $pfile);
@unlink($pfile);
setcookie("emailHTML", $form_ses);

function HTML_sendEmail($to, $from = "noreply@nubuilder.com", $fromname, $content = "nuBuilder report attached", $subject = "", $pfile) {

        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->Host = "127.0.0.1";

        if(empty($from)){
            $mail->From = 'noreply@nusoftware.com.au';
        }else{
            $mail->From = $from;
        }

        $mail->FromName = $fromname;
        $mail->AddAddress($to);
        $mail->WordWrap = 120;
        $mail->IsHTML(true);
	$mail->AddAttachment($pfile,'report.html');
        $mail->Subject = $subject;
        $mail->Body    = $content;
        return $mail->Send();
}

?>
