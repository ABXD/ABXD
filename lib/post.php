<?php
//  AcmlmBoard XD support - Post functions

include_once("write.php");


function ParseThreadTags($title)
{
	preg_match_all("/\[(.*?)\]/", $title, $matches);
	foreach($matches[1] as $tag)
	{
		$title = str_replace("[".$tag."]", "", $title);
		$tag = htmlspecialchars(strtolower($tag));

		//Start at a hue that makes "18" red.
		$hash = -105;
		for($i = 0; $i < strlen($tag); $i++)
			$hash += ord($tag[$i]);

		//That multiplier is only there to make "nsfw" and "18" the same color.
		$color = "hsl(".(($hash * 57) % 360).", 70%, 40%)";

		$tags .= "<span class=\"threadTag\" style=\"background-color: ".$color.";\">".$tag."</span>";
	}
	if(isset($tags))
		$tags = " ".$tags;

	if (!isset($tags)) $tags = " ";

	$title = str_replace("<", "&lt;", $title);
	$title = str_replace(">", "&gt;", $title);

	return array(trim($title), $tags);
}

function filterPollColors($input)
{
	return preg_replace("@[^#0123456789abcdef]@si", "", $input);
}

function loadBlockLayouts()
{
	global $blocklayouts, $loguserid;

	if(isset($blocklayouts))
		return;

	$rBlocks = Query("select * from {blockedlayouts} where blockee = {0}", $loguserid);
	$blocklayouts = array();

	while($block = Fetch($rBlocks))
		$blocklayouts[$block['user']] = 1;
}

function getSyndrome($activity)
{
	include("syndromes.php");
	$soFar = "";
	foreach($syndromes as $minAct => $syndrome)
		if($activity >= $minAct)
			$soFar = "<em style=\"color: ".$syndrome[1].";\">".$syndrome[0]."</em><br />";
	return $soFar;
}

function applyTags($text, $tags)
{
	if(!stristr($text, "&"))
		return $text;
	$s = $text;
	foreach($tags as $tag => $val)
		$s = str_replace("&".$tag."&", $val, $s);
	if(is_numeric($tags['postcount']))
		$s = preg_replace_callback('@&(\d+)&@si', array(new MaxPosts($tags), 'max_posts_callback'), $s);
	else
		$s = preg_replace("'&(\d+)&'si", "preview", $s);
	return $s;
}

// hax for anonymous function
class MaxPosts {
    var $tags;
    function __construct($tags) {
        $this->tags = $tags;
    }

    function max_posts_callback($results) {
        return max($results[1] - $this->tags['postcount'], 0);
    }
}

$activityCache = array();
function getActivity($id)
{
	global $activityCache;

	if(!isset($activityCache[$id]))
		$activityCache[$id] = FetchResult("select count(*) from {posts} where user = {0} and date > {1}", $id, (time() - 86400));

	return $activityCache[$id];
}

$layouCache = array();

function makePostText($post)
{
	global $loguser, $loguserid, $layoutCache, $blocklayouts, $mobileLayout;

	LoadBlockLayouts();
	$poster = getDataPrefix($post, "u_");
	$isBlocked = $poster['globalblock'] || $loguser['blocklayouts'] || $post['options'] & 1 || isset($blocklayouts[$poster['id']]);

	$noSmilies = $post['options'] & 2;
	$noBr = $post['options'] & 4;

	//Do Ampersand Tags
	$tags = array
	(
//This tag breaks because of layout caching.
//		"postnum" => $post['num'],
		"postcount" => $poster['posts'],
		"numdays" => floor((time()-$poster['regdate'])/86400),
		"date" => formatdate($post['date']),
		"rank" => GetRank($poster["rankset"], $poster["posts"]),
	);
	$bucket = "amperTags"; include("./lib/pluginloader.php");

	$postText = $post['text'];
	$postText = ApplyTags($postText, $tags);
	$postText = CleanUpPost($postText, $poster['name'], $noSmilies);

	//Post header and footer.
	$magicString = "###POSTTEXTGOESHEREOMG###";
	$separator = "";

	if($isBlocked || $mobileLayout)
		$postLayout = $magicString;
	else
	{
		if(!isset($layoutCache[$poster["id"]]))
		{
			$postLayout = $poster['postheader'].$magicString.$poster['signature'];
			$postLayout = ApplyTags($postLayout, $tags);
			$postLayout = CleanUpPost($postLayout, $poster['name']);
			$layoutCache[$poster["id"]] = $postLayout;
		}
		else
			$postLayout = $layoutCache[$poster["id"]];

		if($poster['signature'])
			if(!$poster['signsep'])
				$separator = "<br />_________________________<br />";
			else
				$separator = "<br />";
	}

	$postText = str_replace($magicString, $postText.$separator, $postLayout);
	return $postText;
}

