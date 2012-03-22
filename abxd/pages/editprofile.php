<?php
$noAutoHeader = true;

if(!$loguserid)
{
	Kill(__("You must be logged in to edit your profile."));
}

if ($loguser['powerlevel'] < 0)
{
	Kill(__("Banned users may not edit their profile."));
}

$key = hash('sha256', "{$loguserid},{$loguser['pss']},{$salt}");
if (isset($_POST['action']) && $key != $_POST['key'])
{
	Kill(__("No."));
}

if(isset($_POST['editusermode']) && $_POST['editusermode'] != 0)
	$_GET['id'] = $_POST['userid'];

if($loguser['powerlevel'] > 2)
	$userid = (isset($_GET['id'])) ? (int)$_GET['id'] : $loguserid;
else
	$userid = $loguserid;

$user = Fetch(Query("select * from users where id=".$userid));

$editUserMode = isset($_GET['id']) && $loguser['powerlevel'] > 2;
if($editUserMode && $user['powerlevel'] == 4 && $loguserid != $userid)
{
	Kill(__("Cannot edit a root user."));
}

AssertForbidden($editUserMode ? "editUser" : "editProfile");

$qRanksets = "select name from ranksets";
$rRanksets = Query($qRanksets);
$ranksets[] = __("None");
while($rankset = Fetch($rRanksets))
	$ranksets[] = $rankset['name'];

foreach($dateformats as $format)
	$datelist[$format] = ($format ? $format.' ('.cdate($format).')':'');
foreach($timeformats as $format)
	$timelist[$format] = ($format ? $format.' ('.cdate($format).')':'');

$sexes = array(__("Male"), __("Female"), __("N/A"));
$powerlevels = array(-1 => __("-1 - Banned"), __("0 - Normal user"), __("1 - Local Mod"), __("2 - Full Mod"), __("3 - Admin"));

$general = array(
	"login" => array(
		"name" => __("Login information"),
		"items" => array(
			"name" => array(
				"caption" => __("User name"),
				"type" => "text",
				"value" => $user['name'],
				"length" => 20,
				"callback" => "HandleUsername",
			),
			"password" => array(
				"caption" => __("Password"),
				"type" => "password",
				"callback" => "HandlePassword",
			),
		),
	),
	"appearance" => array(
		"name" => __("Appearance"),
		"items" => array(
			"displayname" => array(
				"caption" => __("Display name"),
				"type" => "text",
				"value" => $user['displayname'],
				"width" => "98%",
				"length" => 20,
				"hint" => "Leave this empty to use your login name.",
				"callback" => "HandleDisplayname",
			),
			"rankset" => array(
				"caption" => __("Rankset"),
				"type" => "select",
				"options" => $ranksets,
				"value" => $user['rankset'],
			),
			"title" => array(
				"caption" => __("Title"),
				"type" => "text",
				"value" => $user['title'],
				"width" => "98%",
				"length" => 255,
			),
		),
	),
	"avatar" => array(
		"name" => __("Avatar"),
		"items" => array(
			"picture" => array(
				"caption" => __("Picture"),
				"type" => "displaypic",
				"errorname" => "picture",
				"hint" => format(__("Maximum size is {0} by {0} pixels."), 100),
			),
			"minipic" => array(
				"caption" => __("Minipic"),
				"type" => "minipic",
				"errorname" => "minipic",
				"hint" => format(__("Maximum size is {0} by {0} pixels."), 16),
			),
		),
	),
	"admin" => array(
		"name" => __("Administrative stuff"),
		"items" => array(
			"powerlevel" => array(
				"caption" => __("Power level"),
				"type" => "select",
				"options" => $powerlevels,
				"value" => $user['powerlevel'],
				"callback" => "HandlePowerlevel",
			),
			"globalblock" => array(
				"caption" => __("Globally block layout"),
				"type" => "checkbox",
				"value" => $user['globalblock'],
			),
		),
	),
	"presentation" => array(
		"name" => __("Presentation"),
		"items" => array(
			"threadsperpage" => array(
				"caption" => __("Threads per page"),
				"type" => "number",
				"value" => $user['threadsperpage'],
				"min" => 50,
				"max" => 99,
			),
			"postsperpage" => array(
				"caption" => __("Posts per page"),
				"type" => "number",
				"value" => $user['postsperpage'],
				"min" => 20,
				"max" => 99,
			),
			"dateformat" => array(
				"caption" => __("Date format"),
				"type" => "datetime",
				"value" => $user['dateformat'],
				"presets" => $datelist,
				"presetname" => "presetdate",
			),
			"timeformat" => array(
				"caption" => __("Time format"),
				"type" => "datetime",
				"value" => $user['timeformat'],
				"presets" => $timelist,
				"presetname" => "presettime",
			),
			"fontsize" => array(
				"caption" => __("Font scale"),
				"type" => "number",
				"value" => $user['fontsize'],
				"min" => 20,
				"max" => 200,
			),
		),
	),
	"options" => array(
		"name" => __("Options"),
		"items" => array(
			"blocklayouts" => array(
				"caption" => __("Block all layouts"),
				"type" => "checkbox",
				"value" => $user['blocklayouts'],
			),
			"usebanners" => array(
				"caption" => __("Use nice notification banners"),
				"type" => "checkbox",
				"value" => $user['usebanners'],
			),
		),
	),
);

