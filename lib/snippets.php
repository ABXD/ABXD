<?php
//  AcmlmBoard XD support - Handy snippets

function endsWith($a, $b){
	return substr($a, strlen($a) - strlen($b)) == $b;
}

function endsWithIns($a, $b){
	return endsWith(strtolower($a), strtolower($b));
}

function startsWith($a, $b){
	return substr($a, 0, strlen($b)) == $b;
}

function startsWithIns($a, $b){
	return startsWith(strtolower($a), strtolower($b));
}

include_once("write.php");

function OptimizeLayouts($text)
{
	$bucket = array();

	// Save the tags in the temp array and remove them from where they were originally
	$regexps = array("@<style(.*?)</style(.*?)>(\r?\n?)@si", "@<link(.*?)>(\r?\n?)@si", "@<script(.*?)</script(.*?)>(\r?\n?)@si");
	foreach ($regexps as $regexp)
	{
		preg_match_all($regexp, $text, $temp, PREG_PATTERN_ORDER);
		$text = preg_replace($regexp, "", $text);
		$bucket = array_merge($bucket, $temp[0]);
	}

	// Remove duplicates
	$bucket = array_unique($bucket);

	// Put the tags back
	$newStyles = "<!-- head tags -->".implode("", $bucket)."<!-- /head tags -->";
	$text = str_replace("</head>", $newStyles."</head>", $text);
	$text = str_replace("<recaptcha", "<script", $text);
	return $text;
}


function GetRainbowColor()
{
	$stime = gettimeofday();
	$h = (($stime[usec] / 5) % 600);
	if($h < 100)
	{
		$r = 255;
		$g = 155 + $h;
		$b = 155;
	}
	else if($h < 200)
	{
		$r = 255 - $h + 100;
		$g = 255;
		$b = 155;
	}
	else if($h < 300)
	{
		$r = 155;
		$g = 255;
		$b = 155 + $h - 200;
	}
	else if($h < 400)
	{
		$r = 155;
		$g = 255 - $h + 300;
		$b = 255;
	}
	else if($h < 500)
	{
		$r = 155 + $h - 400;
		$g = 155;
		$b = 255;
	}
	else
	{
		$r = 255;
		$g = 155;
		$b = 255 - $h + 500;
	}
	return substr(dechex($r * 65536 + $g * 256 + $b), -6);
}



function TimeUnits($sec)
{
	if($sec <    60) return "$sec sec.";
	if($sec <  3600) return floor($sec/60)." min.";
	if($sec < 86400) return floor($sec/3600)." hour".($sec >= 7200 ? "s" : "");
	return floor($sec/86400)." day".($sec >= 172800 ? "s" : "");
}

