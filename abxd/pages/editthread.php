<?php
//  AcmlmBoard XD - Thread editing page
//  Access: moderators

$title = __("Edit thread");

AssertForbidden("editThread");

$key = hash('sha256', "{$loguserid},{$loguser['pss']},{$salt}");
if (isset($_REQUEST['action']) && $key != $_REQUEST['key'])
		Kill(__("No."));

if(!$loguserid) //Not logged in?
	Kill(__("You must be logged in to edit threads."));

if(isset($_POST['id']))
	$_GET['id'] = $_POST['id'];

if(!isset($_GET['id']))
	Kill(__("Thread ID unspecified."));

$tid = (int)$_GET['id'];

$qThread = "select * from threads where id=".$tid;
$rThread = Query($qThread);
if(NumRows($rThread))
	$thread = Fetch($rThread);
else
	Kill(__("Unknown thread ID."));

$canMod = CanMod($loguserid, $thread['forum']);

if(!$canMod && $thread['user'] != $loguserid)
	Kill(__("You are not allowed to edit threads."));

$OnlineUsersFid = $thread['forum'];
$qFora = "select minpower from forums where id=".$thread['forum'];
$rFora = Query($qFora);
if(NumRows($rFora))
	$forum = Fetch($rFora);
else
	Kill(__("Unknown forum ID."));

$isHidden = (int)($forum['minpower'] > 0);

