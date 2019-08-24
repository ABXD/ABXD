<?php


function IsReallyEmpty($subject)
{
	$trimmed = trim(preg_replace("/&.*;/", "", $subject));
	return strlen($trimmed) != 0;
}



if(isset($_POST['id']))
	$_GET['id'] = $_POST['id'];

if(!isset($_GET['id']))
	Kill(__("User ID unspecified."));

$id = (int)$_GET['id'];

$rUser = Query("select * from {users} where id={0}", $id);
if(NumRows($rUser))
	$user = Fetch($rUser);
else
	Kill(__("Unknown user ID."));

if($id == $loguserid)
{
	Query("update {users} set newcomments = 0 where id={0}", $loguserid);
	$loguser['newcomments'] = false;
}


$crumbs = new PipeMenu();
$crumbs->add(new PipeMenuLinkEntry(__("Member list"), "memberlist"));
$crumbs->add(new PipeMenuHtmlEntry(userLink($user)));
$crumbs->add(new PipeMenuTextEntry(__("Comments")));
makeBreadcrumbs($crumbs);


$canDeleteComments = ($id == $loguserid || $loguser['powerlevel'] > 2) && IsAllowed("deleteComments") && $loguser['powerlevel'] >= 0;
$canComment = $loguser['powerlevel'] >= 0;

if ($loguserid)
{
	if($canDeleteComments && isset($_GET['action']) && $_GET['action'] == "delete")
	{
		AssertForbidden("deleteComments");
		Query("delete from {usercomments} where uid={0} and id={1}", $id, (int)$_GET['cid']);
		if($mobileLayout)
			die(header("Location: ".actionLink("usercomments", $id)));
		else
			die(header("Location: ".actionLink("profile", $id)));
	}

	if(isset($_POST['actionpost']) && IsReallyEmpty($_POST['text']) && $canComment)
	{
		AssertForbidden("makeComments");
		$rComment = Query("insert into {usercomments} (uid, cid, date, text) values ({0}, {1}, {2}, {3})", $id, $loguserid, time(), $_POST['text']);
		if($loguserid != $id)
			Query("update {users} set newcomments = 1 where id={0}", $id);
		logAction('usercomment', array('user2' => $id));

		if($mobileLayout)
			die(header("Location: ".actionLink("usercomments", $id)));
		else
			die(header("Location: ".actionLink("profile", $id)));
	}
}

$cpp = 15;
$total = FetchResult("SELECT
						count(*)
					FROM {usercomments}
					WHERE uid={0}", $id);
$from = (int)$_GET['from'];


$realFrom = $total-$from-$cpp;
$realLen = $cpp;
if($realFrom < 0)
{
	$realLen += $realFrom;
	$realFrom = 0;
}
$rComments = Query("SELECT
		u.(_userfields),
		{usercomments}.id, {usercomments}.cid, {usercomments}.text
		FROM {usercomments}
		LEFT JOIN {users} u ON u.id = {usercomments}.cid
		WHERE uid={0}
		ORDER BY {usercomments}.date ASC LIMIT {1u},{2u}", $id, $realFrom, $realLen);

$pagelinks = PageLinksInverted(actionLink($mobileLayout?"usercomments":"profile", $id, "from="), $cpp, $from, $total);

$commentList = "";
$commentField = "";
if(NumRows($rComments))
{
	while($comment = Fetch($rComments))
	{
		if($canDeleteComments)
			$deleteLink = "<small style=\"float: right; margin: 0px 4px;\">".
				actionLinkTag("&#x2718;", $mobileLayout?"usercomments":"profile", $id, "action=delete&cid=".$comment['id']."")."</small>";
		$cellClass = 0;
		$cellClass = ($cellClass+1) % 2;
		$thisComment = format(
"
						<tr>
							<td class=\"cell2\">
								{0}
							</td>
							<td class=\"cell{1}\">
								{3}{2}
							</td>
						</tr>
",	UserLink(getDataPrefix($comment, "u_")), $cellClass, CleanUpPost($comment['text'], $comment['u_name']), $deleteLink);
		$commentList = $commentList.$thisComment;
		if(!isset($lastCID))
			$lastCID = $comment['cid'];
	}

	$pagelinks = "<td colspan=\"2\" class=\"cell1\">$pagelinks</td>";
	if($total > $cpp)
		$commentList = "$pagelinks$commentList$pagelinks";
}
else
{
	$commentsWasEmpty = true;
	$commentList = $thisComment = format(
"
						<tr>
							<td class=\"cell0\" colspan=\"2\">
								".__("No comments.")."

							</td>
						</tr>
");
}

//print "lastCID: ".$lastCID;

if($loguserid )
{
	$commentField = "
								<div>
									<form name=\"commentform\" method=\"post\" action=\"".actionLink("usercomments")."\">
										<input type=\"hidden\" name=\"id\" value=\"$id\" />
										<input type=\"text\" name=\"text\" style=\"width: 80%;\" maxlength=\"255\" />
										<input type=\"submit\" name=\"actionpost\" value=\"".__("Post")."\" />
									</form>
								</div>";
//	if($lastCID == $loguserid)
//		$commentField = __("You already have the last word.");
	if(!IsAllowed("makeComments") || !$canComment)
		$commentField = __("You are not allowed to post usercomments.");
}

print "
				<table class=\"outline margin\">
					<tr class=\"header1\">
						<th colspan=\"2\">
							".format(__("Comments about {0}"), UserLink($user))."
						</th>
					</tr>
					$commentList
					<tr>
						<td colspan=\"2\" class=\"cell2\">
							$commentField
						</td>
					</tr>
				</table>";

$bucket = "profileRight"; include("./lib/pluginloader.php");

