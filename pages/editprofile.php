<?php

//Check Stuff
if(!$loguserid)
	Kill(__("You must be logged in to edit your profile."));

if ($loguser['powerlevel'] < 0)
	Kill(__("Banned users may not edit their profile."));

if (isset($_POST['action']) && $loguser['token'] != $_POST['key'])
	Kill(__("No."));

if($loguser['powerlevel'] > 2)
	$userid = (isset($_GET['id'])) ? (int)$_GET['id'] : $loguserid;
else
	$userid = $loguserid;

$user = Fetch(Query("select * from {users} where id={0}", $userid));
if(!$user)
	Kill(__("User not found"));

$editUserMode = $userid != $loguserid;

if($user['powerlevel'] == 4 && $loguser['powerlevel'] != 4 && $loguserid != $userid)
	Kill(__("Cannot edit a root user."));

AssertForbidden($editUserMode ? "editUser" : "editProfile");

//Breadcrumbs
$uname = $user["name"];
if($user["displayname"])
	$uname = $user["displayname"];
makeCrumbs(array(__("Member list")=>actionLink("memberlist"), htmlspecialchars($uname) => actionLink("profile", $userid, "", $user["name"]), __("Edit profile") => ""), "");

loadRanksets();
$ranksets = $ranksetNames;
$ranksets = array_reverse($ranksets);
$ranksets[""] = __("None");
$ranksets = array_reverse($ranksets);

foreach($dateformats as $format)
	$datelist[$format] = ($format ? $format.' ('.cdate($format).')':'');
foreach($timeformats as $format)
	$timelist[$format] = ($format ? $format.' ('.cdate($format).')':'');

$sexes = array(__("Male"), __("Female"), __("N/A"));
$powerlevels = array(-1 => __("-1 - Banned"), __("0 - Normal user"), __("1 - Local Mod"), __("2 - Full Mod"), __("3 - Admin"));
$months = array(
	__("January"),
	__("February"),
	__("March"),
	__("April"),
	__("May"),
	__("June"),
	__("July"),
	__("August"),
	__("September"),
	__("October"),
	__("November"),
	__("December"),
);

//Editprofile.php: Welcome to the Hell of Nested Arrays!
//Now not so much.

$fields = array(
	"displayname" => array(
		"caption" => __("Display name"),
		"type" => "text",
		"width" => "98%",
		"length" => 32,
		"hint" => __("Leave this empty to use your login name."),
		"callback" => "HandleDisplayname",
	),
	"rankset" => array(
		"caption" => __("Rankset"),
		"type" => "select",
		"options" => $ranksets,
	),
	"title" => array(
		"caption" => __("Title"),
		"type" => "text",
		"width" => "98%",
		"length" => 255,
	),
	"picture" => array(
		"caption" => __("Avatar and Minipic"),
		"type" => "label",
		"html" => true,
		"value" => actionLinkTag(__("Click here to edit..."), "editavatars", $editUserMode?$userid:""),
	),
/*
	"picture" => array(
		"caption" => __("Avatar"),
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
	*/
	"threadsperpage" => array(
		"caption" => __("Threads per page"),
		"type" => "number",
		"min" => 50,
		"max" => 99,
	),
	"postsperpage" => array(
		"caption" => __("Posts per page"),
		"type" => "number",
		"min" => 20,
		"max" => 99,
	),
	"dateformat" => array(
		"caption" => __("Date format"),
		"type" => "text",
		"presets" => $datelist,
	),
	"timeformat" => array(
		"caption" => __("Time format"),
		"type" => "text",
		"presets" => $timelist,
	),
	"fontsize" => array(
		"caption" => __("Font scale"),
		"type" => "number",
		"min" => 20,
		"max" => 200,
	),
	"blocklayouts" => array(
		"caption" => __("Block all layouts"),
		"type" => "checkbox",
	),
	"usebanners" => array(
		"caption" => __("Use nice notification banners"),
		"type" => "checkbox",
	),
	"sex" => array(
		"caption" => __("Sex"),
		"type" => "radiogroup",
		"options" => $sexes,
	),
	"realname" => array(
		"caption" => __("Real name"),
		"type" => "text",
		"width" => "98%",
		"length" => 60,
	),
	"location" => array(
		"caption" => __("Location"),
		"type" => "text",
		"width" => "98%",
		"length" => 60,
	),
	"birthdayday" => array(
		"caption" => __("Birthday"),
		"type" => "number",
		"min" => 0,
		"max" => 31,
	),
	"birthdaymonth" => array(
		"caption" => __("Birthday month"),
		"type" => "select",
		"options" => $months
	),
	"birthdayyear" => array(
		"caption" => __("Birthday year"),
		"type" => "number",
		"min" => 1800,
		"max" => 2100,
	),
	"bio" => array(
		"caption" => __("Bio"),
		"type" => "textarea",
	),
	"timezone" => array(
		"caption" => __("Timezone offset"),
		"type" => "timezone",
	),
	"homepageurl" => array(
		"caption" => __("Homepage URL"),
		"type" => "text",
		"width" => "98%",
		"length" => 60,
	),
	"homepagename" => array(
		"caption" => __("Homepage name"),
		"type" => "text",
		"width" => "98%",
		"length" => 60,
	),
	"name" => array(
		"caption" => __("User name"),
		"type" => "text",
		"length" => 20,
		"callback" => "HandleUsername",
		"protected" => true,
	),
	"password" => array(
		"caption" => __("Password"),
		"type" => "password",
		"callback" => "HandlePassword",
		"protected" => true,
	),
	"email" => array(
		"caption" => __("Email address"),
		"type" => "text",
		"width" => "50%",
		"length" => 60,
		"protected" => true,
	),
	"showemail" => array(
		"caption" => __("Make email public"),
		"type" => "checkbox",
	),
	"powerlevel" => array(
		"caption" => __("Power level"),
		"type" => "select",
		"options" => $powerlevels,
		"callback" => "HandlePowerlevel",
		"protected" => true,
	),
	"globalblock" => array(
		"caption" => __("Globally block layout"),
		"type" => "checkbox",
	),
	"postheader" => array(
		"caption" => __("Header"),
		"type" => "textarea",
		"rows" => 16,
	),
	"signature" => array(
		"caption" => __("Footer"),
		"type" => "textarea",
		"rows" => 16,
	),
	"signsep" => array(
		"caption" => __("Show signature separator"),
		"type" => "checkbox",
		"negative" => true,
	),
);

