<?php
//  AcmlmBoard XD - User profile page
//  Access: all

AssertForbidden("viewProfile");

if(isset($_POST['id']))
	$_GET['id'] = $_POST['id'];

if(!isset($_GET['id']))
	Kill(__("User ID unspecified."));

$id = (int)$_GET['id'];

$rUser = Query("select * from {users} where id={0}", $id);
if(NumRows($rUser))
	$user = Fetch($rUser);
else
	Kill(__("Unknown user ID."));

if($id == $loguserid)
{
	Query("update {users} set newcomments = 0 where id={0}", $loguserid);
	$loguser['newcomments'] = false;
}

$canVote = $loguserid && ($loguser['powerlevel'] > 0 || ((time()-$loguser['regdate'])/86400) > 9)
			 && IsAllowed("vote") && $loguserid != $id;

if($loguserid && ($_GET['token'] == $loguser['token'] || $_POST['token'] == $loguser['token']))
{
	if(isset($_GET['block']))
	{
		AssertForbidden("blockLayouts");
		$block = (int)$_GET['block'];
		$rBlock = Query("select * from {blockedlayouts} where user={0} and blockee={1}", $id, $loguserid);
		$isBlocked = NumRows($rBlock);
		if($block && !$isBlocked && $loguserid != $id)
			$rBlock = Query("insert into {blockedlayouts} (user, blockee) values ({0}, {1})", $id, $loguserid);
		elseif(!$block && $isBlocked)
			$rBlock = Query("delete from {blockedlayouts} where user={0} and blockee={1} limit 1", $id, $loguserid);
		die(header("Location: ".actionLink("profile", $id)));
	}
	if(isset($_GET['vote']) && $canVote)
	{
		$vote = (int)$_GET['vote'];
		if($vote > 1) $vote = 1 ;
		if($vote < -1) $vote = -1;
		// TODO: this could be considerably simplified
		// (INSERT ... ON DUPLICATE KEY UPDATE and primary index on uid+voter)
		$k = FetchResult("select count(*) from {uservotes} where uid={0} and voter={1}", $id, $loguserid);
		if($k == 0)
			$_qKarma = "insert into {uservotes} (uid, voter, up) values ({0}, {1}, {2})";
		else
			$_qKarma = "update {uservotes} set up={2} where uid={0} and voter={1}";
		$rKarma = Query($_qKarma, $id, $loguserid, $vote);
		$user['karma'] = RecalculateKarma($id);
		die(header("Location: ".actionLink("profile", $id)));
	}
}


$karma = $user['karma'];
if($canVote)
{
	$k = FetchResult("select up from {uservotes} where uid={0} and voter={1}", $id, $loguserid);

	$karmalinks = "";
	if($k != 1) $karmaLinks .= actionLinkTag("&#x2191;", "profile", $id, "vote=1&token={$loguser['token']}");
	if($k != 0) $karmaLinks .= actionLinkTag("&#x2193;", "profile", $id, "vote=0&token={$loguser['token']}");

	$karmaLinks = "<small>[$karmaLinks]</small>";
}
else
	$karmaLinks = "";

$daysKnown = (time()-$user['regdate'])/86400;
$posts = FetchResult("select count(*) from {posts} where user={0}", $id);
$threads = FetchResult("select count(*) from {threads} where user={0}", $id);
$averagePosts = sprintf("%1.02f", $user['posts'] / $daysKnown);
$averageThreads = sprintf("%1.02f", $threads / $daysKnown);
$score = ((int)$daysKnown * 2) + ($posts * 4) + ($threads * 8) + (($karma - 100) * 3);

$minipic = getMinipicTag($user);

if($user['rankset'])
{
	$currentRank = GetRank($user["rankset"], $user["posts"]);
	$toNextRank = GetToNextRank($user["rankset"], $user["posts"]);
	if($toNextRank)
		$toNextRank = Plural($toNextRank, "post");
}
if($user['title'])
	$title = str_replace("<br />", " &bull; ", strip_tags(CleanUpPost($user['title'], "", true), "<b><strong><i><em><span><s><del><img><a><br><br /><small>"));

