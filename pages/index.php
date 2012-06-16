<?php
$noAutoHeader = TRUE;

if(isset($_GET['fid']) && (int)$_GET['fid'] > 0 && !isset($_GET['action']))
	die(header("Location: forum.php?id=".(int)$_GET['fid']));
else if(isset($_GET['tid']) && (int)$_GET['tid'] > 0)
	die(header("Location: thread.php?id=".(int)$_GET['tid']));
else if(isset($_GET['uid']) && (int)$_GET['uid'] > 0)
	die(header("Location: profile.php?id=".(int)$_GET['uid']));
else if(isset($_GET['pid']) && (int)$_GET['pid'] > 0)
	die(header("Location: thread.php?pid=".(int)$_GET['pid']."#".(int)$_GET['pid']));
    

$links = actionLinkTagItem(__("Mark all forums read"), "index", 0, "action=markallread");
MakeCrumbs(array(), $links);

$numThreads = FetchResult("select count(*) from {$dbpref}threads");
$numPosts = FetchResult("select count(*) from {$dbpref}posts");
$stats = Format(__("{0} and {1} total"), Plural($numThreads, __("thread")), Plural($numPosts, __("post")));

$newToday = FetchResult("select count(*) from {$dbpref}posts where date > ".(time() - 86400));
$newLastHour = FetchResult("select count(*) from {$dbpref}posts where date > ".(time() - 3600));
$stats .= "<br />".format(__("{0} today, {1} last hour"), Plural($newToday, __("new post")), $newLastHour);

$numUsers = FetchResult("select count(*) from {$dbpref}users");
$numActive = FetchResult("select count(*) from {$dbpref}users where lastposttime > ".(time() - 2592000)); //30 days
$percent = $numUsers ? ceil((100 / $numUsers) * $numActive) : 0;
$rLastUser = Query("select id,name,displayname,powerlevel,sex from {$dbpref}users order by regdate desc limit 1");
$lastUser = Fetch($rLastUser);
$last = format(__("{0}, {1} active ({2}%)"), Plural($numUsers, __("registered user")), $numActive, $percent)."<br />".format(__("Newest: {0}"), UserLink($lastUser));

$pl = $loguser['powerlevel'];
if($pl < 0) $pl = 0;

if($loguserid && $_GET['action'] == "markallread")
{
	Query("REPLACE INTO {$dbpref}threadsread (id,thread,date) SELECT ".$loguserid.", {$dbpref}threads.id, ".time()." FROM {$dbpref}threads");
}

printRefreshCode();
write(
"
	<table class=\"outline margin width100 smallFonts\" style=\"overflow: auto;\">
		<tr class=\"header0 cell2 center\" style=\"overflow: auto;\">
		<td>
			<div style=\"float: left; width: 25%;\">&nbsp;<br />&nbsp;</div>
			<div style=\"float: right; width: 25%;\">{1}</div>
			<div class=\"center\">
				{0}
			</div>
		</td>
		</tr>
	</table>
",	$stats, $last);

$lastCatID = -1;
$rFora = Query("	SELECT f.*, 
						c.name cname,
						".($loguserid ? "(NOT ISNULL(i.fid))" : "0")." ignored,
						(SELECT COUNT(*) FROM {$dbpref}threads t".($loguserid ? " LEFT JOIN {$dbpref}threadsread tr ON tr.thread=t.id AND tr.id=".$loguserid : "")."
							WHERE t.forum=f.id AND t.lastpostdate>".($loguserid ? "IFNULL(tr.date,0)" : time()-900).") numnew,
						lu.id luid, lu.name luname, lu.displayname ludisplayname, lu.powerlevel lupowerlevel, lu.sex lusex
					FROM {$dbpref}forums f
						LEFT JOIN {$dbpref}categories c ON c.id=f.catid
						".($loguserid ? "LEFT JOIN {$dbpref}ignoredforums i ON i.fid=f.id AND i.uid=".$loguserid : "")."
						LEFT JOIN {$dbpref}users lu ON lu.id=f.lastpostuser
					WHERE f.minpower<=".$pl.(($pl < 1) ? " AND f.hidden=0" : '')."
					ORDER BY c.corder, c.id, f.forder, f.id");

$rMods = Query("SELECT m.forum, u.id, u.name, u.displayname, u.powerlevel, u.sex FROM {$dbpref}forummods m LEFT JOIN {$dbpref}users u ON m.user=u.id");
$mods = array();
while($mod = Fetch($rMods))
	$mods[$mod['forum']][] = $mod;

$theList = "";
while($forum = Fetch($rFora))
{
	$skipThisOne = false;
	$bucket = "forumListMangler"; include("./lib/pluginloader.php");
	if($skipThisOne)
		continue;

	if($forum['catid'] != $lastCatID)
	{
		$lastCatID = $forum['catid'];
		$theList .= format(
"
		<tr class=\"header0\">
			<th colspan=\"5\">
				{0}
			</th>
		</tr>
", $forum['cname']);
	}

	$forum['description'] = str_replace("[trash]","",$forum['description']);
	$newstuff = 0;
	$NewIcon = "";
	$localMods = "";

	$newstuff = $forum['ignored'] ? 0 : $forum['numnew'];
	$ignoreClass = $forum['ignored'] ? " class=\"ignored\"" : "";

	if ($newstuff > 0)
		$NewIcon = "<img src=\"img/status/new.png\" alt=\"New!\"/>".$newstuff;

	if ($mods[$forum['id']])
	{
		foreach($mods[$forum['id']] as $user)
		{
			$bucket = "userMangler"; include("./lib/pluginloader.php");
			$localMods .= UserLink($user). ", ";
		}
	}

	if($localMods)
		$localMods = "<br /><small>".__("Moderated by:")." ".substr($localMods,0,strlen($localMods)-2)."</small>";

	if($forum['lastpostdate'])
	{
		$user = array('id'=>$forum['luid'], 'name'=>$forum['luname'], 'displayname'=>$forum['ludisplayname'], 'powerlevel'=>$forum['lupowerlevel'], 'sex'=>$forum['lusex']);
		$bucket = "userMangler"; include("./lib/pluginloader.php");
		
		$lastLink = "";
		if($forum['lastpostid'])
			$lastLink = actionLinkTag("&raquo;", "thread", 0, "pid=".$forum['lastpostid']."#".$forum['lastpostid']);
		$lastLink = format("<span class=\"nom\">{0}<br />".__("by")." </span>{1} {2}", formatdate($forum['lastpostdate']), UserLink($user), $lastLink);
	}
	else
		$lastLink = "----";


	$theList .= 
"
		<tr class=\"cell1\">
			<td class=\"cell2 threadIcon newMarker\">
				$NewIcon
			</td>
			<td>
				<h4 $ignoreClass>".
					actionLinkTag($forum['title'], "forum",  $forum['id']) . "
				</h4>
				<span $ignoreClass class=\"nom\">
					{$forum['description']}
					$localMods
				</span>
			</td>
			<td class=\"center cell2\">
				{$forum['numthreads']}
			</td>
			<td class=\"center cell2\">
				{$forum['numposts']}
			</td>
			<td class=\"smallFonts center\">
				$lastLink
			</td>
		</tr>";
}

write(
"
<table class=\"outline margin\" id=\"mainTable\">
	<tr class=\"header1\">
		<th style=\"width: 20px\"></th>
		<th style=\"width: 75%\">".__("Forum title")."</th>
		<th>".__("Threads")."</th>
		<th>".__("Posts")."</th>
		<th style=\"width: 15%\">".__("Last Post")."</th>
	</tr>
	{0}
</table>
",	$theList);

?>
