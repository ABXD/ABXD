<?php
//  AcmlmBoard XD - Rankset import tool
//  Access: administrators

AssertForbidden("addRanks");

if($loguser['powerlevel'] < 3)
	Kill(__("You must be an administrator to import ranksets."));

Query("truncate table {$dbpref}ranks");
Query("truncate table {$dbpref}ranksets");

include("ranksets.php");

$bads = array(" ","-",".",",", "'", '"');

write(
"
	<table class=\"outline margin width25\">
		<tr class=\"header1\">
			<th>
				".__("Postcount")."
			</th>
			<th>
				".__("Rank")."
			</th>
		</tr>
");

foreach($ranks as $rankset)
{
	write(
"
		<tr class=\"header0\">
			<th colspan=\"2\">
				{0}
			</th>
		</tr>
", $rankset['name']);

	if(!$rankset['directory'])
		$rankset['directory'] = strtolower($rankset['name']);

	$index++;
	$description = format(__("Set index is {0}. Base directory is {1}."), $index, "<a href=\"img/ranks/".$rankset['directory']."/\"><code>".$rankset['directory']."</code></a>");
	if($rankset['notolower'])
		$description .= " ".__("Set does not use lowercase filenames.");
	if($rankset['noimages'])
		$description .= " ".__("Set is text-only.");

	write(
"
		<tr class=\"cell1\">
			<td colspan=\"2\">
				{0}
			</td>
		</tr>
", $description);

	Query("insert into {$dbpref}ranksets (name) values ('".$rankset['name']."')");

	foreach($rankset['ranks'] as $val => $text)
	{
		$img = "<img src=\"img/ranks/".$rankset['directory']."/".str_replace($bads,"",(!$rankset['notolower']?strtolower($text):$text)).".png\" alt=\"".$text."\" /> ".($rankset['splitlines'] ? "<br />" : "").$text;
		if($val < 10 || $rankset['noimages'])
			$img = $text;
			write(
"
		<tr class=\"cell0\">
			<td>
				{0}
			</td>
			<td>
				{1}
			</td>
		</tr>
", $val, $img);
		Query("insert into {$dbpref}ranks (rset, num, text) values (".$index.", ".$val.", '".justEscape($img)."')");
	}
}

write(
"
	</table>
	<div>
		".__("The above ranks and sets have been imported.")."
	</div>
");

?>
