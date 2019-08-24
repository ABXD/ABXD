<?php
//  AcmlmBoard XD - Thread display page
//  Access: all


if(isset($_GET['pid']))
{
	header("HTTP/1.1 301 Moved Permanently");
	header("Status: 301 Moved Permanently");
	redirectAction("post", $_GET["pid"]);
}

if(isset($_GET['id']))
	$tid = (int)$_GET['id'];
else
	Kill(__("Thread ID unspecified."));

AssertForbidden("viewThread", $tid);

$rThread = Query("select * from {threads} where id={0}", $tid);

if(NumRows($rThread))
	$thread = Fetch($rThread);
else
	Kill(__("Unknown thread ID."));

$fid = $thread['forum'];
AssertForbidden("viewForum", $fid);

$pl = $loguser['powerlevel'];
if($pl < 0) $pl = 0;

$rFora = Query("select * from {forums} where id={0}", $fid);
if(NumRows($rFora))
{
	$forum = Fetch($rFora);
	if($forum['minpower'] > $pl)
	{
		if($forum["id"] == Settings::get("hiddenTrashForum"))
			Kill(__("This thread is deleted."));
		else
			Kill(__("You are not allowed to browse this forum."));
	}
}
else
	Kill(__("Unknown forum ID."));

setUrlName("newreply", $tid, $thread["title"]);
setUrlName("editthread", $tid, $thread["title"]);

$threadtags = ParseThreadTags($thread['title']);
$title = $threadtags[0];

Query("update {threads} set views=views+1 where id={0} limit 1", $tid);

if(isset($_GET['from']))
	$fromstring = "from=".(int)$_GET["from"];
else
	$fromstring = "";

if(isset($_GET['vote']))
{
	AssertForbidden("vote");
	if(!$loguserid)
		Kill(__("You can't vote without logging in."));
	if($thread['closed'])
		Kill(__("Poll's closed!"));
	if(!$thread['poll'])
		Kill(__("This is not a poll."));
	if ($loguser["token"] != $_GET['token'])
		Kill(__("Invalid token."));

	$vote = (int)$_GET['vote'];

	$doublevote = FetchResult("select doublevote from {poll} where id={0}", $thread['poll']);
	$existing = FetchResult("select count(*) from {pollvotes} where poll={0} and choiceid={1} and user={2}", $thread['poll'], $vote, $loguserid);
	if($doublevote)
	{
		//Multivote.
		if ($existing)
			Query("delete from {pollvotes} where poll={0} and choiceid={1} and user={2}", $thread['poll'], $vote, $loguserid);
		else
			Query("insert into {pollvotes} (poll, choiceid, user) values ({0}, {1}, {2})", $thread['poll'], $vote, $loguserid);
	}
	else
	{
		//Single vote only?
		//Remove any old votes by this user on this poll, then add a new one.
		Query("delete from {pollvotes} where poll={0} and user={1}", $thread['poll'], $loguserid);
		if(!$existing)
			Query("insert into {pollvotes} (poll, choiceid, user) values ({0}, {1}, {2})", $thread['poll'], $vote, $loguserid);
	}
	
	redirectAction("thread", $tid, $fromstring);
	
}

if(!$thread['sticky'] && Settings::get("oldThreadThreshold") > 0 && $thread['lastpostdate'] < time() - (2592000 * Settings::get("oldThreadThreshold")))
	$replyWarning = " onclick=\"if(!confirm('".__("Are you sure you want to reply to this old thread? This will move it to the top of the list. Please only do this if you have something new and relevant to share about this thread's topic that is not better placed in a new thread.")."')) return false;\"";
if($thread['closed'])
	$replyWarning = " onclick=\"if(!confirm('".__("This thread is actually closed. Are you sure you want to abuse your staff position to post in a closed thread?")."')) return false;\"";

$links = new PipeMenu();
if($loguserid)
{
	if($loguser['powerlevel'] < 0)
		$links -> add(new PipeMenuTextEntry(__("You're banned.")));
	else if(IsAllowed("makeReply", $tid) && (!$thread['closed'] || $loguser['powerlevel'] > 2))
		$links -> add(new PipeMenuLinkEntry(__("Post reply"), "newreply", $tid, "", "comment"));
	else if(IsAllowed("makeReply", $tid))
		$links -> add(new PipeMenuTextEntry(__("Thread closed.")));

	if(CanMod($loguserid,$forum['id']) && IsAllowed("editThread", $tid))
	{
		$links -> add(new PipeMenuLinkEntry(__("Edit"), "editthread", $tid, "", "pencil"));
		if($thread['closed'])
			$links -> add(new PipeMenuLinkEntry(__("Open"), "editthread", $tid, "action=open&key=".$loguser['token'], "unlock"));
		else
			$links -> add(new PipeMenuLinkEntry(__("Close"), "editthread", $tid, "action=close&key=".$loguser['token'], "lock"));
		if($thread['sticky'])
			$links -> add(new PipeMenuLinkEntry(__("Unstick"), "editthread", $tid, "action=unstick&key=".$loguser['token'], "pushpin"));
		else
			$links -> add(new PipeMenuLinkEntry(__("Stick"), "editthread", $tid, "action=stick&key=".$loguser['token'], "pushpin"));

		if($forum['id'] != Settings::get('hiddenTrashForum'))
			$links -> add(new PipeMenuLinkEntry(__("Delete"), "editthread", $tid, "action=delete&key=".$loguser['token'], "remove"));
		if($forum['id'] != Settings::get('trashForum'))
			$links -> add(new PipeMenuLinkEntry(__("Trash"), "editthread", $tid, "action=trash&key=".$loguser['token'], "trash"));
	}
	else if($thread['user'] == $loguserid)
		$links -> add(new PipeMenuLinkEntry(__("Edit"), "editthread", $tid, "", "pencil"));
}

