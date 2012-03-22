<?php
$genericInt = "int(11) NOT NULL DEFAULT '0'";
$smallerInt = "int(8) NOT NULL DEFAULT '0'";
$bool = "tinyint(1) NOT NULL DEFAULT '0'";
$notNull = " NOT NULL DEFAULT ''";
$text = "text DEFAULT ''"; //NOT NULL breaks in certain versions/settings.
$var128 = "varchar(128)".$notNull;
$var256 = "varchar(256)".$notNull;
$var1024 = "varchar(1024)".$notNull;
$AI = "int(11) NOT NULL AUTO_INCREMENT";
$keyID = "primary key (`id`)";

//SQL importer based on KusabaX installer
function Import($sqlFile)
{
	$handle = fopen($sqlFile, "r");
	$data = fread($handle, filesize($sqlFile));
	fclose($handle);

	$sqlData = explode("\n", $data);
	//Filter out the comments and empty lines...
	foreach ($sqlData as $key => $sql)
		if (strstr($sql, "--") || strlen($sql) == 0)
			unset($sqlData[$key]);
	$data = implode("",$sqlData);
	$sqlData = explode(";",$data);
	foreach($sqlData as $sql)
	{
		if(strlen($sql) === 0)
			continue;
		if(strstr($sql, "CREATE TABLE `"))
		{
			$pos1 = strpos($sql, '`');
			$pos2 = strpos($sql, '`', $pos1 + 1);
			$tableName = substr($sql, $pos1+1, ($pos2-$pos1)-1);
			print "<li>".$tableName."</li>";
		}
		$query = str_replace("SEMICOLON", ";", $sql);
		Query($query);
	}
}


function Upgrade()
{
	global $dbname;
	
	//Load the board tables.
	include("installSchema.php");
	
	//Allow plugins to add their own tables!
	$bucket = "installSchema"; include('lib/pluginloader.php');

	foreach($tables as $table => $tableSchema)
	{
		print "<li>";
		print $table."&hellip;";
		$tableStatus = Query("show table status from ".$dbname." like '".$table."'");
		$numRows = NumRows($tableStatus);
		if($numRows == 0)
		{
			print " creating&hellip;";
			$create = "create table `".$table."` (\n";
			$comma = "";
			foreach($tableSchema['fields'] as $field => $type)
			{
				$create .= $comma."\t`".$field."` ".$type;
				$comma = ",\n";
			}
			if(isset($tableSchema['special']))
				$create .= ",\n\t".$tableSchema['special'];
			$create .= "\n) ENGINE=MyISAM;";
			//print "<pre>".$create."</pre>";
			Query($create);
		}
		else
		{
			$primaryKey = "";
			$changes = 0;
			$foundFields = array();
			$scan = Query("show columns from `".$table."`");
			while($field = mysql_fetch_assoc($scan))
			{
				$fieldName = $field['Field'];
				$foundFields[] = $fieldName;
				$type = $field['Type'];
				if($field['Null'] == "NO")
					$type .= " NOT NULL";
				//if($field['Default'] != "")
				if($field['Extra'] == "auto_increment")
					$type .= " AUTO_INCREMENT";
				else
					$type .= " DEFAULT '".$field['Default']."'";
				if($field['Key'] == "PRI")
					$primaryKey = $fieldName;
				if(array_key_exists($fieldName, $tableSchema['fields']))
				{
					$wantedType = $tableSchema['fields'][$fieldName];
					if(strcasecmp($wantedType, $type))
					{
						print " \"".$fieldName."\" not correct type&hellip;";
						if($fieldName == "id")
						{
							print_r($field);
							print "{ ".$type." }";
						}
						Query("ALTER TABLE `".$table."` CHANGE `".$fieldName."` `".$fieldName."` ".$wantedType);
						$changes++;
					}
				}
			}
			foreach($tableSchema['fields'] as $fieldName => $type)
			{
				if(!in_array($fieldName, $foundFields))
				{
					print " \"".$fieldName."\" missing&hellip;";
					Query("ALTER TABLE `".$table."` ADD `".$fieldName."` ".$type);
					$changes++;
				}
			}
			if($changes == 0)
				print " OK.";
		}
		print "</li>";
	}
}