$personal = array(
	"personal" => array(
		"name" => __("Personal information"),
		"items" => array(
			"sex" => array(
				"caption" => __("Sex"),
				"type" => "radiogroup",
				"options" => $sexes,
				"value" => $user['sex'],
			),
			"realname" => array(
				"caption" => __("Real name"),
				"type" => "text",
				"value" => $user['realname'],
				"width" => "98%",
				"length" => 60,
			),
			"location" => array(
				"caption" => __("Location"),
				"type" => "text",
				"value" => $user['location'],
				"width" => "98%",
				"length" => 60,
			),
			"birthday" => array(
				"caption" => __("Birthday"),
				"type" => "birthday",
				"value" => $user['birthday'],
				"width" => "98%",
				"length" => 60,
				"extra" => "<span class=\"smallFonts\">".format(__("(example: {0})"), $birthdayExample)."</span>",
			),
			"bio" => array(
				"caption" => __("Bio"),
				"type" => "textarea",
				"value" => $user['bio'],
			),
			"timezone" => array(
				"caption" => __("Timezone offset"),
				"type" => "timezone",
				"value" => $user['timezone'],
			),
		),
	),
	"contact" => array(
		"name" => __("Contact information"),
		"items" => array(
			"email" => array(
				"caption" => __("Email address"),
				"type" => "text",
				"value" => $user['email'],
				"width" => "50%",
				"length" => 60,
				"extra" => "<label><input type=\"checkbox\" name=\"showemail\" ".($user['showemail'] ? "checked=\"checked\"" : "")."/>".__("Public")."</label>",
				"callback" => "HandleEmail",
			),
			"homepageurl" => array(
				"caption" => __("Homepage URL"),
				"type" => "text",
				"value" => $user['homepageurl'],
				"width" => "98%",
				"length" => 60,
			),
			"homepagename" => array(
				"caption" => __("Homepage name"),
				"type" => "text",
				"value" => $user['homepagename'],
				"width" => "98%",
				"length" => 60,
			),		
		),
	),
);

$layout = array(
	"postlayout" => array(
		"name" => __("Post layout"),
		"items" => array(
			"postheader" => array(
				"caption" => __("Header"),
				"type" => "textarea",
				"value" => $user['postheader'],
				"rows" => 16,
			),
			"signature" => array(
				"caption" => __("Footer"),
				"type" => "textarea",
				"value" => $user['signature'],
				"rows" => 16,
			),
			"signsep" => array(
				"caption" => __("Show signature separator"),
				"type" => "checkbox",
				"value" => $user['signsep'],
				"negative" => true,
			),
		),
	),
);

$bucket = "edituser"; include("lib/pluginloader.php");

if($user['posts'] < $customTitleThreshold && $user['powerlevel'] < 1 && !$editUserMode)
	unset($general['appearance']['items']['title']);
if(!$editUserMode)
{
	$general['login']['items']['name']['type'] = "label";
	unset($general['admin']);
}
if($loguser['powerlevel'] > 0)
{
	$general['avatar']['items']['picture']['hint'] = __("As a staff member, you can upload pictures of any reasonable size.");
}
if($loguser['powerlevel'] == 4 && isset($general['admin']['items']['powerlevel']))
{
	if($user['powerlevel'] == 4)
	{
		$general['admin']['items']['powerlevel']['type'] = "label";
		$general['admin']['items']['powerlevel']['value'] = __("4 - Root");
	}
	else
	{
		$general['admin']['items']['powerlevel']['options'][-2] = __("-2 - Slowbanned");
		$general['admin']['items']['powerlevel']['options'][4] = __("4 - Root");
		$general['admin']['items']['powerlevel']['options'][5] = __("5 - System");
		ksort($general['admin']['items']['powerlevel']['options']);
	}
}

