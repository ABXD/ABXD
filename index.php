<?php

$ajaxPage = false;
if(isset($_GET["ajax"]))
	$ajaxPage = true;
	
$boardroot = preg_replace('{/[^/]*$}', '/', $_SERVER['SCRIPT_NAME']);
	
require('lib/common.php');

//TODO: Put this in a proper place.
function getBirthdaysText()
{
	global $dbpref;
	$rBirthdays = Query("select birthday, id, name, displayname, powerlevel, sex from {$dbpref}users where birthday > 0 order by name");
	$birthdays = array();
	while($user = Fetch($rBirthdays))
	{
		$bucket = "userMangler"; include("./lib/pluginloader.php");
		$b = $user['birthday'];
		if(gmdate("m-d", $b) == gmdate("m-d"))
		{
			$y = gmdate("Y") - gmdate("Y", $b);
			$birthdays[] = UserLink($user)." (".$y.")";
		}
	}
	if(count($birthdays))
		$birthdaysToday = implode(", ", $birthdays);
	if($birthdaysToday)
		return "<br>".__("Birthdays today:")." ".$birthdaysToday;
	else
		return "";
}


//=======================
// Do the page

$page = $_GET["page"];
if(!isset($page))
	$page = "index";
if(!ctype_alnum($page))
	$page = "index";

if($page == "index")
{
	if(isset($_GET['fid']) && (int)$_GET['fid'] > 0 && !isset($_GET['action']))
		die(header("Location: ".actionLink("forum", (int)$_GET['fid'])));
	if(isset($_GET['tid']) && (int)$_GET['tid'] > 0)
		die(header("Location: ".actionLink("thread", (int)$_GET['tid'])));
	if(isset($_GET['uid']) && (int)$_GET['uid'] > 0)
		die(header("Location: ".actionLink("profile", (int)$_GET['uid'])));
	if(isset($_GET['pid']) && (int)$_GET['pid'] > 0)
		die(header("Location: ".actionLink("thread", "", "pid=".(int)$_GET['pid']."#".(int)$_GET['pid'])));
}

ob_start();
$layout_crumbs = "";

try {
	try {
		if(array_key_exists($page, $pluginpages))
		{
			$self = $plugins[$pluginpages[$page]];
			$selfsettings = Settings::$pluginsettings[$pluginpages[$page]];
			$page = "./plugins/".$plugins[$pluginpages[$page]]['dir']."/page_".$page.".php";
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
	ob_end_flush();
	die();
}

$layout_contents = ob_get_contents();
ob_end_clean();

//Do this only if it's not an ajax page.
include("lib/views.php");

//=======================
// Panels and footer

ob_start();
require('navigation.php');
$layout_navigation = ob_get_contents();
ob_end_clean();

ob_start();
require('userpanel.php');
$layout_userpanel = ob_get_contents();
ob_end_clean();

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


//=======================
// Board logo and theme

if(file_exists("img/logos/logo_$theme.png"))
	$layout_logopic = resourceLink("img/logos/logo_$theme.png");
else if(file_exists("img/logos/logo_$theme.jpg"))
	$layout_logopic = resourceLink("img/logos/logo_$theme.jpg");
else if(file_exists("img/logos/logo_$theme.gif"))
	$layout_logopic = resourceLink("img/logos/logo_$theme.gif");
else if(file_exists("img/logos/logo.png"))
	$layout_logopic = resourceLink("img/logos/logo.png");
else if(file_exists("img/logos/logo.jpg"))
	$layout_logopic = resourceLink("img/logos/logo.jpg");
else if(file_exists("img/logos/logo.gif"))
	$layout_logopic = resourceLink("img/logos/logo.gif");
else if(file_exists("themes/$theme/logo.png"))
	$layout_logopic = themeResourceLink("logo.png");
else if(file_exists("themes/$theme/logo.jpg"))
	$layout_logopic = themeResourceLink("logo.jpg");
else if(file_exists("themes/$theme/logo.gif"))
	$layout_logopic = themeResourceLink("logo.gif");
else
	$layout_logopic = resourceLink("img/logo.png");

if(file_exists("img/logos/favicon.gif"))
	$layout_favicon = resourceLink("img/logos/favicon.gif");
else if(file_exists("img/logos/favicon.ico"))
	$layout_favicon = resourceLink("img/logos/favicon.ico");
else
	$layout_favicon = resourceLink("img/favicon.ico");

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
			<table class="message">
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
//$layout_contents.="<br>".nl2br(htmlspecialchars($querytext));
require("layouts/$layout.php");


?>

