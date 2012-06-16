<?php


function OnlineUsers($forum = 0, $update = true)
{
	global $loguserid, $dbpref;
	$forumClause = "";
	$browseLocation = __("online");
	
	if ($update)
	{
		if ($loguserid)
			Query("UPDATE {$dbpref}users SET lastforum=".$forum." WHERE id=".$loguserid);
		else
			Query("UPDATE {$dbpref}guests SET lastforum=".$forum." WHERE ip='".$_SERVER['REMOTE_ADDR']."'");
	}
       
	if($forum)
	{
		$forumClause = " and lastforum=".$forum;
		$forumName = FetchResult("SELECT title FROM {$dbpref}forums WHERE id=".$forum);
		$browseLocation = format(__("browsing {0}"), $forumName);
	}
       
	$rOnlineUsers = Query("select id,name,displayname,sex,powerlevel,lastactivity,lastposttime,minipic from {$dbpref}users where (lastactivity > ".(time()-300)." or lastposttime > ".(time()-300).")".$forumClause." order by name");
	$onlineUserCt = 0;
	while($user = Fetch($rOnlineUsers))
	{
		$bucket = "userMangler"; include("./lib/pluginloader.php");
		$loggedIn = ($user['lastpost'] <= $user['lastview']);
		$userLink = UserLink($user, "id", true);

		if(!$loggedIn)
			$userLink = "(".$userLink.")";
		$onlineUsers.=($onlineUserCt ? ", " : "").$userLink;
		$onlineUserCt++;
	}
	//$onlineUsers = $onlineUserCt." "user".(($onlineUserCt > 1 || $onlineUserCt == 0) ? "s" : "")." ".$browseLocation.($onlineUserCt ? ": " : ".").$onlineUsers;
	$onlineUsers = Plural($onlineUserCt, __("user"))." ".$browseLocation.($onlineUserCt ? ": " : ".").$onlineUsers;

	$guests = FetchResult("select count(*) from {$dbpref}guests where bot=0 and date > ".(time() - 300).$forumClause);
	$bots = FetchResult("select count(*) from {$dbpref}guests where bot=1 and date > ".(time() - 300).$forumClause);

	if($guests)
		$onlineUsers .= " | ".Plural($guests,__("guest"));
	if($bots)
		$onlineUsers .= " | ".Plural($bots,__("bot"));
	       
//	$onlineUsers = "<div style=\"display: inline-block; height: 16px; overflow: hidden; padding: 0px; line-height: 16px;\">".$onlineUsers."</div>";
	return $onlineUsers;
}



function getOnlineUsersText()
{
	global $OnlineUsersFid;
	
	$refreshCode = "";

	if(!isset($OnlineUsersFid))
		$OnlineUsersFid = 0;
		
	if(Settings::get("ajax"))
	{
		$refreshCode = format(
	"
		<script type=\"text/javascript\">
			onlineFID = {0};
			window.addEventListener(\"load\",  startOnlineUsers, false);
		</script>
	", $OnlineUsersFid);
	}

	$onlineUsers = OnlineUsers($OnlineUsersFid);

	return "<span id=\"onlineUsers\">$onlineUsers</span>$refreshCode";
}
?>
