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

include("lib/mysqlfunctions.php");
include("lib/version.php");
include("lib/debug.php");
include("lib/mysql.php");


//Here goes the main thing

function install()
{
	global $dblink, $dbserv, $dbuser, $dbpass, $dbname, $dbpref, $dberror, $abxd_version;
	
	doSanityChecks();
	
	if(file_exists("config/database.php"))
	{
		//TODO: Check for errors when parsing this file (It may be corrupted or wrong or whatever.
		//If it fails, fail gracefully and instruct the user to fix or delete database.php
		include("config/database.php");
	}
	else
	{
		$dbserv = $_POST['dbserv'];
		$dbuser = $_POST['dbuser'];
		$dbpass = $_POST['dbpass'];
		$dbname = $_POST['dbname'];
		$dbpref = $_POST['dbpref'];
	}
	
	$convert = $_POST["convert"]=="true";
	$convertFrom = $_POST["convertFrom"];
	$convertDbName = $_POST["convertDbName"];
	$convertDbPrefix = $_POST["convertDbPrefix"];
	
	if(!sqlConnect())
		installationError("Could not connect to the database. Error was: ".$dberror);
	
	$currVersion = getInstalledVersion();
	
	if($currVersion == $abxd_version)
		installationError("The board is already installed and updated (Database version $currVersion). You don't need to run the installer!\n");

	if($currVersion != -1 && $convert)
		die("ERROR: You asked to convert a forum database, but an ABXD installation was already found in the installation DB. Converting is only possible when doing a new installation.");
	
	echo "Setting utf8mb4_unicorn_520__ci collation to the database...\n";
	query("ALTER DATABASE $dbname COLLATE utf8mb4_unicode_520_ci");

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
		Import("install/smilies.sql");
		if($convert)
			runConverter($convertFrom, $convertDbName, $convertPrefix);
		else
			Import("install/installDefaults.sql");
		if(file_exists("config/salt.php"))
			echo "Not generating new salt.php as it's already present...\n";
		else
		{
			echo "Generating new salt.php...\n";
			writeConfigSalt();
		}
	}
	
	if(!file_exists("config/database.php"))
		writeConfigDatabase();
}



//=============================================
// UTILITY FUNCTIONS

