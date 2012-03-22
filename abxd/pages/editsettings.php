<?php
//  AcmlmBoard XD - Board Settings editing page
//  Access: administrators
makeThemeArrays();

$title = __("Edit settings");

AssertForbidden("editSettings");

if($loguser['powerlevel'] < 3)
	Kill(__("You must be an administrator to edit the board settings."));
	
$key = hash('sha256', "{$loguserid},{$loguser['pss']},{$salt}");

if($_POST['action'] == __("Edit"))
{
	if((float)$_POST['uploaderCap'] <= 0)
		$_POST['uploaderCap'] = "0.25";
	if((float)$_POST['personalCap'] <= 0)
		$_POST['personalCap'] = "0.25";
	if((float)($_POST['uploaderMaxFileSize'] * 1024 * 1024) > IniValToBytes(ini_get("upload_max_filesize")) || (float)$_POST['uploaderMaxFileSize'] <= 0)
		$_POST['uploaderMaxFileSize'] = floor(IniValToBytes(ini_get("upload_max_filesize")) / 1024 / 1024);

	$hax = fopen("lib/settings.php", "w");
	fputs($hax, "<?php\n");
	fputs($hax, "//Generated and parsed by the Board Settings admin panel.\n");
	fputs($hax, "\n");
	fputs($hax, "//Settings\n");
	fputs($hax, "\$boardname = \"".prepare($_POST['boardname'])."\";\n");
	fputs($hax, "\$logoalt = \"".prepare($_POST['logoalt'])."\";\n");
	fputs($hax, "\$logotitle = \"".prepare($_POST['logotitle'])."\";\n");
	fputs($hax, "\$dateformat = \"".prepare($_POST['dateformat'])."\";\n");
	fputs($hax, "\$autoLockMonths = ".(int)$_POST['autoLockMonths'].";\n");
	fputs($hax, "\$warnMonths = ".(int)$_POST['warnMonths'].";\n");
	fputs($hax, "\$customTitleThreshold = ".(int)$_POST['customTitleThreshold'].";\n");
	fputs($hax, "\$viewcountInterval = ".(int)$_POST['viewcountInterval'].";\n");
	fputs($hax, "\$overallTidy = ".($_POST['overallTidy'] != "" ? 1 : 0).";\n");
	fputs($hax, "\$noAjax = ".($_POST['noAjax'] != "" ? 1 : 0).";\n");
	fputs($hax, "\$noGuestLayouts = ".($_POST['noGuestLayouts'] != "" ? 1 : 0).";\n");
	fputs($hax, "\$theWord = \"".prepare($_POST['theWord'])."\";\n");
	fputs($hax, "\$systemUser = ".(int)$_POST['systemUser'].";\n");
	fputs($hax, "\$minWords = ".(int)$_POST['minWords'].";\n");	
	fputs($hax, "\$minSeconds = ".(int)$_POST['minSeconds'].";\n");	
	fputs($hax, "\$uploaderCap = ".(float)$_POST['uploaderCap'].";\n");
	fputs($hax, "\$personalCap = ".(float)$_POST['personalCap'].";\n");
	fputs($hax, "\$uploaderMaxFileSize = ".(float)$_POST['uploaderMaxFileSize'].";\n");	
	fputs($hax, "\$uploaderWhitelist = \"".prepare($_POST['uploaderWhitelist'])."\";\n");
	fputs($hax, "\$mailResetFrom = \"".prepare($_POST['mailResetFrom'])."\";\n");
	fputs($hax, "\$lastPostsTimeLimit = ".(int)$_POST['lastPostsTimeLimit'].";\n");	
	fputs($hax, "\$defaultTheme = \"".prepare($_POST['defaulttheme'])."\";\n");
	fputs($hax, "\n");
	fputs($hax, "//Hacks\n");
	fputs($hax, "\$hacks['forcetheme'] = \"".prepare($_POST['theme'])."\";\n");
	fputs($hax, "\$hacks['themenames'] = ".(int)$_POST['names'].";\n");
	fputs($hax, "\n");
	fputs($hax, "//Profile Preview Post\n");
	fputs($hax, "\$profilePreviewText = \"".prepare($_POST['previewtext'], "\\\"")."\";\n");
	fputs($hax, "\n");
	fputs($hax, "//Meta\n");
	fputs($hax, "\$metaDescription = \"".prepare($_POST['metadesc'], "\\\"")."\";\n");
	fputs($hax, "\$metaKeywords = \"".prepare($_POST['metakeys'], "\\\"")."\";\n");
	fputs($hax, "\n");
	fputs($hax, "//RSS\n");
	fputs($hax, "\$feedname = \"".prepare($_POST['feedname'], "\\\"")."\";\n");
	fputs($hax, "\$rssblurb = \"".prepare($_POST['rssblurb'], "\\\"")."\";\n");
	fputs($hax, "\n");
	fputs($hax, "?>");
	fclose($hax);

	die(header("Location: ."));
}

