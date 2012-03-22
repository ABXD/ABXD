<?php

header("Content-type: application/rss+xml");

$loguserid = 0;

//Edit the following lines to your preference.
$feedname = "AcmlmBoard XD (Cynthia IRC Style)";
$boardurl = "http://helmet.kafuka.org/nikoboard";
$description = "The latest replies on the board, mIRC style";
//</edit>

$maxPosts = 5;
$postCount = 0;

include("lib/mysql.php");
include("lib/snippets.php");
include("lib/post.php");

print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">

<channel>
	<title><?php print $feedname; ?></title>
	<link><?php print $boardurl; ?></link>
	<description><?php print $description; ?></description>
	<atom:link href="<?php print $boardurl; ?>/rss2.php" rel="self" type="application/rss+xml" />

<?php
	$qPosts = "select * from posts order by date desc limit 0, ".($maxPosts * 2);
	$rPosts = Query($qPosts);
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
			
		$qPoster = "select * from users where id=".$post['user'];
		$rPoster = Query($qPoster);
		$poster = Fetch($rPoster);

		$qText = "select text,revision from posts_text where pid=".$post['id']." order by revision desc limit 1";
		$rText = Query($qText);
		$text = Fetch($rText);
		$post = array_merge($post, $text);

		$fname = htmlentities($forum['title']);
		$tname = htmlentities($thread['title']);
		$uname = htmlentities($poster['name']);

		$link = $boardurl."?pid=".$post['id'];
		$guid = md5($post['id'].$post['date']);
		
		$tname = str_replace("[", "{[<", $tname);
		$tname = str_replace("]", ">]}", $tname);
		$tname = str_replace("{[<", "[clr]14", $tname);
		$tname = str_replace(">]}", "[clr]11", $tname);
		
		print "\n<item>\n";
		print "<title>irrelevant title</title>\n";
		print "<link>".$link."</link>\n";
		print "<pubDate>".gmdate(DATE_RFC1123, $post['date'])."</pubDate>\n";
		$reply = ($post['revision'] > 0) ? "Post edited" : "New reply";

		if($thread['replies'] < 1)
			print "<description>[clr]12New thread by [clr]11".$uname."[clr]12: \"[clr]11".$tname."[clr]12\" (".$fname.") [clr]14-> ".$link."</description>\n";		
		else
			print "<description>[clr]12".$reply." by [clr]11".$uname." [clr]12in \"[clr]11".$tname."[clr]12\" (".$fname.") [clr]14-> ".$link."</description>\n";
		print "<guid isPermaLink=\"false\">".$guid."</guid>\n";
		print "</item>\n";


		$numPosts++;
 		//if($numPosts == $maxPosts)
 			break;
 	}
?>

</channel>

</rss>