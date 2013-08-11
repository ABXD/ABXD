<?php
// AcmlmBoard XD support - Main hub

// I can't believe there are PRODUCTION servers that have E_NOTICE turned on. What are they THINKING? -- Kawa
error_reporting(E_ALL ^ E_NOTICE | E_STRICT);

// This one should stay like this or the board will redirect to the installer all the time.
include ("config/paths.php");

if(!is_file($installationPath . "/config/database.php"))
	die(header("Location: install.php"));

$boardroot = preg_replace('{/[^/]*$}', '/', $_SERVER['SCRIPT_NAME']);

// Deslash GPC variables if we have magic quotes on
if (get_magic_quotes_gpc())
{
	function AutoDeslash($val)
	{
		if (is_array($val))
			return array_map('AutoDeslash', $val);
		else if (is_string($val))
			return stripslashes($val);
		else
			return $val;
	}

	$_REQUEST = array_map('AutoDeslash', $_REQUEST);
	$_GET = array_map('AutoDeslash', $_GET);
	$_POST = array_map('AutoDeslash', $_POST);
	$_COOKIE = array_map('AutoDeslash', $_COOKIE);
}

function usectime()
{
	$t = gettimeofday();
	return $t['sec'] + ($t['usec'] / 1000000);
}
$timeStart = usectime();


include($libPath . "/version.php");
include($installationPath . "/config/salt.php");
include($libPath . "/dirs.php");
include($libPath . "/settingsfile.php");
include($libPath . "/debug.php");

include($libPath . "/mysql.php");
include($installationPath . "/config/database.php");
if(!sqlConnect())
	die("Can't connect to the board database. Check the installation settings");
if(!fetch(query("SHOW TABLES LIKE '{misc}'")))
	die(header("Location: install.php"));

include($libPath . "/mysqlfunctions.php");
include($libPath . "/settingssystem.php");
Settings::load();
Settings::checkPlugin("main");
include($libPath . "/feedback.php");
include($libPath . "/language.php");
include($libPath . "/snippets.php");
include($libPath . "/links.php");

class KillException extends Exception { }
date_default_timezone_set("GMT");

$title = "";

//WARNING: These things need to be kept in a certain order of execution.

include($libPath . "/pluginsystem.php");
loadFieldLists();
include($libPath . "/loguser.php");
include($libPath . "/permissions.php");
include($libPath . "/ranksets.php");
include($libPath . "/post.php");
include($libPath . "/log.php");
include($libPath . "/onlineusers.php");

include($libPath . "/htmlfilter.php");
include($libPath . "/smilies.php");

$theme = $loguser['theme'];
include($libPath . "/write.php");
include($libPath . '/layout.php');

//Classes
include($installationPath . "/class/PipeMenuBuilder.php");

include($libPath . "/lists.php");

$mainPage = "board";
$bucket = "init"; include($libPath . '/pluginloader.php');

