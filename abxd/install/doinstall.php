<?php
chdir("../");
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

include("lib/settings.php");
include("lib/mysqlfunctions.php");
$overallTidy = 0;

function insError($text) {
	print $text."<br /><strong onload=\"reenableControls();\">Installation error occoured.</strong> <button onclick=\"reenableControls(); page = 2; setStep(2);\">Go back</button> to the previous pages and correct the errors, then <button onclick=\"doInstall();\">click here</button>.";
	die();
}
if (isset($_POST['existingSettings'])) $existingSettings = true;
if (!isset($_POST['action'])) die("There's nothing to do.");
if($_POST['action'] == "Install")
{
	ob_start();
	print "Starting ABXD installation.<br />";
	print "Writing database configuration file&hellip;<br />";
	$dbserv = $_POST['dbserv'];
	$dbuser = $_POST['dbuser'];
	$dbpass = $_POST['dbpass'];
	$dbname = $_POST['dbname'];
	if ($existingSettings) include("lib/database.php");
	@mysql_connect($dbserv, $dbuser, $dbpass) or insError("Could not connect to the MySQL server. Are you sure you entered the right things in the SQL credentials page?<br />
The following info was supplied:<br />Server: ".$sqlserv."<br />Username: ".$sqluser."<br />Password: ---<br />Database: ".$dbname."<br />SQL error: ".mysql_error());
	@mysql_select_db($dbname) or insError("Could not select the database. Try creating it.");

	$dbcfg = @fopen("lib/database.php", "w+") or insError("Could not open the database configuration file (lib/database.php) for writing. Make sure that PHP has access to this file.");
	fwrite($dbcfg, "<?php\n");
	fwrite($dbcfg, "//  AcmlmBoard XD support - Database settings\n\n");
	fwrite($dbcfg, "\$dbserv = \"".$dbserv."\";\n");
	fwrite($dbcfg, "\$dbuser = \"".$dbuser."\";\n");
	fwrite($dbcfg, "\$dbpass = \"".$dbpass."\";\n");
	fwrite($dbcfg, "\$dbname = \"".$dbname."\";\n");
	fwrite($dbcfg, "\n?>");
	fclose($dbcfg);
	if ($_POST['htmltidy'] == "check") {
		print "Detecting HTML Tidy support&hellip; ";
		$tidy = (int)function_exists('tidy_repair_string');
		if($tidy)
			print "available.<br />";
		else
			print "not available.<br />";
	} else print "HTML Tidy disabled.<br />";

	include("lib/mysql.php");
	$shakeIt = false;
	if(!is_file("lib/salt.php"))
		$shakeIt = true;
	$miscStat = Query("show table status from ".$dbname." like 'misc'");
	if(NumRows($miscStat) == 0)
		$shakeIt = true;
	else
	{
		$shakeIt = false;
		$misc = Fetch(Query("select * from misc"));
		if($misc['version'] < 220)
		{
			$shakeIt = true;
			$sltf = @fopen("lib/salt.php", "w+") or Kill(format("Could not open \"lib/{0}.php\" for writing. This has been checked for earlier, so if you see this error now, something very strange is going on.", "salt"), "Mysterious filesystem permission error");
			fwrite($sltf, "<?php \$salt = \"sAltlOlscuZdSfjdSDhfjguvDigEnfjFjfjkDH\" ?>\n");
			fclose($sltf);
		}
	}
	if($shakeIt)
	{
		print "Generating security salt&hellip;<br />";
		$cset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
		$salt = "";
		$chct = strlen($cset) - 1;
		while (strlen($salt) < 16)
			$salt .= $cset[mt_rand(0, $chct)];
		$sltf = @fopen("lib/salt.php", "w+") or Kill(format("Could not open \"lib/{0}.php\" for writing. This has been checked for earlier, so if you see this error now, something very strange is going on.", "salt"), "Mysterious filesystem permission error");
		fwrite($sltf, "<?php \$salt = \"".$salt."\" ?>\n");
		fclose($sltf);
	}
	
		
	print "Writing board configuration file&hellip;<br />";
	if (file_exists("lib/settings.php")) include("lib/settings.php");
	else {
		$dateformat = "m-d-y H:i:s";
		$autoLockMonths = 4;
		$warnMonths = 1;
		$customTitleThreshold = 100;
		$viewcountInterval = 10000;
		$noAjax = false;
		$noGuestLayouts = false;
		$theWord = "";
		$systemUser = 0;
		$minWords = 3;
		$uploaderCap = 128;
		$uploaderWhitelist = "png apng jpg bmp gif xcf pdn psd ogv mov wmv ogg mp3 aac wmv txt rtf odf doc docx odp ppt pptx tar gz xz bz2 bz zip jar rar";
		$mailResetFrom = "someone@example.com";
		$lastPostsTimeLimit = 72;
		$profilePreviewText = trim("
			This is <em>a</em> <strong>sample</strong> [u]post[/u]. Its purpose is to demonstrate how your post would look.[quote=Someone][quote=Nina]Hello there, person![/quote]Hello![/quote]
			Quotes are fun :)
		");
		$metaDescription = "It would seem like the board owner forgot to change this.";
		$metaKeywords = "abxd acmlmboard xd";
		$feedname = "Some ABXD feed";
		$rssblurb = "The latest posts from the board.";
		$defaultTheme = "gold";
	}
	if ($existingSettings) include("lib/settings.php");

	$hax = @fopen("lib/settings.php", "w") or insError("Could not open settings file (lib/settings.php) for writing. Make sure PHP has access to this file.");
	fputs($hax, "<?php\n");
	fputs($hax, "//Generated and parsed by the Board Settings admin panel.\n");
	fputs($hax, "\n");
	fputs($hax, "//Settings\n");
	fputs($hax, "\$boardname = \"".prepare($_POST['boardname'])."\";\n");
	fputs($hax, "\$logoalt = \"".prepare($_POST['logoalt'])."\";\n");
	fputs($hax, "\$logotitle = \"".prepare($_POST['logotitle'])."\";\n");
	fputs($hax, "\$dateformat = \"".prepare($dateformat)."\";\n");
	fputs($hax, "\$autoLockMonths = ".(int)$autoLockMonths.";\n");
	fputs($hax, "\$warnMonths = ".(int)$warnMonths.";\n");
	fputs($hax, "\$customTitleThreshold = ".(int)$customTitleThreshold.";\n");
	fputs($hax, "\$viewcountInterval = ".(int)$viewcountInterval.";\n");
	fputs($hax, "\$overallTidy = ".(int)$tidy.";\n");
	fputs($hax, "\$noAjax = ".(int)$noAjax.";\n");
	fputs($hax, "\$noGuestLayouts = ".(int)$noGuestLayouts.";\n");
	fputs($hax, "\$theWord = \"".prepare($theWord)."\";\n");
	fputs($hax, "\$systemUser = ".(int)$systemUser.";\n");
	fputs($hax, "\$minWords = ".(int)$minWords.";\n");
	fputs($hax, "\$minSeconds = ".(int)$minSeconds.";\n");
	fputs($hax, "\$uploaderCap = ".(int)$uploaderCap.";\n");
	fputs($hax, "\$uploaderMaxFileSize = ".(int)$uploaderMaxFileSize.";\n");
	fputs($hax, "\$uploaderWhitelist = \"".prepare($uploaderWhitelist)."\";\n");
	fputs($hax, "\$mailResetFrom = \"".prepare($mailResetFrom)."\";\n");
	fputs($hax, "\$lastPostsTimeLimit = ".(int)$lastPostsTimeLimit.";\n");
	fputs($hax, "\n");
	fputs($hax, "//Hacks\n");
	fputs($hax, "\$hacks['forcetheme'] = \"".prepare($hacks['forcetheme'])."\";\n");
	fputs($hax, "\$hacks['themenames'] = ".(int)$hacks['themenames'].";\n");
	fputs($hax, "\n");
	fputs($hax, "//Profile Preview Post\n");
	fputs($hax, "\$profilePreviewText = \"".prepare($profilePreviewText, "\\\"")."\";\n");
	fputs($hax, "\n");
	fputs($hax, "//Meta\n");
	fputs($hax, "\$metaDescription = \"".prepare($metaDescription)."\";\n");
	fputs($hax, "\$metaKeywords = \"".prepare($metaKeywords)."\";\n");
	fputs($hax, "\n");
	fputs($hax, "//RSS\n");
	fputs($hax, "\$feedname = \"".prepare($feedname)."\";\n");
	fputs($hax, "\$rssblurb = \"".prepare($rssblurb)."\";\n");
	fputs($hax, "\$defaultTheme = \"".prepare($defaultTheme)."\";\n");
	fputs($hax, "\n");
	fputs($hax, "?>");
	fclose($hax);

	print "Creating or updating tables&hellip;<br />";
	Upgrade();
	
	print "Adding bare neccesities&hellip;<br />"; 
	$misc = Query("select * from misc");
	if(NumRows($misc) == 0)
		Query("INSERT INTO `misc` (`views`, `hotcount`, `porabox`, `poratitle`, `milestone`, `maxuserstext`) VALUES (0, 30, '<a href=\"http://github.org/Dirbaio/ABXD\">ABXD repository on GitHub </a><br /><br />Then, <a href=\"editpora.php\">edit this panel</a>.', 'Points of Required Attention', 'Nothing yet.', 'Nobody yet.');");
	Query("UPDATE `misc` SET `version` = 222");
	$smilies = Query("select * from smilies");
	if(NumRows($smilies) == 0)
		Query("
	INSERT INTO `smilies` (`code`, `image`) VALUES
	(':)', 'smile.png'),
	(';)', 'wink.png'),
	(':D', 'biggrin.png'),
	('o_o', 'blank.png'),
	(':awsum:', 'awsum.png'),
	('-_-', 'annoyed.png'),
	('o_O', 'bigeyes.png'),
	(':LOL:', 'lol.png'),
	(':O', 'jawdrop.png'),
	(':(', 'frown.png'),
	(';_;', 'cry.png'),
	('>:', 'mad.png'),
	('O_O', 'eek.png'),
	('8-)', 'glasses.png'),
	('^_^', 'cute.png'),
	('^^;;;', 'cute2.png'),
	('>_<', 'yuck.png'),
	('<_<', 'shiftleft.png'),
	('>_>', 'shiftright.png'),
	('@_@', 'dizzy.png'),
	('^~^', 'angel.png'),
	('>:)', 'evil.png'),
	('x_x', 'sick.png'),
	(':P', 'tongue.png'),
	(':S', 'wobbly.png'),
	(':[', 'vamp.png'),
	('~:o', 'baby.png'),
	(':YES:', 'yes.png'),
	(':NO:', 'no.png'),
	('<3', 'heart.png'),
	(':3', 'colonthree.png'),
	(':up:', 'approve.png'),
	(':down:', 'deny.png'),
	(':durr:', 'durrr.png'),
	('^^;', 'embarras.png'),
	(':barf:', 'barf.png'),
	('._.', 'ashamed.png'),
	('''.''', 'umm.png'),
	('''_''', 'downcast.png'),
	(':big:', 'teeth.png'),
	(':lawl:', 'lawl.png'),
	(':ninja:', 'ninja.png'),
	(':pirate:', 'pirate.png'),
	('D:', 'outrage.png'),
	(':sob:', 'sob.png'),
	(':XD:', 'xd.png'),
	(':yum:', 'yum.png');
");
	print "Reticulating uploader and usercomments where needed&hellip;<br />";
	Query("update `uploader` set `date` = `id` where `date` = 0;");
	Query("update `usercomments` set `date` = `id` where `date` = 0;");

	if($_POST['addbase'])
	{
		print "Creating starting fora&hellip;<br />";
		Import("installDefaults.sql");
	}
	$output = ob_get_clean();
	if ($existingSettings) {
		die("<h3>Board update successful.</h3><br /><br />
			<a href=\"../index.php\">Go back to the board</a>");
	}
	print "
						<h3>Installation successful</h3>
						<p>
							Your board has been successfully set up. You can view the installation output below if you want.<br />
							<button onclick=\"$('#installOutput').toggle();\">Show installation output</button><br />
							<div id=\"installOutput\" style=\"display: none;\">".$output."</div>
						</p>
						<p>
							<a href=\".\">Go to ".(!isset($existingSettings) ? "your new board" : "the board")."</a> &mdash; everything should be set up and ready to go.<br />
							<a href=\"?page=register\">Register the first user</a> &mdash; the first registered user will be root.
						</p>
	";
}


function prepare($text, $quot = "&quot;")
{
	$s = str_replace("\"", $quot, $text);
	return $s;
}

?>