// Now that we have everything set up, we can link 'em into a set of tabs.

$tabs = array(
	"general" => array(
		"name" => __("General"),
		"page" => $general,
	),
	"personal" => array(
		"name" => __("Personal"),
		"page" => $personal,
	),
	"postlayout" => array(
		"name" => __("Post layout"),
		"page" => $layout,
	),
	"theme" => array(
		"name" => __("Theme"),
		"width" => "80%",
	),
);

$first = "general";
foreach($tabs as $id => $tab)
{
	if(isset($_GET[$id]))
	{
		$first = $id;
		break;
	}
}


$failed = false;

if (isset($_POST['savedpost']))
{
	$_POST = unserialize(base64_decode($_POST['savedpost']));
	$_POST['action'] = '';
	$failed = true;
}

if (isset($_POST['theme']) && $user['id'] == $loguserid)
{
	$theme = $_POST['theme'];
	$themeFile = $theme.".css";
	if(!file_exists("css/".$themeFile))
		$themeFile = $theme.".php";
	$logopic = "img/themes/default/logo.png";
	if(file_exists("img/themes/".$theme."/logo.png"))
		$logopic = "img/themes/".$theme."/logo.png";
}





/* QUICK-E BAN
 * -----------
 */
if($_POST['action'] == __("Tempban") && $user['tempbantime'] == 0)
{
	if($user['powerlevel'] == 4)
	{
		Kill(__("Trying to ban a root user?"));
	}
	$timeStamp = strtotime($_POST['until']);
	if($timeStamp === FALSE)
	{
		Alert(__("Invalid time given. Try again."));
	}
	else
	{
		SendSystemPM($userid, format(__("You have been temporarily banned until {0} GMT. If you don't know why this happened, feel free to ask the one most likely to have done this. Calmly, if possible."), gmdate("M jS Y, G:[b][/b]i:[b][/b]s", $timeStamp)), __("You have been temporarily banned."));
	
		Query("update users set tempbanpl = ".$user['powerlevel'].", tempbantime = ".$timeStamp.", powerlevel = -1 where id = ".$userid);
		Redirect(format(__("User has been banned for {0}."), TimeUnits($timeStamp - time())), actionLink("profile", $userid), __("that user's profile"));
	}
}

/* QUERY PART
 * ----------
 */
