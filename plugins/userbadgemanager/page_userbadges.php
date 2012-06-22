<?php
//  AcmlmBoard XD Plugin - User badges management tool
//  Access: administrators only
//  Based on ipbans.php

$title = __("User Badges Manager");

AssertForbidden("editUserBadges");

if($loguser['powerlevel'] < 3)
	Kill(__("You're not an administrator. There is nothing for you here."));

MakeCrumbs(array(__("Admin") => actionLink("admin"), __("User Badges Manager") => actionLink("userbadges")), "");

if($_POST['action'] == __("Add"))
{
	if($_POST['color'] == -1 || empty($_POST['userid']) || empty($_POST['name']))
	{
		Kill(__("Please review your settings before adding a user badge."));
	}
	else
	{
		$qBadge = "insert into {$dbpref}badges values ('".$_POST['userid']."', '".$_POST['name']."', ".((int)$_POST['color']).")";
		$rBadge = Query($qBadge);
		Alert(__("Added."), __("Notice"));
	}
}
elseif($_GET['action'] == "delete")
{
	$qBadge = "delete from {$dbpref}badges where owner='".$_GET['userid']."' and name='".$_GET['name']."'";
	Query($qBadge);
	Alert(__("Removed."), __("Notice"));
}

// Fetch badges
$qBadge = "select * from {$dbpref}badges";
$rBadge = Query($qBadge);


$badgeList = "";
while($badges = Fetch($rBadge))
{
	$cellClass = ($cellClass+1) % 2;
	$colors = array(__("Bronze"),__("Silver"),__("Gold"),__("Platinum"));
	$id = $badges['owner'];
	// Fetch user
	$qUser = "select * from {$dbpref}users where id=".$id;
	$rUser = Query($qUser);
	$user = Fetch($rUser);
	// userMangler Bucket
	$bucket = "userMangler"; include("./lib/pluginloader.php");
	$badgeList .= format(
"
	<tr class=\"cell{0}\">
		<td>
			{1}
		</td>
		<td>
			{3}
		</td>
		<td>
			{4}
		</td>
		<td>
			<a href=\"".actionLink("userbadges", "", "userid={2}&name={3}&action=delete")."\">&#x2718;</a>
		</td>
	</tr>
", $cellClass, $user['name'], $user['id'], $badges['name'], $colors[$badges['color']]);
}

write("
<table class=\"outline margin width50\">
	<tr class=\"header1\">
		<th>".__("Badge Owner")."</th>
		<th>".__("Badge Name")."</th>
		<th>".__("Badge Type")."</th>
		<th>&nbsp;</th>
	</tr>
	{0}
</table>

<form action=\"".actionLink("userbadges")."\" method=\"post\">
	<table class=\"outline margin width50\">
		<tr class=\"header1\">
			<th colspan=\"2\">
				".__("Add")."
			</th>
		</tr>
		<tr>
			<td class=\"cell2\">
				".__("User ID")."
			</td>
			<td class=\"cell0\">
				<input type=\"text\" name=\"userid\" style=\"width: 98%;\" maxlength=\"25\" />
			</td>
		</tr>
		<tr>
			<td class=\"cell2\">
				".__("Name")."
			</td>
			<td class=\"cell1\">
				<input type=\"text\" name=\"name\" style=\"width: 98%;\" maxlength=\"25\" />
			</td>
		</tr>
		<tr>
			<td class=\"cell2\">
				".__("Type")."
			</td>
			<td class=\"cell1\">
				<select name=\"color\">
					<option value=\"-1\">".__("Select")."</option>
					<option value=\"0\">".__("Bronze")."</option>
					<option value=\"1\">".__("Silver")."</option>
					<option value=\"2\">".__("Gold")."</option>
					<option value=\"3\">".__("Platinum")."</option>
				</select>
			</td>
		</tr>
		<tr class=\"cell2\">
			<td></td>
			<td>
				<input type=\"submit\" name=\"action\" value=\"".__("Add")."\" />
			</td>
		</tr>
	</table>
</form>
", $badgeList);

?>
