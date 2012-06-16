<?php

$title = __("Ranks");
AssertForbidden("viewRanks");

$setCount = FetchResult("select count(*) from {$dbpref}ranksets");
if($setCount == 0)
	Kill(__("No ranksets have been defined."));

$users = array();
$rUsers = Query("select id, name, displayname, powerlevel, sex, posts from {$dbpref}users order by id asc");
while($user = Fetch($rUsers))
	$users[$user['id']] = $user;

$rankset = $loguser['rankset'];
if($rankset == 0)
	$rankset = 1;
if(isset($_POST['rankset']))
	$rankset = (int)$_POST['rankset'];

$ranks = array();
$rRanks = Query("select num, text from {$dbpref}ranks where rset=".$rankset." order by num asc");
while($rank = Fetch($rRanks))
	$ranks[] = $rank;

$rSets = Query("select * from {$dbpref}ranksets order by id asc");
$selected[$rankset] = " selected = \"selected\"";
$ranksets = "";
while($set = Fetch($rSets))
	$ranksets .= format(
"
					<option value=\"{0}\"{1}>{2}</option>
",	$set['id'], $selected[$set['id']], $set['name']);

write(
"
<form action=\"".actionLink("ranks")."\" method=\"post\" id=\"myForm\">
	<table class=\"outline margin width25\">
		<tr class=\"header0\">
			<th colspan=\"2\">
				".__("User ranks")."
			</th>
		</tr>
		<tr class=\"cell0\">
			<td>
				".__("Set")."
			</td>
			<td>
				<select name=\"rankset\" size=\"1\" onchange=\"myForm.submit();\">
					{0}
				</select>
				<input type=\"submit\" value=\"".__("Change")."\" />
			</td>
		</tr>
	</table>
</form>
", $ranksets);


//Handle climbing the ranks again
//$users[1]['posts'] = 6000;
$climbingAgain = array();
for($i = 0; $i < count($users); $i++)
{
	if($users[$i]['posts'] > 5100)
	{
		//print $users[$i]['name']." has ".$users[$i]['posts']." posts. ";
		$climbingAgain[] = UserLink($users[$i]);
		$users[$i]['posts'] %= 5000;
		if($users[$i]['posts'] < 10)
			$users[$i]['posts'] = 10;
		//print "Reset to ".$users[$i]['posts']."...";
	}
}
if(count($climbingAgain))
	$climbingAgain = format(
"
	<tr class=\"header0\">
		<th colspan=\"3\" style=\"height: 4px;\"></th>
	</tr>
	<tr class=\"cell0\">
		<td colspan=\"2\">".__("Climbing the Ranks Again")."</td>
		<td>
			{0}
		</td>
	</tr>
", join(", ", $climbingAgain));
else
	$climbingAgain = "";


$ranklist = "";
for($i = 0; $i < count($ranks); $i++)
{
	$rank = $ranks[$i];
	$nextRank = $ranks[$i+1];
	if($nextRank['num'] == 0)
		$nextRank['num'] = $ranks[$i]['num'] + 1;
	$members = array();
	foreach($users as $user)
	{
		if($user['posts'] >= $rank['num'] && $user['posts'] < $nextRank['num'])
			$members[] = UserLink($user);
	}
	$rankText = ($loguser['powerlevel'] > 0 || $loguser['posts'] >= $rank['num'] || count($members) > 0) ? str_replace("<br />", " ", $rank['text']) : "???";
	if(count($members) == 0)
		$members = "&nbsp;";
	else
		$members = join(", ", $members);

	$cellClass = ($cellClass+1) % 2;
	
	$ranklist .= format(
"
	<tr class=\"cell{0}\">
		<td class=\"cell2\">{1}</td>
		<td>{2}</td>
		<td>{3}</td>
	</tr>
", $cellClass, $rankText, $rank['num'], $members);
}
write(
"
<table class=\"width75 margin outline\">
	<tr class=\"header1\">
		<th>
			".__("Rank")."
		</th>
		<th>
			".__("To get", 1)."
		</th>
		<th>
			&nbsp;
		</th>
	</tr>
	{0}
	{1}
</table>
",	$ranklist, $climbingAgain);

?>
