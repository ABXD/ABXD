<?php

/*
	I really don't like the idea of settings.php. Self-modifying code is bad, and
	it's like that just for historical reasons.

	We should now do one settings system that stores everything in the MySQL DB, and works
	for all possible use cases.

	This is how the new Settings System should work:

	We have to store 4 types of settings:
	- Board global settings
	- Plugin global settings
	- Board per-user settings
	- Plugin per-user settings

	Global settings (both types) are stored in the "settings" table.
	"plugin" field is the plugin name, or "main" if it's a board setting.
	"name" field is the name of the setting.
	"value" field is the value of the setting.

	TODO: specify how are per-user settings stored and implement them.

	======

	Types of settings:

	- boolean		(0 or 1, uses a checkbox)
	- integer
	- text			Creates a text field
	- textbox		Creates a text box with no controls
	- textbbcode	Creates a text box with BBCode post help
	- texthtml		Creates a text box with HTML post help (if it's ever implemented)

	- theme			Creates a theme selection drop-down. Stores the theme name.
	- forum			Creates a forum selection drop-down. Stores the FID

	Additionally settings can have a default value, a friendly name, and a help text.
	Also there should be some validation of the setting values.
	Specially for per-user settings, which can be modified at will by users.

*/

class Settings
{
	public static $settingsArray;
	//Loads ALL the settings.

	public static function load()
	{
		self::$settingsArray = array();
		$rSettings = Query("select * from {settings}");

		while($setting = Fetch($rSettings))
		{
			self::$settingsArray[$setting["plugin"]][$setting["name"]] = $setting["value"];
		}
	}

	public static function getSettingsFile($pluginname)
	{
		global $plugins, $libPath, $installationPath;

		$settings = array();

		//Get the setting list.
		if($pluginname == "main")
			include($libPath . "/settingsfile.php");
		else
		{
			@include($installationPath . "/plugins/".$plugins[$pluginname]['dir']."/settingsfile.php");
		}
		return $settings;
	}


	public static function checkPlugin($pluginname)
	{
		if(!isset(self::$settingsArray[$pluginname]))
			self::$settingsArray[$pluginname] = array();

		$changed = false;

		$settings = self::getSettingsFile($pluginname);
		foreach($settings as $name => $data)
		{
			$type = $data["type"];
			$default = $data["default"];

			if(!isset(self::$settingsArray[$pluginname][$name]) || !self::validate(self::$settingsArray[$pluginname][$name], $type, (isset($data["options"]) ? $data["options"] : array())))
			{
				if (isset($data["defaultfile"]))
					self::$settingsArray[$pluginname][$name] = file_get_contents($data["defaultfile"]);
				else
					self::$settingsArray[$pluginname][$name] = $default;

				self::saveSetting($pluginname, $name);
				$changed = true;
			}
		}

	}

	public static function save($pluginname)
	{
		foreach(self::$settingsArray[$pluginname] as $name=>$value)
			self::saveSetting($pluginname, $name);
	}

	public static function saveSetting($pluginname, $settingname)
	{
		Query("insert into {settings} (plugin, name, value) values ({0}, {1}, {2}) ".
			"on duplicate key update value=VALUES(value)",
			$pluginname, $settingname, self::$settingsArray[$pluginname][$settingname]);
	}


	public static function validate($value, $type, $options = array())
	{
		if($type == "boolean" || $type == "integer" || $type == "float" || $type == "user" || $type == "forum" || $type == "layout" || $type == "theme" || $type == "language")
			if(trim($value) == "")
				return false;

		if($type == "boolean")
			if($value != 0 && $value != 1)
				return false;

		if($type == "integer" || $type == "user" || $type == "forum")
			if(!ctype_digit($value) || $value === "")
				return false;

		if($type == "float")
			if (!is_numeric($value))
				return false;

		if($type == "options")
			if (!isset($options[$value]))
				return false;

		//These should be alphanumeric with underscores.
		if($type == "layout" || $type == "theme" || $type == "language")
			if(!preg_match("/^[a-zA-Z0-9_]+$/", $value))
				return false;

		return true;
	}

	public static function get($name)
	{
		return self::$settingsArray["main"][$name];
	}

	public static function getForPlugin($plugin, $name)
	{
		return self::$settingsArray[$plugin][$name];
	}

	public static function pluginGet($name)
	{
		global $plugin;
		return self::$settingsArray[$plugin][$name];
	}
}
?>
