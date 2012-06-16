<?php
//  AcmlmBoard XD support - Handy snippets
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

function usectime()
{
	$t = gettimeofday();
	return $t['sec'] + ($t['usec'] / 1000000);
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
	global $loguserid, $loguser, $dbpref;

	if($loguserid)
	{
		$qUnread = "select count(*) from {$dbpref}pmsgs where userto = ".$loguserid." and msgread=0 and drafting=0";
		$unread= FetchResult($qUnread);
		$content = "";
		if($unread)
		{
			$pmNotice = $loguser['usebanners'] ? "id=\"pmNotice\" " : "";
			$qLast = "select * from {$dbpref}pmsgs where userto = ".$loguserid." and msgread=0 order by date desc limit 0,1";
			$rLast = Query($qLast);
			$last = Fetch($rLast);
			$qUser = "select * from {$dbpref}users where id = ".$last['userfrom'];
			$rUser = Query($qUser);
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
	$expandAt = 26;
	LoadSmilies(TRUE);
	

	print '<table class="message margin">
		<tr class="header0"><th>'.__("Smilies").'</th></tr>
		<tr class="cell0"><td id=\"smiliesContainer\">';
	
	if(count($smiliesOrdered) > $expandAt)
		write("<button class=\"expander\" id=\"smiliesExpand\" onclick=\"expandSmilies();\">&#x25BC;</button>"); 
	print "<div class=\"smilies\" id=\"commonSet\">";
	for($i = 0; $i < count($smiliesOrdered) - 1; $i++)
	{
		if($i == $expandAt)
			print "</div><div class=\"smilies\" id=\"expandedSet\">";
		$s = $smiliesOrdered[$i];
		print "<img src=\"img/smilies/".$s['image']."\" alt=\"".htmlentities($s['code'])."\" title=\"".htmlentities($s['code'])."\" onclick=\"insertSmiley(' ".str_replace("'", "\'", $s['code'])." ');\" />";
	}

	print '</div></td></tr></table>';
}

function DoPostHelp()
{
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
	$bucket = "postHelpPresentation"; include("./lib/pluginloader.php");
	write("
				<br />
				<h4>".__("Links")."</h4>
				[img]http://&hellip;[/img] &mdash; ".__("insert image")." <br />
				[url]http://&hellip;[/url] <br />
				[url=http://&hellip;]&hellip;[/url] <br />
				>>&hellip; &mdash; ".__("link to post by ID")." <br />
				[user=##] &mdash; ".__("link to user's profile by ID")." <br />
	");
	$bucket = "postHelpLinks"; include("./lib/pluginloader.php");
	write("
				<br />
				<h4>".__("Quotations")."</h4>
				[quote]&hellip;[/quote] &mdash; ".__("untitled quote")."<br />
				[quote=&hellip;]&hellip;[/quote] &mdash; ".__("\"Posted by &hellip;\"")." <br />
				[quote=\"&hellip;\" id=\"&hellip;\"]&hellip;[/quote] &mdash; \"".__("\"Post by &hellip;\" with link by post ID")." <br />
	");
	$bucket = "postHelpQuotations"; include("./lib/pluginloader.php");
	write("
				<br />
				<h4>".__("Embeds")."</h4>
	");
	$bucket = "postHelpEmbeds"; include("./lib/pluginloader.php");
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
	global $dbpref;
	$karma = 100;
	$karmaWeights = array(5, 10, 10, 15, 15);
	$qKarma = "select powerlevel, up from {$dbpref}uservotes left join {$dbpref}users on id=voter where uid=".$uid." and powerlevel > -1";
	$rKarma = Query($qKarma);
	while($k = Fetch($rKarma))
	{
		if($k['up'])
			$karma += $karmaWeights[$k['powerlevel']];
		else
			$karma -= $karmaWeights[$k['powerlevel']];
	}
	Query("update {$dbpref}users set karma=".$karma." where id=".$uid);
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
	global $dbpref;
	$full = GetFullURL();
	$here = substr($full, 0, strrpos($full, "/"))."/";
	
	if ($severity == 2)
		$req = "'".justEscape(base64_encode(serialize($_REQUEST)))."'";
	else
		$req = 'NULL';
	
	Query("insert into {$dbpref}reports (ip,user,time,text,hidden,severity,request) 
		values ('".$_SERVER['REMOTE_ADDR']."', ".(int)$loguserid.", ".time().", '".justEscape(str_replace("#HERE#", $here, $stuff))."', ".$hidden.", ".$severity.", ".$req.")");
	Query("delete from {$dbpref}reports where time < ".(time() - (60*60*24*30)));
}

//TODO: This is used for notifications. We should replace this with the coming-soon notifications system ~Dirbaio
function SendSystemPM($to, $message, $title)
{
	global $systemUser, $dbpref;
	
	//Don't send system PMs if no System user was set
	if($systemUser == 0)
		return;

	$qPM = "insert into {$dbpref}pmsgs (userto, userfrom, date, ip, msgread) values (".$to.", ".$systemUser.", ".time().", '127.0.0.1', 0)";
	$rPM = Query($qPM);
	$pid = InsertId();
	$qPM = "insert into {$dbpref}pmsgs_text (pid, text, title) values (".$pid.", '".justEscape($message)."', '".justEscape($title)."')";
	$rPM = Query($qPM);
	
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
	global $themes, $themefiles;
	$themes = array();
	$themefiles = array();
	$dir = @opendir("themes");
	while ($file = readdir($dir))
	{
		if ($file != "." && $file != "..")
		{
			$themefiles[] = $file;
			$name = explode("\n", @file_get_contents("./themes/".$file."/themeinfo.txt"));
			$themes[] = trim($name[0]);
		}
	}
	closedir($dir);
}

function getUserKey()
{
	return hash('sha256', "{$loguserid},{$loguser['pss']},{$salt}");
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


function endsWith($haystack, $needle)
{
	$length = strlen($needle);
	if ($length == 0)
	{
		return true;
	}

	$start  = $length * -1; //negative
	return (substr($haystack, $start) === $needle);
}
?>
