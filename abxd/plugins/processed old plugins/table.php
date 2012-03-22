<?php
/* [table] BBCodes
 * By Kawa
 *
 * Adds the following markup:
 * [table]...[/table]
 * [table=HEADERTEXT]...[/table]
 * [tr]...[/tr]
 * [td]...[/td]
 * [td HTMLATTRIBUTES]...[/td]
 * [th]...[/th]
 *
 * All tables have initial header (using HEADERTEXT) to prevent
 * ugly poking-outings of rounded borders.
 * Bare [td] or [th] without surrounding [tr] is not parsed.
 * Neither is a bare [tr] without a [table].
 * [tr] automatically zebrastripes using .cell0 and .cell1 classes.
 * A [tr] with only [th] cells in it is given the .header1 class.
 * [td rowspan=#] works, but looks a little bad.
 *
 * Use of NoBR post option highly recommended.
 *
 * Requires ABXD 2.1.x for BBCode bucket.
 */

registerPlugin("[table] BBCode");

//from GlitchMr
function stripbr($arg)
{
	$arg=str_replace('<br />','',$arg);
	return $arg;
}


function MakeTable($match)
{
	if(count($match) == 2)
	{
		$head = "&nbsp;";
		$content = $match[1];
	}
	else
	{
		$head = $match[1];
		$content = $match[2];
	}

	//Figure out how high the header's colspan should be by taking
	//all the [tr] in this table and counting their individual [td].
	$cells = 1;
	$i = preg_match_all("'\[tr\](.*?)\[/tr\]'si", $content, $matches, PREG_PATTERN_ORDER);
	if($i)
	{
		foreach($matches[1] as $tr)
		{
			$thisCells = substr_count($tr, "[td");
			if($thisCells > $cells)
				$cells = $thisCells;
		}
	}

	$content = preg_replace_callback("'\[tr\](.*?)\[/tr\]'si", "MakeTableRow", $content);
	$content = stripbr($content);

	return format("<table class=\"outline\"><tr class=\"header0\"><th colspan=\"{1}\">{2}</th></tr>{0}</table>", $content, $cells, $head);
}

function MakeTableRow($match)
{
	global $tablerows;
	$tablerows++;

	$content = $match[1];
	
	if(substr_count($content, "[td") == 0 && substr_count($content, "[th") > 0)
	{
		$content = preg_replace("'\[th (.*?)\](.*?)\[/th\]'si", "<th \\1\">\\2</th>", $content);
		$content = preg_replace("'\[th\](.*?)\[/th\]'si", "<th>\\1</th>", $content);
		return format("<tr class=\"header1\">{1}</tr>", $tablerows % 2, $content);
	}

	$content = preg_replace("'\[td (.*?)\](.*?)\[/td\]'si", "<td \\1 style=\"border-right: 1px solid #000; border-bottom: 1px solid #000;\">\\2</td>", $content);
	$content = preg_replace("'\[td\](.*?)\[/td\]'si", "<td style=\"border-right: 1px solid #000; border-bottom: 1px solid #000;\">\\1</td>", $content);

	return format("<tr class=\"cell{0}\">{1}</tr>", $tablerows % 2, $content);
}

function Table_Code()
{
	global $text;
	$text = preg_replace_callback("'\[table=(.*?)\](.*?)\[/table\]'si", "MakeTable", $text);
	$text = preg_replace_callback("'\[table\](.*?)\[/table\]'si", "MakeTable", $text);
}

function Table_Help($tag)
{
	if($tag == "Presentation")
		print "[table]&hellip;[/table] &mdash; insert an anonymous table<br />
[table=&hellip;]&hellip;[/table] &mdash; insert a table<br />
[tr]&hellip;[/tr] and [td]&hellip;[/td] &mdash; insert a table row and cell<br />
";
}

register("bbcodes", "Table_Code");
register("postHelp", "Table_Help", 1);

?>