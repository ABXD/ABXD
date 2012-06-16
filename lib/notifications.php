<?php
//ABXD notifications system ~Nina

//$scope lets you add additional conditions to the query to make the listing of notifications more specific.
function getNotifications($uid, $scope = "")
{
	global $dbpref;
	$uid = (int)$uid;
	$rNotifications = query("SELECT * FROM {$dbpref}notifications WHERE uid=".$uid.($scope != "" ? " ".$scope : "")." ORDER BY time DESC"); 
	$notifications = array();
	while (count($notifications) < numRows($rNotifications))
	{
		$notifications[] = fetch($rNotifications);
	}
	return $notifications;
}

//$type here is a string showing the message type, for example pmNotification. These types -do not- have any impact on the way they should show up, they are only for an easy way for pages to identify them.
//$description should be able to be left blank, the board should not make room for a description if there is none needed.
function newNotification($uid, $type, $title, $description, $link = false, $linkLocation = "")
{
	global $dbpref;
	query(format(
		"INSERT INTO {$dbpref}notifications (uid, type, title, description, link, linklocation, time) VALUES ({0}, '{1}', '{2}', '{3}', {4}, '{5}', {6})",
		(int)$uid, justEscape($type), justEscape($title), justEscape($description), (int)$link, justEscape($linkLocation), time()
	));
	return true;
}

function purgeNotification($id)
{
	global $dbpref;
	query("DELETE FROM {$dbpref}notifications WHERE id=".(int)$id);
	return true;
}
