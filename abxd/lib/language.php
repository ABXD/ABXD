<?php
/* Language Pack support
 * ---------------------
 * --> Phase 1 - make all the pages language pack compatible.
 *       This starts from the 2.2.1 release onwards.
 *     Phase 2 - collect translations.
 *     Phase 3 - release 3.0 on December 23rd, with the full language pack support.
 */

define("PHASE", 2);
$language = "en_US";

if(!function_exists("__"))
{
	if(PHASE == 1)
	{
		function __($english, $flags = 0)
		{
			//No need to do anything special in Phase 1.
			//We just need to have this function so the board'll run.
			if($flags & 1)
				return str_replace(" ", "&nbsp;", htmlspecialchars($english));
			return $english;
		}
		include_once("./lib/lang/en_US.php");
	}
	else
	{
		function __($english, $flags = 0)
		{
			global $languagePack, $language;
			if($language != "en_US")
			{
				if(!isset($languagePack))
				{
					if(is_file("./lib/lang/".$language.".txt"))
					{
						importLanguagePack("./lib/lang/".$language.".txt");
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
			$pluginsDir = @opendir("plugins");
			if($pluginsDir !== FALSE)
			while(($plugin = readdir($pluginsDir)) !== FALSE)
			{
				if($plugin == "." || $plugin == "..") continue;
				if(is_dir("./plugins/".$plugin))
				{
					$foo = "./plugins/".$plugin."/".$file;
					if(file_exists($foo))
						importLanguagePack($foo);
				}
			}
		}

		include_once("./lib/lang/".$language.".php");
	}
}

?>