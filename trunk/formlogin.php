<?php
/*
** File:           formlogin.php
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

	setcookie("security_check", security_check());

	$dir                                   = $_GET['d'];
//--a parameter passed that can be accessed by #session_parameter#
	$parameter                             = $_GET['p'];

	require_once("../$dir/database.php");
	require_once("config.php");
	include('common.php');

	$small                                 = iif($_GET['small'] == '', '0', $_GET['small']);

	$user = mysql_real_escape_string($_POST["u"]);
    $pass = mysql_real_escape_string($_POST["p"]);

	$sessionid                             = uniqid(1);
	$twodaysago                            = nuDateAddDays(Today(),-2);
	nuRunQuery("DELETE FROM zzsys_variable WHERE sva_expiry_date < '$twodaysago'");
	nuRunQuery("DELETE FROM zzsys_trap WHERE sys_added is null OR sys_added < '$twodaysago'");
	nuRunQuery("DELETE FROM zzsys_duplicate WHERE sdu_date < '$twodaysago'");
	nuRunQuery("DELETE FROM zzsys_session  WHERE sss_session_date < '$twodaysago'");

	$RQ                                    = nuRunQuery('');
	$_SESSION['security_check']            = $GLOBALS['security_check'];
	$id                                    = $_SESSION['security_check'];
	$now                                   = date('Y-n-d H:i:s');
	$stoplogin                             = false;

	$globeadminPasswords = array();
#	if ($RQ[3])
		$globeadminPasswords[] = $RQ[3];
	if ($DBGlobeadminPassword)
		$globeadminPasswords[] = $DBGlobeadminPassword;
	if ($NUGlobeadminPassword)
		$globeadminPasswords[] = $NUGlobeadminPassword;
	if ($user=='globeadmin' && in_array($pass, $globeadminPasswords)){//----hardcoded user name and password
		nuSessionSet($id, 'globeadmin', 'globeadmin', 'globeadmin', $small, $parameter); tofile('IN GLOBEADMIN');
	}else{
		$s                                 = "SELECT zzsys_user_id AS ID, sal_name AS AccessLevel, sug_group_name as UserGroupName FROM zzsys_user ";
		$s                                 = $s . "INNER JOIN zzsys_user_group ON sus_zzsys_user_group_id = zzsys_user_group_id ";
		$s                                 = $s . "INNER JOIN zzsys_access_level ON sug_zzsys_access_level_id = zzsys_access_level_id ";
		$s                                 = $s . "WHERE sus_login_name = '$user' AND sus_login_password = '$pass'";

		$t                                 = nuRunQuery($s);
		$r = db_fetch_object($t);
tofile('IN NORMAL USER');
		if($r->ID==''){//--not there
			$stoplogin                     = true; tofile('IN NORMAL USER - EMPTY USER');
		}else{ tofile('IN NORMAL USER - NON EMPTY USER');
			if($user=='globeadmin'){//--can't have any other user as globeadmin
				$stoplogin                     = true;
			}else{
				nuSessionSet($id, $r->AccessLevel, $r->ID, $r->UserGroupName, $small, $parameter);
			}
		}
	}

//---print html	
	print "<html>\n";
	print "<script type='text/javascript' src='common.js' language='javascript'></script>\n";
	print "<script type='text/javascript' language='javascript'>\n";
	print "function closeAndOpenForm(){\n";
	print "	   openForm(\"index\", \"-1\");\n";
	print "	   window.open('close1.html', '_self');\n";
	
	print "}\n";
	print "function customDirectory(){\n";
	print "   return '$dir';\n";
	print "}\n";
	print "\n";
	print "function session_id(){\n";
	print "   return '$id';\n";
	print "}\n";
	print "\n";
	print "function goBack(){\n";
	print "   window.open(document.referrer,'_self');";
	print "}\n";
	print "\n";
	print "</script>\n";
	if($stoplogin){ tofile('IN STOPLOGIN');
		// 2009/07/15 - Nick changed failed login attempt to use document.referrer to go back to the login page
		print "<body onload='goBack();'>\n";
	}else{
		$userID = iif($r->ID=='','globeadmin',$r->ID);
// 2009/06/02 - Michael - added IP logging.
                nuRunQuery("INSERT INTO zzsys_user_log (zzsys_user_log_id, sul_zzsys_user_id, sul_ip, sul_start) VALUES ('$id', '$userID', '{$_SERVER['REMOTE_ADDR']}', '$now')");
//		nuRunQuery("INSERT INTO zzsys_user_log (zzsys_user_log_id, sul_zzsys_user_id, sul_start) VALUES ('$id', '$userID', '$now')");
		tofile("small : $small");
		if($small == '1'){ tofile('IN SMALL = 1');
			print "<body onload='document.index.submit();'>\n";
			print "<form name='index' method='post' action='formsmall.php?x=1&r=-1&dir=$dir&ses=$id&f=index'></form>\n";
		}else{ tofile('IN SMALL <> 1');
			print "<body onload='closeAndOpenForm();'>\n";
		}
	}
	print "</body></html>\n";
	tofile('cookie .. '.$_COOKIE['security_check']);
	function security_check(){
		$GLOBALS['security_check'] = uniqid('1');
		return $GLOBALS['security_check'];
	}

	
?>