$fallToEditor = true;
if($_POST['action'] == __("Edit profile"))
{
	$fallToEditor = false;
	$query = "UPDATE users SET ";
	$sets = array();
	$pluginSettings = unserialize($user['pluginsettings']);
	
	$retlink = "<br /><br /><form action=\"".actionLink("editprofile")."\" method=\"post\"><input type=\"hidden\" name=\"savedpost\" value=\""
		.htmlspecialchars(base64_encode(serialize($_POST)))
		."\" /><a href=\"#\" onclick=\"this.parentNode.submit();\">".__("Go back and fix that")."</a></form>";
	
	foreach($tabs as $id => $tab)
	{
		if(isset($tab['page']))
		{
			foreach($tab['page'] as $id => $section)
			{
				foreach($section['items'] as $field => $item)
				{
					if($item['callback'])
					{
						$ret = $item['callback']($field, $item);
						if($ret === true)
							continue;
						else if($ret != "")
						{
							Alert($ret.($fallToEditor ? '':$retlink), __('Error'));
							if(!$fallToEditor)
								die();
						}
					}

					switch($item['type'])
					{
						case "label":
							break;
						case "text":
						case "textarea":
							$sets[] = $field." = '".justEscape($_POST[$field])."'";
							break;
						case "password":
							if($_POST[$field])
								$sets[] = $field." = '".justEscape($_POST[$field])."'";
							break;
						case "select":
							$num = (int)$_POST[$field];
							if (array_key_exists($num, $item['options']))
								$sets[] = $field." = ".$num;
							break;
						case "number":
							$num = (int)$_POST[$field];
							if($num < 1)
								$num = $item['min'];
							elseif($num > $item['max'])
								$num = $item['max'];
							$sets[] = $field." = ".$num;
							break;
						case "datetime":
							if($_POST[$item['presetname']] != -1)
								$_POST[$field] = $_POST[$item['presetname']];
							$sets[] = $field." = '".justEscape($_POST[$field])."'";
							break;
						case "checkbox":
							$val = (int)($_POST[$field] == "on");
							if($item['negative'])
								$val = (int)($_POST[$field] != "on");
							$sets[] = $field." = ".$val;
							break;
						case "radiogroup":
							$num = (int)$_POST[$field];
							if (array_key_exists($num, $item['options']))
								$sets[] = $field." = ".$num;
							break;
						case "birthday":
							if($_POST[$field])
							{
								$val = @stringtotimestamp($_POST[$field]);
								if($val > time())
									$val = 0;
							}
							else
								$val = 0;
							$sets[] = $field." = ".$val;
							break;
						case "timezone":
							$val = ((int)$_POST[$field.'H'] * 3600) + ((int)$_POST[$field.'M'] * 60) * ((int)$_POST[$field.'H'] < 0 ? -1 : 1);
							$sets[] = $field." = ".$val;
							break;
						case "displaypic":
							if($_POST['remove'.$field])
							{
								if(substr($user[$field],0,12) == "img/avatars/")
									@unlink($user[$field]);
								$sets[] = $field." = ''";
								continue;
							}
							if($_FILES[$field]['name'] == "" || $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE)
								continue;
							$res = HandlePicture($field, 0, $item['errorname'], $user['powerlevel'] > 0 || $loguser['powerlevel'] > 0);
							if($res === true)
								$sets[] = $field." = 'img/avatars/".$userid."'";
							else
							{
								Kill($res.$retlink);
							}
							break;
						case "minipic":
							if($_POST['remove'.$field])
							{
								if(substr($user[$field],0,12) == "img/minipic/")
									@unlink($user[$field]);
								$sets[] = $field." = ''";
								continue;
							}
							if($_FILES[$field]['name'] == "" || $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE)
								continue;
							$res = HandlePicture($field, 1, $item['errorname']);
							if($res === true)
								$sets[] = $field." = 'img/minipics/".$userid.".png'";
							else
							{
								Kill($res.$retlink);
							}
							break;
					}
				}
			}
		}
	}

	//Force theme names to be alphanumeric to avoid possible directory traversal exploits ~Dirbaio
	if(preg_match("/^[a-zA-Z0-9_]+$/", $_POST['theme']))
		$sets[] = "theme = '".justEscape($_POST['theme'])."'";
	
	$sets[] = "pluginsettings = '".justEscape(serialize($pluginSettings))."'";
	if ((int)$_POST['powerlevel'] != $user['powerlevel']) $sets[] = "tempbantime = 0";

	$query .= join($sets, ", ")." WHERE id = ".$userid;
	if(!$fallToEditor)
	{
		Query($query);
		if($loguserid == $userid)
		{
			$loguser = Fetch(Query("select * from users where id=".$loguserid));
		}
		
		if(isset($_POST['powerlevel']) && $_POST['powerlevel'] != $user['powerlevel'])
			Karma();

		$his = "[b]".$user['name']."[/]'s";
		if($loguserid == $userid)
			$his = HisHer($user['sex']);
		Report("[b]".$loguser['name']."[/] edited ".$his." profile. -> [g]#HERE#?uid=".$userid, 1);

		die(header("Location: ".actionLink("profile", $userid)));
	}
	else
		$failed = true;
}

if ($fallToEditor && $failed)
{
	foreach($tabs as &$tab)
	{
		if(isset($tab['page']))
		{
			foreach($tab['page'] as &$section)
			{
				foreach($section['items'] as $field => &$item)
				{
					if (in_array($item['type'], array('label','password')))
						continue;
						
					if ($field == 'email')
						$item['extra'] = "<label><input type=\"checkbox\" name=\"showemail\" ".($_POST['showemail'] ? "checked=\"checked\"" : "")."/>".__("Public")."</label>";
						
					if ($item['type'] == 'checkbox')
						$item['value'] = ($_POST[$field] == 'on') ^ $item['negative'];
					elseif ($item['type'] == 'timezone')
						$item['value'] = ((int)$_POST[$field.'H'] * 3600) + ((int)$_POST[$field.'M'] * 60) * ((int)$_POST[$field.'H'] < 0 ? -1 : 1);
					elseif ($item['type'] == 'birthday')
						$item['value'] = @stringtotimestamp($_POST['birthday']);
					else
						$item['value'] = $_POST[$field];
				}
				unset($item);
			}
			unset($section);
		}
	}
	unset($tab);
	
	$loguser['theme'] = $_POST['theme'];
}



