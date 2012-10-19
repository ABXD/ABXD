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


$links = "";

if($loguserid)
	$links = actionLinkTagItem(__("Mark all forums read"), "board", 0, "action=markallread");

MakeCrumbs(array(), $links);

$statData = Fetch(Query("SELECT
	(SELECT COUNT(*) FROM {threads}) AS numThreads,
	(SELECT COUNT(*) FROM {posts}) AS numPosts,
	(SELECT COUNT(*) FROM {users}) AS numUsers,
	(select count(*) from {posts} where date > {0}) AS newToday,
	(select count(*) from {posts} where date > {1}) AS newLastHour,
	(select count(*) from {users} where lastposttime > {2}) AS numActive",
	 time() - 86400, time() - 3600, time() - 2592000));

$stats = Format(__("{0} and {1} total"), Plural($statData["numThreads"], __("thread")), Plural($statData["numPosts"], __("post")));
$stats .= "<br />".format(__("{0} today, {1} last hour"), Plural($statData["newToday"], __("new post")), $statData["newLastHour"]);

$percent = $statData["numUsers"] ? ceil((100 / $statData["numUsers"]) * $statData["numActive"]) : 0;
$lastUser = Query("select u.(_userfields) from {users} u order by u.regdate desc limit 1");
if(numRows($lastUser))
{
	$lastUser = getDataPrefix(Fetch($lastUser), "u_");
	$last = format(__("{0}, {1} active ({2}%)"), Plural($statData["numUsers"], __("registered user")), $statData["numActive"], $percent)."<br />".format(__("Newest: {0}"), UserLink($lastUser));
}
else
	$last = __("No registered users")."<br />&nbsp;";
	
$pl = $loguser['powerlevel'];
if($pl < 0) $pl = 0;

if($loguserid && isset($_GET['action']) && $_GET['action'] == "markallread")
{
	Query("REPLACE INTO {threadsread} (id,thread,date) SELECT {0}, {threads}.id, {1} FROM {threads}", $loguserid, time());
	die(header("Location: ".actionLink("board")));
}

printRefreshCode();
write(
"
	<table class=\"outline margin width100\" style=\"overflow: auto;\">
		<tr class=\"cell2 center\" style=\"overflow: auto;\">
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
						(SELECT COUNT(*) FROM {threads} t".($loguserid ? " LEFT JOIN {threadsread} tr ON tr.thread=t.id AND tr.id={0}" : "")."
							WHERE t.forum=f.id AND t.lastpostdate>".($loguserid ? "IFNULL(tr.date,0)" : time()-900).") numnew,
						lu.(_userfields)
					FROM {forums} f
						LEFT JOIN {categories} c ON c.id=f.catid
						".($loguserid ? "LEFT JOIN {ignoredforums} i ON i.fid=f.id AND i.uid={0}" : "")."
						LEFT JOIN {users} lu ON lu.id=f.lastpostuser
					WHERE f.minpower<={1}".(($pl < 1) ? " AND f.hidden=0" : '')."
					ORDER BY c.corder, c.id, f.forder, f.id", $loguserid, $pl);

$rMods = Query("SELECT m.forum, u.(_userfields) FROM {forummods} m LEFT JOIN {users} u ON m.user=u.id");
$mods = array();
while($mod = Fetch($rMods))
	$mods[$mod['forum']][] = getDataPrefix($mod, "u_");

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

	$newstuff = 0;
	$NewIcon = "";
	$localMods = "";

	$newstuff = $forum['ignored'] ? 0 : $forum['numnew'];
	$ignoreClass = $forum['ignored'] ? " class=\"ignored\"" : "";

	if ($newstuff > 0)
		$NewIcon = "<img src=\"img/status/new.png\" alt=\"New!\"/>".$newstuff;

	if (isset($mods[$forum['id']]))
		foreach($mods[$forum['id']] as $user)
			$localMods .= UserLink($user). ", ";

	if($localMods)
		$localMods = "<br /><small>".__("Moderated by:")." ".substr($localMods,0,strlen($localMods)-2)."</small>";

	if($forum['lastpostdate'])
	{
		$user = getDataPrefix($forum, "lu_");

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
		<th style=\"min-width:150px\">".__("Last post")."</th>
	</tr>
	{0}
</table>
",	$theList);

?>
