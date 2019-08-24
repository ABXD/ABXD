<?php

// BBCode parser core.
// Parses BBCode and HTML intelligently so the output is reasonably well-formed, and doesn't contain evil stuff.

/*
	$parsestatus:
	0 - HTML Entites, Smilies. nl2br
	1 - HTML Entites
	2 - nothing.
*/

function parseText($text)
{
	global $parseStatus, $postNoSmilies, $postNoBr, $postPoster;

	if($parseStatus <= 1)
	{
		$text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');
		$text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
	}

	if($parseStatus == 0)
	{
		if(!$postNoBr)
			$text = nl2br($text);

		$text = postDoReplaceText($text);
	}

	return $text;
}

$tagParseStatus = array(
	'ul' => 1,
	'ol' => 1,
	'li' => 0,

	'table' => 1,
	'td' => 0,
	'th' => 0,

	'img' => 2,
	'imgs' => 2,
	'url' => 2,
	'code' => 2,
	'source' => 2,
	'pre' => 2,
	'style' => 2,
);

$autocloseTags = array(
	'li' => array('li' => 1, 'ul' => 1, 'ol' => 1),
	'td' => array('td' => 1, 'tr' => 1, 'trh' => 1, 'table' => 1),
	'tr' => array('tr' => 1, 'trh' => 1, 'table' => 1),
	'trh' => array('tr' => 1, 'trh' => 1, 'table' => 1),
);

$heavyTags = array(
	'code' => 1,
	'source' => 1,
	'pre' => 1,
);

$singleTags = array(
	'user' => 1,
	'forum' => 1,
	'thread' => 1,
);
$singleHtmlTags = array(
	'p' => 1,
	'br' => 1,
	'img' => 1,
	'link' => 1,
);

$goodHtmlTags = array(
	'a' => 1,
	'b' => 1,
	'big' => 1,
	'br' => 1,
	'caption' => 1,
	'center' => 1,
	'code' => 1,
	'dd' => 1,
	'del' => 1,
	'div' => 1,
	'dl' => 1,
	'dt' => 1,
	'em' => 1,
	'font' => 1,
	'h1' => 1,
	'h2' => 1,
	'h3' => 1,
	'h4' => 1,
	'h5' => 1,
	'h6' => 1,
	'hr' => 1,
	'i' => 1,
	'img' => 1,
	'input' => 1,
	'kbd' => 1,
	'li' => 1,
	'nobr' => 1,
	'ol' => 1,
	'p' => 1,
	'pre' => 1,
	's' => 1,
	'small' => 1,
	'span' => 1,
	'strong' => 1,
	'style' => 1,
	'sub' => 1,
	'sup' => 1,
	'table' => 1,
	'tbody' => 1,
	'td' => 1,
	'textarea' => 1,
	'tfoot' => 1,
	'th' => 1,
	'thead' => 1,
	'tr' => 1,
	'u' => 1,
	'ul' => 1,
	'link' => 1
);

$blockHtmlTags = array(
	'div' => 1,
	'table' => 1,
	'tbody' => 1,
	'thead' => 1,
	'tfoot' => 1,
	'tr' => 1,
	'th' => 1,
	'td' => 1,
	'p' => 1,
	'style' => 1,
	'link' => 1,
	
	// mix BBCode in. Hack.
	'code' => 1,
	'source' => 1,
	'quote' => 1,
	'spoiler' => 1,
);


function tokenValidTag($tagname, $bbcode)
{
	global $bbcodeCallbacks, $goodHtmlTags;

	if($bbcode && !array_key_exists($tagname, $bbcodeCallbacks))
			return false;

	if(!$bbcode && !array_key_exists($tagname, $goodHtmlTags))
		return false;

	return true;
}

function parseToken($token)
{
	$type = 0;
	$match = array();
	$inregex = "(\w+)=?(.*)";

	if(preg_match('@^\\[/'.$inregex.'\]$@', $token, $match))
		$type = 2;
	else if(preg_match('@^\\['.$inregex.'\\]$@', $token, $match))
		$type = 1;
	else if(preg_match("@^</$inregex>$@", $token, $match))
		$type = 4;
	else if(preg_match("@^<$inregex>$@", $token, $match))
		$type = 3;

	if($type == 0)
		return array(
			'type' => 0,
			'text' => $token
		);

	$tagname = strtolower($match[1]);
	$attrs = trim($match[2]);

	if(!tokenValidTag($tagname, $type < 3))
		return array(
			'type' => 0,
			'text' => $token
		);

	return array(
		'type' => $type,
		'tag' => $tagname,
		'text' => $token,
		'attributes' => $attrs
	);
}

