<?php
//  AcmlmBoard XD - Administration hub page
//  Access: administrators


AssertForbidden("viewAdminRoom");

if($loguser['powerlevel'] < 3)
	Kill(__("You're not an administrator. There is nothing for you here."));

$title = __("Administration");

$crumbs = new PipeMenu();
$crumbs->add(new PipeMenuLinkEntry(__("Admin"), "admin"));
makeBreadcrumbs($crumbs);

$cell2 = 1;
function cell2($content)
{
	global $cell2;
	$cell2 = ($cell2 == 1 ? 0 : 1);
	Write("
		<tr class=\"cell{0}\">
			<td>
				{1}
			</td>
		</tr>
	", $cell2, $content);
}

Write("
	<table class=\"outline margin width50 floatright\">
		<tr class=\"header1\">
			<th colspan=\"2\">
				".__("Information")."
			</th>
		</tr>
");
cell2(Format("

				".__("Last viewcount milestone")."
			</td>
			<td style=\"width: 60%;\">
				{0}
			",	$misc['milestone']));

$bucket = "adminright"; include($libPath . "/pluginloader.php");

write(
"
	</table>
");

$cell2 = 1;
Write("
	<table class=\"outline margin width25\">
		<tr class=\"header1\">
			<th>
				".__("Admin tools")."
			</th>
		</tr>
");
cell2(actionLinkTag(__("Recalculate statistics"), "recalc"));
cell2(actionLinkTag(__("Last Known Browsers"), "lastknownbrowsers"));
cell2(actionLinkTag(__("Manage IP bans"), "ipbans"));
cell2(actionLinkTag(__("Manage forum list"), "editfora"));
cell2(actionLinkTag(__("Manage plugins"), "pluginmanager"));
cell2(actionLinkTag(__("Edit settings"), "editsettings"));
cell2(actionLinkTag(__("Edit smilies"), "editsmilies"));
cell2(actionLinkTag(__("Optimize tables"), "optimize"));
cell2(actionLinkTag(__("View log"), "log"));
cell2(actionLinkTag(__("Update table structure"), "updateschema"));

$bucket = "adminleft"; include($libPath . "/pluginloader.php");

write(
"
	</table>
");
?>