$forcetheme = $hacks['forcetheme'];

//HAX
$themes_ = $themes;
$themes = array();
foreach ($themes_ as $key => $name)
	$themes[$themefiles[$key]] = $name;
$themelist[""] = __("[Disabled]");
$themelist = array_merge($themelist, $themes);
$names = array(__("[Disabled]"), __("Christmas"), __("Rainbow"), __("Anonymous"));

if(!function_exists('tidy_repair_string'))
	$tidyAvailable = "disabled=\"disabled\"";

write(
"
	<form action=\"".actionLink("editsettings")."\" method=\"post\">
		<table class=\"outline margin width75\">

			<tr class=\"header1\">
				<th colspan=\"2\">
					".__("Settings")."
				</th>
			</tr>
			<tr class=\"header0\">
				<th colspan=\"2\">
					".__("Various")."
				</th>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"boardname\">".__("Board name")."</label>
				</td>
				<td class=\"width75\">
					<input type=\"text\" id=\"boardname\" name=\"boardname\" value=\"{0}\" class=\"width75\" />
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"logoalt\">".__("Logo alt text")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"logoalt\" name=\"logoalt\" value=\"{1}\" class=\"width75\" />
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"logotitle\">".__("Logo title")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"logotitle\" name=\"logotitle\" value=\"{2}\" class=\"width75\" />
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"dateformat\">".__("Date/time format")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"dateformat\" name=\"dateformat\" value=\"{3}\" />
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"autoLockMonths\">".__("Autolock months")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"autoLockMonths\" name=\"autoLockMonths\" value=\"{4}\" />
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"warnMonths\">".__("Bump warning months")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"warnMonths\" name=\"warnMonths\" value=\"{5}\" />
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"viewcountInterval\">".__("Viewcount report interval")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"viewcountInterval\" name=\"viewcountInterval\" value=\"{6}\" />
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"customTitleThreshold\">".__("Custom title threshold")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"customTitleThreshold\" name=\"customTitleThreshold\" value=\"{7}\" />
				</td>
			</tr>
			<tr c lass=\"cell0\">
				<td>
					<label for=\"defaulttheme\">".__("Default theme")."</label>
				</td>
				<td>
					".makeSelect("defaulttheme", $defaultTheme, $themes)."
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					".__("Markup Cleanup")."
				</td>
				<td>
					<label>
						<input type=\"checkbox\" name=\"overallTidy\" {8} {14} />
						".__("Use HtmlTidy")."
					</label>
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					AJAX
				</td>
				<td>
					<label>
						<input type=\"checkbox\" name=\"noAjax\" {22} />
						".__("Disable AJAX refreshers")."
					</label>
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					".__("Guests")."
				</td>
				<td>
					<label>
						<input type=\"checkbox\" name=\"noGuestLayouts\" {23} />
						".__("Disable post layouts for guests")."
					</label>
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"theWord\">".__("Registration word")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"theWord\" name=\"theWord\" value=\"{9}\" />
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"systemUser\">".__("System user ID")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"systemUser\" name=\"systemUser\" value=\"{10}\" />
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"minWords\">".__("Minimal word count")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"minWords\" name=\"minWords\" value=\"{18}\" />
					<img src=\"img/icons/icon4.png\" title=\"".__("This is supposed to protect your board from the Happyface Guy, who floods a single smiley.")." ".__("Set this to zero to disable the check, at your own risk.")."\" alt=\"[!]\" />
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"minSeconds\">".__("Minimal seconds between posts")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"minSeconds\" name=\"minSeconds\" value=\"{19}\" />
					<img src=\"img/icons/icon4.png\" title=\"".__("This is supposed to protect your board from flooders by slowing them down.")." ".__("Set this to zero to disable the check, at your own risk.")."\" alt=\"[!]\" />
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"uploaderCap\">".__("Uploader size cap")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"uploaderCap\" name=\"uploaderCap\" value=\"{20}\" />
					MiB
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"personalCap\">".__("Uploader private cap")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"personalCap\" name=\"personalCap\" value=\"{25}\" />
					MiB
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"uploaderMaxFileSize\">".__("Uploader max file size")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"uploaderMaxFileSize\" name=\"uploaderMaxFileSize\" value=\"{29}\" />
					MiB <img src=\"img/icons/icon5.png\" title=\"".__("You cannot go past the php.ini setting, which is {30}. Exceeding this value or entering zero will reset the limit to {30}.")."\" alt=\"[?]\" />
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"uploaderWhitelist\">".__("Uploader whitelist")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"uploaderWhitelist\" name=\"uploaderWhitelist\" value=\"{21}\" class=\"width75\" />
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"mailResetFrom\">".__("Mail Reset sender")."</label>
				</td>
				<td>
					<input type=\"email\" id=\"mailResetFrom\" name=\"mailResetFrom\" value=\"{24}\" class=\"width75\" />
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"lastPostsTimeLimit\">".__("Time limit for Last Posts")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"lastPostsTimeLimit\" name=\"lastPostsTimeLimit\" value=\"{26}\" /> hours
				</td>
			</tr>
			
			<tr class=\"header0\">
				<th colspan=\"2\">
					".__("Hacks")."
				</th>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"theme\">".__("Theme")."</label>
				</td>
				<td>{11}
				</td>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"names\">".__("Names")."</label>
				</td>
				<td>{12}
				</td>
			</td>
			</tr>
			<tr class=\"header0\">
				<th colspan=\"2\">
					".__("Profile Preview Post")."
				</th>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"ppp\">".__("Text")."</label>
				</td>
				<td>
					<textarea id=\"ppp\" name=\"previewtext\" rows=\"8\" style=\"width: 98%;\">{15}</textarea>
				</td>
			</tr>
			<tr class=\"header0\">
				<th colspan=\"2\">
					".__("Meta")."
				</th>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"metadesc\">".__("Description")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"metadesc\" name=\"metadesc\" value=\"{16}\" class=\"width75\" />
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"metakeys\">".__("Keywords")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"metakeys\" name=\"metakeys\" value=\"{17}\" class=\"width75\" />
				</td>
			</tr>
			<tr class=\"header0\">
				<th colspan=\"2\">
					".__("RSS Feed")."
				</th>
			</tr>
			<tr class=\"cell1\">
				<td>
					<label for=\"feedname\">".__("Feed name")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"feedname\" name=\"feedname\" value=\"{27}\" class=\"width75\" />
				</td>
			</tr>
			<tr class=\"cell0\">
				<td>
					<label for=\"rssblurb\">".__("Blurb")."</label>
				</td>
				<td>
					<input type=\"text\" id=\"rssblurb\" name=\"rssblurb\" value=\"{28}\" class=\"width75\" />
				</td>
			</tr>
			<tr class=\"cell2\">
				<td>
				</td>
				<td>
					<input type=\"submit\" name=\"action\" value=\"".__("Edit")."\" />
					<input type=\"hidden\" name=\"key\" value=\"{31}\" />
				</td>
			</tr>
		</table>
	</form>
",	htmlspecialchars($boardname), htmlspecialchars($logoalt), htmlspecialchars($logotitle), htmlspecialchars($dateformat),
	$autoLockMonths, $warnMonths, $viewcountInterval, $customTitleThreshold,
	($overallTidy ? "checked=\"checked\"" : ""), htmlspecialchars($theWord), $systemUser,
	MakeSelect("theme",$forcetheme,$themelist), MakeSelect("names",$themenames,$names),
	0, $tidyAvailable, $profilePreviewText,
	htmlspecialchars($metaDescription), htmlspecialchars($metaKeywords), $minWords, $minSeconds, $uploaderCap,
	$uploaderWhitelist, ($noAjax ? "checked=\"checked\"" : ""), ($noGuestLayouts ? "checked=\"checked\"" : ""),
	$mailResetFrom, $personalCap, $lastPostsTimeLimit, $feedname, $rssblurb, $uploaderMaxFileSize,
	BytesToSize(IniValToBytes(ini_get("upload_max_filesize"))), $key
);

function MakeSelect($fieldName, $checkedIndex, $choicesList, $extras = "")
{
	$checks[$checkedIndex] = " selected=\"selected\"";
	foreach($choicesList as $key=>$val)
		$options .= format("
						<option value=\"{0}\"{1}>{2}</option>", $key, $checks[$key], $val);
	$result = format(
"
					<select id=\"{0}\" name=\"{0}\" size=\"1\" {1} >{2}
					</select>", $fieldName, $extras, $options);
	return $result;
}

function prepare($text)
{
	$s = str_replace("\\'", "'", addslashes($text));
	return $s;
}

//From the PHP Manual User Comments
function foldersize($path)
{
	$total_size = 0;
	$files = scandir($path);
	$files = array_slice($files, 2);
	foreach($files as $t)
	{
		if(is_dir($t))
		{
			//Recurse here
			$size = foldersize($path . "/" . $t);
			$total_size += $size;
		}
		else
		{
			$size = filesize($path . "/" . $t);
			$total_size += $size;
		}
	}
	return $total_size;
}

?>
