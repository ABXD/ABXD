<?php
$ajaxPage = TRUE;

if(isset($_GET['id']))
	$tid = (int)$_GET['id'];
elseif(isset($_GET['pid']))
{
	$pid = (int)$_GET['pid'];
	$qPost = "select * from {$dbpref}posts where id=".$pid;
	$rPost = Query($qPost);
	if(NumRows($rPost))
		$post = Fetch($rPost);
	else
		die(__("Unknown post ID."));
	$tid = $post['thread'];
}
else
	die(__("Thread ID unspecified."));
AssertForbidden("viewThread", $tid);

$qThread = "select * from {$dbpref}threads where id=".$tid;
$rThread = Query($qThread);
if(NumRows($rThread))
	$thread = Fetch($rThread);
else
	die(__("Unknown thread ID."));

$fid = $thread['forum'];
AssertForbidden("viewForum", $fid);

$pl = $loguser['powerlevel'];
if($pl < 0) $pl = 0;

$qFora = "select * from {$dbpref}forums where id=".$fid;
$rFora = Query($qFora);
if(NumRows($rFora))
{
	$forum = Fetch($rFora);
	if($forum['minpower'] > $pl)
		die(__("You are not allowed to browse this forum."));
}
else
	die(__("Unknown forum ID."));

$qCategories = "select * from {$dbpref}categories where id=".$forum['catid'];
$rCategories = Query($qCategories);
if(NumRows($rCategories))
	$category = Fetch($rCategories);
else
	die(__("Unknown category ID."));

$tags = ParseThreadTags($thread['title']);
$thread['title'] = strip_tags($thread['title']);
$title = $thread['title'];

write("
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en\" xml:lang=\"en\">
<head>
	<meta http-equiv=\"Content-Type\" content=\"text/html; CHARSET=utf-8\" />
	<title>{0} - {2}</title>
	<link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"img/favicon.ico\" />
</head>
<body>
	<h1>{0}</h1>
	<p>
		{1}
	</p>
", $title, formatdatenow(), htmlspecialchars(Settings::get("boardname")));

if($thread['poll'])
{
	$qPoll = "select * from {$dbpref}poll where id=".$thread['poll'];
	$rPoll = Query($qPoll);
	if(NumRows($rPoll))
	{
		$poll = Fetch($rPoll);

		$qCheck = "select * from {$dbpref}pollvotes where poll=".$thread['poll']." and user=".$loguserid;
		$rCheck = Query($qCheck);
		if(NumRows($rCheck))
		{
			while($check = Fetch($rCheck))
				$pc[$check['choice']] = "&#x2714; ";
		}

		$qVotes = "select count(*) from {$dbpref}pollvotes where poll=".$thread['poll'];
		$totalVotes = FetchResult($qVotes);

		$qOptions = "select * from {$dbpref}poll_choices where poll=".$thread['poll'];
		$rOptions = Query($qOptions);
		$pops = 0;
		$options = array();
		$voters = array();
		while($option = Fetch($rOptions))
			$options[] = $option;

		foreach($options as $option)
		{
			$option['choice'] = htmlspecialchars($option['choice']);

			$qVotes = "select * from {$dbpref}pollvotes where poll=".$thread['poll']." and choice=".$pops;
			$rVotes = Query($qVotes);
			$votes = NumRows($rVotes);
			while($vote = Fetch($rVotes))
				if(!in_array($vote['user'], $voters))
					$voters[] = $vote['user'];

			$label = format("{0} {1}", $pc[$pops], $option['choice']);
			
			if($totalVotes > 0)
			{
				$width = 100 * ($votes / $totalVotes);
				$alt = format("{0} votes, {2}%", $votes, $totalVotes, floor($width));
			}

			$pollLines .= format(
"
		<tr>
			<td>
				{0}
			</td>
			<td>
				{1}
			</td>
		</tr>
", $label, $alt);
			$pops++;
		}
		$voters = count($voters);
		write(
"
	<table border=\"2\">
		<tr>
			<th colspan=\"2\">
				Poll: {1}
			</th>
		</tr>
		{2}
		<tr>
			<td colspan=\"2\">
				{3} voted so far.
			</td>
		</tr>
	</table>
", $cellClass, htmlspecialchars($poll['question']), $pollLines, ($voters == 1 ? $voters." user has" : $voters." users have"));
	}
}

$qPosts = "select ";
$qPosts .=
	"{$dbpref}posts.id, {$dbpref}posts.date, {$dbpref}posts.deleted, {$dbpref}posts.options, {$dbpref}posts.num, {$dbpref}posts_text.text, {$dbpref}posts_text.revision, {$dbpref}users.name, {$dbpref}users.displayname, {$dbpref}users.rankset, {$dbpref}users.posts";
$qPosts .= 
	" from {$dbpref}posts left join {$dbpref}posts_text on {$dbpref}posts_text.pid = {$dbpref}posts.id and {$dbpref}posts_text.revision = {$dbpref}posts.currentrevision left join {$dbpref}users on {$dbpref}users.id = {$dbpref}posts.user";
$qPosts .= " where thread=".$tid." order by date asc";
$rPosts = Query($qPosts);

if(NumRows($rPosts))
{
	while($post = Fetch($rPosts))
	{
		$noSmiles = $post['options'] & 2;
		$noBr = $post['options'] & 4;
		$text = $post['text'];
		
		$text = preg_replace("'\[spoiler\](.*?)\[/spoiler\]'si","&laquo;Spoiler&raquo;", $text);
		$text = preg_replace("'\[video\](.*?)\[/video\]'si","&laquo;HTML5 video&raquo;", $text);
		$text = preg_replace("'\[youtube\](.*?)\[/youtube\]'si","&laquo;YouTube video&raquo;", $text);
		$text = preg_replace("'\[youtube/loop\](.*?)\[/youtube\]'si","&laquo;YouTube video&raquo;", $text);
		$text = preg_replace("'\[swf ([0-9]+) ([0-9]+)\](.*?)\[/swf\]'si","&laquo;Flash video&raquo;", $text);
		$text = preg_replace("'\[svg ([0-9]+) ([0-9]+)\](.*?)\[/svg\]'si", "&laquo;SVG image&raquo;", $text);

		$text = CleanUpPost($text, $post['name'], $noSmiles, $noBr);
		$text = preg_replace("'<div class=\"geshi\">(.*?)</div>'si","<div class=\"geshi\"><code>\\1</code></div>", $text);
		$text = preg_replace("'<table class=\"outline\">'si", "<table border=\"2\">", $text);
		$text = preg_replace("'<td (.*?)style=\"(.*?)\"(.*?)>'si", "<td \\1\\3>", $text);
		$text = preg_replace("'<a (.*?)style=\"(.*?)\"(.*?)>'si", "<a \\1\\3>", $text);

		$tags = array();
		$rankHax = $post['posts'];
		$post['posts'] = $post['num'];
		$tags = array
		(
			"numposts" => $post['num'],
			"5000" => 5000 - $post['num'],
			"20000" => 20000 - $post['num'],
			"30000" => 30000 - $post['num'],
			"rank" => GetRank($post),
		);
		$post['posts'] = $rankHax;
		$text = ApplyTags($text, $tags);
		
		write(
"
	<hr />
	<p>
		<strong>
			{1}
		</strong>
		<small>
			&mdash; {2}, post #{3}
		</small>
	</p>
	<p>
		{0}
	</p>
",	$text,
	$post['name'],
	formatdate($post['date']),
	$post['id']
	);
	}
}

?>
