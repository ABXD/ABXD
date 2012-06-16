<?php
//  AcmlmBoard XD - Private message display page
//  Access: user, specifically the sender or reciever.

$title = __("Private messages");

AssertForbidden("viewPM");

if(!$loguserid)
	Kill(__("You must be logged in to view your private messages."));

if(!isset($_GET['id']) && !isset($_POST['id']))
	Kill(__("No PM specified."));

$id = (int)(isset($_GET['id']) ? $_GET['id'] : $_POST['id']);
$pmid = $id;

if(isset($_GET['snooping']))
{
	if($loguser['powerlevel'] > 2)
		$qPM = "select * from {$dbpref}pmsgs left join p{$dbpref}msgs_text on pid = {$dbpref}pmsgs.id where {$dbpref}pmsgs.id = ".$id;
	else
		Kill(__("No snooping for you."));
}
else
	$qPM = "select * from {$dbpref}pmsgs left join {$dbpref}pmsgs_text on pid = {$dbpref}pmsgs.id where (userto = ".$loguserid." or userfrom = ".$loguserid.") and {$dbpref}pmsgs.id = ".$id;

$rPM = Query($qPM);
if(NumRows($rPM))
	$pm = Fetch($rPM);
else
	Kill(__("Unknown PM"));

if($pm['drafting'] && $pm['userfrom'] != $loguserid)
	Kill(__("Unknown PM")); //could say "PM is addresssed to you, but is being drafted", but what they hey?

$qUser = "select * from {$dbpref}users where id = ".$pm['userfrom'];
$rUser = Query($qUser);
if(NumRows($rUser))
	$user = Fetch($rUser);
else
	Kill(__("Unknown user."));
$bucket = "userMangler"; include("./lib/pluginloader.php");

if(!isset($_GET['snooping']) && $pm['userto'] == $loguserid)
{
	$qPM = "update {$dbpref}pmsgs set msgread=1 where id=".$pm['id'];
	$rPM = Query($qPM);
	$links = actionLinkTag(__("Send reply"), "sendprivate", "", "pid=".$pm['id']);
}
else if(!isset($_GET['snooping']) && $pm['drafting'])
{
	if($pm['userfrom'] != $loguserid)
		Kill(__("This PM is still being drafted."));
	else
		$draftEditor = true;
}
else if(isset($_GET['snooping']))
	Alert(__("You are snooping."));

$pmtitle = htmlspecialchars($pm['title']); //sender's custom title overwrites this below, so save it here
MakeCrumbs(array(("Private messages")=>actionLink("private"), $pmtitle=>""), $links);

$pm['num'] = "preview";
$pm['posts'] = $user['posts'];
$pm['id'] = "???";
$pm['uid'] = $user['id'];
$copies = explode(",","title,name,displayname,picture,sex,powerlevel,avatar,postheader,signature,signsep,regdate,lastactivity,lastposttime");
foreach($copies as $toCopy)
	$pm[$toCopy] = $user[$toCopy];

