<?php
//Improved permissions system ~Nina
$groups = array();
$rGroups = query("SELECT * FROM {$dbpref}usergroups");
while ($group = fetch($rGroups))
{
	$groups[] = $group;
	$groups[$grup['id']]['permissions'] = unserialize($group['permissions']);
}

//Do nothing for guests.
if ($loguserid)
{
	$rPermissions = query("SELECT * FROM {$dbpref}userpermissions WHERE uid=".$loguserid);
	$permissions = fetch($rPermissions);
	$permissions['permissions'] = unserialize($permissions['permissions']);
	if (is_array($groups[$loguser['group']]['permissions']))
		$loguser['permissions'] = array_merge($groups[$loguser['group']]['permissions'], $permissions); //$permissions overrides the group permissions here.	
	if ($loguser['powerlevel'] == 4) $loguser['group'] == "root"; //Just in case.
}

//Returns false for guests no matter what. Returns if the user is allowed to do something otherwise.
//Additionally always returns true if the user's powerlevel is root.
function checkAllowed($p)
{
	global $loguser, $loguserid;
	if (!$loguserid) return false;
	elseif ($loguser['group'] == "root" || $loguser['powerlevel'] == 4) return true;
	elseif (strpos('.', $p))
	{
		$nodes = explode(".", $p);
		$r = $loguser['permissions'];
		foreach ($nodes as $n)
			$r = $r[$node];
		return $r;
	}
	else return $loguser['permissions'][$p];
}


//Functions from old permissions system.
//I'm putting them here so we know what we have to rewrite/nuke ~Dirbaio


function CanMod($userid, $fid)
{
	global $loguser;
	// Private messages. You cannot moderate them
	if (!$fid)
		return 0;
	if($loguser['powerlevel'] > 1)
		return 1;
	if($loguser['powerlevel'] == 1)
	{
		$qMods = "select * from forummods where forum=".$fid." and user=".$userid;
		$rMods = Query($qMods);
		if(NumRows($rMods))
			return 1;
	}
	return 0;
}


function AssertForbidden($to, $specifically = 0)
{
	global $loguser, $forbidden;
	if(!isset($forbidden))
		$forbidden = explode(" ", $loguser['forbiddens']);
	
	$caught = 0;
	if(in_array($to, $forbidden))
		$caught = 1;
	else
	{
		$specific = $to."[".$specifically."]";
		if(in_array($specific, $forbidden))
			$caught = 2;
	}
	
	if($caught)
	{
		$not = __("You are not allowed to {0}.");
		$messages = array
		(
			"addRanks" => __("add new ranks"),
			"blockLayouts" => __("block layouts"),
			"deleteComments" => __("delete usercomments"),
			"editCats" => __("edit the forum categories"),
			"editForum" => __("edit the forum list"),
			"editIPBans" => __("edit the IP ban list"),
			"editMods" => __("edit Local Moderator assignments"),
			"editMoods" => __("edit your mood avatars"),
			"editPoRA" => __("edit the PoRA box"),
			"editPost" => __("edit posts"),
			"editProfile" => __("edit your profile"),
			"editSettings" => __("edit the board settings"),
			"editSmilies" => __("edit the smiley list"),
			"editThread" => __("edit threads"),
			"editUser" => __("edit users"),
			"haveCookie" => __("have a cookie"),
			"listPosts" => __("see all posts by a given user"),
			"makeComments" => __("post usercomments"),
			"makeReply" => __("reply to threads"),
			"makeThread" => __("start new threads"),
			"optimize" => __("optimize the tables"),
			"purgeRevs" => __("purge old revisions"),
			"recalculate" => __("recalculate the board counters"),
			"search" => __("use the search function"),
			"sendPM" => __("send private messages"),
			"snoopPM" => __("view other users' private messages"),
			"useUploader" => __("upload files"),
			"viewAdminRoom" => __("see the admin room"),
			"viewAvatars" => __("see the avatar library"),
			"viewCalendar" => __("see the calendar"),
			"viewForum" => __("view fora"),
			"viewLKB" => __("see the Last Known Browser table"),
			"viewMembers" => __("see the memberlist"),
			"viewOnline" => __("see who's online"),
			"viewPM" => __("view private messages"),
			"viewProfile" => __("view user profiles"),
			"viewRanks" => __("see the rank lists"),
			"viewRecords" => __("see the top scores and DB usage"),
			"viewThread" => __("read threads"),
			"viewUploader" => __("see the uploader"),
			"vote" => __("vote"),
		);
		$messages2 = array
		(
			"viewForum" => __("see this forum"),
			"viewThread" => __("read this thread"),
			"makeReply" => __("reply in this thread"),
			"editUser" => __("edit this user"),
		);
		$bucket = "forbiddens"; include("./lib/pluginloader.php");
		if($caught == 2 && array_key_exists($to, $messages2))
			Kill(format($not, $messages2[$to]), __("Permission denied."));
		Kill(format($not, $messages[$to]), __("Permission denied."));
	}
}

function IsAllowed($to, $specifically = 0)
{
	global $loguser, $forbidden;
	if(!isset($forbidden))
		$forbidden = explode(" ", $loguser['forbiddens']);
	if(in_array($to, $forbidden))
		return FALSE;
	else
	{
		$specific = $to."[".$specifically."]";
		if(in_array($specific, $forbidden))
			return FALSE;
	}
	return TRUE;
}

