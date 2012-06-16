<?php

$title = __("Uploader");

AssertForbidden("viewUploader");

$rootdir = "uploader";
//if(!is_file($rootdir."/.htaccess"))
{
	$here = $_SERVER['SCRIPT_FILENAME'];
	$here = substr($here, 0, strrpos($here, '/') + 1);
	$here = str_replace($_SERVER['DOCUMENT_ROOT'], '', $here);
//	print "<!-- ".$here." -->";
	file_put_contents($rootdir."/.htaccess", "RewriteEngine On\nRewriteRule ^(.+)$ ".$here."get.php?file=$1 [PT,L,QSA]\nRewriteRule ^$ ".$here."get.php?error [PT,L,QSA]");
}

if($selfsettings['uploaderWhitelist'])
	$goodfiles = explode(" ", $selfsettings['uploaderWhitelist']);

$badfiles = array("html", "htm", "php", "php2", "php3", "php4", "php5", "php6", "htaccess", "htpasswd", "mht", "js", "asp", "aspx", "cgi", "py", "exe", "com", "bat", "pif", "cmd", "lnk", "wsh", "vbs", "vbe", "jse", "wsf", "msc", "pl", "rb", "shtm", "shtml", "stm", "htc");

if(isset($_POST['action']))
	$_GET['action'] = $_POST['action'];
if(isset($_POST['fid']))
	$_GET['fid'] = $_POST['fid'];

$quota = $selfsettings['uploaderCap'] * 1024 * 1024;
$pQuota = $selfsettings['personalCap'] * 1024 * 1024;
$uploaderMaxFileSize = $selfsettings['uploaderMaxFileSize'];
$totalsize = foldersize($rootdir);

if(isset($_GET['sort']) && $_GET['sort'] == "filename" || $_GET['sort'] == "date")
	$skey = $_GET['sort'];
else
	$skey = "date";

$sortOptions = "<div class=\"margin smallFonts\">".__("Sort order").": <ul class=\"pipemenu\">";
$sortOptions .= ($skey == "filename") ? "<li>".__("Name")."</li>" : actionLinkTag(__("Name"), "uploader", "", "sort=filename")."</li>";
$sortOptions .= ($skey == "date") ? "<li>".__("Date")."</li>" : actionLinkTag(__("Date"), "uploader", "", "")."</a></li>";
$sortOptions .= "</ul></div>";
$sdir = ($skey == "date") ? " desc" : " asc";

