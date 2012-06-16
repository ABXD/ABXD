<?php
//  AcmlmBoard XD - User profile page
//  Access: all

AssertForbidden("viewProfile");

if(isset($_POST['id']))
	$_GET['id'] = $_POST['id'];

if(!isset($_GET['id']))
	Kill(__("User ID unspecified."));

$id = (int)$_GET['id'];

$qUser = "select * from {$dbpref}users where id=".$id;
$rUser = Query($qUser);
if(NumRows($rUser))
	$user = Fetch($rUser);
else
	Kill(__("Unknown user ID."));
$bucket = "userMangler"; include("./lib/pluginloader.php");

if($id == $loguserid)
{
	Query("update {$dbpref}users set newcomments = 0 where id=".$loguserid);
	$loguser['newcomments'] = false;
}

$canDeleteComments = ($id == $loguserid || $loguser['powerlevel'] > 2) && IsAllowed("deleteComments");

if(isset($_GET['block']) && $loguserid && $_GET['token'] == $loguser['token'])
{
	AssertForbidden("blockLayouts");
	$block = (int)$_GET['block'];
	$qBlock = "select * from {$dbpref}blockedlayouts where user=".$id." and blockee=".$loguserid;
	$rBlock = Query($qBlock);
	$isBlocked = NumRows($rBlock);
	if($block && !$isBlocked)
	{
		$qBlock = "insert into {$dbpref}blockedlayouts (user, blockee) values (".$id.", ".$loguserid.")";
		$rBlock = Query($qBlock);
		Alert(__("Layout blocked."), __("Notice"));
	}
	elseif(!$block && $isBlocked)
	{
		$qBlock = "delete from {$dbpref}blockedlayouts where user=".$id." and blockee=".$loguserid." limit 1";
		$rBlock = Query($qBlock);
		Alert(__("Layout unblocked."), __("Notice"));
	}
}

$canVote = ($loguser['powerlevel'] > 0 || ((time()-$loguser['regdate'])/86400) > 9) && IsAllowed("vote");
if($loguserid == $id) $canVote = FALSE;

if($loguserid)
{
	if(IsAllowed("blockLayouts"))
	{
		$qBlock = "select * from {$dbpref}blockedlayouts where user=".$id." and blockee=".$loguserid;
		$rBlock = Query($qBlock);
		$isBlocked = NumRows($rBlock);
		if($isBlocked)
			$blockLayoutLink = actionLinkTagItem(__("Unblock layout"), "profile", $id, "block=0&token={$loguser['token']}");
		else
			$blockLayoutLink = actionLinkTagItem(__("Block layout"), "profile", $id, "block=1&token={$loguser['token']}");
	}

	if(isset($_GET['vote']) && $canVote && $_GET['token'] == $loguser['token'])
	{
		$vote = (int)$_GET['vote'];
		if($vote > 1) $vote = 1 ;
		if($vote < -1) $vote = -1;
		$k = FetchResult("select count(*) from {$dbpref}uservotes where uid=".$id." and voter=".$loguserid);
		if($k == 0)
			$qKarma = "insert into uservotes (uid, voter, up) values (".$id.", ".$loguserid.", ".$vote.")";
		else
			$qKarma = "update {$dbpref}uservotes set up=".$vote." where uid=".$id." and voter=".$loguserid;
		$rKarma = Query($qKarma);
		$user['karma'] = RecalculateKarma($id);
	}

	$qKarma = "select up from {$dbpref}uservotes where uid=".$id." and voter=".$loguserid;
	$k = FetchResult($qKarma);
	
	$karmalinks = "";
	if($k != 1)
		$karmaLinks .= actionLinkTag("&#x2191;", "profile", $id, "vote=1&token={$loguser['token']}");
		
	if($k != 0)
		$karmaLinks .= actionLinkTag("&#x2193;", "profile", $id, "vote=0&token={$loguser['token']}");
		
	$karmaLinks = "<small>[$karmaLinks]</small>";
}

$karma = $user['karma'];
if(!$canVote)
	$karmaLinks = "";

$daysKnown = (time()-$user['regdate'])/86400;

$qPosts = "select count(*) from {$dbpref}posts where user=".$id;
$posts = FetchResult($qPosts);

