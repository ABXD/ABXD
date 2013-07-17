<?php

$bbcode["code"] = array(
	'callback' => 'bbcodeCodeHighlight',
	'pre'      => TRUE,
);
$bbcode["source"] = array(
	'callback' => 'bbcodeCodeHighlight',
	'pre'      => TRUE,
);


function bbcodeCodeHighlight($dom, $contents, $arg)
{
	// in <pre> style
	$contents = preg_replace('/^\n|\n$/', "", $contents);

	include_once("geshi.php");

	if(!$arg)
	{
		$div = $dom->createElement('div');
		$div->setAttribute('class', 'codeblock');
		$div->appendChild($dom->createTextNode($contents));
		return $div;
	}
	else
	{
		$language = $arg;
		$geshi = new GeSHi($contents, $language);
		$geshi->set_header_type(GESHI_HEADER_NONE);
		$geshi->enable_classes();
		$geshi->enable_keyword_links(false);

		$code = str_replace("\n", "", $geshi->parse_code());
		return markupToMarkup($dom, "<div class=\"codeblock geshi\">$code</div>");
	}
}
