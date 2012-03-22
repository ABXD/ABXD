<?php

header("Content-type: application/rss+xml");

//Edit the following lines to your preference.
//$feedname = "AcmlmBoard XD";
//$boardurl = "http://helmet.kafuka.org/nikoboard";
//$description = "The latest replies on the board.";
//</edit>

include("lib/settings.php");
function GetFullURL()
{
	$pageURL = 'http';
	if ($_SERVER["HTTPS"] == "on")
		$pageURL .= "s";
	$pageURL .= "://";
	
	if ($_SERVER["SERVER_PORT"] != "80")
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	else
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

	return $pageURL;
}
$full = GetFullURL();
$boardurl = substr($full, 0, strrpos($full, "/"));
$description = $rssblurb;


$maxPosts = 20;
$postCount = 0;

//header('Content-Type: text/xml; charset=utf8', true);
include("lib/mysql.php");
include("lib/snippets.php");
include("lib/post.php");


if(isset($_GET['thread']))
{
	$extraWhere = "and thread = ".(int)$_GET['thread'];
	$extraUrl = "?thread=".(int)$_GET['thread'];
	$tq = mysql_query("select title from threads where id = ".(int)$_GET['thread']);
	if(mysql_num_rows($tq) == 0)
		die("Invalid thread ID");
	$tname = mysql_result($tq,0,0);
	$description = "The latest replies for \"".$tname."\"";
}
else if(isset($_GET['forum']))
{
	$extraWhere = "and threads.forum = ".(int)$_GET['forum'];
	$extraUrl = "?forum=".(int)$_GET['forum'];
	$fq = mysql_query("select title from forums where id = ".(int)$_GET['forum']);
	if(mysql_num_rows($fq) == 0)
		die("Invalid forum ID");
	$fname = mysql_result($fq,0,0);
	$description = "The latest replies for \"".$fname."\"";
	//$maxPosts *= 100;
}

print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">

<channel>
	<title><?php print $feedname; ?></title>
	<link><?php print $boardurl; ?></link>
	<description><?php print $description; ?></description>
	<atom:link href="<?php print $boardurl; ?>/rss2.php<?php print $extraUrl; ?>" rel="self" type="application/rss+xml" />

<?php
	$qPosts = "select ";
	$qPosts .=
	"posts.id, posts.thread, posts.date, posts.num, posts.deleted, posts.options, posts_text.text, posts_text.revision, users.id as uid, users.name, users.displayname, threads.forum";
	$qPosts .= 
	" from posts left join posts_text on posts_text.pid = posts.id and posts_text.revision = posts.currentrevision left join users on users.id = posts.user left join threads on threads.id = posts.thread";
	$qPosts .= " where deleted=0 ".$extraWhere." order by date desc limit 0, ".($maxPosts * 2);
	$rPosts = mysql_query($qPosts) or die(mysql_error()." --- ".$qPosts);
	while($post = Fetch($rPosts))
	{
		$qThread = "select * from threads where id=".$post['thread'];
		$rThread = Query($qThread);
		$thread = Fetch($rThread);

		$qForum = "select * from forums where id=".$thread['forum'];
		$rForum = Query($qForum);
		$forum = Fetch($rForum);
		
		if($forum['minpower'] > 0)
			continue;
		
		if(isset($_GET['forum']))
		{
			//$maxposts /= 100; 
			if($thread['forum'] != (int)$_GET['forum'])
				continue;
		}
		
		$forum['title'] = htmlentities($forum['title']);
		$thread['title'] = htmlentities($thread['title']);
		$poster['name'] = htmlentities($poster['name']);

		$poster = $post;
		$poster['id'] = $post['uid'];

		print "\n<item>\n";
		
		$reply = ($post['revision'] > 0) ? "Post edited" : "New post";

		print "<title>".$reply." by ".$poster['name']." (".$forum['title'].": ".$thread['title'].")</title>\n";
		
		$text = $post['text'];
		//Prechew the spoilers
		$text = preg_replace("'\[spoiler\](.*?)\[/spoiler]'si","(spoiler)", $text);
		$text = preg_replace("'\[spoiler=(.*?)\](.*?)\[/spoiler]'si","(spoiler)", $text);
		$text = CleanUpPost($text, $poster['name'], true);
		
		print "<link>".$boardurl."/thread.php?pid=".$post['id']."#".$post['id']."</link>\n";
		print "<pubDate>".gmdate(DATE_RFC1123, $post['date'])."</pubDate>\n";
		print "<description><![CDATA[".$text."]]></description>\n";
		print "<guid isPermaLink=\"false\">t".$thread['id']."p".$post['id']."</guid>\n";
		print "</item>\n";

		$numPosts++;
 		if($numPosts == $maxPosts)
 			break;
	}
?>

</channel>

</rss>
