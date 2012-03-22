<?php
//  AcmlmBoard XD - Thread display page
//  Access: all


if(isset($_GET['id']))
	$tid = (int)$_GET['id'];

elseif(isset($_GET['pid']))
{
	$pid = (int)$_GET['pid'];
	$qPost = "select * from posts where id=".$pid;
	$rPost = Query($qPost);
	if(NumRows($rPost))
		$post = Fetch($rPost);
	else
		Kill(__("Unknown post ID."));
	$tid = $post['thread'];
} else
	Kill(__("Thread ID unspecified."));
AssertForbidden("viewThread", $tid);

$qThread = "select * from threads where id=".$tid;
$rThread = Query($qThread);
if(NumRows($rThread))
	$thread = Fetch($rThread);
else
	Kill(__("Unknown thread ID."));

$fid = $thread['forum'];
AssertForbidden("viewForum", $fid);

$pl = $loguser['powerlevel'];
if($pl < 0) $pl = 0;

$qFora = "select * from forums where id=".$fid;
$rFora = Query($qFora);
if(NumRows($rFora))
{
	$forum = Fetch($rFora);
	if($forum['minpower'] > $pl)
		Kill(__("You are not allowed to browse this forum."));
}
else
	Kill(__("Unknown forum ID."));

$qCategories = "select * from categories where id=".$forum['catid'];
$rCategories = Query($qCategories);
if(NumRows($rCategories))
{
	$category = Fetch($rCategories);
}
else
	Kill(__("Unknown category ID."));

$tags = ParseThreadTags($thread['title']);
$thread['title'] = strip_tags($thread['title']);
$title = $thread['title'];

$qViewCounter = "update threads set views=".($thread['views']+1)." where id=".$tid." limit 1";
$rViewCounter = Query($qViewCounter);

if(isset($_GET['vote']))
{
	AssertForbidden("vote");
	if(!$loguserid)
		Kill(__("You can't vote without logging in."));
	if($thread['closed'])
		Kill(__("Poll's closed!"));
	if($thread['poll'])
	{
		$vote = (int)$_GET['vote'];
		
		$token = hash('sha256', "{$vote},{$loguserid},{$salt}");
		if ($token != $_GET['token'])
			Kill(__("Invalid token."));
		
		$doublevote = FetchResult("select doublevote from poll where id=".$thread['poll']);
		if($doublevote)
		{
			//Multivote.
			$existing = FetchResult("select count(*) from pollvotes where poll=".$thread['poll']." and choice=".$vote." and user=".$loguserid);
			if ($existing)
				Query("delete from pollvotes where poll=".$thread['poll']." and choice=".$vote." and user=".$loguserid);
			else
				Query("insert into pollvotes (poll, choice, user) values (".$thread['poll'].", ".$vote.", ".$loguserid.")");
		}
		else
		{
			//Single vote only?
			//Remove any old votes by this user on this poll, then add a new one.
			Query("delete from pollvotes where poll=".$thread['poll']." and user=".$loguserid);
			Query("insert into pollvotes (poll, choice, user) values (".$thread['poll'].", ".$vote.", ".$loguserid.")");
		}
	}
	else
		Kill(__("This is not a poll."));
}

if(!$thread['sticky'] && $warnMonths > 0 && $thread['lastpostdate'] < time() - (2592000 * $warnMonths))
	$replyWarning = " onclick=\"if(!confirm('".__("Are you sure you want to reply to this old thread? This will move it to the top of the list. Please only do this if you have something new and relevant to share about this thread's topic that is not better placed in a new thread.")."')) return false;\"";
if($thread['closed'])
	$replyWarning = " onclick=\"if(!confirm('".__("This thread is actually closed. Are you sure you want to abuse your staff position to post in a closed thread?")."')) return false;\"";

if($loguser['powerlevel'] < 0)
	$links .= "<li>".__("You're banned.");
elseif(IsAllowed("makeReply", $tid) && (!$thread['closed'] || $loguser['powerlevel'] > 2))
	$links .= actionLinkTagItem(__("Post reply"), "newreply", $tid);
elseif(IsAllowed("makeReply", $tid))
	$links .= "<li>".__("Thread closed.");
if(CanMod($loguserid,$forum['id']) && IsAllowed("editThread", $tid))
{
	$key = hash('sha256', "{$loguserid},{$loguser['pss']},{$salt}");
	
	$links .= actionLinkTagItem(__("Edit"), "editthread", $tid);
	if($thread['closed'])
		$links .= actionLinkTagItem(__("Open"), "editthread", $tid, "action=open&amp;key=".$key);
	else
		$links .= actionLinkTagItem(__("Close"), "editthread", $tid, "action=close&amp;key=".$key);
	if($thread['sticky'])
		$links .= actionLinkTagItem(__("Unstick"), "editthread", $tid, "action=unstick&amp;key=".$key);
	else
		$links .= actionLinkTagItem(__("Stick"), "editthread", $tid, "action=stick&amp;key=".$key);
	$links .= actionLinkTagItemConfirm(__("Delete"), __("Are you sure you want to just up and delete this whole thread?"), "editthread", $tid, "action=delete&amp;key=".$key);
	
	if(strpos($forum['description'],"[trash]") === FALSE)
		$links .= actionLinkTagItem(__("Trash"), "editthread", $tid, "action=trash&amp;key=".$key);
}
else if($thread['user'] == $loguserid)
	$links .= actionLinkTagItem(__("Edit"), "editthread", $tid);

