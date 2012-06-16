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

function filterPollColors($input)
{
	return preg_replace("@[^#0123456789abcdef]@si", "", $input);
}

function LoadSmilies($byOrder = FALSE)
{
	global $smilies, $smiliesOrdered, $dbpref;
	
	if($byOrder)
	{
		if(isset($smiliesOrdered))
			return;
		$rSmilies = Query("select * from {$dbpref}smilies order by id asc");
		$smiliesOrdered = array();
		while($smiley = Fetch($rSmilies))
			$smiliesOrdered[] = $smiley;
	}
	else
	{
		if(isset($smilies))
			return;
		$rSmilies = Query("select * from {$dbpref}smilies order by length(code) desc");
		$smilies = array();
		while($smiley = Fetch($rSmilies))
		{
			$smilies[] = $smiley;
		}
	}
}

function ApplySmilies($text)
{
	global $smilies, $smiliesReplaceOrig, $smiliesReplaceNew;
	
	if (!isset($smiliesReplaceOrig))
	{
		$smiliesReplaceOrig = $smiliesReplaceNew = array();
		for ($i = 0; $i < count($smilies); $i++)
		{
			$smiliesReplaceOrig[] = "/".preg_quote($smilies[$i]['code'], "/")."/";
			$smiliesReplaceNew[] = "<img class=\"smiley\" alt=\"\" src=\"img/smilies/".$smilies[$i]['image']."\" />";
		}
	}
	return preg_replace($smiliesReplaceOrig, $smiliesReplaceNew, $text);
}

function LoadBlocklayouts()
{
	global $blocklayouts, $loguserid, $dbpref;
	if(isset($blocklayouts))
		return;
	$rBlocks = Query("select * from {$dbpref}blockedlayouts where blockee = ".$loguserid);
	while($block = Fetch($rBlocks))
		$blocklayouts[$block['user']] = 1;
}

