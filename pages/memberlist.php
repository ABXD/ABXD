<?php
//  AcmlmBoard XD - Member list page
//  Access: all


$title = __("Member list");

AssertForbidden("viewMembers");


$tpp = $loguser['threadsperpage'];
if($tpp<1) $tpp=50;

if(isset($_GET['from']))
	$from = (int)$_GET['from'];
else
	$from = 0;

if(isset($dir)) unset($dir);
if(isset($_GET['dir']))
{
	$dir = $_GET['dir'];
	if($dir != "asc" && $dir != "desc")
		unset($dir);
}

$sort = $_GET['sort'];
if(!in_array($sort, array('', 'id', 'name', 'karma', 'reg')))
	unset($sort);

$sex = $_GET['sex'];
if(isset($_GET['pow']) && $_GET['pow'] != "")
	$pow = (int)$_GET['pow'];

$order = "";
$where = "";

switch($sort)
{
	case "id": $order = "id ".(isset($dir) ? $dir : "asc"); break;
	case "name": $order = "name ".(isset($dir) ? $dir : "asc"); break;
	case "reg": $order = "regdate ".(isset($dir) ? $dir : "desc"); break;
	case "karma": $order = "karma ".(isset($dir) ? $dir : "desc"); break;
	default: $order="posts ".(isset($dir) ? $dir : "desc");
}

switch($sex)
{
	case "m": $where = "sex=0"; break;
	case "f": $where = "sex=1"; break;
	case "n": $where = "sex=2"; break;
	default: $where = "1";
}

if(isset($pow))
	$where.= " and powerlevel=".$pow;

$query = $_GET['query'];

if($query != "") {
		$where.= " and name like '%".justEscape($query)."%' or displayname like '%".justEscape($query)."%'";
}

if(!(isset($pow) && $pow == 5))
	$where.= " and powerlevel < 5";

$numUsers = FetchResult("select count(*) from {$dbpref}users where ".$where, 0, 0);

$qUsers = "select * from {$dbpref}users where ".$where." order by ".$order.", name asc limit ".$from.", ".$tpp;
$rUsers = Query($qUsers);

function PageLinks2($url, $epp, $from, $total)
{
	$numPages = ceil($total / $epp);
	$page = ceil($from / $epp) + 1;

	$first = ($from) ? "<a href=\"".$url."0)\">&#x00AB;</a> " : "";
	$prev = ($from) ? "<a href=\"".$url.($from - $epp).")\">&#x2039;</a> " : "";
	$next = ($from < $total - $epp) ? " <a href=\"".$url.($from + $epp).")\">&#x203A;</a>" : "";
	$last = ($from < $total - $epp) ? " <a href=\"".$url.(($numPages * $epp) - $epp).")\">&#x00BB;</a>" : "";

	$pageLinks = array();
	for($p = $page - 5; $p < $page + 10; $p++)
	{
		if($p < 1 || $p > $numPages)
			continue;
		if($p == $page || ($from == 0 && $p == 1))
			$pageLinks[] = $p;
		else
			$pageLinks[] = "<a href=\"".$url.(($p-1) * $epp).")\">".$p."</a>";
	}
	
	return $first.$prev.join(array_slice($pageLinks, 0, 11), " ").$next.$last;
}

$pagelinks = PageLinks2("javascript:refreshMemberlist(", $tpp, $from, $numUsers);