if($isBot)
	$links = "";

$OnlineUsersFid = $fid;
write(
"
	<script type=\"text/javascript\">
			window.addEventListener(\"load\",  hookUpControls, false);
	</script>
");

MakeCrumbs(array(__("Main")=>"./", $forum['title']=>actionLink("forum", $fid), $thread['title']." ".$tags=>actionLink("thread", $tid)), $links);

if($thread['poll'])
{
	$qPoll = "select * from poll where id=".$thread['poll'];
	$rPoll = Query($qPoll);
	if(NumRows($rPoll))
	{
		$poll = Fetch($rPoll);

		$qCheck = "select * from pollvotes where poll=".$thread['poll']." and user=".$loguserid;
		$rCheck = Query($qCheck);
		if(NumRows($rCheck))
		{
			while($check = Fetch($rCheck))
				$pc[$check['choice']] = "&#x2714; "; //use &#x2605; for a star
		}

		$qVotes = "select count(*) from pollvotes where poll=".$thread['poll'];
		$totalVotes = FetchResult($qVotes);

		$qOptions = "select * from poll_choices where poll=".$thread['poll'];
		$rOptions = Query($qOptions);
		$pops = 0;
		$options = array();
		$voters = array();
		$noColors = 0;
		$defaultColors = array(
					  "#0000B6","#00B600","#00B6B6","#B60000","#B600B6","#B66700","#B6B6B6",
			"#676767","#6767FF","#67FF67","#67FFFF","#FF6767","#FF67FF","#FFFF67","#FFFFFF",);
		while($option = Fetch($rOptions))
			$options[] = $option;

		foreach($options as $option)
		{			
			if($option['color'] == "")
				$option['color'] = $defaultColors[($pops + 9) % 15];
				
			$option['choice'] = htmlspecialchars($option['choice']);

			$qVotes = "select * from pollvotes where poll=".$thread['poll']." and choice=".$pops;
			$rVotes = Query($qVotes);
			$votes = NumRows($rVotes);
			while($vote = Fetch($rVotes))
				if(!in_array($vote['user'], $voters))
					$voters[] = $vote['user'];

			$cellClass = ($cellClass+1) % 2;
			if($loguserid && !$thread['closed'] && IsAllowed("vote"))
			{
				$token = hash('sha256', "{$pops},{$loguserid},{$salt}");
				$label = $pc[$pops]." ".actionLinkTag($option['choice'], "thread", $thread['id'], "vote=$pops&token=$token");
			}
			else
				$label = format("{0} {1}", $pc[$pops], $option['choice']);
			
			$bar = "&nbsp;0";
			if($totalVotes >= 0)
			{
				$width = 99 * ($votes / $totalVotes) + 0.1;
				$alt = format("{0}&nbsp;of&nbsp;{1},&nbsp;{2}%", $votes, $totalVotes, $width);
				$bar = format("<div class=\"pollbar\" style=\"background-color: {0}; width: {1}%;\" title=\"{2}\">&nbsp;{3}</div>", $option['color'], $width, $alt, $votes);
				if($width == 0)
					$bar = "&nbsp;".$votes;
			}

			$pollLines .= format(
"
		<tr class=\"cell{0}\">
			<td>
				{1}
			</td>
			<td class=\"width75\">
				<div class=\"pollbarContainer\">
					{2}
				</div>
			</td>
		</tr>
", $cellClass, $label, $bar);
			$pops++;
		}
		$voters = count($voters);
		write(
"
	<table class=\"outline margin\">
		<tr class=\"header0\">
			<th colspan=\"2\">
				".__("Poll")."
			</th>
		</tr>
		<tr class=\"cell0\">
			<td colspan=\"2\">
				{1}
			</td>
		</tr>
		{2}
		<tr class=\"cell0\">
			<td colspan=\"2\" class=\"smallFonts\">
				{3}
			</td>
		</tr>
	</table>
",	$cellClass, htmlspecialchars($poll['question']), $pollLines,
	format($voters == 1 ? __("{0} user has voted so far") : __("{0} users have voted so far"), $voters));
	}
}

$qRead = "delete from threadsread where id=".$loguserid." and thread=".$tid;
$rRead = Query($qRead);
$qRead = "insert into threadsread (id,thread,date) values (".$loguserid.", ".$tid.", ".time().")";
$rRead = Query($qRead);

$activity = array();
$rActivity = Query("select user, count(*) num from posts where date > ".(time() - 86400)." group by user");
while($act = Fetch($rActivity))
	$activity[$act['user']] = $act['num'];

$total = $thread['replies'] + 1; //+1 for the OP
$ppp = $loguser['postsperpage'];
if(!$ppp) $ppp = 20;
if(isset($_GET['from']))
	$from = $_GET['from'];
else
	if(isset($pid))
		//$from = (floor(FetchResult("select count(*) from posts where thread=".$tid." and id < ".$pid) / $ppp)) * $ppp;
		$from = (floor(FetchResult("SELECT COUNT(*) FROM posts WHERE thread=".$tid." AND date<=".$post['date']." AND id!=".$pid) / $ppp)) * $ppp;
	else
		$from = 0;

$qPosts = "	SELECT 
				p.id, p.date, p.num, p.deleted, p.options, p.mood, p.ip, 
				pt.text, pt.text, pt.revision, 
				u.id as uid, u.name, u.displayname, u.rankset, u.powerlevel, u.title, u.sex, u.picture, u.posts, u.postheader, u.signature, u.signsep, u.lastposttime, u.lastactivity, u.regdate,
				(u.globalblock OR !ISNULL(bl.user)) layoutblocked
			FROM 
				posts p 
				LEFT JOIN posts_text pt ON pt.pid = p.id AND pt.revision = p.currentrevision 
				LEFT JOIN users u ON u.id = p.user
				LEFT JOIN blockedlayouts bl ON bl.user=u.id AND bl.blockee=".$loguserid."
			WHERE thread=".$tid." 
			ORDER BY date ASC LIMIT ".$from.", ".$ppp;

$rPosts = Query($qPosts);
$numonpage = NumRows($rPosts);

$pagelinks = PageLinks(actionLink("thread", $tid, "from="), $ppp, $from, $total);
if ($pagelinks) write("<div class=\"smallFonts pages\">".__("Pages:")." {0}</div>", $pagelinks);

if(NumRows($rPosts))
{
	while($post = Fetch($rPosts))
	{
		$user = $post;
		$bucket = "userMangler"; include("./lib/pluginloader.php");
		$post = $user;
		//$poster = $post;
		//$poster['id'] = $post['uid'];
		$post['activity'] = $activity[$post['uid']];
		$post['closed'] = $thread['closed'];
		MakePost($post, POST_NORMAL, array('tid'=>$tid, 'fid'=>$fid));
	}
}

if($loguserid && $loguser['powerlevel'] >= $forum['minpowerreply'] && (!$thread['closed'] || $loguser['powerlevel'] > 0) && !isset($replyWarning))
{
	$ninja = FetchResult("select id from posts where thread=".$tid." order by date desc limit 0, 1",0,0);
	
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
	$rMoods = Query("select mid, name from moodavatars where uid=".$loguserid." order by mid asc");
	while($mood = Fetch($rMoods))
		$moodOptions .= format(
"
	<option {0} value=\"{1}\">{2}</option>
",	$moodSelects[$mood['mid']], $mood['mid'], htmlspecialchars($mood['name']));

	write(
	"
	<form action=\"".actionLink("newreply")."\" method=\"post\">
		<input type=\"hidden\" name=\"ninja\" value=\"{0}\" />
		<table class=\"outline margin width75\" style=\"margin: 4px auto;\" id=\"quickreply\">
			<tr class=\"header1\">
				<th onclick=\"expandTable('quickreply', this)\" style=\"cursor: pointer;\">
					".__("Quick-E Post&trade;")."
				</th>
			</tr>
			<tr class=\"cell0\" style=\"display: none;\">
				<td>
					<textarea id=\"text\" name=\"text\" rows=\"16\" style=\"width: 98%;\">{3}</textarea>
				</td>
			</tr>
			<tr class=\"cell2\" style=\"display: none;\">
				<td>
					<input type=\"submit\" name=\"action\" value=\"".__("Post")."\" /> 
					<input type=\"submit\" name=\"action\" value=\"".__("Preview")."\" />
					<select size=\"1\" name=\"mood\">
						{4}
					</select>
					<label>
						<input type=\"checkbox\" name=\"nopl\" {5} />&nbsp;".__("Disable post layout", 1)."
					</label>
					<label>
						<input type=\"checkbox\" name=\"nosm\" {6} />&nbsp;".__("Disable smilies", 1)."
					</label>
					<label>
						<input type=\"checkbox\" name=\"nobr\" {9} />&nbsp;".__("Disable auto-<br>", 1)."
					</label>
					<input type=\"hidden\" name=\"id\" value=\"{7}\" />
					{8}
				</td>
			</tr>
		</table>
	</form>
",	$ninja, htmlspecialchars($postingAsUser['name']), $_POST['password'], $prefill, $moodOptions, $nopl, $nosm, $tid, $mod, $nobr);
}

if ($pagelinks) write("<div class=\"smallFonts pages\">".__("Pages:")." {0}</div>", $pagelinks);

?>
