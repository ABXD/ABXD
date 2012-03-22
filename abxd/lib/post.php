<?php
//  AcmlmBoard XD support - Post functions

include_once("geshi.php");
include_once("write.php");


function ParseThreadTags(&$title)
{
	preg_match_all("/\[(.*?)\]/", $title, $matches);
	foreach($matches[1] as $tag)
	{
		$title = str_replace("[".$tag."]", "", $title);
		$tag = htmlentities(strip_tags(strtolower($tag)));
		
		//Start at a hue that makes "18" red.
		$hash = -105;
		for($i = 0; $i < strlen($tag); $i++)
			$hash += ord($tag[$i]);

		//That multiplier is only there to make "nsfw" and "18" the same color.
		$color = "hsl(".(($hash * 57) % 360).", 70%, 40%)";
		
		$tags .= "<span class=\"threadTag\" style=\"background-color: ".$color.";\">".$tag."</span>";
	}
	if($tags)
		$tags = " ".$tags;
	return $tags;
}

//Simple version -- may expand later.
function CheckTableBreaks($text)
{
	$text = strtolower(CleanUpPost($text));
	$tabO = substr_count($text, "<table");
	$tabC = substr_count($text, "</table>");
	$divO = substr_count($text, "<div");
	$divC = substr_count($text, "</div>");
	$quoO = substr_count($text, "[quote");
	$quoC = substr_count($text, "[/quote]");
	$spoO = substr_count($text, "[spoiler");
	$spoC = substr_count($text, "[/spoiler]");
	if($tabO != $tabC) return true;
	if($divO != $divC) return true;
	if($quoO != $quoC) return true;
	if($spoO != $spoC) return true;
	return false;
}

function filterPollColors($input)
{
	return preg_replace("@[^#0123456789abcdef]@si", "", $input);
}

function LoadSmilies($byOrder = FALSE)
{
	global $smilies, $smiliesOrdered;
	$smiliesR = array
	(
		')' => '\)',
		'(' => '\(',
		'/' => '\/',
		'+' => '\+',
		'|' => '\|',
		'^' => '\^',
		'?' => '\?',
		'[' => '\[',
		']' => '\]',
		'<' => '\<',
		'>' => '\>',
		':' => '\:',
		']' => '\]',
		'.' => '\.',
		'\'' => '\\\'',
	);
	if($byOrder)
	{
		if(isset($smiliesOrdered))
			return;
		$rSmilies = Query("select * from smilies order by id asc");
		$smiliesOrdered = array();
		while($smiley = Fetch($rSmilies))
		{
			//foreach ($smiliesR as $old => $new)
			//	$smiley['code'] = str_replace($old, $new, $smiley['code']);
			$smiliesOrdered[] = $smiley;
		}
	}
	else
	{
		if(isset($smilies))
			return;
		$rSmilies = Query("select * from smilies order by length(code) desc");
		$smilies = array();
		while($smiley = Fetch($rSmilies))
		{
			$smilies[] = $smiley;
		}
	}
}

function ApplySmilies($text)
{
	global $smilies;
	foreach($smilies as $s)
	{
		$text = preg_replace("/\b(".$s['code'].")\b/si", "<img src=\"img/smilies/".htmlentities($s['image'])."\" />", $text);
	}
	return $text;
}

function LoadBlocklayouts()
{
	global $blocklayouts, $loguserid;
	if(isset($blocklayouts))
		return;
	$rBlocks = Query("select * from blockedlayouts where blockee = ".$loguserid);
	while($block = Fetch($rBlocks))
		$blocklayouts[$block['user']] = 1;
	//$qBlock = "select * from blockedlayouts where user=".$post['uid']." and blockee=".$loguserid;
	//$rBlock = Query($qBlock);

}

function LoadRanks($rankset)
{
	global $ranks;	
	if(isset($ranks[$rankset]))
		return;
	$ranks[$poster['rankset']] = array();
	$rRanks = Query("select * from ranks where rset=".$rankset." order by num");
	while($rank = Fetch($rRanks))
		$ranks[$rankset][$rank['num']] = $rank['text'];
}

