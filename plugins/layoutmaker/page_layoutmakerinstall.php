<?php

if($loguserid == 0)
	Kill("Only members can install their layouts");

if(isset($_POST['action']) && $_POST['action'] == "Install")
{
	//Changed this to not to use the uploader since it's now a plugin. ~Dirbaio
	if(!is_dir("layoutmaker"))
		mkdir("layoutmaker/");

	$path = "layoutmaker/".$loguserid.".css";
	file_put_contents($path, $_POST['css']);
	
	$head = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$path."\" />\n".$_POST['header'];
	$foot = $_POST['footer'];
	Query("UPDATE {$dbpref}users SET postheader = '".justEscape($head)."', signature = '".justEscape($foot)."' WHERE id = ".$loguserid);
	Report("[b]".$loguser['name']."[/] edited his layout with the layout maker. -> [g]#HERE#?uid=".$userid, 1);
	
	die(header("Location: ".actionLink("profile", $loguserid)));
}

?>