function DoPrivateMessageBar()
{
	global $loguserid, $loguser;

	if($loguserid)
	{
		$unread = FetchResult("select count(*) from {pmsgs} where userto = {0} and msgread=0 and drafting=0", $loguserid);
		$content = "";
		if($unread)
		{
			$pmNotice = $loguser['usebanners'] ? "id=\"pmNotice\" " : "";
			$rLast = Query("select * from {pmsgs} where userto = {0} and msgread=0 order by date desc limit 0,1", $loguserid);
			$last = Fetch($rLast);
			$rUser = Query("select * from {users} where id = {0}", $last['userfrom']);
			$user = Fetch($rUser);
			$content .= format(
"
		".__("You have {0}{1}. {2}Last message{1} from {3} on {4}."),
			Plural($unread, format(__("new {0}private message"), "<a href=\"".actionLink("private")."\">")),
			"</a>",
			"<a href=\"".actionLink("showprivate", $last['id'])."\">",
			UserLink($user), formatdate($last['date']));
		}

		if($loguser['newcomments'])
		{
			$content .= format(
"
		".__("You {0} have new comments in your {1}profile{2}."),
			$content != "" ? "also" : "",
			"<a href=\"".actionLink("profile", $loguserid)."\">",
			"</a>");
		}

		if($content)
			write(
"
	<div {0} class=\"outline margin header0 cell0 smallFonts\">
		{1}
	</div>
", $pmNotice, $content);
	}
}

function DoSmileyBar($taname = "text")
{
	global $smiliesOrdered;
	$expandAt = 100;
	LoadSmiliesOrdered();
	print '<table class="message margin">
		<tr class="header0"><th>'.__("Smilies").'</th></tr>
		<tr class="cell0"><td id="smiliesContainer">';

	if(count($smiliesOrdered) > $expandAt)
		write("<button class=\"expander\" id=\"smiliesExpand\" onclick=\"expandSmilies();\">&#x25BC;</button>");
	print "<div class=\"smilies\" id=\"commonSet\">";

	$i = 0;
	foreach($smiliesOrdered as $s)
	{
		if($i == $expandAt)
			print "</div><div class=\"smilies\" id=\"expandedSet\">";
		print "<img src=\"".resourceLink("img/smilies/".$s['image'])."\" alt=\"".htmlentities($s['code'])."\" title=\"".htmlentities($s['code'])."\" onclick=\"insertSmiley(' ".str_replace("'", "\'", $s['code'])." ');\" />";
		$i++;
	}

	print '</div></td></tr></table>';
}

function DoPostHelp()
{
	global $pathLib;

	write("
	<table class=\"message margin\">
		<tr class=\"header0\"><th>".__("Post help")."</th></tr>
		<tr class=\"cell0\"><td>
			<button class=\"expander\" id=\"postHelpExpand\" onclick=\"expandPostHelp();\">&#x25BC;</button>
			<div id=\"commonHelp\" class=\"left\">
				<h4>".__("Presentation")."</h4>
				[b]&hellip;[/b] &mdash; <strong>".__("bold type")."</strong> <br />
				[i]&hellip;[/i] &mdash; <em>".__("italic")."</em> <br />
				[u]&hellip;[/u] &mdash; <span class=\"underline\">".__("underlined")."</span> <br />
				[s]&hellip;[/s] &mdash; <del>".__("strikethrough")."</del><br />
			</div>
			<div id=\"expandedHelp\" class=\"left\">
				[code]&hellip;[/code] &mdash; <code>".__("code block")."</code> <br />
				[spoiler]&hellip;[/spoiler] &mdash; ".__("spoiler block")." <br />
				[spoiler=&hellip;]&hellip;[/spoiler] <br />
				[source]&hellip;[/source] &mdash; ".__("colorcoded block, assuming C#")." <br />
				[source=&hellip;]&hellip;[/source] &mdash; ".__("colorcoded block, specific language")."<sup title=\"bnf, c, cpp, csharp, html4strict, irc, javascript, lolcode, lua, mysql, php, qbasic, vbnet, xml\">[".__("which?")."]</sup> <br />
	");
	$bucket = "postHelpPresentation"; include($pathLib . "/pluginloader.php");
	write("
				<br />
				<h4>".__("Links")."</h4>
				[img]http://&hellip;[/img] &mdash; ".__("insert image")." <br />
				[url]http://&hellip;[/url] <br />
				[url=http://&hellip;]&hellip;[/url] <br />
				>>&hellip; &mdash; ".__("link to post by ID")." <br />
				[user=##] &mdash; ".__("link to user's profile by ID")." <br />
	");
	$bucket = "postHelpLinks"; include($pathLib . "/pluginloader.php");
	write("
				<br />
				<h4>".__("Quotations")."</h4>
				[quote]&hellip;[/quote] &mdash; ".__("untitled quote")."<br />
				[quote=&hellip;]&hellip;[/quote] &mdash; ".__("\"Posted by &hellip;\"")." <br />
				[quote=\"&hellip;\" id=\"&hellip;\"]&hellip;[/quote] &mdash; \"".__("\"Post by &hellip;\" with link by post ID")." <br />
	");
	$bucket = "postHelpQuotations"; include($pathLib . "/pluginloader.php");
	write("
				<br />
				<h4>".__("Embeds")."</h4>
	");
	$bucket = "postHelpEmbeds"; include($pathLib . "/pluginloader.php");
	write("
			</div>
			<br />
			".__("Most plain HTML also allowed.")."
		</td></tr>
	</table>
	");
}



function RecalculateKarma($uid)
{
	$karma = 100;
	$karmaWeights = array(5, 10, 10, 15, 15);
	$rKarma = Query("select powerlevel, up from {uservotes} left join {users} on id=voter where uid={0} and powerlevel > -1", $uid);
	while($k = Fetch($rKarma))
	{
		if($k['up'])
			$karma += $karmaWeights[$k['powerlevel']];
		else
			$karma -= $karmaWeights[$k['powerlevel']];
	}
	Query("update {users} set karma={0} where id={1}", $karma, $uid);
	return $karma;
}


function cdate($format, $date = 0)
{
	global $loguser;
	if($date == 0)
		$date = time();
	$hours = (int)($loguser['timezone']/3600);
	$minutes = floor(abs($loguser['timezone']/60)%60);
	$plusOrMinus = $hours < 0 ? "" : "+";
	$timeOffset = $plusOrMinus.$hours." hours, ".$minutes." minutes";
	return gmdate($format, strtotime($timeOffset, $date));
}

function Report($stuff, $hidden = 0, $severity = 0)
{
	//legacy function that should be removed.
}


//TODO: This is used for notifications. We should replace this with the coming-soon notifications system ~Dirbaio
function SendSystemPM($to, $message, $title)
{
	global $systemUser;

	//Don't send system PMs if no System user was set
	if($systemUser == 0)
		return;

	$rPM = Query("insert into {pmsgs} (userto, userfrom, date, ip, msgread) values ({0}, {1}, {2}, '127.0.0.1', 0)", $to, $systemUser, time());
	$pid = InsertId();
	$rPM = Query("insert into {pmsgs_text} (pid, text, title) values ({0}, {1}, {2})", $pid, $message, $title);

	//print "PM sent.";
}

function Shake()
{
	$cset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
	$salt = "";
	$chct = strlen($cset) - 1;
	while (strlen($salt) < 16)
		$salt .= $cset[mt_rand(0, $chct)];
	return $salt;
}

function IniValToBytes($val)
{
	$val = trim($val);
	$last = strtolower($val[strlen($val)-1]);
	switch($last)
	{
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}

	return $val;
}

function BytesToSize($size, $retstring = '%01.2f&nbsp;%s')
{
	$sizes = array('B', 'KiB', 'MiB');
	$lastsizestring = end($sizes);
	foreach($sizes as $sizestring)
	{
		if($size < 1024)
			break;
		if($sizestring != $lastsizestring)
			$size /= 1024;
	}
	if($sizestring == $sizes[0])
		$retstring = '%01d %s'; // Bytes aren't normally fractional
	return sprintf($retstring, $size, $sizestring);
}

function makeThemeArrays()
{
	global $themes, $themefiles, $installationPath;
	$themes = array();
	$themefiles = array();
	$dir = @opendir("themes");
	while ($file = readdir($dir))
	{
		if ($file != "." && $file != "..")
		{
			$themefiles[] = $file;
			$name = explode("\n", @file_get_contents($installationPath . "/themes/".$file."/themeinfo.txt"));
			$themes[] = trim($name[0]);
		}
	}
	closedir($dir);
}

function getdateformat()
{
	global $loguserid, $loguser;

	if($loguserid)
		return $loguser['dateformat'].", ".$loguser['timeformat'];
	else
		return Settings::get("dateformat");
}

function formatdate($date)
{
	return cdate(getdateformat(), $date);
}
function formatdatenow()
{
	return cdate(getdateformat());
}

function formatBirthday($b)
{
	return format("{0} ({1} old)", cdate("F j, Y", $b), Plural(floor((time() - $b) / 86400 / 365.2425), "year"));
}
function getPowerlevelName($pl) {
	$powerlevels = array(
		-1 => __("Banned"),
		0 => __("Normal"),
		1 => __("Local mod"),
		2 => __("Full mod"),
		3 => __("Admin"),
		4 => __("Root"),
		5 => __("System")
	);
	return $powerlevels[$pl];
}

function getSexName($sex) {
	$sexes = array(
		0 => __("Male"),
		1 => __("Female"),
		2 => __("N/A"),
	);

	return $sexes[$sex];
}

//TODO Add caching if it's too slow.
function formatIP($ip)
{
	global $loguser;

	$res = $ip;
	$res .=  " " . IP2C($ip);
	if($loguser["powerlevel"] >= 3)
		return actionLinkTag($res, "ipquery", $ip);
	else
		return $res;
}

function ip2long_better($ip)
{
	$v = explode('.', $ip);
	return ($v[0]*16777216)+($v[1]*65536)+($v[2]*256)+$v[3];
}

//TODO: Optimize it so that it can be made with a join in online.php and other places.
function IP2C($ip)
{
	global $dblink;
	//This nonsense is because ips can be greater than 2^31, which will be interpreted as negative numbers by PHP.
	$ipl = ip2long($ip);
	$r = Fetch(Query("SELECT *
				 FROM {ip2c}
				 WHERE ip_from <= {0s}
				 ORDER BY ip_from DESC
				 LIMIT 1",
				 sprintf("%u", $ipl)));

	if($r && $r["ip_to"] >= ip2long_better($ip))
		return " <img src=\"".resourceLink("img/flags/".strtolower($r['cc']).".png")."\" alt=\"".$r['cc']."\" title=\"".$r['cc']."\" />";
	else
		return "";
}

?>