if($_GET['action'] == __("Upload"))
{
	AssertForbidden("useUploader");
	if($loguserid)
	{
		$targetdir = $rootdir;
		$quot = $quota;
		$privateFlag = 0;
		if($_POST['private'])
		{
			$quot = $pQuota;
			$targetdir = $rootdir."/".$loguserid;
			$privateFlag = 1;
		}
		$totalsize = foldersize($targetdir);
		
		$files = scandir($targetdir);
		if(in_array($_FILES['newfile']['name'], $files))
			Alert(format(__("The file \"{0}\" already exists. Please delete the old copy before uploading a new one."), $_FILES['newfile']['name']));
		else
		{
			if($_FILES['newfile']['size'] == 0)
			{
				if($_FILES['newfile']['tmp_name'] == "")
					Alert(__("No file given."));
				else
					Alert(__("File is empty."));
			}
			else if($_FILES['newfile']['size'] > $uploaderMaxFileSize * 1024 * 1024)
			{
				Alert(format(__("File is too large. Maximum size is {0}."), BytesToSize($uploaderMaxFileSize * 1024 * 1024)));
			}
			else
			{
				$fname = $_FILES['newfile']['name'];
				$temp = $_FILES['newfile']['tmp_name'];
				$size = $_FILES['size']['size'];
				$parts = explode(".", $fname);
				$extension = end($parts);
				if($totalsize + $size > $quot)
					Alert(format(__("Uploading \"{0}\" would break the quota."), $fname));
				else if(in_array(strtolower($extension), $badfiles) || is_array($goodfiles) && !in_array(strtolower($extension), $goodfiles))
				{
					Alert(__("Forbidden file type."));
				}
				else
				{
					$description = strip_tags($_POST['description']);

					$newID = FetchResult("SELECT id+1 FROM {$dbpref}uploader WHERE (SELECT COUNT(*) FROM {$dbpref}uploader u2 WHERE u2.id={$dbpref}uploader.id+1)=0 ORDER BY id ASC LIMIT 1");
					if($newID < 1) $newID = 1;

					Query("insert into {$dbpref}uploader (id, filename, description, date, user, private) values (".$newID.", '".justEscape($fname)."', '".justEscape($description)."', ".time().", ".$loguserid.",".$privateFlag.")");
					copy($temp, $targetdir."/".$fname);
					Alert(format(__("File \"{0}\" has been uploaded."), $fname), __("Okay"));
					Report("[b]".$loguser['name']."[/] uploaded file \"[b]".$fname."[/]\"".($privateFlag ? " (privately)" : ""), $privateFlag); 
				}
			}
		}
	}
	else
		Alert(__("You must be logged in to upload."));
}
else if($loguserid && $_GET['action'] == "multidel" && $_POST['del']) //several files
{
	$deleted = 0;
	foreach($_POST['del'] as $fid => $on)
	{
		if($loguser['powerlevel'] > 2)
			$check = FetchResult("select count(*) from {$dbpref}uploader where id = ".$fid, 0, 0);
		else
			$check = FetchResult("select count(*) from {$dbpref}uploader where user = ".$loguserid." and id = ".$fid, 0, 0);

		if($check)
		{
			$entry = Fetch(Query("select * from {$dbpref}uploader where id = ".$fid));
			if($entry['private'])
				@unlink($rootdir."/".$entry['user']."/".$entry['filename']);
			else
				@unlink($rootdir."/".$entry['filename']);
			Query("delete from {$dbpref}uploader where id = ".$fid);
			$deleted++;
		}
	}
	Alert(format(__("{0} deleted."), Plural($deleted, __("file"))));
}
else if($_GET['action'] == "delete") //single file
{
	$fid = (int)$_GET['fid'];

	if($loguser['powerlevel'] > 2)
		$check = FetchResult("select count(*) from {$dbpref}uploader where id = ".$fid, 0, 0);
	else
		$check = FetchResult("select count(*) from {$dbpref}uploader where user = ".$loguserid." and id = ".$fid, 0, 0);
	
	if($check)
	{
		$entry = Fetch(Query("select * from {$dbpref}uploader where id = ".$fid));
		if($entry['private'])
			@unlink($rootdir."/".$entry['user']."/".$entry['filename']);
		else
			@unlink($rootdir."/".$entry['filename']);
		Query("delete from {$dbpref}uploader where id = ".$fid);
		Report("[b]".$loguser['name']."[/] deleted \"[b]".$entry['filename']."[/]\".", 1);
		Alert(format(__("Deleted \"{0}\"."), $entry['filename']), __("Okay"));
	}
	else
		Alert(__("No such file or not yours to mess with."));
}

$totalsize = foldersize($rootdir);

