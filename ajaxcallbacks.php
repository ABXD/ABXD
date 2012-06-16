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
					left join {$dbpref}posts_text pt on pt.pid = p.id and pt.revision = p.currentrevision
					left join {$dbpref}threads t on t.id=p.thread
					left join {$dbpref}forums f on f.id=t.forum
					left join {$dbpref}users u on u.id=p.user
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
					{$dbpref}posts p
					LEFT JOIN {$dbpref}posts_text pt ON pt.pid = p.id AND pt.revision = p.currentrevision
					LEFT JOIN {$dbpref}users u ON u.id = p.user
					LEFT JOIN {$dbpref}blockedlayouts bl ON bl.user=u.id AND bl.blockee=".$loguserid."
					LEFT JOIN {$dbpref}threads t ON t.id=p.thread
					LEFT JOIN {$dbpref}forums f ON f.id=t.forum
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
elseif($action == "srl")	//Show Revision List
{
	$qPost = "select currentrevision, thread from {$dbpref}posts where id=".$id;
	$rPost = Query($qPost);
	if(NumRows($rPost))
		$post = Fetch($rPost);
	else
		die(format(__("Unknown post ID #{0}."), $id)." ".$hideTricks);

	$qThread = "select forum from {$dbpref}threads where id=".$post['thread'];
	$rThread = Query($qThread);
	$thread = Fetch($rThread);
	$qForum = "select minpower from {$dbpref}forums where id=".$thread['forum'];
	$rForum = Query($qForum);
	$forum = Fetch($rForum);
	if($forum['minpower'] > $loguser['powerlevel'])
		die(__("No.")." ".$hideTricks);


	$qRevs = "SELECT 
				revision, user AS revuser, date AS revdate,
				u2.name AS ru_name, u2.displayname AS ru_dn, u2.powerlevel AS ru_power, u2.sex AS ru_sex
			FROM 
				{$dbpref}posts_text
				LEFT JOIN {$dbpref}users u2 ON u2.id = user
			WHERE pid=".$id." 
			ORDER BY revision ASC";
	$revs = Query($qRevs);
	
	
	$reply = __("Show revision:")."<br>";
	while($revision = Fetch($revs))
	{
		$reply .= " <a href=\"javascript:void(0)\" onclick=\"showRevision(".$id.",".$revision["revision"].")\">".format(__("rev. {0}"), $revision["revision"])."</a>";

			if ($revision['revuser'])
			{
				$ru_link = UserLink(array('id'=>$revision['revuser'], 'name'=>$revision['ru_name'], 'displayname'=>$revision['ru_dn'], 'powerlevel'=>$revision['ru_power'], 'sex'=>$revision['ru_sex']));
				$revdetail = " ".format(__("by {0} on {1}"), $ru_link, formatdate($revision['revdate']));
			}
			else
				$revdetail = '';
		$reply .= $revdetail;
		$reply .= "<br>";
	}
				
	$hideTricks = " <a href=\"javascript:void(0)\" onclick=\"showRevision(".$id.",".$post["currentrevision"]."); hideTricks(".$id.")\">".__("Back")."</a>";
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

	$qThread = "select forum from {$dbpref}threads where id=".$post['thread'];
	$rThread = Query($qThread);
	$thread = Fetch($rThread);
	$qForum = "select minpower from {$dbpref}forums where id=".$thread['forum'];
	$rForum = Query($qForum);
	$forum = Fetch($rForum);
	if($forum['minpower'] > $loguser['powerlevel'])
		die(__("No."));

//	die(var_dump($post));
	die(makePostText($post));
}
elseif($action == "em")	//Email
{
	$blah = FetchResult("select email from {$dbpref}users where id=".$id." and showemail=1");
	die(htmlspecialchars($blah));
}
elseif($action == "vc")	//View Counter
{
	$blah = FetchResult("select views from {$dbpref}misc");
	die(number_format($blah));
}

die(__("Unknown action."));
?>
