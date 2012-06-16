<?php
//  AcmlmBoard XD - Frequently Asked Questions page
//  Access: all

$title = "FAQ";
makeThemeArrays();

$admin = UserLink(Fetch(Query("select name, id, powerlevel, sex from {$dbpref}users where id = 1")));

$sexes = array(0=>__("Male"),1=>__("Female"),2=>__("N/A"));
$powerlevels = array(0=>__("Normal user"),1=>__("Local moderator"),2=>__("Full moderator"),3=>__("Administrator"),4=>__("Root"));
$headers = "";
$colors = "";
foreach($sexes as $ss)
	$headers .= format(
"
	<th>
		{0}
	</th>
", $ss);
foreach($powerlevels as $pn => $ps)
{
	$cellClass = ($cellClass+1) % 2;
	$items = "";
	foreach($sexes as $sn => $ss)
		$items .= format(
"
	<td class=\"center\">
		<a href=\"javascript:void()\"><span class=\"nc{0}{1}\" style=\"font-weight: bold;\">
			{2}
		</span></a>
	</td>
", $sn, $pn, $ps);
	$colors .= format(
"
<tr class=\"cell{0}\">
	{1}
</tr>
", $cellClass, $items);
}
$colortable = format("
<table class=\"width50 outline\" style=\"margin-left: 25%; margin-right: auto\">
	<tr class=\"header1\">
		{0}
	</tr>
	<tr class=\"cell0\">
		<td class=\"center\" colspan=\"3\">
			<a href=\"javascript:void()\"><span class=\"nc0x\" style=\"font-weight: bold;\">
				".__("Banned user")."
			</span></a>
		</td>
	</tr>
	{1}
</table>
", $headers, $colors);

$faq = file_get_contents("lib/lang/faq_".$language.".html");

$faq = str_replace("<colortable />", $colortable, $faq);
if("" == Settings::get("registrationWord"))
	$faq = preg_replace("'<iftheword>(.*)</iftheword>'se", "", $faq);
else
	$faq = str_replace("<theword />", Settings::get("registrationWord"), $faq);

$code1 = '<link rel="stylesheet" type="text/css" href="http://.../MyLayout_$theme.css" />';
$code2 = '<link rel="stylesheet" type="text/css" href="http://.../MyLayout_'.$theme.'.css" />';
$faq = str_replace("<themeexample1 />", DoGeshi($code1), $faq);
$faq = str_replace("<themeexample2 />", DoGeshi($code2), $faq);
$faq = str_replace("<themelist />", implode(", ", $themefiles), $faq);
$faq = str_replace("<admin />", $admin, $faq);

write("
<div class=\"faq outline margin\" style=\"width: 60%; overflow: auto; margin: auto;\">
{0}
</div>", $faq);

function DoGeshi($code)
{
	$geshi = new GeSHi(trim($code), "html4strict", null);
	$geshi->set_header_type(GESHI_HEADER_NONE);
	$geshi->enable_classes();
	return "<span class=\"geshi\">".str_replace("\n", "", $geshi->parse_code())."</span>";
}

?>
