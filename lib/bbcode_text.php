<?php

// Misc things that get replaced in text.

function loadSmilies()
{
	global $smilies, $smiliesReplaceOrig, $smiliesReplaceNew;

	$rSmilies = Query("select * from {smilies} order by length(code) desc");
	$smilies = array();

	while($smiley = Fetch($rSmilies))
		$smilies[] = $smiley;

	$smiliesReplaceOrig = $smiliesReplaceNew = array();
	for ($i = 0; $i < count($smilies); $i++)
	{
		$smiliesReplaceOrig[] = "/(?<!\w)".preg_quote(htmlspecialchars($smilies[$i]['code']), "/")."(?!\w)/";
		$smiliesReplaceNew[] = "<img class=\"smiley\" alt=\"\" src=\"".resourceLink("img/smilies/".$smilies[$i]['image'])."\" />";
	}
}


function loadSmiliesOrdered()
{
	global $smiliesOrdered;

	$rSmilies = Query("select * from {smilies}");
	$smilies = array();

	while($smiley = Fetch($rSmilies))
		$smiliesOrdered[] = $smiley;
}

/*
function makeUserAtLink($matches)
{
	global $members;
	$username = $matches[1];
	foreach($members as $id => $data)
	{
		if($data['name'] == $username)
		{
			return UserLink($members[$data['id']]);
		}
	}
	//Didn't find it in the cache.
	$rUser = Query("select u.(_userfields) from {users} u where name={0} or displayname={0}", $username);
	if(NumRows($rUser))
	{
		$hit = getDataPrefix(Fetch($rUser), "u_");
		$members[$hit['id']] = $hit;
		return UserLink($hit);
	}
	else
		return $username; //Return the actual name attempted.
}*/

//Main post text replacing.
function postDoReplaceText($s)
{
	global $postNoSmilies, $postPoster, $smiliesReplaceOrig, $smiliesReplaceNew;

	//These two are useless because they don't provide new functionality.
//	$s = preg_replace_callback("'@\"([\w ]+)\"'si", "MakeUserAtLink", $s);
//	$s = preg_replace("'>>([0-9]+)'si",">>".actionLinkTag("\\1", "thread", "", "pid=\\1#\\1"), $s);

	if($postPoster)
		$s = preg_replace("'/me '","<b>* ".$postPoster."</b> ", $s);

	//Smilies
	if(!$postNoSmilies)
	{
		if(!isset($smiliesReplaceOrig))
			LoadSmilies();
		$s = preg_replace($smiliesReplaceOrig, $smiliesReplaceNew, $s);
	}
	
	//Automatic links. Messy regex FTW
	$s = preg_replace_callback('((?:(?:view-source:)?(?:[Hh]t|[Ff])tps?://(?:(?:[^:&@/]*:[^:@/]*)@)?|\bwww\.)[a-zA-Z0-9\-]+(?:\.[a-zA-Z0-9\-]+)*(?::[0-9]+)?(?:/(?:->(?=\S)|&amp;|[\w\-/%?=+#~:\'@*^$!]|[.,;\'|](?=\S)|(?:(\()|(\[)|\{)(?:->(?=\S)|[\w\-/%&?=+;#~:\'@*^$!.,;]|(?:(\()|(\[)|\{)(?:->(?=\S)|l[\w\-/%&?=+;#~:\'@*^$!.,;])*(?(3)\)|(?(4)\]|\})))*(?(1)\)|(?(2)\]|\})))*)?)', 'bbcodeURLAuto', $s);

	//Plugin bucket for allowing plugins to add replacements.
	$bucket = "postMangler"; include("./lib/pluginloader.php");

	return $s;
}

