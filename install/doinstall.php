<?php

chdir("../");

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
$overallTidy = 0;

function insError($text)
{
	global $dbpref;
	print $text."<br /><strong onload=\"reenableControls();\">Installation error occoured.</strong> <button onclick=\"reenableControls(); page = 2; setStep(2);\">Go back</button> to the previous pages and correct the errors, then <button onclick=\"doInstall();\">click here</button>.";
	die();
}

	ob_start();

	if(file_exists("lib/database.php"))
		insError("ERROR: Board is already installed. Please delete /lib/database.php to run installation again.");

	print "Starting ABXD installation.<br />";
	print "Writing database configuration file&hellip;<br />";
		
	if(isset($_POST['dbserv']))
	{
		$dbserv = $_POST['dbserv'];
		$dbuser = $_POST['dbuser'];
		$dbpass = $_POST['dbpass'];
		$dbname = $_POST['dbname'];
		$dbpref = $_POST['dbpref'];
	}
	else
		include("lib/database.php");
	
	$dblink = @new mysqli($dbserv, $dbuser, $dbpass);
	
	if ($dblink->connect_error)
	{
		insError(
			"Could not connect to the MySQL server. Are you sure you entered 
			the right things in the SQL credentials page?<br />
			The following info was supplied:<br />
			Server: ".$dbserv."<br />
			Username: ".$dbuser."<br />
			Password: (not shown)<br />
			Database: ".$dbname."<br />
			SQL error: ".$dblink->connect_error);
	}
			
	if (!$dblink->select_db($dbname))
	{
		insError(
			"Could not select the database. Try creating it. <br>
			(The installer doesn't create it automatically for you)");
	}

	$dbcfg = @fopen("lib/database.php", "w+") 
		or insError(
			"Could not open the database configuration file (lib/database.php) for writing.<br>
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

	include("lib/debug.php");
	include("lib/mysql.php");
	
	$shakeIt = false;
	if(!is_file("lib/salt.php"))
		$shakeIt = true;
	else
	{
		include("lib/salt.php");
		if(!isset($salt))
			$shakeIt = true;
	}
	
	if($shakeIt)
	{
		print "Generating security salt&hellip;<br />";
		$cset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
		$salt = "";
		$chct = strlen($cset) - 1;
		while (strlen($salt) < 16)
			$salt .= $cset[mt_rand(0, $chct)];
			
		$sltf = @fopen("lib/salt.php", "w+")
			or insError(
				"Could not open \"lib/salt.php\" for writing. <br>
				This has been checked for earlier, so if you see this error now, 
				something very strange is going on.");
				
		fwrite($sltf, "<?php \$salt = \"".$salt."\" ?>");
		fclose($sltf);
	}
	else
		print "NOT generating security salt. (Already present)<br />";
		
	
	print "Creating or updating tables&hellip;<br />";
	Upgrade();
	
	print "Adding bare neccesities&hellip;<br />"; 
	$misc = Query("select * from {$dbpref}misc");
	if(NumRows($misc) == 0)
		Query("INSERT INTO `{$dbpref}misc` (`views`, `hotcount`, `porabox`, `poratitle`, `milestone`, `maxuserstext`) VALUES (0, 30, '<a href=\"http://github.org/Dirbaio/ABXD\">ABXD repository on GitHub </a><br /><br />Then, <a href=\"editpora.php\">edit this panel</a>.', 'Points of Required Attention', 'Nothing yet.', 'Nobody yet.');");
		
	Query("UPDATE `{$dbpref}misc` SET `version` = 222");

	print "Importing smilies&hellip;<br />";

	$smilies = Query("select * from {$dbpref}smilies");
	if(NumRows($smilies) == 0)
		Import("install/smilies.sql");

	if(isset($_POST['addbase']))
	{
		print "Creating starting fora&hellip;<br />";
		Import("install/installDefaults.sql");
	}
	
	$output = ob_get_clean();
	
	//Finished!
	
	if ($existingSettings)
		print "<h3>Board update successful.</h3><br /><br />
			<a href=\"../index.php\">Go back to the board</a>";
	else
		print "
						<h3>Installation successful</h3>
						<p>
							Your board has been successfully set up. You can view the installation output below if you want.<br />
							<button onclick=\"$('#installOutput').toggle();\">Show installation output</button><br />
							<div id=\"installOutput\" style=\"display: none;\">".$output."</div>
						</p>
						<p>
							<a href=\".\">Go to the board</a> &mdash; everything should be set up and ready to go.<br />
							<a href=\"?page=register\">Register the first user</a> &mdash; the first registered user will be root.
						</p>
	";

?>