makeLinks($links);

$crumbs = new PipeMenu();
makeForumCrumbs($crumbs, $forum);
$crumbs->add(new PipeMenuLinkEntry($title, "thread", $tid));
makeBreadcrumbs($crumbs);


$OnlineUsersFid = $fid;
write(
"
	<script type=\"text/javascript\">
			window.addEventListener(\"load\",  hookUpControls, false);
	</script>
");


if($thread['poll'])
{
	$poll = Fetch(Query("SELECT p.*,
							(SELECT COUNT(DISTINCT user) FROM {pollvotes} pv WHERE pv.poll = p.id) as users,
							(SELECT COUNT(*) FROM {pollvotes} pv WHERE pv.poll = p.id) as votes
						 FROM {poll} p
						 WHERE p.id={0}", $thread['poll']));
						 
	if(!$poll)
		Kill(__("Poll not found"));

	$totalVotes = $poll["users"];

	$rOptions = Query("SELECT pc.*,
							(SELECT COUNT(*) FROM {pollvotes} pv WHERE pv.poll = {0} AND pv.choiceid = pc.id) as votes,
							(SELECT COUNT(*) FROM {pollvotes} pv WHERE pv.poll = {0} AND pv.choiceid = pc.id AND pv.user = {1}) as myvote
					   FROM {poll_choices} pc
					   WHERE poll={0}", $thread['poll'], $loguserid);
	$pops = 0;
	$noColors = 0;
	$defaultColors = array(
				  "#0000B6","#00B600","#00B6B6","#B60000","#B600B6","#B66700","#B6B6B6",
		"#676767","#6767FF","#67FF67","#67FFFF","#FF6767","#FF67FF","#FFFF67","#FFFFFF",);

	while($option = Fetch($rOptions))
	{
		if($option['color'] == "")
			$option['color'] = $defaultColors[($option["id"] + 9) % 15];

		$chosen = $option["myvote"]? "&#x2714;":"";

		$cellClass = ($cellClass+1) % 2;
		if($loguserid && !$thread['closed'] && IsAllowed("vote"))
			$label = $chosen." ".actionLinkTag(htmlspecialchars($option['choice']), "thread", $thread['id'], "vote=".$option["id"]."&token=".$loguser["token"]."&".$fromstring);
		else
			$label = $chosen." ".htmlspecialchars($option['choice']);
		$votes = $option["votes"];
		$bar = "&nbsp;0";
		if($totalVotes > 0)
		{
			$width = 100 * ($votes / $totalVotes);
			$alt = format("{0}&nbsp;of&nbsp;{1},&nbsp;{2}%", $votes, $totalVotes, $width);
			$bar = format("<div class=\"pollbar\" style=\"background-color: {0}; width: {1}%;\" title=\"{2}\">&nbsp;{3}</div>", $option['color'], $width, $alt, $votes);
			if($width == 0)
				$bar = "&nbsp;".$votes;
		}

		$pollLines .= "
	<tr class=\"cell$cellClass\">
		<td>
			$label
		</td>
		<td class=\"width75\">
			<div class=\"pollbarContainer\">
				$bar
			</div>
		</td>
	</tr>";
	}
	
	$voters = $poll["users"];
	$bottom = format($voters == 1 ? __("{0} user has voted so far.") : __("{0} users have voted so far."), $voters);
	if($poll["doublevote"])
		$bottom .= " ".format(__("Total votes: {0}."), $poll["votes"])." ".__("Multi-voting is enabled.");

	echo "
	<table class=\"outline margin\">
		<tr class=\"header0\">
			<th colspan=\"2\">
				".__("Poll")."
			</th>
		</tr>
		<tr class=\"cell0\">
			<td colspan=\"2\">
				".htmlspecialchars($poll['question'])."
			</td>
		</tr>
		$pollLines
		<tr class=\"cell$cellClass\">
			<td colspan=\"2\" class=\"smallFonts\">
				$bottom
			</td>
		</tr>
	</table>";
}

$rRead = Query("insert into {threadsread} (id,thread,date) values ({0}, {1}, {2}) on duplicate key update date={2}", $loguserid, $tid, time());

$total = $thread['replies'] + 1; //+1 for the OP
$ppp = $loguser['postsperpage'];
if(!$ppp) $ppp = 20;
if(isset($_GET['from']))
	$from = $_GET['from'];
else
	$from = 0;

$rPosts = Query("
			SELECT
				p.*,
				pt.text, pt.revision, pt.user AS revuser, pt.date AS revdate,
				u.(_userfields), u.(rankset,title,picture,posts,postheader,signature,signsep,lastposttime,lastactivity,regdate,globalblock),
				ru.(_userfields),
				du.(_userfields)
			FROM
				{posts} p
				LEFT JOIN {posts_text} pt ON pt.pid = p.id AND pt.revision = p.currentrevision
				LEFT JOIN {users} u ON u.id = p.user
				LEFT JOIN {users} ru ON ru.id=pt.user
				LEFT JOIN {users} du ON du.id=p.deletedby
			WHERE thread={1}
			ORDER BY date ASC LIMIT {2u}, {3u}", $loguserid, $tid, $from, $ppp);

$numonpage = NumRows($rPosts);

$pagelinks = PageLinks(actionLink("thread", $tid, "from="), $ppp, $from, $total);
if ($pagelinks) write("<div class=\"smallFonts pages\">".__("Pages:")." {0}</div>", $pagelinks);

if(NumRows($rPosts))
{
	while($post = Fetch($rPosts))
	{
		$post['closed'] = $thread['closed'];
		MakePost($post, POST_NORMAL, array('tid'=>$tid, 'fid'=>$fid));
	}
}

if ($pagelinks) write("<div class=\"smallFonts pages\">".__("Pages:")." {0}</div>", $pagelinks);


if($loguserid && $loguser['powerlevel'] >= $forum['minpowerreply'] && (!$thread['closed'] || $loguser['powerlevel'] > 0) && !isset($replyWarning))
{
	$ninja = FetchResult("select id from {posts} where thread={0} order by date desc limit 0, 1", $tid);

	//Quick reply goes here
	if(CanMod($loguserid, $fid))
	{
		//print $thread['closed'];
		if(!$thread['closed'])
			$mod .= "<label><input type=\"checkbox\" name=\"lock\">&nbsp;".__("Close thread", 1)."</label>\n";
		else
			$mod .= "<label><input type=\"checkbox\" name=\"unlock\">&nbsp;".__("Open thread", 1)."</label>\n";
		if(!$thread['sticky'])
			$mod .= "<label><input type=\"checkbox\" name=\"stick\">&nbsp;".__("Sticky", 1)."</label>\n";
		else
			$mod .= "<label><input type=\"checkbox\" name=\"unstick\">&nbsp;".__("Unstick", 1)."</label>\n";
	}
	$moodOptions = "<option ".$moodSelects[0]."value=\"0\">".__("[Default avatar]")."</option>\n";
	$rMoods = Query("select mid, name from {moodavatars} where uid={0} order by mid asc", $loguserid);

	if (!isset($nopl)) $nopl = 0;
	if (!isset($prefill)) $prefill = '';
	if (!isset($nosm)) $nosm = 0;
	if (!canMod($loguserid, $fid)) $mod = "";

	while($mood = Fetch($rMoods))
		$moodOptions .= format(
"
	<option {0} value=\"{1}\">{2}</option>
",	$moodSelects[$mood['mid']], $mood['mid'], htmlspecialchars($mood['name']));

	write(
	"
	<form action=\"".actionLink("newreply", $tid)."\" method=\"post\">
		<input type=\"hidden\" name=\"ninja\" value=\"{0}\" />
		<table class=\"outline margin width75\" style=\"margin: 4px auto;\" id=\"quickreply\">
			<tr class=\"header1\">
				<th>
					".__("Quick-E Post&trade;")."
				</th>
			</tr>
			<tr class=\"cell0\">
				<td>
					<textarea id=\"text\" name=\"text\" rows=\"8\" style=\"width: 98%;\">{3}</textarea>
				</td>
			</tr>
			<tr class=\"cell2\">
				<td>
					<input type=\"submit\" name=\"actionpost\" value=\"".__("Post")."\" />
					<input type=\"submit\" name=\"actionpreview\" value=\"".__("Preview")."\" />
					<select size=\"1\" name=\"mood\">
						{4}
					</select>
					<label>
						<input type=\"checkbox\" name=\"nopl\" {5} />&nbsp;".__("Disable post layout", 1)."
					</label>
					<label>
						<input type=\"checkbox\" name=\"nosm\" {6} />&nbsp;".__("Disable smilies", 1)."
					</label>
					<input type=\"hidden\" name=\"id\" value=\"{7}\" />
					{8}
				</td>
			</tr>
		</table>
	</form>
",	$ninja, 0, 0, $prefill, $moodOptions, $nopl, $nosm, $tid, $mod);
}

?>
