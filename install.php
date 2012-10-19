<?php
if (file_exists('lib/database.php'))
{
	header('Location: .');
	exit;
}

require 'lib/links.php';
// Placeholder for actual settings
class Settings {
	function get($constant) {
		return false;
	}
}
// Placeholder for pipe menu
class PipeMenu {
	function __construct($menu) {
		$this->menu = $menu;
	}

	function build() {
		return "<ul class='pipemenu'>$this->menu</ul>";
	}
}
// Placeholder for translation function
function __($string) {
	return $string;
}

//WHY DOES THIS NOT KILL EXECUTION !?
function Kill($string) {
	echo "
		<table class='message outline margin'>
			<tr class=header0><th>Error</th></tr>
			<tr class=cell0><td>$string
		</table>
		";
}
function PoRA($string) {
	return "<div class='PoRT nom'><table class='message outline'>
				<tr class=header0><th>Point Required Attention&trade;
				<tr class=cell0><td>$string
			</table></div>";
}
$loguser = array(
	'fontsize' => 80,
);
$layout_logopic = 'img/logo.png';
$layout_themefile = 'themes/gold/style.css';
$layout_favicon = 'img/favicon.ico';
$layout_title = 'ABXD is being installed';
$layout_views = 'Views: -&infin;';
$layout_time = date('m-d-y H:i:s');
$layout_navigation = new PipeMenu(
	'<li><a href="http://abxd.dirbaio.net/">Support forum</a>' .
	'<li><a href="http://github.com/Dirbaio/ABXD">ABXD 3.0 repository</a>'
);
$layout_userpanel = new PipeMenu(
	'<li><a href="install.php">Install ABXD</a>'
);
$layout_onlineusers = '1 user online';
$layout_bars = "";
$layout_crumbs = "";

$footerExtensionsA = "";
ob_start();
require 'footer.php';
$layout_footer = ob_get_clean();

// Now for real installer!
ob_start();
$errors = array();
// Basic sanity tests
if (!function_exists('version_compare') || version_compare(PHP_VERSION, '5.0.0', '<'))
	$errors[] = 'PHP 5.0.0 required, but you have PHP ' . PHP_VERSION . '.';
if (!function_exists('json_encode'))
	$errors[] = 'As you have PHP older than PHP 5.2.0, you have to install ' .
	            'PECL <a href="http://pecl.php.net/package/json">json</a> extension.';
if (!function_exists('preg_match'))
	$errors[] = 'PCRE extension is required, yet it wasn\'t found.';
if (!class_exists('mysqli'))
	$errors[] = 'MySQL improved wasn\'t found. Recompile PHP with mysqli module.';
if (ini_get('register_globals'))
	$errors[] = 'register_globals is not supported. Continuing may cause your ' .
	            'board to be hacked. Disable it.';
/* This program will only run if the laws of mathematics hold */
if (1 == 0)
	$errors[] = "Oh crap - we are not running in the correct Universe.";


