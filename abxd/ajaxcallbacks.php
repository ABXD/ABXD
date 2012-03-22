<?php

$ajaxPage = true;
include("lib/common.php");
header("Cache-Control: no-cache");

$action = $_GET['a'];
$id = (int)$_GET['id'];
$hideTricks = " <a href=\"javascript:void(0)\" onclick=\"hideTricks(".$id.")\">".__("Back")."</a>";
if($action == "q")	//Quote
{
	$qQuote = "	select
					p.id, p.deleted, pt.text,
					f.minpower,
					u.name poster
				from posts p
					left join posts_text pt on pt.pid = p.id and pt.revision = p.currentrevision
					left join threads t on t.id=p.thread
					left join forums f on f.id=t.forum
					left join users u on u.id=p.user
				where p.id=".$id;
	$rQuote = Query($qQuote);

	if(!NumRows($rQuote))
		die(__("Unknown post ID."));

	$quote = Fetch($rQuote);

	//SPY CHECK!
	//Do we need to translate this line? It's not even displayed in its true form ._.
	if($quote['minpower'] > $loguser['powerlevel'])
		$quote['text'] = str_rot13("Pools closed due to not enough power. Prosecutors will be violated.");

	if ($quote['deleted'])
		$quote['text'] = __("Post is deleted");

	$reply = "[quote=\"".$quote['poster']."\" id=\"".$quote['id']."\"]".$quote['text']."[/quote]";
	$reply = str_replace("/me", "[b]* ".htmlspecialchars($quote['poster'])."[/b]", $reply);
	die($reply);
}
else if ($action == 'rp') // retrieve post
{
	$qPost = "	SELECT
					p.id, p.date, p.num, p.deleted, p.options, p.mood, p.ip, p.thread,
					pt.text, pt.text, pt.revision,
					f.id fid,
					u.id as uid, u.name, u.displayname, u.rankset, u.powerlevel, u.title, u.sex, u.picture, u.posts, u.postheader, u.signature, u.signsep, u.lastposttime, u.lastactivity, u.regdate,
					(u.globalblock OR !ISNULL(bl.user)) layoutblocked
				FROM
					posts p
					LEFT JOIN posts_text pt ON pt.pid = p.id AND pt.revision = p.currentrevision
					LEFT JOIN users u ON u.id = p.user
					LEFT JOIN blockedlayouts bl ON bl.user=u.id AND bl.blockee=".$loguserid."
					LEFT JOIN threads t ON t.id=p.thread
					LEFT JOIN forums f ON f.id=t.forum
				WHERE p.id=".$id;
	$rPost = Query($qPost);
	if (!NumRows($rPost))
		die(__("Unknown post ID."));
	$post = Fetch($rPost);

	if (!CanMod($loguserid, $post['fid']))
		die(__("No."));

	die(MakePost($post, isset($_GET['o']) ? POST_DELETED_SNOOP : POST_NORMAL, array('tid'=>$post['thread'], 'fid'=>$post['fid'])));
}
else if($action == "ou")	//Online Users
{
	die(OnlineUsers((int)$_GET['f'], false));
}
else if($action == "tf")	//Theme File
{
	$theme = $_GET['t'];

	$themeFile = "themes/$theme/style.css";
	if(!file_exists($themeFile))
		$themeFile = "themes/$theme/style.php";

	if(file_exists("themes/$theme/logo.png"))
		$layout_logopic = themeResourceLink("logo.png");
	else if(file_exists("themes/$theme/logo.jpg"))
		$layout_logopic = themeResourceLink("logo.jpg");
	else if(file_exists("themes/$theme/logo.gif"))
		$layout_logopic = themeResourceLink("logo.gif");
	else
		$layout_logopic = resourceLink("img/logo.png");

	die($themeFile."|".$layout_logopic);
}
else if($action == "ni")	//New Indicators
{
	$pl = $loguser['powerlevel'];

	//TODO: This is copypasted from pages/index.php. Maybe make a function? ~Dirbaio
	$rFora = Query("	SELECT f.*,
						c.name cname,
						".($loguserid ? "(NOT ISNULL(i.fid))" : "0")." ignored,
						(SELECT COUNT(*) FROM threads t".($loguserid ? " LEFT JOIN threadsread tr ON tr.thread=t.id AND tr.id=".$loguserid : "")."
							WHERE t.forum=f.id AND t.lastpostdate>".($loguserid ? "IFNULL(tr.date,0)" : time()-900).") numnew,
						lu.id luid, lu.name luname, lu.displayname ludisplayname, lu.powerlevel lupowerlevel, lu.sex lusex
					FROM forums f
						LEFT JOIN categories c ON c.id=f.catid
						".($loguserid ? "LEFT JOIN ignoredforums i ON i.fid=f.id AND i.uid=".$loguserid : "")."
						LEFT JOIN users lu ON lu.id=f.lastpostuser
					WHERE f.minpower<=".$pl.(($pl < 1) ? " AND f.hidden=0" : '')."
					ORDER BY c.corder, c.id, f.forder, f.id");

	$first = true;
	while($forum = Fetch($rFora))
	{
		if(!$first) print ",";
		$first = false;
		$newstuff = $forum['ignored'] ? 0 : $forum['numnew'];
		print $newstuff;
	}
	die();
}
elseif($action == "srl")	//Show Revision List
{
	$qPost = "select currentrevision, thread from posts where id=".$id;
	$rPost = Query($qPost);
	if(NumRows($rPost))
		$post = Fetch($rPost);
	else
		die(format(__("Unknown post ID #{0}."), $id)." ".$hideTricks);

	$qThread = "select forum from threads where id=".$post['thread'];
	$rThread = Query($qThread);
	$thread = Fetch($rThread);
	$qForum = "select minpower from forums where id=".$thread['forum'];
	$rForum = Query($qForum);
	$forum = Fetch($rForum);
	if($forum['minpower'] > $loguser['powerlevel'])
		die(__("No.")." ".$hideTricks);

	$reply = __("Show revision:");
	for($i = 0; $i <= $post['currentrevision']; $i++)
		$reply .= " <a href=\"javascript:void(0)\" onclick=\"showRevision(".$id.",".$i.")\">".$i."</a>";
	$reply .= $hideTricks;
	die($reply);
}
elseif($action == "sr")	//Show Revision
{

	$qPost = "select ";
	$qPost .=
		"posts.id, posts.date, posts.num, posts.deleted, posts.options, posts.mood, posts.ip, posts_text.text, posts_text.text, posts_text.revision, users.id as uid, users.name, users.displayname, users.rankset, users.powerlevel, users.title, users.sex, users.picture, users.posts, users.postheader, users.signature, users.signsep, users.globalblock, users.lastposttime, users.lastactivity, users.regdate, posts.thread";
	$qPost .=
		" from posts left join posts_text on posts_text.pid = posts.id and posts_text.revision = ".(int)$_GET['rev']." left join users on users.id = posts.user";
	$qPost .= " where posts_text.pid=".$id;

	$rPost = Query($qPost);
	if(NumRows($rPost))
		$post = Fetch($rPost);
	else
		die(format(__("Unknown post ID #{0} or revision missing."), $id));

	$qThread = "select forum from threads where id=".$post['thread'];
	$rThread = Query($qThread);
	$thread = Fetch($rThread);
	$qForum = "select minpower from forums where id=".$thread['forum'];
	$rForum = Query($qForum);
	$forum = Fetch($rForum);
	if($forum['minpower'] > $loguser['powerlevel'])
		$post['text'] = __("No.");

	LoadBlockLayouts();
	$isBlocked = $blocklayouts[$post['uid']] /* NumRows($rBlock) */ | $post['globalblock'] | $loguser['blocklayouts'] | $post['options'] & 1;
	$noSmilies = $post['options'] & 2;

	$tags = array();
	$rankHax = $post['posts'];
	if($post['num'] == "???")
		$post['num'] = $post['posts'];
	$post['posts'] = $post['num'];
	//Disable tags by commenting/removing this part.
	$tags = array
	(
		"numposts" => $post['num'],
		"numdays" => floor((time()-$post['regdate'])/86400),
		"date" => cdate($dateformat,$post['date']),
		"rank" => GetRank($post),
	);
	$bucket = "amperTags"; include("./lib/pluginloader.php");

	$post['posts'] = $rankHax;

	if($post['postheader'] && !$isBlocked)
		$postHeader = str_replace('$theme', $theme, ApplyTags(CleanUpPost($post['postheader']), $tags));

	$postText = ApplyTags(CleanUpPost($post['text'],$post['name'],$noSmilies), $tags);

	$bucket = "postMangler"; include("./lib/pluginloader.php");

	if($post['signature'] && !$isBlocked)
	{
		$postFooter = ApplyTags(CleanUpPost($post['signature']), $tags);
		if(!$post['signsep'])
			$separator = "<br />_________________________<br />";
		else
			$separator = "<br />";
	}

	$reply = $postHeader.$postText.$separator.$postFooter;

	die($reply);
}
elseif($action == "em")	//Email
{
	$blah = FetchResult("select email from users where id=".$id." and showemail=1");
	die(htmlspecialchars($blah));
}
elseif($action == "vc")	//View Counter
{
	$blah = FetchResult("select views from misc");
	die(number_format($blah));
}

die(__("Unknown action."));
?>
