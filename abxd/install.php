<?php

/*if (file_exists("lib/database.php"))
{
		die("No, I don't think so.");
}*/

if(isset($_GET['dbcheck']))
{
	include("lib/write.php");
	//dbserv=localhost&dbname=helmeted_nikoboard&dbuser=helmeted_kawa&dbpass=marshmallows
	parse_str($_GET['dbcheck']);
	//2005: no such server
	//1045: no such user
	@mysql_connect($dbserv, $dbuser, $dbpass) or die(mysql_errno() == 2005 ? format("Could not connect to any database server at \"{0}\". Usually, the database server runs on the same system as the web server, in which case \"localhost\" would suffice. If not, the server could be (temporarily) offline, nonexistant, or maybe you entered a full URL instead of just a hostname (\"http://www.mydbserver.com\" instead of just \"mydb.com\").", $dbserv) : format("The database server has rejected your username and/or password."));
	mysql_select_db($dbname) or die(format("Could not select database \"{0}\". Even though we could connect to the database server, there does not seem to be a database by that name on that server. Perhaps you forgot to add it before trying to install?", $dbname));
	die("OK");
}

$noViewCount = TRUE;
$noOnlineUsers = TRUE;
$noFooter = TRUE;
function runBucket($blar)
{
	;
}
function IsAllowed()
{
	return false;
}
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
include("lib/snippets.php");
include("lib/settings.php");
$overallTidy = 0;
unset($misc['porabox']);
$title = "Installation";
$timeStart = usectime();
include("lib/feedback.php");
include("lib/write.php");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
	<title>ABXD Installation</title>
	<meta http-equiv="Content-Type" content="text/html; CHARSET=utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico" />
	<link rel="stylesheet" type="text/css" href="css/common.css" />
	<link rel="stylesheet" type="text/css" href="css/default.css" />
	<script type="text/javascript" src="lib/tricks.js"></script>
	<script type="text/javascript" src="lib/jquery.js"></script>
	<script type="text/javascript">
		function checkDB()
		{
			var foo = $('form').serialize();
			$.get("install.php", { dbcheck: foo }, function(data)
			{
				if(data == "OK")
				{
					$('#installButton')[0].disabled = false;
					$('#errorText').text("Database credentials have been accepted. You can continue the installation.");
					$('#errorText').fadeIn(600);
				}
				else
				{
					$('#installButton')[0].disabled = true;
					$('#errorText').text(data);
					$('#errorText').fadeIn(600);
				}
			});
		}
	</script>
	<style type="text/css">
		button, input[type="submit"]
		{
			border: 1px solid #000646;
			background: rgba(0, 100, 200, 0.25);
			color: #EEEEEE;
			font-size: 120%;
			padding: 0.2em 1em;
			border-radius: 0.5em;
		}
		button:hover, input[type="submit"]:hover
		{
			background: rgba(0, 100, 200, 0.5);
		}
	</style>
</head>
<body style="width: 80%; margin: 0em 3em;">
<?php

if(is_file("lib/database.php"))
	include("lib/database.php");
else
	$dbserv = "localhost";

if(isset($_GET['delete']))
{
	unlink("install.php") or Kill("Could not delete installation script.");

	die(header("Location: ."));
}