function GetRank($poster)
{
	global $ranks;
	if($poster['rankset'] == 0)
		return "";
	LoadRanks($poster['rankset']);
	$thisSet = $ranks[$poster['rankset']];
	if(!is_array($thisSet))
		return "";
	$ret = "";
	foreach($thisSet as $num => $text)
	{
		if($num > $poster['posts'])
			return $ret;
		$ret = $text;
	}
}

function GetToNextRank($poster)
{
	global $ranks;
	if($poster['rankset'] == 0)
		return "";
	LoadRanks($poster['rankset']);
	$thisSet = $ranks[$poster['rankset']];
	if(!is_array($thisSet))
		return 0;
	$ret = 0;
	foreach($thisSet as $num => $text)
	{
		$ret = $num - $poster['posts'];
		if($num > $poster['posts'])
			return $ret;
	}	
}

function GeshiCallback($matches)
{
	$geshi = new GeSHi(trim($matches[1]), "csharp", null);
	$geshi->set_header_type(GESHI_HEADER_NONE);
	$geshi->enable_classes();
	return format("<div class=\"codeblock geshi\">{0}</div>", str_replace("\n", "", $geshi->parse_code()));
}

function GeshiCallbackL($matches)
{
	$geshi = new GeSHi(trim($matches[2]), $matches[1], null);
	$geshi->set_header_type(GESHI_HEADER_NONE);
	$geshi->enable_classes();
	return format("<div class=\"codeblock geshi\">{0}</div>", str_replace("\n", "", $geshi->parse_code()));
}

function MakeUserAtLink($matches)
{
	global $members;
	$username = $matches[1];
	foreach($members as $id => $data)
	{
		if($data['name'] == $username)
		{
			return UserLink($members[$data['id']]);
		}
	}
	//Didn't find it in the cache.
	$rUser = Query("select id, name, displayname, powerlevel, sex from users where name='".$username."' or displayname='".$username."'");
	if(NumRows($rUser))
	{
		$hit = Fetch($rUser);
		$members[$hit['id']] = $hit;
		return UserLink($hit);
	}
	else
		return $username; //Return the actual name attempted.
}

function MakeUserLink($matches)
{
	global $members;
	$id = (int)$matches[1];
	if(!isset($members[$id]))
	{
		$rUser = Query("select id, name, displayname, powerlevel, sex from users where id=".$id);
		if(NumRows($rUser))
			$members[$id] = Fetch($rUser);
		else
			return UserLink(array('id' => 0, 'name' => "Unknown User", 'sex' => 0, 'powerlevel' => -1));
	}
	return UserLink($members[$id]);
}

function MakeThreadLink($matches)
{
	global $threadLinkCache;
	$id = (int)$matches[1];
	if(!isset($threadLinkCache[$id]))
	{
		$rThread = Query("select id, title from threads where id=".$id);
		if(NumRows($rThread))
		{
			$thread = Fetch($rThread);
			$threadLinkCache[$id] = actionLinkTag($thread['title'], "thread", $thread['id']);
		}
		else
			$threadLinkCache[$id] = "&lt;invalid thread ID&gt;";
	}
	return $threadLinkCache[$id];
}

