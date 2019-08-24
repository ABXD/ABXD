<?php
// Protect from <iframe> password steal hack
header('X-Frame-Options: DENY');

$ajaxPage = false;
if(isset($_GET["ajax"]))
	$ajaxPage = true;

require('lib/common.php');

//TODO: Rewrite since it was using old methods.
//Also it's unused by the board other than that variable below
function getBirthdaysText()
{
	return;
}

//Use buffering to draw the page. 
//Useful to have it disabled when running from the terminal.
$useBuffering = true;

//Support for running pages from the terminal.
if(isset($argv))
{
	$_GET = array();
	$_GET["page"] = $argv[1];
	
	$_SERVER = array();
	$_SERVER["REMOTE_ADDR"] = "0.0.0.0";
	
	$ajaxPage = true;
	$useBuffering = false;
}


//=======================
// Do the page
if (isset($_GET['page']))
	$page = $_GET["page"];
else
	$page = $mainPage;
if(!ctype_alnum($page))
	$page = $mainPage;

if($page == $mainPage)
{
	if(isset($_GET['fid']) && (int)$_GET['fid'] > 0 && !isset($_GET['action']))
		die(header("Location: ".actionLink("forum", (int)$_GET['fid'])));
	if(isset($_GET['tid']) && (int)$_GET['tid'] > 0)
		die(header("Location: ".actionLink("thread", (int)$_GET['tid'])));
	if(isset($_GET['uid']) && (int)$_GET['uid'] > 0)
		die(header("Location: ".actionLink("profile", (int)$_GET['uid'])));
	if(isset($_GET['pid']) && (int)$_GET['pid'] > 0)
		die(header("Location: ".actionLink("post", (int)$_GET['pid'])));
}

define('CURRENT_PAGE', $page);

if($useBuffering)
	ob_start();

$layout_crumbs = new PipeMenu();
$layout_links = new PipeMenu();

try {
	try {
		if(array_key_exists($page, $pluginpages))
		{
			$plugin = $pluginpages[$page];
			$self = $plugins[$plugin];

			$page = "./plugins/".$self['dir']."/page_".$page.".php";
			if(!file_exists($page))
				throw new Exception(404);
			include($page);
			unset($self);
		}
		else {
			$page = 'pages/'.$page.'.php';
			if(!file_exists($page))
				throw new Exception(404);
			include($page);
		}
	}
	catch(Exception $e)
	{
		if ($e->getMessage() != 404)
		{
			throw $e;
		}
		require('pages/404.php');
	}
}
catch(KillException $e)
{
	// Nothing. Just ignore this exception.
}

if($ajaxPage)
{
	
	if($useBuffering)
	{
		header("Content-Type: text/plain");
		ob_end_flush();
	}
		
	die();
}

$layout_contents = ob_get_contents();
ob_end_clean();

//Do these things only if it's not an ajax page.
include("lib/views.php");
setLastActivity();

//=======================
// Panels and footer

require('navigation.php');
require('userpanel.php');

ob_start();
require('footer.php');
$layout_footer = ob_get_contents();
ob_end_clean();


//=======================
// Notification bars

ob_start();

$bucket = "userBar"; include("./lib/pluginloader.php");
/*
if($rssBar)
{
	write("
	<div style=\"float: left; width: {1}px;\">&nbsp;</div>
	<div id=\"rss\">
		{0}
	</div>
", $rssBar, $rssWidth + 4);
}*/
DoPrivateMessageBar();
$bucket = "topBar"; include("./lib/pluginloader.php");
$layout_bars = ob_get_contents();
ob_end_clean();


//=======================
// Misc stuff

$layout_time = formatdatenow();
$layout_onlineusers = getOnlineUsersText();
$layout_birthdays = getBirthdaysText();
$layout_views = __("Views:")." ".'<span id="viewCount">'.number_format($misc['views']).'</span>';

$layout_title = htmlspecialchars(Settings::get("boardname"));
if($title != "")
	$layout_title .= " &raquo; ".$title;

$layout_logotitle = Settings::get("boardname");

//=======================
// Board logo and theme

function checkForImage(&$image, $external, $file)
{
	global $dataDir, $dataUrl;

	if($image) return;

	if($external)
	{
		if(file_exists($dataDir.$file))
			$image = $dataUrl.$file;
	}
	else
	{
		if(file_exists($file))
			$image = resourceLink($file);
	}
}

if (!file_exists("themes/$theme")) { //Are we using some invalid theme?
	if ($loguserid) {
		$defaultTheme = Settings::get("defaultTheme");
		query("update users set theme='" . $defaultTheme . "' where id=$loguserid");
		$theme = $defaultTheme;
	}
}


checkForImage($layout_logopic, true, "logos/logo_$theme.png");
checkForImage($layout_logopic, true, "logos/logo_$theme.jpg");
checkForImage($layout_logopic, true, "logos/logo_$theme.gif");
checkForImage($layout_logopic, true, "logos/logo.png");
checkForImage($layout_logopic, true, "logos/logo.jpg");
checkForImage($layout_logopic, true, "logos/logo.gif");
checkForImage($layout_logopic, false, "themes/$theme/logo.png");
checkForImage($layout_logopic, false, "themes/$theme/logo.jpg");
checkForImage($layout_logopic, false, "themes/$theme/logo.gif");
checkForImage($layout_logopic, false, "img/logo.png");

checkForImage($layout_favicon, true, "logos/favicon.gif");
checkForImage($layout_favicon, true, "logos/favicon.ico");
checkForImage($layout_favicon, false, "img/favicon.ico");

$layout_themefile = "themes/$theme/style.css";
if(!file_exists($layout_themefile))
	$layout_themefile = "themes/$theme/style.php";

$layout_contents = "<div id=\"page_contents\">$layout_contents</div>";
//=======================
// PoRA box

if(Settings::get("showPoRA"))
{
	$layout_pora = '
		<div class="PoRT nom">
			<table class="message outline">
				<tr class="header0"><th>'.Settings::get("PoRATitle").'</th></tr>
				<tr class="cell0"><td>'.Settings::get("PoRAText").'</td></tr>
			</table>
		</div>';
}
else
	$layout_pora = "";

//=======================
// Print everything!

$layout = Settings::get("defaultLayout");

if($debugQueries)
	$layout_contents.="<table class=\"outline margin width100\"><tr class=header0><th colspan=4>List of queries
	                   <tr class=header1><th>Query<th>Backtrace$querytext</table>";

if($mobileLayout)
	$layout = "mobile";
if(!file_exists("layouts/$layout/layout.php"))
	$layout = "abxd";
require("layouts/$layout/layout.php"); echo (isset($times) ? $times : "");

$bucket = "finish"; include('lib/pluginloader.php');

?>

