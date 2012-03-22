<?php

$pluginSettings = array();
$plugins = array();
$pluginbuckets = array();
$pluginpages = array();

function registerSetting($settingname, $label, $check = false)
{
    // TODO: Make this function.
}

function getSetting($settingname, $useUser = false)
{
	global $pluginSettings, $user;
	if(!$useUser) //loguser
	{
		if(array_key_exists($settingname, $pluginSettings))
			return $pluginSettings[$settingname]["value"];
	}
	else if($user['pluginsettings'] != "");
	{
		$settings = unserialize($user['pluginsettings']);
		if(!is_array($settings))
			return "";
		if(array_key_exists($settingname, $settings))
			return stripslashes(urldecode($settings[$settingname]));
	}
	return "";
}

class BadPluginException extends Exception { }


function getPluginData($plugin, $load = true)
{

	global $pluginpages, $pluginbuckets, $misc;

	if(!is_dir("./plugins/".$plugin))
		throw new BadPluginException("Plugin folder is gone");
	
	//assume this to prevent plugincide
	if($misc['version'] < 220)
		$misc['version'] = 220;

	$plugindata = array();
	$plugindata['dir'] = $plugin;
	if(!file_exists("./plugins/".$plugin."/plugin.settings"))
		throw new BadPluginException(_("Plugin folder doesn't contain plugin.settings"));


	$minver = 220; //we introduced these plugins in 2.2.0 so assume this.

	$settingsFile = file_get_contents("./plugins/".$plugin."/plugin.settings");
	$settings = explode("\n", $settingsFile);
	foreach($settings as $setting)
	{
		$setting = trim($setting);
		if($setting == "") continue;
		$setting = explode("=", $setting);
		$setting[0] = trim($setting[0]);
		$setting[1] = trim($setting[1]);
		if($setting[0][0] == "#") continue;
		if($setting[0][0] == "$")
			registerSetting(substr($setting[0],1), $setting[1]);
		else
			$plugindata[$setting[0]] = $setting[1];
			
		if($setting[0] == "minversion")
			$minver = (int)$setting[1];
	}

	if($minver > $misc['version'])
		throw new BadPluginException(_("Plugin meant for a later version"));

	if($load)
	{
		$dir = "./plugins/".$plugindata['dir'];
		$pdir = @opendir($dir);
		while($f = readdir($pdir))
		{
			if(substr($f, (strlen($f) - 4), 4) == ".php")
			{
				if(substr($f, 0, 5) == "page_")
					$pluginpages[substr($f, 5, strlen($f) - 4 - 5)] = $plugindata['dir'];
				else
					$pluginbuckets[substr($f, 0, strlen($f) - 4)][] = $plugindata['dir'];
			}
		}
	}
	
	return $plugindata;
}

$rPlugins = Query("select * from enabledplugins");

while($plugin = Fetch($rPlugins))
{
	$plugin = $plugin["plugin"];
	
	try
	{
		$plugins[$plugin] = getPluginData($plugin);
	}
	catch(BadPluginException $e)
	{
		Report(Format("Disabled plugin \"{0}\" -- {1}", $plugin, $e->getMessage()));
		Query("delete from enabledplugins where plugin='".$plugin."'");
	}
}



if($loguser['pluginsettings'] != "")
{
	$settings = unserialize($loguser['pluginsettings']);
	if(!is_array($settings))
		$settings = array();
	foreach($settings as $setName => $setVal)
		if(array_key_exists($setName, $pluginSettings))
			$pluginSettings[$setName]["value"] = stripslashes(urldecode($setVal));
}

?>