write(
"
	<div style=\"text-align: center; width: 100%;\">
		<img src=\"img/themes/default/logo.png\" alt=\"ABXD 3.0\" style=\"margin: 1em auto;\" />
	</div>
");

if(!isset($_POST['action']))
{
	write(
"
	<div id=\"page1\">
		<div class=\"width50 outline faq\" style=\"float: right; padding: 1em;\">
			<h2>Welcome to ABXD 3.0</h2>
			<p>
				We've tried our best to stamp out all of those recurring problems in earlier versions, and to make the experience for both users and staff a little better.
			</p>
			<p>
				[rant a little longer, preferably without lies.]
			</p>
		</div>
		
		<div style=\"width: 40%\">
			<div class=\"errorc\">Step 1</div>
			<div class=\"errort margin\" style=\"padding: 1em;\">
				<b>Running some preliminary access tests&hellip;</b><br />
");	
	//Begin Pyra-proofing...
	write("&bull; Writing to the board's main directory&hellip; ");
	$test = @fopen("test.txt", "w");
	if($test === FALSE)
	{
		write("<strong>FAILED</strong><hr />PHP does not seem to have write access to this directory ({0}). This is required for proper functionality. Please contact your hosting provider for information on how to make the current directory writable.</div></div>", $_SERVER['DOCUMENT_ROOT']);
		die();
	}
	else
	{
		write("OK.<br />");
		fclose($test);
		unlink("test.txt");
	}
	write("&bull; Writing to the /lib subdirectory&hellip; ");
	$test = @fopen("lib/test.txt", "w");
	if($test === FALSE)
	{
		write("<strong>FAILED</strong><hr />PHP does not seem to have write access to the /{1} directory ({0}{1}). This is required for proper functionality. Please contact your hosting provider for information on how to make that directory writable.</div></div>", $_SERVER['DOCUMENT_ROOT'], "lib");
		die();
	}
	else
	{
		write("OK.<br />");
		fclose($test);
		unlink("lib/test.txt");
	}
	write("&bull; Writing to the /img/avatars subdirectory&hellip; ");
	$test = @fopen("img/avatars/test.txt", "w");
	if($test === FALSE)
	{
		write("<strong>FAILED</strong><hr />PHP does not seem to have write access to the /{1} directory ({0}{1}). This is required for proper functionality. Please contact your hosting provider for information on how to make that directory writable.</div></div>", $_SERVER['DOCUMENT_ROOT'], "img/avatars");
		die();
	}
	else
	{
		write("OK.<br />");
		fclose($test);
		unlink("img/avatars/test.txt");
	}
	write(
"
				All file access tests are clear.
				<hr />
				<button onclick=\"$('#page1').slideUp(200); $('#page2').slideDown(200);\">Next &rarr;</button>
			</div>
		</div>
	</div>

	<form action=\"install.php\" method=\"post\">
		<table class=\"outline margin width100\" id=\"page2\" style=\"display: none;\">
			<tr class=\"header0\">
				<th colspan=\"2\">
					Step 2 - Installation options
				</th>
			</tr>
			<tr>
				<td class=\"cell2\">
					<label for=\"dbs\">Database server</label>
				</td>
				<td class=\"cell0\">
					<input type=\"text\" id=\"dbs\" name=\"dbserv\" style=\"width: 98%;\" value=\"{0}\" />
				</td>
			</tr>
			<tr>
				<td class=\"cell2\">
					<label for=\"dbn\">Database name</label>
				</td>
				<td class=\"cell0\">
					<input type=\"text\" id=\"dbn\" name=\"dbname\" style=\"width: 98%;\" value=\"{3}\" />
				</td>
			</tr>
			<tr>
				<td class=\"cell2\">
					<label for=\"dun\">Database user name</label>
				</td>
				<td class=\"cell1\">
					<input type=\"text\" id=\"dun\" name=\"dbuser\" style=\"width: 98%;\" value=\"{1}\" />
				</td>
			</tr>
			<tr>
				<td class=\"cell2\">
					<label for=\"dpw\">Database user password</label>
				</td>
				<td class=\"cell1\">
					<input type=\"password\" id=\"dpw\" name=\"dbpass\" style=\"width: 98%;\" value=\"{2}\" />
				</td>
			</tr>
			<tr>
				<td class=\"cell2\">
					Options
				</td>
				<td class=\"cell1\">
					<label>
						<input type=\"checkbox\" id=\"b\" name=\"addbase\" />
						Add starting forums and the usual Super Mario rankset
					</label>
				</td>
			</tr>
			<tr class=\"cell2\">
				<td></td>
				<td>
					<button onclick=\"checkDB(); return false;\">Check settings</button>
					<input type=\"submit\" name=\"action\" value=\"Install\" id=\"installButton\" disabled=\"disabled\" style=\"font-weight: bold;\" />
				</td>
			</tr>
			<tr class=\"cell2\" id=\"errorRow\">
				<td colspan=\"2\" id=\"errorText\" style=\"display: none;\">
					Errors go here.
				</td>
			</tr>
			<tr class=\"cell2\">
				<td colspan=\"2\">
					<strong>Warning</strong> &mdash;
					When updating, <em>back up your database</em> before you press the \"Install\" button and don't check the \"add starting forums\" box.
				</td>
			</tr>
		</table>
	</form>
", $dbserv, $dbuser, $dbpass, $dbname);

}
else if($_POST['action'] == "Install")
{
	print "<div class=\"outline faq\">";

	print "Writing database configuration file&hellip;<br />";
	$dbserv = $_POST['dbserv'];
	$dbuser = $_POST['dbuser'];
	$dbpass = $_POST['dbpass'];
	$dbname = $_POST['dbname'];
	@mysql_connect($dbserv, $dbuser, $dbpass) or Kill("Could not connect to the database server. This has been checked for earlier, so if you see this error now, something very strange is going on.", "And all of a sudden&hellip;");
	@mysql_select_db($dbname) or Kill("Could not select our database. This has been checked for earlier, so if you see this error now, something very strange is going on.", "And all of a sudden&hellip;", "And all of a sudden&hellip;");

	$dbcfg = @fopen("lib/database.php", "w+") or Kill(format("Could not open \"lib/{0}.php\" for writing. This has been checked for earlier, so if you see this error now, something very strange is going on.", "database"), "Mysterious filesystem permission error");
	fwrite($dbcfg, "<?php\n");
	fwrite($dbcfg, "//  AcmlmBoard XD support - Database settings\n\n");
	fwrite($dbcfg, "\$dbserv = \"".$dbserv."\";\n");
	fwrite($dbcfg, "\$dbuser = \"".$dbuser."\";\n");
	fwrite($dbcfg, "\$dbpass = \"".$dbpass."\";\n");
	fwrite($dbcfg, "\$dbname = \"".$dbname."\";\n");
	fwrite($dbcfg, "\n?>");
	fclose($dbcfg);

	print "Detecting Tidy support&hellip; ";
	$tidy = (int)function_exists('tidy_repair_string');
	if($tidy)
		print "available.<br />";
	else
		print "not available.<br />";

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
	include("lib/settings.php");
	$hax = @fopen("lib/settings.php", "w") or Kill(format("Could not open \"lib/{0}.php\" for writing. This has been checked for earlier, so if you see this error now, something very strange is going on.", "settings"), "Mysterious filesystem permission error");
	fputs($hax, "<?php\n");
	fputs($hax, "//Generated and parsed by the Board Settings admin panel.\n");
	fputs($hax, "\n");
	fputs($hax, "//Settings\n");
	fputs($hax, "\$boardname = \"".prepare($boardname)."\";\n");
	fputs($hax, "\$logoalt = \"".prepare($logoalt)."\";\n");
	fputs($hax, "\$logotitle = \"".prepare($logotitle)."\";\n");
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
	fputs($hax, "\n");
	fputs($hax, "?>");
	fclose($hax);

	print "Creating/updating tables&hellip;<br />";
	Upgrade();
	
	print "Adding bare neccesities&hellip;<br />"; 
	$misc = Query("select * from misc");
	if(NumRows($misc) == 0)
		Query("INSERT INTO `misc` (`views`, `hotcount`, `porabox`, `poratitle`, `milestone`, `maxuserstext`) VALUES (0, 30, 'You might want to download the geolocation<br />table and some avatar sets from the same<br />place you got this forum software:<br /><br /><a href=\"http://helmet.kafuka.org/thepile/ABXD\">ABXD page on The Pile</a><br /><br />Then, <a href=\"editpora.php\">edit this panel</a>.', 'Points of Required Attention', 'Nothing yet.', 'Nobody yet.');");
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

	print "<h3>Your board has been set up.</h3>";
	print "Things for you to do now:";
	print "<ul>";
	print "<li><a href=\"register.php\">Register your account</a> &mdash; the first to register gets to be Root.</li>";
	print "<li>Check out the <a href=\"admin.php\">administrator's toolkit</a>.</li>";
	print "<li><a href=\"install.php?delete=1\">Delete</a> the installation script.</li>";
	print "</ul>";

	print "</div>";
}

//SQL importer based on KusabaX installer
function Import($sqlFile)
{
	$handle = fopen($sqlFile, "r");
	$data = fread($handle, filesize($sqlFile));
	fclose($handle);

	$sqlData = explode("\n", $data);
	//Filter out the comments and empty lines...
	foreach ($sqlData as $key => $sql)
		if (strstr($sql, "--") || strlen($sql) == 0)
			unset($sqlData[$key]);
	$data = implode("",$sqlData);
	$sqlData = explode(";",$data);
	foreach($sqlData as $sql)
	{
		if(strlen($sql) === 0)
			continue;
		if(strstr($sql, "CREATE TABLE `"))
		{
			$pos1 = strpos($sql, '`');
			$pos2 = strpos($sql, '`', $pos1 + 1);
			$tableName = substr($sql, $pos1+1, ($pos2-$pos1)-1);
			print "<li>".$tableName."</li>";
		}
		$query = str_replace("SEMICOLON", ";", $sql);
		Query($query);
	}
}


function prepare($text, $quot = "&quot;")
{
	$s = str_replace("\"", $quot, $text);
	return $s;
}

function Upgrade()
{
	global $dbname;
	include("installSchema.php");
	foreach($tables as $table => $tableSchema)
	{
		print "<li>";
		print $table."&hellip;";
		$tableStatus = Query("show table status from ".$dbname." like '".$table."'");
		$numRows = NumRows($tableStatus);
		if($numRows == 0)
		{
			print " creating&hellip;";
			$create = "create table `".$table."` (\n";
			$comma = "";
			foreach($tableSchema['fields'] as $field => $type)
			{
				$create .= $comma."\t`".$field."` ".$type;
				$comma = ",\n";
			}
			if(isset($tableSchema['special']))
				$create .= ",\n\t".$tableSchema['special'];
			$create .= "\n) ENGINE=MyISAM;";
			Query($create);
		}
		else
		{
			$primaryKey = "";
			$changes = 0;
			$foundFields = array();
			$scan = Query("show columns from `".$table."`");
			while($field = mysql_fetch_assoc($scan))
			{
				$fieldName = $field['Field'];
				$foundFields[] = $fieldName;
				$type = $field['Type'];
				if($field['Null'] == "NO")
					$type .= " NOT NULL";
				if($field['Extra'] == "auto_increment")
					$type .= " AUTO_INCREMENT";
				else
					$type .= " DEFAULT '".$field['Default']."'";
				if($field['Key'] == "PRI")
					$primaryKey = $fieldName;
				if(array_key_exists($fieldName, $tableSchema['fields']))
				{
					$wantedType = $tableSchema['fields'][$fieldName];
					if(strcasecmp($wantedType, $type))
					{
						print " \"".$fieldName."\" not correct type&hellip;";
						if($fieldName == "id")
						{
							print_r($field);
							print "{ ".$type." }";
						}
						Query("ALTER TABLE `".$table."` CHANGE `".$fieldName."` `".$fieldName."` ".$wantedType);
						$changes++;
					}
				}
			}
			foreach($tableSchema['fields'] as $fieldName => $type)
			{
				if(!in_array($fieldName, $foundFields))
				{
					print " \"".$fieldName."\" missing&hellip;";
					Query("ALTER TABLE `".$table."` ADD `".$fieldName."` ".$type);
					$changes++;
				}
			}
			if($changes == 0)
				print " OK.";
		}
		print "</li>";
	}
}

?>
