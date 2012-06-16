	<?php

if($loguser['powerlevel'] < 1)
	Kill("Access denied.");

MakeCrumbs(array(__("Admin") => actionLink("admin"), __("Log") => actionLink("log")), "");

//$here = "http://helmet.kafuka.org/nikoboard";
$full = GetFullURL();
$here = substr($full, 0, strrpos($full, "/"))."/";
$there = "./"; //"/";

$logR = Query("select * from {$dbpref}reports order by time desc");
while($item = Fetch($logR))
{
	//print $item['text'];
	$blar = $item['text'];
	$blar = htmlspecialchars($blar);
	$blar = str_replace("[g]", "", $blar);
	$blar = str_replace("[b]", "", $blar);
	$blar = str_replace("[/]", "", $blar);
	$blar = str_replace("-&gt;", "&rarr;", $blar);

	$blar = str_replace($here, $there, $blar);

	$cellClass = ($cellClass + 1) % 2;
	$log .= format(
"
		<tr>
			<td class=\"cell2\">
				{1}&nbsp;
			</td>
			<td class=\"cell{0}\">
				{2}
			</td>
		</tr>
", $cellClass, str_replace(" ", "&nbsp;", TimeUnits(time() - $item['time'])), $blar);
}

write(
"
	<table class=\"outline margin\">
		<tr class=\"header1\">
			<th>
				".__("Time")."
			</th>
			<th>
				".__("Event")."
			</th>
		</tr>
		{0}
	</table>
", $log);

?>
