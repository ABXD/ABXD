<?php
//  AcmlmBoard XD - Private message sending/previewing page
//  Access: user

$title = __("Private messages");

$crumbs = new PipeMenu();
$crumbs->add(new PipeMenuLinkEntry(__("Member list"), "memberlist"));
$crumbs->add(new PipeMenuHtmlEntry(userLink($loguser)));
$crumbs->add(new PipeMenuLinkEntry(__("Private messages"), "private"));
$crumbs->add(new PipeMenuLinkEntry(__("New PM"), "sendprivate"));
makeBreadcrumbs($crumbs);

AssertForbidden("sendPM");

if(!$loguserid) //Not logged in?
	Kill(__("You must be logged in to send private messages."));

$pid = (int)$_GET['pid'];
if($pid)
{
	$rPM = Query("select * from {pmsgs} p left join {pmsgs_text} t on t.pid = p.id where p.userto = {0} and p.id = {1}", $loguserid, $pid);
	if(NumRows($rPM))
	{
		$sauce = Fetch($rPM);
		$rUser = Query("select * from {users} where id = {0}", (int)$sauce['userfrom']);
		if(NumRows($rUser))
			$user = Fetch($rUser);
		else
			Kill(__("Unknown user."));
		$prefill = "[reply=\"".$user['name']."\"]".htmlspecialchars($sauce['text'])."[/reply]";

		if(strpos($sauce['title'], "Re: Re: Re: ") !== false)
			$trefill = str_replace("Re: Re: Re: ", "Re*4: ", $sauce['title']);
		else if(preg_match("'Re\*([0-9]+): 'se", $sauce['title'], $reeboks))
			$trefill = "Re*" . ((int)$reeboks[1] + 1) . ": " . substr($sauce['title'], strpos($sauce['title'], ": ") + 2);
		else
			$trefill = "Re: ".$sauce['title'];


		if(!isset($_POST['to']))
			$_POST['to'] = $user['name'];
	} else
		Kill(__("Unknown PM."));
}

$uid = (int)$_GET['uid'];
if($uid)
{
	$rUser = Query("select * from {users} where id = {0}", $uid);
	if(NumRows($rUser))
	{
		$user = Fetch($rUser);
		$_POST['to'] = $user['name'];
	} else
		Kill(__("Unknown user."));
}

/*
// "Banned users can't send PMs. Bad bad bad, quite often PMs are a good way for them to try and get unbanned." -- Mega-Mario
if($loguser['powerlevel'] < 0)
	Kill("You're banned.");
*/
require 'config/database.php';
$userMySQLconn = new mysqli($dbserv, $dbuser, $dbpass, $dbname);
$userMySQL = "SELECT name FROM " . $dbpref . "users";
$userMySQL2 = "SELECT displayname " . $dbpref . "users";
$userMySQLresult = $userMySQLconn->query($userMySQL);
$userMySQLresult2 = $userMySQLconn->query($userMySQL2);
echo "<script type=\"text/javascript\">";
echo "$(function() {";
echo "var availableTags = [";
while($row = $userMySQLresult->fetch_assoc()) {
if ($row['name']==$loguser['name']) {} else {
        echo "\"" . $row['name'] . "\",";
}
}
while($row = $userMySQLresult2->fetch_assoc()) {
if ($row['displayname']=="" || $row['displayname']==$loguser['displayname']) {
} else {
echo "\"" . $row['displayname'] . "\",";
}
}
echo "\"\"";
echo "];";
echo "$( \"#tags\" ).autocomplete({";
echo "source: availableTags";
echo "});";
echo "});";
echo "</script>";

$recipIDs = array();
if($_POST['to'])
{
	$firstTo = -1;
	$recipients = explode(";", $_POST['to']);
	foreach($recipients as $to)
	{
		$to = trim(htmlentities($to));
		if($to == "")
			continue;

		$rUser = Query("select id from {users} where name={0} or displayname={0}", $to);
		if(NumRows($rUser))
		{
			$user = Fetch($rUser);
			$id = $user['id'];
			if($firstTo == -1)
				$firstTo = $id;
			if($id == $loguserid)
				$errors .= __("You can't send private messages to yourself.")."<br />";
			else if(!in_array($id, $recipIDs))
				$recipIDs[] = $id;
		}
		else
			$errors .= format(__("Unknown user \"{0}\""), $to)."<br />";
	}
	$maxRecips = array(-1 => 1, 3, 3, 3, 10, 100, 1);
	$maxRecips = $maxRecips[$loguser['powerlevel']];
	//$maxRecips = ($loguser['powerlevel'] > 1) ? 5 : 1;
	if(count($recipIDs) > $maxRecips)
		$errors .= __("Too many recipients.");
	if($errors != "")
	{
		Alert($errors);
		$_POST['action'] = "";
	}
}
else
{
	if($_POST['action'] == __("Send"))
		Alert("Enter a recipient and try again.", "Your PM has no recipient.");
	$_POST['action'] = "";
}