function doSanityChecks()
{
	$errors = array();
	// Basic sanity tests
	if (!function_exists('version_compare') || version_compare(PHP_VERSION, '5.3.7') < 0)
		$errors[] = 'PHP 5.3.7 or greater required, but you have PHP ' . PHP_VERSION . '.';
	if (!function_exists('json_encode'))
		if (version_compare(PHP_VERSION, '5.5.0') >= 0)
			$errors[] = 'Because of JSON licensing terms, JSON doesn\'t exist in PHP 5.5. ' .
			            'Depending on how you installed PHP, you may need to install specific ' .
			            'package, enable "json.so" extension in php.ini, or install ' .
			            'PECL <a target="_blank" href="http://pecl.php.net/package/json">json</a> extension.';
		else
			$errors[] = 'You don\'t have JSON support in your installation, however ' .
			            'you aren\'t using PHP version older than 5.2 or newer (or equal to) than 5.5. ' .
			            'No specific instructions could be given, but you could try installing ' .
			            '<a target="_blank" href="http://pecl.php.net/package/json">json</a> extension from PECL.';
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
	global $dbserv, $dbuser, $dbpass, $dbname, $dbpref;
	$dbcfg = @fopen("config/database.php", "w+") 
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
	$cset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
	$salt = "";
	$chct = strlen($cset) - 1;
	while (strlen($salt) < 16)
		$salt .= $cset[mt_rand(0, $chct)];
		
	$sltf = @fopen("config/salt.php", "w+")
		or installationError(
			"Could not open \"config/salt.php\" for writing. <br>
			This has been checked for earlier, so if you see this error now, 
			something very strange is going on.");
			
	fwrite($sltf, "<?php \$salt = \"".$salt."\" ?>");
	fclose($sltf);
}

$converters = array("IPB");

function isValidConverter($converter)
{
	global $converters;
	return in_array($converter, $converters);
}

function runConverter($converter, $db, $pref)
{
	global $converters;
	if(!isValidConverter($converter))
		die("Invalid converter!");
		
	$converter = "converter$converter";

	$converter($db, $pref);
}

// CONVERTERS
// TODO: Maybe move them to another file?

function converterIPB($db, $pref)
{
	echo "Starting IPB conversion...\n";
	$pref = "$db.$pref";

	$isCat = array();
	$forums = query("SELECT * FROM {$pref}forums");
	while($forum = fetch($forums))
		if($forum["parent_id"] == -1) //Category
			$isCat[$forum["id"]] = true;
	
	$forums = query("SELECT * FROM {$pref}forums");
	while($forum = fetch($forums))
	{
		if($forum["parent_id"] == -1) //Category
			query("INSERT INTO {categories} (id, name) VALUES ({0}, {1})", $forum["id"], $forum["name"]);
		else
		{
			$parent = $forum["parent_id"];
			if(!$isCat[$parent])
				$parent = -$parent;
			query("INSERT INTO {forums} (id, title, description, catid) VALUES ({0}, {1}, {2}, {3})", 
					$forum["id"], $forum["name"], $forum["description"], $parent);
		}
	}
	
	$insert = new ChunkedInsert("INSERT INTO {threads} (id, forum, title, sticky, closed, views) VALUES ");
	
	$threads = query("SELECT * FROM {$pref}topics");
	while($thread = fetch($threads))
	{
		$closed = ($thread["state"] == "closed")?1:0;
		$sticky = $thread["pinned"];
		$insert->insert("({0}, {1}, {2}, {3}, {4}, {5})", 
				$thread["tid"], $thread["forum_id"], $thread["title"], $sticky, $closed, $thread["views"]);
	}
	$insert->finish();
	
	$insert = new ChunkedInsert("INSERT INTO {posts} (id, thread, user, date, ip) VALUES ");
	$insertText = new ChunkedInsert("INSERT INTO {posts_text} (pid, text, revision, user, date) VALUES ");
	$posts = query("SELECT * FROM {$pref}posts");
	while($post = fetch($posts))
	{
		$insert->insert("({0}, {1}, {2}, {3}, {4})", $post["pid"], $post["topic_id"], $post["author_id"], $post["post_date"], $post["ip_address"]);
		$insertText->insert("({0}, {1}, 0, {2}, {3})", $post["pid"], $post["post"], $post["author_id"], $post["post_date"]);
	}

/*	for($id = 1000; $id < 21000; $id++)
	{
		$insert->insert("({0}, {1}, {2}, {3}, {4})", $id, 1, 1, 0, "127.0.0.1");
		$insertText->insert("({0}, {1}, 0, {2}, {3})", $id, "HELLO WORLD SPAM LOLOLOL", 1, 0);
	}*/
	
	$insert->finish();
	$insertText->finish();
	
	$insert = new ChunkedInsert("INSERT INTO {users} (id, name, email, title, regdate, convertpassword, convertpasswordsalt, convertpasswordtype) VALUES ");
	$users = query("SELECT * FROM {$pref}members");
	while($user = fetch($users))
	{
		$insert->insert("({0}, {1}, {2}, {3}, {4}, {5}, {6}, {7})", 
			$user["member_id"], $user["name"], $user["email"], $user["title"], $user["joined"], $user["members_pass_hash"], $user["members_pass_salt"], "IPB");
	}
	$insert->finish();

	echo "IPB conversion completed.\n";
}

class ChunkedInsert
{
	private $header;
	private $data;
	private $count;
	public function __construct($header)
	{
		$this->header = parseQuery($header);
		$this->data = array();
		$this->count = 0;
	}
	
	public function insert()
	{
		$args = func_get_args();
		if (is_array($args[0])) $args = $args[0];
		$this->data[] = parseQuery($args);
		$this->count++;
		
		if($this->count >= 100)
			$this->finish();
	}
	
	public function finish()
	{
		if($this->count == 0)
			return;
		
		rawQuery($this->header.implode(",",$this->data));
		$this->data = array();
		$this->count = 0;
	}
}






