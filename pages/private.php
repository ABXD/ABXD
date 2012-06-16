<?php
//  AcmlmBoard XD - Private message inbox/outbox viewer
//  Access: users

AssertForbidden("viewPM");

$title = "Private messages";

if(!$loguserid)
	Kill(__("You must be logged in to view your private messages."));

$user = $loguserid;
if(isset($_GET['user']) && $loguser['powerlevel'] > 2)
{
	$user = (int)$_GET['user'];
	$snoop = "&snooping=1";
	$userGet = "&user=".$user;
}

if(isset($_POST['action']))
{
	if($_POST['action'] == "multidel" && $_POST['delete'] && $snoop != 1)
	{
		$deleted = 0;
		foreach($_POST['delete'] as $pid => $on)
		{
			$rPM = Query("select * from {$dbpref}pmsgs where id = ".$pid." and (userto = ".$loguserid." or userfrom = ".$loguserid.")");
			if(NumRows($rPM))
			{
				$pm = Fetch($rPM);
				$val = $pm['userto'] == $loguserid ? 2 : 1;
				$newVal = ($pm['deleted'] | $val);
				if($newVal == 3)
				{
					Query("delete from {$dbpref}pmsgs where id = ".$pid);
					Query("delete from {$dbpref}pmsgs_text where pid = ".$pid);
				}
				else
					Query("update {$dbpref}pmsgs set deleted = ".$newVal." where id = ".$pid);
				$deleted++;
			}
		}
		Alert(format(__("{0} deleted."), Plural($deleted, __("private message"))));
	}
}

if(isset($_GET['del']))
{
	$pid = (int)$_GET['del'];
	$rPM = Query("select * from {$dbpref}pmsgs where id = ".$pid." and (userto = ".$loguserid." or userfrom = ".$loguserid.")");
	if(NumRows($rPM))
	{
		$pm = Fetch($rPM);
		$val = $pm['userto'] == $loguserid ? 2 : 1;
		$newVal = ($pm['deleted'] | $val);
		if($newVal == 3)
		{
			Query("delete from {$dbpref}pmsgs where id = ".$pid);
			Query("delete from {$dbpref}pmsgs_text where pid = ".$pid);
		}
		else
			Query("update {$dbpref}pmsgs set deleted = ".$newVal." where id = ".$pid);
		Alert(__("Private message deleted."));
	}
}

$whereFrom = "userfrom = ".$user;
$drafting = 0;
$deleted = 2;
if(isset($_GET['show']))
{
	$show = "&show=".(int)$_GET['show'];
	if($_GET['show'] == 1)
		$deleted = 1;
	else if($_GET['show'] == 2)
		$drafting = 1;
}
else
{
	$whereFrom = "userto = ".$user;
}
$whereFrom .= " and drafting = ".$drafting;

$qTotal = "select count(*) from {$dbpref}pmsgs where ".$whereFrom." and deleted != ".$deleted;
$total = FetchResult($qTotal);

$ppp = $loguser['postsperpage'];

if(isset($_GET['from']))
	$from = (int)$_GET['from'];
else
	$from = 0;


$links = "<ul class=\"pipemenu\">";

$links .= actionLinkTagItem(__("Show received"), "private", "", str_replace("&", "", $userGet));
$links .= actionLinkTagItem(__("Show sent"), "private", "", "show=1".$userGet);
$links .= actionLinkTagItem(__("Show drafts"), "private", "", "show=2".$userGet);
$links .= actionLinkTagItem(__("Send new PM"), "sendprivate");

MakeCrumbs(array(__("Private messages")=>actionLink("private")), $links);

$qPM = "select * from {$dbpref}pmsgs left join {$dbpref}pmsgs_text on pid = {$dbpref}pmsgs.id where ".$whereFrom." and deleted != ".$deleted." order by date desc limit ".$from.", ".$ppp;

//print $qPM;

$rPM = Query($qPM);
$numonpage = NumRows($rPM);

$pagelinks = PageLinks(actionLink("private", "", "$show$userGet&from="), $ppp, $from, $total);

if($pagelinks)
	write("<div class=\"smallFonts pages\">".__("Pages:")." {0}</div>", $pagelinks);

if(NumRows($rPM))
{
	while($pm = Fetch($rPM))
	{
		$qUser = "select * from {$dbpref}users where id = ".(isset($_GET['show']) ? $pm['userto'] : $pm['userfrom']);
		$rUser = Query($qUser);
		if(NumRows($rUser))
			$user = Fetch($rUser);

		$cellClass = ($cellClass+1) % 2;
		if(!$pm['msgread'])
			$img = "<img src=\"img/status/new.png\" alt=\"New!\" />";
		else
			$img = "";

		$sender = (NumRows($rUser) ? UserLink($user) : "???");

		$check = $snoop ? "" : "<input type=\"checkbox\" name=\"delete[{2}]\" />";

		$delLink = $snoop == "" ? "<sup>&nbsp;".actionLinkTag("&#x2718;", "private", "", "del=".$pm['id'].$show)."</sup>" : "";
		
		$pms .= format(
"
		<tr class=\"cell{0}\">
			<td>
				".$check."
			</td>
			<td class=\"center\">
				{1}
			</td>
			<td>
				".actionLinkTag(htmlspecialchars($pm['title']), "showprivate", $pm['id'], $snoop)."{7}
			</td>
			<td>
				{5}
			</td>
			<td>
				{6}
			</td>
		</tr>
",	$cellClass, $img, $pm['id'], $snoop, htmlspecialchars($pm['title']), $sender, formatdate($pm['date']), $delLink);
	}
}
else
	$pms = format(
"
		<tr class=\"cell1\">
			<td colspan=\"6\">
				".__("There are no messages to display.")."
			</td>
		</tr>
");

write(
"
	<form method=\"post\" action=\"".actionLink("private")."\">
	<table class=\"outline margin\">
		<tr class=\"header1\">
			<th style=\"width: 22px;\">
				<input type=\"checkbox\" id=\"ca\" onchange=\"checkAll();\" />
			</th>
			<th style=\"width: 22px;\">&nbsp;</th>
			<th style=\"width: 75%;\">".__("Title")."</th>
			<th>{0}</th>
			<th>".__("Date")."</th>
		</tr>
		{1}
		<tr class=\"header1\">
			<th style=\"text-align: right;\" colspan=\"6\">
				<input type=\"hidden\" name=\"action\" value=\"multidel\" />
				<a href=\"javascript:void();\" onclick=\"document.forms[1].submit();\">".__("delete checked")."</a>
			</th>
		</tr>
	</table>
	</font>
", (isset($_GET['show']) ? __("To") : __("From")), $pms);

if($pagelinks)
	write("<div class=\"smallFonts pages\">".__("Pages:")." {0}</div>", $pagelinks);

?>
