<?php
/* Language Pack support
 * ---------------------
 * --> Phase 1 - make all the pages language pack compatible.
 *       This starts from the 2.2.1 release onwards.
 *     Phase 2 - collect translations.
 *     Phase 3 - release 3.0 on December 23rd, with the full language pack support.
 */

//define("PHASE", 2);

$language = Settings::get("defaultLanguage");

include_once($libPath . "/lang/".$language.".php");
if($language != "en_US")
	include_once($libPath . "/lang/".$language."_lang.php");

function __($english, $flags = 0)
{
	global $languagePack, $language, $libPath;
	if($language != "en_US")
	{
		if(!isset($languagePack))
		{
			if(is_file($libPath . "/lang/".$language.".txt"))
			{
				importLanguagePack($libPath . "/lang/".$language.".txt");
				importPluginLanguagePacks($language.".txt");
			}
			else
				$final = $english;
		}
		if(!isset($languagePack))
			$languagePack = array();
		$eDec = html_entity_decode($english, ENT_COMPAT, "UTF-8");
		if(array_key_exists($eDec, $languagePack))
			$final = $languagePack[$eDec];
		elseif(array_key_exists($english, $languagePack))
			$final = $languagePack[$english];
		if($final == "")
			$final = $english; //$final = "[".$english."]";
	}
	else
		$final = $english;

	if($flags & 1)
		return str_replace(" ", "&nbsp;", htmlspecialchars($final));
	else if($flags & 2)
		return html_entity_decode($final);
	return $final	;
}

function importLanguagePack($file)
{
	global $languagePack;
	$f = file_get_contents($file);
	$f = explode("\n", $f);
	for($i = 0; $i < count($f); $i++)
	{
		$k = trim($f[$i]);
		if($k == "" || $k[0] == "#")
			continue;
		$i++;
		$v = trim($f[$i]);
		if($v == "")
			continue;
		$languagePack[$k] = $v;
	}
}

function importPluginLanguagePacks($file)
{
	global $installationPath;

	$pluginsDir = @opendir($installationPath . "/plugins");
	if($pluginsDir !== FALSE)
	while(($plugin = readdir($pluginsDir)) !== FALSE)
	{
		if($plugin == "." || $plugin == "..") continue;
		if(is_dir($installationPath . "/plugins/".$plugin))
		{
			$foo = $installationPath . "/plugins/".$plugin."/".$file;
			if(file_exists($foo))
				importLanguagePack($foo);
		}
	}
}

?>
