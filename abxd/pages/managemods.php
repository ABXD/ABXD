<?php
//  AcmlmBoard XD - Local moderator assignment tool
//  Access: administrators only

$title = __("Manage localmod assignments");

AssertForbidden("editMods");

if($loguser['powerlevel'] < 3)
	Kill(__("You're not an administrator. There is nothing for you here."));

if(!isset($_GET['action']))
{
	$qFora = "select * from forums order by catid, forder";
	$rFora = Query($qFora);
	while($forum = Fetch($rFora))
	{
		$modList = "";
		$qMods = "select * from forummods where forum=".$forum['id'];
		$rMods = Query($qMods);
		while($mods = Fetch($rMods))
		{
			$qMod = "select name, displayname, id, powerlevel, sex from users where id=".$mods['user'];
			$rMod = Query($qMod);
			$mod = Fetch($rMod);
			$modList .= "<li>".UserLink($mod)."<sup>";
			$modList .= actionLinkTag("&#x2718;", "managemods", "", "action=delete&amp;fid={$forum['id']}&amp;mid={$mods['user']}");
			$modList .= "</sup></li>";
		}
		$theList .= format(
"
		<li>
			{0}
			<ul>
				{2}
				".actionLinkTagItem(__("Add"), "managemods", "", "action=add&amp;fid={1}")."
			</ul>
		</li>
", $forum['title'], $forum['id'], $modList);
	}
	write(
"
	<div class=\"faq outline margin\">
		<h3>".__("Moderators as of {0}")."</h3>
		<ul>
			{1}
		</ul>
	</div>
", gmdate("F jS Y"), $theList);
}
elseif($_GET['action'] == "delete")
{
	if(!isset($_GET['fid']))
		Kill(__("Forum ID unspecified."));
	if(!isset($_GET['mid']))
		Kill(__("Mod ID unspecified."));

	$fid = (int)$_GET['fid'];
	$mid = (int)$_GET['mid'];

	$qMod = "delete from forummods where forum=".$fid." and user=".$mid;
	$rMod = Query($qMod);
	
	die(header("Location: ".actionLink("managemods")));
}
elseif($_GET['action'] == "add")
{
	if(!isset($_GET['fid']))
		Kill(__("Forum ID unspecified."));

	$fid = (int)$_GET['fid'];

	if(!isset($_GET['mid']))
	{
		$modList = "";
		$qMod = "select * from users where powerlevel=1 order by name asc";
		$rMod = Query($qMod);
		while($mod = Fetch($rMod))
		{
			$qCheck = "select user from forummods where forum=".$fid." and user=".$mod['id'];
			$rCheck = Query($qCheck);
			if(NumRows($rCheck))
				$add = __("already there");
			else
				$add = actionLinkTag("Add", "managemods", "", "action=add&amp;fid=$fid&amp;mid={$mod['id']}");

			$modList .= format(
"
<li>
{0}
<sup>[{1}]</sup>
</li>
", UserLink($mod), $add);
		}
		write(
"
		<div class=\"faq outline margin\">
			".__("Pick a mod, any mod.")."
			<ul>
				{0}
			</ul>
		</div>
",	$modList);
	}
	else
	{
		$mid = (int)$_GET['mid'];
		$qMod = "insert into forummods (forum	, user) values (".$fid.", ".$mid.")";
		$rMod = Query($qMod);

		die(header("Location: ".actionLink("managemods")));
	}
}

?>
