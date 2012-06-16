<?php

$title = "Admin Cruft";

if ($loguser['powerlevel'] < 4)
	Kill('No.');

$shitbugs = @file_get_contents('shitbugs.dat');
$shitbugs = $shitbugs ? unserialize($shitbugs) : array();

echo "
	<table class=\"outline margin width100\">
		<tr class=\"header0\">
			<th>
				Date
			</th>
			<th>
				IP
			</th>
			<th>
				Matching users
			</th>
			<th>
				&nbsp;
			</th>
		</tr>
";

foreach ($shitbugs as $foo)
{
	$date = formatdate($foo['date']);
	
	$userlisting = '';
	$users = Query("SELECT name FROM {$dbpref}users WHERE lastip='{$foo['ip']}' ORDER BY name");
	while ($user = Fetch($users))
		$userlisting .= htmlspecialchars($user['name']).', ';
		
	if (!$userlisting) $userlisting = 'None';
	else $userlisting = substr($userlisting, 0, strlen($userlisting)-2);
	
	echo "
		<tr class=\"cell0\">
			<td>
				{$date}
			</td>
			<td>
				{$foo['ip']}
			</td>
			<td>
				{$userlisting}
			</td>
			<td>
				<form action=\"".actionLink('ipbans')."\" method=\"post\">
					<input type=\"hidden\" name=\"ip\" value=\"{$foo['ip']}\">
					<input type=\"hidden\" name=\"reason\" value=\"mean script kiddy\">
					<input type=\"hidden\" name=\"days\" value=\"0\">
					<input type=\"hidden\" name=\"action\" value=\"".__('Add')."\">
					<input type=\"submit\" value=\"BAN!!\">
				</form>
			</td>
		</tr>
";
}

echo "
	</table>
";

?>
