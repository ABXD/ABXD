<?php

$title = 'postfixxor';

$posts = Query("
	SELECT p.id, (SELECT MAX(pt2.revision) FROM {$dbpref}posts_text pt2 WHERE pt2.pid=pt.pid) maxrevision, pt.revision 
	FROM posts p LEFT JOIN {$dbpref}posts_text pt ON pt.pid=p.id 
	WHERE pt.revision>0 AND (pt.user=0 OR pt.date=0) 
	ORDER BY p.id ASC, pt.revision DESC");
	
if (!NumRows($posts)) die('No posts to fix');
	
while ($post = Fetch($posts))
{
	echo "POST ID {$post['id']} REV {$post['revision']} MAXREV {$post['maxrevision']}: ";
	
	$logentry = Fetch(Query("SELECT time,text FROM {$dbpref}reports WHERE text LIKE 'Post edited by %pid={$post['id']}' ORDER BY time DESC LIMIT ".($post['maxrevision'] - $post['revision']).",1"));
	if (!$logentry)
	{
		echo "no log entry found, skipping<br>";
		continue;
	}
	
	echo "log entry found: {$logentry['time']} {$logentry['text']}<br>";
	
	$match = array();
	if (!preg_match('@Post edited by \[b\](.*?)\[/\]@si', $logentry['text'], $match))
	{
		echo " * invalid log entry, skipping<br>";
		continue;
	}
	
	$userid = FetchResult("SELECT id FROM {$dbpref}users WHERE name='".justEscape($match[1])."'");
	if ($userid == -1)
	{
		echo " * user '{$match[1]}' not found, skipping<br>";
		continue;
	}
	
	echo " * revision {$post['revision']} by {$match[1]} (user ID {$userid}) on {$logentry['time']}, adjusting table entry<br>";
	Query("UPDATE {$dbpref}posts_text SET user={$userid}, date={$logentry['time']} WHERE pid={$post['id']} AND revision={$post['revision']}");
}

?>