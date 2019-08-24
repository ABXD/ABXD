<?php

$bbcodeCallbacks = array(
	"b" => "bbcodeBold",
	"i" => "bbcodeItalics",
	"u" => "bbcodeUnderline",
	"s" => "bbcodeStrikethrough",

	"url" => "bbcodeURL",
	"img" => "bbcodeImage",
	"imgs" => "bbcodeImageScale",

	"user" => "bbcodeUser",
	"thread" => "bbcodeThread",
	"forum" => "bbcodeForum",

	"quote" => "bbcodeQuote",
	"reply" => "bbcodeReply",

	"spoiler" => "bbcodeSpoiler",
	"code" => "bbcodeCode",
	"source" => "bbcodeCode",

	"table" => "bbcodeTable",
	"tr" => "bbcodeTableRow",
	"trh" => "bbcodeTableRowHeader",
	"td" => "bbcodeTableCell",
);

//Allow plugins to register their own callbacks (new bbcode tags)
$bucket = "bbcode"; include("pluginloader.php");

function bbcodeBold($contents){
	return "<strong>$contents</strong>";
}
function bbcodeItalics($contents){
	return "<em>$contents</em>";
}
function bbcodeUnderline($contents){
	return "<u>$contents</u>";
}
function bbcodeStrikethrough($contents){
	return "<del>$contents</del>";
}

function bbcodeURL($contents, $arg)
{
	$dest = htmlspecialchars($contents);
	$title = htmlspecialchars($contents);

	if($arg)
		$dest = htmlspecialchars($arg);

	return '<a href="'.$dest.'">'.$title.'</a>';
}

function bbcodeURLAuto($match)
{
	$text = $match[0];
	$text = html_entity_decode($text);
	return '<a href="'.htmlspecialchars($text).'">'.htmlspecialchars($text).'</a>';
}

function bbcodeImage($contents, $arg)
{
	$dest = $contents;
	$title = "";
	if($arg)
	{
		$title = $contents;
		$dest = $arg;
	}

	return '<img class="imgtag" src="'.htmlspecialchars($dest).'" alt="'.htmlspecialchars($title).'"/>';
}


function bbcodeImageScale($contents, $arg)
{
	$dest = $contents;
	$title = "";
	if($arg)
	{
		$title = $contents;
		$dest = $arg;
	}

	return '<a href="'.htmlspecialchars($dest).'"><img class="imgtag" style="max-width:300px; max-height:300px;" src="'.htmlspecialchars($dest).'" alt="'.htmlspecialchars($title).'"/></a>';
}


function bbcodeUser($contents, $arg)
{
	return UserLinkById((int)$arg);
}

function bbcodeThread($contents, $arg)
{
	global $threadLinkCache, $loguser;
	$id = (int)$arg;
	if(!isset($threadLinkCache[$id]))
	{
		$rThread = Query("SELECT
							t.id, t.title
						FROM {threads} t 
						LEFT JOIN {forums} f ON t.forum = f.id
						WHERE t.id={0} AND f.minpower <= {1} ", $id, $loguser["powerlevel"]);
		if(NumRows($rThread))
		{
			$thread = Fetch($rThread);
			$threadLinkCache[$id] = makeThreadLink($thread);
		}
		else
			$threadLinkCache[$id] = "&lt;invalid thread ID&gt;";
	}
	return $threadLinkCache[$id];
}

function bbcodeForum($contents, $arg)
{
	global $forumLinkCache, $loguser;
	$id = (int)$arg;
	if(!isset($forumLinkCache[$id]))
	{
		$rForum = Query("SELECT 
							id, title 
						FROM {forums}
						WHERE id={0} and minpower <= {1}", $id, $loguser["powerlevel"]);
		if(NumRows($rForum))
		{
			$forum = Fetch($rForum);
			$forumLinkCache[$id] = actionLinkTag($forum['title'], "forum", $forum['id']);
		}
		else
			$forumLinkCache[$id] = "&lt;invalid forum ID&gt;";
	}
	return $forumLinkCache[$id];
}

function bbcodeQuote($contents, $arg)
{
	return bbcodeQuoteGeneric($contents, $arg, __("Posted by"));
}

function bbcodeReply($contents, $arg)
{
	return bbcodeQuoteGeneric($contents, $arg, __("Sent by"));
}

function bbcodeQuoteGeneric($contents, $arg, $text)
{
	if(!$arg)
		return "<div class='quote'><div class='quotecontent'>$contents</div></div>";

	// Possible formats:
	// [quote=blah]
	// [quote="blah blah" id="123"]

	if(preg_match('/"(.*)" id="(.*)"/', $arg, $match))
	{
		$who = htmlspecialchars($match[1]);
		$id = (int) $match[2];
		return "<div class='quote'><div class='quoteheader'>$text <a href=\"".actionLink("post", $id)."\">$who</a></div><div class='quotecontent'>$contents</div></div>";
	}
	else
	{
		$who = htmlspecialchars($arg);
		return "<div class='quote'><div class='quoteheader'>$text $who</div><div class='quotecontent'>$contents</div></div>";
	}
}

function bbcodeSpoiler($contents, $arg)
{
	if($arg)
		return "<div class=\"spoiler\"><button class=\"spoilerbutton named\">".htmlspecialchars($arg)."</button><div class=\"spoiled hidden\">$contents</div></div>";
	else
		return "<div class=\"spoiler\"><button class=\"spoilerbutton\">Show spoiler</button><div class=\"spoiled hidden\">$contents</div></div>";
}

function bbcodeCode($contents, $arg)
{
	return '<div class="codeblock">'.htmlentities($contents).'</div>';
}

function bbcodeTable($contents, $arg)
{
	return "<table class=\"outline margin\">$contents</table>";
}

function bbcodeTableCell($contents, $arg)
{
	global $bbcodeIsTableHeader;

	//I think this is not working as intended?
	$contents = trimbr($contents);

	if($bbcodeIsTableHeader)
		return "<th>$contents</th>";
	else
		return "<td>$contents</td>";
}

$bbcodeCellClass = 0;

function bbcodeTableRow($contents, $arg)
{
	global $bbcodeCellClass;
	$bbcodeCellClass++;
	$bbcodeCellClass %= 2;

	return "<tr class=\"cell$bbcodeCellClass\">$contents</tr>";
}

function bbcodeTableRowHeader($contents, $arg)
{
	global $bbcodeCellClass;
	$bbcodeCellClass++;
	$bbcodeCellClass %= 2;

	return "<tr class=\"header0\">$contents</tr>";
}

function trimbr($string)
{
	$string = trim($string);
	$string = preg_replace('/^(?:<br\s*\/?>\s*)+/', '', $string);
	$string = preg_replace('/(?:<br\s*\/?>\s*)+$/', '', $string);
	$string = trim($string);
	return $string;
}
