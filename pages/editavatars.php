<?php

$title = __("Mood avatars");

AssertForbidden("editMoods");

if(!$loguserid)
	Kill(__("You must be logged in to edit your avatars."));

if(isset($_POST['action']))
{
	$mid = (int)$_POST['mid'];
	if($_POST['action'] == __("Rename"))
	{
		Query("update {$dbpref}moodavatars set name='".justEscape($_POST['name'])."' where mid=".$mid." and uid=".$loguserid);
		Alert(__("Avatar renamed."), __("Okay"));
	}
	else if($_POST['action'] == __("Delete"))
	{
		Query("delete from {$dbpref}moodavatars where uid=".$loguserid." and mid=".$mid);
		Query("update {$dbpref}posts set mood=0 where user=".$loguserid." and mood=".$mid);
		if(file_exists("img/avatars/".$loguserid."_".$mid))
			unlink("img/avatars/".$loguserid."_".$mid);
		Alert(__("Avatar deleted."), __("Okay"));
	}
	else if($_POST['action'] == __("Add"))
	{
		$highest = FetchResult("select mid from {$dbpref}moodavatars where uid=".$loguserid." order by mid desc limit 1");
		if($highest < 1)
			$highest = 1;
		$mid = $highest + 1;
		
		//Begin copypasta from edituser/editprofile_avatar...
		if($fname = $_FILES['picture']['name'])
		{
			$fext = strtolower(substr($fname,-4));
			$error = "";

			$exts = array(".png",".jpg",".gif");
			$dimx = 100;
			$dimy = 100;
			$dimxs = 60;
			$dimys = 60;
			$size = 30720;

			$validext = false;
			$extlist = "";
			foreach($exts as $ext)
			{
				if($fext == $ext)
				$validext = true;
				$extlist .= ($extlist ? ", " : "").$ext;
			}
			if(!$validext)
				$error.="<li>".__("Invalid file type, must be one of:")." ".$extlist."</li>";

			if(!$error)
			{
				$tmpfile = $_FILES['picture']['tmp_name'];
				$file = "img/avatars/".$loguserid."_".$mid;
				
				if($_POST['name'] == "")
					$_POST['name'] = "#".$mid;

				Query("insert into {$dbpref}moodavatars (uid, mid, name) values (".$loguserid.", ".$mid.", '".justEscape($_POST['name'])."')"); 

				if($loguser['powerlevel'])	//Are we at least a local mod?
					copy($tmpfile,$file);	//Then ignore the 100x100 rule.
				else
				{
					list($width, $height, $type) = getimagesize($tmpfile);

					if($type == 1) $img1 = imagecreatefromgif ($tmpfile);
					if($type == 2) $img1 = imagecreatefromjpeg($tmpfile);
					if($type == 3) $img1 = imagecreatefrompng ($tmpfile);

					if($width <= $dimx && $height <= $dimy && $type<=3)
						copy($tmpfile,$file);
					elseif($type <= 3)
					{
						$r = imagesx($img1) / imagesy($img1);
						if($r > 1)
						{
							$img2=imagecreatetruecolor($dimx,floor($dimy / $r));
							imagecopyresampled($img2,$img1,0,0,0,0,$dimx,$dimy/$r,imagesx($img1),imagesy($img1));
						} else
						{
							$img2=imagecreatetruecolor(floor($dimx * $r), $dimy);
							imagecopyresampled($img2,$img1,0,0,0,0,$dimx*$r,$dimy,imagesx($img1),imagesy($img1));
						}
						imagepng($img2,$file);
					} else
						$error.="<li>Invalid format.</li>";
				}
				$usepic = $file;
			} else
				Kill(__("Could not update your avatar for the following reason(s):")."<ul>".$error."</ul>");
		}
	}
}

$moodRows = "";
$rMoods = Query("select mid, name from {$dbpref}moodavatars where uid=".$loguserid." order by mid asc");
while($mood = Fetch($rMoods))
{
	$cellClass = ($cellClass+1) % 2;
	$moodRows .= format(
"
		<tr class=\"cell{0}\">
			<td style=\"width: 100px;\">
				<img src=\"img/avatars/{1}_{2}\" alt=\"\">
			</td>
			<td>
				<form method=\"post\" action=\"".actionLink("editavatars")."\">
					<input type=\"hidden\" name=\"mid\" value=\"{2}\" />
					<input type=\"text\" id=\"name{2}\" name=\"name\" style=\"width: 60%;\" value=\"{3}\" />
					<input type=\"submit\" name=\"action\" value=\"".__("Rename")."\" />
					<input type=\"submit\" name=\"action\" value=\"".__("Delete")."\" />
				</form>
			</td>
		</tr>
",	$cellClass, $loguserid, $mood['mid'], htmlspecialchars($mood['name']));
}

write(
"
	<table class=\"margin outline width50\">
		<tr class=\"header1\">
			<th colspan=\"2\">
				".__("Mood avatars")."
			</th>
		</tr>
		{0}
		<tr class=\"header1\">
			<th colspan=\"2\">
				".__("Add new")."
			</th>
		</tr>
		<tr class=\"cell2\">
			<td>
			</td>
			<td>
				<form method=\"post\" action=\"".actionLink("editavatars")."\" enctype=\"multipart/form-data\">
					<label for=\"newName\">".__("Name:")."</label>
					<input type=\"text\" id=\"newName\" name=\"name\" style=\"width: 60%;\" /><br />
					
					<label for=\"pic\">".__("Image:")."</label>
					<input type=\"file\" id=\"pic\" name=\"picture\"  style=\"width: 75%;\" />

					<input type=\"submit\" name=\"action\" value=\"".__("Add")."\" />
				</form>
			</td>
	</table>
", $moodRows);

?>
