<?php
set_time_limit(0);

include("lib/common.php");

if($loguser['powerlevel'] != 4)
	Kill(__("You're not a root user. There is nothing for you here."));

$src = "http://helmet.kafuka.org/abxd/repo/";
//$src= "http://127.0.0.1/abxd/repo/";

if(isset($_POST['localinstall']) && trim($_POST['setname']) != "" && trim($_POST['list']) != "")
{
	$lines = explode("\n", trim($_POST['list']));
	if(FetchResult("SELECT COUNT(*) FROM ranksets WHERE id=1") == 0)
		$setid = 1;
	else
		$setid = FetchResult("SELECT id+1 FROM ranksets WHERE (SELECT COUNT(*) FROM ranksets r2 WHERE r2.id=ranksets.id+1)=0 ORDER BY id ASC LIMIT 1");
	if($setid < 1) $setid = 1;
	$setname = mysql_real_escape_string($_POST['setname']);
	$setbase = mysql_real_escape_string($_POST['setbase']);
	Query("INSERT INTO `ranksets` (`id`, `name`) VALUES (".$setid.", '".$setname."')");
	$q = "INSERT INTO `ranks` (`rset`, `num`, `text`) VALUES\n";
	foreach($lines as $setitem)
	{
		$item = explode(", ", trim($setitem));
		$num = $item[0];
		$text = $item[1];
		$text = str_replace("<img src=\"", "<img src=\"img/ranks/".$setbase."/", $text);
		$text = mysql_real_escape_string($text);
		$q .= "(".$setid.", ".$num.", '".$text."'),\n";
	}
	$q = substr($q, 0, -2).";";
	Query($q);
	Alert(format(__("Installed rankset \"{0}\" on ID {1}."), $setname, $setid));
	die();
}

if(isset($_GET['delete']))
{
	$name = FetchResult("SELECT name FROM ranksets WHERE id=".(int)$_GET['delete']);
	if($name == -1)
		Alert(__("No such rankset."));
	else
	{
		$fourthOne = FetchResult("SELECT text FROM ranks WHERE rset=".(int)$_GET['delete']." LIMIT 4,1");
		if(strpos($fourthOne, "<img src=\"img/ranks/") !== FALSE)
		{
			//This set has images to delete.
			preg_match("/<img src=\"(.*?)\"/", $fourthOne, $match);
			$parts = explode("/", $match[1]);
			KillFolder("img/ranks/".$parts[2]);
		}
		Query("DELETE FROM ranksets WHERE id=".(int)$_GET['delete']);
		Query("DELETE FROM ranks WHERE rset=".(int)$_GET['delete']);
		Alert(Format(__("Rankset \"{0}\" deleted."), $name));
	}
}

$installedr = Query("select name from ranksets");
$installed = array();
while($set = Fetch($installedr))
	$installed[] = $set['name'];

