<?php


if($mailResetFrom == "")
	Kill(__("No sender specified for reset emails. Please check the board settings."));

if(isset($_GET['key']) && isset($_GET['id']))
{
	$user = Query("select id, name, password from users where id = '".(int)$_GET['id']."' and lostkey = '".justEscape($_GET['key'])."'");
	if(NumRows($user) == 0)
		Kill(__("This old key cannot be used."), __("Invalid key"));
	else
		$user = Fetch($user);

	$newPass = randomString(8);
	$sha = hash("sha256", $newPass.$salt, FALSE);

	Query("update users set lostkey = '', password = '".$sha."', pss = '' where id = ".(int)$_GET['id']);
	Kill(format(__("Your password has been reset to <strong>{0}</strong>. You can use this password to log in to the board. We suggest you change it as soon as possible."), $newPass), __("Password reset"));
	
}
else if($_POST['action'] == __("Send reset email"))
{
	$user = Query("select id, name, password, email, lostkeytimer from users where name = '".justEscape($_POST['name'])."' and email = '".justEscape($_POST['mail'])."'");
	if(NumRows($user) == 0)
		Kill(__("Could not find a user with that name and email address."), __("Invalid user name or email"));
	else
		$user = Fetch($user);
	if($user['lostkeytimer'] > time() - (60*60)) //wait an hour between attempts
		Kill(__("To prevent abuse, this function can only be used once an hour."), __("Slow down!"));

	$resetKey = md5($user['id'].$user['name'].$user['password'].$user['email']);

	$from = $mailResetFrom;
	$to = $user['email'];
	$subject = format(__("Password reset for {0}"), $user['name']);
	$message = format(__("A password reset was requested for your user account on {0}."), $boardname)."\n".__("If you did not submit this request, this message can be ignored.")."\n\n".__("To reset your password, visit the following URL:")."\n\n".$_SERVER['HTTP_REFERER']."?id=".$user['id']."&key=".$resetKey."\n\n".__("This link can be used once.");
	
	$headers = "From: ".$from."\r\n"."Reply-To: ".$from."\r\n"."X-Mailer: PHP/".phpversion();
	
	mail($to, $subject, wordwrap($message, 70), $headers);
	//print "NORMALLY I WOULD SEND MAIL NAO:<pre>".$headers."\n\n".wordwrap($message,70)."</pre>";

	Query("update users set lostkey = '".justEscape($resetKey)."', lostkeytimer = ".time()." where id = ".$user['id']);

	Kill(__("Check your email in a moment and follow the link found therein."), __("Reset email sent"));
}
else
{
	write("
	<form action=\"".actionLink("lostpass").".\" method=\"post\">
		<table class=\"outline margin width50\">
			<tr class=\"header0\">
				<th colspan=\"2\">
					".__("Lost password")."
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
					<label for=\"em\">".__("Email address")."</label>
				</td>
				<td class=\"cell1\">
					<input type=\"email\" id=\"em\" name=\"mail\" style=\"width: 98%;\" maxlength=\"60\" />
				</td>
			</tr>
			<tr class=\"cell2\">
				<td></td>
				<td>
					<input type=\"submit\" name=\"action\" value=\"".__("Send reset email")."\" />
				</td>
			</tr>
			<tr>
				<td class=\"cell1 smallFonts\" colspan=\"2\">
					".__("If you did not specify an email address in your profile, you are <em>not</em> out of luck. The old method of contacting an administrator from outside the board is still an option.")."
				</td>
			</tr>
		</table>
	</form>
");
	
}

function randomString($len, $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789")
{
   $s = "";
   for ($i = 0; $i < $len; $i++)
   {
       $p = rand(0, strlen($chars)-1);
       $s .= $chars[$p];
   }
   return $s;
}

?>