$general = array(
	"appearance" => array(
		"name" => __("Appearance"),
		"items" => array(
			"displayname",
			"rankset",
			"title",
		),
	),
	"avatar" => array(
		"name" => __("Avatar"),
		"items" => array(
			"picture",
			"minipic",
		),
	),
	"presentation" => array(
		"name" => __("Presentation"),
		"items" => array(
			"threadsperpage",
			"postsperpage",
			"dateformat",
			"timeformat",
			"fontsize",
		),
	),
	"options" => array(
		"name" => __("Options"),
		"items" => array(
			"blocklayouts",
			"usebanners",
		),
	),
);

$personal = array(
	"personal" => array(
		"name" => __("Personal information"),
		"items" => array(
			"sex",
			"realname",
			"location",
			"bio",
			"timezone",
		),
	),
	"birthday" => array(
		"name" => __("Birthday"),
		"items" => array(
			"birthdayday",
			"birthdaymonth",
			"birthdayyear",
		),
	),
	"contact" => array(
		"name" => __("Contact information"),
		"items" => array(
			"homepageurl",
			"homepagename",
		),
	),
);

$account = array(
	"login" => array(
		"name" => __("Login information"),
		"items" => array(
			"name",
			"password",
		),
	),
	"email" => array(
		"name" => __("Email information"),
		"items" => array(
			"email",
			"showemail",
		),
	),
	"admin" => array(
		"name" => __("Administrative stuff"),
		"items" => array(
			"powerlevel",
			"globalblock",
		),
	),
);

$layout = array(
	"postlayout" => array(
		"name" => __("Post layout"),
		"items" => array(
			"postheader",
			"signature",
			"signsep",
		),
	),
);

//Allow plugins to add their own fields
$bucket = "edituser"; include("lib/pluginloader.php");

//Make some more checks.
if($user['posts'] < Settings::get("customTitleThreshold") && $user['powerlevel'] < 1 && !$editUserMode)
	unset($fields['title']);

if(!$editUserMode)
{
	$fields['name']['type'] = "label";
	$fields['name']['value'] = $user["name"];
	unset($fields['powerlevel']);
	unset($fields['globalblock']);
	unset($account['admin']);
}

