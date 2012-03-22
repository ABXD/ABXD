<?php

registerPlugin("Battle system's coolshades");

function BottleSystem_Goggles()
{
	global $goggles, $loguserid, $loguser, $postText;
	if(!isset($goggles))
	{
		if($loguser['powerlevel'] > 0)
			$goggles = 1;
	}
	if($goggles)
	{
		$postText = str_replace("<!--", "<span style=\"color: #66ff66;\">&lt;!--", $postText);
		$postText = str_replace("-->", "--&gt;</span>", $postText);
	}
}

register("postManglers", "BottleSystem_Goggles");
	
?>