if (isset($_POST['dbhost']))
{
	$layout_pora = PoRA("Something went wrong... visit support forum!");

	$dbserv = $_POST['dbhost'];
	$dbuser = $_POST['dbuser'];
	$dbpass = $_POST['dbpass'];
	$dbname = $_POST['dbname'];
	$dbpref = $_POST['dbpref'];

	if ($dbcfg = @fopen("lib/database.php", "w+"))
	{
		if (!$dblink = @new mysqli($dbserv, $dbuser, $dbpass)) {
			Kill("<p>Couldn't connect SQL server because '$dblink->connect_error'? " .
				"But... I have checked that before...");
		}
		else {
			$dblink->close();

			fwrite($dbcfg, "<?php\n");
			fwrite($dbcfg, "//  AcmlmBoard XD support - Database settings\n");
			fwrite($dbcfg, "//  This file is auto-generated by the installer\n\n");
			fwrite($dbcfg, '$dbserv = ' . var_export($dbserv, true) . ";\n");
			fwrite($dbcfg, '$dbuser = ' . var_export($dbuser, true) . ";\n");
			fwrite($dbcfg, '$dbpass = ' . var_export($dbpass, true) . ";\n");
			fwrite($dbcfg, '$dbname = ' . var_export($dbname, true) . ";\n");
			fwrite($dbcfg, '$dbpref = ' . var_export($dbpref, true) . ";\n");
			fwrite($dbcfg, "\n?>");
			fclose($dbcfg);

			require 'lib/mysql.php';
			require 'lib/mysqlfunctions.php';

			Upgrade();

			$misc = Query("select * from {misc}");
			if(NumRows($misc) == 0)
				Query("INSERT INTO `{misc}` (`views`, `hotcount`, `porabox`, `poratitle`, `milestone`, `maxuserstext`) VALUES (0, 30, '<a href=\"http://github.org/Dirbaio/ABXD\">ABXD repository on GitHub </a><br /><br />Then, <a href=\"editpora.php\">edit this panel</a>.', 'Points of Required Attention', 'Nothing yet.', 'Nobody yet.');");

			Query("UPDATE `{misc}` SET `version` = 300");

			$smilies = Query("select * from {smilies}"); //var_dump(NumRows($smilies));exit;
			if(NumRows($smilies) == 0)
				Import("install/smilies.sql");
			$shakeIt = false;
			if(is_file("lib/salt.php"))
			{
				include("lib/salt.php");
				if(!isset($salt))
					$shakeIt = true;
			}
			else
			{
				$shakeIt = true;
			}

			if($shakeIt)
			{
				$cset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
				$salt = "";
				$chct = strlen($cset) - 1;
				while (strlen($salt) < 16)
					$salt .= $cset[mt_rand(0, $chct)];

				if ($sltf = @fopen("lib/salt.php", "w+"))
				{
					fwrite($sltf, "<?php \$salt = \"".$salt."\" ?>");
					fclose($sltf);
					ob_end_clean();
					header('Location: .');
					die;
				}
				else
					Kill(
						"Could not open \"lib/salt.php\" for writing. <br />
						This has been checked for earlier, so if you see this error now,
						something very strange is going on.");
			}
			else
			{
				ob_end_clean();
				header('Location: .');
				die;
			}
		}
	}
	else
		Kill(
			"Could not open the database configuration file (lib/database.php) for writing.<br />
			Make sure that PHP has access to this file.");
}
elseif ($errors && !isset($_POST['ignore']))
{
	$layout_pora = PoRA("ABXD won't run on your PHP installation.");
	Kill('
		<p>' . implode('<br>', $errors) . '
		<p><a href="?ignore">If you really need to, you can continue, but
		expect ABXD to be broken and broken at once.</a>
	');
}
else
{
	$layout_pora = PoRA("On this screen, you have to configure your database.");
	if (!isset($_POST['dbhost'])) {
		$_POST['dbname'] = $_POST['dbuser'] = null;
		$_POST['dbpref'] = 'abxd_';
		$_POST['dbhost'] = 'localhost';
		$_POST['dbport'] = 3306;
	}
	echo '
	<script>
	function send(array) {
		function status(status) {
			if (!$("#status").length) {
				$("form .cell2").before("<tr class=cell0 id=realstatus><td>Status<td id=status>")
			}
			$("#status").html(status)
		}
		$("[type=submit]").attr("disabled", true)
		var fields = {}
		for (var i = 0; i < array.length; i++) {
			fields[array[i]] = $("[name=" + array[i] + "]").val() || ""
		}
		$.post("install/checksql.php", fields, function (text) {
			status(text)
			if (/<!--ABXD-->/.test(text)) {
				$("[type=submit]").attr("disabled", false)
				$("#realstatus").remove()
			}
		})
	}
	function create() {
		send(["dbhost", "dbname", "dbuser", "dbpass", "dbport", "create"])
	}
	$(function () {
		$("input").change(function () {
			send(["dbhost", "dbname", "dbuser", "dbpass", "dbport"])
		})
		$("[type=submit]").attr("disabled", true)
	})
	</script>
	<form action=?ignore method=post>
		<table class="outline margin width75">
			<tr class=header1>
				<th colspan=2>
					ABXD Installation
			<tr class=cell0>
				<td style="width: 200px"><label for=dbhost>Database host
				<td><input type=text name=dbhost value="', htmlspecialchars($_POST['dbhost']), '" class=width75>
			<tr class=cell1>
				<td><label for=dbname>Database name
				<td><input type=text name=dbname value="', htmlspecialchars($_POST['dbname']), '" class=width75>
			<tr class=cell0>
				<td><label for=dbpref>Database prefix
				<td><input type=text name=dbpref value="', htmlspecialchars($_POST['dbpref']), '" class=width75>
			<tr class=cell1>
				<td><label for=dbuser>Database user
				<td><input type=text name=dbuser value="', htmlspecialchars($_POST['dbuser']), '" class=width75>
			<tr class=cell0>
				<td><label for=dbname>Database password
				<td><input type=password name=dbpass class=width75>
			<tr class=cell1>
				<td><label for=dbuser>Database port
				<td><input type=text pattern="\d+" name=dbport value="', htmlspecialchars($_POST['dbport']), '">
			<tr class=cell2>
				<td><td><input type=submit value="Continue">
		</table>
	</form>
	';
}
$layout_contents = ob_get_clean();
require 'layouts/abxd.php';