function HandlePicture($field, $type, $errorname, $allowOversize = false)
{
	global $userid;
	if($type == 0)
	{
		$extensions = array(".png",".jpg",".gif");
		$maxDim = 100;
		$maxSize = 300 * 1024;
	}
	else if($type == 1)
	{
		$extensions = array(".png", ".gif");
		$maxDim = 16;
		$maxSize = 100 * 1024;
	}
	$fileName = $_FILES[$field]['name'];
	$fileSize = $_FILES[$field]['size'];
	$tempFile = $_FILES[$field]['tmp_name'];
	list($width, $height, $fileType) = getimagesize($tempFile);

	$extension = strtolower(strrchr($fileName, "."));
	if(!in_array($extension, $extensions))
		return format(__("Invalid extension used for {0}. Allowed: {1}"), $errorname, join($extensions, ", "));

	if($fileSize > $maxSize && !$allowOversize)
		return format(__("File size for {0} is too high. The limit is {1} bytes, the uploaded image is {2} bytes."), $errorname, $maxSize, $fileSize)."</li>";

	switch($fileType)
	{
		case 1:
			$sourceImage = imagecreatefromgif($tempFile);
			break;
		case 2:
			$sourceImage = imagecreatefromjpeg($tempFile);
			break;
		case 3:
			$sourceImage = imagecreatefrompng($tempFile);
			break;
	}

	$oversize = ($width > $maxDim || $height > $maxDim);
	if ($type == 0)
	{
		$targetFile = "img/avatars/".$userid;
		
		if($allowOversize || !$oversize)
		{
			//Just copy it over.
			copy($tempFile, $targetFile);
		}
		else
		{
			//Resample that mother!
			$ratio = $width / $height;
			if($ratio > 1)
			{
				$targetImage = imagecreatetruecolor($maxDim, floor($maxDim / $ratio));
				imagecopyresampled($targetImage, $sourceImage, 0,0,0,0, $maxDim, $maxDim / $ratio, $width, $height);
			} else
			{
				$targetImage = imagecreatetruecolor(floor($maxDim * $ratio), $maxDim);
				imagecopyresampled($targetImage, $sourceImage, 0,0,0,0, $maxDim * $ratio, $maxDim, $width, $height);
			}
			imagepng($targetImage, $targetFile);
			imagedestroy($targetImage);
		}
	}
	elseif ($type == 1)
	{
		$targetFile = "img/minipics/".$userid.".png";
		
		if ($oversize)
		{
			//Don't allow minipics over $maxDim for anypony.
			return format(__("Dimensions of {0} must be at most {1} by {1} pixels."), $errorname, $maxDim);
		}
		else
			copy($tempFile, $targetFile);
	}
	return true;
}

// Special field-specific callbacks
function HandlePassword($field, $item)
{
	global $fallToEditor, $sets, $salt, $user, $loguser, $loguserid;
	if($_POST[$field] != "" && $_POST['repeat'.$field] != "" && $_POST['repeat'.$field] != $_POST[$field])
	{
		$fallToEditor = true;
		return __("To change your password, you must type it twice without error.");
	}
	else if($_POST[$field] != "" && $_POST['repeat'.$field] == "")
	{
		$_POST[$field] = "";
	}
	
	if($_POST[$field])
	{
		$newsalt = Shake();
		$sha = hash("sha256", $_POST[$field].$salt.$newsalt, FALSE);
		if($user['id'] == $loguser['id'])
		{
			$logdata['loguserid'] = $user['id'];
			$logdata['bull'] = hash('sha256', $user['id'].$sha.$salt.$newsalt, FALSE);
			$logdata_s = base64_encode(serialize($logdata));
			setcookie("logdata", $logdata_s, 2147483647, "", "", false, true);
		}
		$_POST[$field] = $sha;
		$sets[] = "pss = '".$newsalt."'";
	}
}

function HandleDisplayname($field, $item)
{
	global $fallToEditor, $user;
	if(!IsReallyEmpty($_POST[$field]) || $_POST[$field] == $user['name'])
	{
		// unset the display name if it's really empty or the same as the login name.
		$_POST[$field] = "";
	}
	else
	{
		$dispCheck = FetchResult("select count(*) from users where id != ".$user['id']." and (name = '".justEscape($_POST[$field])."' or displayname = '".justEscape($_POST[$field])."')", 0, 0);
		if($dispCheck)
		{
			$fallToEditor = true;
			return format(__("The display name you entered, \"{0}\", is already taken."), justEscape($_POST[$field]));
		}
		else if(strpos($_POST[$field], ";") !== false)
		{
			$user['displayname'] = str_replace(";", "", $_POST[$field]);
			$fallToEditor = true;
			return __("The display name you entered cannot contain semicolons.");
		}
	}
}

