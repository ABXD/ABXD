<div style="width: 75%; margin-left: auto; margin-right: auto;">
<?php

/*
	TEMPORARY ABXD download page -- Mega-Mario
	This shouldn't stay in the repo, you don't want
	every ABXD board to have a copy of this bundled
*/

$downloads = @file_get_contents('downloads/listing.dat');
if (!$downloads)
{
	@mkdir('downloads', 0755);
	$downloads = array();
	@file_put_contents('downloads/listing.dat', serialize($downloads));
}
else $downloads = unserialize($downloads);

if (isset($_POST['upload']) && $loguser['powerlevel'] > 2)
{
	if (!trim($_POST['name'])) Kill("You must enter a name.");
	
	$filename = $_FILES['file']['name'];
	$tmpfile = $_FILES['file']['tmp_name'];
	if (!file_exists($tmpfile)) Kill("File upload failed.");
	
	$ext = substr($filename, strlen($filename)-3);
	$allowed_ext = array('zip', 'rar');
	if (!in_array($ext, $allowed_ext)) Kill("Invalid filetype.");
	
	$file = fopen($tmpfile, 'rb');
	$tag = fread($file, 4);
	fclose($file);
	
	$allowed_tag = array("PK\x03\x04", "Rar!");
	if (!in_array($tag, $allowed_tag)) Kill("Invalid filetype.");
	
	copy($tmpfile, 'downloads/'.$filename);
	
	$thedl = array('file'=>'downloads/'.$filename, 'name'=>$_POST['name'], 'desc'=>$_POST['desc']);
	$downloads = array_merge(array($thedl), $downloads);
	file_put_contents('downloads/listing.dat', serialize($downloads));
	
	Alert("Upload successful.", "Notice");
}

$c = 0;
$hl = ' highlightedPost';
foreach ($downloads as $dl)
{
	echo "
	<table class=\"outline margin width100{$hl}\">
		<tr class=\"cell{$c}\">
			<td>
				<span style=\"font-size: 120%; text-decoration: underline;\"><a href=\"{$dl['file']}\">".htmlspecialchars($dl['name'])."</a></span><br>
				File size: ".ceil(filesize($dl['file']) / 1024)." KB<br>
				MD5: ".md5_file($dl['file'])."
				".($dl['desc'] ? '<br><br>'.nl2br(htmlspecialchars($dl['desc'])) : '')."
			</td>
		</tr>
	</table>
";
	$c = !$c ? 1 : 0;
	$hl = '';
}

if ($loguser['powerlevel'] > 2)
{
?>
	<form action="" method="post" enctype="multipart/form-data">
		<table class="outline margin width100">
			<tr class="header0"><th colspan="2">Upload a file</th></tr>
			<tr class="cell0">
				<td>File</td>
				<td><input type="file" name="file" style="width: 98%;" /></td>
			</tr>
			<tr class="cell1">
				<td>Name</td>
				<td><input type="text" name="name" style="width: 98%;" /></td>
			</tr>
			<tr class="cell0">
				<td>Description</td>
				<td><textarea name="desc" rows="4" style="width: 98%;"></textarea></td>
			</tr>
			<tr class="cell1">
				<td>&nbsp;</td>
				<td><input type="submit" name="upload" value="Upload" /></td>
			</tr>
		</table>
	</form>
<?php
}
?>
</div>