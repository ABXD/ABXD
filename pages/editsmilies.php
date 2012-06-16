<?php
//  AcmlmBoard XD - Smiley editing tool
//  Access: administrators only

AssertForbidden("editSmilies");

if($loguser['powerlevel'] < 3)
	Kill("You must be an administrator to edit the smiley table.");

MakeCrumbs(array(__("Admin") => actionLink("admin"), __("Edit smilies") => actionLink("editsmilies")), "");

$key = hash('sha256', "{$loguserid},{$loguser['pss']},{$salt}");
if (isset($_POST['action']) && $key != $_POST['key'])
	Kill(__("No."));

if($_POST['action'] == "Apply")
{
	$qSmilies = "select * from {$dbpref}smilies";
	$rSmilies = Query($qSmilies);
	$numSmilies = NumRows($rSmilies);

	for($i = 0; $i <= $numSmilies; $i++)
	{
		if($_POST['code_'.$i] != $_POST['oldcode_'.$i] || $_POST['image_'.$i] != $_POST['oldimage_'.$i])
		{
			if($_POST['code_'.$i] == "")
			{
				$act = "deleted";
				$qSmiley = "delete from {$dbpref}smilies where code='".$_POST['oldcode_'.$i]."'";
			} else
			{
				$act = "edited to \"".$_POST['image_'.$i]."\"";
				$qSmiley = "update {$dbpref}smilies set code='".$_POST['code_'.$i]."', image='".$_POST['image_'.$i]."' where code='".$_POST['oldcode_'.$i]."'";
			}
			$rSmiley = Query($qSmiley);
			$log .= "Smiley \"".$_POST['oldcode_'.$i]."\" ".$act.".<br />";
		}
	}

	if($_POST['code_add'] && $_POST['image_add'])
	{
		$qSmiley = "insert into {$dbpref}smilies (code,image) value ('".$_POST['code_add']."', '".$_POST['image_add']."')";
		$rSmiley = Query($qSmiley);
		$log .= "Smiley \"".$_POST['code_add']."\" added.<br />";
	}
	if($log)
		Alert($log,"Log");
}

$smileyList = "";
$qSmilies = "select * from {$dbpref}smilies";
$rSmilies = Query($qSmilies);
while($smiley = Fetch($rSmilies))
{
	$cellClass = ($cellClass+1) % 2;
	$i++;

	$smileyList .= format(
"
			<tr class=\"cell{0}\">
				<td>
					<input type=\"text\" name=\"code_{1}\" value=\"{2}\" />
					<input type=\"hidden\" name=\"oldcode_{1}\" value=\"{2}\" />
				</td>
				<td>
					<input type=\"text\" name=\"image_{1}\" value=\"{3}\" />
					<input type=\"hidden\" name=\"oldimage_{1}\" value=\"{3}\" />
					<img src=\"img/smilies/{4}\" alt=\"{5}\" title=\"{5}\">
				</td>
			</tr>
",	$cellClass, $i, $smiley['code'], $smiley['image'],
	$smiley['image'], $smiley['code']);
}

write(
"
	<div class=\"outline margin width25 faq\">
		To add, fill in both bottom fields and apply.<br />
		To edit, change either code or image fields to <em>not</em> match their hidden counterparts.
	</div>

	<form method=\"post\" action=\"".actionLink("editsmilies")."\">

		<table class=\"outline margin\" style=\"width: 30%;\">
			<tr class=\"header1\">
				<th>
					Code
				</th>
				<th>
					Image
				</th>
			</tr>
			{0}
			<tr class=\"header0\">
				<th colspan=\"2\">
					Add
				</th>
			</tr>
			<tr class=\"cell2\">
				<td>
					<input type=\"text\" name=\"code_add\" />
				</td>
				<td>
					<input type=\"text\" name=\"image_add\" />
				</td>
			</tr>

			<tr class=\"cell2\">
				<td>
				</td>
				<td>
					<input type=\"submit\" name=\"action\" value=\"Apply\" />
					<input type=\"hidden\" name=\"key\" value=\"{1}\" />
				</td>
			</tr>

		</table>
	</form>
", $smileyList, $key);

?>