function LoadRanks($rankset)
{
	global $ranks, $dbpref;
	if(isset($ranks[$rankset]))
		return;
	$ranks[$poster['rankset']] = array();
	$rRanks = Query("select * from {$dbpref}ranks where rset=".$rankset." order by num");
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

function MakeUserAtLink($matches)
{
	global $members, $dbpref;
	$username = $matches[1];
	foreach($members as $id => $data)
	{
		if($data['name'] == $username)
		{
			return UserLink($members[$data['id']]);
		}
	}
	//Didn't find it in the cache.
	$rUser = Query("select id, name, displayname, powerlevel, sex from {$dbpref}users where name='".$username."' or displayname='".$username."'");
	if(NumRows($rUser))
	{
		$hit = Fetch($rUser);
		$members[$hit['id']] = $hit;
		return UserLink($hit);
	}
	else
		return $username; //Return the actual name attempted.
}


function ApplyNetiquetteToLinks($match)
{
	if (substr($match[1], 0, 7) != 'http://')
		return $match[0];

	if (stripos($match[1], 'http://'.$_SERVER['SERVER_NAME']) === 0)
		return $match[0];

	return $match[0].' target="_blank"';
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

function postDoReplaceText($s)
{
	global $postNoSmilies, $postNoBr, $postPoster, $smilies;
	
	$s = preg_replace_callback("'@\"([\w ]+)\"'si", "MakeUserAtLink", $s);
	$s = preg_replace("'>>([0-9]+)'si",">>".actionLinkTag("\\1", "thread", "", "pid=\\1#\\1"), $s);
	if($postPoster)
		$s = preg_replace("'/me '","<b>* ".$postPoster."</b> ", $s);

	LoadSmilies();
	
	//Smilies
	if(!$postNoSmilies)
		$s = ApplySmilies($s);
		


	include("macros.php");
	foreach($macros as $macro => $img)
		$s = str_replace(":".$macro.":", "<img src=\"img/macros/".$img."\" alt=\":".$macro.":\" />", $s);
	
	return $s;
}

function CleanUpPost($postText, $poster = "", $noSmilies = false, $noBr = false)
{
	global $postNoSmilies, $postNoBr, $smilies, $postPoster;
	static $orig, $repl;
	
	$postNoSmilies = $noSmilies;
	$postNoBr = $noBr;
	$postPoster = $poster;
	
	$s = parseBBCode($postText);

	$s = preg_replace_callback("@<a[^>]+href\s*=\s*\"(.*?)\"@si", 'ApplyNetiquetteToLinks', $s);
	$s = preg_replace_callback("@<a[^>]+href\s*=\s*'(.*?)'@si", 'ApplyNetiquetteToLinks', $s);
	$s = preg_replace_callback("@<a[^>]+href\s*=\s*([^\"'][^\s>]*)@si", 'ApplyNetiquetteToLinks', $s);

	$s = securityPostFilter($s);
	
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

//This function is CRITICAL for the post security.
//Should always run LAST and on the WHOLE post.

$badTags = array('script','iframe','frame','blink','textarea','noscript','meta','xmp','plaintext','marquee','embed','object');

function FilterJS($match)
{
	$url = html_entity_decode($match[2]);
	if (stristr($url, "javascript:"))
		return "";
	return $match[0];
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
		return "&#x".dechex($num).";";
}

function securityPostFilter($s)
{
	global $badTags;

	$s = str_replace("\r\n","\n", $s);

	$s = EatThatPork($s);

	//Blacklisted tags
	//Commenting this out because the post parser now checks for bad tags by itself. ~Dirbaio
	/*
	foreach($badTags as $tag)
	{
		$s = preg_replace("'<$tag(.*?)>'si", "&lt;$tag\\1>" ,$s);
		$s = preg_replace("'</$tag(.*?)>'si", "&lt;/$tag>", $s);
	}*/

	//Bad sites
	//Do we reaaally need this? This could be plugin-based... ~Dirbaio
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

	$s = preg_replace_callback("@(href|src)\s*=\s*\"([^\"]+)\"@si", "FilterJS", $s);
	$s = preg_replace_callback("@(href|src)\s*=\s*'([^']+)'@si", "FilterJS", $s);
	$s = preg_replace_callback("@(href|src)\s*=\s*([^\s>]+)@si", "FilterJS", $s);

	return $s;
}

function makePostText($post)
{
	global $loguser, $loguserid, $theme, $hacks, $isBot, $blocklayouts, $postText, $sideBarStuff, $sideBarData, $salt;

	LoadBlockLayouts();

	$isBlocked = $post['layoutblocked'] | $loguser['blocklayouts'] | $post['options'] & 1;
	$noSmilies = $post['options'] & 2;
	$noBr = $post['options'] & 4;

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
		"date" => formatdate($post['date']),
		"rank" => GetRank($post2),
	);
	$bucket = "amperTags"; include("./lib/pluginloader.php");

	$post['posts'] = $rankHax;

	$postText = CleanUpPost(ApplyTags($post['text'], $tags), $post['name'], $noSmilies, $noBr);
	$bucket = "postMangler"; include("./lib/pluginloader.php");

	//Post header and footer.
	//OMFG, more hax.
	$magicString = "###POSTTEXTGOESHEREOMG###";
	$separator = "";
	
	if($isBlocked)
		$postLayout = $magicString;
	else
	{
		$postLayout = $post['postheader'].$magicString.$post['signature'];
		$postLayout = ApplyTags($postLayout, $tags);
		$postLayout = CleanUpPost($postLayout, $post['name'], $noSmilies, true);
		
		if($post['signature'])
			if(!$post['signsep'])
				$separator = "<br />_________________________<br />";
			else
				$separator = "<br />";
	}
	
	$postText = str_replace($magicString, "<!-- LOL -->".$postText.$separator, $postLayout);
	return $postText;
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
	global $loguser, $loguserid, $theme, $hacks, $isBot, $blocklayouts, $postText, $sideBarStuff, $sideBarData, $salt;

	$sideBarStuff = "";

	if(isset($_GET['pid']))
		$highlight = (int)$_GET['pid'];

	if($post['deleted'] && $type == POST_NORMAL)
	{
		$meta = format(__("Posted on {0}"), formatdate($post['date']));
		$links = "<ul class=\"pipemenu\"><li>".__("Post deleted")."</li>";
		if(CanMod($loguserid,$params['fid']))
		{
			$key = hash('sha256', "{$loguserid},{$loguser['pss']},{$salt}");
			if (IsAllowed("editPost", $post['id']))
				$links .= actionLinkTagItem(__("Undelete"), "editpost", $post['id'], "delete=2&key=".$key);
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
		$meta = $params['metatext'] ? $params['metatext'] : __("Sample post");	// dirty hack
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
					$links .= actionLinkTagItem(__("Undelete"), "editpost", $post['id'], "delete=2&key=".$key);
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
					$links .= actionLinkTagItem(__("Delete"), "editpost", $post['id'], "delete=1&key=".$key);
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

		if ($type == POST_PM)
		{
			$message = __("Sent on {0}");
		}
		else {
			$message = __("Posted on {0}");
		}
		$meta = format($message, formatdate($post['date']));
		//Threadlinks for listpost.php
		if ($params['threadlink'])
			$meta .= " ".__("in")." ".actionLinkTag($post['threadname'], "thread", $post['thread']);
		//Revisions
		if($post['revision'])
		{
			if ($post['revuser'])
			{
				$ru_link = UserLink(array('id'=>$post['revuser'], 'name'=>$post['ru_name'], 'displayname'=>$post['ru_dn'], 'powerlevel'=>$post['ru_power'], 'sex'=>$post['ru_sex']));
				$revdetail = " ".format(__("by {0} on {1}"), $ru_link, formatdate($post['revdate']));
			}
			else
				$revdetail = '';

			if ($canmod)
				$meta .= " (<a href=\"javascript:void(0);\" onclick=\"showRevisions(".$post['id'].")\">".format(__("rev. {0}"), $post['revision'])."</a>".$revdetail.")";
			else
				$meta .= " (".format(__("rev. {0}"), $post['revision']).$revdetail.")";
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
/*
	if($hacks['themenames'] == 3)
	{
		$sideBarStuff = "";
		$isBlocked = 1;
	}*/

	if($post['lastactivity'] > time() - 300)
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

	$postText = makePostText($post);

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
					<div style=\"float: left; text-align:left; display: none;\" id=\"dyna_{13}\">
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

					{9}
					<!-- POST BEGIN -->
					{10}
					<!-- POST END -->
					{12}
					{11}

				</td>
			</tr>
		</table>
";

	write($postCode,
			$anchor, $topBar1, $topBar2, $sideBar, $mainBar,
			UserLink($post, "uid"), $sideBarStuff, $meta, $links,
			"", $postText, "", "", $post['id'], $post['id'] == $highlight ? "highlightedPost" : "");

}

?>
