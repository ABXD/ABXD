<?php
$logText = array
(
	// register/login actions
	'register' => 'New user: {user}',
	'login' => '{user} logged in',
	'logout' => '{user} logged out',
	'loginfail' => '{user} attempted to log in as {user2}',
	'loginfail2' => '{user} attempted to log in as user "{text}"',
	'lostpass' => '{user} requested a password reset for {user2}',
	'lostpass2' => '{user} successfully reset his password.',

	// post related actions
	'newreply' => 'New reply by {user} in {thread} ({forum}): {post}',
	'editpost' => '{user} edited {user2 s} post in {thread} ({forum}): {post}',
	'deletepost' => '{user} deleted {user2 s} post in {thread} ({forum}): {post}',
	'undeletepost' => '{user} undeleted {user2 s} post in {thread} ({forum}): {post}',

	// thread related actions
	'newthread' => 'New thread by {user}: {thread}',
	'editthread' => '{user} edited {user2 s} thread {thread} ({forum})',
	'movethread' => '{user} moved {user2 s} thread {thread} from {forum} to {forum2}',
	'stickthread' => '{user} sticked {user2 s} thread {thread} ({forum})',
	'unstickthread' => '{user} unsticked {user2 s} thread {thread} ({forum})',
	'closethread' => '{user} closed {user2 s} thread {thread} ({forum})',
	'openthread' => '{user} opened {user2 s} thread {thread} ({forum})',
	'trashthread' => '{user} trashed {user2 s} thread {thread} from forum {forum}',
	'deletethread' => '{user} deleted {user2 s} thread {thread} from forum {forum}',


	// admin actions
	'edituser' => '{user} edited {user2 s} profile',
	'usercomment' => '{user} commented on {user2 s} profile',
	'pmsnoop' => '{user} read {user2 s} PM: {pm}',
	'editsettings' => '{user} edited the board\'s settings',
	'editplugsettings' => '{user} edited the settings of plugin {text}',
	'enableplugin' => '{user} enabled plugin {text}',
	'disableplugin' => '{user} disabled plugin {text}',
	//Add other log actions in here
);

// CONSIDER: most of the log texts if not all, are going to be like "{user} did action foo"
// take out the {user} part and put it in a separate column on log.php?

// TODO move the fields/callbacks from pages/log.php here and make everything use the same plugin bucket?
$bucket = 'log_texts'; include($libPath . '/pluginloader.php');

function logAction($type, $params)
{
	global $loguserid, $libPath;

	if(!isset($params["user"]))
		$params["user"] = $loguserid;

	$fields = array();
	$values = array();

	foreach ($params as $field => $val)
	{
		$fields[] = $field;
		$values[] = $val;
	}

	Query("INSERT INTO {log} (date,type,ip,".implode(',',$fields).")
		VALUES ({0},{1},{2},{3c})",
		time(), $type, $_SERVER['REMOTE_ADDR'], $values);

	$bucket = 'logaction'; include($libPath . '/pluginloader.php');
}



function doLogList($cond)
{
	global $libPath;

	$log_fields = array
	(
		'user' => array('table' => 'users', 'key' => 'id', 'fields' => '_userfields'),
		'user2' => array('table' => 'users', 'key' => 'id', 'fields' => '_userfields'),
		'thread' => array('table' => 'threads', 'key' => 'id', 'fields' => 'id,title'),
		'post' => array('table' => 'posts', 'key' => 'id', 'fields' => 'id'),
		'forum' => array('table' => 'forums', 'key' => 'id', 'fields' => 'id,title'),
		'forum2' => array('table' => 'forums', 'key' => 'id', 'fields' => 'id,title'),
		'pm' => array('table' => 'pmsgs', 'key' => 'id', 'fields' => 'id'),
	);

	$bucket = 'log_fields'; include($libPath . '/pluginloader.php');


	$joinfields = '';
	$joinstatements = '';
	foreach ($log_fields as $field=>$data)
	{
		$joinfields .= ", {$field}.({$data['fields']}) \n";
		$joinstatements .= "LEFT JOIN {{$data['table']}} AS {$field} ON l.{$field}!='0' AND {$field}.{$data['key']}=l.{$field} \n";
	}

	$logR = Query("	SELECT
						l.*
						{$joinfields}
					FROM
						{log} l
						{$joinstatements}
					WHERE $cond
					ORDER BY date DESC
					LIMIT 100"); //TODO Paging

	while($item = Fetch($logR))
	{
		$event = formatEvent($item);
		$ip = formatIP($item["ip"]);
		$cellClass = ($cellClass + 1) % 2;
		$log .= "
			<tr>
				<td class=\"cell2\">
					".str_replace(" ", "&nbsp;", TimeUnits(time() - $item['date']))."
				</td>
				<td class=\"cell$cellClass\">
					$event
				</td>
				<td class=\"cell$cellClass\">
					$ip
				</td>
			</tr>";
	}

	echo "
		<table class=\"outline margin\">
			<tr class=\"header1\">
				<th>
					".__("Time")."
				</th>
				<th>
					".__("Event")."
				</th>
				<th>
					".__("IP")."
				</th>
			</tr>
			$log
		</table>";
}

function formatEvent($item)
{
	global $logText, $me, $lastuser, $loguserid, $theItem;
	$theItem = $item;
	$me = $loguserid;
	$lastuser = -1;
	if(!isset($logText[$item['type']]))
		return "[Unknown event: ".htmlspecialchars($item['type'])."]";
	$event = $logText[$item['type']];
	$event = preg_replace_callback("@\{(\w+)( (\w+))?\}@", 'addLogInput', $event);
	$event = ucfirst($event);
	return $event;
}

function addLogInput($m)
{
	global $theItem;

	$func = 'logFormat_'.$m[1];
	$option = $m[3];
	return $func($theItem, $option);
}


function logFormat_user($data, $option)
{
	$userdata = getDataPrefix($data, 'user_');
	return formatUser($userdata, $data, $option);
}

function logFormat_user2($data, $option)
{
	$userdata = getDataPrefix($data, 'user2_');
	return formatUser($userdata, $data, $option);
}

function formatUser($userdata, $data, $option)
{
	global $me, $lastuser;
	$id = $userdata["id"];
	$possessive = $option=="s";

	if($id == $me) return $possessive ? "your" : "you";
	if($id == $lastuser)
	{
		if($userdata["sex"] == 1)
			return $possessive ? "her" : "her";
		else
			return $possessive ? "his" : "him";
	}
	else $lastuser = $id;

	if($id == 0)
		$res = "A guest from ".htmlspecialchars($data["ip"]);
	else
		$res = userLink($userdata);

	if($possessive)
		$res .= "'s";
	return $res;
}

function logFormat_text($data)
{
	return htmlspecialchars($data["text"]);
}

function logFormat_thread($data)
{
	$thread = getDataPrefix($data, "thread_");
	return makeThreadLink($thread);
}

function logFormat_post($data)
{
	return actionLinkTag('#'.$data['post_id'], 'post', $data['post_id']);
}

function logFormat_forum($data)
{
	return actionLinkTag($data['forum_title'], 'forum', $data['forum_id'], "", $data['forum_title']);
}

function logFormat_forum2($data)
{
	return actionLinkTag($data['forum2_title'], 'forum', $data['forum2_id'], "", $data['forum2_title']);
}

function logFormat_pm($data)
{
	return actionLinkTag('PM #'.$data['pm_id'], 'showprivate', $data['pm_id'], 'snoop=1');
}