function HandleUsername($field, $item)
{
	global $user;
	if(!IsReallyEmpty($_POST[$field]))
		$_POST[$field] = $user[$field];

	$dispCheck = FetchResult("select count(*) from users where id != ".$user['id']." and (name = '".justEscape($_POST[$field])."' or displayname = '".justEscape($_POST[$field])."')", 0, 0);
	if($dispCheck)
	{
		$fallToEditor = true;
		return format(__("The login name you entered, \"{0}\", is already taken."), justEscape($_POST[$field]));
	}
	else if(strpos($_POST[$field], ";") !== false)
	{
		$user['name'] = str_replace(";", "", $_POST[$field]);
		$fallToEditor = true;
		return __("The login name you entered cannot contain semicolons.");
	}
}

function HandleEmail($field, $item)
{
	global $sets;
	$sets[] = "showemail = ".(int)($_POST['showemail'] == "on");
}

function HandlePowerlevel($field, $item)
{
	global $user, $loguserid, $userid;
	$id = $userid;
	if($user['powerlevel'] != (int)$_POST['powerlevel'] && $id != $loguserid)
	{
		$newPL = (int)$_POST['powerlevel'];
		$oldPL = $user['powerlevel'];
		
		if($newPL == 5)
			; //Do nothing -- System won't pick up the phone.
		else if($newPL == -1)
		{
			SendSystemPM($id, __("If you don't know why this happened, feel free to ask the one most likely to have done this. Calmly, if possible."), __("You have been banned."));
		}
		else if($newPL == 0)
		{
			if($oldPL == -1)
				SendSystemPM($id, __("Try not to repeat whatever you did that got you banned."), __("You have been unbanned."));
			else if($oldPL > 0)
				SendSystemPM($id, __("Try not to take it personally."), __("You have been brought down to normal."));
		}
		else if($newPL == 4)
		{
			SendSystemPM($id, __("Your profile is now untouchable to anybody but you. You can give root status to anybody else, and can access the RAW UNFILTERED POWERRR of sql.php. Do not abuse this. Your root status can only be removed through sql.php."), __("You are now a root user."));
		}
		else
		{
			if($oldPL == -1)
				; //Do nothing.
			else if($oldPL > $newPL)
				SendSystemPM($id, __("Try not to take it personally."), __("You have been demoted."));
			else if($oldPL < $newPL)
				SendSystemPM($id, __("Congratulations. Don't forget to review the rules regarding your newfound powers."), __("You have been promoted."));
		}
	}	
}






/* EDITOR PART
 * -----------
 */

//Dirbaio: Rewrote this so that it scans the themes dir.
$dir = "themes/";
$themeList = "";
$themes = array();

// Open a known directory, and proceed to read its contents
if (is_dir($dir))
{
    if ($dh = opendir($dir))
    {
        while (($file = readdir($dh)) !== false)
        {
            if(filetype($dir . $file) != "dir") continue;
            if($file == ".." || $file == ".") continue;
            $infofile = $dir.$file."/themeinfo.txt";
            
            if(file_exists($infofile))
            {
		        $themeinfo = file_get_contents($infofile);
		        $themeinfo = explode("\n", $themeinfo, 2);
		        
		        $themes[$file]["name"] = trim($themeinfo[0]);
		        $themes[$file]["author"] = trim($themeinfo[1]);
		    }
		    else
		    {
		        $themes[$file]["name"] = $file;
		        $themes[$file]["author"] = "";
		    }
        }
        closedir($dh);
    }
}


foreach($themes as $themeKey => $themeData)
{
	$themeName = $themeData["name"];
	$themeAuthor = $themeData["author"];

	$qCount = "select count(*) from users where theme='".$themeKey."'";
	$numUsers = FetchResult($qCount);
	
	$preview = "themes/".$themeKey."/preview.png";
	if(is_file($preview))
		$preview = "<img src=\"".$preview."\" alt=\"".$themeName."\" style=\"margin-bottom: 0.5em\" />";
	else
		$preview = "<img src=\"./img/nopreview.png\" alt=\"".$themeName."\" style=\"margin-bottom: 0.5em\" />";
	
	if($themeAuthor)
		$byline = "<br />".nl2br($themeAuthor);
	else
		$byline = "";
	
	if($themeKey == $user['theme'])
		$selected = " checked=\"checked\"";
	else
		$selected = "";
	
	$themeList .= format(
"
	<input style=\"display: none;\" type=\"radio\" name=\"theme\" value=\"{3}\"{4} id=\"{3}\" onchange=\"ChangeTheme(this.value);\" />
	<label style=\"display: inline-block; clear: left; padding: 0.5em; {6} width: 260px; vertical-align: top\" onmousedown=\"void();\" for=\"{3}\">
		{2} <br />
		<strong>{0}</strong>
		{1}<br />
		{5}
	</label>
",	$themeName, $byline, $preview, $themeKey, $selected, Plural($numUsers, "user"),
	($ii > 0 ? "border-top: 1px solid black;" : "") );
}