if($user['homepageurl'])
{
	$nofollow = "";
	if(Settings::get("nofollow"))
		$nofollow = "rel=\"nofollow\"";

	if($user['homepagename'])
		$homepage = "<a $nofollow target=\"_blank\" href=\"".htmlspecialchars($user['homepageurl'])."\">".htmlspecialchars($user['homepagename'])."</a> - ".htmlspecialchars($user['homepageurl']);
	else
		$homepage = "<a $nofollow target=\"_blank\" href=\"".htmlspecialchars($user['homepageurl'])."\">".htmlspecialchars($user['url'])."</a>";
}

$emailField = __("Private");
if($user['email'] == "")
	$emailField = __("None given");
elseif($user['showemail'])
	$emailField = "<span id=\"emailField\">".__("Public")." <button style=\"font-size: 0.7em;\" onclick=\"$(this.parentNode).load('{$boardroot}ajaxcallbacks.php?a=em&amp;id=".$id."');\">".__("Show")."</button></span>";

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
$foo[__("Name")] = $minipic . htmlspecialchars($user['displayname'] ? $user['displayname']." (".$user['name'].")" : $user['name']);
$foo[__("Power")] = getPowerlevelName($user['powerlevel']);
$foo[__("Sex")] = getSexName($user['sex']);
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

