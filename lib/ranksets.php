<?php

function loadRanksets()
{
	global $ranksetData, $ranksetNames, $installationPath;

	if(isset($ranksetNames)) return;

	$ranksetData = array();
	$ranksetNames = array();

	$dir = $installationPath . "/ranksets/";

	if (is_dir($dir))
	{
		if ($dh = opendir($dir))
		{
		    while (($file = readdir($dh)) !== false)
		    {
		        if(filetype($dir . $file) != "dir") continue;
		        if($file == ".." || $file == ".") continue;
		        $infofile = $dir.$file."/rankset.php";

		        if(file_exists($infofile))
		        	include($infofile);
		    }
		    closedir($dh);
		}
	}
}

function getRankHtml($rankset, $rank)
{
	$text = htmlspecialchars($rank["text"]);
	if($rank["image"] == "") return $text;

	$img = htmlspecialchars(resourceLink("ranksets/".$rankset."/".$rank["image"]));
	return "<img src=\"$img\" alt=\"\" /> $text";
}

function getRank($rankset, $posts)
{
	global $ranksetData;
	if(!$rankset) return "";
	if(!isset($ranksetData)) loadRanksets();

	$thisSet = $ranksetData[$rankset];
	if(!is_array($thisSet)) return "";
	$ret = "";
	foreach($thisSet as $row)
	{
		if($row["num"] > $posts)
			break;
		$ret = $row;
	}

	if(!$ret) return "";
	return getRankHtml($rankset, $ret);
}

function getToNextRank($rankset, $posts)
{
	global $ranksetData;
	if(!$rankset) return "";
	if(!isset($ranksetData)) loadRanksets();

	$thisSet = $ranksetData[$rankset];
	if(!is_array($thisSet)) return "";
	$ret = "";
	foreach($thisSet as $row)
	{
		$ret = $row["num"] - $posts;
		if($row["num"] > $posts)
			return $ret;
	}
}
