<?php
//Layoutmaker.php AJAX backend

$ajaxPage = true;

$loguser['blocklayouts'] = 0; //force layouts to show up
$base = $_POST['base'];

if(!isset($base) || strpos($base, ".") !== FALSE)
	Kill("Invalid base layout.");

$basefile = $installationPath . "/plugins/layoutmaker/bases/".$base.".php";
if(is_file($basefile))
	include($basefile);
else
	Kill("Invalid base layout.");

print "<style type=\"text/css\">".ApplyParameters($cssTemplate)."</style>";

$previewPost['num'] = "_";
$previewPost['id'] = "_";
foreach($loguser as $key => $value)
	$previewPost["u_".$key] = $value;

$previewPost['u_postheader'] = trim(ApplyParameters($markupTemplateA));
$previewPost['text'] = Settings::get("profilePreviewText");
$previewPost['u_signature'] = trim(ApplyParameters($markupTemplateB));

$is_unoriginal = true;
foreach ($parameters as $key=>$param)
{
	if (isset($param['default']) && $_POST[$key] != $param['default'])
		$is_unoriginal = false;
}

if ($is_unoriginal)
	$previewPost['text'] = '<span style="font-size:150%;font-weight:bold;">Please be original, don\'t just use the default values!</span>';

MakePost($previewPost, POST_SAMPLE);

write("
<form action=\"".actionLink("layoutmakerinstall")."\" method=\"post\">
<table class=\"outline margin width100\">
	<tr class=\"header1\">
		<th colspan=\"2\">
			Code
		</th>
	</tr>
	<tr>
		<td class=\"cell2\">
			CSS stylesheet
		</td>
		<td class=\"cell0\">
			<textarea name=\"css\" class=\"output\">{0}</textarea>
		</td>
	</tr>
	<tr>
		<td class=\"cell2\">
			Post header
		</td>
		<td class=\"cell1\">
			<textarea name=\"header\" class=\"output\">{1}</textarea>
		</td>
	</tr>
	<tr>
		<td class=\"cell2\">
			Footer
		</td>
		<td class=\"cell1\">
			<textarea name=\"footer\" class=\"output\">{2}</textarea>
		</td>
	</tr>
",	htmlentities(ApplyParameters($cssTemplate)),
	htmlentities(ApplyParameters($markupTemplateA)),
	htmlentities(ApplyParameters($markupTemplateB))
);
if($loguserid)
	write("
	<tr>
		<td class=\"cell2\" colspan=\"2\">
			<input type=\"submit\" onclick=\"return confirm('This will completely overwrite your old layout. Are you sure?');\" name=\"action\" value=\"Install\" />
		</td>
	</tr>
");

write("
</table>
</form>
");

function hex2rgb($color)
{
	if ($color[0] == '#')
	$color = substr($color, 1);

	if (strlen($color) == 6)
		list($r, $g, $b) = array($color[0].$color[1],
					$color[2].$color[3],
					$color[4].$color[5]);
	elseif (strlen($color) == 3)
		list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
	else
		return false;

	$r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

	return "$r, $g, $b";
}

function ApplyParameters($input)
{
	global $parameters;

	$textfx = array
	(
		"",
		"text-shadow: 1px 1px black;",
		"text-shadow: -1px -1px black, 0px -1px black, 1px -1px black, 1px 0px black, 1px 1px black, 0px 1px black, -1px 1px black, -1px 0px black;",
	);
	$metafonts = array
	(
		"serif", "sans-serif", "fantasy", "monospace"
	);

	$lines = explode("\n", str_replace("\r", "", $input));
	$output = "";
	foreach($lines as $line)
	{
		foreach($parameters as $id => $settings)
		{
			$value = $_POST[$id];
			if($settings['type'] == "percentage")
				$value = $_POST[$id] / 100;
			else if($settings['type'] == "textfx")
				$value = $textfx[$value];
			$line = str_replace("[".$id."]", $value, $line);
			if($settings['type'] == "color")
			$line = str_replace("[".$id."_RGB]", hex2rgb($value), $line);
			if($settings['type'] == "font")
			{
				if(in_array($value, $metafonts))
					$line = str_replace("\"", "", $line);
			}
		}
		if(strpos($line, ": ;") === FALSE
		&& strpos($line, ":  !important;") === FALSE
		&& strpos($line, ": px;") === FALSE
		&& strpos($line, ": em;") === FALSE
		&& strpos($line, ": 0px;") === FALSE
		&& strpos($line, ": 0em;") === FALSE
		&& !(strpos($line, "rgba(") !== FALSE && strpos($line, ", 0);") !== FALSE)
		)
		{
			$output .= $line ."\n";
		}
	}

	return $output;
}

?>
