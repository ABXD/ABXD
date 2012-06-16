<?php

/* Badge ideas from way back then:
BRONZE
	Autobiographer		Completed all user profile fields
	Commentator			Left 20 comments
	Hiding Something	Edited a post of at least 100 hundred words down to ten or less
	Balloonist			Edited a post to at least five times its original word count
	Database Stuffing	Member for a year, with ten or less posts
	Upaluppa Syndrome	Edited a single post at least 15 times
SILVER
	Citizen Patrol		Knows how to backseat mod the right way
	Yearling			Active member for a year, with at least 100 posts
GOLD
	Oldbie				Active member for two years, with at least 300 posts 
	Popular				Got 200 User Comments
PLATINUM
	Hadn't come up with that then.

Upaluppa Syndrome might as well be Synthetek Syndrome for the added alliterative appeal.

Badge list from Kiyoshi:
							Bronze	Silver	Gold	Platinum
	Postcount				100		1000	5000	?
	Memberlist ranking		100		50		10		?
	Postmaster				3rd		2nd		1st		?			place in memberlist
	Editor					10x		25x		50x		?			Synthetek Syndrome
	Hysteria				20		50		100		?			consecutive caps
	Frequent Poster			10		100		500		?			posts in 24 hours
	Oldbie					1/100	3/300	5/500	?			years/posts
	Heart					120		150		200		?			karma points

Final version being implemented:

BRONZE
	Autobiographer		Completed all user profile fields
	Heart				Get 120 karma points
	Editor				Edit a single post ten times
SILVER
	Yearling			Active member for a year, with at least 100 posts
	Karma Chameleon		Get 150 karma points
	Synthetek			Edit a single post twenty times
GOLD
	Oldbie				Active member for two years, with at least 500 posts
	Dearly Beloved		Get 200 karma points
PLATINUM
	The Elder			Active member for three years, with at least 1000 posts
	Ghandi Incarnate	Get 250 karma points

*/

function CheckAutobiographer()
{
	//Assume $loguser is already up-to-date. This is done by editprofile when $loguserid == $userid.
	global $loguser, $loguserid;
	
	if($loguser['realname'] && $loguser['location'] && $loguser['birthday'] && $loguser['bio'] && $loguser['email'] && $loguser['homepageurl'] && $loguser['homepagename'])
	{
		//We use INSERT IGNORE here to remain silent.
		Query("insert ignore into {$dbpref}badges values(".$loguserid.", 'Autobiographer', 0)");
	}
}

function CheckYearling($marty = 0)
{
	//$marty is used to adjust the postcount -- we call this after updating users.posts, but without updating $loguser, and in certain other cases $loguser may actually BE up to date.
	global $loguser, $loguserid, $dbpref;
	$daysKnown = (time() - $loguser['regdate']) / 86400;
	$posts = $loguser['posts'] + $marty;
	
	if($daysKnown >= 356 * 3 && $posts >= 1000)
	{
		Query("delete from {$dbpref}badges where owner=".$loguserid." and (name='Yearling' or name='Oldbie')");
		Query("insert ignore into {$dbpref}badges values(".$loguserid.", 'The Elder', 3)");
	}
	else if($daysKnown >= 356 * 2 && $posts >= 500)
	{
		Query("delete from {$dbpref}badges where owner=".$loguserid." and name='Yearling'");
		Query("insert ignore into {$dbpref}badges values(".$loguserid.", 'Oldbie', 2)");
	}
	else if($daysKnown >= 356 && $posts >= 100)
		Query("insert ignore into {$dbpref}badges values(".$loguserid.", 'Yearling', 1)");
}

function CheckHeart($user, $karma)
{
	global $dbpref;
	//Delete the old karma badge, no matter which color it was.
	Query("delete from {$dbpref}badges where owner=".$user." and (name='Ghandi Incarnate' or name='Dearly Beloved' or name='Karma Chameleon' or name='Heart')");
	//Now insert the new one.
	if($karma >= 250)
		Query("insert into {$dbpref}badges values(".$user.", 'Ghandi Incarnate', 3)");
	else if($karma >= 200)
		Query("insert into {$dbpref}badges values(".$user.", 'Dearly Beloved', 2)");
	else if($karma >= 150)
		Query("insert into {$dbpref}badges values(".$user.", 'Karma Chameleon', 1)");
	else if($karma >= 120)
		Query("insert into {$dbpref}badges values(".$user.", 'Heart', 0)");
}

function CheckEditor()
{
	//$rev taken from editpost.
	global $loguserid, $rev, $dbpref;
	if($rev >= 20)
	{
		Query("delete from {$dbpref}badges where owner=".$loguserid." and name='Editor'");
		Query("insert into {$dbpref}badges values(".$loguserid.", 'Synthetek', 1)");
	}
	else if($rev >= 10)
	{
		Query("insert into {$dbpref}badges values(".$loguserid.", 'Editor', 0)");
	}
}

?>