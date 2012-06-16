<?php

$title = __("Uploader");

AssertForbidden("viewUploader");

$rootdir = "uploader";
if(!is_file($rootdir."/.htaccess"))
{
	$here = $_SERVER['SCRIPT_FILENAME'];
	$here = substr($here, 0, strrpos($here, '/') + 1);
	$here = str_replace($_SERVER['DOCUMENT_ROOT'], '', $here);
	@mkdir('uploader');
	file_put_contents($rootdir."/.htaccess", "RewriteEngine On\nRewriteRule ^(.+)$ ".$here."get.php?file=$1 [PT,L,QSA]\nRewriteRule ^$ ".$here."get.php?error [PT,L,QSA]");
}

if($uploaderWhitelist)
	$goodfiles = explode(" ", $selfsettings['uploaderWhitelist']);

$badfiles = array("html", "htm", "php", "php2", "php3", "php4", "php5", "php6", "htaccess", "htpasswd", "mht", "js", "asp", "aspx", "cgi", "py", "exe", "com", "bat", "pif", "cmd", "lnk", "wsh", "vbs", "vbe", "jse", "wsf", "msc", "pl", "rb", "shtm", "shtml", "stm", "htc");

if(isset($_POST['action']))
	$_GET['action'] = $_POST['action'];
if(isset($_POST['fid']))
	$_GET['fid'] = $_POST['fid'];

$quota = $selfsettings['uploaderCap'] * 1024 * 1024;
$pQuota = $selfsettings['personalCap'] * 1024 * 1024;
$totalsize = foldersize($rootdir);

$maxSizeMult = $selfsettings['uploaderMaxFileSize'] * 1024 * 1024;