if($editUserMode && $user['powerlevel'] < 4 && $user['tempbantime'] == 0)
	write(
"
	<form action=\"".actionLink("editprofile")."\" method=\"post\">
		<table class=\"outline margin width25\" style=\"float: right;\">
			<tr class=\"header0\">
				<th colspan=\"2\">
					".__("Quick-E Ban&trade;")."
				</th>
			</tr>
			<tr>
				<td class=\"cell2\">
					<label for=\"until\">".__("Target time")."</label>
				</td>
				<td class=\"cell0\">
					<input id=\"until\" name=\"until\" type=\"text\" />
				</td>
			</tr>
			<tr>
				<td class=\"cell1\" colspan=\"2\">
					<input type=\"submit\" name=\"action\" value=\"".__("Tempban")."\" />
					<input type=\"hidden\" name=\"userid\" value=\"{0}\" />
					<input type=\"hidden\" name=\"editusermode\" value=\"1\" />
					<input type=\"hidden\" name=\"key\" value=\"{1}\" />
				</td>
			</tr>
		</table>
	</form>
", $userid, $key);

Write("<div class=\"margin width0\" id=\"tabs\">");
foreach($tabs as $id => $tab)
{
	$selected = ($first == $id) ? " selected" : "";
	Write("
	<button id=\"{2}Button\" class=\"tab{1}\" onclick=\"showEditProfilePart('{2}');\">{0}</button>
	", $tab['name'], $selected, $id);
}
Write("
</div>
<form action=\"".actionLink("editprofile")."\" method=\"post\" enctype=\"multipart/form-data\">
");

foreach($tabs as $id => $tab)
{
	if(isset($tab['page']))
		BuildPage($tab['page'], $id);
	elseif($id == "theme")
		Write("
	<table class=\"outline margin width100 eptable\" id=\"{0}\"{1}>
		<tr class=\"header0\"><th>&nbsp;</th></tr>
		<tr class=\"cell0\"><td class=\"themeselector\">{2}</td></tr>
	</table>
",	$id, ($id != $first) ? " style=\"display: none;\"" : "",
	$themeList);
}

$editUserFields = "";
if($editUserMode)
{
	$editUserFields = format(
"
		<input type=\"hidden\" name=\"editusermode\" value=\"1\" />
		<input type=\"hidden\" name=\"userid\" value=\"{0}\" />
", $userid);
}

Write(
"
	<div class=\"margin right width50\" id=\"button\">
		{2}
		<input type=\"submit\" id=\"submit\" name=\"action\" value=\"".__("Edit profile")."\" />
		<input type=\"hidden\" name=\"id\" value=\"{0}\" />
		<input type=\"hidden\" name=\"key\" value=\"{1}\" />
	</div>
</form>
", $id, $key, $editUserFields);

function BuildPage($page, $id)
{
	global $first, $loguser;
	$display = ($id != $first) ? " style=\"display: none;\"" : "";
	$cellClass = 0;
	$output = "<table class=\"outline margin width50 eptable\" id=\"".$id."\"".$display.">\n";
	foreach($page as $pageID => $section)
	{
		$output .= "<tr class=\"header0\"><th colspan=\"2\">".$section['name']."</th></tr>\n";
		foreach($section['items'] as $field => $item)
		{
			$output .= "<tr class=\"cell".$cellClass."\">\n";
			$output .= "<td>\n";
			if($item['type'] != "checkbox")
				$output .= "<label for=\"".$field."\">".$item['caption']."</label>\n";

			if($item['hint'])
				$output .= "<img src=\"img/icons/icon5.png\" title=\"".$item['hint']."\" alt=\"[?]\" />\n";
			$output .= "</td>\n";
			$output .= "<td>\n";

			if($item['before'])
				$output .= " ".$item['before'];
		
			switch($item['type'])
			{
				case "label":
					$output .= htmlspecialchars($item['value'])."\n";
					break;
				case "birthday":
					$item['type'] = "text";
					//$item['value'] = gmdate("F j, Y", $item['value']);
					$item['value'] = timestamptostring($item['value']);
				case "password":
					if(!isset($item['size']))
						$item['size'] = 13;
					if(!isset($item['length']))
						$item['length'] = 32;
					if($item['type'] == "password")
						$item['extra'] = "/ ".__("Repeat:")." <input type=\"password\" name=\"repeat".$field."\" size=\"".$item['size']."\" maxlength=\"".$item['length']."\" />";
				case "text":
					$output .= "<input id=\"".$field."\" name=\"".$field."\" type=\"".$item['type']."\" value=\"".htmlspecialchars($item['value'])."\"";
					if(isset($item['size']))
						$output .= " size=\"".$item['size']."\"";
					if(isset($item['length']))
						$output .= " maxlength=\"".$item['length']."\"";
					if(isset($item['width']))
						$output .= " style=\"width: ".$item['width'].";\"";
					if(isset($item['more']))
						$output .= " ".$item['more'];
					$output .= " />\n";
					break;
				case "textarea":
					if(!isset($item['rows']))
						$item['rows'] = 8;
					$output .= "<textarea id=\"".$field."\" name=\"".$field."\" rows=\"".$item['rows']."\" style=\"width: 98%;\">".htmlspecialchars($item['value'])."</textarea>";
					break;
				case "checkbox":
					$output .= "<label><input id=\"".$field."\" name=\"".$field."\" type=\"checkbox\"";
					if(($item['negative'] && !$item['value']) || (!$item['negative'] && $item['value']))
						$output .= " checked=\"checked\"";
					$output .= " /> ".$item['caption']."</label>\n";
					break;
				case "select":
					$disabled = isset($item['disabled']) ? $item['disabled'] : false;
					$disabled = $disabled ? "disabled=\"disabled\" " : "";
					$checks = array();
					$checks[$item['value']] = " selected=\"selected\"";
					$options = "";
					foreach($item['options'] as $key => $val)
						$options .= format("<option value=\"{0}\"{1}>{2}</option>", $key, $checks[$key], $val);
					$output .= format("<select id=\"{0}\" name=\"{0}\" size=\"1\" {2}>\n{1}\n</select>\n", $field, $options, $disabled);
					break;
				case "radiogroup":
					$checks = array();
					$checks[$item['value']] = " checked=\"checked\"";
					foreach($item['options'] as $key => $val)
						$output .= format("<label><input type=\"radio\" name=\"{1}\" value=\"{0}\"{2} />{3}</label>", $key, $field, $checks[$key], $val);
					break;
				case "displaypic":
				case "minipic":
					$output .= "<input type=\"file\" id=\"".$field."\" name=\"".$field."\" style=\"width: 98%;\" />\n";
					$output .= "<label><input type=\"checkbox\" name=\"remove".$field."\" /> ".__("Remove")."</label>\n";
					break;
				case "number":
					//$output .= "<input type=\"number\" id=\"".$field."\" name=\"".$field."\" value=\"".$item['value']."\" />";
					$output .= "<input type=\"text\" id=\"".$field."\" name=\"".$field."\" value=\"".$item['value']."\" size=\"6\" maxlength=\"4\" />";
					break;
				case "datetime":
					$output .= "<input type=\"text\" id=\"".$field."\" name=\"".$field."\" value=\"".$item['value']."\" />\n";
					$output .= __("or preset:")."\n";
					$options = "<option value=\"-1\">".__("[select]")."</option>";
					foreach($item['presets'] as $key => $val)
						$options .= format("<option value=\"{0}\">{1}</option>", $key, $val);
					$output .= format("<select id=\"{0}\" name=\"{0}\" size=\"1\" >\n{1}\n</select>\n", $item['presetname'], $options);
					break;
				case "timezone":
					$output .= "<input type=\"text\" name=\"".$field."H\" size=\"2\" maxlength=\"3\" value=\"".(int)($item['value']/3600)."\" />\n";
					$output .= ":\n";
					$output .= "<input type=\"text\" name=\"".$field."M\" size=\"2\" maxlength=\"3\" value=\"".floor(abs($item['value']/60)%60)."\" />";
					break;
			}
			if($item['extra'])
				$output .= " ".$item['extra'];

			$output .= "</td>\n"; 
			$output .= "</tr>\n";
			$cellClass = ($cellClass + 1) % 2;
		}
	}
	$output .= "</table>";
	Write($output);
}


function IsReallyEmpty($subject)
{
	$trimmed = trim(preg_replace("/&.*;/", "", $subject));
	return strlen($trimmed) != 0;
}

function Karma()
{
	global $userid;
	$votes = Query("select uid from uservotes where voter=".$userid);
	if(NumRows($votes))
		while($karmaChameleon = Fetch($votes))
			RecalculateKarma($karmaChameleon['uid']);
}

?>