//if($loguser['powerlevel'] > 0)
//	$fields['picture']['hint'] = __("As a staff member, you can upload pictures of any reasonable size.");

if($loguser['powerlevel'] == 4 && isset($fields['powerlevel']))
{
	if($user['powerlevel'] == 4)
	{
		$fields['powerlevel']['type'] = "label";
		$fields['powerlevel']['value'] = __("4 - Root");
	}
	else
	{
		$fields['powerlevel']['options'][-2] = __("-2 - Slowbanned");
		$fields['powerlevel']['options'][4] = __("4 - Root");
		$fields['powerlevel']['options'][5] = __("5 - System");
		ksort($fields['powerlevel']['options']);
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
	"account" => array(
		"name" => __("Account settings"),
		"page" => $account,
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

/*
if (isset($_POST['theme']) && $user['id'] == $loguserid)
{
	$theme = $_POST['theme'];
	$themeFile = $theme.".css";
	if(!file_exists("css/".$themeFile))
		$themeFile = $theme.".php";
	$logopic = "img/themes/default/logo.png";
	if(file_exists("img/themes/".$theme."/logo.png"))
		$logopic = "img/themes/".$theme."/logo.png";
}*/

/* QUICK-E BAN
 * -----------
 */
$_POST['action'] = (isset($_POST['action']) ? $_POST['action'] : "");
if($_POST['action'] == __("Tempban") && $user['tempbantime'] == 0)
{
	if ($loguser['powerlevel'] < 3) Kill(__('No.'));

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

		Query("update {users} set tempbanpl = {0}, tempbantime = {1}, powerlevel = -1 where id = {2}", $user['powerlevel'], $timeStamp, $userid);
		redirect(format(__("User has been banned for {0}."), TimeUnits($timeStamp - time())), actionLink("profile", $userid), __("that user's profile"));
	}
}

//So. Every field has originalvalue which is taken from $user
//and also value which is taken from $_POST if set, or from $user
foreach($fields as $name => &$field)
{
	if($field["type"] == 'label') continue;
	if($field["type"] == 'password')
		$field["originalvalue"] = "";
	else
		$field["originalvalue"] = $user[$name];
	
	$field["value"] = $field["originalvalue"];
	if($_POST["action"])
	{
		$postval = $_POST[$name];

		switch($field['type'])
		{
			case "label":
				break;
			case "text":
			case "textarea":
			case "password":
				$field["value"] = $postval;
				break;
			case "select":
			case "radiogroup":
				if (array_key_exists($postval, $field['options']))
					$field["value"] = $postval;
				break;
			case "number":
				$num = (int)$postval;
				if($num < $field['min'])
					$num = $field['min'];
				elseif($num > $field['max'])
					$num = $field['max'];
				$field["value"] = $num;
				break;
			case "checkbox":
				$field["value"] = ((int)($postval == "on")) ^ $field['negative'];
				break;
			case "timezone":
				$field['value'] = ((int)$_POST[$field.'H'] * 3600) + ((int)$_POST[$field.'M'] * 60) * ((int)$_POST[$field.'H'] < 0 ? -1 : 1);
				break;
		}
	}
}

/* QUERY PART
 * ----------
 */

$failed = false;

$passwordEntered = false;
$passwordFailed = false;

if(isset($_POST["currpassword"]))
{
	$sha = doHash($_POST["currpassword"].$salt.$loguser['pss']);
	if($loguser['password'] == $sha)
		$passwordEntered = true;
	else
		$passwordFailed = true;
}

$needsPassword = false;

if($_POST['action'] == __("Edit profile"))
{
	$query = "UPDATE {$dbpref}users SET ";
	$sets = array();

	foreach($fields as $name => &$field)
	{
		//IMPORTANT NOTICE
		//Callbacks are CONFUSING! Return values mean:
		// True: the callback did its thing, no need to set anything
		// False: the callback checked everything is OK, now we have to do the normal set
		// A string: an error occured!
		if($field["type"] == "label") continue;
		
		$normalSet = true;
		if($field['callback'])
		{
			$ret = $field['callback']($name, $field);
			if($ret === true)
				$normalSet = false;
			else if($ret != "")
			{
				$failed = true;
				$failedField = $name;
				$field["fail"] = true;
				$field["error"] = $ret;
			}
		}

		if($normalSet)
			$sets[] = $name." = '".SqlEscape($field["value"])."'";
			
		if($field["value"] !== $field["originalvalue"] && $field["protected"])
			$needsPassword = true;

		//TODO FIX
		/*
		switch($field['type'])
		{
			//TODO: These two are copypasta, fixit
			case "displaypic":
				if($_POST['remove'.$name])
				{
					@unlink($dataDir."avatars/$userid");
					$sets[] = $name." = ''";
					continue;
				}
				if($_FILES[$name]['name'] == "" || $_FILES[$name]['error'] == UPLOAD_ERR_NO_FILE)
					continue;
				$res = HandlePicture($name, 0, $field['errorname'], $user['powerlevel'] > 0 || $loguser['powerlevel'] > 0);
				if($res === true)
					$sets[] = $name." = '#INTERNAL#'";
				else
				{
					Alert($res);
					$failed = true;
					$field["fail"] = true;
				}
				break;
			case "minipic":
				if($_POST['remove'.$name])
				{
					@unlink($dataDir."minipic/$userid");
					$sets[] = $name." = ''";
					continue;
				}
				if($_FILES[$name]['name'] == "" || $_FILES[$name]['error'] == UPLOAD_ERR_NO_FILE)
					continue;
				$res = HandlePicture($name, 1, $field['errorname']);
				if($res === true)
					$sets[] = $name." = '#INTERNAL#'";
				else
				{
					Alert($res);
					$failed = true;
					$field["fail"] = true;
				}
				break;
		}*/
	}

	//Force theme names to be alphanumeric to avoid possible directory traversal exploits ~Dirbaio
	if(preg_match("/^[a-zA-Z0-9_]+$/", $_POST['theme']))
		$sets[] = "theme = '".SqlEscape($_POST['theme'])."'";

	if ((int)$_POST['powerlevel'] != $user['powerlevel']) $sets[] = "tempbantime = 0";

	$query .= join($sets, ", ")." WHERE id = ".$userid;

	if(!$failed && ($passwordEntered || !$needsPassword))
	{
		RawQuery($query);
		if($loguserid == $userid)
			$loguser = Fetch(Query("select * from {users} where id={0}", $loguserid));

		if(isset($_POST['powerlevel']) && $_POST['powerlevel'] != $user['powerlevel'])
			Karma();

		logAction('edituser', array('user2' => $user['id']));
		redirectAction("profile", $userid);
	}
}

//TODO $failedField
if($failed)
	Alert(__("There were errors saving your settings. Please fix them below and try again."));
else if($needsPassword)
{
	if($passwordFailed)
		Alert("Invalid password. Please try again.");
	echo "
		<form name=\"confirmform\" action=\"".actionLink("editprofile", $editUserMode?$userid:"")."\" method=\"post\">";
	foreach($_POST as $key => $val)
		echo "<input type=\"hidden\" name=\"".htmlspecialchars($key)."\" value=\"".htmlspecialchars($val)."\" />";
	echo "
		<table class=\"outline margin width50\">
			<tr class=\"header0\">
				<th colspan=\"2\">
					".__("Password confirmation")."
				</th>
			</tr>
			<tr>
				<td class=\"cell2\">
				</td>
				<td class=\"cell0\">
					".__("Please enter your current password to confirm the changes.")."
				</td>
			</tr>
			<tr>
				<td class=\"cell2\">
					<label for=\"currpassword\">".__("Password")."</label>
				</td>
				<td class=\"cell1\">
					<input type=\"password\" id=\"currpassword\" name=\"currpassword\" size=\"13\" maxlength=\"32\" />
				</td>
			</tr>
			<tr class=\"cell2\">
				<td></td>
				<td>
					<input type=\"submit\" name=\"actionlogin\" value=\"".__("Edit profile")."\" />
				</td>
			</tr>
		</table>
	</form>";
	
	throw new KillException();
}


function HandlePicture($field, $type, $errorname, $allowOversize = false)
{
	global $userid, $dataDir;
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

	if ($type == 0 && ($width > 300 || $height > 300))
		return __("That avatar is definitely too big. The avatar field is meant for an avatar, not a wallpaper.");

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
		$targetFile = $dataDir."avatars/".$userid;

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
		$targetFile = $dataDir."minipics/".$userid;

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
	global $sets, $salt, $user, $loguser, $loguserid;
	if(($_POST[$field] != "" || $_POST['repeat'.$field] != "") && $_POST['repeat'.$field] !== $_POST[$field])
	{
		return __("To change your password, you must type it twice without error.");
	}

	if($_POST[$field] != "" && $_POST['repeat'.$field] == "")
		$_POST[$field] = "";

	if($_POST[$field])
	{
		$newsalt = Shake();
		$sha = doHash($_POST[$field].$salt.$newsalt);
		$sets[] = "pss = '".$newsalt."'";
		$sets[] = "password = '".$sha."'";

		//Now logout all the sessions that aren't this one, for security.
		Query("DELETE FROM {sessions} WHERE id != {0} and user = {1}", doHash($_COOKIE['logsession'].$salt), $user["id"]);
	}
	
	return true;
}

function HandleDisplayname($field, $item)
{
	global $user;
	if(!IsReallyEmpty($_POST[$field]) || $_POST[$field] == $user['name'])
	{
		// unset the display name if it's really empty or the same as the login name.
		$_POST[$field] = "";
	}
	else
	{
		$dispCheck = FetchResult("select count(*) from {users} where id != {0} and (name = {1} or displayname = {1})", $user['id'], $_POST[$field]);
		if($dispCheck)
		{

			return format(__("The display name you entered, \"{0}\", is already taken."), SqlEscape($_POST[$field]));
		}
		else if(strpos($_POST[$field], ";") !== false)
		{
			$user['displayname'] = str_replace(";", "", $_POST[$field]);

			return __("The display name you entered cannot contain semicolons.");
		}
		else if($_POST[$field] !== ($_POST[$field] = preg_replace('/(?! )[\pC\pZ]/u', '', $_POST[$field])))
		{

			return __("The display name you entered cannot contain control characters.");
		}
	}
}

function HandleUsername($field, $item)
{
	global $user;
	if(!IsReallyEmpty($_POST[$field]))
		$_POST[$field] = $user[$field];

	$dispCheck = FetchResult("select count(*) from {users} where id != {0} and (name = {1} or displayname = {1})", $user['id'], $_POST[$field]);
	if($dispCheck)
	{

		return format(__("The login name you entered, \"{0}\", is already taken."), SqlEscape($_POST[$field]));
	}
	else if(strpos($_POST[$field], ";") !== false)
	{
		$user['name'] = str_replace(";", "", $_POST[$field]);

		return __("The login name you entered cannot contain semicolons.");
	}
	else if($_POST[$field] !== ($_POST[$field] = preg_replace('/(?! )[\pC\pZ]/u', '', $_POST[$field])))
	{

		return __("The login name you entered cannot contain control characters.");
	}
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

asort($themes);

$themeList .= "
	<div style=\"text-align: right;\">
		<input type=\"text\" placeholder=\"".__("Search")."\" id=\"search\" onkeyup=\"searchThemes(this.value);\" />
	</div>";

foreach($themes as $themeKey => $themeData)
{
	$themeName = $themeData["name"];
	$themeAuthor = $themeData["author"];

	$qCount = "select count(*) from {users} where theme='".$themeKey."'";
	$numUsers = FetchResult($qCount);

	$preview = "themes/".$themeKey."/preview.png";
	if(!is_file($preview))
		$preview = "img/nopreview.png";
	$preview = resourceLink($preview);

	$preview = "<img src=\"".$preview."\" alt=\"".$themeName."\" style=\"margin-bottom: 0.5em\" />";

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
	<div style=\"display: inline-block;\" class=\"theme\" title=\"{0}\">
		<input style=\"display: none;\" type=\"radio\" name=\"theme\" value=\"{3}\"{4} id=\"{3}\" onchange=\"ChangeTheme(this.value);\" />
		<label style=\"display: inline-block; clear: left; padding: 0.5em; {6} width: 260px; vertical-align: top\" onmousedown=\"void();\" for=\"{3}\">
			{2}<br />
			<strong>{0}</strong>
			{1}<br />
			{5}
		</label>
	</div>
",	$themeName, $byline, $preview, $themeKey, $selected, Plural($numUsers, "user"), "");
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
", $userid, $loguser['token']);

if(!isset($selectedTab))
{
	$selectedTab = "general";
	foreach($tabs as $id => $tab)
	{
		if(isset($_GET[$id]))
		{
			$selectedTab = $id;
			break;
		}
	}
}

echo "<div class=\"margin width0\" id=\"tabs\">";
foreach($tabs as $id => $tab)
{
	$selected = ($selectedTab == $id) ? " selected" : "";
	Write("
	<button id=\"{2}Button\" class=\"tab{1}\" onclick=\"showEditProfilePart('{2}');\">{0}</button>
	", $tab['name'], $selected, $id);
}
echo "
	</div>
	<form action=\"".actionLink("editprofile", $editUserMode?$userid:"")."\" method=\"post\" enctype=\"multipart/form-data\">
";

foreach($tabs as $id => $tab)
{
	if(isset($tab['page']))
		BuildPage($tab['page'], $id);
	elseif($id == "theme")
		Write("
	<table class=\"outline margin width100 eptable\" id=\"{0}\"{1}>
		<tr class=\"header0\"><th>".__("Theme")."</th></tr>
		<tr class=\"cell0\"><td class=\"themeselector\">{2}</td></tr>
	</table>
",	$id, ($id != $selectedTab) ? " style=\"display: none;\"" : "",
	$themeList);
}

echo "
	<div class=\"margin right width50\" id=\"button\">
		<input type=\"submit\" id=\"submit\" name=\"action\" value=\"".__("Edit profile")."\" />
		<input type=\"hidden\" name=\"key\" value=\"".$loguser['token']."\" />
	</div>
</form>";

function BuildPage($page, $id)
{
	global $selectedTab, $loguser, $fields;

	//TODO: This should be done in JS.
	//So that a user who doesn't have Javascript will see all the tabs.
	$display = ($id != $selectedTab) ? " style=\"display: none;\"" : "";

	$cellClass = 0;
	$output = "<table class=\"outline margin width50 eptable\" id=\"".$id."\"".$display.">\n";
	foreach($page as $pageID => $section)
	{
		$secClass = $section["class"];
		$output .= "<tr class=\"header0 $secClass\"><th colspan=\"2\">".$section['name']."</th></tr>\n";
		foreach($section['items'] as $field)
		{
			$item = $fields[$field];
			if(!$item) continue;

			$output .= "<tr class=\"cell$cellClass $secClass\" >\n";
			$output .= "<td>\n";
			if($item['type'] != "checkbox")
				$output .= "<label for=\"".$field."\">".$item['caption']."</label>\n";

			if(isset($item['hint']))
				$output .= "<img src=\"".resourceLink("img/icons/icon5.png")."\" title=\"".$item['hint']."\" alt=\"[?]\" />\n";
			$output .= "</td>\n";
			$output .= "<td>\n";

			if(isset($item['before']))
				$output .= " ".$item['before'];

			switch($item['type'])
			{
				case "label":
					if($item['html'])
						$output .= $item['value']."\n";
					else
						$output .= htmlspecialchars($item['value'])."\n";
					break;
				case "password":
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
					if((isset($item['negative']) && !$item['value']) || (!isset($item['negative']) && $item['value']))
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
				case "timezone":
					$output .= "<input type=\"text\" name=\"".$field."H\" size=\"2\" maxlength=\"3\" value=\"".(int)($item['value']/3600)."\" />\n";
					$output .= ":\n";
					$output .= "<input type=\"text\" name=\"".$field."M\" size=\"2\" maxlength=\"3\" value=\"".floor(abs($item['value']/60)%60)."\" />";
					break;
			}

			if(isset($item["presets"]))
			{
				$output .= __("or preset:")."\n";
				$options = "<option value=\"-1\">".__("[select]")."</option>";
				foreach($item['presets'] as $key => $val)
					$options .= format("<option value=\"{0}\">{1}</option>", $key, $val);
				$output .= format("<select class=\"preset\" id=\"{0}\" name=\"{0}\" size=\"1\" >\n{1}\n</select>\n", "_preset_".$field, $options);
			}
			
			if(isset($item['extra']))
				$output .= " ".$item['extra'];
			if(isset($item["fail"])) 
				$output .= "<br>[ERROR] ".htmlspecialchars($item["error"]);
			
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
	$votes = Query("select uid from {uservotes} where voter={0}", $userid);
	if(NumRows($votes))
		while($karmaChameleon = Fetch($votes))
			RecalculateKarma($karmaChameleon['uid']);
}

?>

<script type="text/javascript">
	$(".preset").change(function() {
		var preset = $(this);
		var id = preset.attr("id").substring(8);
		var val = preset.val();
		if(val != "-1")
			$("#"+id).val(val);
	});
</script>