define('POST_NORMAL', 0);			// standard post box
define('POST_PM', 1);				// PM post box
define('POST_DELETED_SNOOP', 2);	// post box with close/undelete (for mods 'view deleted post' feature)
define('POST_SAMPLE', 3);			// sample post box (profile sample post, newreply post preview, etc)

function makePostLinks($post, $type, $params=array())
{
	global $loguser, $loguserid, $mobileLayout;

	$forum = $params['fid'];
	$thread = $params['tid'];
	$canMod = CanMod($loguserid, $forum);
	$canReply = ($canMod || (!$post['closed'] && $loguser['powerlevel'] > -1)) && $loguserid;

	$links = new PipeMenu();

	if($type == POST_PM || POST_SAMPLE)
		return $links;

	if($post['deleted'])
	{
		if($canMod)
			$links->add(new PipeMenuLinkEntry(__('Undelete'), "", "", "", "undo", "deletePost(".$post["id"].", '".$loguser["token"]."', 2);return false;"));

		if($canMod || $post["u_id"] == $loguserid)
		{
			if($type == POST_DELETED_SNOOP)
				$links->add(new PipeMenuLinkEntry(__('Close'), "", "", "", "chevron-up", "replacePost(".$post['id'].", false); return false;"));
			else
				$links->add(new PipeMenuLinkEntry(__('View'), "", "", "", "chevron-down", "replacePost(".$post['id'].", true); return false;"));
		}
	}
	else
	{
		$links->add(new PipeMenuLinkEntry(__("Link"), "post", $post['id'], "", "link"));

		if ($canReply && !isset($params['noreplylinks']))
			$links->add(new PipeMenuLinkEntry(__("Quote"), "newreply", $thread, "quote=".$post['id'], "quote-left"));

		if ($canMod || ($post['user'] == $loguserid && $loguser['powerlevel'] > -1 && !$post['closed']))
			$links->add(new PipeMenuLinkEntry(__("Edit"), "editpost", $post['id'], "", "pencil"));

		if ($canMod)
			$links->add(new PipeMenuLinkEntry(__('Delete'), "", "", "", "remove", "deletePost(".$post["id"].", '".$loguser["token"]."', 1);return false;"));

		if(!$mobileLayout)
		{
			$links->add(new PipeMenuTextEntry(format(__("ID: {0}"), $post['id'])));

			if ($canMod)
				$links->add(new PipeMenuTextEntry($post['ip']));
		}

		$bucket = "topbar"; include("./lib/pluginloader.php");
	}

	return $links;
}