$lastPost = Fetch(Query("
	SELECT
		p.id as pid, p.date as date,
		{threads}.title AS ttit, {threads}.id AS tid,
		{forums}.title AS ftit, {forums}.id AS fid, {forums}.minpower
	FROM {posts} p
		LEFT JOIN {users} u on u.id = p.user
		LEFT JOIN {threads} on {threads}.id = p.thread
		LEFT JOIN {forums} on {threads}.forum = {forums}.id
	WHERE p.user={0}
	ORDER BY p.date DESC
	LIMIT 0, 1", $user["id"]));

if($lastPost)
{
	$thread = array();
	$thread["title"] = $lastPost["ttit"];
	$thread["id"] = $lastPost["tid"];

	$realpl = $loguser["powerlevel"];
	if($realpl < 0) $realpl = 0;
	if($lastPost["minpower"] > $realpl)
		$place = __("a restricted forum.");
	else
	{
		$pid = $lastPost["pid"];
		$place = makeThreadLink($thread)." (".actionLinkTag($lastPost["ftit"], "forum", $lastPost["fid"], "", $lastPost["ftit"]).")";
		$place .= " &raquo; ".actionLinkTag($pid, "post", $pid);
	}
	$foo[__("Last post")] = format("{0} ({1} ago)", formatdate($lastPost["date"]), TimeUnits(time() - $lastPost["date"])) .
								"<br>".__("in")." ".$place;
}
else
	$foo[__("Last post")] = __("Never");

$foo[__("Last view")] = format("{0} ({1} ago)", formatdate($user['lastactivity']), TimeUnits(time() - $user['lastactivity']));
$foo[__("Score")] = $score;
$foo[__("Browser")] = $user['lastknownbrowser'];
if($loguser['powerlevel'] > 0)
	$foo[__("Last known IP")] = formatIP($user['lastip']);
$profileParts[__("General information")] = $foo;

$foo = array();
$foo[__("Email address")] = $emailField;
if($homepage)
	$foo[__("Homepage")] = $homepage;
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
	$foo[__("Real name")] = htmlspecialchars($user['realname']);
if($user['location'])
	$foo[__("Location")] = htmlspecialchars($user['location']);
if($user['birthday'])
	$floo[__("Birthday")] = formatBirthday($user['birthday']);

if(count($foo))
	$profileParts[__("Personal information")] = $foo;

if($user['bio'])
	$profileParts[__("Bio")] = array("" => CleanUpPost($user['bio'], $user['displayname'] ? $user['displayname'] : $user['name']));

$badgersR = Query("select * from {badges} where owner={0} order by color", $id);
if(NumRows($badgersR))
{
	$badgers = "";
	$colors = array("bronze", "silver", "gold", "platinum");
	while($badger = Fetch($badgersR))
		$badgers .= Format("<span class=\"badge {0}\">{1}</span> ", $colors[$badger['color']], $badger['name']);
	$profileParts['General information']['Badges'] = $badgers;
}

$bucket = "profileTable"; include($libPath . "/pluginloader.php");

if(!$mobileLayout)
	echo "
	<table>
		<tr>
			<td style=\"width: 60%; border: 0px none; vertical-align: top; padding-right: 1em; padding-bottom: 1em;\">";

echo "<table class=\"outline margin\">";

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
		if($label)
			write("
								<tr>
									<td class=\"cell2\">{0}</td>
									<td class=\"cell{2}\">{1}</td>
								</tr>
	", str_replace(" ", "&nbsp;", $label), $value, $cc);
		else
			write("
								<tr>
									<td colspan=\"2\" class=\"cell{2}\">{1}</td>
								</tr>
	", str_replace(" ", "&nbsp;", $label), $value, $cc);
	}
}

write("
				</table>
");

$bucket = "profileLeft"; include($libPath . "/pluginloader.php");

if(!$mobileLayout)
{
	write("
				</td>
				<td style=\"vertical-align: top; border: 0px none;\">
	");

	include("usercomments.php");

	print "
				</td>
			</tr>
		</table>";
}

$previewPost['text'] = Settings::get("profilePreviewText");

$previewPost['num'] = "_";
$previewPost['id'] = "_";

foreach($user as $key => $value)
	$previewPost["u_".$key] = $value;

MakePost($previewPost, POST_SAMPLE);


$links = new PipeMenu();
if($mobileLayout)
	$links -> add(new PipeMenuLinkEntry(__("Comments"), "usercomments", $id, "", "comments"));
if(IsAllowed("editProfile") && $loguserid == $id)
	$links -> add(new PipeMenuLinkEntry(__("Edit my profile"), "editprofile", "", "", "pencil"));
else if(IsAllowed("editUser") && $loguser['powerlevel'] > 2)
	$links -> add(new PipeMenuLinkEntry(__("Edit user"), "editprofile", $id, "", "pencil"));

if(IsAllowed("snoopPM") && $loguser['powerlevel'] > 2)
	$links -> add(new PipeMenuLinkEntry(__("Show PMs"), "private", $id, "", "eye-open"));

if($loguserid && IsAllowed("sendPM"))
	$links -> add(new PipeMenuLinkEntry(__("Send PM"), "sendprivate", "", "uid=".$id, "envelope"));
if(IsAllowed("listPosts"))
		$links -> add(new PipeMenuLinkEntry(__("Show posts"), "listposts", $id, "", "copy"));
if(IsAllowed("listThreads"))
		$links -> add(new PipeMenuLinkEntry(__("Show threads"), "listthreads", $id, "", "list"));


if(IsAllowed("blockLayouts") && $loguserid)
{
	$rBlock = Query("select * from {blockedlayouts} where user={0} and blockee={1}", $id, $loguserid);
	$isBlocked = NumRows($rBlock);
	if($isBlocked)
		$links -> add(new PipeMenuLinkEntry(__("Unblock layout"), "profile", $id, "block=0&token={$loguser['token']}", "ban-circle"));
	else if($id != $loguserid)
		$links -> add(new PipeMenuLinkEntry(__("Block layout"), "profile", $id, "block=1&token={$loguser['token']}", "ban-circle"));
}

makeLinks($links);

$uname = $user["name"];
if($user["displayname"])
	$uname = $user["displayname"];

$crumbs = new PipeMenu();
$crumbs->add(new PipeMenuLinkEntry(__("Member list"), "memberlist"));
$crumbs->add(new PipeMenuHtmlEntry(userLink($user)));
makeBreadcrumbs($crumbs);

$title = format(__("Profile for {0}"), htmlspecialchars($uname));


?>