if ($_GET['listing'])  {
	$ajaxPage = true;

	if($pagelinks)
	{
		write(
	"
		<table class=\"outline margin\">
			<tr class=\"cell2 smallFonts\">
				<td colspan=\"2\">
					".__("Page")."
				</td>
				<td colspan=\"6\">
					{0}
				</td>
			</tr>
	",	$pagelinks);
	}

	$memberList = "";
	if($numUsers)
	{
		while($user = Fetch($rUsers))
		{
			$bucket = "userMangler"; include("./lib/pluginloader.php");
			$daysKnown = (time()-$user['regdate'])/86400;
			$user['average'] = sprintf("%1.02f", $user['posts'] / $daysKnown);

			$userPic = "";
			if($user['picture'] && $hacks['themenames'] != 3)
				$userPic = "<img src=\"".str_replace("img/avatars/", "img/avatars/", $user['picture'])."\" alt=\"\" style=\"width: 60px;\" />";

			$cellClass = ($cellClass+1) % 2;
			$memberList .= format(
	"
			<tr class=\"cell{0}\">
				<td>{1}</td>
				<td>{2}</td>
				<td>{3}</td>
				<td>{4}</td>
				<td>{5}</td>
				<td>{6}</td>
				<td>{7}</td>
				<td>{8}</td>
			</tr>
	",	$cellClass, $user['id'], $userPic, UserLink($user), $user['posts'],
		$user['average'], $user['karma'],
		($user['birthday'] ? cdate("M jS", $user['birthday']) : "&nbsp;"),
		cdate("M jS Y", $user['regdate'])
		);
		}
	} else
	{
		$memberList = format(
	"
			<tr class=\"cell0\">
				<td colspan=\"8\">
					".__("Nothing matched your search.")."
				</td>
			</tr>
	");
	}

	write(
	"
			<tr class=\"header1\">
				<th style=\"width: 30px; \">#</th>
				<th style=\"width: 62px; \">".__("Picture")."</th>
				<th>".__("Name")."</th>
				<th style=\"width: 50px; \">".__("Posts")."</th>
				<th style=\"width: 50px; \">".__("Average")."</th>
				<th style=\"width: 50px; \">".__("Karma")."</th>
				<th style=\"width: 80px; \">".__("Birthday")."</th>
				<th style=\"width: 130px; \">".__("Registered on")."</th>
			</tr>
			{0}
	",	$memberList);

	if($pagelinks)
	{
		write(
	"
			<tr class=\"cell2 smallFonts\">
				<td colspan=\"2\">
					".__("Page")."
				</td>
				<td colspan=\"6\">
					{0}
				</td>
			</tr>
	",	$pagelinks);
	}

	write("
		</table>
	");
	$noAutoHeader = true;
	die();
}


MakeCrumbs(array(__("Member list")=>actionLink("memberlist")), $links);

if (!$isBot)
{
	write(
"
	<script src=\"".resourceLink("lib/memberlist.js")."\"></script>
	<div id=\"userFilter\" style=\"margin-bottom: 1em; margin-left: auto; margin-right: auto; padding: 1em; padding-bottom: 0.5em; padding-top: 0.5em;\">
		".__("Sort by").": 
		".makeSelect("orderBy", array(
			"" => "Post count",
			"id" => "ID",
			"name" => "Name",
			"karma" => "Karma",
			"reg" => "Registration date"
		))." &nbsp;
		".__("Order").":
		".makeSelect("order", array(
			"desc" => "Descending",
			"asc" => "Ascending",
		))." &nbsp;
		".__("Sex").":
		".makeSelect("sex", array(
			"" => "(any)",
			"n" => "N/A",
			"f" => "Female",
			"m" => "Male"
		))." &nbsp;
		".__("Power").":
		".makeSelect("power", array(
			"" => "(any)",
			-1 => "Banned",
			0 => "Normal",
			1 => "Local Mod",
			2 => "Full Mod",
			3 => "Admin",
			4 => "Root",
			5 => "System"
		))."
		<div style=\"float: right;\">
			<form action=\"javascript:refreshMemberlist();\">
				<input type=\"text\" name=\"query\" id=\"query\" placeholder=\"".__("Search")."\" />
				<button id=\"submitQuery\">&rarr;</button>
			</form>
		</div>
	</div>
");
}

write("
	<div id=\"memberlist\">
		<div class=\"center\" style=\"padding: 2em;\">
			".__("Loading memberlist...")."
		</div>
	</div>
");


//We do not need a default index.
//All options are translatable too, so no need for __() in the array.
//Name is the same as ID.

function makeSelect($name, $options) {
	$result = "<select name=\"".$name."\" id=\"".$name."\">";

	$i = 0;
	foreach ($options as $key => $value) {
		$result .= "\n\t<option".($i = 0 ? " selected=\"selected\"" : "")." value=\"".$key."\">".__($value)."</option>";
	}

	$result .= "\n</select>";

	return $result;
}


?>