$head = format(
"
		<tr class=\"header1\">
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
");
if($loguserid)
	$head = str_replace("<tr class=\"header1\">","<tr class=\"header1\"><th style=\"width: 22px;\"><input type=\"checkbox\" id=\"ca\" onchange=\"checkAll();\" /></th>", $head);

if($loguserid && is_dir($rootdir."/".$loguserid) || $loguser['powerlevel'] > 2)
{
	if($loguser['powerlevel'] > 2)
		$entries = Query("select {$dbpref}uploader.*, {$dbpref}users.name, {$dbpref}users.displayname, {$dbpref}users.powerlevel, {$dbpref}users.sex from {$dbpref}uploader left join {$dbpref}users on {$dbpref}uploader.user = {$dbpref}users.id where {$dbpref}uploader.private = 1 order by user, ".$skey.$sdir);
	else
		$entries = Query("select {$dbpref}uploader.*, {$dbpref}users.name, {$dbpref}users.displayname, {$dbpref}users.powerlevel, {$dbpref}users.sex from {$dbpref}uploader left join {$dbpref}users on {$dbpref}uploader.user = {$dbpref}users.id where {$dbpref}uploader.user = ".$loguserid." and {$dbpref}uploader.private = 1 order by ".$skey.$sdir);

	if(NumRows($entries) == 0)
	{
		$private = format(
	"
		<table class=\"outline margin\">
			<tr class=\"header1\">
				<th colspan=\"7\">".__("Private Files")."</th>
			</tr>
			<tr class=\"cell1\">
				<td colspan=\"4\">
					".__("Your personal folder is empty.")."
				</td>
			</tr>
		</table>
	");
	}
	else
	{
		$havePrivates = true;
		$private = format(
"
		<table class=\"outline margin\">
			<tr class=\"header0\">
				<th colspan=\"7\">".__("Private Files")."</th>
			</tr>
			{0}
", $head);
		$lastID = -1;
		while($entry = Fetch($entries))
		{
			$delete = "";
			$multidel = "";
			if($loguserid)
				$multidel = "<td><input type=\"checkbox\" name=\"delete[".$entry['id']."]\" disabled=\"disabled\" /></td>";
			if($loguserid == $entry['user'] || $loguser['powerlevel'] > 2)
			{
				$delete = "<sup>&nbsp;".actionLinkTag("&#x2718;", "uploader", "", "action=delete&fid=".$entry['id'])."</sup>";
				$multidel = "<td><input type=\"checkbox\" name=\"del[".$entry['id']."]\" /></td>";
			}

			$cellClass = ($cellClass+1) % 2;

			if($entry['user'] != $lastID)
			{
				if($lastID == -1)
					$lastID = $entry['user'];
				else
				{
					$lastID = $entry['user'];
					$private .= format(
"
			<tr class=\"header1\">
				<th colspan=\"7\" style=\"height: 2px;\"></th>
			</tr>
");
				}
			}

			$private .= format(
	"
			<tr class=\"cell{0}\">
				{8}
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
		BytesToSize(@filesize($rootdir."/".$entry['user']."/".$entry['filename'])), UserLink($entry, "user"),
		$entry['user'], $multidel);
		}
	}
	$private .= "</table>";
	

}

$entries = Query("select {$dbpref}uploader.*, users.name, users.displayname, users.powerlevel, users.sex from {$dbpref}uploader left join {$dbpref}users on {$dbpref}uploader.user = {$dbpref}users.id where {$dbpref}uploader.private = 0 order by ".$skey.$sdir);

if(NumRows($entries) == 0 && !$havePrivates)
{
	$public = format(
"
	<table class=\"outline margin\">
		<tr class=\"header0\">
			<th colspan=\"7\">".__("Public Files")."</th>
		</tr>
		<tr class=\"cell1\">
			<td colspan=\"4\">
				".__("The uploader is empty.")."
			</td>
		</tr>
	</table>
");
}
else
{
	if($havePrivates)
		$head = str_replace("<input type=\"checkbox\" id=\"ca\" onchange=\"checkAll();\" />", "", $head);

	$public = format(
"
	<table class=\"outline margin\">
		<tr class=\"header0\">
			<th colspan=\"7\">".__("Public Files")."</th>
		</tr>
		{0}
", $head);
	while($entry = Fetch($entries))
	{
		$delete = "";
		$multidel = "";
		if($loguserid)
			$multidel = "<td><input type=\"checkbox\" name=\"delete[".$entry['id']."]\" disabled=\"disabled\" /></td>";
		if($loguserid == $entry['user'] || $loguser['powerlevel'] > 2)
		{
			$delete = "<sup>&nbsp;".actionLinkTag("&#x2718;", "uploader", "", "action=delete&fid=".$entry['id'])."</sup>";
			$multidel = "<td><input type=\"checkbox\" name=\"del[".$entry['id']."]\" /></td>";
		}
		$cellClass = ($cellClass+1) % 2;

		$public .= format(
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
	BytesToSize(@filesize($rootdir."/".$entry['filename'])), UserLink($entry, "user"), $multidel);
	}
	if($loguserid)
		$public .= format("
			<tr class=\"header1\">
				<th style=\"text-align: right;\" colspan=\"6\">
					<input type=\"hidden\" name=\"action\" value=\"multidel\" />
					<a href=\"javascript:void();\" onclick=\"document.forms[2].submit();\">".__("delete checked")."</a>
				</th>
			</tr>");
	$public .= "</table>";
}

$maxSizeMult = $uploaderMaxFileSize * 1024 * 1024;
if($loguserid && IsAllowed("useUploader"))
{
	$uploadPart = format(
"
<script type=\"text/javascript\">
	window.addEventListener(\"load\", function() { hookUploadCheck(\"newfile\", 1, {1}) }, false);
</script>
<form action=\"".actionLink("uploader")."\" method=\"post\" enctype=\"multipart/form-data\">
	<table class=\"outline margin\">
		<tr class=\"header0\">
			<th colspan=\"4\">".__("Upload")."</th>
		</tr>
		<tr class=\"cell2\">
			<td>
				<input type=\"file\" id=\"newfile\" name=\"newfile\" style=\"width: 80%;\" />
			</td>
			<td>
				<input type=\"text\" name=\"description\" style=\"width: 80%;\" />
				<label>
					<input type=\"checkbox\" name=\"private\" />&nbsp;".__("Private")."
				</label>
			</td>
			<td>
				<input type=\"submit\" id=\"submit\" name=\"action\" value=\"".__("Upload")."\" disabled=\"disabled\" />
			</td>
		</tr>
		<tr class=\"cell1 smallFonts\">
			<td colspan=\"3\">
				".__("The maximum upload size is {0} per file. You can upload the following types: {2}.")."
				<div id=\"sizeWarning\" style=\"display: none; font-weight: bold\">".__("File is too large.")."</div>
				<div id=\"typeWarning\" style=\"display: none; font-weight: bold\">".__("File is not an allowed type.")."</div>
			</td>
		</tr>
	</table>
</form>
", BytesToSize($maxSizeMult), $maxSizeMult, $selfsettings['uploaderWhitelist']);
}

$bar = "&nbsp;0%";

if($totalsize > 0)
{
	$width = floor(100 * ($totalsize / $quota));
	if($width > 0)
	{
		$color = "green";
		if($width > 75)
			$color = "yellow";
		if($width > 90)
			$color = "orange";
		if($width > 100)
		{
			$width = 100;
			$color = "red;";
		}
		$alt = format("{0}&nbsp;of&nbsp;{1},&nbsp;{2}%", BytesToSize($totalsize), BytesToSize($quota), $width);
		$bar = format("<div class=\"pollbar\" style=\"width: {0}%; background: {2}\" title=\"{1}\">&nbsp;$width%</div>", $width, $alt, $color);
	}
}

write(
"
	<table class=\"outline margin\">
		<tr class=\"header0\">
			<th colspan=\"2\">".__("Status")."</th>
		</tr>
		<tr class=\"cell2\">
			<td style=\"width:40%\">
				".__("Public space usage: {0} of {1}")."
			</td><td>
				<div class=\"pollbarContainer\" style=\"width: 90%;\">
					{2}
				</div>
			</td>
		</tr>
	</table>
",	BytesToSize($totalsize), BytesToSize($quota), $bar);

$bar = "&nbsp;0%";

if($loguserid && is_dir($rootdir."/".$loguserid))
{
	$personalsize = foldersize($rootdir."/".$loguserid);
	if($personalsize > 0)
	{
		$width = floor(100 * ($personalsize / $pQuota));
		if($width > 0)
		{
			$color = "green";
			if($width > 75)
				$color = "yellow";
			if($width > 90)
				$color = "orange";
			if($width > 100)
			{
				$width = 100;
				$color = "red;";
			}
			$alt = format("{0}&nbsp;of&nbsp;{1},&nbsp;{2}%", BytesToSize($personalsize), BytesToSize($pQuota), $width);
			$bar = format("<div class=\"pollbar\" style=\"width: {0}%; background: {2}\" title=\"{1}\">&nbsp;$width%</div>", $width, $alt, $color);
		}
	}
	write(
"
<div style=\"clear: both;\">
	<div class=\"pollbarContainer\" style=\"float: right; width: 50%;\">
		{2}
	</div>
	".__("Personal folder space usage: {0} of {1}")."
</div>
",	BytesToSize($personalsize), BytesToSize($pQuota), $bar);
}

write($uploadPart);
write("<form method=\"post\" action=\"".actionLink("uploader")."\">");
write($sortOptions);
write($private);
write($public);
write("</form>");

//From the PHP Manual User Comments
function foldersize($path)
{
	$total_size = 0;
	if (!file_exists($path))
		mkdir($path);
	$files = scandir($path);
	$files = array_slice($files, 2);
	foreach($files as $t)
	{
		$size = filesize($path . "/" . $t);
		$total_size += $size;
	}
	return $total_size;
}

?>
