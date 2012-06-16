<?php

$title = __("Uploader");

AssertForbidden("viewUploader");

$rootdir = "uploader";

if($uploaderWhitelist)
	$goodfiles = explode(" ", $uploaderWhitelist);

$badfiles = array("html", "htm", "php", "php2", "php3", "php4", "php5", "php6", "htaccess", "htpasswd", "mht", "js", "asp", "aspx", "cgi", "py", "exe", "com", "bat", "pif", "cmd", "lnk", "wsh", "vbs", "vbe", "jse", "wsf", "msc", "pl", "rb", "shtm", "shtml", "stm", "htc");

function listCategory($cat)
{
	global $loguser, $loguserid, $rootdir, $userSelectUsers, $dbpref;
	
	if(isset($_GET['sort']) && $_GET['sort'] == "filename" || $_GET['sort'] == "date")
		$skey = $_GET['sort'];
	else
		$skey = "date";

	$sortOptions = "<div class=\"margin smallFonts\">".__("Sort order").": <ul class=\"pipemenu\">";
	$sortOptions .= ($skey == "filename") 
			?"<li>".__("Name")."</li>"
			:actionLinkTagItem(__("Name"), "uploaderlist", "", "cat=${_GET["cat"]}&sort=filename");
	$sortOptions .= ($skey == "date") 
			?"<li>".__("Date")."</li>"
			:actionLinkTagItem(__("Date"), "uploaderlist", "", "cat=${_GET["cat"]}&sort=date");
	$sortOptions .= "</ul></div>";
	$sdir = ($skey == "date") ? " desc" : " asc";


	print $sortOptions;

	if($cat == -1)
		$condition = "uploader.user = ".$loguserid." and uploader.private = 1";
	else if($cat == -2 && $loguser['powerlevel'] > 2)
		$condition = "uploader.private = 1";
	else
		$condition = "uploader.private = 0 and uploader.category = $cat";
	
	$errormsg = __("The category is empty.");
	if($cat < 0)
		$errormsg = __("You have no private files.");
		
	$entries = Query("select {$dbpref}uploader.*, $userSelectUsers from uploader left join users on uploader.user = users.id where $condition order by ".$skey.$sdir);

	$checkbox = "";
	if($loguserid)
	{
		$checkbox = "<input type=\"checkbox\" id=\"ca\" onchange=\"checkAll();\" />";
		$checkbox = "<th style=\"width: 22px;\">$checkbox</th>";
	}

	if(NumRows($entries) == 0)
	{
		print "
		<table class=\"outline margin\">
			<tr class=\"header0\">
				<th colspan=\"7\">".__("Files")."</th>
			</tr>
			<tr class=\"cell1\">
				<td colspan=\"4\">
					".$errormsg."
				</td>
			</tr>
		</table>
		";
	}
	else
	{
		print 
		"
		<table class=\"outline margin\">
			<tr class=\"header0\">
				<th colspan=\"7\">".__("Files")."</th>
			</tr>
			
		";

		print 	"
			<tr class=\"header1\">
				$checkbox
				<th>
					".__("File")."
				</th>
				<th>
					".__("Description")."
				</th>
				<th>
					".__("Size")."
				</th>
				<th>
					".__("Uploader")."
				</th>
			</tr>
		";

		while($entry = Fetch($entries))
		{
			$delete = "";
			$multidel = "";
			if($loguserid)
				$multidel = "<td><input type=\"checkbox\" name=\"delete[".$entry['id']."]\" disabled=\"disabled\" /></td>";
			if($loguserid == $entry['user'] || $loguser['powerlevel'] > 2)
			{
				$delete = "&nbsp;<sup>"
					.actionLinkTag("&#x2718;", "uploader", "", "action=delete&fid=".$entry['id']."&cat=".$_GET["cat"])
					."</sup>";
				$multidel = "<td><input type=\"checkbox\" name=\"del[".$entry['id']."]\" /></td>";
			}
			$cellClass = ($cellClass+1) % 2;
			
			$filepath = $rootdir."/".$entry['filename'];
			if($entry['private'])
				$filepath = $rootdir."/".$entry['user']."/".$entry['filename'];
				
			print format(			
			"
			<tr class=\"cell{0}\">
				{7}
				<td>
					<a href=\"get.php?id={1}\">{2}</a>{3}
				</td>
				<td>
					{4}
				</td>
				<td>
					{5}
				</td>
				<td>
					{6}
				</td>
			</tr>
			",	$cellClass, $entry['id'], $entry['filename'], $delete, $entry['description'],
				BytesToSize(@filesize($filepath)), UserLink($entry, "user"), $multidel);
		}
		
		
		if($loguserid)
		{
			$entries = Query("select * from {$dbpref}uploader_categories order by ord");
			$movelist = "";
			
			while($entry = Fetch($entries))
			{
				$movelist .= "<option value='${entry["id"]}'>${entry["name"]}</option>";
			}
			$movelist = "<select name='destcat' size='1'>$movelist</select>";

			print format("
				<tr class=\"header1\">
					<th style=\"text-align: right;\" colspan=\"6\">
						<input type=\"hidden\" id='actionfield' name=\"action\" value=\"multidel\" />
						<a href=\"javascript:void();\" onclick=\"document.getElementById('actionfield').value = 'multidel'; document.forms[1].submit();\">".__("delete checked")."</a>
						<a href=\"javascript:void();\" onclick=\"document.getElementById('actionfield').value = 'multimove'; document.forms[1].submit();\">".__("Move checked to")."</a>$movelist
					</th>
				</tr>");
		}
			print "</table>";
	}
}


$cat = getCategory($_GET["cat"]);
$links = actionLinkTag("Upload file", "uploader", "", "action=uploadform&cat=".$_GET["cat"]);

if($_GET["cat"] == -2)
	$links = "";
if($isBot)
	$links = "";
if(!$loguserid)
	$links = "";

MakeCrumbs(array(
				"Uploader"=>actionLink("uploader"), 
				$cat["name"] => actionLink("uploaderlist", "", "cat=".$cat["id"]), 
				), $links);

print "<form method=\"post\" action=\"".actionLink("uploader", "", "cat=${_GET["cat"]}")."\">";
listCategory($_GET["cat"]);
print "</form>";


function getCategory($cat)
{
	global $dbpref;
	if (!is_numeric($cat))
		Kill('Invalid category');

	if($cat >= 0)
	{
		$qCategory = "select * from {$dbpref}uploader_categories where id=".$cat;
		$rCategory = Query($qCategory);
		if(NumRows($rCategory) == 0) Kill("Invalid category");
		$rcat = Fetch($rCategory);
	}
	else if($cat == -1)
		$rcat = array("id" => -1, "name" => "Private files");
	else if($cat == -2)
		$rcat = array("id" => -2, "name" => "All private files");
	else
		Kill('Invalid category');

	return $rcat;
}

?>
