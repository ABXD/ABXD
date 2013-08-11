<?php

//Random includes needed for the installer to work.

$debugMode = true;
error_reporting(-1 & ~E_NOTICE);

function cdate($format, $date = 0)
{
	global $loguser;
	if($date == 0)
		$date = gmmktime();
	$hours = (int)($loguser['timezone']/3600);
	$minutes = floor(abs($loguser['timezone']/60)%60);
	$plusOrMinus = $hours < 0 ? "" : "+";
	$timeOffset = $plusOrMinus.$hours." hours, ".$minutes." minutes";
	return gmdate($format, strtotime($timeOffset, $date));
}

include ("config/paths.php"); // This one should stay like this or install will fail!
include($libPath . "/mysqlfunctions.php");
include($libPath . "/version.php");
include($libPath . "/debug.php");
include($libPath . "/mysql.php");

function install()
{
	global $dblink, $dbserv, $dbuser, $dbpass, $dbname, $dbpref, $dberror, $abxd_version;
	global $installationPath, $libPath;

	doSanityChecks();

	if(file_exists($installationPath . "/config/database.php"))
	{
		//TODO: Check for errors when parsing this file (It may be corrupted or wrong or whatever.
		//If it fails, fail gracefully and instruct the user to fix or delete database.php
		include($installationPath . "/config/database.php");
	}
	else
	{
		$dbserv = $_POST['dbserv'];
		$dbuser = $_POST['dbuser'];
		$dbpass = $_POST['dbpass'];
		$dbname = $_POST['dbname'];
		$dbpref = $_POST['dbpref'];
	}

	if(!sqlConnect())
		installationError("Could not connect to the database. Error was: ".$dberror);

	$currVersion = getInstalledVersion();

	if($currVersion == $abxd_version)
		installationError("The board is already installed and updated (Database version $currVersion). You don't need to run the installer!\n");

	echo "Setting utf8_unicode_ci collation to the database...\n";
	query("ALTER DATABASE $dbname COLLATE utf8_unicode_ci");

	if($currVersion == -1)
		echo "Installing database version $abxd_version...\n";
	else
		echo "Upgrading database from version $currVersion to $abxd_version...\n";
	upgrade();

	$misc = Query("select * from {misc}");
	if(NumRows($misc) == 0)
		Query("INSERT INTO `{misc}` (`views`, `hotcount`, `milestone`, `maxuserstext`) VALUES (0, 30, 'Nothing yet.', 'Nobody yet.');");

	Query("UPDATE `{misc}` SET `version` = {0}", $abxd_version);


	if(!is_dir("config"))
		mkdir("config");

	if($currVersion == -1)
	{
		//Stuff to do on new installation (Not upgrade)
		Import($installationPath . "/install/smilies.sql");
		Import($installationPath . "/install/installDefaults.sql");
		if(!file_exists($installationPath . "/config/salt.php"))
			writeConfigSalt();
	}

	if(!file_exists($installationPath . "/config/database.php"))
		writeConfigDatabase();
}



//=============================================
// UTILITY FUNCTIONS

function doSanityChecks()
{
	$errors = array();
	// Basic sanity tests
	if (!function_exists('version_compare') || version_compare(PHP_VERSION, '5.0.0', '<'))
		$errors[] = 'PHP 5.0.0 required, but you have PHP ' . PHP_VERSION . '.';
	if (!function_exists('json_encode'))
		$errors[] = 'As you have PHP older than PHP 5.2.0, you have to install ' .
			        'PECL <a target="_blank" href="http://pecl.php.net/package/json">json</a> extension.';
	if (!function_exists('preg_match'))
		$errors[] = 'PCRE extension is required, yet it wasn\'t found. Please install it.';
	if (!class_exists('mysqli'))
		$errors[] = 'MySQLi extension wasn\'t found. Please install MySQLi.';
	if (ini_get('register_globals'))
		$errors[] = 'register_globals is not supported. Continuing may cause your ' .
			        'board to be hacked. Disable it.';
	/* This program will only run if the laws of mathematics hold */
	if (1 == 0)
		$errors[] = "Oh crap - we are not running in the correct Universe.";

	if(count($errors))
	{
		echo "Your server doesn't meet the minimum requeriments for ABXD:\n";
		foreach($errors as $error)
			echo " - ", $error, "\n";

		echo "\nCan't install ABXD. Sorry!\n";
		die();
	}
}

//Returns -1 if board is not installed.
//Returns the version installed if installed.
function getInstalledVersion()
{
	//If no misc table, not installed.
	if(numRows(query("SHOW TABLES LIKE '{misc}'")) == 0)
		return -1;

	$row = query("SELECT * FROM {misc}");

	//If no row in misc table, not installed.
	if(numRows($row) == 0)
		return -1;

	//Otherwise return version.
	$row = fetch($row);
	return $row["version"];
}

function installationError($message)
{
	echo $message;
	die();
}


function writeConfigDatabase()
{
	global $dbserv, $dbuser, $dbpass, $dbname, $dbpref, $installationPath;

	$dbcfg = @fopen($installationPath . "/config/database.php", "w+")
		or installationError(
			"Could not open the database configuration file (config/database.php) for writing.<br>
			 Make sure that PHP has access to this file.");

	fwrite($dbcfg, "<?php\n");
	fwrite($dbcfg, "//  AcmlmBoard XD support - Database settings\n\n");
	fwrite($dbcfg, '$dbserv = ' . var_export($dbserv, true) . ";\n");
	fwrite($dbcfg, '$dbuser = ' . var_export($dbuser, true) . ";\n");
	fwrite($dbcfg, '$dbpass = ' . var_export($dbpass, true) . ";\n");
	fwrite($dbcfg, '$dbname = ' . var_export($dbname, true) . ";\n");
	fwrite($dbcfg, '$dbpref = ' . var_export($dbpref, true) . ";\n");
	fwrite($dbcfg, "\n?>");
	fclose($dbcfg);
}

function writeConfigSalt()
{
	global $installationPath;

	$cset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
	$salt = "";
	$chct = strlen($cset) - 1;
	while (strlen($salt) < 16)
		$salt .= $cset[mt_rand(0, $chct)];

	$sltf = @fopen($installationPath . "/config/salt.php", "w+")
		or installationError(
			"Could not open \"config/salt.php\" for writing. <br>
			This has been checked for earlier, so if you see this error now,
			something very strange is going on.");

	fwrite($sltf, "<?php \$salt = \"".$salt."\" ?>");
	fclose($sltf);
}