function parse($parentToken)
{
	global $tokens, $tokenPtr, $heavyTags, $singleTags, $singleHtmlTags, $tagParseStatus, $parseStatus, $bbcodeCallbacks, $allowTables, $autocloseTags, $bbcodeIsTableHeader, $tokenCt;
	global $blockHtmlTags;
	
	$lasttoken = $parentToken;

	$parentTag = $parentToken['tag'];

	//Single tags just can't/aren't supposed to be closed, like [user=xx]
	if($parentToken['type'] == 1)
		$singleTag = array_key_exists($parentTag, $singleTags);
	else
		$singleTag = array_key_exists($parentTag, $singleHtmlTags);

	$finished = $singleTag;

	//Heavy tags just put everything as text until close tag.
	$heavyTag = $parentToken != 0 && array_key_exists($parentTag, $heavyTags);

	//Backup parse status
	$oldParseStatus = $parseStatus;
	$oldAllowTables = $allowTables;

	//Force parse status if tag wants to.
	if($parentToken != 0)
		if(array_key_exists($parentTag, $tagParseStatus))
			$parseStatus = $tagParseStatus[$parentTag];

	if(($parentToken['type'] == 3 || $parentToken['type'] == 1) && $parentTag == 'table')
		$allowTables = true;

	if($parentTag == 'trh')
		$bbcodeIsTableHeader = true;

	$textcontents = '';

	while($tokenPtr < $tokenCt && !$finished)
	{
		$token = $tokens[$tokenPtr++];

		$printAsText = false;
		$result = '';
		switch($token['type'])
		{
			case 0: //Text
				$printAsText = true;
				break;
			case 1: //BBCode open
			case 3: //HTML open
				if($parentToken['type'] == $token['type']
						&& array_key_exists($parentTag, $autocloseTags)
						&& array_key_exists($token['tag'], $autocloseTags[$parentTag]))
				{
//					$result .= "[AUTO]";
					$finished = true;
					$tokenPtr--;
				}
				else if(!$allowTables && ($token['tag'] == 'td' || $token['tag'] == 'tr' || $token['tag'] == 'th'))
					$printAsText = true;
				else
					if(!$heavyTag)
						$result .= parse($token);
				break;

			case 2: //BBCode close
			case 4: //HTML close
				if($parentToken != 0 && $parentToken['type']+1 == $token['type'] && $token['tag'] == $parentTag)
					$finished = true;
				else if($parentToken != 0
						&& $parentToken['type']+1 == $token['type']
						&& array_key_exists($parentTag, $autocloseTags)
						&& array_key_exists($token['tag'], $autocloseTags[$parentTag]))
				{
//					$result .= "[AUTO]";
					$finished = true;
					$tokenPtr--;
				}
				else
					$printAsText = true;
				break;
		}

		if($heavyTag && !$finished)
			$printAsText = true;

		if($printAsText)
		{
			if (!$heavyTag && array_key_exists($lasttoken['tag'], $blockHtmlTags))
				$token['text'] = preg_replace("@^\r?\n@", '', $token['text']);
			$contents = '';
			$textcontents .= $token['text'];
		}
		else
		{
			if($textcontents !== "")
				$contents .= parseText($textcontents);
			$textcontents = '';
			$contents .= $result;
		}
		
		$lasttoken = $token;
	}

	if($parentTag == 'trh')
		$bbcodeIsTableHeader = false;

	if($textcontents !== "")
		$contents .= parseText($textcontents);

	//Restore saved parse status.
	$parseStatus = $oldParseStatus;
	$allowTables = $oldAllowTables;

	if($parentToken == 0)
		return $contents;

	if($parentToken['type'] == 1) //BBCode
	{
		$func = $bbcodeCallbacks[$parentTag];
		if($func)
			return $func($contents, $parentToken['attributes']);
		else
			return $contents;
	}
	else if($parentToken['type'] == 3) //HTML
	{
		if ($parentTag == 'nobr')
			return preg_replace('@<br\s*/?>@i', '', $contents); // hack
		elseif($singleTag)
			return '<'.$parentTag.' '.$parentToken['attributes'].'>';
		else
			return '<'.$parentTag.' '.$parentToken['attributes'].'>'.$contents.'</'.$parentTag.'>';
	}
	else return 'WTF?';
}


function parseBBCode($text)
{
	global $tokens, $tokenPtr, $parseStatus, $tokenCt;

	$tokens = preg_split('/(\[(?:\w+(?:=".*?"|=[^]]*)?|\/\w+)\]|<[^\[\]<>]+>)/S', $text, 0, PREG_SPLIT_DELIM_CAPTURE);
	$tokens = array_map('parseToken', $tokens);

	$parseStatus = 0;
	$tokenCt = count($tokens);
	$tokenPtr = 0;

	$res = parse(0);
	return $res;
}

