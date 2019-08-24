<?php
// AcmlmBoard XD - Ban/Unban User
// TODO: check this is secure, etc. - also cleanup because this is messy as shit -- whiteneko21

$uid = (int)$_GET['id'];

$user = Fetch(Query("SELECT * from {users} WHERE id={0}", $uid));

if ($loguser['powerlevel'] < 2)
Kill__("WMDs aren't for you.");

if ($user['powerlevel'] > 1)
Kill__("You cannot ban a staff member.");

if ($user['powerlevel'] == 0) {
$reason = $_POST['reason'];
$duration = strtotime($_POST['duration']);

if ($duration == "permanent") {
$duration = 0;
}

echo "
<form action=\"?page=ban&id=$uid\" method=\"post\">
<table class=\"outline margin form\">
		<tr class=\"header1\">
			<th colspan=\"2\">Ban user</th>
		<tr class=\"cell1\">
			<td class=\"cell2 center\">
				Duration (type \"permanent\" if permanent)
			</td>
			<td>
				<input type=\"text\" name=\"duration\" />
			</td>
		</tr>
		<tr class=\"cell0\">
			<td class=\"cell2 center\">
				Reason
			</td>
			<td>
				<input type=\"text\" name=\"reason\" />
			</td>
		</tr>
		<tr class=\"cell2\">
			<td>&nbsp;</td>
			<td>
				<input type=\"submit\" name=\"submit\" value=\"Ban user\" />
			</td>
		</tr>
	</table>
	</form>
";
if ($_POST['submit']) {
Query("UPDATE {users} SET title={2}, tempbanpl=0, tempbantime={1}, powerlevel=-1 WHERE id={0}", $uid, $duration, $reason);
redirectAction("profile", $uid);
}
}

if ($user['powerlevel'] == -1) {
Query("UPDATE {users} SET title='', powerlevel=0 WHERE id={0}", $uid);
redirectAction("profile", $uid);
}

?>
