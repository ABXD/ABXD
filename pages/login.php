<?php
//  AcmlmBoard XD - Login page
//  Access: guests

if($_POST['action'] == "logout")
{
	setcookie("logdata", 0);

	die(header("Location: ."));
}
elseif($_POST['action'] == __("Log in"))
{
	$okay = true;
	$original = $_POST['pass'];
	$escapedName = justEscape($_POST['name']);
	$qUser = "select * from {$dbpref}users where name='".$escapedName."'";
	$rUser = Query($qUser);
	if(NumRows($rUser))
	{
		$user = Fetch($rUser);
		$sha = hash("sha256", $original.$salt.$user['pss'], FALSE);
		if($user['password'] != $sha)
		{
			Report("A visitor from [b]".$_SERVER['REMOTE_ADDR']."[/] tried to log in as [b]".$user['name']."[/].", 1);
			Alert(__("Invalid user name or password."));
			$okay = false;
		}
	}
	else
	{
		Alert(__("Invalid user name or password."));
		$okay = false;
	}

	if($okay)
	{
		$logdata['loguserid'] = $user['id'];
		$logdata['bull'] = hash('sha256', $user['id'].$user['password'].$salt.$user['pss'], FALSE);
		$logdata_s = base64_encode(serialize($logdata));

		if(isset($_POST['session']))
			setcookie("logdata", $logdata_s, 0, "", "", false, true);
		else
			setcookie("logdata", $logdata_s, 2147483647, "", "", false, true);

		Report("[b]".$escapedName."[/] logged in.", 1);

		die(header("Location: ."));
	}
}

write(
"
	<form action=\"".actionLink("login")."\" method=\"post\">
		<table class=\"outline margin width50\">
			<tr class=\"header0\">
				<th colspan=\"2\">
					".__("Log in")."
				</th>
			</tr>
			<tr>
				<td class=\"cell2\">
					<label for=\"un\">".__("User name")."</label>
				</td>
				<td class=\"cell0\">
					<input type=\"text\" id=\"un\" name=\"name\" style=\"width: 98%;\" maxlength=\"25\" />
				</td>
			</tr>
			<tr>
				<td class=\"cell2\">
					<label for=\"pw\">".__("Password")."</label>
				</td>
				<td class=\"cell1\">
					<input type=\"password\" id=\"pw\" name=\"pass\" size=\"13\" maxlength=\"32\" />
				</td>
			</tr>
			<tr>
				<td class=\"cell2\"></td>
				<td class=\"cell1\">
					<label>
						<input type=\"checkbox\" name=\"session\" />
						".__("This session only")."
					</label>
				</td>
			</tr>
			<tr class=\"cell2\">
				<td></td>
				<td>
					<input type=\"submit\" name=\"action\" value=\"".__("Log in")."\" />
					{0}
				</td>
			</tr>
		</table>
	</form>
",  $mailResetFrom == "" ? "" : "<button onclick=\"document.location = '".actionLink("lostpass")."'; return false;\">".__("Forgot password?")."</button>"
);

?>
