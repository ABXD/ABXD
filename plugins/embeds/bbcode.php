<?php

$bbcode['youtube'] = array(
	'callback' => 'bbcodeYoutube',
	'pre'      => TRUE,
);
$bbcode['swf'] = array(
	'callback' => 'bbcodeFlash',
	'pre'      => TRUE,
);
$bbcode['video'] = array(
	'callback' => 'bbcodeVideo',
	'pre'      => TRUE,
);
$bbcode['tindeck'] = array(
	'callback' => 'bbcodeTindeck',
	'void'     => 'bbcodeNullIfArg',
	'pre'      => TRUE,
);

function getYoutubeIdFromUrl($url) {
	$pattern =
		'%^# Match any youtube URL
		(?:https?://)?  # Optional scheme. Either http or https
		(?:www\.)?      # Optional www subdomain
		(?:             # Group host alternatives
		youtu\.be/    # Either youtu.be,
		| youtube\.com  # or youtube.com
		(?:           # Group path alternatives
			/embed/     # Either /embed/
		| /v/         # or /v/
		| /watch\?v=  # or /watch\?v=
		)             # End path alternatives.
		)               # End host alternatives.
		([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
		\b%x'
		;
	$result = preg_match($pattern, $url, $matches);
	if (false !== $result) {
		return $matches[1];
	}
	return false;
}

function bbcodeYoutube($dom, $contents, $arg)
{
	$contents = trim($contents);

	$id = getYoutubeIdFromUrl($contents);
	if($id) $contents = $id;

	if(!preg_match("/^[\-0-9_a-zA-Z]+$/", $contents))
		return "[Invalid youtube video ID]";

	$args = "";

	if($arg == "loop")
		$args .= "&amp;loop=1";

	$frame = $dom->createElement('iframe');
	$frame->setAttribute('width', 560);
	$frame->setAttribute('height', 315);
	$frame->setAttribute('src', "//www.youtube-nocookie.com/embed/$contents");
	$frame->setAttribute('frameborder', 0);
	$frame->setAttribute('allowfullscreen', "");
	return $frame;
}

function bbcodeVideo($dom, $contents)
{
	$video = $dom->createElement('video');
	$video->setAttribute('src', $contents);
	$video->setAttribute('width', 425);
	$video->setAttribute('height', 344);
	$video->setAttribute('controls', "");
	$video->appendChild($dom->createTextNode('Video not supported — '));
	$a = $dom->createElement('a');
	$a->setAttribute('href', $contents);
	$a->appendChild($dom->createTextNode('download'));
	$video->appendChild($a);
	return $video;
}

function bbcodeTindeck($dom, $contents, $arg)
{
	if ($arg !== NULL) $contents = $arg;

	$a = $dom->createElement('a');
	$a->setAttribute('href', "http://tindeck.com/listen/$contents");

	$img = $dom->createElement('img');
	$img->setAttribute('src', "http://tindeck.com/image/$contents/stats.png");
	$img->setAttribute('alt', 'Tindeck');

	$a->appendChild($img);

	return $a;
}

function bbcodeFlash($dom, $contents, $arg)
{
	static $flashloops;
	$flashloops++;

	$width = 400;
	$height = 300;

	$args = explode(" ", $arg);
	if(count($args) == 2)
	{
		$width = $args[0];
		$height = $args[1];
	}

	// DOM is very verbose. But at least it's secure.
	$swf = $dom->createElement('div');
	$swf->setAttribute('class', 'swf');
	$swf->setAttribute('style', 'width:' . ($width + 4) . 'px');

	$swfmain = $dom->createElement('div');
	$swfmain->setAttribute('class', 'swfmain');
	$swfmain->setAttribute('id', "swf${flashloops}main");
	$swfmain->setAttribute('style', "width: ${width}px; height: ${height}px");
	$swf->appendChild($swfmain);

	$swfcontrol = $dom->createElement('div');
	$swfcontrol->setAttribute('class', 'swfcontrol');

	$play = $dom->createElement('button');
	$play->setAttribute('type', 'button');
	$play->setAttribute('style', 'height: 25px');
	$play->setAttribute('class', 'startFlash');
	$play->setAttribute('id', "swfa$flashloops");
	$play->appendChild($dom->createTextNode('►'));
	$swfcontrol->appendChild($play);

	$stop = $dom->createElement('button');
	$stop->setAttribute('type', 'button');
	$stop->setAttribute('style', 'height: 25px');
	$stop->setAttribute('class', 'stopFlash');
	$stop->setAttribute('id', "swfb$flashloops");
	$stop->appendChild($dom->createTextNode('■'));
	$swfcontrol->appendChild($stop);

	$span = $dom->createElement('span');
	$span->setAttribute('style', 'display; none');
	$span->setAttribute('id', "swf${flashloops}url");
	$span->appendChild($dom->createTextNode($contents));
	$swfcontrol->appendChild($span);

	$swf->appendChild($swfcontrol);

	return $swf;
}
?>