// $post: post data (typically returned by SQL queries or forms)
// $type: one of the POST_XXX constants
// $params: an array of extra parameters, depending on the post box type. Possible parameters:
//		* tid: the ID of the thread the post is in (POST_NORMAL and POST_DELETED_SNOOP only)
//		* fid: the ID of the forum the thread containing the post is in (POST_NORMAL and POST_DELETED_SNOOP only)
// 		* threadlink: if set, a link to the thread is added next to 'Posted on blahblah' (POST_NORMAL and POST_DELETED_SNOOP only)
//		* noreplylinks: if set, no links to newreply.php (Quote/ID) are placed in the metabar (POST_NORMAL only)
//		* forcepostnum: if set, forces sidebar to show "Posts: X/X" (POST_SAMPLE only)
//		* metatext: if non-empty, this text is displayed in the metabar instead of 'Sample post' (POST_SAMPLE only)
function makePost($post, $type, $params=array())
{
	global $loguser, $loguserid, $blocklayouts, $dataDir, $dataUrl, $mobileLayout;

	$sideBarStuff = "";
	$poster = getDataPrefix($post, "u_");
	LoadBlockLayouts();
	$isBlocked = $poster['globalblock'] || $loguser['blocklayouts'] || $post['options'] & 1 || isset($blocklayouts[$poster['id']]);

	$links = makePostLinks($post, $type, $params);

	if($post['deleted'] && $type == POST_NORMAL)
	{
		$meta = format(__("Posted on {0}"), formatdate($post['date']));
		$meta .= __(', deleted');
		if ($post['deletedby'])
		{
			$db_link = UserLink(getDataPrefix($post, "du_"));
			$meta .= __(' by ').$db_link;

			if ($post['reason'])
				$meta .= ': '.htmlspecialchars($post['reason']);
		}
		
		if($mobileLayout)
		{
			$links->setClass("toolbarMenu");

			echo "
				<table class=\"outline margin mobile-postBox\" id=\"post${post['id']}\">
					<tr class=\"header0 mobile-postHeader\">
						<th>
							$anchor
							<table>
								<tr>
									<td>
										<div class=\"mobile-userAvatarBox\">
											$picture
										</div>
									</td>
									<td class=\"mobile-postInfoCell\" style=\"width: 99%; overflow: hidden;\">
										<div style=\"position: relative; height: 40px; top: 0; left: 0;\">
											<div style=\"position: absolute; top: 0; left: 0;\">
												" . userLink($poster) . "<br />
												<span class=\"date\">$meta</span>
											</div>
										</div>
										<span style=\"text-align:left; display: none;\" id=\"dyna_${post['id']}\">
											&nbsp;
										</span>
									</td>
									<td>
										".$links->build(2)."
									</td>
								</tr>
							</table>
						</th>
					</tr>
				</table>
			";
		}
		else
			echo "
				<table class=\"post margin deletedpost\" id=\"post{$post['id']}\">
					<tr>
						<td class=\"side userlink\">
							".userLink($poster)."
						</td>
						<td class=\"smallFonts meta right\">
							<div style=\"float:left\">
								$meta
							</div>
							".$links->build()."
						</td>
					</tr>
				</table>";
		return;
	}

	if ($type == POST_SAMPLE)
		$meta = $params['metatext'] ? $params['metatext'] : __("Sample post");
	else
	{
		$forum = $params['fid'];
		$thread = $params['tid'];
		$canMod = CanMod($loguserid, $forum);
		$canReply = ($canMod || (!$post['closed'] && $loguser['powerlevel'] > -1)) && $loguserid;
		
		if ($type == POST_PM)
			$message = __("Sent on {0}");
		else
			$message = __("Posted on {0}");

		$meta = format($message, formatdate($post['date']));

		//Threadlinks for listpost.php
		if (isset($params['threadlink']))
		{
			$thread = array();
			$thread["id"] = $post["thread"];
			$thread["title"] = $post["threadname"];

			$meta .= " ".__("in")." ".makeThreadLink($thread);
		}

		//Revisions
		if($post['revision'])
		{
			if ($post['revuser'])
			{
				$ru_link = UserLink(getDataPrefix($post, "ru_"));
				$revdetail = " ".format(__("by {0} on {1}"), $ru_link, formatdate($post['revdate']));
			}
			else
				$revdetail = '';

			if ($canMod)
				$meta .= " (<a href=\"javascript:void(0);\" onclick=\"showRevisions(".$post['id'].")\">".format(__("rev. {0}"), $post['revision'])."</a>".$revdetail.")";
			else
				$meta .= " (".format(__("rev. {0}"), $post['revision']).$revdetail.")";
		}
		//</revisions>
	}


	// POST SIDEBAR

	$sideBarStuff .= GetRank($poster["rankset"], $poster["posts"]);
	if($sideBarStuff)
		$sideBarStuff .= "<br />";
	if($poster['title'])
		$sideBarStuff .= strip_tags(CleanUpPost($poster['title'], "", true), "<b><strong><i><em><span><s><del><img><a><br/><br><small>")."<br />";
	else
	{
		$levelRanks = array(-1=>__("Banned"), 0=>"", 1=>__("Local mod"), 2=>__("Full mod"), 3=>__("Administrator"));
		$sideBarStuff .= $levelRanks[$poster['powerlevel']]."<br />";
	}
	$sideBarStuff .= GetSyndrome(getActivity($poster["id"]));

	$pictureUrl = "";
	
	if($post['mood'] > 0)
	{
		if(file_exists("${dataDir}avatars/".$poster['id']."_".$post['mood']))
			$pictureUrl = "${dataUrl}avatars/".$poster['id']."_".$post['mood'];
	}
	else
	{
		if($poster["picture"] == "#INTERNAL#")
			$pictureUrl = "${dataUrl}avatars/".$poster['id'];
		else if($poster["picture"])
			$pictureUrl = $poster["picture"];
	}

	if($pictureUrl)
		$sideBarStuff .= "<img src=\"".htmlspecialchars($pictureUrl)."\" alt=\"\" />";

	$lastpost = ($poster['lastposttime'] ? timeunits(time() - $poster['lastposttime']) : "none");
	$lastview = timeunits(time() - $poster['lastactivity']);

	$sideBarStuff .= "<br />\n".__("Karma:")." ".$poster['karma'];

	if(!isset($params['forcepostnum']) && ($type == POST_PM || $type == POST_SAMPLE))
		$sideBarStuff .= "<br />\n".__("Posts:")." ".$poster['posts'];
	else
		$sideBarStuff .= "<br />\n".__("Posts:")." ".$post['num']."/".$poster['posts'];

	$sideBarStuff .= "<br />\n".__("Since:")." ".cdate($loguser['dateformat'], $poster['regdate'])."<br />";

	$bucket = "sidebar"; include("./lib/pluginloader.php");

	if(Settings::get("showExtraSidebar"))
	{
		$sideBarStuff .= "<br />\n".__("Last post:")." ".$lastpost;
		$sideBarStuff .= "<br />\n".__("Last view:")." ".$lastview;

		if($poster['lastactivity'] > time() - 300)
			$sideBarStuff .= "<br />\n".__("User is <strong>online</strong>");
	}

	// OTHER STUFF

	if($type == POST_NORMAL)
		$anchor = "<a name=\"".$post['id']."\"></a>";

	if(!$isBlocked)
	{
		$pTable = "table".$poster['id'];
		$row1 = "row".$poster['id']."_1";
		$row2 = "row".$poster['id']."_2";
		$topBar1 = "topbar".$poster['id']."_1";
		$topBar2 = "topbar".$poster['id']."_2";
		$sideBar = "sidebar".$poster['id'];
		$mainBar = "mainbar".$poster['id'];
	}

	$postText = makePostText($post);

	//PRINT THE POST!

	if($mobileLayout)
	{
		$links->setClass("toolbarMenu");

		if($pictureUrl)
			$picture = "<img src=\"".htmlspecialchars($pictureUrl)."\" alt=\"\" style=\"max-width: 40px; max-height: 40px;\"/>";
		else
			$picture = "";
		
		echo "
				<table class=\"outline margin mobile-postBox\" id=\"post${post['id']}\">
				<tr class=\"header0 mobile-postHeader\">
					<th>
						$anchor
						<table>
							<tr>
								<td>
									<div class=\"mobile-userAvatarBox\">
										$picture
									</div>
								</td>
								<td class=\"mobile-postInfoCell\" style=\"width: 99%; overflow: hidden;\">
									<div style=\"position: relative; height: 40px; top: 0; left: 0;\">
										<div style=\"position: absolute; top: 0; left: 0;\">
											" . userLink($poster) . "<br />
											<span class=\"date\">$meta</span>
										</div>
									</div>
									<span style=\"text-align:left; display: none;\" id=\"dyna_${post['id']}\">
										&nbsp;
									</span>
								</td>
								<td>
									".$links->build(2)."
								</td>
							</tr>
						</table>
					</th>
				</tr>
				<tr>
					<td colspan=\"3\" class=\"cell0 mobile-postBox\">
						$postText
					</td>
				</tr>
			</table>
		";
	}
	else
		echo "
			<table class=\"post margin $pTable\" id=\"post${post['id']}\">
				<tr class=\"$row1\">
					<td class=\"side userlink $topBar1\">
						$anchor
						".UserLink($poster)."
					</td>
					<td class=\"meta right $topBar2\">
						<div style=\"float: left;\" id=\"meta_${post['id']}\">
							$meta
						</div>
						<div style=\"float: left; text-align:left; display: none;\" id=\"dyna_${post['id']}\">
							Hi.
						</div>
						" . $links->build() . "
					</td>
				</tr>
				<tr class=\"".$row2."\">
					<td class=\"side $sideBar\">
						<div class=\"smallFonts\">
							$sideBarStuff
						</div>
					</td>
					<td class=\"post $mainBar\" id=\"post_${post['id']}\">
						<div>
							$postText
						</div>
					</td>
				</tr>
			</table>";
}
?>