if($_POST['action'] == __("Send") || $_POST['action'] == __("Save as Draft"))
{
	if($_POST['title'])
	{
		$_POST['title'] = $_POST['title'];

		if($_POST['text'])
		{
			$wantDraft = (int)($_POST['action'] == __("Save as Draft"));

			$post = $_POST['text'];
			$post = preg_replace("'/me '","[b]* ".$loguser['name']."[/b] ", $post); //to prevent identity confusion
			if($wantDraft)
				$post = "<!-- ###MULTIREP:".$_POST['to']." ### -->".$post;

			if($_POST['action'] == __("Save as Draft"))
			{
				$rPM = Query("insert into {pmsgs} (userto, userfrom, date, ip, msgread, drafting) values ({0}, {1}, {2}, {3}, 0, {4})", $firstTo, $loguserid, time(), $_SERVER['REMOTE_ADDR'], $wantDraft);
				$pid = InsertId();

				$rPMT = Query("insert into {pmsgs_text} (pid,title,text) values ({0}, {1}, {2})", $pid, $_POST['title'], $post);

				redirectAction("private", "", "show=2");
				//Redirect(__("Draft saved!"), "private.php?show=2", __("your drafts box"));
			}
			else
			{
				foreach($recipIDs as $recipient)
				{
					$rPM = Query("insert into {pmsgs} (userto, userfrom, date, ip, msgread, drafting) values ({0}, {1}, {2}, {3}, 0, {4})", $recipient, $loguserid, time(), $_SERVER['REMOTE_ADDR'], $wantDraft);
					$pid = InsertId();

					$rPMT = Query("insert into {pmsgs_text} (pid,title,text) values ({0}, {1}, {2})", $pid, $_POST['title'], $post);
				}

				redirectAction("private", "", "show=1");
				//Redirect(__("PM sent!"),"private.php?show=1", __("your PM outbox"));
			}
			exit();
		} else
		{
			Alert(__("Enter a message and try again."), __("Your PM is empty."));
		}
	} else
	{
		Alert(__("Enter a title and try again."), __("Your PM is untitled."));
	}
}

write(
"
    <script type=\"text/javascript\">
            window.addEventListener(\"load\",  hookUpControls, false);
    </script>
");

$_POST['title'] = $_POST['title'];
$_POST['text'] = $_POST['text'];

if($_POST['action']==__("Preview"))
{
	if($_POST['text'])
	{

		$previewPost['text'] = $_POST["text"];
		$previewPost['num'] = "---";
		$previewPost['posts'] = "---";
		$previewPost['id'] = "_";
		$previewPost['options'] = 0;

		foreach($loguser as $key => $value)
			$previewPost["u_".$key] = $value;

		MakePost($previewPost, POST_SAMPLE, array('metatext'=>__("Preview")));
	}
}

if($_POST['text']) $prefill = htmlspecialchars($_POST['text']);
if($_POST['title']) $trefill = htmlspecialchars($_POST['title']);

if(!isset($_POST['iconid']))
	$_POST['iconid'] = 0;

$form = "
	<form name=\"postform\" action=\"".actionLink("sendprivate")."\" method=\"post\">
		<table class=\"outline margin width100\">
			<tr class=\"header1\">
				<th colspan=\"2\">
					".__("Send PM")."
				</th>
			</tr>
			<tr class=\"cell0\">
				<td>
					".__("To")."
				</td>
				<td>
					<div class=\"ui-widget\"><input type=\"text\" name=\"to\" id=\"tags\" style=\"width: 98%;\" maxlength=\"1024\" value=\"".htmlspecialchars($_POST['to'])."\" /></div>
			</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					".__("Title")."
				</td>
				<td>
					<input type=\"text\" name=\"title\" style=\"width: 98%;\" maxlength=\"60\" value=\"$trefill\" />
				</td>
			<tr class=\"cell0\">
				<td colspan=\"2\">
					<textarea id=\"text\" name=\"text\" rows=\"16\" style=\"width: 98%;\">$prefill</textarea>
				</td>
			</tr>
			<tr class=\"cell2\">
				<td></td>
				<td>
					<input type=\"submit\" name=\"action\" value=\"".__("Send")."\" />
					<input type=\"submit\" name=\"action\" value=\"".__("Preview")."\" />
					<input type=\"submit\" name=\"action\" value=\"".__("Save as Draft")."\" />
				</td>
			</tr>
		</table>
	</form>";
doPostForm($form);
?>