if($canMod)
{
	if($_GET['action']=="close")
	{
		$qThread = "update threads set closed=1 where id=".$tid;
		$rThread = Query($qThread);
		Report("[b]".$loguser['name']."[/] closed thread [b]".$thread['title']."[/] -> [g]#HERE#?tid=".$tid, $isHidden);
	
		die(header("Location: ".actionLink("thread", $tid)));
	}
	elseif($_GET['action']=="open")
	{
		$qThread = "update threads set closed=0 where id=".$tid;
		$rThread = Query($qThread);
		Report("[b]".$loguser['name']."[/] opened thread [b]".$thread['title']."[/] -> [g]#HERE#?tid=".$tid, $isHidden);
			
		die(header("Location: ".actionLink("thread", $tid)));
	}
	elseif($_GET['action']=="stick")
	{
		$qThread = "update threads set sticky=1 where id=".$tid;
		$rThread = Query($qThread);
		Report("[b]".$loguser['name']."[/] stickied thread [b]".$thread['title']."[/] -> [g]#HERE#?tid=".$tid, $isHidden);
			
		die(header("Location: ".actionLink("thread", $tid)));
	}
	elseif($_GET['action']=="unstick")
	{
		$qThread = "update threads set sticky=0 where id=".$tid;
		$rThread = Query($qThread);
		Report("[b]".$loguser['name']."[/] unstuck thread [b]".$thread['title']."[/] -> [g]#HERE#?tid=".$tid, $isHidden);
			
		die(header("Location: ".actionLink("thread", $tid)));
	}
	elseif($_POST['action']==__("Move"))
	{
		$moveto = (int)$_POST['moveTo'];
		
		//Tweak forum counters
		$qForum = "update forums set numthreads=numthreads-1, numposts=numposts-".($thread['replies']+1)." where id=".$thread['forum'];
		$rForum = Query($qForum);
		$qForum = "update forums set numthreads=numthreads+1, numposts=numposts+".($thread['replies']+1)." where id=".$moveto;
		$rForum = Query($qForum);


		$qThread = "update threads set forum=".(int)$_POST['moveTo']." where id=".$tid;
		$rThread = Query($qThread);
		
		// Tweak forum counters #2
		Query("	UPDATE forums LEFT JOIN threads
				ON forums.id=threads.forum AND threads.lastpostdate=(SELECT MAX(nt.lastpostdate) FROM threads nt WHERE nt.forum=forums.id)
				SET forums.lastpostdate=IFNULL(threads.lastpostdate,0), forums.lastpostuser=IFNULL(threads.lastposter,0), forums.lastpostid=IFNULL(threads.lastpostid,0)
				WHERE forums.id=".$thread['forum']." OR forums.id=".$moveto);
		
		Report("[b]".$loguser['name']."[/] moved thread [b]".$thread['title']."[/] -> [g]#HERE#?tid=".$tid, $isHidden);
			
		die(header("Location: ".actionLink("thread", $tid)));
	}
	elseif($_GET['action']=="delete")
	{
		$qPosts = "select id,user from posts where thread=".$tid;
		$rPosts = Query($qPosts);
		//Round up posts in this thread
		while($post = Fetch($rPosts))
		{
			//Delete this post
			$qPost = "delete from posts where id=".$post['id'];
			$qPostText = "delete from posts_text where pid=".$post['id'];
			$rPost = Query($qPost);
			$rPostText = Query($qPostText);

			//Find and decrease user's postcount
			$qUser = "select id from users where id=".$post['user'];
			$rUser = Query($qUser);
			$qUser = "update users set posts = posts - 1 where id=".$post['user'];

			$rUser = Query($qUser);

			//Decrease forum postcount
			$qForum = "update forums set numposts = numposts - 1 where id=".$thread['forum'];
			$rForum = Query($qForum);
		}
		//Delete the thread
		$qThread = "delete from threads where id=".$tid;
		$rThread = Query($qThread);

		//Decrease forum threadcount
		$qForum = "update forums set numthreads = numthreads - 1 where id=".$thread['forum'];
		$rForum = Query($qForum);
		
		// Update the forum's lastpost stuff
		Query("	UPDATE forums LEFT JOIN threads
				ON forums.id=threads.forum AND threads.lastpostdate=(SELECT MAX(nt.lastpostdate) FROM threads nt WHERE nt.forum=forums.id)
				SET forums.lastpostdate=IFNULL(threads.lastpostdate,0), forums.lastpostuser=IFNULL(threads.lastposter,0), forums.lastpostid=IFNULL(threads.lastpostid,0)
				WHERE forums.id=".$thread['forum']);

		if($thread['poll'])
		{
			//Delete poll things
			$qPoll = "delete from poll where id=".$thread['poll'];
			$rPoll = Query($qPoll);
			$qPollVotes = "delete from pollvotes where poll=".$thread['poll'];
			$rPollVotes = Query($qPollVotes);
			$qPollChoices = "delete from poll_choices where poll=".$thread['poll'];
			$rPollChoices = Query($qPollChoices);
		}

		Report("[b]".$loguser['name']."[/] deleted thread [b]".$thread['title']."[/]", $isHidden);
			
		die(header("Location: ".actionLink("forum", $thread['forum'])));
	}
	elseif($_GET['action'] == "trash")
	{
		$qForum = "select id from forums where description like '%[trash]%' limit 1";
		$trashid = FetchResult($qForum);
		if($trashid > 0)
		{
			$qThread = "update threads set forum=".$trashid.", closed=1 where id=".$tid." limit 1";
			$rThread = Query($qThread);

			//Tweak forum counters
			$qForum = "update forums set numthreads=numthreads-1, numposts=numposts-".($thread['replies']+1)." where id=".$thread['forum'];
			$rForum = Query($qForum);
			$qForum = "update forums set numthreads=numthreads+1, numposts=numposts+".($thread['replies']+1)." where id=".$trashid;
			$rForum = Query($qForum);
			
			// Tweak forum counters #2
			Query("	UPDATE forums LEFT JOIN threads
					ON forums.id=threads.forum AND threads.lastpostdate=(SELECT MAX(nt.lastpostdate) FROM threads nt WHERE nt.forum=forums.id)
					SET forums.lastpostdate=IFNULL(threads.lastpostdate,0), forums.lastpostuser=IFNULL(threads.lastposter,0), forums.lastpostid=IFNULL(threads.lastpostid,0)
					WHERE forums.id=".$thread['forum']." OR forums.id=".$trashid);

			Report("[b]".$loguser['name']."[/] thrashed thread [b]".$thread['title']."[/] -> [g]#HERE#?tid=".$tid, $isHidden);

			die(header("Location: ".actionLink("forum", $thread['forum'])));
		}
		else
			Kill(__("Could not identify trash forum."));
	}

	if($_POST['action'] == __("Edit"))
	{
		$isClosed = (isset($_POST['isClosed']) ? 1 : 0);
		$isSticky = (isset($_POST['isSticky']) ? 1 : 0);

		$trimmedTitle = trim(str_replace('&nbsp;', ' ', $thread['title']));
		if($trimmedTitle != "")
		{
			if($_POST['iconid'])
			{
				$_POST['iconid'] = (int)$_POST['iconid'];
				if($_POST['iconid'] < 255)
					$iconurl = "img/icons/icon".$_POST['iconid'].".png";
				else
					$iconurl = justEscape($_POST['iconurl']);
			}

			$qThreads = "update threads set title='".justEscape($_POST['title'])."', icon='".$iconurl."', closed=".$isClosed.", sticky=".$isSticky." where id=".$tid." limit 1";
			$rThreads = Query($qThreads);

			Report("[b]".$loguser['name']."[/] edited thread [b]".$thread['title']."[/] -> [g]#HERE#?tid=".$tid, $isHidden);

			die(header("Location: ".actionLink("thread", $tid)));
			exit();
		}
		else
			Alert(__("Your thread title is empty. Enter a message and try again."));
	}
}
else
{
	if($_POST['action'] == __("Edit"))
	{
		if($_POST['title'])
		{
			$qThreads = "update threads set title='".justEscape($_POST['title'])."' where id=".$tid." limit 1";
			$rThreads = Query($qThreads);

			Report("[b]".$loguser['name']."[/] renamed thread [b]".$thread['title']."[/] -> [g]#HERE#?tid=".$tid, $isHidden);

			die(header("Location: ".actionLink("thread", $tid)));
			//Redirect(__("Edited!"), "thread.php?id=".$tid, __("the thread"));
			exit();
		}
		else
			Alert(__("Your thread title is empty. Enter a message and try again."));
	}
}

if(!$_POST['title']) $_POST['title'] = $thread['title'];

$match = array();
if (preg_match("@^img/icons/icon(\d+)\..{3,}\$@si", $thread['icon'], $match))
	$_POST['iconid'] = $match[1];
elseif($thread['icon'] == "") //Has no icon
	$_POST['iconid'] = 0;
else //Has custom icon
{
	$_POST['iconid'] = 255;
	$_POST['iconurl'] = $thread['icon'];
}

if(!isset($_POST['iconid'])) $_POST['iconid'] = 0;

$qFora = "select title, id from forums order by catid, id";
$rFora = Query($qFora);
while($forum = Fetch($rFora))
{
	$moveToTargets .= "<option value=\"".$forum['id']."\">".$forum['title']."</option>";
}

if($canMod)
{
	$icons = "";
	$i = 1;
	while(is_file("img/icons/icon".$i.".png"))
	{
		$check = "";
		if($_POST['iconid'] == $i) $check = "checked=\"checked\" ";
		$icons .= format(
"
				<label>
					<input type=\"radio\" {0} name=\"iconid\" value=\"{1}\" />
					<img src=\"img/icons/icon{1}.png\" alt=\"Icon {1}\" />
				</label>
", $check, $i);
		$i++;
	}
	$check[0] = "";
	$check[1] = "";
	if($_POST['iconid'] == 0) $check[0] = "checked=\"checked\" ";
	if($_POST['iconid'] == 255)
	{
		$check[1] = "checked=\"checked\" ";
		$iconurl = htmlspecialchars($_POST['iconurl']);
	}
	
	write(
"
	<form action=\"".actionLink("editthread")."\" method=\"post\">
		<table class=\"outline margin\" style=\"width: 100%;\">
			<tr class=\"header1\">
				<th colspan=\"2\">
					".__("Edit Thread")."
				</th>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"tit\">".__("Title")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"tit\" name=\"title\" style=\"width: 98%;\" maxlength=\"60\" value=\"{0}\" />
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					".__("Icon")."
				</td>
				<td class=\"threadIcons\">
					<label>
						<input type=\"radio\" {2} id=\"noicon\" name=\"iconid\" value=\"0\">
						".__("None")."
					</label>
					{1}
					<br/>
					<label>
						<input type=\"radio\" {3} name=\"iconid\" value=\"255\" />
						<span>".__("Custom")."</span>
					</label>
					<input type=\"text\" name=\"iconurl\" style=\"width: 50%;\" maxlength=\"100\" value=\"{4}\" />
				</td>
			</tr>
			<tr class=\"cell2\">
				<td>
					".__("Extras")."
				</td>
				<td>
					<label>
						<input type=\"checkbox\" name=\"isClosed\" {5} />
						".__("Closed")."
					</label>
					<label>
						<input type=\"checkbox\" name=\"isSticky\" {6} />
						".__("Sticky")."
					</label>
				</td>
			</tr>
			<tr class=\"cell2\">
				<td></td>
				<td>
					<input type=\"submit\" name=\"action\" value=\"".__("Edit")."\"></input>
					<button onclick=\"window.navigate('".actionLink("editthread", "{7}", "action=delete")."');\">".__("Delete")."</button>

					<select name=\"moveTo\" size=\"1\">{8}</select>
					<input type=\"submit\" name=\"action\" value=\"".__("Move")."\" />
					<input type=\"hidden\" name=\"id\" value=\"{7}\" />
					<input type=\"hidden\" name=\"key\" value=\"{9}\" />
				</td>
			</tr>
		</table>
	</form>
",	htmlspecialchars($_POST['title']), $icons, $check[0], $check[1], $iconurl,
	($thread['closed'] ? " checked=\"checked\"" : ""),
	($thread['sticky'] ? " checked=\"checked\"" : ""),
	$tid, $moveToTargets, $key);
}
else
{
	write(
"
	<form action=\"".actionLink("editthread")."\" method=\"post\">
		<table class=\"outline margin width50\">
			<tr class=\"cell0\">
				<td>
					<label for=\"tit\">".__("Title")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"tit\" name=\"title\" style=\"width: 98%;\" maxlength=\"60\" value=\"{0}\" />
				</td>
			</tr>
			<tr class=\"cell2\">
				<td></td>
				<td>
					<input type=\"submit\" name=\"action\" value=\"".__("Edit")."\" />
					<input type=\"hidden\" name=\"id\" value=\"{1}\" />
					<input type=\"hidden\" name=\"key\" value=\"{2}\" />
				</td>
			</tr>
		</table>
	</form>
",	htmlspecialchars($_POST['title']), $tid, $key);
}

?>
