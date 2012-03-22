<?php
include("lib/common.php");

if(isset($_POST['action']) && $_POST['action'] == "Install")
{
	$isInUploader = FetchResult("SELECT COUNT(*) FROM uploader WHERE filename = 'layoutmaker.css' AND private = 1 AND user = ".$loguserid);
	if(!$isInUploader)
		Query("INSERT INTO uploader (filename, description, user, date, private) VALUES ('layoutmaker.css', 'Layoutmaker CSS', ".$loguserid.", ".time().", 1)");
	if(!is_dir("uploader/".$loguserid))
		mkdir("uploader/".$loguserid);
	$path = "uploader/".$loguserid."/layoutmaker.css";
	file_put_contents($path, $_POST['css']);
	$head = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$path."\" />\n".$_POST['header'];
	$foot = $_POST['footer'];
	Query("UPDATE users SET postheader = '".justEscape($head)."', signature = '".justEscape($foot)."' WHERE id = ".$loguserid);
	Report("[b]".$loguser['name']."[/] edited [b]".$user['name']."[/]'s layout with the layout maker. -> [g]#HERE#?uid=".$userid, 1);
	
	die(header("Location: profile.php?id=".$loguserid));
}

if(!isset($_GET['base']) || strpos($_GET['base'], ".") !== FALSE)
{
	include("layoutmaker/list.php");
	$offers = "";
	foreach($bases as $base)
	{
		$cellClass = ($cellClass + 1) % 2;
		$offers .= format(
"
		<tr class=\"cell{3}\">
			<td>
				<img src=\"layoutmaker/{0}.png\" />
			</td>
			<td>
				<a href=\"layoutmaker.php?base={0}\">{1}</a>
			</td>
			<td>
				{2}
			</td>
		</tr>
",	$base['name'], $base['title'], $base['description'], $cellClass);
	}
	write(
"
	<table class=\"outline margin width50\">
		<tr class=\"header1\">
			<th>Preview</th>
			<th>Name</th>
			<th>Description</th>
		</tr>
		{0}
	</table>
	<p>
	Obvious problems I'm well aware of that you shouldn't bother bugging me about:
	<ul>
		<li>Introduction page (this one) sucks balls.</li>
		<li>Sliders don't have buddy ranges</li>
		<li>The CGA palette in the color picker doesn't trigger the auto-update.</li>
		<li>Layouts only work on ABXD and Neritic Net, and not on Board2.</li>
	</ul>
	</p>
",	$offers);
	die();
}
else
{
	if(is_file("layoutmaker/".$_GET['base'].".php"))
		include("layoutmaker/".$_GET['base'].".php");
	else
		Kill("Invalid base layout.");
}

write(
"
	<link rel=\"stylesheet\" type=\"text/css\" href=\"layoutmaker/layoutmaker.css\" />
	<script type=\"text/javascript\" src=\"lib/jscolor/jscolor.js\"></script>
");

$parmFields = "";

foreach($parameters as $id => $settings)
{
	if($settings['hidden'])
		continue;

	$label = "<label>".($settings['label'] ? $settings['label']."</label>" : "");
	$extrasA = "";
	$extrasB = "";
	$input = "type=\"text\"";
	
	if($id == "ID")
		$settings['default'] = $loguserid;

	$pxem = "";
	if($settings['pxem'])
	{
		$pxem = format("
				<select id=\"{0}TYPE\" name=\"{0}TYPE\" onchange=\"Update()\">
					<option value=\"px\">px</option>
					<option value=\"em\">em</option>
				</select>
", $id);
	}
	
	switch($settings['type'])
	{
		case "int":
			$parmFields .= format(
"
		<tr>
			<td class=\"cell2\">
				{0}
			</td>
			<td class=\"cell1\">
				<input type=\"text\" id=\"{1}\" name=\"{1}\" value=\"{2}\" onchange=\"Update()\" />
				{3}
			</td>
		</tr>
", $label, $id, $settings['default'], $pxem);
			break;


		case "percentage":
			$settings['min'] = 0;
			$settings['max'] = 100;
			//Let RANGE do the rest:
		case "range":
			$parmFields .= format(
"
		<tr>
			<td class=\"cell2\">
				{0}
			</td>
			<td class=\"cell1\">
				<span><input type=\"range\" id=\"{1}\" name=\"{1}\" value=\"{2}\" min=\"{4}\" max=\"{5}\" onchange=\"this.parentNode.childNodes[1].value = this.value; Update();\" /><input type=\"number\" value=\"{2}\" min=\"{4}\" max=\"{5}\" size=\"5\" oninput=\"this.parentNode.childNodes[0].value = this.value; Update();\" />
				</span>
				{3}
			</td>
		</tr>
", $label, $id, $settings['default'], $pxem, $settings['min'], $settings['max']);
			break;


		case "select":
			$values = "";
			foreach($settings['values'] as $value)
			{
				$values .= format(
"
				<option value=\"{0}\"{1}>{0}</option>
",	$value, ($value == $settings['default'] ? " selected=\"selected\"" : "")
				);
			}

			$parmFields .= format(
"
		<tr>
			<td class=\"cell2\">
				{0}
			</td>
			<td class=\"cell1\">
				<select id=\"{1}\" name=\"{1}\" onchange=\"Update()\">
					{2}
				</select>
			</td>
		</tr>
", $label, $id, $values);
			break;


		case "color":
			$parmFields .= format(
"
		<tr>
			<td class=\"cell2\">
				{0}
			</td>
			<td class=\"cell1\">
				<input type=\"text\" id=\"{1}\" name=\"{1}\" value=\"{2}\" onchange=\"Update()\" class=\"color {hash:true,required:false}\" />
				{3}
			</td>
		</tr>
", $label, $id, $settings['default'], $pxem);
			break;


		case "textfx":
			$parmFields .= format(
"
		<tr>
			<td class=\"cell2\">
				{0}
			</td>
			<td class=\"cell1\">
				<select id=\"{1}\" name=\"{1}\" onchange=\"Update()\">
					<option value=\"0\">None</option>
					<option value=\"1\">Shadow</option>
					<option value=\"2\">Outline</option>
				</select>
			</td>
		</tr>
", $label, $id);
			break;

		default:
			$extras = "";
			if($settings['type'] == "background")
				$extras = "<button onclick=\"startBackgroundEditor('".$id."')\">&hellip;</button>";
			if($settings['type'] == "border")
				$extras = "<button onclick=\"startBorderEditor('".$id."')\">&hellip;</button>";
			$parmFields .= format(
"
		<tr>
			<td class=\"cell2\">
				{0}
			</td>
			<td class=\"cell1\">
				<input type=\"text\" style=\"width: 80%;\" id=\"{1}\" name=\"{1}\" value=\"{2}\" onchange=\"Update()\" />
				{3}
				{4}
			</td>
		</tr>
", $label, $id, $settings['default'], $pxem, $extras);
			break;

	}
}

write(
"
	<table style=\"width: 45%; float: left; margin-bottom: 1em;\">
		<tr class=\"header1\">
			<th colspan=\"2\">Parameters</th>
		</tr>
		{0}
	</table>",
	$parmFields
);

?>

<table id="backgroundmaker" class="subeditor">
	<tr class="header1">
		<th colspan="2">Backgrounds</th>
	</tr>
	<tr>
		<td class="cell2">
			<label>
				<input type="checkbox" id="background_usecolor" onchange="backgroundUpdate()" />
				Color
			</label>
		</td>
		<td class="cell1">
			<input type="text" class="color {hash:true,required:true}" id="background_color" onchange="backgroundUpdate()" />
		</td>
	</tr>

	<tr>
		<td class="cell2">
			<label>
				<input type="checkbox" id="background_useurl" onchange="backgroundUpdate()" />
				Image URL
			</label>
		</td>
		<td class="cell1">
			<input type="text" id="background_url" onchange="backgroundUpdate()" style="width: 98%;" />
		</td>
	</tr>

	<tr>
		<td class="cell2">
			<label>
				<input type="checkbox" id="background_userepeat" onchange="backgroundUpdate()" />
				Repeat
			</label>
		</td>
		<td class="cell1">
			<select id="background_repeat" onchange="backgroundUpdate()">
				<option value="repeat">Repeat</option>
				<option value="repeat-x">Horizontally</option>
				<option value="repeat-y">Vertically</option>
				<option value="no-repeat">Not at all</option>
			</select>
		</td>
	</tr>

	<tr>
		<td class="cell2" colspan="2">
			<label>
				<input type="checkbox" id="background_attach" onchange="backgroundUpdate()" />
				Fixed attachment
			</label>
		</td>
		<td>
		</td>
	</tr>

	<tr>
		<td class="cell2">
			<label>
				<input type="checkbox" id="background_useposition" onchange="backgroundUpdate()" />
				Position
			</label>
		</td>
		<td class="cell1">
			<select id="background_positionX" onchange="backgroundUpdate()">
				<option value="left">Left</option>
				<option value="center">Center</option>
				<option value="right">Right</option>
			</select>
			<select id="background_positionY" onchange="backgroundUpdate()">
				<option value="top">Top</option>
				<option value="center">Center</option>
				<option value="bottom">Bottom</option>
			</select>
		</td>
	</tr>

	<tr>
		<td colspan="2" class="cell2">
			<button onclick="closeSubeditor()">OK</button>
		</td>
	</tr>
</table>

<table id="bordermaker" class="subeditor">
	<tr class="header1">
		<th colspan="2">Borders</th>
	</tr>
	<tr>
		<td class="cell2">
			<label>
				<input type="checkbox" id="border_usewidth" onchange="backgroundUpdate()" />
				Width
			</label>
		</td>
		<td class="cell1">
			<input type="number" min="1" max="16" id="border_width" onchange="borderUpdate()" />
			<select id="border_widthunit" onchange="borderUpdate()">
				<option value="px">px</option>
				<option value="em">em</option>
			</select>
		</td>
	</tr>

	<tr>
		<td class="cell2">
			<label>
				<input type="checkbox" id="border_usestyle" onchange="backgroundUpdate()" />
				Style
			</label>
		</td>
		<td class="cell1">
			<select id="border_style" onchange="borderUpdate()">
				<option value="none">None</option>
				<option value="hidden">Hidden</option>
				<option value="dotted">Dotted</option>
				<option value="dashed">Dashed</option>
				<option value="solid">Solid</option>
				<option value="double">Double</option>
				<option value="groove">Groove</option>
				<option value="ridge">Ridge</option>
				<option value="inset">Inset</option>
				<option value="outset">Outset</option>
			</select>
		</td>
	</tr>

	<tr>
		<td class="cell2">
			<label>
				<input type="checkbox" id="border_usecolor" onchange="backgroundUpdate()"> 
					Color
			</label>
		</td>
		<td class="cell1">
			<input type="text" class="color {hash:true,required:true}" id="border_color" onchange="borderUpdate()" />
		</td>
	</tr>

	<tr>
		<td colspan="2" class="cell2">
			<button onclick="closeSubeditor()">OK</button>
		</td>
	</tr>
</table>

<div id="preview" style="clear: both;">
</div>



<script type="text/javascript">
var xmlHttp, loading;

function Update()
{
	if (loading)
		return;
		
	if(xmlHttp == null)
		xmlHttp = new XMLHttpRequest();
	
	var previewDiv = document.getElementById("preview");

	xmlHttp.onreadystatechange = function()
	{
		if (xmlHttp.readyState==4)
		{
			previewDiv.innerHTML = xmlHttp.responseText;
			loading = 0;
		}
	};
	
	var url = "lm_backend.php";
	var data = "<?php

$params = "base=".$_GET['base']."&";
foreach($parameters as $id => $settings)
{
	$params .= $id."=\" + encodeURIComponent(document.getElementById(\"".$id."\").value) + \"&"; 
}
$params = substr($params, 0, strlen($params) - 5).";";
print $params;

	?>
	
	loading = 1;
	xmlHttp.open("POST",url,true);
	xmlHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xmlHttp.send(data);
}

function init()
{
	var ranges = document.querySelectorAll('input[type=range]');
	for(var i = 0; i < ranges.length; i++)
	{
		ranges[i].onchange = ranges[i].nextSibling.onchange = function()
		{
			var current = this,
			sibling = (current.nextSibling) ? current.nextSibling : current.previousSibling;
			sibling.value = current.value;
			Update();
		}
	}
	Update();
}
window.onload = init;



var retfield;

function backgroundUpdate()
{
	var useColor = document.getElementById("background_usecolor");
	var useUrl = document.getElementById("background_useurl");
	var useRepeat = document.getElementById("background_userepeat");
	var usePosition = document.getElementById("background_useposition");
	var color = document.getElementById("background_color");
	var url = document.getElementById("background_url");
	var repeat = document.getElementById("background_repeat");
	var attach = document.getElementById("background_attach");
	var positionX = document.getElementById("background_positionX");
	var positionY = document.getElementById("background_positionY");

	var ret = "";

	if(useColor.checked)
		ret	+= color.value + " ";
	if(useUrl.checked)
		ret	+= "url(" + url.value + ") ";
	if(useRepeat.checked)
		ret	+= repeat.value + " ";
	if(attach.checked)
		ret	+= "fixed ";
	if(usePosition.checked)
		ret	+= positionX.value + " " + positionY.value + " ";

	retfield.value = ret.trim();
	Update();
}
function startBackgroundEditor(id)
{
	closeSubeditor();
	retfield = document.getElementById(id);

	var dummy = document.createElement("div");
	dummy.style['background'] = retfield.value;

	if(dummy.style['backgroundColor'])
	{
		document.getElementById("background_usecolor").checked = true;
		document.getElementById("background_color").value = dummy.style['backgroundColor'];
	}

	if(dummy.style['backgroundImage'] && dummy.style['backgroundImage'] != "none")
	{
		document.getElementById("background_useurl").checked = true;
		var url = dummy.style['backgroundImage'];
		document.getElementById("background_url").value = url.substr(5, url.length - 7);
	}

	if(dummy.style['backgroundRepeat'])
	{
		if(dummy.style['backgroundRepeat'] == "repeat")
		{
			document.getElementById("background_userepeat").checked = false;
			document.getElementById("background_repeat").value = "repeat";
		}
		else
		{
			document.getElementById("background_userepeat").checked = true;
			document.getElementById("background_repeat").value = dummy.style['backgroundRepeat'];
		}
	}

	if(dummy.style['backgroundAttachment'])
	{
		if(dummy.style['backgroundAttachment'] == "scroll")
			document.getElementById("background_attach").checked = false;
		else
			document.getElementById("background_attach").checked = true;
	}
	else
		document.getElementById("background_attach").checked = false;

	//TODO: Position is internally stored as "##% ##%".
	//alert(dummy.style['backgroundPosition']);

	document.getElementById("backgroundmaker").style.display = "table";
}

function borderUpdate()
{
	var useWidth = document.getElementById("border_usewidth");
	var useStyle = document.getElementById("border_usestyle");
	var useColor = document.getElementById("border_usecolor");
	var width = document.getElementById("border_width");
	var unit = document.getElementById("border_widthunit");
	var style = document.getElementById("border_style");
	var color = document.getElementById("border_color");

	var ret = "";

	if(useWidth.checked)
		ret	+= width.value + unit.value + " ";
	if(useStyle.checked)
		ret	+= style.value + " ";
	if(useColor.checked)
		ret	+= color.value + " ";

	retfield.value = ret.trim();
	Update();
}
function startBorderEditor(id)
{
	closeSubeditor();

	retfield = document.getElementById(id);

	var dummy = document.createElement("div");
	dummy.style['border'] = retfield.value;

	var widthVal = dummy.style['borderWidth'];
	var width = widthVal.substr(0, widthVal.length - 2);
	var pxem = widthVal.substr(widthVal.length - 2);
	document.getElementById("border_usewidth").checked = (width > 0);
	document.getElementById("border_width").value = width;
	document.getElementById("border_widthunit").value = pxem;

	var color = dummy.style['borderColor'];
	if(color && color != "currentColor" && color[0] == "#")
	{
		document.getElementById("border_usecolor").checked = true;
		document.getElementById("border_color").value = color;
	}
	else
	{
		document.getElementById("border_usecolor").checked = false;
		document.getElementById("border_color").value = "#000000";
	}

	var style = dummy.style['borderStyle'];
	document.getElementById("border_usestyle").checked = (style != "none");
	document.getElementById("border_style").value = style;

	document.getElementById("bordermaker").style.display = "table";
}

function closeSubeditor()
{
	document.getElementById("backgroundmaker").style['display'] = "none";
	document.getElementById("bordermaker").style['display'] = "none";
}
</script>