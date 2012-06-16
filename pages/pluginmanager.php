<?php

$title = "Plugin Manager";

AssertForbidden("managePlugins");

if($loguser['powerlevel'] < 3)
	Kill(__("You must be an administrator to manage plugins."));
MakeCrumbs(array(__("Admin") => actionLink("admin"), __("Plugin Manager") => actionLink("pluginmanager")), "");


if($_GET["action"] == "enable")
{
	if($_GET["key"] != getUserKey())
		Kill("No.");
	
	Query("insert into {$dbpref}enabledplugins values ('".justEscape($_GET["id"])."')");
	die(header("location: ".actionLink("pluginmanager")));
}
if($_GET["action"] == "disable")
{
	if($_GET["key"] != getUserKey())
		Kill("No.");
	
	Query("delete from {$dbpref}enabledplugins where plugin='".justEscape($_GET["id"])."'");
	die(header("location: ".actionLink("pluginmanager")));
}


$cell = 0;
$pluginsDir = @opendir("plugins");

$enabledplugins = array();
$disabledplugins = array();
$pluginDatas = array();

if($pluginsDir !== FALSE)
{
	while(($plugin = readdir($pluginsDir)) !== FALSE)
	{
		if($plugin == "." || $plugin == "..") continue;
		if(is_dir("./plugins/".$plugin))
		{
			try
			{
				$plugindata = getPluginData($plugin, false);
			}
			catch(BadPluginException $e)
			{
				continue;
			}
			
			$pluginDatas[$plugin] = $plugindata;
			if(isset($plugins[$plugin]))
				$enabledplugins[$plugin] = $plugindata["name"];
			else
				$disabledplugins[$plugin] = $plugindata["name"];
		}
	}

}
	
asort($enabledplugins);
asort($disabledplugins);

print '<table class="outline margin width50">';
print '<tr class="header0"><th colspan="2">'.__("Enabled plugins").'</th></tr>';
foreach($enabledplugins as $plugin => $pluginname)
	listPlugin($plugin, $pluginDatas[$plugin]);
print '<tr class="header0"><th colspan="2">'.__("Disabled plugins").'</th></tr>';
foreach($disabledplugins as $plugin => $pluginname)
	listPlugin($plugin, $pluginDatas[$plugin]);

print '</table>';

function listPlugin($plugin, $plugindata)
{
	global $cell, $plugins;
	
	print '<tr class="cell'.$cell.'"><td>';
	print "<b>".$plugindata["name"]."</b><br>";
	print '<span style="margin-left:30px;">'.$plugindata["description"].'</span>';
	print '</td><td>';
	
	print '<ul class="pipemenu">';
	
	$text = __("Enable");
	$act = "enable";
	if(isset($plugins[$plugin]))
	{
		$text = __("Disable");
		$act = "disable";
	}
	print actionLinkTagItem($text, "pluginmanager", $plugin, "action=".$act."&key=".getUserKey());
	
	if(in_array("settingsfile", $plugindata["buckets"]))
	{
		if(isset($plugins[$plugin]))
			print actionLinkTagItem(__("Settings&hellip;"), "editsettings", "", "plugin=".$plugin);
	}
	print '</ul>';
	print '</td></tr>';
	
	$cell++;
	$cell %= 2;
}
?>
