<?php
//  AcmlmBoard XD - Avatar library
//  Access: all

$title = __("Avatar library");

AssertForbidden("viewAvatars");

if(isset($_GET['rebuild'])) //Now no longers requires an actual value.
{
	$avalib = array();
	//Prepare file tree...
	$library = @opendir("img/avatars/library"); //in some PHP setups, you get an ugly "invalid argument" message here on fail.
	if($library === FALSE)
		Kill(__("Could not open avatar library."));
	//Loop through library folders...
	while(FALSE !== ($folder = readdir($library)))
	{
		if($folder[0] == ".") continue;
		if(substr($folder,-4) == ".txt") continue;
		$fol = opendir("img/avatars/library/".$folder);
		$thisFolder = array();
		//Loop through folder images...
		while(FALSE !== ($image = readdir($fol)))
		{
			if($image[0] == ".") continue;
			if(substr($image,-4) != ".png") continue;
			$image = substr($image,0,strlen($image)-4);
			$thisFolder[] = $image;
		}
		sort($thisFolder);
		//Add this branch to the file tree...
		$avalib[] = array("name"=>$folder, "content"=>$thisFolder);
	}
	if(count($avalib) == 0)
		Kill(__("There was nothing to index. Make sure there are actually images in the avatar library directory and try again."));
	file_put_contents("avalib.txt", serialize($avalib));
	Alert(__("The avatar library has been rebuilt."), __("All done"));
}

//Because it seems faster to just slurp in a single file than to do a whole folder scan |3
$avalib = @unserialize(file_get_contents("avalib.txt")); //in the same vein as opendir above...
if($avalib === FALSE)
	Kill(format(__("Could not open avatar library file. Please {0}rebuild{1} it."), "<a href=\"".actionLink("avatarlibrary", 0, "rebuild=1")."\">", "</a>"));

if(count($avalib) == 0)
	Kill(__("The avatar library is empty."));

if(isset($_GET['fid']))
{
	$fid = (int)$_GET['fid'];
	$selected[$fid] = " selected=\"selected\"";
	if($avalib[$fid]['name'] == "")
	{
		Alert(__("Unknown category."));
		unset($fid);
	}
}

if($_GET['action'] == "set")
{
	if(!$loguserid)
		Kill(__("You must be logged in to set your avatar."));
	elseif($_SERVER['REMOTE_ADDR'] != $loguser['lastip'])
		Kill(__("Haaaah, no."));
	else
		if(!isset($_GET['fid']) || !isset($_GET['img']))
			Alert(__("Both category and image must be chosen to set your avatar."), __("Error"));
		elseif(!is_numeric($_GET['fid']) || !is_numeric($_GET['img']))
			Alert(__("Category and image are supposed to be numerical!"), "WTFHAX?");
		else
			if($avalib[$fid]['content'][$_GET['img']] == "")
				Alert(__("Unknown image."), __("Error"));
			else
			{
				//Here's where the fun starts.
				$image = "img/avatars/library/".$avalib[$fid]['name']."/".$avalib[$fid]['content'][$_GET['img']].".png";

				//Copy the selected image to /avatars/$loguserid.png (assume library is 100x100)
				copy($image, "img/avatars/".$loguserid);

				//Set your profile
				Query("update users set picture='img/avatars/".$loguserid."' where id=".$loguserid." limit 1");
				
				Report("[b]".$loguser['name']."[/] switched avatars to [b]\"".$avalib[$fid]['content'][$_GET['img']]."\"[/] -> [g]#HERE#?uid=".$loguserid, 1);
				
				die(header("Location: profile.php?id".$loguserid));
			}
}

$i = 0;
$options = "";
foreach($avalib as $category)
	$options .= format("<option value=\"{0}\" {1}>{2}</option>\n", $i, $selected[$i++], $category['name']);

write(
"
	<form action=\"avatarlibrary.php\" method=\"get\" id=\"myForm\">
		<table class=\"outline margin\">
			<tr class=\"header1\">
				<th colspan=\"2\">".__("Avatar library")."</th>
			</tr>
			<tr class=\"cell0\">
				<td style=\"width: 10%;\">".__("Category")."</td>
				<td>
					<select name=\"fid\" size=\"1\" onchange=\"myForm.submit();\">
						{0}
					</select>
					<input type=\"submit\" value=\"".__("Change")."\" />
				</td>
			</tr>
		</table>
	</form>
", $options);

if(isset($fid))
{
	$i = 0;
	$set = "";
	if($loguserid)
		foreach($avalib[$fid]['content'] as $image)
		{
			$img = "<img src=\"img/avatars/library/{$avalib[$fid]['name']}/$image.png\" alt=\"$image\" title=\"$image\" />";
			$set .= actionLinkTag($img, "avatarlibrary", 0, "action=set&amp;fid=$fid&amp;img=".$i++);
		}
	else
		foreach($avalib[$fid]['content'] as $image)
		{
			$img = "<img src=\"img/avatars/library/{$avalib[$fid]['name']}/$image.png\" alt=\"$image\" title=\"$image\" />";
			$set .= $img;
		}

	write(
"
	<div class=\"outline margin faq avaLib\">
		{0}
	</div>
", $set);
}

if($loguser['picture'] == "img/avatars/".$loguserid)
{
	write(
"
	<table class=\"outline margin\">
		<tr class=\"header1\">
			<th colspan=\"2\">
				".__("Notice")."
			</th>
		</tr>
		<tr class=\"cell2\">
			<td>
				<a href=\"img/avatars/{0}\">
					<img src=\"img/avatars/{0}\" alt=\"\" style=\"border: 1px solid #888; width: 60px;\" />
				</a>
			</td>
			<td>
				".__("Please note that anything you choose here will <em>overwrite</em> your previous avatar. Keep a backup in case you want to switch back later &mdash; we can't and won't help you restore your previous avatar. However, we can help make a backup &mdash; you can save the image to the left.")."
			</td>
		</tr>
	</table>
", $loguserid);
} elseif(!$loguserid)
{
	write(
"
	<table class=\"outline margin\">
		<tr class=\"header1\">
			<th colspan=\"2\">
				".__("Notice")."
			</th>
		</tr>
		<tr class=\"cell2\">
			<td>
				".__("Because you are not logged in, you cannot select any avatars. Feel free to browse, though.")."
			</td>
		</tr>
	</table>
");
}

?>
