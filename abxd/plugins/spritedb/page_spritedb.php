<?php

	$fieldtypes = array('checkbox', 'value',  'signedvalue', 'list', 'binary', 'index');

	function trim_value(&$value) 
	{ 
		$value = trim($value); 
	}


    function myisint($int)
    {
        // First check if it's a numeric value as either a string or number
        if(is_numeric($int) === TRUE){
           
            // It's a number, but it has to be an integer
            if((int)$int == $int){

                return TRUE;
               
            // It's a number, but not an integer, so we fail
            }else{
           
                return FALSE;
            }
       
        // Not a number
        }else{
       
            return FALSE;
        }
    }
    
    
	function rep($str)
	{
		$order   = array("\r\n", "\n", "\r");
		$str = str_replace($order, "@", $str);
		$str = str_replace(";", ":", $str);
		return $str;
	}

	//0: Type
	//1: Nibbles
	//2: Value
	//3: Name
	//4: Notes
	
	function describefield($field, $html = true)
	{
		$field = explode(";", $field);
		$res = "";
		if($html)
			$res.= "<b>".htmlentities($field[3])."</b>: ";
		else
			$res.= htmlentities($field[3]).": ";

		$atnybble = "at nybble ".htmlentities($field[1]);
		switch ($field[0])
		{
			case 'checkbox':
				$res .= "checkbox $atnybble with mask ".htmlentities($field[2]);
				break;
			case 'value':
				$res .= "value $atnybble";
				break;
			case 'signedvalue':
				$res .= "signed value $atnybble";
				break;
			case 'index':
				$res .= "index at $atnybble";
				break;
			case 'binary':
				$res .= "binary editor $atnybble";
				break;
			case 'list':
				$listentries = str_replace("\n", ', ', rtrim($field[2]));
				$res .= "list $atnybble: ".htmlentities($listentries)."";
			break;
		}

		if ($field[4] != '') $res.= ". ".htmlentities($field[4])."";
		
		return $res;
	}

	function printSpriteRow($row)
	{
		global $wantGuest, $loguser;
		
		if ($row['known'] == 0)
			$class = 'unknownp';
		else if ($row['complete'] == 0)
			$class = 'knownp';
		else
			$class = 'completep';
		

		$onclick = "onclick=\"showsprite(this, ${row['id']});\"";
		if(($wantGuest || $loguser['powerlevel'] < 0))
			$onclick = "";

		print "<tr id='sprite${row['id']}' class='$class' $onclick>";
		print "<td>${row['id']}<a name='${row['id']}'></a> </td>";
		print "<td>".htmlentities($row['classid'])."</td>";
		print "<td><b>".htmlentities($row['name']).'</b>';

		$fields = array_filter(explode("\n", $row['fields']));
		
		if(count($fields) != 0)
		{
			print "<table style='width: 95%' class='data'>";
			foreach($fields as $field)
			{
				print "<tr><td>";
				print describefield($field);
				print "</td></tr>";
			}
			print "</table>";
		}

		print "</td>";
		
		$lastEditor = "-";
		if($row['lasteditor'] != 0)
		{
			$qUser = "select * from users where id=".$row['lasteditor'];
			$rUser = Query($qUser);
			if(NumRows($rUser))
			{
				$user = Fetch($rUser);
				$lastEditor = UserLink($user);
			}
			else
				$lastEditor = '??';
		}
			
		print "<td>".$lastEditor.'</td></tr>';
	}


	function printSpriteRowText($row)
	{
		global $wantGuest;

		print "Sprite ${row['id']}: ".htmlentities($row['name'])."\n";

		$fields = array_filter(explode("\n", $row['fields']));
		
		if(count($fields) != 0)
		{
			foreach($fields as $field)
			{
				print describefield($field, false);
				print "\n";
			}
		}

		$lastEditor = "-";
		if($row['lasteditor'] != 0)
		{
			$qUser = "select * from users where id=".$row['lasteditor'];
			$rUser = Query($qUser);
			if(NumRows($rUser))
			{
				$user = Fetch($rUser);
				$lastEditor = $user["name"];
			}
			else
				$lastEditor = '??';
		}
			
		print "Last edited by ".$lastEditor."\n";
	}

	if (get_magic_quotes_gpc()) {
		foreach ($_GET as $k => $v) $_GET[$k] = stripslashes($v);
		foreach ($_POST as $k => $v) $_POST[$k] = stripslashes($v);
	}

	if (isset($_GET['e']))
	{
		$_GET['act'] = 'edit';
		$_GET['id'] = $_GET['e'];
	}

	$actions = array('list', 'edit', 'modsprite', 'addfield', 'deletefield', 'getsprite', 'spriteplaintext');
	
	$action = "";
	if(isset($_GET['act']))
		$action = $_GET['act'];
	
	if (!in_array($action, $actions))
		$action = 'list';
    

	if($action != 'list')
		$ajaxPage = TRUE;
	
	$title = "Sprite Database";

    if($action != 'list' && $action != 'spriteplaintext' && ($wantGuest || $loguser['powerlevel'] < 0))
    {
    	die("You can't do that!");
    }
    
    $csrftoken = hash('sha256', $loguser['id'].$loguser['password']."spritedbtrololol", FALSE);
 
    
    switch ($action) {
		case 'list':
			MakeCrumbs(array("Main"=>"./", "Sprite Database"=>actionLink("spritedb")), $links);

			if (!is_numeric($_GET["go"]))
				unset($_GET["go"]);
				
			if(isset($_GET["go"]))
			{
				$spid = intval($_GET["go"]);
				print "<script type='text/javascript'>setTimeout(\"showsprite(document.getElementById('sprite$spid'), $spid);\", 300);</script>";
			}
			
			print "<table class='outline margin'><tr class='header1'><th>Sprite DB</th><th>By Category</th></tr><tr class='cell0'>";
			print "<td>Welcome to the Sprite DB! Here you will find information on how to use any sprite in NSMB. <br>You can also have this database in NSMB Editor if you're using the latest version!<br><br>All the registered users can also collaborate with the Sprite DB by sharing their sprite data findings. Click any sprite below to edit it.</td>";
			print "<td style='text-align:center;' rowspan='3'>";

			$entries = Query("select * from spritecategories order by ord, id");
			
			if(!isset($_GET["cat"]))
				print "All sprites<br>";
			else
				print actionLinkTag("All sprites", "spritedb")."<br>";
			print "<br>";
			while($entry = Fetch($entries))
			{
				if($_GET["cat"] == $entry["id"])
					print "${entry["name"]}<br>";
				else
					print actionLinkTag($entry["name"], "spritedb", "", "cat=".$entry["id"])."<br>";
			}

			$gg = "";
			if(isset($_GET["go"]))
				$gg = intval($_GET["go"]);
			print "<br><form action='".actionLink("spritedb")."' method='POST'>Go to sprite ID:<br><input type='text' maxlength='10' size='8' name='go' value='$gg'/><input type='submit' value='Go'/></form>";
			print "</td></tr><tr class='header1'><th>Status</th></tr><tr><td class='cell0'>";
			
			$gettotal = mysql_fetch_row(mysql_query('select count(id) from sprites'));
			$getoriginal = mysql_fetch_row(mysql_query('select count(id) from sprites where orig = 1'));
			$getknown = mysql_fetch_row(mysql_query('select count(id) from sprites where known = 1'));// and orig = 0'));
			$getcomplete = mysql_fetch_row(mysql_query('select count(id) from sprites where complete = 1'));// and orig = 0'));

			$c = intval($getcomplete[0]);
			$o = $getoriginal[0];
			$k = $getknown[0] - $c;
			$u = $gettotal[0] - $k - $c;// - $o;
			
			print "&nbsp;{$gettotal[0]} total";
			if ($c > 0) print "<div class='percentbar completep' style='width: {$c}px'>$c</div>";
			if ($k > 0) print "<div class='percentbar knownp' style='width: {$k}px'>$k</div>";
			if ($u > 0) print "<div class='percentbar unknownp' style='width: {$u}px'>$u</div>";
			
			print "<br><br>The above bar needs <i>more green</i>. HELP US MAKE IT HAPPEN! NOW!";
			print "</td></tr></table>";
			
			

			$cond = "";
			if(isset($_GET["cat"]))
				$cond = "where category=".intval($_GET["cat"]);
			if(isset($_POST["go"]))
				$_GET["go"] = $_POST["go"];
			if(isset($_GET["go"]))
			{
				if (!is_numeric($_GET["go"]))
					Kill('Invalid sprite ID');
				$cond = "where id=".intval($_GET["go"]);				
			}

			$getsprites = mysql_query("select * from sprites $cond order by id");
			$hasSprite = false;
			print "<table class='sprites'>
				<tr class='header1'>
					<th style='width: 60px'>ID</th>
					<th style='width: 60px'>Class ID</th>
					<th>Name</th>
					<th style='width: 150px'>Last edited by</th>
				</tr>";
			
			while ($row = mysql_fetch_array($getsprites))
			{
				printSpriteRow($row);
				$hasSprite = true;
			}
			
			if(!$hasSprite)
				print "<tr class='cell0'><td colspan='4'>No sprites found</td></tr>";

			print "</table>";

			print '<div class="footer">
		Awesome sprite database PHP script created by Treeki. Adapted to NSMB DS and integrated into ABXD by Dirbaio.
		</div>';
			break;

		case 'edit':
			$id = $_GET['id'];
			if (!is_numeric($id)) { die('Invalid sprite ID'); }
				$id = intval($id);

			$getsprite = mysql_query("select * from sprites where id = $id");
			if (mysql_num_rows($getsprite) == 0)
				die("Can't find the sprite ID $id");

			$sprite = mysql_fetch_array($getsprite);
			
			print "<form id='spritedataform' onsubmit='sendSpriteData(1); return false;' action='".actionLink("spritedb", "", "act=modsprite")."' method='post'>";
			print "<input type='hidden' name='token' value='$csrftoken'>";
			print "<input type='hidden' name='id' value='$id'>";
			print "<table class='outline margin width50'>";
			print "<tr class='header1'><th>Sprite Information</th></tr>";

			$n = htmlspecialchars($sprite['name']);
			print "<tr class='cell0'><td>Name: <input type='text' name='spritename' value=\"{$n}\" class='text'></td></tr>";

			$entries = Query("select * from spritecategories order by ord, id");
			$catlist = "";
			
			while($entry = Fetch($entries))
			{
				$sel = "";
				if($sprite['category'] == $entry["id"])
					$sel = "selected='selected'";
					
				$catlist .= "<option value='${entry["id"]}' $sel>${entry["name"]}</option>";
			}
			$catlist = "<select name='cat' size='1'>$catlist</select>";

			print "<tr class='cell0'><td>Category: $catlist</td></tr>";

			$known = ($sprite['known'] == 1) ? " checked='checked'" : '';
			$complete = ($sprite['complete'] == 1) ? " checked='checked'" : '';

			print "<tr class='cell1'><td><input type='checkbox' name='known' value='yes'{$known}> This sprite's purpose is known</td></tr>";
			print "<tr class='cell0'><td><input type='checkbox' name='complete' value='yes'{$complete}> This sprite's data is complete</td></tr>";

			print "<tr class='cell1'><td><b>Notes:</b><br>";
			$notes = htmlspecialchars($sprite['notes']);
			print "<textarea name='notes' rows='4' cols='60' style='font-family: Arial,sans-serif'>$notes</textarea></td></tr>";
			
			print "<tr class='cell1'><td><b>Data Files:</b><br>List here all files the sprite uses, like graphics, textures or models.<br>Enter them one by line, like this: \"/obj/A_block_ncg.bin\"<br>";
			$files = htmlspecialchars($sprite['files']);
			print "<textarea name='files' rows='4' cols='60' style='font-family: Arial,sans-serif'>$files</textarea></td></tr>";
			print "<tr class='cell0'><td><center>";

			print "<button type='button' onclick='sendSpriteData(0); return false;'>Save</button>";
			print "<button type='button' onclick='sendSpriteData(1); return false;'>Save and Close</button>";
			print "<span id='savestatus'></span> </center></td></tr>";			print "</table>";

			print "<br>";

			// fields
			print "<table id='spritefields' class='outline margin width50'>";
			print "<tr class='header1 nodrop nodrag'><th colspan='7'>Sprite Fields</th></tr>";
?>
<tr class='cell1 nodrop nodrag'><td colspan='7'>

  <b>Field Types and Descriptions:</b> (<a href='' onclick='showhidetypeinfo(); return false;'>Show/Hide Info</a>)
  <div id='typeinfo' style='display: none'>
  <br>
  <i>About nybbles:</i>
Nybbles are hex digits. The 12 nybbles in the sprite data are numbered from left to right, starting from 0: 0-11.<br>
If you want the data to be just one nybble, enter its number.<br />
If you want it to be multiple nybbles, enter them first-last. For example, 2-3<br/><br>
  <b>Checkbox</b>: Activates/deactivates a specific bit in a nybble. Set the data to the value of the bit that will be activated.<br><br>
  <b>Value</b>: A simple value which shows up as a spinner in the editor. The data field is used as an added offset for the value.<br><br>
  <b>Signed value</b>: Same as a value, but it has a sign: can be positive or negative, using the two's complement.<br>
  <b>List</b>: Lets you choose from a list of values. Enter the values into the data field: <i>0=Right, 1=Up+Right, 2=Up, 3=Up+Left</i><br><br>
  <b>Binary</b>: Shows 4 checkboxes in a row, one for every bit in the nybble.</br>
  <b>Index</b>: Do not use it. It's use was for NSMBW stuff like the rotation indexes.<br><br>
  </div>
  </tr></td>
<?php
			$fields = array_filter(explode("\n", $sprite['fields']));

			print "<tr class='cell1 nodrop nodrag'><td colspan='7'><center>";
			print "<button type='button'  onclick=\"addField($id, '$csrftoken'); return false;\">Add field</button>";
			print "</center></td></tr>";
			print "<tr class='header1 nodrop nodrag'><th>Drag</th><th>Title</th><th>Nybble</th><th>Type</th><th>Options/Offset/Mask</th><th>Comment</th><th></th></tr>";

			$i = 0;
			foreach($fields as $field)
			{

	//0: Type
	//1: Nibbles
	//2: Value
	//3: Name
	//4: Notes
				$field = explode(";", $field);
				$ftitle = htmlspecialchars($field[3]);
				$nybble = htmlspecialchars($field[1]);
				$type = $field[0];
				$data = htmlentities(str_replace("\n", ', ', rtrim($field[2])), 0);
				$comment = htmlentities($field[4]);
				print "<tr class='cell0'>";
				print "<td class='dragHandle'></td>";
				
				print "<td><input type='text' name='title[$i]' value=\"$ftitle\" size='10' class='text'></td>";
				print "<td><input type='text' name='nybble[$i]' value=\"$nybble\" size='6' class='text'></td>";
				print "<td><select name='type[$i]'>";
				foreach ($fieldtypes as $t)
				{
					print "<option value='$t'";
					if ($t == $type) print " selected='selected'";
						print ">$t</option>";
				}
				print "</select></td>";
				print "<td><input type='text' name='data[$i]' value=\"$data\" size='35' class='text'></td>";
				print "<td><input type='text' name='comment[$i]' value=\"$comment\" size='40' class='text'></td>";
				print "<td style='font-size: 10px'><button type='button' onclick='deleteField(this); return false;'>Delete</button></td>";
				print "</tr>";
				
				$i++;
			}
      
			print "</table>";
			print "</form>";

			print "<br>";

			print "<table class='outline margin width50'>";
			print "<tr class='header1'><th colspan='2'>Existing In-Game Sprite Data</th></tr>";
			
			$getdata = mysql_query("select DISTINCT level, data from origdata where sprite = $id");
			if (mysql_num_rows($getdata) == 0)
			{
				print "<tr class='cell0'> <td>This sprite isn't used in the original game.</td></tr>";
			}
			else
			{
				$datavalues = array();
				print "<tr class='cell0'><td style='width: 240px'><b>Level</b></td><td><b>Data</b></td></tr>";
				while ($row = mysql_fetch_row($getdata))
				{
					if (!isset($datavalues[$row[0]])) $datavalues[$row[0]] = array();
						$datavalues[$row[0]][] = $row[1];
					//print "<tr><td>$row[1]</td><td>$row[2]</td></tr>";
				}
				$c = 1;
				foreach ($datavalues as $data => $levels)
				{
					print "<tr class='cell$c'><td valign='top'>$data</td><td>".implode('<br/>', $levels)."</td></tr>";
					$c++;
					if($c == 2) $c = 0;
				}
			}
			print "</table>";
			
			break;

			
		case 'modsprite':

			$id = $_POST['id'];
			if (!is_numeric($id))
				die('Invalid sprite ID');

			if($_POST['token'] != $csrftoken) 
				die("Bad token!");
			
			$id = intval($id);
			$getsprite = mysql_query("select * from sprites where id = $id");
			if (mysql_num_rows($getsprite) == 0)
				die("Can't find the sprite ID $id");
			
			$sprite = mysql_fetch_row($getsprite);

			//Now let's validate all the data!

			// SAVE FLAGS
			$known = 0; 	if ($_POST['known'] == 'yes') $known = 1;
			$complete = 0; 	if ($_POST['complete'] == 'yes') $complete = 1;


			// Sprite name
			$spritename = mysql_real_escape_string($_POST['spritename']);


			// Sprite category: it must exist
			$cat = intval($_POST['cat']);
			$getcategory = mysql_query("select * from spritecategories where id = $cat");
			if (mysql_num_rows($getsprite) == 0)
				die("Can't find category ID $id");


			// Notes and files.
			$notes = mysql_real_escape_string($_POST['notes']);
			$files = mysql_real_escape_string($_POST['files']);
			
			
			//Fields
			$fields = "";

			
			
			array_walk($_POST['type'], 'trim_value');
			array_walk($_POST['nybble'], 'trim_value');
			array_walk($_POST['data'], 'trim_value');
			array_walk($_POST['title'], 'trim_value');
			array_walk($_POST['comment'], 'trim_value');
			
			if(in_array("New Field", $_POST['title']))
				die("Please no fields named \"New Field\"");

			if(in_array("", $_POST['title']))
				die("Please no fields without title");
			
			$usednybbles = array();
			if (isset($_POST['title']) && is_array($_POST['title']) && count($_POST['title']) > 0) {
				foreach ($_POST['title'] as $fid => $title)
				{
					$fieldtype = $_POST['type'][$fid];
					$fieldnybble = $_POST['nybble'][$fid];
					$fieldvalue = $_POST['data'][$fid];
					$fieldname = rep($_POST['title'][$fid]);
					$fieldnotes = rep($_POST['comment'][$fid]);
					
					if(!in_array($fieldtype, $fieldtypes))
						die("Invalid field type");
						
					$nybbles = explode("-", $fieldnybble);
					if(count($nybbles) != 1 && count($nybbles) != 2)
						die("Invalid nybble format (index count)");
						
					$nybblestart = $nybbles[0];
					$nybbleend = count($nybbles)==1?$nybbles[0]:$nybbles[1];
	
					if(!myisint($nybblestart)) die("Nybble index not a number");
					if(!myisint($nybbleend)) die("Nybble index not a number");

					if($nybblestart < 0 || $nybblestart > 11) die("Nybble index out of range");
					if($nybbleend < 0 || $nybbleend > 11) die("Nybble index out of range");

					for($i = $nybblestart; $i <= $nybbleend; $i++)
					{
						if($usednybbles[$i]) die("Two fields on the same nybble are not allowed");
						$usednybbles[$i] = true;
					}
					if($nybbleend < $nybblestart) die("Nybble range end is smaller than start");
					
					if($fieldtype == "list")
					{
						
					}
					else
					{
						if($fieldvalue != "") 
						{
							if(!myisint($fieldvalue)) die("Field value must be empty or an integer.");
						}
					}

					$fields .= "$fieldtype;$fieldnybble;$fieldvalue;$fieldname;$fieldnotes\n";
				}
			}
			
			$fields = mysql_real_escape_string($fields);

			mysql_query("update sprites set known=$known, category=$cat, complete=$complete, name='$spritename', notes='$notes', files='$files', fields='$fields', lasteditor='${loguser['id']}' where id=$id");

			die("Ok");
			break;

		case 'getsprite':
		
			$id = $_POST['id'];
			if (!is_numeric($id))
				die('Invalid sprite ID');

			$getsprites = mysql_query('select * from sprites where id = '.$id);
			while ($row = mysql_fetch_array($getsprites))
			{
				printSpriteRow($row);
			}
			die();
			break;
		case 'spriteplaintext':
		
			$id = $_GET['id'];
			if (!is_numeric($id))
				die('Invalid sprite ID');

			$getsprites = mysql_query('select * from sprites where id = '.$id);
			$found = false;
			while ($row = mysql_fetch_array($getsprites))
			{
				printSpriteRowText($row);
				$found = true;
			}
			if(!$found)
				die("Sprite not found");
			die();
			break;
	}

?>