$qThreads = "select count(*) from {$dbpref}threads where user=".$id;
$threads = FetchResult($qThreads);

$averagePosts = sprintf("%1.02f", $user['posts'] / $daysKnown);
$averageThreads = sprintf("%1.02f", $threads / $daysKnown);

$score = ((int)$daysKnown * 2) + ($posts * 4) + ($threads * 8) + (($karma - 100) * 3);

if($user['minipic'])
	$minipic = "<img src=\"".$user['minipic']."\" alt=\"\" style=\"vertical-align: middle;\" />&nbsp;";

if($user['rankset'])
{
	$currentRank = GetRank($user);
	$toNextRank = GetToNextRank($user);
	if($toNextRank)
		$toNextRank = Plural($toNextRank, "post");
}
if($user['title'])
	$title = str_replace("<br />", " &bull; ", strip_tags(CleanUpPost($user['title'], "", true), "<b><strong><i><em><span><s><del><img><a><br><small>"));
//$title = "";

if($user['homepageurl'])
{
	if($user['homepagename'])
		$homepage = "<a target=\"_blank\" href=\"".htmlspecialchars($user['homepageurl'])."\">".htmlspecialchars($user['homepagename'])."</a> - ".htmlspecialchars($user['homepageurl']);
	else
		$homepage = "<a target=\"_blank\" href=\"".htmlspecialchars($user['homepageurl'])."\">".htmlspecialchars($user['url'])."</a>";
}

$emailField = __("Private");
if($user['email'] == "")
{
	$emailField = __("None given");
}
elseif($user['showemail'])
{
	$emailField = "<span id=\"emailField\">".__("Public")." <button style=\"font-size: 0.7em;\" onclick=\"$(this.parentNode).load('ajaxcallbacks.php?a=em&amp;id=".$id."');\">".__("Show")."</button></span>";
}

if($user['tempbantime'])
{
	write(
"
	<div class=\"outline margin cell1 smallFonts\">
		".__("This user has been temporarily banned until {0} (GMT). That's {1} left.")."
	</div>
",	gmdate("M jS Y, G:i:s",$user['tempbantime']), TimeUnits($user['tempbantime'] - time())
	);
}



$profileParts = array();

$foo = array();
$foo[__("Name")] = $minipic . htmlspecialchars($user['displayname'] ? $user['displayname'] : $user['name']) . ($user['displayname'] ? " (".$user['name'].")" : "");
if($title)
	$foo[__("Title")] = $title;
if($currentRank)
	$foo[__("Rank")] = $currentRank;
if($toNextRank)
	$foo[__("To next rank")] = $toNextRank;
$foo[__("Karma")] = $karma.$karmaLinks;
$foo[__("Total posts")] = format("{0} ({1} per day)", $posts, $averagePosts);
$foo[__("Total threads")] = format("{0} ({1} per day)", $threads, $averageThreads);
$foo[__("Registered on")] = format("{0} ({1} ago)", formatdate($user['regdate']), TimeUnits($daysKnown*86400));
$foo[__("Score")] = $score;
$foo[__("Browser")] = $user['lastknownbrowser'];
if($loguser['powerlevel'] > 0)
	$foo[__("Last known IP")] = $user['lastip'] . " " . IP2C($user['lastip']);	
$profileParts[__("General information")] = $foo;

$foo = array();
$foo[__("Email address")] = $emailField;
if($homepage)
	$foo[__("Homepage")] = securityPostFilter($homepage);
$profileParts[__("Contact information")] = $foo;

$foo = array();
$infofile = "themes/".$user['theme']."/themeinfo.txt";

$themeinfo = file_get_contents($infofile);
$themeinfo = explode("\n", $themeinfo, 2);

if(file_exists($infofile))
{
	$themename = trim($themeinfo[0]);
	$themeauthor = trim($themeinfo[1]);
}
else
{
	$themename = $user['theme'];
	$themeauthor = "";
}
$foo[__("Theme")] = $themename;
$foo[__("Items per page")] = Plural($user['postsperpage'], __("post")) . ", " . Plural($user['threadsperpage'], __("thread"));
$profileParts[__("Presentation")] = $foo;

$foo = array();
if($user['realname'])
	$foo[__("Real name")] = strip_tags($user['realname']);
if($user['location'])
	$foo[__("Location")] = strip_tags($user['location']);