function MakeForumLink($matches)
{
	global $forumLinkCache;
	$id = (int)$matches[1];
	if(!isset($forumLinkCache[$id]))
	{
		$rForum = Query("select id, title from forums where id=".$id);
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

function ApplyNetiquetteToLinks($match)
{
	if (substr($match[1], 0, 7) != 'http://')
		return $match[0];

	if (stripos($match[1], 'http://'.$_SERVER['SERVER_NAME']) === 0)
		return $match[0];

	return $match[0].' target="_blank"';
}

function FilterJS($match)
{
	$url = html_entity_decode($match[2]);
	if (stristr($url, "javascript:"))
		return "";
	return $match[0];
}

function GetSyndrome($activity)
{
	include("syndromes.php");
	$soFar = "";
	foreach($syndromes as $minAct => $syndrome)
		if($activity >= $minAct)
			$soFar = "<em style=\"color: ".$syndrome[1].";\">".$syndrome[0]."</em><br />";
	return $soFar;
}

$text = "";
function CleanUpPost($postText, $poster = "", $noSmilies = false, $noBr = false)
{
	global $smilies, $text;
	static $orig, $repl;
	LoadSmilies();

	$s = $postText;
	$s = str_replace("\r\n","\n", $s);
	
	$s = EatThatPork($s);

	$s = preg_replace_callback("'\[source=(.*?)\](.*?)\[/source\]'si", "GeshiCallbackL", $s);
	$s = preg_replace_callback("'\[source\](.*?)\[/source\]'si", "GeshiCallback", $s);

	$s = preg_replace_callback("'\[user=([0-9]+)\]'si", "MakeUserLink", $s);
	$s = preg_replace_callback("'\[thread=([0-9]+)\]'si", "MakeThreadLink", $s);
	$s = preg_replace_callback("'\[forum=([0-9]+)\]'si", "MakeForumLink", $s);
	$s = preg_replace_callback("'@\"([\w ]+)\"'si", "MakeUserAtLink", $s);

	//De-tabled [code] tag, based on BH's...
    $list  = array("<"   ,"\\\"" ,"\\\\" ,"\\'","\r"  ,"["    ,":"    ,")"    ,"_"    );
    $list2 = array("&lt;","\""   ,"\\"   ,"\'" ,"<br/>","&#91;","&#58;","&#41;","&#95;");
    $s = preg_replace("'\[code\](.*?)\[/code\]'sie",
					'\''."<div class=\"codeblock\">".'\''
					.'.str_replace($list,$list2,\'\\1\').\'</div>\'',$s);

	$s = preg_replace("'\[b\](.*?)\[/b\]'si","<strong>\\1</strong>", $s);
	$s = preg_replace("'\[i\](.*?)\[/i\]'si","<em>\\1</em>", $s);
	$s = preg_replace("'\[u\](.*?)\[/u\]'si","<u>\\1</u>", $s);
	$s = preg_replace("'\[s\](.*?)\[/s\]'si","<del>\\1</del>", $s);

	$s = preg_replace("'<b>(.*?)\</b>'si","<strong>\\1</strong>", $s);
	$s = preg_replace("'<i>(.*?)\</i>'si","<em>\\1</em>", $s);
	$s = preg_replace("'<u>(.*?)\</u>'si","<span class=\"underline\">\\1</span>", $s);
	$s = preg_replace("'<s>(.*?)\</s>'si","<del>\\1</del>", $s);

	if($noBr == FALSE)
		$s = str_replace("\n","<br />", $s);

	//Blacklisted tags
	$badTags = array('script','iframe','frame','blink','textarea','noscript','meta','xmp','plaintext','marquee','embed','object');
	foreach($badTags as $tag)
	{
		$s = preg_replace("'<$tag(.*?)>'si", "&lt;$tag\\1>" ,$s);
		$s = preg_replace("'</$tag(.*?)>'si", "&lt;/$tag>", $s);
	}

	//Bad sites
	$s = preg_replace("'goatse'si","goat<span>se</span>", $s);
	$s = preg_replace("'tubgirl.com'si","www.youtube.com/watch?v=EK2tWVj6lXw", $s);
	$s = preg_replace("'ogrish.com'si","www.youtube.com/watch?v=2iveTJXcp6k", $s);
	$s = preg_replace("'liveleak.com'si","www.youtube.com/watch?v=xhLxnlNcxv8", $s);
	$s = preg_replace("'charonboat.com'si","www.youtube.com/watch?v=c9BA5e2Of_U", $s);
	$s = preg_replace("'shrewsburycollege.co.uk'si","www.youtube.com/watch?v=EK2tWVj6lXw", $s);
	$s = preg_replace("'lemonparty.com'si","www.youtube.com/watch?v=EK2tWVj6lXw", $s);
	$s = preg_replace("'meatspin.com'si","www.youtube.com/watch?v=2iveTJXcp6k", $s);

	//Various other stuff
	//[SUGGESTION] Block "display: none" instead of just "display:" -- Mega-Mario
	$s = preg_replace("'display:'si", "display<em></em>:", $s);

	$s = preg_replace("@(on)(\w+?\s*?)=@si", '$1$2&#x3D;', $s);
	
	$s = preg_replace("'-moz-binding'si"," -mo<em></em>z-binding", $s);
	$s = preg_replace("'filter:'si","filter<em></em>:>", $s);
	$s = preg_replace("'javascript:'si","javascript<em></em>:>", $s);

	$s = str_replace("[spoiler]","<div class=\"spoiler\"><button onclick=\"toggleSpoiler(this.parentNode);\">Show spoiler</button><div class=\"spoiled hidden\">", $s);
	$s = preg_replace("'\[spoiler=(.*?)\]'si","<div class=\"spoiler\"><button onclick=\"toggleSpoiler(this.parentNode);\" class=\"named\">\\1</button><div class=\"spoiled hidden\">", $s);
	$s = str_replace("[/spoiler]","</div></div>", $s);

	$s = preg_replace("'\[url\](.*?)\[/url\]'si","<a href=\"\\1\">\\1</a>", $s);
	$s = preg_replace("'\[url=[\'\"]?(.*?)[\'\"]?\](.*?)\[/url\]'si","<a href=\"\\1\">\\2</a>", $s);
	$s = preg_replace("'\[url=(.*?)\](.*?)\[/url\]'si","<a href=\"\\1\">\\2</a>", $s);
	$s = preg_replace("'\[img\](.*?)\[/img\]'si","<img src=\"\\1\" alt=\"\">", $s);
	$s = preg_replace("'\[img=(.*?)\](.*?)\[/img\]'si","<img src=\"\\1\" alt=\"\\2\" title=\"\\2\">", $s);

	//Changed quote style.
	//The new one is way easier to style. ~Dirbaio
	$s =  str_replace("[quote]","<div class='quote'><div class='quotecontent'>", $s);
	$s =  str_replace("[/quote]","</div></div>", $s);
	$s = preg_replace("'\[quote=\"(.*?)\" id=\"(.*?)\"\]'si","<div class='quote'><div class='quoteheader'>Posted by <a href=\"thread.php?pid=\\2#\\2\">\\1</a></div><div class='quotecontent'>", $s);
	$s = preg_replace("'\[quote=(.*?)\]'si","<div class='quote'><div class='quoteheader'>Posted by \\1</div><div class='quotecontent'>", $s);
	$s = preg_replace("'\[reply=\"(.*?)\"\]'si","<div class='quote'><div class='quoteheader'>Sent by \\1</div><div class='quotecontent'>", $s);

	$bucket = "bbCode"; include("./lib/pluginloader.php");

	$s = preg_replace_callback("@(href|src)\s*=\s*\"([^\"]+)\"@si", "FilterJS", $s);
	$s = preg_replace_callback("@(href|src)\s*=\s*'([^']+)'@si", "FilterJS", $s);
	$s = preg_replace_callback("@(href|src)\s*=\s*([^\s>]+)@si", "FilterJS", $s);

	$s = preg_replace("'>>([0-9]+)'si",">>".actionLinkTag("\\1", "thread", "", "pid=\\1#\\1"), $s);
	if($poster)
		$s = preg_replace("'/me '","<b>* ".$poster."</b> ", $s);

	//Smilies
	if(!$noSmilies)
	{
		if (!isset($orig))
		{
			$orig = $repl = array();
			for ($i = 0; $i < count($smilies); $i++)
			{
				$orig[] = "/(?<=.\W|\W.|^\W)".preg_quote($smilies[$i]['code'], "/")."(?=.\W|\W.|\W$)/";
				$repl[] = "<img src=\"img/smilies/".$smilies[$i]['image']."\" />";
			}
		}
		$s = preg_replace($orig, $repl, " ".$s." ");
		$s = substr($s, 1, -1);
	}
	
	$s = preg_replace_callback("@<a[^>]+href\s*=\s*\"(.*?)\"@si", 'ApplyNetiquetteToLinks', $s);
	$s = preg_replace_callback("@<a[^>]+href\s*=\s*'(.*?)'@si", 'ApplyNetiquetteToLinks', $s);
	$s = preg_replace_callback("@<a[^>]+href\s*=\s*([^\"'][^\s>]*)@si", 'ApplyNetiquetteToLinks', $s);	

	include("macros.php");
	foreach($macros as $macro => $img)
		$s = str_replace(":".$macro.":", "<img src=\"img/macros/".$img."\" alt=\":".$macro.":\" />", $s);

	return $s;
}

function ApplyTags($text, $tags)
{
	if(!stristr($text, "&"))
		return $text;
	$s = $text;
	foreach($tags as $tag => $val)
		$s = str_replace("&".$tag."&", $val, $s);
	if(is_numeric($tags['numposts']))
		$s = preg_replace('@&(\d+)&@sie', 'max($1 - '.$tags['numposts'].', 0)', $s);
	else
		$s = preg_replace("'&(\d+)&'si", "preview", $s);
	return $s;
}

define('POST_NORMAL', 0);			// standard post box
define('POST_PM', 1);				// PM post box
define('POST_DELETED_SNOOP', 2);	// post box with close/undelete (for mods 'view deleted post' feature)
define('POST_SAMPLE', 3);			// sample post box (profile sample post, newreply post preview, etc)

$sideBarStuff = "";
$sideBarData = 0;

// $post: post data (typically returned by SQL queries or forms)
// $type: one of the POST_XXX constants
// $params: an array of extra parameters, depending on the post box type. Possible parameters:
//		* tid: the ID of the thread the post is in (POST_NORMAL and POST_DELETED_SNOOP only)
//		* fid: the ID of the forum the thread containing the post is in (POST_NORMAL and POST_DELETED_SNOOP only)
// 		* threadlink: if set, a link to the thread is added next to 'Posted on blahblah' (POST_NORMAL and POST_DELETED_SNOOP only)
//		* noreplylinks: if set, no links to newreply.php (Quote/ID) are placed in the metabar (POST_NORMAL only)
//		* forcepostnum: if set, forces sidebar to show "Posts: X/X" (POST_SAMPLE only)
//		* metatext: if non-empty, this text is displayed in the metabar instead of 'Sample post' (POST_SAMPLE only)
function MakePost($post, $type, $params=array())
{
	global $loguser, $loguserid, $dateformat, $theme, $hacks, $isBot, $blocklayouts, $postText, $sideBarStuff, $sideBarData, $salt;
	
	$sideBarStuff = "";
	
	if(isset($_GET['pid']))
		$highlight = (int)$_GET['pid'];

	$isBlocked = $post['layoutblocked'] | $loguser['blocklayouts'] | $post['options'] & 1;
	$noSmilies = $post['options'] & 2;
	$noBr = $post['options'] & 4;

	if($post['deleted'] && $type == POST_NORMAL)
	{
		$meta = format(__("Posted on {0}"), cdate($dateformat,$post['date']));
		$links = "<ul class=\"pipemenu\"><li>".__("Post deleted")."</li>";
		if(CanMod($loguserid,$params['fid']))
		{
			$key = hash('sha256', "{$loguserid},{$loguser['pss']},{$salt}");
			if (IsAllowed("editPost", $post['id']))
				$links .= actionLinkTagItem(__("Undelete"), "editpost", $post['id'], "delete=2&amp;key=".$key);
			$links .= "<li><a href=\"#\" onclick=\"ReplacePost(".$post['id'].",true); return false;\">".__("View")."</a></li>";
		}
		$links .= "<li>".format(__("ID: {0}"), $post['id'])."</li></ul>";
		write(
"
		<table class=\"post margin\" id=\"post{0}\">
			<tr>
				<td class=\"side userlink\" id=\"{0}\">
					{1}
				</td>
				<td class=\"smallFonts\" style=\"border-left: 0px none; border-right: 0px none;\">
					{2}
				</td>
				<td class=\"smallFonts right\" style=\"border-left: 0px none;\">
					{3}
				</td>
			</tr>
		</table>
",	$post['id'], UserLink($post, "uid"), $meta, $links
);
		return;
	}

	if ($type == POST_SAMPLE)
		$meta = $params['metatext'] ? $params['metatext'] : __("Sample post");
	else
	{
		$forum = $params['fid'];
		$thread = $params['tid'];
		$canmod = CanMod($loguserid, $forum);
		$replyallowed = IsAllowed("makeReply", $thread);
		$editallowed = IsAllowed("editPost", $post['id']);
		$canreply = $replyallowed && ($canmod || (!$post['closed'] && $loguser['powerlevel'] > -1));
		
		$links = "";
		if (!$isBot)
		{
			if ($type == POST_DELETED_SNOOP)
			{
				$key = hash('sha256', "{$loguserid},{$loguser['pss']},{$salt}");
				$links = "<ul class=\"pipemenu\"><li>".__("Post deleted")."</li>";
				if ($editallowed)
					$links .= actionLinkTagItem(__("Undelete"), "editpost", $post['id'], "delete=2&amp;key=".$key);
				$links .= "<li><a href=\"#\" onclick=\"ReplacePost(".$post['id'].",false); return false;\">".__("Close")."</a></li>";
				$links .= "<li>".format(__("ID: {0}"), $post['id'])."</li></ul>";
			}
			else if ($type == POST_NORMAL)
			{
				$links .= "<ul class=\"pipemenu\">";
				
				$links .= actionLinkTagItem(__("Link"), "thread", "", "pid=".$post['id']."#".$post['id']);

				if ($canreply && !$params['noreplylinks'])
					$links .= actionLinkTagItem(__("Quote"), "newreply", $thread, "quote=".$post['id']);

				if ($editallowed && ($canmod || ($post['uid'] == $loguserid && $loguser['powerlevel'] > -1 && !$post['closed'])))
					$links .= actionLinkTagItem(__("Edit"), "editpost", $post['id']);

				if ($editallowed && $canmod)
				{
					// TODO: perhaps make delete links not require a key to be passed
					//  * POST-form delete confirmation, on separate page, a la Jul?
					//  * hidden form and Javascript-submit() link?
					$key = hash('sha256', "{$loguserid},{$loguser['pss']},{$salt}");
					$links .= actionLinkTagItem(__("Delete"), "editpost", $post['id'], "delete=1&amp;key=".$key);
				}
				if ($canreply && !$params['noreplylinks'])
					$links .= "<li>".format(__("ID: {0}"), actionLinkTag($post['id'], "newreply", $thread, "link=".$post['id']))."</li>";
				else
					$links .= "<li>".format(__("ID: {0}"), $post['id'])."</li>";
				if ($loguser['powerlevel'] > 0)
					$links .= "<li>".$post['ip']."</li>";
				$links .= "</ul>";
			}
		}

		$meta = format(__(($type == POST_PM) ? "Sent on {0}" : "Posted on {0}"), cdate($dateformat,$post['date']));
		//Threadlinks for listpost.php
		if ($params['threadlink'])
			$meta .= " ".__("in")." ".actionLinkTag($post['threadname'], "thread", $post['thread']);
		//Revisions
		if($post['revision'])
		{
			if ($canmod)
				$meta .= " (<a href=\"javascript:void(0);\" onclick=\"showRevisions(".$post['id'].")\">".format(__("revision {0}"), $post['revision'])."</a>)";
			else
				$meta .= " (".format(__("revision {0}"), $post['revision']).")";
		}
		//</revisions>
	}

	$sideBarStuff .= GetRank($post);
	if($sideBarStuff)
		$sideBarStuff .= "<br />";
	if($post['title'])
		$sideBarStuff .= strip_tags(CleanUpPost($post['title'], "", true), "<b><strong><i><em><span><s><del><img><a><br><small>")."<br />";
	else
	{
		$levelRanks = array(-1=>__("Banned"), 0=>"", 1=>__("Local mod"), 2=>__("Full mod"), 3=>__("Administrator"));
		$sideBarStuff .= $levelRanks[$post['powerlevel']]."<br />";
	}
	$sideBarStuff .= GetSyndrome($post['activity']);
	if($post['picture'])
	{
		if($post['mood'] > 0 && file_exists("img/avatars/".$post['uid']."_".$post['mood']))
			$sideBarStuff .= "<img src=\"img/avatars/".$post['uid']."_".$post['mood']."\" alt=\"\" />";
		else
			$sideBarStuff .= "<img src=\"".$post['picture']."\" alt=\"\" />";
	}

	$lastpost = ($post['lastposttime'] ? timeunits(time() - $post['lastposttime']) : "none");
	$lastview = timeunits(time() - $post['lastactivity']);

	if(!$params['forcepostnum'] && ($type == POST_PM || $type == POST_SAMPLE))
		$sideBarStuff .= "<br />\n".__("Posts:")." ".$post['posts'];
	else
		$sideBarStuff .= "<br />\n".__("Posts:")." ".$post['num']."/".$post['posts'];
		
	$sideBarStuff .= "<br />\n".__("Since:")." ".cdate($loguser['dateformat'], $post['regdate'])."<br />";

	$bucket = "sidebar"; include("./lib/pluginloader.php");

	$sideBarStuff .= "<br />\n".__("Last post:")." ".$lastpost;
	$sideBarStuff .= "<br />\n".__("Last view:")." ".$lastview;

	if($hacks['themenames'] == 3)
	{
		$sideBarStuff = "";
		$isBlocked = 1;
	}

	if($post['lastactivity'] > time() - 3600)
		$sideBarStuff .= "<br />\n".__("User is <strong>online</strong>");

	if($type == POST_NORMAL)
		$anchor = "<a name=\"".$post['id']."\" />";
	if(!$isBlocked)
	{
		$pTable = "table".$post['uid'];
		$row1 = "row".$post['uid']."_1";
		$row2 = "row".$post['uid']."_2";
		$topBar1 = "topbar".$post['uid']."_1";
		$topBar2 = "topbar".$post['uid']."_2";
		$sideBar = "sidebar".$post['uid'];
		$mainBar = "mainbar".$post['uid'];
	}

	$tags = array();
	$rankHax = $post['posts'];
	//if($post['num'] == "preview")
	//	$post['num'] = $post['posts'];
	//
	//	Crappy hack to fix what another crappy hack broke
	$post2 = $post;
	$post2['posts'] = $post['num'];
	//Disable tags by commenting/removing this part.
	// TODO: this could be done only once somewhere else (unless plugins doing stuff like per-user &tags& are desired)
	$tags = array
	(
		"postnum" => $post['num'],
		"postcount" => $post['posts'],
		"numdays" => floor((time()-$post['regdate'])/86400),
		"date" => cdate($dateformat,$post['date']),
		"rank" => GetRank($post2),
	);
	$bucket = "amperTags"; include("./lib/pluginloader.php");
	
	$post['posts'] = $rankHax;
	
	$postText = $noBr ? $post['text'] : nl2br($post['text']);
	$bucket = "postMangler"; include("./lib/pluginloader.php");
	
	if($post['postheader'] && !$isBlocked)
		$postText = str_replace('$theme', $theme, $post['postheader']).$postText;

	if($post['signature'] && !$isBlocked)
	{
		if(!$post['signsep'])
			$postText .= "<br />_________________________<br />";
		else
			$postText .= "<br />";
			
		$postText .= $post['signature'];
	}
	
	$postText = ApplyTags(CleanUpPost($postText,$post['name'], $noSmilies, true), $tags);

	$postCode =
"
		<table class=\"post margin {14} ".$pTable."\" id=\"post{13}\">
			<tr class=\"".$row1."\">
				<td class=\"side userlink {1}\">
					{0}
					{5}
				</td>
				<td class=\"meta right {2}\">
					<div style=\"float: left;\" id=\"meta_{13}\">
						{7}
					</div>
					<div style=\"float: left; display: none;\" id=\"dyna_{13}\">
						Hi.
					</div>
					{8}
				</td>
			</tr>
			<tr class=\"".$row2."\">
				<td class=\"side {3}\">
					<div class=\"smallFonts\">
						{6}
					</div>
				</td>
				<td class=\"post {4}\" id=\"post_{13}\">

					{10}

				</td>
			</tr>
		</table>
";

	write($postCode,
			$anchor, $topBar1, $topBar2, $sideBar, $mainBar,
			UserLink($post, "uid"), $sideBarStuff, $meta, $links,
			null, $postText, null, null, $post['id'], $post['id'] == $highlight ? "highlightedPost" : "");

}


//Scans for any numerical entities that decode to the 7-bit printable ASCII range and removes them.
//This makes a last-minute hack impossible where a javascript: link is given completely in absurd and malformed entities.
function EatThatPork($s)
{
	$s = preg_replace_callback("/(&#)(x*)([a-f0-9]+(?![a-f0-9]))(;*)/i", "CheckKosher", $s);
	return $s;
}

function CheckKosher($matches)
{
	$num = ltrim($matches[3], "0");
	if($matches[2])
		$num = hexdec($num);
	if($num < 127)
		return ""; //"&#xA4;";
	else
		return "&#x".dechex($num).";"; //$matches[0];
}

?>
