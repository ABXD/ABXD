<?php
//  AcmlmBoard XD support - Login support

$bots = array(
	"Microsoft URL Control",
	"Yahoo! Slurp",
	"Mediapartners-Google",
	"Twiceler",
	"facebook",
	"bot","crawl","spider", //catch-all
);

$isBot = 0;
if(str_replace($bots,"x",$_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) // stristr()/stripos()?
	$isBot = 1;


//Check the amount of users right now for the records
$rMisc = Query("select * from {misc}");
$misc = Fetch($rMisc);

$rOnlineUsers = Query("select id, powerlevel, colorset, name from {users} where lastactivity > {0} or lastposttime > {0} order by name", (time()-300));

$_qRecords = "";
$onlineUsers = "";
$onlineUserCt = 0;
while($onlineUser = Fetch($rOnlineUsers))
{
	$onlineUsers .= ":".$onlineUser["id"];
	$onlineUserCt++;
}

if($onlineUserCt > $misc['maxusers'])
{
	$_qRecords = "maxusers = {0}, maxusersdate = {1}, maxuserstext = {2}";
}
//Check the amount of posts for the record
$newToday = FetchResult("select count(*) from {posts} where date > {0}", (time() - 86400));
$newLastHour = FetchResult("select count(*) from {posts} where date > {0}", (time() - 3600));
if($newToday > $misc['maxpostsday'])
{
	if($_qRecords) $_qRecords .= ", ";
	$_qRecords .= "maxpostsday = {3}, maxpostsdaydate = {1}";
}
if($newLastHour > $misc['maxpostshour'])
{
	if($_qRecords) $_qRecords .= ", ";
	$_qRecords .= "maxpostshour = {4}, maxpostshourdate = {1}";
}
if($_qRecords)
{
	$_qRecords = "update {misc} set ".$_qRecords;
	$rRecords = Query($_qRecords, $onlineUserCt, time(), $onlineUsers, $newToday, $newLastHour);
}

//Delete oldies visitor from the guest list. We may re-add him/her later.
Query("delete from {guests} where date < {0}", (time()-300));

//Lift dated Tempbans
Query("update {users} set powerlevel = tempbanpl, tempbantime = 0 where tempbantime != 0 and tempbantime < {0}", time());

//Lift dated IP Bans
Query("delete from {ipbans} where date != 0 and date < {0}", time());

//Delete expired sessions
Query("delete from {sessions} where expiration != 0 and expiration < {0}", time());

function isIPBanned($ip)
{
	$rIPBan = Query("select * from {ipbans} where instr({0}, ip)=1", $ip);
	
	$result = false;
	while($ipban = Fetch($rIPBan))
	{
		if (IPMatches($ip, $ipban['ip']))
			if ($ipban['whitelisted'])
				return false;
			else
				$result = $ipban;
	}
	return $result;
}

function IPMatches($ip, $mask) {
	return $ip === $mask || $mask[strlen($mask) - 1] === '.';
}

$ipban = isIPBanned($_SERVER['REMOTE_ADDR']);

if($ipban)
	$_GET["page"] = "ipbanned";

if(FetchResult("select count(*) from {proxybans} where instr({0}, ip)=1", $_SERVER['REMOTE_ADDR']))
	die("No.");

function doHash($data)
{
	return hash('sha256', $data, FALSE);
}

$loguser = NULL;

if(isset($_COOKIE['logsession']) && !$ipban)
{
	$session = Fetch(Query("SELECT * FROM {sessions} WHERE id={0}", doHash($_COOKIE['logsession'].$salt)));
	if($session)
	{
		$loguser = Fetch(Query("SELECT * FROM {users} WHERE id={0}", $session["user"]));
		if($session["autoexpire"])
			Query("UPDATE {sessions} SET expiration={0} WHERE id={1}", time()+10*60, $session["id"]); //10 minutes
	}
}

if($loguser)
{
	$loguser['token'] = hash('sha1', "{$loguser['id']},{$loguser['pss']},{$salt},dr567hgdf546guol89ty896rd7y56gvers9t");
	$loguserid = $loguser["id"];
}
else
{
	$loguser = array("name"=>"", "powerlevel"=>0, "threadsperpage"=>50, "postsperpage"=>20, "theme"=>Settings::get("defaultTheme"),
		"dateformat"=>"m-d-y", "timeformat"=>"h:i A", "fontsize"=>80, "timezone"=>0, "blocklayouts"=>!Settings::get("guestLayouts"),
		'token'=>hash('sha1', rand()));
	$loguserid = 0;
}

/*if($hacks['forcetheme'] != "")
	$loguser['theme'] = $hacks['forcetheme'];

if ($loguserid)
	$loguserNotifications = getNotifications($loguserid);
else
	$loguserNotifications = array();*/

function setLastActivity()
{
	global $loguserid, $isBot, $lastKnownBrowser, $ipban;

	Query("delete from {guests} where ip = {0}", $_SERVER['REMOTE_ADDR']);

	if($ipban) return;

	if($loguserid == 0)
	{
		$ua = "";
		if(isset($_SERVER['HTTP_USER_AGENT']))
			$ua = $_SERVER['HTTP_USER_AGENT'];
		Query("insert into {guests} (date, ip, lasturl, useragent, bot) values ({0}, {1}, {2}, {3}, {4})",
			time(), $_SERVER['REMOTE_ADDR'], getRequestedURL(), $ua, $isBot);
	}
	else
	{
		Query("update {users} set lastactivity={0}, lastip={1}, lasturl={2}, lastknownbrowser={3}, loggedin=1 where id={4}",
			time(), $_SERVER['REMOTE_ADDR'], getRequestedURL(), $lastKnownBrowser, $loguserid);
	}
}

?>
