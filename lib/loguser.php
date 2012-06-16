<?php
//  AcmlmBoard XD support - Login support

$bots = array(
	"Microsoft URL Control",
	"Yahoo! Slurp",
	"Mediapartners-Google",
	"Twiceler",
	"facebook",
	"bot","spider", //catch-all
);

$isBot = 0;
if(str_replace($bots,"x",$_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) // stristr()/stripos()?
	$isBot = 1;

include("browsers.php");

//Check the amount of users right now for the records
$qMisc = "select * from {$dbpref}misc";
$rMisc = Query($qMisc);
$misc = Fetch($rMisc);
$qOnlineUsers = "select id, powerlevel, sex, name from {$dbpref}users where lastactivity > ".(time()-300)." or lastposttime > ".(time()-300)." order by name";
$rOnlineUsers = Query($qOnlineUsers);
$qRecords = ""; //Thanks for inspiring me to check this out, Blackhole ;)
$onlineUsers = "";
$onlineUserCt = 0;
while($onlineUser = Fetch($rOnlineUsers))
{
	$onlineUsers .= ($onlineUserCt ? ", " : "").UserLink($onlineUser);
	$onlineUserCt++;
}
if($onlineUserCt > $misc['maxusers'])
{
	$qRecords = "maxusers = ".$onlineUserCt.", maxusersdate = ".time().", maxuserstext = '".justEscape($onlineUsers)."'";
}
//Check the amount of posts for the record
$qNewToday = "select count(*) from {$dbpref}posts where date > ".(time() - 86400);
$newToday = FetchResult($qNewToday);
$qNewLastHour = "select count(*) from {$dbpref}posts where date > ".(time() - 3600);
$newLastHour = FetchResult($qNewLastHour);
if($newToday > $misc['maxpostsday'])
{
	if($qRecords) $qRecords .= ", ";
	$qRecords .= "maxpostsday = ".$newToday.", maxpostsdaydate = ".time();
}
if($newLastHour > $misc['maxpostshour'])
{
	if($qRecords) $qRecords .= ", ";
	$qRecords .= "maxpostshour = ".$newLastHour.", maxpostshourdate = ".time();
}
if($qRecords)
{
	$qRecords = "update {$dbpref}misc set ".$qRecords;
	$rRecords = Query($qRecords);
}

//Delete oldies visitor from the guest list. We may re-add him/her later.
$qGuests = "delete from {$dbpref}guests where ip='".$_SERVER['REMOTE_ADDR']."' or date < ".(time()-300);
$rGuests = Query($qGuests);

//Lift dated Tempbans
$qTempban = "update {$dbpref}users set powerlevel = tempbanpl, tempbantime = 0 where tempbantime != 0 and tempbantime < ".time();
$rTempban = Query($qTempban);

//Lift dated IP Bans
$qIPBan = "delete from {$dbpref}ipbans where date != 0 and date < ".time();
$rIPBan = Query($qIPBan);

//Do IP Ban check
$qIPBan = "select * from {$dbpref}ipbans where instr('".$_SERVER['REMOTE_ADDR']."', ip)=1";
$rIPBan = Query($qIPBan);
if(NumRows($rIPBan))
{
	$ipban = Fetch($rIPBan);
	print "You have been ".($ipban['date'] ? "" : "<strong>permanently</strong> ")."IP-banned from this board".($ipban['date'] ? " until ".gmdate("M jS Y, G:i:s",$ipban['date'])." (GMT). That's ".TimeUnits($ipban['date']-time())." left" : "").". Attempting to get around this in any way will result in worse things.";
	exit();
}

if(FetchResult("select count(*) from {$dbpref}proxybans where instr('".$_SERVER['REMOTE_ADDR']."', ip)=1"))
	die("No.");


$logdata = unserialize(base64_decode($_COOKIE['logdata']));
$loguserid = (int)$logdata['loguserid'];
$loguserbull = $logdata['bull'];

$wantGuest = TRUE;

if($loguserid) //Are we logged in?
{
	//$qLogUser = "select * from users where id=".(int)$loguserid." and password='".justEscape($loguserpw)."'";
	$qLogUser = "select * from {$dbpref}users where id=".(int)$loguserid;
	$rLogUser = Query($qLogUser);
	if(NumRows($rLogUser)) //We have at least one result.
	{
		$loguser = Fetch($rLogUser);
		
		//Bullcheck
		$ourbull = hash('sha256', $loguser['id'].$loguser['password'].$salt.$loguser['pss'], FALSE);
		if($loguserbull == $ourbull)
		{
			$rLastView = "update {$dbpref}users set lastactivity=".time().", lastip='".$_SERVER['REMOTE_ADDR']."', lasturl='".justEscape(getRequestedURL())."', lastknownbrowser='".justEscape($lastKnownBrowser)."' where id=".$loguserid;
			if(!$ajaxPage)
				$qLastView = Query($rLastView);

			// Given that tokens are to be included in URLs, they really shouldn't be as long as a SHA256 hash
			// SHA1 with a sufficiently long salt should be enough.
			$loguser['token'] = hash('sha1', "{$loguserid},{$loguser['pss']},{$salt},dr567hgdf546guol89ty896rd7y56gvers9t");
			
			$wantGuest = FALSE;
		}
	}
}

if($wantGuest)
{
	$qGuest = "insert into {$dbpref}guests (date, ip, lasturl, useragent, bot) values (".time().", '".$_SERVER['REMOTE_ADDR']."', '".justEscape(getRequestedURL())."', '".justEscape($_SERVER['HTTP_USER_AGENT'])."', ".$isBot.")";
 	if(!$ajaxPage)
 		$rGuest = Query($qGuest);
	
	$loguser = array("name"=>"", "powerlevel"=>0, "threadsperpage"=>50, "postsperpage"=>20, "theme"=>Settings::get("defaultTheme"), 
		"dateformat"=>"m-d-y", "timeformat"=>"h:i A", "fontsize"=>80, "timezone"=>0, "blocklayouts"=>!Settings::get("guestLayouts"),
		'token'=>hash('sha1', rand()));
	$loguserid = 0;
}

if($hacks['forcetheme'] != "")
	$loguser['theme'] = $hacks['forcetheme'];

if ($loguserid)
	$loguserNotifications = getNotifications($loguserid);
else $loguserNotifications = array();

?>