if($user['birthday'])
	$foo[__("Birthday")] = format("{0} ({1} old)", cdate("F j, Y", $user['birthday']), Plural(floor((time() - $user['birthday']) / 86400 / 365.2425), "year"));
if($user['bio'])
	$foo[__("Bio")] = CleanUpPost($user['bio']);
if(count($foo))
	$profileParts[__("Personal information")] = $foo;

$badgersR = Query("select * from {$dbpref}badges where owner=".$id." order by color");
if(NumRows($badgersR))
{
	$badgers = "";
	$colors = array("bronze", "silver", "gold", "platinum");
	while($badger = Fetch($badgersR))
		$badgers .= Format("<span class=\"badge {0}\">{1}</span> ", $colors[$badger['color']], $badger['name']);
	$profileParts['General information']['Badges'] = $badgers;
}

$prepend = "";
$bucket = "profileTable"; include("./lib/pluginloader.php");

write("
	<table>
		<tr>
			<td style=\"width: 60%; border: 0px none; vertical-align: top; padding-right: 1em; padding-bottom: 1em;\">
				{0}
				<table class=\"outline margin\">
", $prepend);
$cc = 0;
foreach($profileParts as $partName => $fields)
{
	write("
					<tr class=\"header0\">
						<th colspan=\"2\">{0}</th>
					</tr>
", $partName);
	foreach($fields as $label => $value)
	{
		$cc = ($cc + 1) % 2;
		write("
							<tr>
								<td class=\"cell2\">{0}</td>
								<td class=\"cell{2}\">{1}</td>
							</tr>
", str_replace(" ", "&nbsp;", $label), $value, $cc);
	}
}

write("
				</table>
");

$bucket = "profileLeft"; include("./lib/pluginloader.php");
write("
			</td>
");

if($canDeleteComments && $_GET['action'] == "delete" && $_GET['token'] == $loguser['token'])
{
	AssertForbidden("deleteComments");
	Query("delete from {$dbpref}usercomments where uid=".$id." and id=".(int)$_GET['cid']);
}

if($_POST['action'] == __("Post") && IsReallyEmpty(strip_tags($_POST['text'])) && $loguserid 
	/*&& $loguserid != $lastCID*/ && $_POST['token'] == $loguser['token'])
{
	AssertForbidden("makeComments");
	$_POST['text'] = strip_tags($_POST['text']);
	$newID = FetchResult("SELECT id+1 FROM {$dbpref}usercomments WHERE (SELECT COUNT(*) FROM {$dbpref}usercomments u2 WHERE u2.id={$dbpref}usercomments.id+1)=0 ORDER BY id ASC LIMIT 1");
	if($newID < 1) $newID = 1;
	$qComment = "insert into {$dbpref}usercomments (id, uid, cid, date, text) values (".$newID.", ".$id.", ".$loguserid.", ".time().", '".justEscape($_POST['text'])."')";
	$rComment = Query($qComment);
	if($loguserid != $id)
		Query("update {$dbpref}users set newcomments = 1 where id=".$id);
}


$qComments = "select {$dbpref}users.name, {$dbpref}users.displayname, {$dbpref}users.powerlevel, {$dbpref}users.sex, {$dbpref}usercomments.id, {$dbpref}usercomments.cid, {$dbpref}usercomments.text from {$dbpref}usercomments left join {$dbpref}users on {$dbpref}users.id = {$dbpref}usercomments.cid where uid=".$id." order by {$dbpref}usercomments.date desc limit 0,10";
$rComments = Query($qComments);
$commentList = "";
$commentField = "";
if(NumRows($rComments))
{
	while($comment = Fetch($rComments))
	{
		if($canDeleteComments)
			$deleteLink = "<small style=\"float: right; margin: 0px 4px;\">".
				actionLinkTag("&#x2718;", "profile", $id, "action=delete&cid=".$comment['id']."&token={$loguser['token']}")."</small>";
		$cellClass = ($cellClass+1) % 2;
		$thisComment = format(
"
						<tr>
							<td class=\"cell2 width25\">
								{0}
							</td>
							<td class=\"cell{1}\">
								{3}{2}
							</td>
						</tr>
",	UserLink($comment, "cid"), $cellClass, CleanUpPost($comment['text']), $deleteLink);
		$commentList = $thisComment . $commentList;
		if(!isset($lastCID))
			$lastCID = $comment['cid'];
	}
}
else
{
	$commentsWasEmpty = true;
	$commentList = $thisComment = format(
"
						<tr>
							<td class=\"cell0\" colspan=\"2\">
								".__("No comments.")."

							</td>
						</tr>
");
}

//print "lastCID: ".$lastCID;

if($loguserid)
{
	$commentField = format(
"
								<div>
									<form method=\"post\" action=\"".actionLink("profile")."\">
										<input type=\"hidden\" name=\"id\" value=\"{0}\" />
										<input type=\"text\" name=\"text\" style=\"width: 80%;\" maxlength=\"255\" />
										<input type=\"submit\" name=\"action\" value=\"".__("Post")."\" />
										<input type=\"hidden\" name=\"token\" value=\"{$loguser['token']}\" />
									</form>
								</div>
", $id);
//	if($lastCID == $loguserid)
//		$commentField = __("You already have the last word.");
	if(!IsAllowed("makeComments"))
		$commentField = __("You are not allowed to post usercomments.");
}

write(
"
			<td style=\"vertical-align: top; border: 0px none;\">
				<table class=\"outline margin\">
					<tr class=\"header1\">
						<th colspan=\"2\">
							".__("Comments about {0}")."
						</th>
					</tr>
					{1}
					<tr>
						<td colspan=\"2\" class=\"cell2\">
							{2}
						</td>
					</tr>
				</table>
",	UserLink($user), $commentList, $commentField);

$bucket = "profileRight"; include("./lib/pluginloader.php");

write(
"
			</td>
		</tr>
	</table>
");

$previewPost['text'] = Settings::get("profilePreviewText");

$previewPost['num'] = "preview";
$previewPost['id'] = "preview";
$previewPost['uid'] = $id;
$copies = explode(",","title,name,displayname,picture,sex,powerlevel,avatar,postheader,rankset,signature,signsep,posts,regdate,lastactivity,lastposttime");
foreach($copies as $toCopy)
	$previewPost[$toCopy] = $user[$toCopy];

$previewPost['activity'] = FetchResult("select count(*) from {$dbpref}posts where user = ".$id." and date > ".(time() - 86400), 0, 0);

$previewPost['layoutblocked'] = $user['globalblock'] || FetchResult("SELECT COUNT(*) FROM {$dbpref}blockedlayouts WHERE user=".$user['id']." AND blockee=".$loguserid);

MakePost($previewPost, POST_SAMPLE);


if(IsAllowed("editProfile") && $loguserid == $id)
	$links .= actionLinkTagItem(__("Edit my profile"), "editprofile", $id);
else if(IsAllowed("editUser") && $loguser['powerlevel'] > 2)
	$links .= actionLinkTagItem(__("Edit user"), "editprofile", $id);

if(IsAllowed("snoopPM") && $loguser['powerlevel'] > 2)
	$links .= actionLinkTagItem(__("Show PMs"), "private", "", "user=".$id);

if($loguserid && IsAllowed("sendPM"))
	$links .= actionLinkTagItem(__("Send PM"), "sendprivate", "", "uid=".$id);
if(IsAllowed("listPosts"))
		$links .= actionLinkTagItem(__("Show posts"), "listposts", $id);

$links .= $blockLayoutLink;

$uname = $user["name"];
if($user["displayname"])
	$uname = $user["displayname"];
MakeCrumbs(array(__("Member list")=>actionLink("memberlist"), $uname => actionLink("profile", $id)), $links);

$title = "Profile for ".htmlspecialchars($user['name']);

function IsReallyEmpty($subject)
{
	$trimmed = trim(preg_replace("/&.*;/", "", $subject));
	return strlen($trimmed) != 0;
}

function IP2C($ip)
{
	global $dblink;
	$q = @$dblink->query("select {$dbpref}cc from ip2c where ip_from <= inet_aton('".$ip."') and ip_to >= inet_aton('".$ip."')") or $r['cc'] = "";
	if($q) $r = @$q->fetch_array();
	if($r['cc'])
		return " <img src=\"img/flags/".strtolower($r['cc']).".png\" alt=\"".$r['cc']."\" title=\"".$r['cc']."\" />";
}


?>