if($_GET['action'] == "uploadform")
{

	$cat = getCategory($_GET["cat"]);
	if (!is_numeric($_GET["cat"]))
		Kill('Invalid category');

	$cat = getCategory($_GET["cat"]);

	MakeCrumbs(array(
					"Uploader"=>actionLink("uploader"), 
					$cat["name"] => actionLink("uploaderlist", "", "cat=".$cat["id"]), 
					"Upload new file" => actionLink("uploader", "", "action=uploadforum&cat=".$cat["id"])), $links);

	if($loguserid && IsAllowed("useUploader"))
	{
		print format(
		"
		<script type=\"text/javascript\">
			window.addEventListener(\"load\", function() { hookUploadCheck(\"newfile\", 1, {1}) }, false);
		</script>
		<form action=\"".actionLink("uploader")."\" method=\"post\" enctype=\"multipart/form-data\">
			<input type='hidden' name='cat' value='${_GET["cat"]}'>
			<table class=\"outline margin\">
				<tr class=\"header0\">
					<th colspan=\"4\">".__("Upload")."</th>
				</tr>
				<tr class=\"cell0\">
					<td>File</td><td>
						<input type=\"file\" id=\"newfile\" name=\"newfile\" style=\"width: 80%;\" />
					</td>
				</tr>
				<tr class=\"cell1\">
					<td>Description</td><td>
						<input type=\"text\" name=\"description\" style=\"width: 80%;\" />
					</td>
				</tr>
				<tr class=\"cell0\">
					<td></td><td>
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
}

else if($_GET['action'] == __("Upload"))
{
	AssertForbidden("useUploader");
	if($loguserid)
	{
		$cat = getCategory($_POST["cat"]);
		$targetdir = $rootdir;
		$quot = $quota;
		$privateFlag = 0;
		if($_POST['cat'] == -1)
		{
			$quot = $pQuota;
			$targetdir = $rootdir."/".$loguserid;
			$privateFlag = 1;
		}
		$totalsize = foldersize($targetdir);
		
		mkdir($targetdir);
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
			else if($_FILES['newfile']['size'] > $selfsettings['uploaderMaxFileSize'] * 1024 * 1024)
			{
				Alert(format(__("File is too large. Maximum size is {0}."), BytesToSize($selfsettings['uploaderMaxFileSize'] * 1024 * 1024)));
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

					Query("insert into {$dbpref}uploader (id, filename, description, date, user, private, category) values (".$newID.", '".justEscape($fname)."', '".justEscape($description)."', ".time().", ".$loguserid.",".$privateFlag.",".$_POST["cat"].")");
					copy($temp, $targetdir."/".$fname);
					Report("[b]".$loguser['name']."[/] uploaded file \"[b]".$fname."[/]\"".($privateFlag ? " (privately)" : ""), $privateFlag); 

					die(header("Location: ".actionLink("uploaderlist", "", "cat=".$_POST["cat"])));
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
	die(header("Location: ".actionLink("uploaderlist", "", "cat=".$_GET["cat"])));
}
else if($loguserid && $_GET['action'] == "multimove" && $_POST['del']) //several files
{
	
	$moved = 0;
	$newcat = $_POST['destcat'];
	if (!is_numeric($newcat))
		Kill('Invalid category ID');

	foreach($_POST['del'] as $fid => $on)
	{
		if($loguser['powerlevel'] > 2)
			$check = FetchResult("select count(*) from {$dbpref}uploader where id = ".$fid, 0, 0);
		else
			$check = FetchResult("select count(*) from {$dbpref}uploader where user = ".$loguserid." and id = ".$fid, 0, 0);

		if($check)
		{
			if(!$entry['private'])
			{
				$entry = Fetch(Query("update {$dbpref}uploader set `category` = $newcat where id = ".$fid));
				$moved++;
			}
		}
	}
	die(header("Location: ".actionLink("uploaderlist", "", "cat=".$_GET["cat"])));
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
		die(header("Location: ".actionLink("uploaderlist", "", "cat=".$_GET["cat"])));
	}
	else
		Alert(__("No such file or not yours to mess with."));
}
else
{
	MakeCrumbs(array(
					"Uploader"=>actionLink("uploader")), $links);

	$errormsg = __("No categories found.");
	$entries = Query("select * from {$dbpref}uploader_categories order by ord");

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
		<table class=\"outline margin width50\">
			<tr class=\"header0\">
				<th colspan=\"7\">".__("Categories")."</th>
			</tr>
		";

		$cellClass = 0;
				
		while($entry = Fetch($entries))
		{
			$filecount = FetchResult("select count(*) from {$dbpref}uploader where category = ".$entry['id'], 0, 0);
		
			print "<tr class=\"cell$cellClass\"><td>";
			print actionLinkTag($entry['name'], "uploaderlist", "", "cat=".$entry['id']);
			print "<br>";
			print $entry['description'];
			print "<br>";
			print $filecount." files.";
			print "<br>";
			print "</td></tr>";
			$cellClass = ($cellClass+1) % 2;
		}
		
		if($loguserid)
		{
			$filecount = FetchResult("select count(*) from {$dbpref}uploader where uploader.user = ".$loguserid." and uploader.private = 1", 0, 0);

			print "<tr class=\"cell$cellClass\"><td>";
			print actionLinkTag("Private files", "uploaderlist", "", "cat=-1");
			print "<br>";
			print "Only for you.";
			print "<br>";
			print $filecount." files.";
			print "<br>";
			print "</td></tr>";

			$cellClass = ($cellClass+1) % 2;

			if($loguser['powerlevel'] > 2)
			{
				$filecount = FetchResult("select count(*) from {$dbpref}uploader where uploader.private = 1", 0, 0);

				print "<tr class=\"cell$cellClass\"><td>";
				print actionLinkTag("All private files", "uploaderlist", "", "cat=-2");
				print "<br>";
				print $filecount." files.";
				print "<br>";
				print "</td></tr>";
			}
		}
		print "</table>";
	}
}

//From the PHP Manual User Comments
function foldersize($path)
{
	$total_size = 0;
	$files = scandir($path);
	$files = array_slice($files, 2);
	foreach($files as $t)
	{
		$size = filesize($path . "/" . $t);
		$total_size += $size;
	}
	return $total_size;
}

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
