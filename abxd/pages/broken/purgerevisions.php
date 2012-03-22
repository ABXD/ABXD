<?php

include("lib/common.php");

AssertForbidden("purgeRevs");

if($loguser['powerlevel'] < 3)
	Kill("You're not an administrator. There is nothing for you here.");

print "<pre>";

$allrevisedposts = Query("select id, currentrevision from posts where currentrevision > 0");
while($revision = Fetch($allrevisedposts))
{
	$deletion = "delete from posts_text where pid = ".$revision['id'] . " and revision < ".$revision['currentrevision'];
	print "<b>Query:</b> ".$deletion."\n";
	Query($deletion);
}

Query("update posts set currentrevision = 0 where currentrevision > 0");
Query("update posts_text set revision = 0 where revision > 0");

?>
