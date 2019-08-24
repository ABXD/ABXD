<?php
//  AcmlmBoard XD - Login page
//  Access: guests

function validateConvertPassword($pass, $hash, $salt, $type)
{
	if($type == "IPB")
		return $hash === md5(md5($salt).md5($pass));
		
	return false;
}

// This is needed to keep up to date with new hashing settings.
// From https://gist.github.com/nikic/3707231#rehashing-passwords
function isValidPassword($password, $hash, $uid)
{
    if (!password_verify($password, $hash))
        return false;

    if (password_needs_rehash($hash, PASSWORD_DEFAULT))
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        Query('UPDATE {users} SET password = {0} WHERE id = {1}', $hash, $uid);
    }

    return true;
}

$crumbs = new PipeMenu();
$crumbs->add(new PipeMenuLinkEntry(__("Log in"), "login"));
makeBreadcrumbs($crumbs);

if(isset($_POST['action']) && $_POST['action'] == "logout")
{
	setcookie("logsession", "", 2147483647, $boardroot, "", false, true);
	Query("UPDATE {users} SET loggedin = 0 WHERE id={0}", $loguserid);
	Query("DELETE FROM {sessions} WHERE id={0}", doHash($_COOKIE['logsession'].$salt));

	logAction('logout', array());
	die(header("Location: $boardroot"));
}
elseif(isset($_POST['actionlogin']))
{
	$okay = false;
	$pass = $_POST['pass'];

	$user = Fetch(Query("select * from {users} where name={0}", $_POST['name']));
	if($user)
	{
		//Find out if the user has a legacy password stored.
		if($user["convertpassword"])
		{
			//If he has one, validate it.
			if(validateConvertPassword($pass, $user["convertpassword"], $user["convertpasswordsalt"], $user["convertpasswordtype"]))
			{
				//If the user has entered password correctly, upgrade it to ABXD hash and wipe the legacy hash.
				$newsalt = Shake();
				$password = password_hash($pass, PASSWORD_DEFAULT);
				query("UPDATE {users} SET convertpassword='', convertpasswordsalt='', convertpasswordtype='', password={0}, pss={1} WHERE id={2}", $password, $newsalt, $user["id"]);
				
				//Login successful.
				$okay = true;
			}
		}
		else
		{
			// Check for the password. (new type)
			if (isValidPassword($pass, $user['password'], $user['id']))
				$okay = true;
			else
			{
				// Check for the legacy ABXD password and convert it to the new password.
				$sha = doHash($pass.$salt.$user['pss']);
				if ($user['password'] == $sha)
				{
					$password = password_hash($pass, PASSWORD_DEFAULT);

					Query("UPDATE {users} SET password = {0} WHERE id={1}", $password, $user['id']);
					$okay = true;
				}
			}
		}

		if(!$okay)
			logAction('loginfail', array('user2' => $user["id"]));
	}
	else
		logAction('loginfail2', array('text' => $_POST["name"]));

	if(!$okay)
		Alert(__("Invalid user name or password."));
	else
	{
		//TODO: Tie sessions to IPs if user has enabled it (or probably not)

		$sessionID = Shake();
		setcookie("logsession", $sessionID, 2147483647, $boardroot, "", false, true);
		Query("INSERT INTO {sessions} (id, user, autoexpire) VALUES ({0}, {1}, {2})", doHash($sessionID.$salt), $user["id"], $_POST["session"]?1:0);

		logAction('login', array('user' => $user["id"]));

		redirectAction("board");
	}
}

$forgotPass = "";

if(Settings::get("mailResetSender") != "")
	$forgotPass = "<button onclick=\"document.location = '".actionLink("lostpass")."'; return false;\">".__("Forgot password?")."</button>";

echo "
	<form name=\"loginform\" action=\"".actionLink("login")."\" method=\"post\">
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
					<input type=\"submit\" name=\"actionlogin\" value=\"".__("Log in")."\" />
					$forgotPass
				</td>
			</tr>
		</table>
	</form>
	<script type=\"text/javascript\">
		document.loginform.name.focus();
	</script>
";

?>
