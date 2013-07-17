<?php

$bbcode['color'] = array(
	'callback' => 'bbcodeColor',
);

function bbcodeColor($dom, $nodes, $color) {
	$span = $dom->createElement('span');
	$span->setAttribute('style', "color: $color");
	bbcodeAppend($span, $nodes);
	return $span;
}

?>
