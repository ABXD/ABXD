<?php
if(isset($_GET['error'])) die("Please use get.php");

$ajaxPage = TRUE;
include("lib/common.php");

$full = GetFullURL();
$here = substr($full, 0, strrpos($full, "/"))."/";

if(isset($_GET['id']))
	$entry = Query("select * from uploader where id = ".(int)$_GET['id']);
else if(isset($_GET['file']))
	$entry = Query("select * from uploader where filename = '".justEscape($_GET['file'])."'");
else
	die("Nothing specified.");

if(NumRows($entry))
{
	$entry = Fetch($entry);

	if($entry['private'])
		$path = "uploader/".$entry['user']."/".$entry['filename'];
	else
		$path = "uploader/".$entry['filename'];

	if(!file_exists($path))
		die("No such file.");
	
	$fsize = filesize($path);
	$parts = pathinfo($path);
	$ext = strtolower($parts["extension"]);
	$download = true;
	
	switch ($ext)
	{
		case "gif": $ctype="image/gif"; $download = false; break;
		case "apng":
		case "png": $ctype="image/png"; $download = false; break;
		case "jpeg":
		case "jpg": $ctype="image/jpg"; $download = false; break;
		case "css": $ctype="text/css"; $download = false; break;
		case "txt": $ctype="text/plain"; $download = false; break;
		case "swf": $ctype="application/x-shockwave-flash"; $download = false; break;
		case "pdf": $ctype="application/pdf"; $download = false; break;
		default: $ctype="application/force-download"; break;
	} 

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private", false);
	header("Content-Type: ".$ctype);
	if($download)
		header("Content-Disposition: attachment; filename=\"".$entry['filename']."\";");
	else
		header("Content-Disposition: filename=\"".$entry['filename']."\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".$fsize);

	readfile($path);
}
else
{
	die(__("No such file."));
}

?>
