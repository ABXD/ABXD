<?php
$pl = $loguser['powerlevel'];
if($pl < 0) $pl = 0;

$qFora = "select * from {$dbpref}forums where id = ".$selfsettings["forum"];
$rFora = Query($qFora);
if(NumRows($rFora))
{
	$forum = Fetch($rFora);
	if($forum['minpower'] > $pl)
		Kill(__("You are not allowed to browse this forum."));
} else
	Kill(__("Unknown forum ID."));

$fid = $forum['id'];

write('<table><tr><td style="width: 50%; border: 0px none; vertical-align: top; padding-right: 1em; padding-bottom: 1em;">');
$total = $forum['numthreads'];

if(isset($_GET['from']))
	$from = (int)$_GET['from'];
else
	$from = 0;

$tpp = 5;

print "<h2 style='text-align:center;'>Latest News</h2>";

$rThreads = Query("	SELECT 
						t.*,
						".($loguserid ? "tr.date readdate," : '')."
						$userSelectSU,
						$userSelectLU
					FROM 
						{$dbpref}threads t
						".($loguserid ? "LEFT JOIN {$dbpref}threadsread tr ON tr.thread=t.id AND tr.id=".$loguserid : '')."
						LEFT JOIN {$dbpref}users su ON su.id=t.user
						LEFT JOIN {$dbpref}users lu ON lu.id=t.lastposter
					WHERE forum=".$fid." 
					ORDER BY sticky DESC, lastpostdate DESC LIMIT ".$from.", ".$tpp);

$numonpage = NumRows($rThreads);

for($i = $tpp; $i < $total; $i+=$tpp)
	if($i == $from)
		$pagelinks .= " ".(($i/$tpp)+1);
	else
		$pagelinks .= " <a href=\"./?from=".$i."\">".(($i/$tpp)+1)."</a>";
if($pagelinks)
{
	if($from == 0)
		$pagelinks = " 1".$pagelinks;
	else
		$pagelinks = "<a href=\"./\">1</a>".$pagelinks;
	Write("<div class=\"smallFonts pages\">".__("Pages:")." {0}</div>", $pagelinks);
}

$haveStickies = 0;
$ppp = 10;

while($thread = Fetch($rThreads))
{
	$user = UserStructure($thread, "su");
	$bucket = "userMangler"; include("./lib/pluginloader.php");
	$starter = $user;
	
	$user = UserStructure($thread, "lu");
	$bucket = "userMangler"; include("./lib/pluginloader.php");
	$last = $user;

	$tags = ParseThreadTags($thread['title']);

	if($thread['sticky'] && $haveStickies == 0) $haveStickies = 1;

	$lastLink = "";
	if($thread['lastpostid'])
		$lastLink = " ".actionLinkTag("&raquo;", "thread", "", "pid=".$thread['lastpostid']."#".$thread['lastpostid']);
		
	if($thread['replies'] == 0) $lastLink = "";
	
	$subtitle = strip_tags($thread['subtitle']);
	if($subtitle != "") $subtitle = '<br>'.$subtitle;
	
	$qPosts = "select ";
	$qPosts .=
	"{$dbpref}posts.thread, {$dbpref}posts.id, {$dbpref}posts.date, {$dbpref}posts.num, {$dbpref}posts.deleted, {$dbpref}posts.options, {$dbpref}posts.mood, {$dbpref}posts.ip, {$dbpref}posts_text.text, {$dbpref}posts_text.text, {$dbpref}posts_text.revision, {$dbpref}users.id as uid, {$dbpref}users.name, {$dbpref}users.displayname, {$dbpref}users.rankset, {$dbpref}users.powerlevel, {$dbpref}users.title, {$dbpref}users.sex, {$dbpref}users.picture, {$dbpref}users.posts, {$dbpref}users.postheader, {$dbpref}users.signature, {$dbpref}users.signsep, {$dbpref}users.globalblock, {$dbpref}users.lastposttime, {$dbpref}users.lastactivity, {$dbpref}users.regdate";
	$qPosts .= 
	" from {$dbpref}posts left join {$dbpref}posts_text on {$dbpref}posts_text.pid = {$dbpref}posts.id and {$dbpref}posts_text.revision = {$dbpref}posts.currentrevision left join {$dbpref}users on {$dbpref}users.id = {$dbpref}posts.user";
	$qPosts .= " where thread=".$thread['id']." order by date asc limit 1";
	$rPosts = Query($qPosts);
	$post = Fetch($rPosts);
	
	$postdate = formatdate($post['date']);
	$posttext = CleanUpPost($post['text'],$post['name'], false, false);

	$comments = Plural($thread['replies'], "comment");
	$comments = actionLinkTag($comments, "thread", $thread['id']).". ";

	if($thread['replies'] != 0)
		$comments .="Last comment by ".UserLink($last).". $lastLink";

	$newreply = actionLinkTag("Post a comment", "newreply", $thread['id']);

	
	if($thread['sticky'])
	{
		$forumList .= "<table class='outline margin width100'>";
		$forumList .= "<tr class='cell1'><td style='border: 1px solid #000; padding:16px' colspan='2'>$posttext</td></tr>";
		$forumList .="</table>";
	}
	else
	{
		$forumList .= "<table class='outline margin width100'>";
		$forumList .= "
		<tr class=\"header1\" >
			<th style='text-align:left;'><span style='font-size:15px'>".strip_tags($thread['title'])."</span><span style='font-weight:normal;'>$subtitle</span></th>
			<th style='text-align:left; width:150px; font-weight:normal;'>Posted by ".UserLink($starter)."<br>$postdate</th>
		</tr>";
		$forumList .= "<tr class='cell1'><td colspan='2' style='padding:10px'>$posttext</td></tr>";
		$forumList .= "<tr class='cell0'><td>$comments</td><td style=\"border-left: 0px none;\">$newreply</td></tr>";
		$forumList .="</table>";
	}
}

Write($forumList);

if($pagelinks)
	Write("<div class=\"smallFonts pages\">".__("Pages:")." {0}</div>", $pagelinks);


write('</td><td style="border: 0px none; vertical-align: top; padding-right: 1em; padding-bottom: 1em;">');
?>
<table class='outline margin width100'>
<tr class="header0"><th>&nbsp;</th></tr>
<tr class='cell1'><td style='padding:16px' colspan='2'>
<?php echo CleanUpPost($selfsettings["righttext"]);?>
</td></tr></table>
<?php
$bucket = "blogxd_rightcolumn"; include("lib/pluginloader.php");
write('</td></tr></table>');
?>