if($draftEditor)
{
	write(
"
	<script type=\"text/javascript\">
			window.addEventListener(\"load\",  hookUpControls, false);
	</script>
");


	$qUser = "select name from {$dbpref}users where id=".$pm['userto'];
	$rUser = Query($qUser);
	if(!NumRows($rUser))
	{
		if($_POST['action'] == __("Send"))
			Kill(__("Unknown user."));
	}
	$user = Fetch($rUser);
	
	if($_POST['action'] == __("Preview"))
	{
		$pm['text'] = $_POST['text'];
		$pmtitle = $_POST['title'];
	}
	
	if($_POST['action'] == __("Discard Draft"))
	{
		Query("delete from {$dbpref}pmsgs where id = ".$pmid);
		Query("delete from {$dbpref}pmsgs_text where pid = ".$pmid);

		die(header("Location: ".actionLink("private")));
		exit();
	}

	if(substr($pm['text'], 0, 17) == "<!-- ###MULTIREP:")
	{
		$to = substr($pm['text'], 17, strpos($pm['text'], "### -->") - 18);
		$pm['text'] = substr($pm['text'], strpos($pm['text'], "### -->") + 7);
	}
	
	if($_POST['action'] == __("Send") || $_POST['action'] == __("Update Draft"))
	{
		$recipIDs = array();
		if($_POST['to'])
		{
			$firstTo = -1;
			$recipients = explode(";", $_POST['to']);
			foreach($recipients as $to)
			{
				$to = justEscape(trim(htmlentities($to)));
				if($to == "")
					continue;
				$qUser = "select id from {$dbpref}users where name='".$to."' or displayname='".$to."'";
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
				$maxRecips = array(-1 => 1, 3, 3, 3, 10, 100, 1);
				$maxRecips = $maxRecips[$loguser['powerlevel']];
				//$maxRecips = ($loguser['powerlevel'] > 1) ? 5 : 1;
				if(count($recipIDs) > $maxRecips)
					$errors .= __("Too many recipients.");
				else
					$errors .= format(__("Unknown user \"{0}\""), $to)."<br />";
			}
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

		if($_POST['title'])
		{
			$_POST['title'] = $_POST['title'];

			if($_POST['text'])
			{
				if($_POST['action'] == __("Update Draft"))
				{
					$post = $pm['text'];
					$post = preg_replace("'/me '","[b]* ".$loguser['name']."[/b] ", $post); //to prevent identity confusion
						$post = "<!-- ###MULTIREP:".$_POST['to']." ### -->".$post;
					$post = justEscape($post);
	
					$qPMT = "update {$dbpref}pmsgs_text set title = '".justEscape($_POST['title'])."', text = '".$post."' where pid = ".$pmid;
					$rPMT = Query($qPMT);
					$qPM = "update {$dbpref}pmsgs set userto = ".$firstTo." where id = ".$pmid;
					$rPM = Query($qPM);

                	die(header("Location: ".actionLink("private", "", "show=2")));
				}
				else
				{
					$post = $pm['text'];
					$post = preg_replace("'/me '","[b]* ".$loguser['name']."[/b] ", $post); //to prevent identity confusion
					$post = justEscape($post);

					$qPMT = "update {$dbpref}pmsgs_text set title = '".justEscape($_POST['title'])."', text = '".$post."' where pid = ".$pmid;
					$rPMT = Query($qPMT);
					$qPM = "update {$dbpref}pmsgs set drafting = 0 where id = ".$pmid;
					$rPM = Query($qPM);

					foreach($recipIDs as $recipient)
					{
						if($recipient == $firstTo)
							continue;
										
						$qPM = "insert into {$dbpref}pmsgs (userto, userfrom, date, ip, msgread) values (".$recipient.", ".$loguserid.", ".time().", '".$_SERVER['REMOTE_ADDR']."', 0)";
						$rPM = Query($qPM);
						$pid = insertId();

						$qPMT = "insert into {$dbpref}pmsgs_text (pid,title,text) values (".$pid.", '".justEscape($_POST['title'])."', '".$post."')";
						$rPMT = Query($qPMT);
					}

				die(header("Location: ".actionLink("private", "", "show=1")));
					exit();
				}
			}
			else
				Alert(__("Enter a message and try again."), __("Your PM is empty."));
		}
		else
			Alert(__("Enter a title and try again."), __("Your PM is untitled."));
	}

	$prefill = $pm['text'];
	$trefill = $pmtitle;

	MakePost($pm, POST_PM);

	Write(
"
	<table style=\"width: 100%;\">
		<tr>
			<td style=\"vertical-align: top; border: none;\">
				<form action=\"".actionLink("showprivate")."\" method=\"post\">
					<table class=\"outline margin width100\">
						<tr class=\"header1\">
							<th colspan=\"2\">
								".__("Edit Draft")."
							</th>
						</tr>
						<tr class=\"cell0\">
							<td>
								".__("To")."
							</td>
							<td>
								<input type=\"text\" name=\"to\" style=\"width: 98%;\" maxlength=\"1024\" value=\"{2}\" />
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
								<input type=\"submit\" name=\"action\" value=\"".__("Update Draft")."\" /> 
								<input type=\"submit\" name=\"action\" value=\"".__("Discard Draft")."\" /> 
								<input type=\"hidden\" name=\"id\" value=\"{3}\" />
							</td>
						</tr>
					</table>
				</form>
			</td>
			<td style=\"width: 200px; vertical-align: top; border: none;\">
",	$prefill, $trefill, $to, $pmid);

	DoSmileyBar();
	DoPostHelp();

	Write(
"
			</td>
		</tr>
	</table>
");
}
else
{
	MakePost($pm, POST_PM);
}

?>