if(!isset($_GET['set']))
{
	$rawlist = trim(@file_get_contents($src."getrankset.php?list"));
	if($rawlist == "")
		$content = __("Could not get the rankset list.");
	else
	{
		$lines = explode("\n", $rawlist);
		$items = "";
		foreach($lines as $set)
		{
			$item = explode("\t", trim($set));
			if(in_array($item[1], $installed))
			{
				$items .= format("
				<li>
					".__("<b>{1}</b> by {2} (already installed)")."
				</li>
", $item[0], $item[1], $item[2]);
			}
			else
			{
				$items .= format("
				<li>
					{0}
				</li>
", format(__("{0}{1}{2} by {3}"), "<a href=\"installrankset.php?set=".$item[0]."\">", $item[1], "</a>", $item[2]));
			}
		}
		$content = format("
			".__("The following ranksets are available:")."
			<ul>
				{0}
			</ul>
", $items);
	}
	
	$installed = Query("SELECT * FROM ranksets");
	$items = "";
	while($set = Fetch($installed))
	{
		$items .= format("
				<li>
					<a href=\"installrankset.php?delete={0}\">{1}</a>
				</li>", $set['id'], $set['name']);
	}
	if($items)
		$deleters = format("
			".__("Click a rankset to delete it.")."
			<ul>
				{0}
			</ul>
", $items);
	
	write("
	<div class=\"outline margin\" style=\"float: left; width: 40%\">
		<div class=\"errort\">
			<strong>".__("Install from the Repository")."</strong>
		</div>
		<div class=\"errorc cell2\" style=\"text-align: left; padding: 8px;\">
			{0}
		</div>
	</div>", $content);
	
	if($deleters)
		write("
	<div class=\"outline margin\" style=\"float: right; width: 30%\">
		<div class=\"errort\">
			<strong>".__("Delete a rankset")."</strong>
		</div>
		<div class=\"errorc cell2\" style=\"text-align: left; padding: 8px;\">
			{0}
		</div>
	</div>", $deleters);

	write("
	<form method=\"post\" action=\"installrankset.php\">
		<table class=\"outline margin\" style=\"float: left; width: 60%; clear: left\">
			<tr class=\"header0\">
				<th colspan=\"2\">".__("Install locally")."</th>
			</tr>
			<tr class=\"cell2\">
				<td colspan=\"2\" class=\"smallFonts\">
					".__("This method assumes you already have the required images in the correct place on the server.")."
				</td>
			</tr>
			<tr>
				<td class=\"cell1\">
					<label for=\"nm\">".__("Name")."</label>
				</td>
				<td class=\"cell0\">
					<input type=\"text\" id=\"nm\" name=\"setname\" maxlength=\"128\" />
				</td>
			</tr>
			<tr>
				<td class=\"cell1\">
					<label for=\"imd\">".__("Image directory")."</label> <img src=\"img/icons/icon4.png\" title=\"".__("Not required if the set doesn't use images. The whole \"/img/ranks/\" bit is implied.")."\" alt=\"[!]\" />
				</td>
				<td class=\"cell0\">
					<input type=\"text\" id=\"imd\" name=\"setbase\" maxlength=\"128\" />
				</td>
			</tr>
			<tr>
				<td class=\"cell1\">
					<label for=\"lst\">".__("List")."</label>
				</td>
				<td class=\"cell0\">
					<textarea id=\"lst\" name=\"list\" style=\"width: 98%; height: 256px;\">0, Non-poster
1, Newcomer
10, Poptart Cat
</textarea>
				</td>
			</tr>
			<tr class=\"cell2\">
				<td colspan=\"2\" style=\"text-align: right\">
					<input type=\"submit\" name=\"localinstall\" value=\"".__("Install")."\" />
				</td>
			</tr>
		</table>
	</form>");

	die();
}

$_GET['set'] = preg_replace("/[^abcdefghijklmnopqrstuvwxyz]/", "", $_GET['set']);

$rawset = file_get_contents($src."getrankset.php?id=".$_GET['set']);
if(substr($rawset, 0, 3) != "OK\n")
	Kill(__("Something went wrong:")."<br />".$rawset);
$lines = explode("\n", $rawset);
$setname = trim($lines[1]);
$setbase = trim($lines[2]);
$setitems = array_slice($lines, 4, -2);

if(strpos($rawset, "<img") !== false)
	@mkdir("img/ranks/".$setbase, 0777, true);

if(FetchResult("SELECT COUNT(*) FROM ranksets WHERE id=1") == 0)
	$setid = 1;
else
	$setid = FetchResult("SELECT id+1 FROM ranksets WHERE (SELECT COUNT(*) FROM ranksets r2 WHERE r2.id=ranksets.id+1)=0 ORDER BY id ASC LIMIT 1");
if($setid < 1) $setid = 1;
Query("INSERT INTO `ranksets` (`id`, `name`) VALUES (".$setid.", '".$setname."')");

$q = "INSERT INTO `ranks` (`rset`, `num`, `text`) VALUES\n";
$copies = 0;
$totalpics = 0;
foreach($setitems as $setitem)
{
	$item = explode("\t", trim($setitem));
	$num = $item[0];
	$text = $item[1];
	if(strpos($text, "<img") !== false)
	{
		$match = array();
		preg_match("/<img src=\"(.*)\" /U", $text, $match);
		$img = $match[1];
		$imgdst = "img/ranks/".$setbase."/".$img;
		$imgsrc = $src.$imgdst;
		if(!file_exists($imgdst))
		{
			file_put_contents($imgdst, file_get_contents($imgsrc));
			$copies++;
		}
		$text = str_replace("<img src=\"", "<img src=\"img/ranks/".$setbase."/", $text);
		$totalpics++;
	}
	$text = mysql_real_escape_string($text);
	$q .= "(".$setid.", ".$num.", '".$text."'),\n";
}
$q = substr($q, 0, -2).";";
Query($q);

$content = format(__("Installed rankset \"{0}\" on ID {1}."), $setname, $setid);
if($copies)
	$content .= format("<br />".__("Had to copy {0} files out of {1}."), $copies, $totalpics);

write("
	<div class=\"outline margin width50\">
		<div class=\"errort\">
			<strong>".__("Install a rankset")."</strong>
		</div>
		<div class=\"errorc cell2\" style=\"text-align: left; padding: 8px;\">
			{0}
		</div>
	</div>", $content);



function KillFolder($folderPath)
{
	if(is_dir($folderPath))
	{
		foreach(scandir($folderPath) as $value)
		{
			if($value != "." && $value != "..")
			{
				$value = $folderPath."/".$value;
				if(is_dir($value))
					KillFolder($value);
				else if(is_file($value))
					@unlink($value);
			}
		}
		return @rmdir($folderPath);
	}
	else
		return FALSE;
}

?>