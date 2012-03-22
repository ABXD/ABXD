<?php
//  AcmlmBoard XD - Private message sending/previewing page
//  Access: user

$title = __("Private messages");

MakeCrumbs(array(__("Main")=>"./", __("Private messages")=>actionLink("private"), __("New PM")=>""), "");

AssertForbidden("sendPM");

if(!$loguserid) //Not logged in?
	Kill(__("You must be logged in to send private messages."));

$pid = (int)$_GET['pid'];
if($pid)
{
	$qPM = "select * from pmsgs left join pmsgs_text on pid = pmsgs.id where userto = ".$loguserid." and pmsgs.id = ".$pid;
	$rPM = Query($qPM);
	if(NumRows($rPM))
	{
		$sauce = Fetch($rPM);
		$qUser = "select * from users where id = ".(int)$sauce['userfrom'];
		$rUser = Query($qUser);
		if(NumRows($rUser))
			$user = Fetch($rUser);
		else
			Kill(__("Unknown user."));
		$prefill = "[reply=\"".$user['name']."\"]".htmlspecialchars($sauce['text'])."[/quote]";

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
	$qUser = "select * from users where id = ".$uid;
	$rUser = Query($qUser);
	if(NumRows($rUser))
	{
		$user = Fetch($rUser);
		$_POST['to'] = $user['name'];
	} else
		Kill(__("Unknown user."));
}

write(
"
	<script type=\"text/javascript\">
			window.addEventListener(\"load\",  hookUpControls, false);
	</script>
");


$recipIDs = array();
if($_POST['to'])
{
	$firstTo = -1;
	$recipients = explode(";", $_POST['to']);
	foreach($recipients as $to)
	{
		$to = mysql_real_escape_string(trim(htmlentities($to)));
		if($to == "")
			continue;
		$qUser = "select id from users where name='".$to."' or displayname='".$to."'";
		$rUser = Query($qUser);
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
			$post = mysql_real_escape_string($post);
			
			if($_POST['action'] == __("Save as Draft"))
			{
				$qPM = "insert into pmsgs (userto, userfrom, date, ip, msgread, drafting) values (".$firstTo.", ".$loguserid.", ".time().", '".$_SERVER['REMOTE_ADDR']."', 0, ".$wantDraft.")";
				$rPM = Query($qPM);
				$pid = mysql_insert_id();

				$qPMT = "insert into pmsgs_text (pid,title,text) values (".$pid.", '".justEscape($_POST['title'])."', '".$post."')";
				$rPMT = Query($qPMT);

				die(header("Location: ".actionLink("private", "", "show=2")));
			}
			else
			{
				foreach($recipIDs as $recipient)
				{
					$qPM = "insert into pmsgs (userto, userfrom, date, ip, msgread, drafting) values (".$recipient.", ".$loguserid.", ".time().", '".$_SERVER['REMOTE_ADDR']."', 0, ".$wantDraft.")";
					$rPM = Query($qPM);
					$pid = mysql_insert_id();

					$qPMT = "insert into pmsgs_text (pid,title,text) values (".$pid.", '".justEscape($_POST['title'])."', '".$post."')";
					$rPMT = Query($qPMT);
				}

				die(header("Location: ".actionLink("private", "", "show=1")));
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

$_POST['title'] = $_POST['title'];
$_POST['text'] = $_POST['text'];

if($_POST['action']=="Preview")
{
	if($_POST['text'])
	{
		$_POST['realtitle'] = $_POST['title']; //store the real PM title in another field...
		$_POST['num'] = "---";
		$_POST['posts'] = "---";
		$_POST['id'] = "???";
		$_POST['uid'] = $loguserid;
		$copies = explode(",","title,name,displayname,picture,sex,powerlevel,avatar,postheader,signature,signsep,regdate,lastactivity,lastposttime");
		foreach($copies as $toCopy)
			$_POST[$toCopy] = $loguser[$toCopy];
		$realtext = $_POST['text'];
		$_POST['text'] = preg_replace("'/me '","[b]* ".$loguser['name']."[/b] ", $_POST['text']); //to prevent identity confusion
		MakePost($_POST, POST_SAMPLE, array('metatext'=>__("Preview")));
		$_POST['title'] = $_POST['realtitle']; //and put it back for the form.
		$_POST['text'] = $realtext;
	}
}

if($_POST['text']) $prefill = htmlspecialchars($_POST['text']);
if($_POST['title']) $trefill = htmlspecialchars($_POST['title']);

if(!isset($_POST['iconid']))
	$_POST['iconid'] = 0;

Write(
"
	<script type=\"text/javascript\" src=\"lib/sendprivate.js\"></script>
	<table style=\"width: 100%;\">
		<tr>
			<td style=\"vertical-align: top; border: none;\">
				<form action=\"".actionLink("sendprivate")."\" method=\"post\">
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
								<span id=\"to\" style=\"display: none;\">
									&nbsp;
								</span>
								<button id=\"addReceiver\" type=\"button\">".__("Add")."</button>
								<span id=\"addReceiverFormContainer\" style=\"display: none;\"> <!-- Because you apparently can't use display: none on a form -->
									<input type=\"text\" name=\"receiver\" size=\"26\" /> <button type=\"button\" id=\"done\">".__("Done")."</button>
								</span>
							</td>
						</tr>
						<tr class=\"cell1\">
							<td>
								".__("Title")."
							</td>
							<td>
								<input type=\"text\" name=\"title\" style=\"width: 98%;\" maxlength=\"60\" value=\"{1}\" />
							</td>
						<tr class=\"cell0\">
							<td>
								".__("Message")."
							</td>
							<td>
								<textarea id=\"text\" name=\"text\" rows=\"16\" style=\"width: 98%;\">{0}</textarea>
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
				</form>
			</td>
			<td style=\"width: 200px; vertical-align: top; border: none;\">
",	$prefill, $trefill, $_POST['to']);

DoSmileyBar();
DoPostHelp();

Write(
"
			</td>
		</tr>
	</table>
");

?>
